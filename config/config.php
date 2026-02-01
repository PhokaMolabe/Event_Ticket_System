<?php


class Config {
    private static $instance = null;
    private $config = [];
    
    private function __construct() {
        // Load environment variables from .env file manually
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue; // Skip comments
                if (strpos($line, '=') === false) continue; // Skip invalid lines
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
        
        $this->config = [
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'name' => $_ENV['DB_NAME'] ?? 'event_ticketing',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci'
            ],
            
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Event Ticketing System',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC'
            ],
            
            'security' => [
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-change-me',
                'jwt_expiry' => $_ENV['JWT_EXPIRY'] ?? 86400,
                'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? 'default-encryption-key-32-chars',
                'csrf_token_length' => $_ENV['CSRF_TOKEN_LENGTH'] ?? 32
            ],
            
            'email' => [
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Event Ticketing System'
            ],
            
            'payment' => [
                'stripe' => [
                    'public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
                    'secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? '',
                    'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? ''
                ],
                'paypal' => [
                    'client_id' => $_ENV['PAYPAL_CLIENT_ID'] ?? '',
                    'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? '',
                    'sandbox' => filter_var($_ENV['PAYPAL_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN)
                ]
            ],
            
            'upload' => [
                'max_size' => $_ENV['UPLOAD_MAX_SIZE'] ?? 10485760,
                'allowed_types' => explode(',', $_ENV['ALLOWED_FILE_TYPES'] ?? 'jpg,jpeg,png,pdf,csv,xlsx')
            ],
            
            'rate_limiting' => [
                'requests' => $_ENV['RATE_LIMIT_REQUESTS'] ?? 100,
                'window' => $_ENV['RATE_LIMIT_WINDOW'] ?? 3600
            ],
            
            'cache' => [
                'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
                'prefix' => $_ENV['CACHE_PREFIX'] ?? 'event_ticketing_',
                'ttl' => $_ENV['CACHE_TTL'] ?? 3600
            ],
            
            'session' => [
                'lifetime' => $_ENV['SESSION_LIFETIME'] ?? 120,
                'secure' => filter_var($_ENV['SESSION_SECURE'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'httponly' => filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN)
            ],
            
            'logging' => [
                'level' => $_ENV['LOG_LEVEL'] ?? 'info',
                'max_files' => $_ENV['LOG_MAX_FILES'] ?? 30
            ],
            
            'api' => [
                'version' => $_ENV['API_VERSION'] ?? 'v1',
                'rate_limit' => $_ENV['API_RATE_LIMIT'] ?? 1000,
                'throttle_requests' => $_ENV['API_THROTTLE_REQUESTS'] ?? 60
            ],
            
            'qr_code' => [
                'size' => $_ENV['QR_CODE_SIZE'] ?? 300,
                'margin' => $_ENV['QR_CODE_MARGIN'] ?? 10,
                'error_correction' => $_ENV['QR_CODE_ERROR_CORRECTION'] ?? 'M'
            ],
            
            'barcode' => [
                'type' => $_ENV['BARCODE_TYPE'] ?? 'code128',
                'width' => $_ENV['BARCODE_WIDTH'] ?? 2,
                'height' => $_ENV['BARCODE_HEIGHT'] ?? 50
            ],
            
            'currency' => [
                'default' => $_ENV['DEFAULT_CURRENCY'] ?? 'USD',
                'supported' => explode(',', $_ENV['SUPPORTED_CURRENCIES'] ?? 'USD,EUR,GBP,ZAR'),
                'tax_rate' => floatval($_ENV['TAX_RATE'] ?? 0.15),
                'vat_enabled' => filter_var($_ENV['VAT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN)
            ],
            
            'checkin' => [
                'timeout' => $_ENV['CHECK_IN_TIMEOUT'] ?? 30,
                'offline_mode' => filter_var($_ENV['OFFLINE_MODE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sync_interval' => $_ENV['SYNC_INTERVAL'] ?? 300
            ],
            
            'analytics' => [
                'reports_retention' => $_ENV['REPORTS_RETENTION_DAYS'] ?? 365,
                'batch_size' => $_ENV['ANALYTICS_BATCH_SIZE'] ?? 1000
            ],
            
            'high_availability' => [
                'load_balancer' => filter_var($_ENV['LOAD_BALANCER_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'redis' => [
                    'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                    'port' => $_ENV['REDIS_PORT'] ?? 6379,
                    'password' => $_ENV['REDIS_PASSWORD'] ?? ''
                ]
            ],
            
            'compliance' => [
                'gdpr' => filter_var($_ENV['GDPR_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'data_retention' => $_ENV['DATA_RETENTION_YEARS'] ?? 7,
                'audit_log' => filter_var($_ENV['AUDIT_LOG_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'pci_dss_mode' => $_ENV['PCI_DSS_MODE'] ?? 'strict'
            ]
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function set($key, $value) {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    public function all() {
        return $this->config;
    }
}
