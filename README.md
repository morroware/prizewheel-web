# Prize Wheel - Web Only Version

A standalone PHP web application for the Castle Kingdom Prize Wheel. This version has no Raspberry Pi/GPIO dependencies and can run on any standard web server with PHP.

## Features

- **Interactive Prize Wheel** - Beautiful animated wheel with configurable prizes
- **Dashboard** - Manage prizes, settings, and view statistics
- **Odds Calculator** - Analyze and adjust prize probabilities
- **Sound Effects** - Customizable audio for spins, wins, and losses
- **Responsive Design** - Works on desktop, tablet, and mobile

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
web-only/
├── index.php           # Main PHP application (routing & API)
├── .htaccess           # Apache URL rewriting rules
├── templates/
│   ├── display.php     # Main wheel display page
│   ├── dashboard.php   # Admin dashboard
│   └── odds_calculator.php  # Probability analyzer
├── data/               # JSON data storage (auto-created)
│   ├── config.json     # Configuration settings
│   ├── prizes.json     # Prize definitions
│   ├── history.json    # Spin history
│   └── state.json      # Wheel state
└── static/
    ├── sounds/         # Audio files
    └── images/         # Image assets
```

## Pages

- `/` or `/display` - Main prize wheel display
- `/dashboard` - Admin dashboard for managing prizes and settings
- `/odds` - Probability calculator and simulation tool

## API Endpoints

### Prizes
- `GET /api/prizes` - Get all prizes
- `POST /api/prizes` - Create new prize
- `PUT /api/prizes/{id}` - Update prize
- `DELETE /api/prizes/{id}` - Delete prize

### Spin
- `POST /api/spin` - Trigger a spin
- `GET /api/spin/status` - Get current spin status
- `POST /api/spin/complete` - Mark spin as complete

### Configuration
- `GET /api/config` - Get configuration
- `POST /api/config` - Save configuration
- `GET /api/dashboard_data` - Get all dashboard data

### Sounds
- `GET /api/sounds/list` - List available sounds
- `POST /api/upload/sound` - Upload a sound file

### Statistics
- `DELETE /api/stats` - Clear spin history
- `GET /api/export/csv` - Export history as CSV

### Odds
- `GET /api/odds/prizes` - Get prizes for odds calculator
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

## License

Same license as the parent project.
