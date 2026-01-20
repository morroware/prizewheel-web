# Prize Wheel - Professional Web Application

A professional, full-featured PHP prize wheel application with enterprise-grade capabilities. Perfect for promotions, events, contests, and gamification. No Raspberry Pi/GPIO dependencies - runs on any standard web server.

## ✨ Core Features

- **Interactive Prize Wheel** - Beautiful animated wheel with advanced customization
- **Dashboard** - Comprehensive admin interface for managing everything
- **Advanced Analytics** - Real-time statistics, charts, and performance metrics
- **Multi-Wheel Support** - Run multiple wheels simultaneously with campaigns
- **Odds Calculator** - Analyze and adjust prize probabilities with simulation
- **Sound Effects** - Customizable audio for spins, wins, and losses
- **Responsive Design** - Works flawlessly on desktop, tablet, and mobile

## 🚀 Professional Features

### Prize Management
- **Categories & Tags** - Organize prizes efficiently
- **Inventory Tracking** - Set stock limits and track remaining prizes
- **Scheduling** - Time-limited prizes with start/end dates
- **Custom Images** - Add visual appeal to your prizes
- **Redemption Codes** - Generate unique codes for prize validation
- **Rich Metadata** - Store custom data with each prize

### Database & Performance
- **SQLite/MySQL Support** - Choose your database backend
- **Advanced Queries** - Complex filtering and aggregations
- **Scalability** - Handle millions of spins efficiently
- **Data Export** - CSV, JSON, Excel formats
- **Backup/Restore** - Automated backup system

### Analytics & Reporting
- **Real-Time Stats** - Live spin counts, win rates, user metrics
- **Performance Charts** - Visualize data with interactive charts
- **Prize Analytics** - Track which prizes perform best
- **Activity Heatmaps** - See when users are most active
- **Conversion Funnels** - Track user journey from view to redemption
- **Custom Reports** - Export data for deeper analysis

### Integration & Automation
- **Webhook System** - Trigger external services on events
- **REST API** - Complete API for integrations
- **API Authentication** - Secure with API keys or JWT
- **Rate Limiting** - Protect against abuse
- **Import/Export** - Bulk operations via CSV/JSON

### Security
- **User Authentication** - Secure admin and user accounts
- **Password Hashing** - Argon2 encryption
- **API Key Management** - Generate and manage access keys
- **Input Validation** - Prevent XSS and SQL injection
- **CSRF Protection** - Secure forms and requests

## Requirements

- PHP 7.4 or higher
- Apache with mod_rewrite (or nginx with equivalent config)
- Write permissions for the `data/` directory

## Installation

1. Upload the `web-only` folder to your web server
2. Ensure the `data/` directory is writable by the web server:
   ```bash
   chmod 755 data/
   ```
3. Copy sound files to `static/sounds/` directory
4. Access the application via your web browser

## Directory Structure

```
prizewheel-web/
├── index.php              # Main application (routing & API)
├── api_extended.php       # Professional API endpoints
├── config.php             # Configuration settings
├── .htaccess              # Apache URL rewriting
├── includes/              # Professional classes
│   ├── Database.php       # Database abstraction layer
│   ├── PrizeManager.php   # Enhanced prize management
│   ├── Analytics.php      # Analytics engine
│   ├── WebhookManager.php # Webhook system
│   ├── WheelManager.php   # Multi-wheel support
│   ├── AuthManager.php    # Authentication & security
│   └── ImportExport.php   # Import/export utilities
├── templates/
│   ├── display.php        # Main wheel display
│   ├── dashboard.php      # Admin dashboard
│   └── odds_calculator.php # Probability analyzer
├── data/                  # Data storage (auto-created)
│   ├── prizewheel.db      # SQLite database
│   ├── prizes.json        # Prize definitions (fallback)
│   ├── config.json        # Configuration
│   ├── customization.json # Theme settings
│   ├── backups/           # Automated backups
│   └── presets/           # Theme presets
└── static/
    ├── sounds/            # Audio files
    └── images/            # Image assets
```

## Pages

- `/` or `/display` - Main prize wheel display
- `/dashboard` - Admin dashboard for managing prizes and settings
- `/odds` - Probability calculator and simulation tool

## API Endpoints

### Basic Prizes
- `GET /api/prizes` - Get all prizes
- `POST /api/prizes` - Create new prize
- `PUT /api/prizes/{id}` - Update prize
- `DELETE /api/prizes/{id}` - Delete prize

### Enhanced Prizes (Professional)
- `GET /api/prizes/enhanced` - Get prizes with filtering
- `POST /api/prizes/enhanced` - Create prize with advanced features
- `PUT /api/prizes/enhanced/{id}` - Update prize with full options
- `GET /api/prizes/categories` - Get all prize categories

### Spin & State
- `POST /api/spin` - Trigger a spin
- `GET /api/spin/status` - Get current spin status
- `POST /api/spin/complete` - Mark spin as complete
- `POST /api/state/reset` - Reset wheel state

### Analytics (Professional)
- `GET /api/analytics/stats` - Comprehensive statistics
- `GET /api/analytics/prizes` - Prize performance metrics
- `GET /api/analytics/timeline` - Spins over time
- `GET /api/analytics/categories` - Category breakdown
- `GET /api/analytics/heatmap` - Hourly activity heatmap
- `GET /api/analytics/funnel` - Conversion funnel analysis
- `GET /api/analytics/export/csv` - Export analytics data

### Multi-Wheel (Professional)
- `GET /api/wheels` - List all wheels
- `POST /api/wheels` - Create new wheel
- `GET /api/wheels/{slug}` - Get wheel by slug
- `PUT /api/wheels/{id}` - Update wheel
- `DELETE /api/wheels/{id}` - Delete wheel
- `POST /api/wheels/{id}/clone` - Clone wheel with prizes
- `GET /api/wheels/{id}/stats` - Wheel statistics

### Webhooks (Professional)
- `POST /api/webhooks` - Register webhook
- `GET /api/webhooks` - List webhooks
- `PUT /api/webhooks/{id}` - Update webhook
- `DELETE /api/webhooks/{id}` - Delete webhook

### Import/Export (Professional)
- `POST /api/import/prizes/csv` - Import prizes from CSV
- `POST /api/import/prizes/json` - Import prizes from JSON
- `GET /api/export/prizes/csv` - Export prizes to CSV
- `GET /api/export/prizes/json` - Export prizes to JSON
- `POST /api/backup/create` - Create full backup
- `GET /api/backup/list` - List available backups
- `POST /api/backup/restore` - Restore from backup
- `GET /api/backup/download/{file}` - Download backup file

### Authentication (Professional)
- `POST /api/auth/login` - User login (JWT)
- `POST /api/auth/register` - Create user account
- `POST /api/auth/api-keys` - Generate API key
- `GET /api/auth/api-keys` - List API keys
- `DELETE /api/auth/api-keys/{id}` - Revoke API key

### Configuration
- `GET /api/config` - Get configuration
- `POST /api/config` - Save configuration
- `GET /api/customization` - Get customization settings
- `POST /api/customization` - Save customization
- `POST /api/customization/apply-preset` - Apply theme preset
- `POST /api/customization/reset` - Reset to defaults
- `GET /api/customization/export` - Export theme
- `POST /api/customization/import` - Import theme
- `GET /api/dashboard_data` - Get all dashboard data

### Media
- `GET /api/sounds/list` - List available sounds
- `POST /api/upload/sound` - Upload sound file
- `GET /api/images/list` - List available images
- `POST /api/upload/image` - Upload image file

### Statistics
- `DELETE /api/stats` - Clear spin history
- `GET /api/export/csv` - Export history as CSV

### Odds Calculator
- `GET /api/odds/prizes` - Get prizes for calculator
- `POST /api/odds/simulate` - Run probability simulation

## Differences from Original Version

This web-only version differs from the original Raspberry Pi version:

1. **No GPIO Support** - No hardware button integration
2. **Polling-based Updates** - Uses REST API polling instead of WebSockets/Socket.IO
3. **PHP Backend** - Replaces Python/Flask with pure PHP
4. **File-based Storage** - Uses JSON files instead of requiring a database
5. **Simplified State Management** - Uses file locking for concurrency

## Configuration Options

Edit settings via the Dashboard or directly in `data/config.json`:

| Setting | Default | Description |
|---------|---------|-------------|
| spin_duration_seconds | 8 | How long the wheel spins |
| cooldown_seconds | 3 | Delay between spins |
| volume | 75 | Master volume (0-100) |
| modal_delay_ms | 4500 | Delay before winner modal appears |
| modal_auto_close_ms | 6000 | How long modal stays open |
| winner_flash_duration_ms | 4000 | Winner segment flash duration |

## 🎯 Quick Start - Professional Features

### Enable Database Mode

1. Edit `config.php` to enable database support:
   ```php
   define('USE_DATABASE', true);
   ```

2. Enable extended API in `index.php` (add before 404 handler):
   ```php
   if (defined('ENABLE_EXTENDED_API') && ENABLE_EXTENDED_API) {
       require_once __DIR__ . '/api_extended.php';
   }
   ```

3. Access your wheel - the database will be created automatically!

### Documentation

- **Full Features Guide**: See [FEATURES.md](FEATURES.md) for complete documentation
- **Upgrade Guide**: See [UPGRADE_GUIDE.md](UPGRADE_GUIDE.md) for migration instructions
- **API Reference**: All endpoints documented with examples

### Example: Create a Prize with Inventory

```bash
curl -X POST http://your-site.com/api/prizes/enhanced \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Limited Edition Prize",
    "category": "Premium",
    "weight": 0.5,
    "color": "#FFD700",
    "stock_enabled": true,
    "stock_total": 100,
    "stock_remaining": 100,
    "start_date": "2026-06-01 00:00:00",
    "end_date": "2026-12-31 23:59:59"
  }'
```

### Example: Get Analytics

```bash
curl http://your-site.com/api/analytics/stats?wheel_id=default
```

Returns:
```json
{
  "total_spins": 1543,
  "total_wins": 428,
  "win_rate": 27.74,
  "unique_sessions": 892,
  "avg_spins_per_session": 1.73
}
```

## 🎨 Customization Levels

This prize wheel offers three levels of customization:

### Level 1: Basic (Dashboard UI)
- Colors, fonts, branding
- Prize configuration
- Sound effects
- Display settings

### Level 2: Advanced (JSON Configuration)
- Animation settings
- Effect parameters
- Modal styling
- Wheel geometry
- Theme presets

### Level 3: Professional (Code)
- Custom CSS injection
- Custom JavaScript
- Plugin development
- API integrations

## 🌟 Use Cases

### Retail & E-commerce
- In-store promotions
- Online spin-to-win campaigns
- Loyalty program rewards
- Holiday campaigns

### Events & Conferences
- Booth engagement tools
- Attendee prize systems
- Networking game mechanics
- Swag distribution

### Gaming & Entertainment
- Streaming giveaways
- In-game reward systems
- Community events
- Tournament prizes

### Marketing & Lead Generation
- Email campaign incentives
- Social media contests
- Website engagement tools
- Lead capture mechanics

## 📊 What Makes This Professional?

### Enterprise-Ready
- Handles high traffic with database backend
- Scalable architecture
- Security best practices
- Professional code structure

### Full-Featured
- Every feature you need out of the box
- Extensive API for custom integrations
- Multiple data export options
- Comprehensive analytics

### Customizable
- 8+ built-in themes
- Complete visual customization
- Custom code injection
- Theme import/export

### Well-Documented
- Complete API documentation
- Detailed feature guides
- Code examples
- Upgrade instructions

## 🔧 System Requirements

### Minimum
- PHP 7.4+
- Apache/Nginx
- 50MB disk space
- SQLite support (built into PHP)

### Recommended for Production
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- 500MB disk space
- SSL certificate
- CDN for static assets

## 🤝 Support & Community

- **Issues**: Report bugs and feature requests
- **Documentation**: Comprehensive guides included
- **Updates**: Regular feature additions

## 📝 License

Same license as the parent project.

## 🎉 Credits

Enhanced with professional features including:
- Database layer with SQLite/MySQL support
- Advanced analytics and reporting
- Multi-wheel campaign management
- Webhook integration system
- Complete REST API
- Import/export tools
- Authentication & security
- And much more!

**Transform your prize wheel into a professional, enterprise-ready application!**
