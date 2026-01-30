<?php

class Analytics {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function getDashboardStats($filters = []) {
        $dateFrom = $filters['date_from'] ?? date('Y-m-01');
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        
        $stats = [
            'overview' => $this->getOverviewStats($dateFrom, $dateTo),
            'events' => $this->getEventsStats($dateFrom, $dateTo),
            'tickets' => $this->getTicketsStats($dateFrom, $dateTo),
            'revenue' => $this->getRevenueStats($dateFrom, $dateTo),
            'attendance' => $this->getAttendanceStats($dateFrom, $dateTo)
        ];
        
        return $stats;
    }
    
    public function getOverviewStats($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT CASE WHEN o.status = 'paid' THEN o.id END) as paid_orders,
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) as checked_in_tickets,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as average_order_value
            FROM events e
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE DATE(e.created_at) BETWEEN ? AND ?
        ";
        
        return $this->db->fetch($sql, [$dateFrom, $dateTo]);
    }
    
    public function getEventsStats($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                e.event_type,
                COUNT(e.id) as event_count,
                COUNT(DISTINCT o.id) as order_count,
                COUNT(t.id) as ticket_count,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as revenue
            FROM events e
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE DATE(e.created_at) BETWEEN ? AND ?
            GROUP BY e.event_type
            ORDER BY event_count DESC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getTicketsStats($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                tt.ticket_type,
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'confirmed' THEN t.id END) as confirmed_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) as checked_in_tickets,
                COUNT(CASE WHEN t.status = 'cancelled' THEN t.id END) as cancelled_tickets,
                COUNT(CASE WHEN t.status = 'refunded' THEN t.id END) as refunded_tickets,
                AVG(t.price_paid) as average_price
            FROM ticket_types tt
            LEFT JOIN tickets t ON tt.id = t.ticket_type_id
            LEFT JOIN events e ON t.event_id = e.id
            WHERE DATE(e.created_at) BETWEEN ? AND ?
            GROUP BY tt.ticket_type
            ORDER BY total_tickets DESC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getRevenueStats($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                DATE(o.created_at) as date,
                COUNT(o.id) as order_count,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as daily_revenue,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as avg_order_value,
                COUNT(CASE WHEN o.status = 'paid' THEN 1 END) as paid_orders
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at)
            ORDER BY date ASC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getAttendanceStats($dateFrom, $dateTo) {
        $sql = "
            SELECT 
                DATE(cil.check_in_time) as date,
                COUNT(CASE WHEN cil.check_in_result = 'success' THEN 1 END) as successful_checkins,
                COUNT(CASE WHEN cil.check_in_result = 'duplicate' THEN 1 END) as duplicate_attempts,
                COUNT(CASE WHEN cil.check_in_result = 'invalid' THEN 1 END) as invalid_attempts,
                COUNT(DISTINCT cil.ticket_id) as unique_tickets_checked_in
            FROM check_in_logs cil
            WHERE DATE(cil.check_in_time) BETWEEN ? AND ?
            GROUP BY DATE(cil.check_in_time)
            ORDER BY date ASC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getEventPerformance($eventId) {
        $sql = "
            SELECT 
                e.title,
                e.starts_at,
                e.ends_at,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT CASE WHEN o.status = 'paid' THEN o.id END) as paid_orders,
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'confirmed' THEN t.id END) as confirmed_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) as checked_in_tickets,
                COUNT(CASE WHEN t.status = 'cancelled' THEN t.id END) as cancelled_tickets,
                COUNT(CASE WHEN t.status = 'refunded' THEN t.id END) as refunded_tickets,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as average_order_value,
                (COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) / COUNT(t.id)) * 100 as attendance_rate
            FROM events e
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE e.id = ?
        ";
        
        return $this->db->fetch($sql, [$eventId]);
    }
    
    public function getSalesVelocity($eventId, $days = 30) {
        $sql = "
            SELECT 
                DATE(o.created_at) as date,
                COUNT(o.id) as daily_orders,
                COUNT(t.id) as daily_tickets_sold,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as daily_revenue,
                COUNT(CASE WHEN o.status = 'paid' THEN 1 END) as paid_orders
            FROM orders o
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE o.event_id = ?
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(o.created_at)
            ORDER BY date ASC
        ";
        
        return $this->db->fetchAll($sql, [$eventId, $days]);
    }
    
    public function getTopPerformingEvents($limit = 10, $dateFrom = null, $dateTo = null) {
        $dateFrom = $dateFrom ?? date('Y-m-01');
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $sql = "
            SELECT 
                e.id,
                e.title,
                e.starts_at,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(t.id) as total_tickets,
                COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) as checked_in_tickets,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_revenue,
                (COUNT(CASE WHEN t.status = 'checked_in' THEN t.id END) / COUNT(t.id)) * 100 as attendance_rate
            FROM events e
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE DATE(e.starts_at) BETWEEN ? AND ?
            GROUP BY e.id, e.title, e.starts_at
            ORDER BY total_revenue DESC
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo, $limit]);
    }
    
    public function getCustomerAnalytics($filters = []) {
        $dateFrom = $filters['date_from'] ?? date('Y-m-01');
        $dateTo = $filters['date_to'] ?? date('Y-m-d');
        
        $sql = "
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(t.id) as total_tickets,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as avg_order_value,
                MIN(o.created_at) as first_order_date,
                MAX(o.created_at) as last_order_date,
                COUNT(DISTINCT e.id) as unique_events_attended
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            LEFT JOIN tickets t ON o.id = t.order_id
            LEFT JOIN events e ON t.event_id = e.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.status = 'paid'
            GROUP BY u.id, u.first_name, u.last_name, u.email
            HAVING total_orders > 0
            ORDER BY total_spent DESC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getRevenueByChannel($dateFrom = null, $dateTo = null) {
        $dateFrom = $dateFrom ?? date('Y-m-01');
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $sql = "
            SELECT 
                o.payment_method,
                o.payment_gateway,
                COUNT(o.id) as order_count,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as revenue,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as avg_order_value
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.status = 'paid'
            GROUP BY o.payment_method, o.payment_gateway
            ORDER BY revenue DESC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getConversionFunnel($eventId) {
        $event = $this->db->fetch("SELECT starts_at FROM events WHERE id = ?", [$eventId]);
        if (!$event) {
            return null;
        }
        
        $sql = "
            SELECT 
                COUNT(DISTINCT v.id) as page_views,
                COUNT(DISTINCT CASE WHEN v.action = 'add_to_cart' THEN v.user_id END) as cart_additions,
                COUNT(DISTINCT CASE WHEN v.action = 'checkout_initiated' THEN v.user_id END) as checkout_initiations,
                COUNT(DISTINCT o.id) as orders_created,
                COUNT(DISTINCT CASE WHEN o.status = 'paid' THEN o.id END) as paid_orders,
                COUNT(t.id) as tickets_sold
            FROM events e
            LEFT JOIN user_activity_logs v ON e.id = v.resource_id AND v.resource_type = 'event'
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE e.id = ?
        ";
        
        $data = $this->db->fetch($sql, [$eventId]);
        
        // Calculate conversion rates
        $data['cart_conversion_rate'] = $data['page_views'] > 0 ? 
            round(($data['cart_additions'] / $data['page_views']) * 100, 2) : 0;
        
        $data['checkout_conversion_rate'] = $data['cart_additions'] > 0 ? 
            round(($data['checkout_initiations'] / $data['cart_additions']) * 100, 2) : 0;
        
        $data['order_conversion_rate'] = $data['checkout_initiations'] > 0 ? 
            round(($data['orders_created'] / $data['checkout_initiations']) * 100, 2) : 0;
        
        $data['payment_conversion_rate'] = $data['orders_created'] > 0 ? 
            round(($data['paid_orders'] / $data['orders_created']) * 100, 2) : 0;
        
        return $data;
    }
    
    public function getFinancialReconciliation($dateFrom = null, $dateTo = null) {
        $dateFrom = $dateFrom ?? date('Y-m-01');
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $sql = "
            SELECT 
                DATE(o.created_at) as date,
                COUNT(o.id) as total_orders,
                COUNT(CASE WHEN o.status = 'paid' THEN o.id END) as paid_orders,
                COUNT(CASE WHEN o.status = 'refunded' THEN o.id END) as refunded_orders,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as gross_revenue,
                SUM(CASE WHEN o.status = 'refunded' THEN o.total_amount ELSE 0 END) as refunded_amount,
                SUM(o.tax_amount) as total_tax,
                SUM(o.service_fee) as total_service_fees,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) - 
                SUM(CASE WHEN o.status = 'refunded' THEN o.total_amount ELSE 0 END) as net_revenue
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at)
            ORDER BY date ASC
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function getTaxReports($dateFrom = null, $dateTo = null) {
        $dateFrom = $dateFrom ?? date('Y-m-01');
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $sql = "
            SELECT 
                o.currency,
                SUM(o.tax_amount) as total_tax_collected,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as taxable_revenue,
                SUM(CASE WHEN o.status = 'refunded' THEN o.tax_amount ELSE 0 END) as tax_refunded,
                SUM(CASE WHEN o.status = 'paid' THEN o.tax_amount ELSE 0 END) - 
                SUM(CASE WHEN o.status = 'refunded' THEN o.tax_amount ELSE 0 END) as net_tax
            FROM orders o
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            AND o.tax_amount > 0
            GROUP BY o.currency
        ";
        
        return $this->db->fetchAll($sql, [$dateFrom, $dateTo]);
    }
    
    public function exportReport($reportType, $filters = [], $format = 'csv') {
        switch ($reportType) {
            case 'events':
                $data = $this->getTopPerformingEvents(1000, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
                break;
                
            case 'revenue':
                $data = $this->getFinancialReconciliation($filters['date_from'] ?? null, $filters['date_to'] ?? null);
                break;
                
            case 'customers':
                $data = $this->getCustomerAnalytics($filters);
                break;
                
            case 'attendance':
                $data = $this->getAttendanceStats($filters['date_from'] ?? null, $filters['date_to'] ?? null);
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
        
        if ($format === 'csv') {
            return $this->generateCSV($data);
        } elseif ($format === 'excel') {
            return $this->generateExcel($data, $reportType);
        } else {
            throw new Exception('Unsupported format');
        }
    }
    
    public function updateEventAnalytics($eventId) {
        $event = $this->db->fetch("SELECT * FROM events WHERE id = ?", [$eventId]);
        if (!$event) {
            return false;
        }
        
        $date = date('Y-m-d');
        
        // Get analytics data for today
        $sql = "
            SELECT 
                COUNT(DISTINCT CASE WHEN v.action = 'page_view' THEN v.user_id END) as views,
                COUNT(DISTINCT CASE WHEN v.action = 'add_to_cart' THEN v.user_id END) as cart_additions,
                COUNT(DISTINCT CASE WHEN v.action = 'checkout_initiated' THEN v.user_id END) as checkout_initiations,
                COUNT(DISTINCT o.id) as orders,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as revenue,
                COUNT(t.id) as tickets_sold,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as average_order_value
            FROM events e
            LEFT JOIN user_activity_logs v ON e.id = v.resource_id AND v.resource_type = 'event' AND DATE(v.created_at) = ?
            LEFT JOIN orders o ON e.id = o.event_id AND DATE(o.created_at) = ?
            LEFT JOIN tickets t ON o.id = t.order_id
            WHERE e.id = ?
        ";
        
        $analytics = $this->db->fetch($sql, [$date, $date, $eventId]);
        
        // Calculate conversion rate
        $analytics['conversion_rate'] = $analytics['views'] > 0 ? 
            round(($analytics['orders'] / $analytics['views']) * 100, 2) : 0;
        
        // Update or insert analytics record
        $existing = $this->db->fetch(
            "SELECT id FROM analytics_events WHERE event_id = ? AND date = ?", 
            [$eventId, $date]
        );
        
        if ($existing) {
            $this->db->update('analytics_events', $analytics, 'id = ?', [$existing['id']]);
        } else {
            $analytics['event_id'] = $eventId;
            $analytics['date'] = $date;
            $this->db->insert('analytics_events', $analytics);
        }
        
        return true;
    }
    
    private function generateCSV($data) {
        if (empty($data)) {
            return '';
        }
        
        $headers = array_keys($data[0]);
        $csv = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                // Escape commas and quotes
                if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                $csvRow[] = $value;
            }
            $csv .= implode(',', $csvRow) . "\n";
        }
        
        return $csv;
    }
    
    private function generateExcel($data, $reportType) {
        // This would use a library like PhpSpreadsheet to generate Excel files
        // For now, return CSV as fallback
        return $this->generateCSV($data);
    }
    
    public function getRealTimeMetrics($eventId = null) {
        $whereClause = $eventId ? "WHERE e.id = ?" : "";
        $params = $eventId ? [$eventId] : [];
        
        $sql = "
            SELECT 
                COUNT(DISTINCT CASE WHEN o.status = 'paid' THEN o.id END) as active_orders,
                COUNT(CASE WHEN t.status = 'confirmed' THEN t.id END) as pending_checkins,
                COUNT(CASE WHEN t.status = 'checked_in' AND DATE(t.checked_in_at) = CURDATE() THEN t.id END) as today_checkins,
                SUM(CASE WHEN o.status = 'paid' AND DATE(o.created_at) = CURDATE() THEN o.total_amount ELSE 0 END) as today_revenue,
                COUNT(CASE WHEN o.status = 'pending' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN o.id END) as recent_pending_orders
            FROM events e
            LEFT JOIN orders o ON e.id = o.event_id
            LEFT JOIN tickets t ON o.id = t.order_id
            {$whereClause}
        ";
        
        return $this->db->fetch($sql, $params);
    }
}
