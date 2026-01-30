<?php

class Database {
    private static $instance = null;
    private $pdo;
    private $config;
    
    private function __construct() {
        $this->config = Config::getInstance()->get('database');
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['name']};charset={$this->config['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE {$this->config['collation']}"
            ];
            
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['password'], $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    public function query($sql) {
        return $this->pdo->query($sql);
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchColumn($sql, $params = [], $column = 0) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, $values);
        
        return $this->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setClause);
        $values = array_merge($values, $whereParams);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->execute($sql, $values);
        
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $params);
        
        return $stmt->rowCount();
    }
    
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        return $this->fetchColumn($sql, $params) !== false;
    }
    
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }
    
    public function paginate($sql, $params = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $countSql = "SELECT COUNT(*) FROM ({$sql}) as count_query";
        $total = (int) $this->fetchColumn($countSql, $params);
        
        $dataSql = "{$sql} LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->fetchAll($dataSql, $params);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    public function transaction(callable $callback) {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
