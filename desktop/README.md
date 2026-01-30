# Prize Wheel Desktop Application

Windows desktop application wrapper for the Prize Wheel web app.

## Features

- **Fullscreen kiosk mode** - Perfect for public displays
- **USB HID keyboard support** - Works with any HID device that sends keyboard input (SPACE triggers spin)
- **Admin access via keyboard shortcuts** - Easy navigation without a mouse
- **Bundled PHP server** - No external dependencies needed

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `SPACE` | Spin the wheel |
| `F1` | Go to Prize Wheel display |
| `F2` | Go to Admin Dashboard |
| `F3` | Go to Odds Calculator |
| `F5` | Refresh page |
| `F11` | Toggle fullscreen |
| `F12` | Toggle developer tools |
| `ESC` | Exit fullscreen |
| `Ctrl+Shift+D` | Quick switch to Dashboard |
| `Ctrl+Shift+W` | Quick switch to Wheel |

## Setup Instructions

### Prerequisites

1. **Node.js 18+** - Download from https://nodejs.org/
2. **PHP 8.x for Windows** - Download from https://windows.php.net/download/

### Step 1: Download PHP

1. Go to https://windows.php.net/download/
2. Download **VS16 x64 Non Thread Safe** ZIP (php-8.x.x-nts-Win32-vs16-x64.zip)
3. Extract the contents to `desktop/php/` folder
4. Rename `php.ini-production` to `php.ini`
5. Edit `php.ini` and enable these extensions:
   ```ini
   extension=fileinfo
   extension_dir = "ext"
   ```

Your `desktop/php/` folder should look like:
```
desktop/php/
├── php.exe
├── php.ini
├── ext/
│   ├── php_fileinfo.dll
│   └── ...
└── ...
```

### Step 2: Install Dependencies

```bash
cd desktop
npm install
```

### Step 3: Run in Development Mode

```bash
npm start
```

### Step 4: Build Windows Installer

```bash
npm run build
```

The installer will be created in `desktop/dist/`.

### Step 5: Build Portable Version (No Installation Required)

```bash
npm run build:portable
```

## Auto-Start on Windows Boot (Kiosk Mode)

To have the app start automatically when Windows boots:

1. Press `Win + R`, type `shell:startup`, press Enter
2. Create a shortcut to `Prize Wheel.exe` in this folder

Or use Task Scheduler for more control:
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: "At log on"
4. Action: Start a program
5. Browse to `Prize Wheel.exe`

## HID Gadget Configuration

The app responds to **SPACE** key to trigger spins. Configure your HID gadget to send SPACE (USB HID keycode 0x2C) when the button is pressed.

### Common HID Gadget Options:

- **Arduino Leonardo/Pro Micro** - Use Keyboard.write(' ') or Keyboard.press(KEY_SPACE)
- **Raspberry Pi Pico** - Use CircuitPython HID library
- **Commercial USB buttons** - Configure to send SPACE key
- **DIY with ATmega32U4** - Program as USB HID keyboard

Example Arduino code:
```cpp
#include <Keyboard.h>

const int BUTTON_PIN = 2;
bool lastState = HIGH;

void setup() {
  pinMode(BUTTON_PIN, INPUT_PULLUP);
  Keyboard.begin();
}

void loop() {
  bool currentState = digitalRead(BUTTON_PIN);

  if (lastState == HIGH && currentState == LOW) {
    // Button pressed - send SPACE
    Keyboard.write(' ');
    delay(50); // Debounce
  }

  lastState = currentState;
  delay(10);
}
```

## Troubleshooting

### PHP Server Won't Start
- Ensure `php.exe` is in `desktop/php/`
- Check that port 8847 is not in use
- Check PHP error output in the console (F12 > Console)

### App Crashes on Startup
- Run from command line to see error messages: `Prize Wheel.exe`
- Check that all files were bundled correctly

### HID Button Not Working
- Ensure your HID device is configured to send SPACE key
- Try pressing SPACE on a regular keyboard to verify the app responds
- Check that the wheel is in "Ready" state (not spinning or on cooldown)

### Fullscreen Issues
- Press F11 to toggle fullscreen
- Press ESC to exit fullscreen
- Check display resolution settings

## File Structure

```
desktop/
├── package.json        # NPM configuration and build settings
├── main.js            # Electron main process
├── preload.js         # Preload script for security
├── assets/
│   └── icon.ico       # Application icon (add your own)
├── php/               # PHP runtime (download separately)
│   ├── php.exe
│   ├── php.ini
│   └── ext/
└── dist/              # Built installers (after npm run build)
```

## Development

To modify the web application, edit files in the parent directory. Changes will be reflected immediately when you refresh (F5).

The Electron wrapper bundles:
- All PHP files and templates
- Static assets (sounds, images)
- Data directory (prizes, customization)
- PHP runtime

## License

MIT License
