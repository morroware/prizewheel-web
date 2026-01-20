# Upgrade Guide - Professional Prize Wheel

This guide will help you enable and configure the professional features added to your prize wheel.

## Quick Start

### 1. Enable Database Mode

The professional features require a database for optimal performance. By default, SQLite is used (no setup required).

**Edit index.php** and add at the top (after session_start()):

```php
// Include configuration
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    require_once __DIR__ . '/config.php';
}
```

### 2. Enable Extended API

**In index.php**, find the section where API routes end (before the 404 handler) and add:

```php
// Extended API endpoints
if (defined('ENABLE_EXTENDED_API') && ENABLE_EXTENDED_API) {
    require_once __DIR__ . '/api_extended.php';
}
```

### 3. Set Permissions

Make sure the data directory is writable:

```bash
chmod 755 data/
chmod 755 includes/
```

### 4. Initialize Database

Visit your prize wheel URL. The database will be created automatically on first run if using SQLite.

## Feature Configuration

### Database Options

#### SQLite (Default - Recommended for most users)
No configuration needed. The database file will be created automatically at `data/prizewheel.db`.

#### MySQL (For high-traffic sites)

1. Create a MySQL database
2. Edit `config.php`:

```php
define('USE_DATABASE', true);
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'prizewheel');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Security Configuration

**Change the JWT secret** in `config.php`:

```php
define('JWT_SECRET', 'your-random-secret-key-here');
```

Generate a secure key:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Enable Specific Features

In `config.php`, enable/disable features:

```php
define('ENABLE_ANALYTICS', true);      // Advanced analytics
define('ENABLE_WEBHOOKS', true);       // Webhook notifications
define('ENABLE_MULTI_WHEEL', true);    // Multiple wheels
define('ENABLE_AUTH', true);           // User authentication
define('ENABLE_IMPORT_EXPORT', true);  // Import/export tools
```

## Migration from JSON to Database

If you have existing prizes in JSON format:

### Automatic Migration

The system will continue to work with JSON files. Database features will only activate when `USE_DATABASE` is true.

### Manual Migration

1. Enable database mode
2. Use the import API:

```bash
curl -X POST http://your-site.com/api/import/prizes/json \
  -H "Content-Type: application/json" \
  -d @data/prizes.json
```

Or import via the dashboard UI (coming soon).

## New API Endpoints

Once enabled, you'll have access to:

### Enhanced Prizes
- `GET /api/prizes/enhanced` - Get prizes with filters
- `POST /api/prizes/enhanced` - Create prize with advanced features
- `GET /api/prizes/categories` - Get prize categories

### Analytics
- `GET /api/analytics/stats` - Spin statistics
- `GET /api/analytics/prizes` - Prize performance
- `GET /api/analytics/timeline` - Spins over time
- `GET /api/analytics/heatmap` - Activity heatmap

### Multi-Wheel
- `GET /api/wheels` - List all wheels
- `POST /api/wheels` - Create new wheel
- `POST /api/wheels/{id}/clone` - Clone wheel
- `GET /api/wheels/{id}/stats` - Wheel statistics

### Webhooks
- `POST /api/webhooks` - Register webhook
- `GET /api/webhooks` - List webhooks
- `DELETE /api/webhooks/{id}` - Remove webhook

### Import/Export
- `POST /api/import/prizes/csv` - Import from CSV
- `GET /api/export/prizes/csv` - Export to CSV
- `POST /api/backup/create` - Create backup
- `GET /api/backup/list` - List backups

### Authentication
- `POST /api/auth/api-keys` - Generate API key
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - Create account

See [FEATURES.md](FEATURES.md) for complete API documentation.

## Testing the Installation

### 1. Check Database

Visit your wheel and spin it once. Then check:

```bash
ls -la data/prizewheel.db
```

Should show the database file.

### 2. Test Analytics

```bash
curl http://your-site.com/api/analytics/stats?wheel_id=default
```

Should return statistics.

### 3. Test Enhanced Prizes

```bash
curl http://your-site.com/api/prizes/enhanced
```

Should return prize list.

## Troubleshooting

### "Database not enabled" Error

- Make sure `USE_DATABASE` is true in config.php
- Check that data/ directory is writable
- Verify PHP has SQLite or MySQL extension installed

### Permission Denied Errors

```bash
chmod -R 755 data/
chmod -R 755 includes/
chown -R www-data:www-data data/  # Linux/Apache
```

### API Endpoints Return 404

Make sure you added the `require_once` for `api_extended.php` in index.php.

### Can't Import CSV

- Check file format (UTF-8 encoding)
- Verify required columns: name, weight, color
- Check max upload size in php.ini:

```ini
upload_max_filesize = 10M
post_max_size = 10M
```

## Performance Optimization

### For High Traffic

1. **Use MySQL instead of SQLite**
2. **Enable caching in config.php**:
   ```php
   define('ENABLE_CACHE', true);
   define('CACHE_DURATION', 300);
   ```
3. **Set up automated backups**
4. **Use a CDN for static assets**

### Database Optimization

```sql
-- For MySQL, add indexes
CREATE INDEX idx_spin_created ON spin_history(created_at);
CREATE INDEX idx_prize_category ON prizes(category);

-- Analyze tables periodically
ANALYZE TABLE prizes;
ANALYZE TABLE spin_history;
```

## Backup & Restore

### Manual Backup

```bash
# Backup database
cp data/prizewheel.db data/backups/prizewheel_$(date +%Y%m%d).db

# Or use API
curl -X POST http://your-site.com/api/backup/create
```

### Automated Backups

Set in config.php:
```php
define('AUTO_BACKUP_INTERVAL', 24);  // Hours
define('MAX_BACKUP_COUNT', 30);
```

Add to cron:
```bash
0 2 * * * curl -X POST http://your-site.com/api/backup/create
```

### Restore Backup

```bash
curl -X POST http://your-site.com/api/backup/restore \
  -F "file=@backup_2026-01-20.zip"
```

## Webhooks Setup

### Register a Webhook

```bash
curl -X POST http://your-site.com/api/webhooks \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-app.com/webhook",
    "events": ["prize.won", "prize.redeemed"],
    "secret": "your_webhook_secret"
  }'
```

### Verify Webhook Signature (Your Server)

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

$expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expectedSignature, $signature)) {
    // Valid webhook
    $data = json_decode($payload, true);
    // Process event
}
```

## Multi-Wheel Setup

### Create a New Wheel

```bash
curl -X POST http://your-site.com/api/wheels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Summer Campaign",
    "slug": "summer-2026",
    "description": "Summer promotion",
    "is_active": true,
    "campaign_start": "2026-06-01 00:00:00",
    "campaign_end": "2026-08-31 23:59:59",
    "max_spins_per_user": 5
  }'
```

### Display Specific Wheel

Modify your display page URL:
```
http://your-site.com/display?wheel=summer-2026
```

## Advanced Customization

### Custom Prize Fields

Add metadata to prizes:

```json
{
  "name": "Premium Prize",
  "metadata": {
    "sku": "PREM-001",
    "vendor": "Acme Corp",
    "cost": 25.99,
    "margin": 45.5
  }
}
```

### Custom Analytics Events

Track custom events:

```javascript
fetch('/api/analytics/event', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    type: 'custom_event',
    data: {
      action: 'button_click',
      button: 'spin_now',
      timestamp: Date.now()
    }
  })
});
```

## Next Steps

1. **Explore the Dashboard** - Visit `/dashboard` to see the new features
2. **Read the API Docs** - See [FEATURES.md](FEATURES.md)
3. **Set Up Webhooks** - Integrate with your systems
4. **Configure Analytics** - Track important metrics
5. **Create Multiple Wheels** - Set up campaigns

## Support

- **Documentation**: See FEATURES.md and README.md
- **Issues**: Check the issues page
- **Community**: Join our forum

## Rollback

To disable professional features and return to JSON-only mode:

1. Set in config.php:
   ```php
   define('USE_DATABASE', false);
   define('ENABLE_EXTENDED_API', false);
   ```

2. Your JSON files will continue to work as before

3. Database data is preserved for when you re-enable

## Version Compatibility

- PHP 7.4+
- SQLite 3.x or MySQL 5.7+
- Modern browsers (Chrome, Firefox, Safari, Edge)

---

**Congratulations!** Your prize wheel is now a professional, full-featured application! 🎉
