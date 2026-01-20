# Prize Wheel - Professional Features Documentation

## Overview

This professional prize wheel system has been enhanced with enterprise-grade features including advanced prize management, analytics, multi-wheel support, webhooks, and comprehensive customization options.

---

## 🎯 Core Features

### 1. **Enhanced Prize Management**

#### Advanced Prize Properties
- **Categories & Tags**: Organize prizes into categories with custom tags
- **Inventory Tracking**: Set stock limits and track remaining prizes
- **Scheduling**: Configure start/end dates for time-limited prizes
- **Custom Images**: Add custom images and icons to prizes
- **Redemption Codes**: Generate unique codes for prize redemption
- **Rich Metadata**: Store custom data with each prize

#### Prize API Endpoints
```
GET    /api/prizes/enhanced?wheel_id={id}&category={cat}
POST   /api/prizes/enhanced
PUT    /api/prizes/enhanced/{id}
DELETE /api/prizes/enhanced/{id}
GET    /api/prizes/categories?wheel_id={id}
```

#### Example Prize Structure
```json
{
  "name": "Grand Prize",
  "description": "Amazing grand prize",
  "category": "Premium",
  "tags": ["featured", "limited"],
  "weight": 0.5,
  "color": "#FFD700",
  "is_winner": true,
  "image_url": "/static/images/grand-prize.png",
  "icon": "🏆",
  "stock_enabled": true,
  "stock_total": 10,
  "stock_remaining": 8,
  "start_date": "2026-01-01 00:00:00",
  "end_date": "2026-12-31 23:59:59",
  "redemption_code": "GRAND2026",
  "redemption_url": "https://example.com/redeem"
}
```

---

### 2. **Database Layer (SQLite/MySQL)**

#### Benefits
- **Better Performance**: Faster queries for large datasets
- **Advanced Queries**: Complex filtering and aggregations
- **Data Integrity**: Foreign keys and constraints
- **Scalability**: Handle millions of spins efficiently
- **Concurrent Access**: Better handling of simultaneous users

#### Database Tables
- `prizes` - Enhanced prize definitions
- `spin_history` - Complete spin tracking
- `wheels` - Multiple wheel configurations
- `users` - User accounts and authentication
- `api_keys` - API authentication
- `webhooks` - Webhook registrations
- `redemptions` - Prize redemption tracking
- `analytics_events` - Event tracking

#### Configuration
```php
$dbConfig = [
    'type' => 'sqlite',  // or 'mysql'
    'path' => DATA_DIR . 'prizewheel.db'
    // For MySQL:
    // 'host' => 'localhost',
    // 'database' => 'prizewheel',
    // 'username' => 'user',
    // 'password' => 'pass'
];

$db = new Database($dbConfig);
```

---

### 3. **Advanced Analytics**

#### Real-Time Statistics
- Total spins, wins, win rate
- Unique sessions and users
- Active prizes count
- Average spins per session

#### Performance Metrics
- Prize performance analysis
- Category breakdown
- Hourly activity heatmap
- Spins over time (hourly/daily)
- Conversion funnel tracking

#### API Endpoints
```
GET /api/analytics/stats?wheel_id={id}&from={date}&to={date}
GET /api/analytics/prizes?wheel_id={id}
GET /api/analytics/timeline?wheel_id={id}&interval=day
GET /api/analytics/categories?wheel_id={id}
GET /api/analytics/heatmap?wheel_id={id}
GET /api/analytics/funnel?wheel_id={id}
GET /api/analytics/export/csv?wheel_id={id}
```

#### Example Response
```json
{
  "total_spins": 1543,
  "total_wins": 428,
  "win_rate": 27.74,
  "unique_sessions": 892,
  "active_days": 14,
  "avg_spins_per_session": 1.73
}
```

---

### 4. **Multi-Wheel System**

#### Features
- Run multiple wheels simultaneously
- Each wheel with unique configuration
- Campaign scheduling (start/end dates)
- Per-wheel customization
- User spin limits per wheel
- Authentication requirements

#### Wheel Configuration
```json
{
  "name": "Summer Campaign",
  "slug": "summer-2026",
  "description": "Summer promotion wheel",
  "customization": { ... },
  "is_active": true,
  "campaign_start": "2026-06-01 00:00:00",
  "campaign_end": "2026-08-31 23:59:59",
  "max_spins_per_user": 5,
  "cooldown_seconds": 5,
  "requires_auth": true
}
```

#### API Endpoints
```
GET    /api/wheels
POST   /api/wheels
GET    /api/wheels/{slug}
PUT    /api/wheels/{id}
DELETE /api/wheels/{id}
POST   /api/wheels/{id}/clone
GET    /api/wheels/{id}/stats
```

---

### 5. **Webhook System**

#### Supported Events
- `spin.started` - When a spin begins
- `spin.completed` - When a spin finishes
- `prize.won` - When a prize is won
- `prize.redeemed` - When a prize is redeemed
- `prize.created` - When a new prize is added
- `prize.updated` - When a prize is modified
- `prize.stock_low` - When stock falls below threshold
- `*` - Subscribe to all events

#### Webhook Registration
```json
POST /api/webhooks
{
  "url": "https://your-server.com/webhook",
  "events": ["prize.won", "prize.redeemed"],
  "secret": "your_webhook_secret"
}
```

#### Webhook Payload
```json
{
  "event": "prize.won",
  "payload": {
    "spin_id": "spin_123abc",
    "prize": {
      "id": 5,
      "name": "Grand Prize",
      "category": "Premium"
    },
    "session_id": "sess_xyz789",
    "user_id": "user_456"
  },
  "timestamp": "2026-01-20T10:30:00+00:00",
  "webhook_id": 1
}
```

#### Security
Webhooks include `X-Webhook-Signature` header with HMAC-SHA256 signature for verification.

---

### 6. **API Authentication**

#### API Key Authentication
```
GET /api/prizes
Authorization: Bearer pk_your_api_key_here
```

#### Generate API Key
```json
POST /api/auth/api-keys
{
  "name": "Mobile App",
  "permissions": ["read:prizes", "write:spins"],
  "rate_limit": 1000,
  "expires_at": "2027-01-01"
}
```

#### JWT Authentication
```json
POST /api/auth/login
{
  "username": "admin",
  "password": "password"
}

Response:
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600
}
```

#### Rate Limiting
- Per API key rate limits
- Configurable window (default: 60 seconds)
- Returns `429 Too Many Requests` when exceeded
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

---

### 7. **Import/Export System**

#### Prize Import
- **CSV Import**: Bulk import prizes from CSV files
- **JSON Import**: Import from JSON format
- **Theme Import**: Import complete theme packages

#### Export Formats
- **CSV**: Prizes, spin history, analytics
- **JSON**: Full data export
- **Excel**: Enhanced exports with formatting (planned)

#### Backup/Restore
```
POST /api/backup/create
GET  /api/backup/list
POST /api/backup/restore
GET  /api/backup/download/{filename}
```

#### CSV Format
```csv
name,description,category,weight,color,is_winner,stock_total,enabled
Grand Prize,Amazing prize,Premium,0.5,#FFD700,true,10,true
Try Again,Better luck next time,Standard,15.0,#9E9E9E,false,0,true
```

---

### 8. **Visual Customization Studio** (Planned)

#### Features
- Live preview editor
- Drag-and-drop interface
- Color picker with presets
- Font selector
- Animation controls
- Effect toggles
- Export/import themes

---

### 9. **Redemption System**

#### Features
- Unique redemption codes
- QR code generation
- Expiration dates
- Usage tracking
- Redemption validation

#### API Endpoints
```
POST /api/redemptions/generate
GET  /api/redemptions/validate/{code}
POST /api/redemptions/redeem/{code}
GET  /api/redemptions/history
```

---

## 📊 Analytics Dashboard

The analytics dashboard provides comprehensive insights:

### Key Metrics
- Real-time spin counter
- Win rate percentage
- Active users
- Revenue tracking (with payment integration)

### Charts & Visualizations
- Line charts for spins over time
- Bar charts for prize performance
- Pie charts for category distribution
- Heatmaps for activity patterns

### Filters
- Date range selection
- Wheel filtering
- Category filtering
- Export options

---

## 🔧 Configuration Options

### Environment Variables
```php
// Enable database mode
define('USE_DATABASE', true);

// Database configuration
define('DB_TYPE', 'sqlite'); // or 'mysql'
define('DB_PATH', DATA_DIR . 'prizewheel.db');

// API settings
define('API_RATE_LIMIT', 100);
define('JWT_SECRET', 'your-secret-key');

// Webhook settings
define('WEBHOOK_TIMEOUT', 10);
define('WEBHOOK_RETRY_COUNT', 3);
```

---

## 🚀 Performance Optimization

### Caching
- Database query caching
- Static asset caching
- API response caching

### Optimizations
- Indexed database queries
- Batch operations
- Lazy loading
- Asset minification

---

## 🔐 Security Features

### Authentication
- Password hashing (Argon2)
- API key validation
- JWT token verification
- Session management

### Protection
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF tokens
- Rate limiting
- Input validation
- File upload restrictions

---

## 🎨 Customization Levels

### Level 1: Basic
- Colors, fonts, branding
- Prize names and colors
- Sound effects

### Level 2: Advanced
- Animation settings
- Effect configurations
- Modal customization
- Wheel geometry

### Level 3: Professional
- Custom CSS injection
- Custom JavaScript
- Plugin system
- Complete theming

---

## 📱 Mobile Support

- Responsive design
- Touch-friendly controls
- Mobile-optimized UI
- QR code scanning
- Progressive Web App (PWA) ready

---

## 🔌 Integration Options

### Payment Gateways
- Stripe integration
- PayPal support
- Custom payment processors

### Email/SMS
- Prize notifications
- Winner confirmations
- Marketing campaigns

### Social Media
- Share wins on social media
- Social login integration
- Viral sharing features

---

## 📖 API Documentation

Full API documentation available at: `/api/docs`

### Interactive API Explorer
- Try endpoints directly
- View request/response examples
- Authentication testing
- Rate limit monitoring

---

## 🎯 Use Cases

### 1. **Retail Promotions**
- In-store prize wheel
- Loyalty program rewards
- Holiday campaigns

### 2. **Events & Conferences**
- Booth engagement
- Attendee prizes
- Gamification

### 3. **Online Marketing**
- Website engagement
- Email campaigns
- Social media contests

### 4. **Gaming & Entertainment**
- In-game rewards
- Streaming giveaways
- Community events

---

## 🛠️ Developer Tools

### Debug Mode
```php
define('DEBUG_MODE', true);
```

### Logging
- Error logging
- Event logging
- Performance logging
- Audit trails

### Testing
- Unit tests
- Integration tests
- API tests
- Load testing

---

## 📦 Plugin System (Planned)

Create custom plugins to extend functionality:

```php
class CustomPrizePlugin extends WheelPlugin {
    public function onSpin($prize, $user) {
        // Custom logic
    }

    public function onWin($prize, $user) {
        // Send notification
    }
}
```

---

## 🌐 Multi-Language Support (Planned)

- Translation system
- RTL support
- Locale-specific formatting
- Custom language packs

---

## 📊 Reporting

### Standard Reports
- Daily spin summary
- Prize distribution
- User engagement
- Revenue reports

### Custom Reports
- Query builder
- Scheduled reports
- Email delivery
- PDF export

---

## 🎓 Getting Started

### Quick Setup
1. Upload files to server
2. Set permissions: `chmod 755 data/`
3. Initialize database: Visit `/setup`
4. Configure settings in dashboard
5. Add prizes and customize

### Documentation
- Full user guide: `/docs/user-guide.pdf`
- API reference: `/api/docs`
- Video tutorials: `/docs/videos`

---

## 🤝 Support

- GitHub Issues: [Report bugs](https://github.com/your-repo/issues)
- Documentation: [Full docs](https://docs.prizewheel.com)
- Community: [Forum](https://community.prizewheel.com)

---

## 📄 License

Same license as parent project.

---

## 🎉 Conclusion

This professional prize wheel system provides everything needed for enterprise-level deployments with advanced features, robust architecture, and extensive customization options.
