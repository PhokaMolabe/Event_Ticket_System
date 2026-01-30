<?php

class Order {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function create($data) {
        $validation = $this->validateOrderData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        try {
            return $this->db->transaction(function($db) use ($data) {
                $uuid = Ramsey\Uuid\Uuid::uuid4()->toString();
                $orderNumber = $this->generateOrderNumber();
                
                // Calculate totals
                $subtotal = 0;
                $taxAmount = 0;
                $discountAmount = 0;
                
                foreach ($data['items'] as $item) {
                    $subtotal += $item['subtotal'];
                    $taxAmount += $item['tax_amount'];
                    $discountAmount += $item['discount_amount'];
                }
                
                $serviceFee = $this->calculateServiceFee($subtotal);
                $totalAmount = $subtotal + $taxAmount - $discountAmount + $serviceFee;
                
                $orderData = [
                    'order_number' => $orderNumber,
                    'uuid' => $uuid,
                    'user_id' => $data['user_id'] ?? null,
                    'guest_email' => $data['guest_email'] ?? null,
                    'guest_name' => $data['guest_name'] ?? null,
                    'guest_phone' => $data['guest_phone'] ?? null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'service_fee' => $serviceFee,
                    'total_amount' => $totalAmount,
                    'currency' => $data['currency'] ?? 'USD',
                    'promo_code_id' => $data['promo_code_id'] ?? null,
                    'promo_code' => $data['promo_code'] ?? null,
                    'billing_address' => isset($data['billing_address']) ? json_encode($data['billing_address']) : null,
                    'notes' => $data['notes'] ?? null,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
                ];
                
                $orderId = $db->insert('orders', $orderData);
                
                // Create order items
                foreach ($data['items'] as $item) {
                    $orderItemData = [
                        'order_id' => $orderId,
                        'ticket_type_id' => $item['ticket_type_id'],
                        'event_id' => $item['event_id'],
                        'session_id' => $item['session_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'tax_amount' => $item['tax_amount'],
                        'discount_amount' => $item['discount_amount'],
                        'total_amount' => $item['total_amount']
                    ];
                    
                    $orderItemId = $db->insert('order_items', $orderItemData);
                    
                    // Update ticket availability
                    $this->updateTicketAvailability($item['event_id'], $item['ticket_type_id'], $item['quantity'], 'hold');
                }
                
                // Log activity
                $this->logActivity($data['user_id'], 'order_created', 'order', $orderId);
                
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'uuid' => $uuid,
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount,
                    'currency' => $orderData['currency']
                ];
            });
        } catch (Exception $e) {
            error_log("Order creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Order creation failed'];
        }
    }
    
    public function getById($id) {
        $sql = "
            SELECT o.*, 
                   u.first_name, u.last_name, u.email as user_email,
                   e.title as event_title
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN events e ON o.event_id = e.id
            WHERE o.id = ?
        ";
        
        $order = $this->db->fetch($sql, [$id]);
        if (!$order) {
            return null;
        }
        
        return $this->formatOrderData($order);
    }
    
    public function getByUuid($uuid) {
        $sql = "
            SELECT o.*, 
                   u.first_name, u.last_name, u.email as user_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.uuid = ?
        ";
        
        $order = $this->db->fetch($sql, [$uuid]);
        if (!$order) {
            return null;
        }
        
        return $this->formatOrderData($order);
    }
    
    public function getByOrderNumber($orderNumber) {
        $sql = "
            SELECT o.*, 
                   u.first_name, u.last_name, u.email as user_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.order_number = ?
        ";
        
        $order = $this->db->fetch($sql, [$orderNumber]);
        if (!$order) {
            return null;
        }
        
        return $this->formatOrderData($order);
    }
    
    public function getOrderItems($orderId) {
        $sql = "
            SELECT oi.*, 
                   tt.name as ticket_type_name,
                   e.title as event_title,
                   e.starts_at as event_starts_at
            FROM order_items oi
            LEFT JOIN ticket_types tt ON oi.ticket_type_id = tt.id
            LEFT JOIN events e ON oi.event_id = e.id
            WHERE oi.order_id = ?
        ";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }
    
    public function getTickets($orderId) {
        $sql = "
            SELECT t.*, 
                   tt.name as ticket_type_name,
                   e.title as event_title,
                   e.starts_at as event_starts_at,
                   v.name as venue_name
            FROM tickets t
            LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE t.order_id = ?
            ORDER BY t.id ASC
        ";
        
        return $this->db->fetchAll($sql, [$orderId]);
    }
    
    public function updateStatus($orderId, $status, $updatedBy = null) {
        try {
            $this->db->update('orders', 
                ['status' => $status], 
                'id = ?', 
                [$orderId]
            );
            
            // Log activity
            $this->logActivity($updatedBy, 'order_status_updated', 'order', $orderId, ['new_status' => $status]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Status update failed'];
        }
    }
    
    public function updatePaymentStatus($orderId, $paymentStatus, $paymentData = []) {
        try {
            $updateData = ['payment_status' => $paymentStatus];
            
            if ($paymentStatus === 'completed') {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
                $updateData['status'] = 'paid';
            }
            
            if (isset($paymentData['payment_method'])) {
                $updateData['payment_method'] = $paymentData['payment_method'];
            }
            
            if (isset($paymentData['payment_gateway'])) {
                $updateData['payment_gateway'] = $paymentData['payment_gateway'];
            }
            
            if (isset($paymentData['payment_reference'])) {
                $updateData['payment_reference'] = $paymentData['payment_reference'];
            }
            
            $this->db->update('orders', $updateData, 'id = ?', [$orderId]);
            
            // If payment completed, confirm tickets
            if ($paymentStatus === 'completed') {
                $this->confirmTickets($orderId);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Payment status update failed'];
        }
    }
    
    public function cancel($orderId, $reason = null, $cancelledBy = null) {
        try {
            return $this->db->transaction(function($db) use ($orderId, $reason, $cancelledBy) {
                // Update order status
                $db->update('orders', 
                    [
                        'status' => 'cancelled',
                        'cancelled_at' => date('Y-m-d H:i:s')
                    ], 
                    'id = ?', 
                    [$orderId]
                );
                
                // Cancel tickets
                $db->update('tickets', 
                    ['status' => 'cancelled'], 
                    'order_id = ?', 
                    [$orderId]
                );
                
                // Release held tickets
                $orderItems = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
                
                foreach ($orderItems as $item) {
                    $this->updateTicketAvailability($item['event_id'], $item['ticket_type_id'], $item['quantity'], 'release');
                }
                
                // Log activity
                $this->logActivity($cancelledBy, 'order_cancelled', 'order', $orderId, ['reason' => $reason]);
                
                return ['success' => true];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Order cancellation failed'];
        }
    }
    
    public function refund($orderId, $amount, $reason = null, $processedBy = null) {
        try {
            return $this->db->transaction(function($db) use ($orderId, $amount, $reason, $processedBy) {
                $order = $db->fetch("SELECT * FROM orders WHERE id = ?", [$orderId]);
                
                if (!$order) {
                    throw new Exception('Order not found');
                }
                
                $refundId = 'REF-' . strtoupper(Ramsey\Uuid\Uuid::uuid4()->toString());
                
                $refundData = [
                    'order_id' => $orderId,
                    'refund_id' => $refundId,
                    'amount' => $amount,
                    'currency' => $order['currency'],
                    'reason' => $reason,
                    'status' => 'pending',
                    'processed_by' => $processedBy
                ];
                
                $db->insert('refunds', $refundData);
                
                // Update order status if full refund
                if ($amount >= $order['total_amount']) {
                    $db->update('orders', 
                        ['status' => 'refunded'], 
                        'id = ?', 
                        [$orderId]
                    );
                } else {
                    $db->update('orders', 
                        ['status' => 'partially_refunded'], 
                        'id = ?', 
                        [$orderId]
                    );
                }
                
                // Log activity
                $this->logActivity($processedBy, 'order_refunded', 'order', $orderId, [
                    'amount' => $amount,
                    'reason' => $reason
                ]);
                
                return [
                    'success' => true,
                    'refund_id' => $refundId
                ];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Refund processing failed'];
        }
    }
    
    public function getUserOrders($userId, $status = null, $page = 1, $perPage = 20) {
        $where = ["o.user_id = ?"];
        $params = [$userId];
        
        if ($status) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT o.*, 
                   e.title as event_title,
                   e.starts_at as event_starts_at
            FROM orders o
            LEFT JOIN events e ON o.event_id = e.id
            WHERE {$whereClause}
            ORDER BY o.created_at DESC
        ";
        
        $result = $this->db->paginate($sql, $params, $page, $perPage);
        
        foreach ($result['data'] as &$order) {
            $order = $this->formatOrderData($order);
        }
        
        return $result;
    }
    
    public function search($query, $filters = [], $page = 1, $perPage = 20) {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($query)) {
            $where[] = "(o.order_number LIKE ? OR o.guest_email LIKE ? OR o.guest_name LIKE ?)";
            $searchTerm = "%{$query}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $where[] = "o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "o.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "o.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT o.*, 
                   u.first_name, u.last_name, u.email as user_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE {$whereClause}
            ORDER BY o.created_at DESC
        ";
        
        $result = $this->db->paginate($sql, $params, $page, $perPage);
        
        foreach ($result['data'] as &$order) {
            $order = $this->formatOrderData($order);
        }
        
        return $result;
    }
    
    private function confirmTickets($orderId) {
        $orderItems = $this->db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
        
        foreach ($orderItems as $item) {
            for ($i = 0; $i < $item['quantity']; $i++) {
                $ticketData = [
                    'uuid' => Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'ticket_number' => $this->generateTicketNumber(),
                    'order_id' => $orderId,
                    'order_item_id' => $item['id'],
                    'ticket_type_id' => $item['ticket_type_id'],
                    'event_id' => $item['event_id'],
                    'session_id' => $item['session_id'],
                    'attendee_name' => '', // Will be filled later
                    'price_paid' => $item['unit_price'],
                    'currency' => $item['currency'] ?? 'USD',
                    'qr_code' => '', // Will be generated later
                    'barcode' => '', // Will be generated later
                    'status' => 'confirmed'
                ];
                
                $this->db->insert('tickets', $ticketData);
            }
            
            // Update ticket availability
            $this->updateTicketAvailability($item['event_id'], $item['ticket_type_id'], $item['quantity'], 'sold');
        }
    }
    
    private function updateTicketAvailability($eventId, $ticketTypeId, $quantity, $operation) {
        $field = ($operation === 'hold') ? 'quantity_held' : 
                 (($operation === 'sold') ? 'quantity_sold' : 
                 (($operation === 'release') ? 'quantity_held' : null));
        
        if (!$field) return;
        
        $operator = ($operation === 'release') ? '-' : '+';
        
        $sql = "UPDATE ticket_types SET {$field} = {$field} {$operator} ? WHERE id = ? AND event_id = ?";
        $this->db->execute($sql, [$quantity, $ticketTypeId, $eventId]);
    }
    
    private function calculateServiceFee($subtotal) {
        $serviceFeeRate = $this->config->get('payment.service_fee_rate', 0.05);
        $minServiceFee = $this->config->get('payment.min_service_fee', 0);
        $maxServiceFee = $this->config->get('payment.max_service_fee', 50);
        
        $fee = $subtotal * $serviceFeeRate;
        $fee = max($fee, $minServiceFee);
        $fee = min($fee, $maxServiceFee);
        
        return round($fee, 2);
    }
    
    private function generateOrderNumber() {
        $prefix = 'ORD';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $random;
    }
    
    private function generateTicketNumber() {
        $prefix = 'TKT';
        $timestamp = date('YmdHis');
        $random = mt_rand(10000, 99999);
        
        return $prefix . $timestamp . $random;
    }
    
    private function validateOrderData($data) {
        $errors = [];
        
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'Order items are required';
        } else {
            foreach ($data['items'] as $index => $item) {
                if (empty($item['ticket_type_id'])) {
                    $errors[] = "Ticket type ID is required for item {$index}";
                }
                
                if (empty($item['quantity']) || $item['quantity'] < 1) {
                    $errors[] = "Valid quantity is required for item {$index}";
                }
                
                if (!isset($item['unit_price']) || $item['unit_price'] < 0) {
                    $errors[] = "Valid unit price is required for item {$index}";
                }
            }
        }
        
        if (!empty($data['user_id'])) {
            $user = $this->db->fetch("SELECT id FROM users WHERE id = ?", [$data['user_id']]);
            if (!$user) {
                $errors[] = 'Invalid user ID';
            }
        } else {
            if (empty($data['guest_email'])) {
                $errors[] = 'Guest email is required for guest orders';
            } elseif (!filter_var($data['guest_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid guest email format';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function formatOrderData($order) {
        if (isset($order['billing_address']) && $order['billing_address']) {
            $order['billing_address'] = json_decode($order['billing_address'], true);
        }
        
        return $order;
    }
    
    private function logActivity($userId, $action, $resourceType = null, $resourceId = null, $metadata = null) {
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => $metadata ? json_encode($metadata) : null
        ];
        
        $this->db->insert('user_activity_logs', $logData);
    }
}
