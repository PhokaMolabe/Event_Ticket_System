<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../models/Order.php';

class PayPalWebhookHandler {
    private $db;
    private $config;
    private $orderModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
        $this->orderModel = new Order();
    }
    
    public function handle() {
        $headers = getallheaders();
        $payload = file_get_contents('php://input');
        $sigHeader = $headers['PAYPAL-AUTH-ALGO'] ?? '';
        $certId = $headers['PAYPAL-TRANSMISSION-ID'] ?? '';
        $transmissionId = $headers['PAYPAL-TRANSMISSION-SIG'] ?? '';
        $transmissionTime = $headers['PAYPAL-TRANSMISSION-TIME'] ?? '';
        
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($payload, $sigHeader, $certId, $transmissionId, $transmissionTime)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit();
        }
        
        $event = json_decode($payload, true);
        
        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit();
        }
        
        // Handle the event
        switch ($event['event_type']) {
            case 'PAYMENT.AUTHORIZATION.CREATED':
                $this->handleAuthorizationCreated($event);
                break;
                
            case 'PAYMENT.AUTHORIZATION.VOIDED':
                $this->handleAuthorizationVoided($event);
                break;
                
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handlePaymentCaptured($event);
                break;
                
            case 'PAYMENT.CAPTURE.DENIED':
                $this->handlePaymentDenied($event);
                break;
                
            case 'PAYMENT.SALE.COMPLETED':
                $this->handleSaleCompleted($event);
                break;
                
            case 'PAYMENT.SALE.DENIED':
                $this->handleSaleDenied($event);
                break;
                
            case 'PAYMENT.SALE.REFUNDED':
                $this->handleSaleRefunded($event);
                break;
                
            case 'CHECKOUT.ORDER.APPROVED':
                $this->handleOrderApproved($event);
                break;
                
            case 'CHECKOUT.ORDER.COMPLETED':
                $this->handleOrderCompleted($event);
                break;
                
            case 'BILLING.SUBSCRIPTION.CREATED':
                $this->handleSubscriptionCreated($event);
                break;
                
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $this->handleSubscriptionCancelled($event);
                break;
                
            default:
                echo json_encode(['received' => true]);
        }
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    }
    
    private function verifyWebhookSignature($payload, $sigHeader, $certId, $transmissionId, $transmissionTime) {
      
        return true; 
    }
    
    private function handleAuthorizationCreated($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if (!$orderId) {
                error_log('PayPal webhook: No order_id in custom field');
                return;
            }
            
            // Log authorization
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $resource['id'],
                'gateway' => 'paypal',
                'gateway_transaction_id' => $resource['id'],
                'amount' => floatval($resource['amount']['total']),
                'currency' => $resource['amount']['currency'],
                'status' => 'pending',
                'payment_method' => 'paypal',
                'gateway_response' => json_encode($resource)
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            $this->logWebhookEvent('PAYMENT.AUTHORIZATION.CREATED', $resource['id'], $orderId);
        } catch (Exception $e) {
            error_log('PayPal webhook error (authorization created): ' . $e->getMessage());
        }
    }
    
    private function handlePaymentCaptured($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if (!$orderId) {
                error_log('PayPal webhook: No order_id in custom field');
                return;
            }
            
            // Update payment transaction
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $resource['id'],
                'gateway' => 'paypal',
                'gateway_transaction_id' => $resource['id'],
                'amount' => floatval($resource['amount']['total']),
                'currency' => $resource['amount']['currency'],
                'status' => 'completed',
                'payment_method' => 'paypal',
                'gateway_response' => json_encode($resource),
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            // Update order
            $this->orderModel->updatePaymentStatus($orderId, 'completed', [
                'payment_method' => 'paypal',
                'payment_gateway' => 'paypal',
                'payment_reference' => $resource['id']
            ]);
            
            $this->logWebhookEvent('PAYMENT.CAPTURE.COMPLETED', $resource['id'], $orderId);
        } catch (Exception $e) {
            error_log('PayPal webhook error (payment captured): ' . $e->getMessage());
        }
    }
    
    private function handlePaymentDenied($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if (!$orderId) {
                error_log('PayPal webhook: No order_id in custom field');
                return;
            }
            
            // Update payment transaction
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $resource['id'],
                'gateway' => 'paypal',
                'gateway_transaction_id' => $resource['id'],
                'amount' => floatval($resource['amount']['total']),
                'currency' => $resource['amount']['currency'],
                'status' => 'failed',
                'payment_method' => 'paypal',
                'gateway_response' => json_encode($resource),
                'failure_reason' => $resource['status_details']['reason'] ?? 'Payment denied'
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            // Update order
            $this->orderModel->updatePaymentStatus($orderId, 'failed', [
                'payment_method' => 'paypal',
                'payment_gateway' => 'paypal',
                'payment_reference' => $resource['id']
            ]);
            
            $this->logWebhookEvent('PAYMENT.CAPTURE.DENIED', $resource['id'], $orderId);
        } catch (Exception $e) {
            error_log('PayPal webhook error (payment denied): ' . $e->getMessage());
        }
    }
    
    private function handleSaleCompleted($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if (!$orderId) {
                error_log('PayPal webhook: No order_id in custom field');
                return;
            }
            
            // Update payment transaction
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $resource['id'],
                'gateway' => 'paypal',
                'gateway_transaction_id' => $resource['id'],
                'amount' => floatval($resource['amount']['total']),
                'currency' => $resource['amount']['currency'],
                'status' => 'completed',
                'payment_method' => 'paypal',
                'gateway_response' => json_encode($resource),
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            // Update order
            $this->orderModel->updatePaymentStatus($orderId, 'completed', [
                'payment_method' => 'paypal',
                'payment_gateway' => 'paypal',
                'payment_reference' => $resource['id']
            ]);
            
            $this->logWebhookEvent('PAYMENT.SALE.COMPLETED', $resource['id'], $orderId);
        } catch (Exception $e) {
            error_log('PayPal webhook error (sale completed): ' . $e->getMessage());
        }
    }
    
    private function handleSaleDenied($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if (!$orderId) {
                error_log('PayPal webhook: No order_id in custom field');
                return;
            }
            
            // Update payment transaction
            $transactionData = [
                'order_id' => $orderId,
                'transaction_id' => $resource['id'],
                'gateway' => 'paypal',
                'gateway_transaction_id' => $resource['id'],
                'amount' => floatval($resource['amount']['total']),
                'currency' => $resource['amount']['currency'],
                'status' => 'failed',
                'payment_method' => 'paypal',
                'gateway_response' => json_encode($resource),
                'failure_reason' => $resource['status'] ?? 'Sale denied'
            ];
            
            $this->db->insert('payment_transactions', $transactionData);
            
            // Update order
            $this->orderModel->updatePaymentStatus($orderId, 'failed', [
                'payment_method' => 'paypal',
                'payment_gateway' => 'paypal',
                'payment_reference' => $resource['id']
            ]);
            
            $this->logWebhookEvent('PAYMENT.SALE.DENIED', $resource['id'], $orderId);
        } catch (Exception $e) {
            error_log('PayPal webhook error (sale denied): ' . $e->getMessage());
        }
    }
    
    private function handleSaleRefunded($event) {
        try {
            $resource = $event['resource'];
            $saleId = $resource['sale_id'];
            
            // Find the original transaction
            $transaction = $this->db->fetch(
                "SELECT * FROM payment_transactions WHERE gateway_transaction_id = ? AND gateway = 'paypal'",
                [$saleId]
            );
            
            if ($transaction) {
                // Create refund record
                $refundData = [
                    'order_id' => $transaction['order_id'],
                    'refund_id' => $resource['id'],
                    'amount' => floatval($resource['amount']['total']),
                    'currency' => $resource['amount']['currency'],
                    'status' => 'completed',
                    'refund_method' => 'paypal',
                    'gateway_refund_id' => $resource['id'],
                    'gateway_response' => json_encode($resource),
                    'processed_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert('refunds', $refundData);
                
                // Update order status if full refund
                $order = $this->db->fetch("SELECT * FROM orders WHERE id = ?", [$transaction['order_id']]);
                if ($order && floatval($resource['amount']['total']) >= $order['total_amount']) {
                    $this->db->update('orders', ['status' => 'refunded'], 'id = ?', [$transaction['order_id']]);
                } else {
                    $this->db->update('orders', ['status' => 'partially_refunded'], 'id = ?', [$transaction['order_id']]);
                }
                
                $this->logWebhookEvent('PAYMENT.SALE.REFUNDED', $resource['id'], $transaction['order_id']);
            }
        } catch (Exception $e) {
            error_log('PayPal webhook error (sale refunded): ' . $e->getMessage());
        }
    }
    
    private function handleOrderApproved($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if ($orderId) {
                $this->logWebhookEvent('CHECKOUT.ORDER.APPROVED', $resource['id'], $orderId);
            }
        } catch (Exception $e) {
            error_log('PayPal webhook error (order approved): ' . $e->getMessage());
        }
    }
    
    private function handleOrderCompleted($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if (!$orderId) {
                error_log('PayPal webhook: No order_id in custom field');
                return;
            }
            
            // Update order status
            $this->db->update('orders', 
                ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$orderId]
            );
            
            $this->logWebhookEvent('CHECKOUT.ORDER.COMPLETED', $resource['id'], $orderId);
        } catch (Exception $e) {
            error_log('PayPal webhook error (order completed): ' . $e->getMessage());
        }
    }
    
    private function handleSubscriptionCreated($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if ($orderId) {
                $this->logWebhookEvent('BILLING.SUBSCRIPTION.CREATED', $resource['id'], $orderId);
            }
        } catch (Exception $e) {
            error_log('PayPal webhook error (subscription created): ' . $e->getMessage());
        }
    }
    
    private function handleSubscriptionCancelled($event) {
        try {
            $resource = $event['resource'];
            $orderId = $this->extractOrderIdFromCustom($resource['custom'] ?? '');
            
            if ($orderId) {
                $this->logWebhookEvent('BILLING.SUBSCRIPTION.CANCELLED', $resource['id'], $orderId);
            }
        } catch (Exception $e) {
            error_log('PayPal webhook error (subscription cancelled): ' . $e->getMessage());
        }
    }
    
    private function extractOrderIdFromCustom($custom) {
        if (empty($custom)) {
            return null;
        }
        
        // Parse custom field - it could be JSON or simple string
        $data = json_decode($custom, true);
        if ($data && isset($data['order_id'])) {
            return $data['order_id'];
        }
        
        // Try to extract order_id from string
        if (strpos($custom, 'order_id:') !== false) {
            $parts = explode('order_id:', $custom);
            return trim($parts[1]);
        }
        
        // If it's just a number, treat it as order_id
        if (is_numeric($custom)) {
            return $custom;
        }
        
        return null;
    }
    
    private function logWebhookEvent($eventType, $eventId, $orderId = null) {
        $logData = [
            'event_type' => $eventType,
            'gateway_event_id' => $eventId,
            'order_id' => $orderId,
            'gateway' => 'paypal',
            'payload' => file_get_contents('php://input'),
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('webhook_logs', $logData);
    }
}

// Handle the webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new PayPalWebhookHandler();
    $handler->handle();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
