<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/PaymentTransaction.php';

class StripeWebhookHandler {
    private $db;
    private $config;
    private $orderModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
        $this->orderModel = new Order();
    }
    
    public function handle() {
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $webhookSecret = $this->config->get('payment.stripe.webhook_secret');
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            exit();
        }
        
        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
                
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
                
            case 'payment_intent.canceled':
                $this->handlePaymentCanceled($event->data->object);
                break;
                
            case 'charge.succeeded':
                $this->handleChargeSucceeded($event->data->object);
                break;
                
            case 'charge.failed':
                $this->handleChargeFailed($event->data->object);
                break;
                
            case 'charge.dispute.created':
                $this->handleDisputeCreated($event->data->object);
                break;
                
            default:
                echo json_encode(['received' => true]);
        }
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    }
    
    private function handlePaymentSucceeded($paymentIntent) {
        try {
            $this->db->transaction(function($db) use ($paymentIntent) {
                $orderId = $paymentIntent->metadata->order_id ?? null;
                
                if (!$orderId) {
                    error_log('Stripe webhook: No order_id in payment intent metadata');
                    return;
                }
                
                // Update payment transaction
                $transactionData = [
                    'order_id' => $orderId,
                    'transaction_id' => $paymentIntent->id,
                    'gateway' => 'stripe',
                    'gateway_transaction_id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount / 100, // Convert from cents
                    'currency' => strtoupper($paymentIntent->currency),
                    'status' => 'completed',
                    'payment_method' => $paymentIntent->payment_method_types[0] ?? 'card',
                    'gateway_response' => json_encode($paymentIntent),
                    'processed_at' => date('Y-m-d H:i:s', $paymentIntent->created)
                ];
                
                $db->insert('payment_transactions', $transactionData);
                
                // Update order
                $this->orderModel->updatePaymentStatus($orderId, 'completed', [
                    'payment_method' => 'credit_card',
                    'payment_gateway' => 'stripe',
                    'payment_reference' => $paymentIntent->id
                ]);
                
                // Log webhook event
                $this->logWebhookEvent('payment_intent.succeeded', $paymentIntent->id, $orderId);
            });
        } catch (Exception $e) {
            error_log('Stripe webhook error (payment succeeded): ' . $e->getMessage());
        }
    }
    
    private function handlePaymentFailed($paymentIntent) {
        try {
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                error_log('Stripe webhook: No order_id in payment intent metadata');
                return;
            }
            
            // Update payment transaction
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $paymentIntent->id,
                'gateway' => 'stripe',
                'gateway_transaction_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'failed',
                'payment_method' => $paymentIntent->payment_method_types[0] ?? 'card',
                'gateway_response' => json_encode($paymentIntent),
                'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed'
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            // Update order
            $this->orderModel->updatePaymentStatus($orderId, 'failed', [
                'payment_method' => 'credit_card',
                'payment_gateway' => 'stripe',
                'payment_reference' => $paymentIntent->id
            ]);
            
            // Log webhook event
            $this->logWebhookEvent('payment_intent.payment_failed', $paymentIntent->id, $orderId);
        } catch (Exception $e) {
            error_log('Stripe webhook error (payment failed): ' . $e->getMessage());
        }
    }
    
    private function handlePaymentCanceled($paymentIntent) {
        try {
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                error_log('Stripe webhook: No order_id in payment intent metadata');
                return;
            }
            
            // Update payment transaction
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $paymentIntent->id,
                'gateway' => 'stripe',
                'gateway_transaction_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'cancelled',
                'payment_method' => $paymentIntent->payment_method_types[0] ?? 'card',
                'gateway_response' => json_encode($paymentIntent)
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            // Update order
            $this->orderModel->updatePaymentStatus($orderId, 'cancelled', [
                'payment_method' => 'credit_card',
                'payment_gateway' => 'stripe',
                'payment_reference' => $paymentIntent->id
            ]);
            
            // Log webhook event
            $this->logWebhookEvent('payment_intent.canceled', $paymentIntent->id, $orderId);
        } catch (Exception $e) {
            error_log('Stripe webhook error (payment canceled): ' . $e->getMessage());
        }
    }
    
    private function handleChargeSucceeded($charge) {
        try {
            $paymentIntentId = $charge->payment_intent;
            
            // Find the transaction
            $transaction = $this->db->fetch(
                "SELECT * FROM payment_transactions WHERE gateway_transaction_id = ? AND gateway = 'stripe'",
                [$paymentIntentId]
            );
            
            if ($transaction) {
                // Update transaction with charge details
                $this->db->update('payment_transactions', [
                    'status' => 'completed',
                    'gateway_response' => json_encode($charge),
                    'processed_at' => date('Y-m-d H:i:s', $charge->created)
                ], 'id = ?', [$transaction['id']]);
                
                // Log webhook event
                $this->logWebhookEvent('charge.succeeded', $charge->id, $transaction['order_id']);
            }
        } catch (Exception $e) {
            error_log('Stripe webhook error (charge succeeded): ' . $e->getMessage());
        }
    }
    
    private function handleChargeFailed($charge) {
        try {
            $paymentIntentId = $charge->payment_intent;
            
            // Find the transaction
            $transaction = $this->db->fetch(
                "SELECT * FROM payment_transactions WHERE gateway_transaction_id = ? AND gateway = 'stripe'",
                [$paymentIntentId]
            );
            
            if ($transaction) {
                // Update transaction with charge details
                $this->db->update('payment_transactions', [
                    'status' => 'failed',
                    'gateway_response' => json_encode($charge),
                    'failure_reason' => $charge->failure_message ?? 'Charge failed'
                ], 'id = ?', [$transaction['id']]);
                
                // Log webhook event
                $this->logWebhookEvent('charge.failed', $charge->id, $transaction['order_id']);
            }
        } catch (Exception $e) {
            error_log('Stripe webhook error (charge failed): ' . $e->getMessage());
        }
    }
    
    private function handleDisputeCreated($dispute) {
        try {
            $chargeId = $dispute->charge;
            
            // Find the transaction
            $transaction = $this->db->fetch(
                "SELECT pt.*, o.user_id FROM payment_transactions pt 
                 LEFT JOIN orders o ON pt.order_id = o.id 
                 WHERE pt.gateway_response LIKE ? AND pt.gateway = 'stripe'",
                ['%' . $chargeId . '%']
            );
            
            if ($transaction) {
                // Create dispute record
                $disputeData = [
                    'order_id' => $transaction['order_id'],
                    'dispute_id' => $dispute->id,
                    'amount' => $dispute->amount / 100,
                    'currency' => strtoupper($dispute->currency),
                    'reason' => $dispute->reason,
                    'status' => 'opened',
                    'gateway' => 'stripe',
                    'gateway_response' => json_encode($dispute),
                    'created_at' => date('Y-m-d H:i:s', $dispute->created)
                ];
                
                $this->db->insert('disputes', $disputeData);
                
                // Log webhook event
                $this->logWebhookEvent('charge.dispute.created', $dispute->id, $transaction['order_id']);
                
                // Send notification to admin
                $this->sendDisputeNotification($transaction, $dispute);
            }
        } catch (Exception $e) {
            error_log('Stripe webhook error (dispute created): ' . $e->getMessage());
        }
    }
    
    private function logWebhookEvent($eventType, $eventId, $orderId = null) {
        $logData = [
            'event_type' => $eventType,
            'gateway_event_id' => $eventId,
            'order_id' => $orderId,
            'gateway' => 'stripe',
            'payload' => file_get_contents('php://input'),
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('webhook_logs', $logData);
    }
    
    private function sendDisputeNotification($transaction, $dispute) {
        // This would integrate with your notification system
        // Send email/SMS to admin about the dispute
        $message = sprintf(
            "Payment dispute created for Order #%d. Amount: $%.2f. Reason: %s",
            $transaction['order_id'],
            $dispute->amount / 100,
            $dispute->reason
        );
        
        error_log("DISPUTE NOTIFICATION: " . $message);
        
        // You could integrate with PHPMailer here to send actual emails
    }
}

// Handle the webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new StripeWebhookHandler();
    $handler->handle();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
