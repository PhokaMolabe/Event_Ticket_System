<?php

class EventController {
    private $eventModel;
    private $userModel;
    private $config;
    
    public function __construct() {
        $this->eventModel = new Event();
        $this->userModel = new User();
        $this->config = Config::getInstance();
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->requireAuth();
        $this->requirePermission('events.create');
        
        $data = $this->getJsonInput();
        
        $result = $this->eventModel->create($data, $this->getCurrentUserId());
        
        if ($result['success']) {
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->requireAuth();
        $this->requirePermission('events.update');
        
        $data = $this->getJsonInput();
        
        $result = $this->eventModel->update($id, $data, $this->getCurrentUserId());
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    public function get($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $event = $this->eventModel->getById($id);
        
        if (!$event) {
            $this->jsonResponse(['success' => false, 'error' => 'Event not found'], 404);
            return;
        }
        
        // Check permissions for unpublished events
        if ($event['status'] !== 'published' && $event['status'] !== 'live') {
            $this->requireAuth();
            $this->requirePermission('events.view_unpublished');
        }
        
        // Add ticket types
        $event['ticket_types'] = $this->eventModel->getTicketTypes($id);
        
        // Add event stats if user has permission
        if ($this->isAuthenticated() && $this->hasPermission('events.view_stats')) {
            $event['stats'] = $this->eventModel->getEventStats($id);
        }
        
        $this->jsonResponse(['success' => true, 'event' => $event]);
    }
    
    public function getBySlug($slug) {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $event = $this->eventModel->getBySlug($slug);
        
        if (!$event) {
            $this->jsonResponse(['success' => false, 'error' => 'Event not found'], 404);
            return;
        }
        
        // Add ticket types
        $event['ticket_types'] = $this->eventModel->getTicketTypes($event['id']);
        
        $this->jsonResponse(['success' => true, 'event' => $event]);
    }
    
    public function list() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $filters = $this->getQueryFilters();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        // Limit per page to reasonable values
        $perPage = min(max($perPage, 1), 100);
        
        $result = $this->eventModel->getList($filters, $page, $perPage);
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }
    
    public function upcoming() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $limit = (int)($_GET['limit'] ?? 10);
        $limit = min(max($limit, 1), 50);
        
        $events = $this->eventModel->getUpcomingEvents($limit);
        
        $this->jsonResponse(['success' => true, 'events' => $events]);
    }
    
    public function featured() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $limit = (int)($_GET['limit'] ?? 6);
        $limit = min(max($limit, 1), 20);
        
        $events = $this->eventModel->getFeaturedEvents($limit);
        
        $this->jsonResponse(['success' => true, 'events' => $events]);
    }
    
    public function publish($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->requireAuth();
        $this->requirePermission('events.publish');
        
        $result = $this->eventModel->publish($id, $this->getCurrentUserId());
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    public function cancel($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->requireAuth();
        $this->requirePermission('events.cancel');
        
        $data = $this->getJsonInput();
        $reason = $data['reason'] ?? null;
        
        $result = $this->eventModel->cancel($id, $this->getCurrentUserId(), $reason);
        
        if ($result['success']) {
            $this->jsonResponse($result);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    public function duplicate($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->requireAuth();
        $this->requirePermission('events.create');
        
        $originalEvent = $this->eventModel->getById($id);
        if (!$originalEvent) {
            $this->jsonResponse(['success' => false, 'error' => 'Original event not found'], 404);
            return;
        }
        
        $data = $this->getJsonInput();
        
        // Prepare duplicate data
        $duplicateData = [
            'title' => $data['title'] ?? $originalEvent['title'] . ' (Copy)',
            'description' => $originalEvent['description'],
            'short_description' => $originalEvent['short_description'],
            'venue_id' => $originalEvent['venue_id'],
            'event_type' => $originalEvent['event_type'],
            'category' => $originalEvent['category'],
            'tags' => $originalEvent['tags'],
            'organizer_name' => $originalEvent['organizer_name'],
            'organizer_email' => $originalEvent['organizer_email'],
            'organizer_phone' => $originalEvent['organizer_phone'],
            'visibility' => $originalEvent['visibility'],
            'max_tickets_per_order' => $originalEvent['max_tickets_per_order'],
            'min_tickets_per_order' => $originalEvent['min_tickets_per_order'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null
        ];
        
        $result = $this->eventModel->create($duplicateData, $this->getCurrentUserId());
        
        if ($result['success']) {
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 400);
        }
    }
    
    public function search() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            $this->jsonResponse(['success' => false, 'error' => 'Search query is required'], 400);
            return;
        }
        
        $filters = $this->getQueryFilters();
        $filters['search'] = $query;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $result = $this->eventModel->getList($filters, $page, $perPage);
        
        $this->jsonResponse(['success' => true, 'data' => $result]);
    }
    
    public function getStats($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->requireAuth();
        $this->requirePermission('events.view_stats');
        
        $event = $this->eventModel->getById($id);
        if (!$event) {
            $this->jsonResponse(['success' => false, 'error' => 'Event not found'], 404);
            return;
        }
        
        $stats = $this->eventModel->getEventStats($id);
        
        $this->jsonResponse(['success' => true, 'stats' => $stats]);
    }
    
    private function getQueryFilters() {
        $filters = [];
        
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (!empty($_GET['event_type'])) {
            $filters['event_type'] = $_GET['event_type'];
        }
        
        if (!empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        
        if (!empty($_GET['venue_id'])) {
            $filters['venue_id'] = (int)$_GET['venue_id'];
        }
        
        if (!empty($_GET['created_by'])) {
            $filters['created_by'] = (int)$_GET['created_by'];
        }
        
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        return $filters;
    }
    
    private function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
            exit;
        }
    }
    
    private function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            $this->jsonResponse(['success' => false, 'error' => 'Insufficient permissions'], 403);
            exit;
        }
    }
    
    private function isAuthenticated() {
        $token = $this->getAuthToken();
        if (!$token) {
            return false;
        }
        
        try {
            $payload = Firebase\JWT\JWT::decode($token, $this->config->get('security.jwt_secret'), ['HS256']);
            return isset($payload->user_id);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function hasPermission($permission) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $userId = $this->getCurrentUserId();
        return $this->userModel->hasPermission($userId, $permission);
    }
    
    private function getCurrentUserId() {
        $token = $this->getAuthToken();
        if (!$token) {
            return null;
        }
        
        try {
            $payload = Firebase\JWT\JWT::decode($token, $this->config->get('security.jwt_secret'), ['HS256']);
            return $payload->user_id ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function getAuthToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
