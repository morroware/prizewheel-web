// Preload script for Prize Wheel Desktop
// This runs in a sandboxed context with access to some Node.js APIs

const { contextBridge, ipcRenderer } = require('electron');

// Expose minimal API to renderer
contextBridge.exposeInMainWorld('desktopApp', {
  // Check if running in desktop mode
  isDesktop: true,

  // Platform info
  platform: process.platform,

  // Version info
  versions: {
    electron: process.versions.electron,
    node: process.versions.node,
    chrome: process.versions.chrome
  }
});

// Log that we're running in desktop mode
console.log('Prize Wheel Desktop - Preload initialized');
