# Prize Wheel Desktop Setup Script
# Run this in PowerShell as Administrator

$ErrorActionPreference = "Stop"

Write-Host "Prize Wheel Desktop Setup" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan
Write-Host ""

# Check if running as admin (optional but recommended)
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "Note: Running without admin privileges. Some features may not work." -ForegroundColor Yellow
}

# Get script directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptDir

# Step 1: Check for Node.js
Write-Host "Checking for Node.js..." -ForegroundColor Green
try {
    $nodeVersion = node --version
    Write-Host "  Found Node.js $nodeVersion" -ForegroundColor Gray
} catch {
    Write-Host "  Node.js not found! Please install from https://nodejs.org/" -ForegroundColor Red
    exit 1
}

# Step 2: Check for npm
Write-Host "Checking for npm..." -ForegroundColor Green
try {
    $npmVersion = npm --version
    Write-Host "  Found npm $npmVersion" -ForegroundColor Gray
} catch {
    Write-Host "  npm not found! Please reinstall Node.js" -ForegroundColor Red
    exit 1
}

# Step 3: Download PHP if not present
$phpDir = Join-Path $scriptDir "php"
$phpExe = Join-Path $phpDir "php.exe"

if (Test-Path $phpExe) {
    Write-Host "PHP already installed in php/ folder" -ForegroundColor Green
} else {
    Write-Host "Downloading PHP..." -ForegroundColor Green

    # PHP download URL (VS16 x64 Non Thread Safe)
    $phpVersion = "8.3.14"
    $phpZipUrl = "https://windows.php.net/downloads/releases/php-$phpVersion-nts-Win32-vs16-x64.zip"
    $phpZipFile = Join-Path $env:TEMP "php.zip"

    try {
        Write-Host "  Downloading PHP $phpVersion..." -ForegroundColor Gray
        Invoke-WebRequest -Uri $phpZipUrl -OutFile $phpZipFile -UseBasicParsing

        Write-Host "  Extracting PHP..." -ForegroundColor Gray
        if (-not (Test-Path $phpDir)) {
            New-Item -ItemType Directory -Path $phpDir | Out-Null
        }
        Expand-Archive -Path $phpZipFile -DestinationPath $phpDir -Force

        # Configure php.ini
        Write-Host "  Configuring PHP..." -ForegroundColor Gray
        $phpIniProd = Join-Path $phpDir "php.ini-production"
        $phpIni = Join-Path $phpDir "php.ini"

        if (Test-Path $phpIniProd) {
            Copy-Item $phpIniProd $phpIni

            # Enable required extensions
            $content = Get-Content $phpIni -Raw
            $content = $content -replace ';extension=fileinfo', 'extension=fileinfo'
            $content = $content -replace 'extension_dir = "ext"', 'extension_dir = "ext"'
            Set-Content $phpIni $content
        }

        # Cleanup
        Remove-Item $phpZipFile -ErrorAction SilentlyContinue

        Write-Host "  PHP installed successfully!" -ForegroundColor Green
    } catch {
        Write-Host "  Failed to download PHP: $_" -ForegroundColor Red
        Write-Host "  Please download manually from https://windows.php.net/download/" -ForegroundColor Yellow
        Write-Host "  Extract to: $phpDir" -ForegroundColor Yellow
    }
}

# Step 4: Install npm dependencies
Write-Host "Installing npm dependencies..." -ForegroundColor Green
npm install
if ($LASTEXITCODE -ne 0) {
    Write-Host "  Failed to install dependencies!" -ForegroundColor Red
    exit 1
}
Write-Host "  Dependencies installed!" -ForegroundColor Gray

# Step 5: Summary
Write-Host ""
Write-Host "Setup Complete!" -ForegroundColor Cyan
Write-Host "===============" -ForegroundColor Cyan
Write-Host ""
Write-Host "Available commands:" -ForegroundColor White
Write-Host "  npm start          - Run in development mode" -ForegroundColor Gray
Write-Host "  npm run build      - Build Windows installer" -ForegroundColor Gray
Write-Host "  npm run build:portable - Build portable version" -ForegroundColor Gray
Write-Host ""
Write-Host "Keyboard shortcuts in the app:" -ForegroundColor White
Write-Host "  SPACE    - Spin the wheel" -ForegroundColor Gray
Write-Host "  F1       - Prize Wheel display" -ForegroundColor Gray
Write-Host "  F2       - Admin Dashboard" -ForegroundColor Gray
Write-Host "  F11      - Toggle fullscreen" -ForegroundColor Gray
Write-Host ""
Write-Host "Run 'npm start' to test the application!" -ForegroundColor Green
