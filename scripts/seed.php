<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Ticket.php';

class SeedData {
    private $db;
    private $userModel;
    private $eventModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->eventModel = new Event();
    }
    
    public function seedAll() {
        echo "Seeding database...\n";
        
        $this->seedRoles();
        $this->seedUsers();
        $this->seedVenues();
        $this->seedEvents();
        $this->seedTicketTypes();
        
        echo "Database seeded successfully!\n";
    }
    
    private function seedRoles() {
        echo "Seeding roles...\n";
        
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'level' => 100, 'is_system' => true],
            ['name' => 'admin', 'display_name' => 'Administrator', 'level' => 80, 'is_system' => true],
            ['name' => 'event_manager', 'display_name' => 'Event Manager', 'level' => 60, 'is_system' => false],
            ['name' => 'promoter', 'display_name' => 'Promoter', 'level' => 40, 'is_system' => false],
            ['name' => 'finance', 'display_name' => 'Finance', 'level' => 50, 'is_system' => false],
            ['name' => 'support', 'display_name' => 'Support', 'level' => 30, 'is_system' => false],
            ['name' => 'user', 'display_name' => 'User', 'level' => 10, 'is_system' => true]
        ];
        
        foreach ($roles as $role) {
            $this->db->insert('roles', $role);
        }
        
        // Seed permissions
        $permissions = [
            ['name' => 'users.create', 'display_name' => 'Create Users', 'module' => 'users'],
            ['name' => 'users.view', 'display_name' => 'View Users', 'module' => 'users'],
            ['name' => 'users.update', 'display_name' => 'Update Users', 'module' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'module' => 'users'],
            
            ['name' => 'events.create', 'display_name' => 'Create Events', 'module' => 'events'],
            ['name' => 'events.view', 'display_name' => 'View Events', 'module' => 'events'],
            ['name' => 'events.update', 'display_name' => 'Update Events', 'module' => 'events'],
            ['name' => 'events.delete', 'display_name' => 'Delete Events', 'module' => 'events'],
            ['name' => 'events.publish', 'display_name' => 'Publish Events', 'module' => 'events'],
            ['name' => 'events.cancel', 'display_name' => 'Cancel Events', 'module' => 'events'],
            ['name' => 'events.view_stats', 'display_name' => 'View Event Stats', 'module' => 'events'],
            ['name' => 'events.view_unpublished', 'display_name' => 'View Unpublished Events', 'module' => 'events'],
            
            ['name' => 'orders.create', 'display_name' => 'Create Orders', 'module' => 'orders'],
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'module' => 'orders'],
            ['name' => 'orders.update', 'display_name' => 'Update Orders', 'module' => 'orders'],
            ['name' => 'orders.cancel', 'display_name' => 'Cancel Orders', 'module' => 'orders'],
            ['name' => 'orders.refund', 'display_name' => 'Refund Orders', 'module' => 'orders'],
            
            ['name' => 'tickets.view', 'display_name' => 'View Tickets', 'module' => 'tickets'],
            ['name' => 'tickets.check_in', 'display_name' => 'Check-in Tickets', 'module' => 'tickets'],
            ['name' => 'tickets.transfer', 'display_name' => 'Transfer Tickets', 'module' => 'tickets'],
            
            ['name' => 'analytics.view', 'display_name' => 'View Analytics', 'module' => 'analytics'],
            ['name' => 'reports.export', 'display_name' => 'Export Reports', 'module' => 'reports'],
            
            ['name' => 'system.config', 'display_name' => 'System Configuration', 'module' => 'system'],
            ['name' => 'system.audit', 'display_name' => 'View Audit Logs', 'module' => 'system']
        ];
        
        foreach ($permissions as $permission) {
            $this->db->insert('permissions', $permission);
        }
        
        // Assign permissions to roles
        $this->assignRolePermissions();
    }
    
    private function assignRolePermissions() {
        // Super Admin gets all permissions
        $superAdminRole = $this->db->fetch("SELECT id FROM roles WHERE name = 'super_admin'");
        $allPermissions = $this->db->fetchAll("SELECT id FROM permissions");
        
        foreach ($allPermissions as $permission) {
            $this->db->insert('role_permissions', [
                'role_id' => $superAdminRole['id'],
                'permission_id' => $permission['id']
            ]);
        }
        
        // Admin gets most permissions (except system config)
        $adminRole = $this->db->fetch("SELECT id FROM roles WHERE name = 'admin'");
        $adminPermissions = $this->db->fetchAll("SELECT id FROM permissions WHERE name != 'system.config'");
        
        foreach ($adminPermissions as $permission) {
            $this->db->insert('role_permissions', [
                'role_id' => $adminRole['id'],
                'permission_id' => $permission['id']
            ]);
        }
        
        // Event Manager permissions
        $eventManagerRole = $this->db->fetch("SELECT id FROM roles WHERE name = 'event_manager'");
        $eventManagerPermissions = [
            'events.create', 'events.view', 'events.update', 'events.publish', 'events.cancel', 'events.view_stats',
            'orders.view', 'orders.update', 'orders.cancel',
            'tickets.view', 'tickets.check_in',
            'analytics.view'
        ];
        
        foreach ($eventManagerPermissions as $permName) {
            $permission = $this->db->fetch("SELECT id FROM permissions WHERE name = ?", [$permName]);
            if ($permission) {
                $this->db->insert('role_permissions', [
                    'role_id' => $eventManagerRole['id'],
                    'permission_id' => $permission['id']
                ]);
            }
        }
    }
    
    private function seedUsers() {
        echo "Seeding users...\n";
        
        $users = [
            [
                'email' => 'admin@eventticketing.com',
                'password' => 'admin123',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'role' => 'super_admin'
            ],
            [
                'email' => 'manager@eventticketing.com',
                'password' => 'manager123',
                'first_name' => 'Event',
                'last_name' => 'Manager',
                'role' => 'event_manager'
            ],
            [
                'email' => 'john.doe@example.com',
                'password' => 'user123',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'role' => 'user'
            ],
            [
                'email' => 'jane.smith@example.com',
                'password' => 'user123',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'role' => 'user'
            ]
        ];
        
        foreach ($users as $userData) {
            $result = $this->userModel->create($userData);
            if ($result['success']) {
                // Assign role
                $role = $this->db->fetch("SELECT id FROM roles WHERE name = ?", [$userData['role']]);
                if ($role) {
                    $this->db->insert('user_roles', [
                        'user_id' => $result['user_id'],
                        'role_id' => $role['id']
                    ]);
                }
                echo "Created user: {$userData['email']}\n";
            }
        }
    }
    
    private function seedVenues() {
        echo "Seeding venues...\n";
        
        $venues = [
            [
                'name' => 'Grand Convention Center',
                'address' => '123 Main Street',
                'city' => 'New York',
                'country' => 'USA',
                'postal_code' => '10001',
                'capacity' => 5000,
                'contact_email' => 'info@grandconvention.com',
                'contact_phone' => '+1-212-555-0100',
                'website' => 'https://grandconvention.com',
                'created_by' => 1
            ],
            [
                'name' => 'Madison Square Garden',
                'address' => '4 Pennsylvania Plaza',
                'city' => 'New York',
                'country' => 'USA',
                'postal_code' => '10001',
                'capacity' => 20000,
                'contact_email' => 'info@msg.com',
                'contact_phone' => '+1-212-465-6741',
                'website' => 'https://www.msg.com',
                'created_by' => 1
            ],
            [
                'name' => 'Central Park Amphitheater',
                'address' => 'Central Park',
                'city' => 'New York',
                'country' => 'USA',
                'postal_code' => '10024',
                'capacity' => 3000,
                'contact_email' => 'info@centralpark.com',
                'contact_phone' => '+1-212-310-6600',
                'website' => 'https://www.centralparknyc.org',
                'created_by' => 1
            ]
        ];
        
        foreach ($venues as $venue) {
            $venue['uuid'] = Ramsey\Uuid\Uuid::uuid4()->toString();
            $this->db->insert('venues', $venue);
            echo "Created venue: {$venue['name']}\n";
        }
    }
    
    private function seedEvents() {
        echo "Seeding events...\n";
        
        $events = [
            [
                'title' => 'Summer Music Festival 2024',
                'description' => 'Join us for the biggest music festival of the summer featuring top artists from around the world.',
                'short_description' => 'Annual summer music festival with international artists',
                'venue_id' => 1,
                'event_type' => 'festival',
                'category' => 'Music',
                'tags' => ['music', 'festival', 'summer', 'outdoor'],
                'organizer_name' => 'Event Productions Inc',
                'organizer_email' => 'info@eventproductions.com',
                'organizer_phone' => '+1-212-555-0200',
                'starts_at' => '2024-07-15 18:00:00',
                'ends_at' => '2024-07-15 23:00:00',
                'created_by' => 2
            ],
            [
                'title' => 'Tech Conference 2024',
                'description' => 'A comprehensive technology conference featuring the latest innovations in AI, blockchain, and cloud computing.',
                'short_description' => 'Annual technology conference',
                'venue_id' => 1,
                'event_type' => 'conference',
                'category' => 'Technology',
                'tags' => ['tech', 'conference', 'AI', 'blockchain'],
                'organizer_name' => 'TechEvents Corp',
                'organizer_email' => 'info@techevents.com',
                'organizer_phone' => '+1-212-555-0300',
                'starts_at' => '2024-09-20 09:00:00',
                'ends_at' => '2024-09-20 18:00:00',
                'created_by' => 2
            ],
            [
                'title' => 'Stand-up Comedy Night',
                'description' => 'An evening of laughter with top comedians from the comedy circuit.',
                'short_description' => 'Live stand-up comedy show',
                'venue_id' => 3,
                'event_type' => 'theater',
                'category' => 'Comedy',
                'tags' => ['comedy', 'stand-up', 'live', 'entertainment'],
                'organizer_name' => 'Laugh Factory',
                'organizer_email' => 'info@laughfactory.com',
                'organizer_phone' => '+1-212-555-0400',
                'starts_at' => '2024-06-10 20:00:00',
                'ends_at' => '2024-06-10 22:30:00',
                'created_by' => 2
            ],
            [
                'title' => 'Basketball Championship Finals',
                'description' => 'The ultimate basketball showdown between the top teams of the season.',
                'short_description' => 'Championship basketball game',
                'venue_id' => 2,
                'event_type' => 'sports',
                'category' => 'Sports',
                'tags' => ['basketball', 'championship', 'sports', 'finals'],
                'organizer_name' => 'National Basketball League',
                'organizer_email' => 'info@nbl.com',
                'organizer_phone' => '+1-212-555-0500',
                'starts_at' => '2024-08-15 19:00:00',
                'ends_at' => '2024-08-15 22:00:00',
                'created_by' => 2
            ],
            [
                'title' => 'Art Exhibition Opening',
                'description' => 'Exclusive opening night for contemporary art exhibition featuring emerging artists.',
                'short_description' => 'Contemporary art exhibition',
                'venue_id' => 1,
                'event_type' => 'exhibition',
                'category' => 'Art',
                'tags' => ['art', 'exhibition', 'contemporary', 'opening'],
                'organizer_name' => 'Modern Art Gallery',
                'organizer_email' => 'info@modernart.com',
                'organizer_phone' => '+1-212-555-0600',
                'starts_at' => '2024-05-25 18:00:00',
                'ends_at' => '2024-05-25 21:00:00',
                'created_by' => 2
            ]
        ];
        
        foreach ($events as $eventData) {
            $result = $this->eventModel->create($eventData, $eventData['created_by']);
            if ($result['success']) {
                echo "Created event: {$eventData['title']}\n";
            }
        }
    }
    
    private function seedTicketTypes() {
        echo "Seeding ticket types...\n";
        
        $ticketTypes = [
            // Summer Music Festival
            [
                'event_id' => 1,
                'name' => 'General Admission',
                'description' => 'General admission ticket with access to all stages',
                'ticket_type' => 'general',
                'price' => 75.00,
                'quantity_available' => 2000,
                'min_per_order' => 1,
                'max_per_order' => 10,
                'sale_starts_at' => '2024-01-01 00:00:00',
                'sale_ends_at' => '2024-07-14 23:59:59'
            ],
            [
                'event_id' => 1,
                'name' => 'VIP Pass',
                'description' => 'VIP ticket with backstage access and premium seating',
                'ticket_type' => 'vip',
                'price' => 250.00,
                'quantity_available' => 200,
                'min_per_order' => 1,
                'max_per_order' => 4,
                'sale_starts_at' => '2024-01-01 00:00:00',
                'sale_ends_at' => '2024-07-14 23:59:59'
            ],
            [
                'event_id' => 1,
                'name' => 'Early Bird',
                'description' => 'Special early bird pricing - limited quantity',
                'ticket_type' => 'early_bird',
                'price' => 50.00,
                'quantity_available' => 500,
                'min_per_order' => 1,
                'max_per_order' => 6,
                'sale_starts_at' => '2024-01-01 00:00:00',
                'sale_ends_at' => '2024-03-31 23:59:59'
            ],
            
            // Tech Conference
            [
                'event_id' => 2,
                'name' => 'Standard Pass',
                'description' => 'Full conference access with all sessions',
                'ticket_type' => 'general',
                'price' => 299.00,
                'quantity_available' => 1000,
                'min_per_order' => 1,
                'max_per_order' => 5,
                'sale_starts_at' => '2024-01-15 00:00:00',
                'sale_ends_at' => '2024-09-19 23:59:59'
            ],
            [
                'event_id' => 2,
                'name' => 'Student Pass',
                'description' => 'Discounted ticket for students with valid ID',
                'ticket_type' => 'student',
                'price' => 99.00,
                'quantity_available' => 200,
                'min_per_order' => 1,
                'max_per_order' => 2,
                'sale_starts_at' => '2024-01-15 00:00:00',
                'sale_ends_at' => '2024-09-19 23:59:59'
            ],
            
            // Stand-up Comedy Night
            [
                'event_id' => 3,
                'name' => 'General Admission',
                'description' => 'General seating for comedy show',
                'ticket_type' => 'general',
                'price' => 35.00,
                'quantity_available' => 500,
                'min_per_order' => 1,
                'max_per_order' => 8,
                'sale_starts_at' => '2024-04-01 00:00:00',
                'sale_ends_at' => '2024-06-09 23:59:59'
            ],
            
            // Basketball Championship
            [
                'event_id' => 4,
                'name' => 'Lower Bowl',
                'description' => 'Lower bowl seating with great view',
                'ticket_type' => 'general',
                'price' => 150.00,
                'quantity_available' => 5000,
                'min_per_order' => 1,
                'max_per_order' => 6,
                'sale_starts_at' => '2024-06-01 00:00:00',
                'sale_ends_at' => '2024-08-14 23:59:59'
            ],
            [
                'event_id' => 4,
                'name' => 'VIP Courtside',
                'description' => 'Premium courtside seating with VIP access',
                'ticket_type' => 'vip',
                'price' => 500.00,
                'quantity_available' => 100,
                'min_per_order' => 1,
                'max_per_order' => 4,
                'sale_starts_at' => '2024-06-01 00:00:00',
                'sale_ends_at' => '2024-08-14 23:59:59'
            ],
            
            // Art Exhibition
            [
                'event_id' => 5,
                'name' => 'Opening Night Ticket',
                'description' => 'Access to opening night reception and exhibition',
                'ticket_type' => 'general',
                'price' => 45.00,
                'quantity_available' => 300,
                'min_per_order' => 1,
                'max_per_order' => 4,
                'sale_starts_at' => '2024-04-01 00:00:00',
                'sale_ends_at' => '2024-05-24 23:59:59'
            ]
        ];
        
        foreach ($ticketTypes as $ticketType) {
            $this->db->insert('ticket_types', $ticketType);
            echo "Created ticket type: {$ticketType['name']} for event {$ticketType['event_id']}\n";
        }
        
        // Publish some events
        $this->db->update('events', 
            ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')], 
            'id IN (1, 2, 3, 4, 5)'
        );
    }
}

// Run the seeder
if (php_sapi_name() === 'cli') {
    $seeder = new SeedData();
    $seeder->seedAll();
} else {
    echo "This script must be run from the command line.\n";
}
?>
