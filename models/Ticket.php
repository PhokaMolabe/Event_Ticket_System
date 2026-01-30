<?php

class Ticket {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function getById($id) {
        $sql = "
            SELECT t.*, 
                   tt.name as ticket_type_name,
                   e.title as event_title,
                   e.starts_at as event_starts_at,
                   e.ends_at as event_ends_at,
                   v.name as venue_name,
                   v.address as venue_address,
                   o.order_number,
                   o.user_id
            FROM tickets t
            LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN orders o ON t.order_id = o.id
            WHERE t.id = ?
        ";
        
        $ticket = $this->db->fetch($sql, [$id]);
        if (!$ticket) {
            return null;
        }
        
        return $this->formatTicketData($ticket);
    }
    
    public function getByUuid($uuid) {
        $sql = "
            SELECT t.*, 
                   tt.name as ticket_type_name,
                   e.title as event_title,
                   e.starts_at as event_starts_at,
                   v.name as venue_name,
                   o.order_number
            FROM tickets t
            LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN orders o ON t.order_id = o.id
            WHERE t.uuid = ?
        ";
        
        $ticket = $this->db->fetch($sql, [$uuid]);
        if (!$ticket) {
            return null;
        }
        
        return $this->formatTicketData($ticket);
    }
    
    public function getByTicketNumber($ticketNumber) {
        $sql = "
            SELECT t.*, 
                   tt.name as ticket_type_name,
                   e.title as event_title,
                   e.starts_at as event_starts_at,
                   v.name as venue_name,
                   o.order_number
            FROM tickets t
            LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN orders o ON t.order_id = o.id
            WHERE t.ticket_number = ?
        ";
        
        $ticket = $this->db->fetch($sql, [$ticketNumber]);
        if (!$ticket) {
            return null;
        }
        
        return $this->formatTicketData($ticket);
    }
    
    public function getByOrder($orderId) {
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
        
        $tickets = $this->db->fetchAll($sql, [$orderId]);
        return array_map([$this, 'formatTicketData'], $tickets);
    }
    
    public function getByEvent($eventId, $status = null) {
        $where = ["t.event_id = ?"];
        $params = [$eventId];
        
        if ($status) {
            $where[] = "t.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT t.*, 
                   tt.name as ticket_type_name,
                   o.order_number
            FROM tickets t
            LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
            LEFT JOIN orders o ON t.order_id = o.id
            WHERE {$whereClause}
            ORDER BY t.id ASC
        ";
        
        $tickets = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'formatTicketData'], $tickets);
    }
    
    public function getUserTickets($userId, $status = null, $page = 1, $perPage = 20) {
        $where = ["o.user_id = ?"];
        $params = [$userId];
        
        if ($status) {
            $where[] = "t.status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT t.*, 
                   tt.name as ticket_type_name,
                   e.title as event_title,
                   e.starts_at as event_starts_at,
                   e.ends_at as event_ends_at,
                   v.name as venue_name,
                   v.city as venue_city,
                   o.order_number
            FROM tickets t
            LEFT JOIN ticket_types tt ON t.ticket_type_id = tt.id
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN venues v ON e.venue_id = v.id
            LEFT JOIN orders o ON t.order_id = o.id
            WHERE {$whereClause}
            ORDER BY e.starts_at DESC, t.created_at DESC
        ";
        
        $result = $this->db->paginate($sql, $params, $page, $perPage);
        
        foreach ($result['data'] as &$ticket) {
            $ticket = $this->formatTicketData($ticket);
        }
        
        return $result;
    }
    
    public function updateAttendeeInfo($ticketId, $attendeeData) {
        $allowedFields = ['attendee_name', 'attendee_email', 'attendee_phone'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($attendeeData[$field])) {
                $updateData[$field] = trim($attendeeData[$field]);
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No valid fields to update'];
        }
        
        try {
            $this->db->update('tickets', $updateData, 'id = ?', [$ticketId]);
            
            // Generate QR code and barcode if not already generated
            $ticket = $this->getById($ticketId);
            if ($ticket && !$ticket['qr_code']) {
                $this->generateTicketCodes($ticketId);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Update failed'];
        }
    }
    
    public function checkIn($ticketId, $gateId = null, $deviceId = null, $operatorId = null) {
        try {
            return $this->db->transaction(function($db) use ($ticketId, $gateId, $deviceId, $operatorId) {
                $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
                
                if (!$ticket) {
                    return ['success' => false, 'error' => 'Ticket not found'];
                }
                
                if ($ticket['status'] === 'checked_in') {
                    return ['success' => false, 'error' => 'Ticket already checked in'];
                }
                
                if ($ticket['status'] !== 'confirmed') {
                    return ['success' => false, 'error' => 'Ticket not confirmed'];
                }
                
                // Check if event has started
                $event = $db->fetch("SELECT starts_at, ends_at FROM events WHERE id = ?", [$ticket['event_id']]);
                if (!$event) {
                    return ['success' => false, 'error' => 'Event not found'];
                }
                
                $now = time();
                $eventStart = strtotime($event['starts_at']);
                $eventEnd = strtotime($event['ends_at']);
                
                if ($now < $eventStart - 3600) { // Allow check-in 1 hour before event
                    return ['success' => false, 'error' => 'Event has not started yet'];
                }
                
                if ($now > $eventEnd) {
                    return ['success' => false, 'error' => 'Event has already ended'];
                }
                
                // Update ticket status
                $db->update('tickets', 
                    [
                        'status' => 'checked_in',
                        'checked_in_at' => date('Y-m-d H:i:s'),
                        'checked_in_by' => $operatorId,
                        'check_in_device_id' => $deviceId,
                        'gate_id' => $gateId
                    ], 
                    'id = ?', 
                    [$ticketId]
                );
                
                // Log check-in
                $logData = [
                    'ticket_id' => $ticketId,
                    'gate_id' => $gateId,
                    'device_id' => $deviceId,
                    'operator_id' => $operatorId,
                    'check_in_result' => 'success',
                    'scan_data' => $ticket['qr_code'] ?? $ticket['barcode']
                ];
                
                $db->insert('check_in_logs', $logData);
                
                // Log activity
                $this->logActivity($operatorId, 'ticket_checked_in', 'ticket', $ticketId, [
                    'gate_id' => $gateId,
                    'device_id' => $deviceId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Check-in successful',
                    'ticket_info' => [
                        'ticket_number' => $ticket['ticket_number'],
                        'attendee_name' => $ticket['attendee_name'],
                        'event_title' => $event['title'] ?? 'Unknown Event'
                    ]
                ];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Check-in failed'];
        }
    }
    
    public function checkInByCode($code, $gateId = null, $deviceId = null, $operatorId = null) {
        // Try to find ticket by QR code or barcode
        $sql = "
            SELECT * FROM tickets 
            WHERE (qr_code = ? OR barcode = ? OR ticket_number = ?)
            AND status = 'confirmed'
        ";
        
        $ticket = $this->db->fetch($sql, [$code, $code, $code]);
        
        if (!$ticket) {
            // Log failed attempt
            $this->logCheckInAttempt(null, $code, 'invalid', $gateId, $deviceId, $operatorId);
            return ['success' => false, 'error' => 'Invalid ticket code'];
        }
        
        return $this->checkIn($ticket['id'], $gateId, $deviceId, $operatorId);
    }
    
    public function transfer($ticketId, $toUserId, $fromUserId = null) {
        try {
            return $this->db->transaction(function($db) use ($ticketId, $toUserId, $fromUserId) {
                $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
                
                if (!$ticket) {
                    return ['success' => false, 'error' => 'Ticket not found'];
                }
                
                if ($ticket['status'] !== 'confirmed') {
                    return ['success' => false, 'error' => 'Only confirmed tickets can be transferred'];
                }
                
                if ($ticket['user_id'] && $ticket['user_id'] != $fromUserId) {
                    return ['success' => false, 'error' => 'You do not own this ticket'];
                }
                
                // Generate transfer token
                $transferToken = bin2hex(random_bytes(32));
                $transferExpires = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                // Update ticket
                $db->update('tickets', 
                    [
                        'status' => 'transferred',
                        'transfer_token' => $transferToken,
                        'transfer_expires_at' => $transferExpires,
                        'transferred_to' => $toUserId,
                        'transferred_at' => date('Y-m-d H:i:s')
                    ], 
                    'id = ?', 
                    [$ticketId]
                );
                
                // Log activity
                $this->logActivity($fromUserId, 'ticket_transferred', 'ticket', $ticketId, [
                    'to_user_id' => $toUserId,
                    'transfer_token' => $transferToken
                ]);
                
                return [
                    'success' => true,
                    'transfer_token' => $transferToken,
                    'expires_at' => $transferExpires
                ];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Transfer failed'];
        }
    }
    
    public function acceptTransfer($transferToken, $userId) {
        try {
            return $this->db->transaction(function($db) use ($transferToken, $userId) {
                $ticket = $db->fetch(
                    "SELECT * FROM tickets WHERE transfer_token = ? AND transferred_to = ?", 
                    [$transferToken, $userId]
                );
                
                if (!$ticket) {
                    return ['success' => false, 'error' => 'Invalid transfer token'];
                }
                
                if (strtotime($ticket['transfer_expires_at']) < time()) {
                    return ['success' => false, 'error' => 'Transfer has expired'];
                }
                
                // Update ticket
                $db->update('tickets', 
                    [
                        'status' => 'confirmed',
                        'attendee_id' => $userId,
                        'transfer_token' => null,
                        'transfer_expires_at' => null
                    ], 
                    'id = ?', 
                    [$ticket['id']]
                );
                
                // Log activity
                $this->logActivity($userId, 'transfer_accepted', 'ticket', $ticket['id']);
                
                return ['success' => true];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Transfer acceptance failed'];
        }
    }
    
    public function generateTicketCodes($ticketId) {
        $ticket = $this->getById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'error' => 'Ticket not found'];
        }
        
        try {
            // Generate QR code
            $qrCodeData = $ticket['uuid'];
            $qrCode = $this->generateQRCode($qrCodeData);
            
            // Generate barcode
            $barcodeData = $ticket['ticket_number'];
            $barcode = $this->generateBarcode($barcodeData);
            
            // Update ticket
            $this->db->update('tickets', 
                [
                    'qr_code' => $qrCode,
                    'barcode' => $barcode
                ], 
                'id = ?', 
                [$ticketId]
            );
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Code generation failed'];
        }
    }
    
    public function getCheckInStats($eventId) {
        $sql = "
            SELECT 
                COUNT(*) as total_tickets,
                COUNT(CASE WHEN t.status = 'confirmed' THEN 1 END) as confirmed_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN 1 END) as checked_in_tickets,
                COUNT(CASE WHEN t.status = 'used' THEN 1 END) as used_tickets,
                COUNT(CASE WHEN t.status = 'cancelled' THEN 1 END) as cancelled_tickets,
                COUNT(CASE WHEN t.status = 'refunded' THEN 1 END) as refunded_tickets
            FROM tickets t
            WHERE t.event_id = ?
        ";
        
        return $this->db->fetch($sql, [$eventId]);
    }
    
    public function getCheckInLogs($eventId, $date = null, $gateId = null) {
        $where = ["t.event_id = ?"];
        $params = [$eventId];
        
        if ($date) {
            $where[] = "DATE(cil.check_in_time) = ?";
            $params[] = $date;
        }
        
        if ($gateId) {
            $where[] = "cil.gate_id = ?";
            $params[] = $gateId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT cil.*, 
                   t.ticket_number,
                   t.attendee_name,
                   u.first_name as operator_name,
                   g.name as gate_name,
                   d.device_name
            FROM check_in_logs cil
            LEFT JOIN tickets t ON cil.ticket_id = t.id
            LEFT JOIN users u ON cil.operator_id = u.id
            LEFT JOIN check_in_gates g ON cil.gate_id = g.id
            LEFT JOIN check_in_devices d ON cil.device_id = d.id
            WHERE {$whereClause}
            ORDER BY cil.check_in_time DESC
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function generateQRCode($data) {
        $qrCode = new Endroid\QrCode\QrCode($data);
        $qrCode->setSize($this->config->get('qr_code.size', 300));
        $qrCode->setMargin($this->config->get('qr_code.margin', 10));
        
        $writer = new Endroid\QrCode\Writer\PngWriter();
        return base64_encode($writer->write($qrCode));
    }
    
    private function generateBarcode($data) {
        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        return base64_encode($generator->getBarcode($data, $generator::TYPE_CODE_128));
    }
    
    private function formatTicketData($ticket) {
        // Format dates
        if (isset($ticket['event_starts_at'])) {
            $ticket['event_starts_at_formatted'] = date('M j, Y g:i A', strtotime($ticket['event_starts_at']));
        }
        
        if (isset($ticket['event_ends_at'])) {
            $ticket['event_ends_at_formatted'] = date('M j, Y g:i A', strtotime($ticket['event_ends_at']));
        }
        
        if (isset($ticket['checked_in_at'])) {
            $ticket['checked_in_at_formatted'] = date('M j, Y g:i A', strtotime($ticket['checked_in_at']));
        }
        
        // Add status display
        $ticket['status_display'] = $this->getStatusDisplay($ticket['status']);
        
        return $ticket;
    }
    
    private function getStatusDisplay($status) {
        $displays = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'checked_in' => 'Checked In',
            'used' => 'Used',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'transferred' => 'Transferred'
        ];
        
        return $displays[$status] ?? $status;
    }
    
    private function logCheckInAttempt($ticketId, $code, $result, $gateId, $deviceId, $operatorId) {
        $logData = [
            'ticket_id' => $ticketId,
            'gate_id' => $gateId,
            'device_id' => $deviceId,
            'operator_id' => $operatorId,
            'check_in_result' => $result,
            'scan_data' => $code
        ];
        
        $this->db->insert('check_in_logs', $logData);
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
