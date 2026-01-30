<?php

class Event {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function create($data, $createdBy) {
        $validation = $this->validateEventData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $uuid = Ramsey\Uuid\Uuid::uuid4()->toString();
        $slug = $this->generateSlug($data['title']);
        
        $eventData = [
            'uuid' => $uuid,
            'title' => trim($data['title']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'venue_id' => $data['venue_id'],
            'event_type' => $data['event_type'],
            'category' => $data['category'] ?? null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'images' => isset($data['images']) ? json_encode($data['images']) : null,
            'organizer_name' => $data['organizer_name'] ?? null,
            'organizer_email' => $data['organizer_email'] ?? null,
            'organizer_phone' => $data['organizer_phone'] ?? null,
            'status' => 'draft',
            'visibility' => $data['visibility'] ?? 'public',
            'max_tickets_per_order' => $data['max_tickets_per_order'] ?? 10,
            'min_tickets_per_order' => $data['min_tickets_per_order'] ?? 1,
            'settings' => isset($data['settings']) ? json_encode($data['settings']) : null,
            'created_by' => $createdBy,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at']
        ];
        
        try {
            $eventId = $this->db->insert('events', $eventData);
            
            // Log activity
            $this->logActivity($createdBy, 'event_created', 'event', $eventId);
            
            return [
                'success' => true,
                'event_id' => $eventId,
                'uuid' => $uuid,
                'slug' => $slug
            ];
        } catch (Exception $e) {
            error_log("Event creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Event creation failed'];
        }
    }
    
    public function update($id, $data, $updatedBy) {
        $allowedFields = [
            'title', 'description', 'short_description', 'venue_id', 
            'event_type', 'category', 'tags', 'images', 'organizer_name',
            'organizer_email', 'organizer_phone', 'visibility',
            'max_tickets_per_order', 'min_tickets_per_order', 'settings',
            'starts_at', 'ends_at'
        ];
        
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, ['tags', 'images', 'settings'])) {
                    $updateData[$field] = json_encode($data[$field]);
                } elseif ($field === 'title') {
                    $updateData[$field] = trim($data[$field]);
                    $updateData['slug'] = $this->generateSlug($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No valid fields to update'];
        }
        
        try {
            $this->db->update('events', $updateData, 'id = ?', [$id]);
            
            // Log activity
            $this->logActivity($updatedBy, 'event_updated', 'event', $id);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Update failed'];
        }
    }
    
    public function getById($id) {
        $sql = "
            SELECT e.*, v.name as venue_name, v.city as venue_city, v.country as venue_country
            FROM events e
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.id = ?
        ";
        
        $event = $this->db->fetch($sql, [$id]);
        if (!$event) {
            return null;
        }
        
        return $this->formatEventData($event);
    }
    
    public function getByUuid($uuid) {
        $sql = "
            SELECT e.*, v.name as venue_name, v.city as venue_city, v.country as venue_country
            FROM events e
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.uuid = ?
        ";
        
        $event = $this->db->fetch($sql, [$uuid]);
        if (!$event) {
            return null;
        }
        
        return $this->formatEventData($event);
    }
    
    public function getBySlug($slug) {
        $sql = "
            SELECT e.*, v.name as venue_name, v.city as venue_city, v.country as venue_country
            FROM events e
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.slug = ? AND e.status IN ('published', 'live')
        ";
        
        $event = $this->db->fetch($sql, [$slug]);
        if (!$event) {
            return null;
        }
        
        return $this->formatEventData($event);
    }
    
    public function getList($filters = [], $page = 1, $perPage = 20) {
        $where = ["1=1"];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['event_type'])) {
            $where[] = "e.event_type = ?";
            $params[] = $filters['event_type'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "e.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['venue_id'])) {
            $where[] = "e.venue_id = ?";
            $params[] = $filters['venue_id'];
        }
        
        if (!empty($filters['created_by'])) {
            $where[] = "e.created_by = ?";
            $params[] = $filters['created_by'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(e.title LIKE ? OR e.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "e.starts_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "e.starts_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT e.*, v.name as venue_name, v.city as venue_city
            FROM events e
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE {$whereClause}
            ORDER BY e.starts_at DESC, e.created_at DESC
        ";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    public function getUpcomingEvents($limit = 10) {
        $sql = "
            SELECT e.*, v.name as venue_name, v.city as venue_city
            FROM events e
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.status IN ('published', 'live')
            AND e.starts_at > NOW()
            ORDER BY e.starts_at ASC
            LIMIT ?
        ";
        
        $events = $this->db->fetchAll($sql, [$limit]);
        return array_map([$this, 'formatEventData'], $events);
    }
    
    public function getFeaturedEvents($limit = 6) {
        $sql = "
            SELECT e.*, v.name as venue_name, v.city as venue_city
            FROM events e
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.status IN ('published', 'live')
            AND e.is_featured = true
            AND e.starts_at > NOW()
            ORDER BY e.starts_at ASC
            LIMIT ?
        ";
        
        $events = $this->db->fetchAll($sql, [$limit]);
        return array_map([$this, 'formatEventData'], $events);
    }
    
    public function publish($id, $publishedBy) {
        try {
            $this->db->update('events', 
                [
                    'status' => 'published',
                    'published_at' => date('Y-m-d H:i:s')
                ], 
                'id = ?', 
                [$id]
            );
            
            // Log activity
            $this->logActivity($publishedBy, 'event_published', 'event', $id);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Publish failed'];
        }
    }
    
    public function cancel($id, $cancelledBy, $reason = null) {
        try {
            $this->db->update('events', 
                ['status' => 'cancelled'], 
                'id = ?', 
                [$id]
            );
            
            // Cancel all pending orders for this event
            $this->db->update('orders', 
                ['status' => 'cancelled'], 
                'event_id = ? AND status IN ("pending", "payment_pending")', 
                [$id]
            );
            
            // Log activity
            $this->logActivity($cancelledBy, 'event_cancelled', 'event', $id, ['reason' => $reason]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Cancel failed'];
        }
    }
    
    public function getTicketTypes($eventId) {
        $sql = "
            SELECT tt.*, 
                   (tt.quantity_available - tt.quantity_sold - tt.quantity_held) as available_quantity
            FROM ticket_types tt
            WHERE tt.event_id = ?
            AND tt.is_active = true
            ORDER BY tt.sort_order ASC, tt.price ASC
        ";
        
        $ticketTypes = $this->db->fetchAll($sql, [$eventId]);
        
        foreach ($ticketTypes as &$type) {
            $type['is_available'] = $type['available_quantity'] > 0;
            $type['is_on_sale'] = $this->isTicketTypeOnSale($type);
        }
        
        return $ticketTypes;
    }
    
    public function getEventStats($eventId) {
        $sql = "
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT CASE WHEN o.status = 'paid' THEN o.id END) as paid_orders,
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'confirmed' THEN t.id END) as confirmed_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) as checked_in_tickets,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as average_order_value
            FROM events e
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE e.id = ?
        ";
        
        return $this->db->fetch($sql, [$eventId]);
    }
    
    public function updateTicketAvailability($eventId, $ticketTypeId, $quantity, $operation) {
        $field = ($operation === 'hold') ? 'quantity_held' : 'quantity_sold';
        
        $sql = "UPDATE ticket_types SET {$field} = {$field} + ? WHERE id = ? AND event_id = ?";
        
        try {
            $this->db->execute($sql, [$quantity, $ticketTypeId, $eventId]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Availability update failed'];
        }
    }
    
    private function validateEventData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Event title is required';
        } elseif (strlen($data['title']) < 3) {
            $errors[] = 'Event title must be at least 3 characters';
        }
        
        if (empty($data['venue_id'])) {
            $errors[] = 'Venue is required';
        }
        
        if (empty($data['event_type'])) {
            $errors[] = 'Event type is required';
        }
        
        if (empty($data['starts_at'])) {
            $errors[] = 'Start date is required';
        }
        
        if (empty($data['ends_at'])) {
            $errors[] = 'End date is required';
        }
        
        if (!empty($data['starts_at']) && !empty($data['ends_at'])) {
            if (strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
                $errors[] = 'End date must be after start date';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function generateSlug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and make unique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->db->fetch("SELECT id FROM events WHERE slug = ?", [$slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    private function formatEventData($event) {
        if (isset($event['tags']) && $event['tags']) {
            $event['tags'] = json_decode($event['tags'], true);
        }
        
        if (isset($event['images']) && $event['images']) {
            $event['images'] = json_decode($event['images'], true);
        }
        
        if (isset($event['settings']) && $event['settings']) {
            $event['settings'] = json_decode($event['settings'], true);
        }
        
        return $event;
    }
    
    private function isTicketTypeOnSale($ticketType) {
        $now = time();
        
        if ($ticketType['sale_starts_at'] && strtotime($ticketType['sale_starts_at']) > $now) {
            return false;
        }
        
        if ($ticketType['sale_ends_at'] && strtotime($ticketType['sale_ends_at']) < $now) {
            return false;
        }
        
        return true;
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
