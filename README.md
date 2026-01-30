# Enterprise Event Management and Ticketing System

A comprehensive, enterprise-grade event management and ticketing platform built with PHP, featuring multi-tier ticketing, dynamic pricing, real-time check-in, analytics, and full compliance with security standards.

## 🚀 Features

### Core Ticketing & Sales
- **Multi-Tier Ticketing**: General, VIP, Early Bird, Group, Complimentary tickets
- **Dynamic Pricing**: Time-based price increases and demand-based pricing
- **Seat Mapping**: Interactive venue maps with section/row/seat logic
- **Promo Codes**: Percentage and fixed discounts with usage limits
- **Multiple Payment Gateways**: Stripe, PayPal, bank transfers, buy-now-pay-later
- **Currency & Tax Support**: Multi-currency with VAT/GST calculations
- **Refunds & Transfers**: Partial refunds, ticket upgrades, attendee transfers

### Event Management
- **Event Lifecycle**: Draft → Published → Live → Closed → Archived
- **Multi-Session Events**: Conferences, expos, festivals with session tracking
- **Capacity Control**: Per-session limits and access rules
- **Staff Management**: Role-based permissions (Admin, Promoter, Finance, Support)
- **Venue Management**: Room, stage, equipment management with clash detection

### Check-in & Security
- **QR/Barcode Validation**: Offline scanning with anti-duplication logic
- **Real-time Attendance**: Live dashboards with entry/exit counts
- **Fraud Prevention**: Duplicate scan detection and suspicious activity alerts
- **Device & Gate Management**: Assign scanners to gates with gate-specific rules

### Analytics & Reporting
- **Advanced Dashboards**: Sales velocity, attendance vs sales, revenue by channel
- **Financial Reconciliation**: Payout schedules, commission splits, tax reports
- **Export Options**: CSV, Excel, API integration with accounting systems

### Enterprise Features
- **High Availability**: Load balancing, queue systems, burst traffic handling
- **Compliance**: GDPR/POPIA/PCI-DSS compliant with audit logs
- **API Integration**: Public and private APIs with webhooks
- **White-label Portals**: Custom branding for partners
- **Mobile Apps**: Native iOS and Android support

## 📋 Requirements

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer for dependency management
- SSL certificate (required for payment processing)

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd Event_Ticket_System
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file with your database and payment gateway credentials:

```env
# Database
DB_HOST=localhost
DB_NAME=event_ticketing
DB_USER=root
DB_PASSWORD=your_password

# Payment Gateways
STRIPE_PUBLIC_KEY=pk_test_your_key
STRIPE_SECRET_KEY=sk_test_your_key
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_secret

# Security
JWT_SECRET=your-super-secret-jwt-key
ENCRYPTION_KEY=your-32-character-encryption-key
```

### 4. Database Setup
```bash
# Create database and import schema
mysql -u root -p < database.sql
```

### 5. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /public/index.php?$query_string;
}
```

### 6. Set Permissions
```bash
chmod -R 755 .
chmod -R 777 logs/
```

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to configure your database connection.

### Application Settings
Configure application settings in `config/config.php`:
- Security settings (JWT, encryption)
- Email configuration
- File upload limits
- Rate limiting
- Cache configuration

### Payment Gateway Setup

#### Stripe
1. Create Stripe account
2. Get API keys from Stripe Dashboard
3. Configure webhooks for payment notifications

#### PayPal
1. Create PayPal Developer account
2. Create REST API app
3. Get Client ID and Secret

## 📚 API Documentation

### Authentication
Most endpoints require JWT authentication. Include token in Authorization header:

```
Authorization: Bearer {your-jwt-token}
```

### Core Endpoints

#### Events
```http
GET    /api/events              # List events
POST   /api/events              # Create event
GET    /api/events/{id}          # Get event details
PUT    /api/events/{id}          # Update event
POST   /api/events/{id}/publish  # Publish event
POST   /api/events/{id}/cancel   # Cancel event
```

#### Orders
```http
POST   /api/orders              # Create order
GET    /api/orders/{id}          # Get order details
PUT    /api/orders/{id}/cancel   # Cancel order
POST   /api/orders/{id}/refund   # Refund order
```

#### Tickets
```http
GET    /api/tickets/{id}         # Get ticket details
POST   /api/tickets/check-in     # Check in by code
PUT    /api/tickets/{id}/attendee # Update attendee info
POST   /api/tickets/transfer     # Transfer ticket
```

#### Users
```http
POST   /api/users/register       # User registration
POST   /api/auth/login           # User login
GET    /api/users/{id}/orders    # Get user orders
GET    /api/users/{id}/tickets   # Get user tickets
```

### Example API Usage

#### Create Event
```bash
curl -X POST http://your-domain.com/api/events \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Summer Music Festival",
    "description": "Annual summer music festival",
    "venue_id": 1,
    "event_type": "festival",
    "starts_at": "2024-07-15T18:00:00Z",
    "ends_at": "2024-07-15T23:00:00Z"
  }'
```

#### Create Order
```bash
curl -X POST http://your-domain.com/api/orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 123,
    "items": [
      {
        "ticket_type_id": 1,
        "event_id": 1,
        "quantity": 2,
        "unit_price": 50.00,
        "subtotal": 100.00,
        "tax_amount": 15.00,
        "total_amount": 115.00
      }
    ],
    "currency": "USD"
  }'
```

#### Check-in Ticket
```bash
curl -X POST http://your-domain.com/api/tickets/check-in \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "TKT2024011512345678901234",
    "gate_id": 1,
    "device_id": "scanner001",
    "operator_id": 123
  }'
```

## 🏗️ Project Structure

```
Event_Ticket_System/
├── api/                    # API endpoint handlers
├── config/                 # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database configuration
├── controllers/            # MVC controllers
├── models/                # Data models
├── views/                 # View templates
├── public/                # Web root
│   └── index.php         # Front controller
├── middleware/            # Request middleware
├── helpers/              # Utility functions
├── logs/                 # Application logs
├── docs/                 # Documentation
├── tests/                # Unit tests
├── vendor/               # Composer dependencies
├── database.sql          # Database schema
├── composer.json         # Dependencies
├── .env.example          # Environment template
└── README.md             # This file
```

## 🔒 Security Features

- **JWT Authentication**: Secure token-based authentication
- **Role-based Access Control**: Granular permissions system
- **Input Validation**: Comprehensive input sanitization
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Protection**: Output encoding and CSP headers
- **Rate Limiting**: API rate limiting to prevent abuse
- **Audit Logging**: Complete audit trail for compliance
- **Data Encryption**: Sensitive data encryption at rest

## 📊 Analytics & Reporting

### Built-in Reports
- Sales performance by event/date
- Attendance analytics
- Revenue breakdown by channel
- Customer demographics
- Ticket type performance

### Export Options
- CSV/Excel exports
- PDF reports
- API data access
- Real-time dashboard

## 🔄 Integration Capabilities

### Payment Gateways
- Stripe (cards, Apple Pay, Google Pay)
- PayPal (including Pay Later)
- Bank transfers
- Buy-now-pay-later services

### Third-party Integrations
- CRM systems (Salesforce, HubSpot)
- Email marketing (Mailchimp, SendGrid)
- Accounting (QuickBooks, Xero)
- Analytics (Google Analytics, Mixpanel)

### Webhooks
- Order status changes
- Payment confirmations
- Check-in events
- User registrations

## 🚀 Deployment

### Production Deployment
1. Set up SSL certificate
2. Configure production database
3. Set up Redis for caching (optional)
4. Configure load balancer (for high availability)
5. Set up monitoring and logging
6. Configure backup strategies

### Docker Deployment
```dockerfile
FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader
```

### Environment Variables
Key environment variables for production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
- `JWT_SECRET` (must be unique and secure)
- Payment gateway credentials

## 🧪 Testing

### Run Tests
```bash
composer test
```

### Test Coverage
```bash
composer test-coverage
```

## 📝 Logging

Logs are stored in the `logs/` directory:
- `application.log` - General application logs
- `error.log` - Error logs
- `audit.log` - Security audit logs
- `payment.log` - Payment transaction logs

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and documentation:
- Email: support@eventticketing.com
- Documentation: https://docs.eventticketing.com
- Community Forum: https://community.eventticketing.com

## 🗺️ Roadmap

### Upcoming Features
- Mobile apps (iOS/Android)
- AI-powered demand forecasting
- Advanced seating maps
- Multi-language support
- Advanced analytics with ML
- Blockchain ticket verification
- Virtual event integration

### Version History
- **v2.0** - Enterprise features and scalability
- **v1.5** - Advanced analytics and reporting
- **v1.0** - Core ticketing functionality

---

Built with ❤️ for the event management industry.
