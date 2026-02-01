<?php

class CheckIn {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function createGate($data, $createdBy) {
        $validation = $this->validateGateData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        try {
            $gateData = [
                'venue_id' => $data['venue_id'],
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'gate_type' => $data['gate_type'] ?? 'entrance',
                'location' => $data['location'] ?? null,
                'access_rules' => isset($data['access_rules']) ? json_encode($data['access_rules']) : null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $createdBy
            ];
            
            $gateId = $this->db->insert('check_in_gates', $gateData);
            
            // Log activity
            $this->logActivity($createdBy, 'gate_created', 'check_in_gate', $gateId);
            
            return [
                'success' => true,
                'gate_id' => $gateId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Gate creation failed'];
        }
    }
    
    public function getGatesByVenue($venueId) {
        $sql = "
            SELECT g.*, 
                   u.first_name as created_by_name
            FROM check_in_gates g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.venue_id = ?
            ORDER BY g.name ASC
        ";
        
        $gates = $this->db->fetchAll($sql, [$venueId]);
        
        foreach ($gates as &$gate) {
            if ($gate['access_rules']) {
                $gate['access_rules'] = json_decode($gate['access_rules'], true);
            }
        }
        
        return $gates;
    }
    
    public function registerDevice($data, $createdBy) {
        $validation = $this->validateDeviceData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        try {
            $deviceData = [
                'gate_id' => $data['gate_id'],
                'device_name' => trim($data['device_name']),
                'device_identifier' => trim($data['device_identifier']),
                'device_type' => $data['device_type'] ?? 'mobile',
                'is_active' => $data['is_active'] ?? true,
                'offline_mode_enabled' => $data['offline_mode_enabled'] ?? true,
                'created_by' => $createdBy
            ];
            
            $deviceId = $this->db->insert('check_in_devices', $deviceData);
            
            // Log activity
            $this->logActivity($createdBy, 'device_registered', 'check_in_device', $deviceId);
            
            return [
                'success' => true,
                'device_id' => $deviceId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Device registration failed'];
        }
    }
    
    public function getDevicesByGate($gateId) {
        $sql = "
            SELECT d.*, 
                   g.name as gate_name,
                   u.first_name as created_by_name
            FROM check_in_devices d
            LEFT JOIN check_in_gates g ON d.gate_id = g.id
            LEFT JOIN users u ON d.created_by = u.id
            WHERE d.gate_id = ?
            ORDER BY d.device_name ASC
        ";
        
        return $this->db->fetchAll($sql, [$gateId]);
    }
    
    public function checkInTicket($ticketId, $gateId, $deviceId, $operatorId, $scanData = null) {
        try {
            return $this->db->transaction(function($db) use ($ticketId, $gateId, $deviceId, $operatorId, $scanData) {
                // Get ticket details
                $ticket = $db->fetch("
                    SELECT t.*, e.starts_at, e.ends_at, v.id as venue_id
                    FROM tickets t
                    LEFT JOIN events e ON t.event_id = e.id
                    LEFT JOIN venues v ON e.venue_id = v.id
                    WHERE t.id = ?
                ", [$ticketId]);
                
                if (!$ticket) {
                    $result = ['success' => false, 'error' => 'Ticket not found', 'check_in_result' => 'invalid'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                // Validate ticket status
                if ($ticket['status'] !== 'confirmed') {
                    $result = ['success' => false, 'error' => 'Ticket not confirmed', 'check_in_result' => 'invalid'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                // Check if already checked in
                if ($ticket['status'] === 'checked_in') {
                    $result = ['success' => false, 'error' => 'Ticket already checked in', 'check_in_result' => 'duplicate'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                // Validate event timing
                $now = time();
                $eventStart = strtotime($ticket['starts_at']);
                $eventEnd = strtotime($ticket['ends_at']);
                
                $checkInWindow = $this->config->get('checkin.check_in_window', 3600); // 1 hour before event
                
                if ($now < $eventStart - $checkInWindow) {
                    $result = ['success' => false, 'error' => 'Event has not started yet', 'check_in_result' => 'invalid'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                if ($now > $eventEnd) {
                    $result = ['success' => false, 'error' => 'Event has already ended', 'check_in_result' => 'expired'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                // Validate gate access
                $gate = $db->fetch("SELECT * FROM check_in_gates WHERE id = ?", [$gateId]);
                if (!$gate || !$gate['is_active']) {
                    $result = ['success' => false, 'error' => 'Invalid gate', 'check_in_result' => 'invalid'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                // Check gate access rules
                if (!$this->validateGateAccess($ticket, $gate)) {
                    $result = ['success' => false, 'error' => 'Access denied for this gate', 'check_in_result' => 'invalid'];
                    $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData);
                    return $result;
                }
                
                // Update ticket status
                $db->update('tickets', [
                    'status' => 'checked_in',
                    'checked_in_at' => date('Y-m-d H:i:s'),
                    'checked_in_by' => $operatorId,
                    'check_in_device_id' => $deviceId,
                    'gate_id' => $gateId
                ], 'id = ?', [$ticketId]);
                
                // Update device last sync
                $db->update('check_in_devices', [
                    'last_sync_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$deviceId]);
                
                // Log successful check-in
                $this->logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, 
                    ['success' => true, 'check_in_result' => 'success'], $scanData);
                
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
                        'event_title' => $ticket['event_title'] ?? 'Unknown Event'
                    ]
                ];
            });
        } catch (Exception $e) {
            error_log("Check-in failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Check-in failed'];
        }
    }
    
    public function checkInByCode($code, $gateId, $deviceId, $operatorId) {
        // Try to find ticket by QR code, barcode, or ticket number
        $sql = "
            SELECT t.*, e.title as event_title
            FROM tickets t
            LEFT JOIN events e ON t.event_id = e.id
            WHERE (t.qr_code = ? OR t.barcode = ? OR t.ticket_number = ?)
            AND t.status = 'confirmed'
        ";
        
        $ticket = $this->db->fetch($sql, [$code, $code, $code]);
        
        if (!$ticket) {
            $result = ['success' => false, 'error' => 'Invalid ticket code', 'check_in_result' => 'invalid'];
            $this->logCheckInAttempt(null, $gateId, $deviceId, $operatorId, $result, $code);
            return $result;
        }
        
        return $this->checkInTicket($ticket['id'], $gateId, $deviceId, $operatorId, $code);
    }
    
    public function getCheckInStats($eventId, $date = null) {
        $where = ["t.event_id = ?"];
        $params = [$eventId];
        
        if ($date) {
            $where[] = "DATE(cil.check_in_time) = ?";
            $params[] = $date;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT 
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'confirmed' THEN 1 END) as confirmed_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN 1 END) as checked_in_tickets,
                COUNT(CASE WHEN t.status = 'used' THEN 1 END) as used_tickets,
                COUNT(CASE WHEN t.status = 'cancelled' THEN 1 END) as cancelled_tickets,
                COUNT(CASE WHEN t.status = 'refunded' THEN 1 END) as refunded_tickets,
                COUNT(CASE WHEN cil.check_in_result = 'success' THEN 1 END) as successful_check_ins,
                COUNT(CASE WHEN cil.check_in_result = 'duplicate' THEN 1 END) as duplicate_attempts,
                COUNT(CASE WHEN cil.check_in_result = 'invalid' THEN 1 END) as invalid_attempts
            FROM tickets t
            LEFT JOIN check_in_logs cil ON t.id = cil.ticket_id
            WHERE {$whereClause}
        ";
        
        return $this->db->fetch($sql, $params);
    }
    
    public function getCheckInLogs($eventId, $filters = []) {
        $where = ["t.event_id = ?"];
        $params = [$eventId];
        
        if (!empty($filters['date'])) {
            $where[] = "DATE(cil.check_in_time) = ?";
            $params[] = $filters['date'];
        }
        
        if (!empty($filters['gate_id'])) {
            $where[] = "cil.gate_id = ?";
            $params[] = $filters['gate_id'];
        }
        
        if (!empty($filters['device_id'])) {
            $where[] = "cil.device_id = ?";
            $params[] = $filters['device_id'];
        }
        
        if (!empty($filters['operator_id'])) {
            $where[] = "cil.operator_id = ?";
            $params[] = $filters['operator_id'];
        }
        
        if (!empty($filters['result'])) {
            $where[] = "cil.check_in_result = ?";
            $params[] = $filters['result'];
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
        
        $limit = $filters['limit'] ?? 1000;
        $offset = $filters['offset'] ?? 0;
        
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getRealTimeStats($eventId) {
        $sql = "
            SELECT 
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN 1 END) as checked_in_count,
                COUNT(CASE WHEN t.status = 'confirmed' THEN 1 END) as pending_count,
                COUNT(CASE WHEN cil.check_in_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as last_hour_checkins,
                COUNT(CASE WHEN cil.check_in_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 END) as last_15min_checkins
            FROM tickets t
            LEFT JOIN check_in_logs cil ON t.id = cil.ticket_id AND cil.check_in_result = 'success'
            WHERE t.event_id = ?
        ";
        
        $stats = $this->db->fetch($sql, [$eventId]);
        
        // Calculate check-in rate
        $stats['check_in_rate'] = $stats['total_tickets'] > 0 ? 
            round(($stats['checked_in_count'] / $stats['total_tickets']) * 100, 2) : 0;
        
        return $stats;
    }
    
    public function getGatePerformance($gateId, $date = null) {
        $where = ["cil.gate_id = ?"];
        $params = [$gateId];
        
        if ($date) {
            $where[] = "DATE(cil.check_in_time) = ?";
            $params[] = $date;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT 
                COUNT(*) as total_scans,
                COUNT(CASE WHEN cil.check_in_result = 'success' THEN 1 END) as successful_scans,
                COUNT(CASE WHEN cil.check_in_result = 'duplicate' THEN 1 END) as duplicate_scans,
                COUNT(CASE WHEN cil.check_in_result = 'invalid' THEN 1 END) as invalid_scans,
                MIN(cil.check_in_time) as first_scan,
                MAX(cil.check_in_time) as last_scan,
                COUNT(DISTINCT cil.operator_id) as unique_operators,
                COUNT(DISTINCT cil.device_id) as unique_devices
            FROM check_in_logs cil
            WHERE {$whereClause}
        ";
        
        return $this->db->fetch($sql, $params);
    }
    
    public function syncOfflineData($deviceId, $offlineLogs) {
        try {
            return $this->db->transaction(function($db) use ($deviceId, $offlineLogs) {
                $syncedCount = 0;
                $errorCount = 0;
                
                foreach ($offlineLogs as $log) {
                    try {
                        // Check if log already exists
                        $existing = $db->fetch("
                            SELECT id FROM check_in_logs 
                            WHERE ticket_id = ? AND check_in_time = ?
                        ", [$log['ticket_id'], $log['check_in_time']]);
                        
                        if (!$existing) {
                            $db->insert('check_in_logs', [
                                'ticket_id' => $log['ticket_id'],
                                'gate_id' => $log['gate_id'],
                                'device_id' => $deviceId,
                                'operator_id' => $log['operator_id'],
                                'check_in_time' => $log['check_in_time'],
                                'check_in_result' => $log['check_in_result'],
                                'scan_data' => $log['scan_data'] ?? null,
                                'notes' => $log['notes'] ?? null
                            ]);
                            
                            $syncedCount++;
                        }
                    } catch (Exception $e) {
                        $errorCount++;
                        error_log("Failed to sync offline log: " . $e->getMessage());
                    }
                }
                
                // Update device last sync
                $db->update('check_in_devices', [
                    'last_sync_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$deviceId]);
                
                return [
                    'success' => true,
                    'synced_count' => $syncedCount,
                    'error_count' => $errorCount
                ];
            });
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Sync failed'];
        }
    }
    
    private function validateGateData($data) {
        $errors = [];
        
        if (empty($data['venue_id'])) {
            $errors[] = 'Venue ID is required';
        }
        
        if (empty($data['name'])) {
            $errors[] = 'Gate name is required';
        }
        
        if (!empty($data['gate_type']) && !in_array($data['gate_type'], ['entrance', 'exit', 'vip', 'staff'])) {
            $errors[] = 'Invalid gate type';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function validateDeviceData($data) {
        $errors = [];
        
        if (empty($data['gate_id'])) {
            $errors[] = 'Gate ID is required';
        }
        
        if (empty($data['device_name'])) {
            $errors[] = 'Device name is required';
        }
        
        if (empty($data['device_identifier'])) {
            $errors[] = 'Device identifier is required';
        }
        
        if (!empty($data['device_type']) && !in_array($data['device_type'], ['mobile', 'tablet', 'scanner', 'kiosk'])) {
            $errors[] = 'Invalid device type';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function validateGateAccess($ticket, $gate) {
        // If no access rules, allow access
        if (!$gate['access_rules']) {
            return true;
        }
        
        $rules = json_decode($gate['access_rules'], true);
        
        // Check ticket type restrictions
        if (!empty($rules['allowed_ticket_types'])) {
        }
        
        // Check time restrictions
        if (!empty($rules['time_restrictions'])) {
            $now = time();
            foreach ($rules['time_restrictions'] as $restriction) {
                $startTime = strtotime($restriction['start_time']);
                $endTime = strtotime($restriction['end_time']);
                
                if ($now >= $startTime && $now <= $endTime) {
                    return true;
                }
            }
            return false;
        }
        
        return true;
    }
    
    private function logCheckInAttempt($ticketId, $gateId, $deviceId, $operatorId, $result, $scanData = null) {
        $logData = [
            'ticket_id' => $ticketId,
            'gate_id' => $gateId,
            'device_id' => $deviceId,
            'operator_id' => $operatorId,
            'check_in_time' => date('Y-m-d H:i:s'),
            'check_in_result' => $result['check_in_result'] ?? 'invalid',
            'scan_data' => $scanData,
            'notes' => $result['error'] ?? null
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
