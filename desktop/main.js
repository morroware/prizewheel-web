const { app, BrowserWindow, globalShortcut, Menu, dialog, shell } = require('electron');
const { spawn, execSync } = require('child_process');
const path = require('path');
const fs = require('fs');
const net = require('net');

// Configuration
const PHP_PORT = 8847;
const PHP_HOST = '127.0.0.1';

let mainWindow;
let phpProcess;
let isDevMode = !app.isPackaged;

// User data directory for writable files (uploads, config, etc.)
function getUserDataDir() {
  return path.join(app.getPath('userData'), 'app-data');
}

// Copy default data and sounds to userData on first run
function initUserData() {
  const userDataDir = getUserDataDir();
  const webappPath = getWebappPath();

  // Create user data directories
  const userDataData = path.join(userDataDir, 'data');
  const userSoundsDir = path.join(userDataDir, 'static', 'sounds');

  if (!fs.existsSync(userDataData)) {
    fs.mkdirSync(userDataData, { recursive: true });
    // Copy default data files
    const srcData = path.join(webappPath, 'data');
    if (fs.existsSync(srcData)) {
      const files = fs.readdirSync(srcData);
      for (const file of files) {
        const srcFile = path.join(srcData, file);
        if (fs.statSync(srcFile).isFile()) {
          fs.copyFileSync(srcFile, path.join(userDataData, file));
        }
      }
      // Copy presets subdirectory if exists
      const srcPresets = path.join(srcData, 'presets');
      if (fs.existsSync(srcPresets)) {
        const destPresets = path.join(userDataData, 'presets');
        fs.mkdirSync(destPresets, { recursive: true });
        const presetFiles = fs.readdirSync(srcPresets);
        for (const file of presetFiles) {
          const srcFile = path.join(srcPresets, file);
          if (fs.statSync(srcFile).isFile()) {
            fs.copyFileSync(srcFile, path.join(destPresets, file));
          }
        }
      }
    }
  }

  if (!fs.existsSync(userSoundsDir)) {
    fs.mkdirSync(userSoundsDir, { recursive: true });
    // Copy default sounds
    const srcSounds = path.join(webappPath, 'static', 'sounds');
    if (fs.existsSync(srcSounds)) {
      const files = fs.readdirSync(srcSounds);
      for (const file of files) {
        const srcFile = path.join(srcSounds, file);
        if (fs.statSync(srcFile).isFile()) {
          fs.copyFileSync(srcFile, path.join(userSoundsDir, file));
        }
      }
    }
  }

  console.log('User data directory:', userDataDir);
  return userDataDir;
}

// Paths differ between development and production
function getResourcePath(relativePath) {
  if (isDevMode) {
    return path.join(__dirname, relativePath);
  }
  return path.join(process.resourcesPath, relativePath);
}

function getWebappPath() {
  if (isDevMode) {
    // In dev mode, webapp is parent directory
    return path.join(__dirname, '..');
  }
  return path.join(process.resourcesPath, 'webapp');
}

function getPHPPath() {
  if (isDevMode) {
    // In dev mode, check if PHP is in PATH or local php folder
    const localPHP = path.join(__dirname, 'php', 'php.exe');
    if (fs.existsSync(localPHP)) {
      return localPHP;
    }
    // Fall back to system PHP
    return 'php';
  }
  return path.join(process.resourcesPath, 'php', 'php.exe');
}

// Check if port is available
function isPortAvailable(port) {
  return new Promise((resolve) => {
    const server = net.createServer();
    server.once('error', () => resolve(false));
    server.once('listening', () => {
      server.close();
      resolve(true);
    });
    server.listen(port, PHP_HOST);
  });
}

// Wait for PHP server to be ready
function waitForServer(port, maxAttempts = 30) {
  return new Promise((resolve, reject) => {
    let attempts = 0;

    const tryConnect = () => {
      attempts++;
      const client = net.createConnection({ port, host: PHP_HOST }, () => {
        client.end();
        resolve(true);
      });

      client.on('error', () => {
        if (attempts < maxAttempts) {
          setTimeout(tryConnect, 200);
        } else {
          reject(new Error('PHP server failed to start'));
        }
      });
    };

    tryConnect();
  });
}

async function startPHPServer() {
  const phpPath = getPHPPath();
  const webappPath = getWebappPath();

  console.log('Starting PHP server...');
  console.log('PHP path:', phpPath);
  console.log('Webapp path:', webappPath);

  // Check if PHP exists
  if (!isDevMode && !fs.existsSync(phpPath)) {
    throw new Error(`PHP not found at: ${phpPath}\nPlease ensure PHP is bundled in the php/ folder.`);
  }

  // Check if port is available
  const portAvailable = await isPortAvailable(PHP_PORT);
  if (!portAvailable) {
    console.log(`Port ${PHP_PORT} already in use, assuming PHP server is running`);
    return;
  }

  // Initialize writable user data directory (for packaged builds)
  let userDataDir = null;
  if (!isDevMode) {
    userDataDir = initUserData();
  }

  // Start PHP built-in server with index.php as router
  // This ensures all requests (including /dashboard, /odds) are
  // routed through index.php instead of returning 404
  const env = { ...process.env };
  if (userDataDir) {
    env.USER_DATA_DIR = userDataDir;
  }

  phpProcess = spawn(phpPath, [
    '-S', `${PHP_HOST}:${PHP_PORT}`,
    '-t', webappPath,
    path.join(webappPath, 'index.php')
  ], {
    cwd: webappPath,
    env: env,
    stdio: ['ignore', 'pipe', 'pipe']
  });

  phpProcess.stdout.on('data', (data) => {
    console.log(`PHP: ${data}`);
  });

  phpProcess.stderr.on('data', (data) => {
    console.log(`PHP: ${data}`);
  });

  phpProcess.on('error', (err) => {
    console.error('Failed to start PHP server:', err);
    dialog.showErrorBox('PHP Error', `Failed to start PHP server: ${err.message}`);
  });

  phpProcess.on('exit', (code) => {
    console.log(`PHP server exited with code ${code}`);
    phpProcess = null;
  });

  // Wait for server to be ready
  await waitForServer(PHP_PORT);
  console.log('PHP server is ready');
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1920,
    height: 1080,
    fullscreen: true,
    autoHideMenuBar: true,
    backgroundColor: '#1a1a2e',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true
    }
  });

  // Load the prize wheel display
  mainWindow.loadURL(`http://${PHP_HOST}:${PHP_PORT}/`);

  // Handle window close
  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  // Open external links in default browser
  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url).catch((err) => {
      console.error('Failed to open external URL:', err);
    });
    return { action: 'deny' };
  });
}

function createMenu() {
  const template = [
    {
      label: 'Navigation',
      submenu: [
        {
          label: 'Prize Wheel',
          accelerator: 'F1',
          click: () => navigateTo('/')
        },
        {
          label: 'Admin Dashboard',
          accelerator: 'F2',
          click: () => navigateTo('/dashboard')
        },
        {
          label: 'Odds Calculator',
          accelerator: 'F3',
          click: () => navigateTo('/odds')
        },
        { type: 'separator' },
        {
          label: 'Refresh',
          accelerator: 'F5',
          click: () => mainWindow?.webContents.reload()
        }
      ]
    },
    {
      label: 'View',
      submenu: [
        {
          label: 'Toggle Fullscreen',
          accelerator: 'F11',
          click: () => {
            if (mainWindow) {
              mainWindow.setFullScreen(!mainWindow.isFullScreen());
            }
          }
        },
        {
          label: 'Toggle Developer Tools',
          accelerator: 'F12',
          click: () => mainWindow?.webContents.toggleDevTools()
        }
      ]
    },
    {
      label: 'Help',
      submenu: [
        {
          label: 'About',
          click: () => {
            dialog.showMessageBox(mainWindow, {
              type: 'info',
              title: 'About Prize Wheel',
              message: 'Prize Wheel Desktop',
              detail: `Version: ${app.getVersion()}\nElectron: ${process.versions.electron}\nNode: ${process.versions.node}`
            });
          }
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

function navigateTo(path) {
  if (mainWindow) {
    mainWindow.loadURL(`http://${PHP_HOST}:${PHP_PORT}${path}`);
  }
}

function registerShortcuts() {
  // F1 - Prize Wheel (main display)
  globalShortcut.register('F1', () => navigateTo('/'));

  // F2 - Admin Dashboard
  globalShortcut.register('F2', () => navigateTo('/dashboard'));

  // F3 - Odds Calculator
  globalShortcut.register('F3', () => navigateTo('/odds'));

  // F11 - Toggle Fullscreen
  globalShortcut.register('F11', () => {
    if (mainWindow) {
      mainWindow.setFullScreen(!mainWindow.isFullScreen());
    }
  });

  // Escape - Exit fullscreen (but don't close app)
  globalShortcut.register('Escape', () => {
    if (mainWindow && mainWindow.isFullScreen()) {
      mainWindow.setFullScreen(false);
    }
  });

  // Ctrl+Shift+D - Quick toggle to dashboard
  globalShortcut.register('CommandOrControl+Shift+D', () => {
    navigateTo('/dashboard');
  });

  // Ctrl+Shift+W - Quick toggle to wheel
  globalShortcut.register('CommandOrControl+Shift+W', () => {
    navigateTo('/');
  });
}

// App lifecycle
app.whenReady().then(async () => {
  try {
    await startPHPServer();
    createMenu();
    createWindow();
    registerShortcuts();

    app.on('activate', () => {
      if (BrowserWindow.getAllWindows().length === 0) {
        createWindow();
      }
    });
  } catch (err) {
    console.error('Failed to start application:', err);
    dialog.showErrorBox('Startup Error', err.message);
    app.quit();
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('will-quit', () => {
  // Unregister all shortcuts
  globalShortcut.unregisterAll();

  // Kill PHP server
  if (phpProcess) {
    console.log('Stopping PHP server...');
    phpProcess.kill();
    phpProcess = null;
  }
});

// Handle uncaught exceptions
process.on('uncaughtException', (err) => {
  console.error('Uncaught exception:', err);
  dialog.showErrorBox('Error', err.message);
});
