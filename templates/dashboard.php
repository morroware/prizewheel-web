<?php
/**
 * Prize Wheel Dashboard - Web Only Version
 * No Socket.IO or GPIO dependencies
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prize Wheel Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800;900&family=Montserrat:wght@400;600;700;800;900&family=Roboto:wght@400;500;700;900&family=Open+Sans:wght@400;600;700;800&family=Lato:wght@400;700;900&family=Oswald:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold-1: #ffd700; --gold-2: #ffb300; --royal-purple: #6B46C1;
            --danger: #dc3545; --success: #28a745; --warning: #ffc107; --info: #0dcaf0;
            --dark-bg: #0a0a0f; --card-bg: rgba(42,26,78,0.3); --card-bg-solid: #1a1a2e;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a0033 35%, #000622 100%);
            color: #fff; font-family: 'Montserrat', sans-serif; min-height: 100vh; padding: 20px;
        }
        .dashboard-container { max-width: 1400px; margin: 0 auto; }
        .header { background: var(--card-bg); padding: 20px; border-radius: 20px; margin-bottom: 20px; border: 2px solid var(--gold-2); text-align: center; }
        .header h1 { font-family: 'Cinzel', serif; font-size: 32px; color: var(--gold-1); }
        .header .version-badge { display: inline-block; background: rgba(255,215,0,0.2); padding: 4px 12px; border-radius: 12px; font-size: 11px; margin-left: 10px; color: var(--gold-2); }
        .nav-links { margin-top: 15px; }
        .nav-links a { color: var(--gold-2); text-decoration: none; margin: 0 10px; padding: 8px 16px; border: 1px solid rgba(255,179,0,0.3); border-radius: 5px; transition: all 0.3s ease; }
        .nav-links a:hover { background: rgba(255,179,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: var(--card-bg); padding: 20px; border-radius: 15px; border: 1px solid rgba(255,215,0,0.3); text-align: center; }
        .stat-value { font-size: 32px; font-weight: 800; color: var(--gold-1); margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: rgba(255,255,255,0.7); text-transform: uppercase; }

        .main-content { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 1200px) { .main-content { grid-template-columns: 1fr; } }

        .section { background: var(--card-bg); padding: 25px; border-radius: 15px; border: 1px solid rgba(255,215,0,0.3); }
        .section-title { font-family: 'Cinzel', serif; font-size: 22px; color: var(--gold-1); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid rgba(255,215,0,0.3); }

        .tabs { display: flex; border-bottom: 2px solid rgba(255,215,0,0.3); margin-bottom: 20px; }
        .tab-link { padding: 10px 20px; cursor: pointer; font-weight: 600; color: rgba(255,255,255,0.6); transition: all 0.3s ease; }
        .tab-link.active { color: var(--gold-1); border-bottom: 2px solid var(--gold-1); transform: translateY(2px); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .btn { padding: 10px 18px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin: 5px; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .btn-sm { padding: 6px 12px; font-size: 11px; }
        .btn-primary { background: var(--royal-purple); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: black; }
        .btn-info { background: var(--info); color: black; }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .prizes-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .prizes-table th, .prizes-table td { padding: 12px; text-align: left; vertical-align: middle; }
        .prizes-table th { color: var(--gold-2); }
        .prizes-table tr { background: rgba(0,0,0,0.3); transition: background 0.3s ease; }
        .prizes-table tr:hover { background: rgba(255,215,0,0.1); }
        .prize-color { width: 20px; height: 20px; border-radius: 50%; display: inline-block; margin-right: 10px; border: 1px solid rgba(255,255,255,0.3); vertical-align: middle; }

        .history-list { max-height: 400px; overflow-y: auto; }
        .history-item { background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; margin-bottom: 8px; border: 1px solid rgba(255,255,255,0.1); }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 1000; display: none; align-items: center; justify-content: center; }
        .modal-card { background: var(--card-bg-solid); border: 2px solid var(--gold-1); border-radius: 15px; padding: 30px; width: 90%; max-width: 600px; position: relative; }
        .modal-title { font-family: 'Cinzel', serif; font-size: 24px; color: var(--gold-1); margin-bottom: 20px; }
        .modal-close { position: absolute; top: 15px; right: 20px; background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: 600; font-size: 13px; color: rgba(255,255,255,0.8); }
        .form-group input, .form-group select, .form-group textarea { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); color: white; padding: 10px; border-radius: 5px; font-family: 'Montserrat', sans-serif; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group.toggle-group { flex-direction: row; align-items: center; }
        .form-group.toggle-group label { margin: 0 10px 0 0; }
        .form-group small { font-size: 11px; opacity: 0.7; margin-top: 5px; }

        .timing-section { background: rgba(255,215,0,0.05); border: 1px solid rgba(255,215,0,0.2); border-radius: 10px; padding: 20px; margin: 20px 0; }
        .timing-title { font-family: 'Cinzel', serif; color: var(--gold-1); font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }

        .notification { position: fixed; top: 20px; right: 20px; z-index: 2000; padding: 15px 20px; border-radius: 8px; font-weight: 600; animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

        /* Customization Styles */
        .preset-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .preset-card { background: rgba(0,0,0,0.3); border: 2px solid rgba(255,215,0,0.2); border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s ease; text-align: center; }
        .preset-card:hover { border-color: var(--gold-1); transform: translateY(-2px); }
        .preset-card.active { border-color: var(--gold-1); background: rgba(255,215,0,0.1); }
        .preset-colors { display: flex; gap: 5px; justify-content: center; margin-bottom: 10px; }
        .preset-color { width: 24px; height: 24px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); }
        .preset-name { font-weight: 600; font-size: 14px; margin-bottom: 5px; }
        .preset-desc { font-size: 11px; opacity: 0.7; }

        .customization-section { background: rgba(255,215,0,0.05); border: 1px solid rgba(255,215,0,0.2); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .customization-section-title { font-family: 'Cinzel', serif; color: var(--gold-1); font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .customization-section-title::before { content: ''; display: inline-block; width: 8px; height: 8px; background: var(--gold-1); border-radius: 50%; }
        .customization-section.collapsed .customization-section-content { display: none; }
        .customization-section-title::after { content: '\\25BC'; margin-left: auto; font-size: 10px; transition: transform 0.3s; }
        .customization-section.collapsed .customization-section-title::after { transform: rotate(-90deg); }

        .color-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
        .color-input-group { display: flex; align-items: center; gap: 10px; }
        .color-input-group label { flex: 1; font-size: 12px; }
        .color-input-group input[type="color"] { width: 40px; height: 30px; padding: 0; border: none; border-radius: 5px; cursor: pointer; }
        .color-input-group input[type="text"] { width: 80px; padding: 5px 8px; font-size: 11px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 4px; color: white; font-family: monospace; }

        .toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-label { font-size: 13px; }
        .toggle-switch { position: relative; width: 50px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background: rgba(0,0,0,0.4); border-radius: 26px; transition: 0.3s; }
        .toggle-slider::before { content: ''; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--gold-1); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(24px); }

        .slider-row { padding: 10px 0; }
        .slider-row label { display: block; font-size: 13px; margin-bottom: 8px; }
        .slider-row input[type="range"] { width: 100%; }
        .slider-value { font-size: 12px; color: var(--gold-1); float: right; }

        .branding-preview { background: rgba(0,0,0,0.3); border-radius: 10px; padding: 20px; text-align: center; margin-top: 15px; }
        .branding-preview img { max-height: 60px; margin-bottom: 10px; }

        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .action-buttons .btn { flex: 1; min-width: 120px; }

        .text-input-row { margin-bottom: 15px; }
        .text-input-row label { display: block; font-size: 13px; margin-bottom: 5px; color: rgba(255,255,255,0.8); }
        .text-input-row input, .text-input-row textarea { width: 100%; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white; font-family: 'Montserrat', sans-serif; }
        .text-input-row textarea { min-height: 100px; resize: vertical; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="header">
            <h1>Prize Wheel Dashboard <span class="version-badge">Web Only</span></h1>
            <div class="nav-links">
                <a href="<?php echo BASE_PATH; ?>/">Display Page</a>
                <a href="<?php echo BASE_PATH; ?>/dashboard">Dashboard</a>
                <a href="<?php echo BASE_PATH; ?>/odds">Odds Calculator</a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-value" id="statTotalSpins">-</div><div class="stat-label">Total Spins</div></div>
            <div class="stat-card"><div class="stat-value" id="statWinRate">-</div><div class="stat-label">Win Rate</div></div>
            <div class="stat-card"><div class="stat-value" id="statActivePrizes">-</div><div class="stat-label">Active Prizes</div></div>
            <div class="stat-card"><div class="stat-value" id="statMode">Web</div><div class="stat-label">Mode</div></div>
        </div>

        <div class="main-content">
            <div class="section">
                <div class="tabs">
                    <div class="tab-link active" onclick="openTab(event, 'prizes')">Prizes</div>
                    <div class="tab-link" onclick="openTab(event, 'customization')">Customization</div>
                    <div class="tab-link" onclick="openTab(event, 'settings')">Settings</div>
                    <div class="tab-link" onclick="openTab(event, 'sounds')">Sounds</div>
                    <div class="tab-link" onclick="openTab(event, 'system')">System</div>
                </div>

                <div id="prizes" class="tab-content active">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 class="section-title" style="margin: 0; border: none; padding: 0;">Prize Management</h2>
                        <button class="btn btn-success" onclick="openPrizeModal()">+ Create New Prize</button>
                    </div>
                    <div id="prizesContainer" style="max-height: 500px; overflow-y: auto;"></div>
                </div>

                <div id="customization" class="tab-content">
                    <h2 class="section-title">Theme & Customization</h2>
                    <div id="customizationContainer"></div>
                </div>

                <div id="settings" class="tab-content">
                    <h2 class="section-title">Configuration Settings</h2>
                    <form id="configForm" onsubmit="saveConfig(event)"></form>
                </div>

                <div id="sounds" class="tab-content">
                    <h2 class="section-title">Sound Management</h2>
                    <div id="soundsContainer"></div>
                </div>

                <div id="system" class="tab-content">
                    <h2 class="section-title">System Actions</h2>
                    <div id="systemContainer"></div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Recent Activity</h2>
                <div class="history-list" id="historyList"></div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="prizeModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closePrizeModal()">&times;</button>
            <h3 class="modal-title" id="prizeModalTitle">Edit Prize</h3>
            <form id="prizeForm" onsubmit="savePrize(event)">
                <input type="hidden" id="prizeId">
                <div class="form-group">
                    <label for="prizeName">Prize Name</label>
                    <input type="text" id="prizeName" required>
                </div>
                <div class="form-group">
                    <label for="prizeDescription">Description</label>
                    <textarea id="prizeDescription"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="prizeWeight">Weight</label>
                        <input type="number" id="prizeWeight" required min="0.1" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="prizeColor">Color</label>
                        <input type="color" id="prizeColor" value="#FFD700" style="padding: 5px; height: 40px;">
                    </div>
                </div>
                <div class="form-group">
                    <label for="prizeSound">Sound Effect</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <select id="prizeSound" style="flex: 1;"></select>
                        <button type="button" class="btn btn-sm btn-info" onclick="previewPrizeSound()" title="Preview Sound">&#9658;</button>
                    </div>
                    <small style="display: block; margin-top: 5px;">Leave empty to use default winner/loser sound based on prize type.</small>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Or Upload New Sound for This Prize</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="file" id="prizeSoundUpload" accept=".mp3,.wav,.ogg,.m4a" style="flex: 1;">
                        <button type="button" class="btn btn-sm btn-success" onclick="uploadPrizeSound()">Upload</button>
                    </div>
                </div>
                <div class="form-grid" style="margin: 20px 0;">
                    <div class="form-group toggle-group">
                        <label for="prizeIsWinner">Is a winning prize?</label>
                        <input type="checkbox" id="prizeIsWinner">
                    </div>
                    <div class="form-group toggle-group">
                        <label for="prizeEnabled">Is prize enabled?</label>
                        <input type="checkbox" id="prizeEnabled">
                    </div>
                </div>
                <div style="text-align: right;">
                    <button type="submit" class="btn btn-success">Save Prize</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Base path for subdirectory support
    const BASE_PATH = '<?php echo BASE_PATH; ?>';

    // Helper to normalize paths (prepend BASE_PATH if path starts with /)
    function normalizePath(path) {
      if (!path) return path;
      if (path.startsWith('/') && !path.startsWith(BASE_PATH)) {
        return BASE_PATH + path;
      }
      return path;
    }

    // Utility Functions
    const escapeHtml = (str) => !str ? '' : str.replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
    const showNotification = (message, type = 'success') => {
        const notif = document.createElement('div');
        notif.className = 'notification';
        notif.style.background = type === 'success' ? 'var(--success)' : 'var(--danger)';
        notif.style.color = 'white';
        notif.textContent = message;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 4000);
    };

    // Tab Handling
    const openTab = (evt, tabName) => {
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        document.querySelectorAll('.tab-link').forEach(tl => tl.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    };

    // Data Fetch & Render
    async function loadDashboardData() {
        try {
            const response = await fetch(BASE_PATH + '/api/dashboard_data');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();

            updateStats(data.stats);
            renderPrizes(data.prizes);
            renderHistory(data.recent_spins);
            renderConfig(data.config);
            renderSounds(data.prizes);
            renderSystem();

        } catch (error) {
            showNotification('Failed to load dashboard data: ' + error.message, 'error');
        }
    }

    const updateStats = (stats) => {
        document.getElementById('statTotalSpins').textContent = stats.total_spins || 0;
        document.getElementById('statWinRate').textContent = `${stats.win_rate || 0}%`;
        document.getElementById('statActivePrizes').textContent = stats.active_prizes || 0;
    };

    const renderPrizes = (prizes) => {
        const container = document.getElementById('prizesContainer');
        if (!prizes || prizes.length === 0) {
            container.innerHTML = '<p>No prizes configured.</p>';
            return;
        }
        container.innerHTML = `<table class="prizes-table">
            <thead><tr><th>Prize</th><th>Weight</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>${prizes.map(p => `
                <tr data-prize-id="${p.id}">
                    <td><span class="prize-color" style="background-color:${p.color}"></span><strong>${escapeHtml(p.name)}</strong></td>
                    <td>${p.weight}</td>
                    <td>${p.is_winner ? 'Winner' : 'Try Again'}</td>
                    <td>${p.enabled ? 'Enabled' : 'Disabled'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="openPrizeModal('${p.id}')">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePrize('${p.id}', '${escapeHtml(p.name)}')">Delete</button>
                    </td>
                </tr>`).join('')}
            </tbody></table>`;
    };

    const renderHistory = (history) => {
        const container = document.getElementById('historyList');
        if (!history || history.length === 0) {
            container.innerHTML = '<p style="text-align:center;">No activity yet.</p>';
            return;
        }
        container.innerHTML = history.map(item => `<div class="history-item">
            <strong>${escapeHtml(item.prize_name)}</strong>
            <div style="font-size:12px;opacity:0.7;">${new Date(item.timestamp).toLocaleString()}</div>
            </div>`).join('');
    };

    const renderConfig = (config) => {
        // Note: No GPIO button_pin setting in web-only version
        document.getElementById('configForm').innerHTML = `
            <div class="form-grid">
                <div class="form-group">
                    <label for="spinDuration">Spin Duration (seconds)</label>
                    <input type="number" id="spinDuration" value="${config.spin_duration_seconds || 8}" step="1" min="1">
                </div>
                <div class="form-group">
                    <label for="cooldown">Cooldown (seconds)</label>
                    <input type="number" id="cooldown" value="${config.cooldown_seconds || 3}" step="1" min="0">
                </div>
                <div class="form-group">
                    <label for="volume">Master Volume (${config.volume || 75}%)</label>
                    <input type="range" id="volume" value="${config.volume || 75}" min="0" max="100" oninput="this.previousElementSibling.textContent = 'Master Volume ('+this.value+'%)'">
                </div>
            </div>

            <div class="timing-section">
                <h4 class="timing-title">Modal Timing Settings</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="modalDelay">Modal Delay (milliseconds)</label>
                        <input type="number" id="modalDelay" value="${config.modal_delay_ms || 3000}" step="100" min="500" max="10000">
                        <small>Time before winner modal appears</small>
                    </div>
                    <div class="form-group">
                        <label for="modalAutoClose">Modal Auto-Close (milliseconds)</label>
                        <input type="number" id="modalAutoClose" value="${config.modal_auto_close_ms || 10000}" step="500" min="2000" max="30000">
                        <small>How long modal stays open</small>
                    </div>
                    <div class="form-group">
                        <label for="winnerFlash">Winner Flash Duration (milliseconds)</label>
                        <input type="number" id="winnerFlash" value="${config.winner_flash_duration_ms || 4000}" step="100" min="1000" max="10000">
                        <small>How long the winning segment flashes</small>
                    </div>
                </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
                <button type="submit" class="btn btn-success">Save Settings</button>
            </div>`;
    };

    const renderSounds = async (prizes) => {
        const soundResponse = await fetch(BASE_PATH + '/api/sounds/list');
        const soundData = await soundResponse.json();
        const sounds = soundData.sounds || [];

        // Fetch current customization for sound settings
        let customization = {};
        try {
            const custResponse = await fetch(BASE_PATH + '/api/customization');
            const custData = await custResponse.json();
            customization = custData.customization || {};
        } catch (e) {}
        const soundSettings = customization.sounds || {};
        const systemSounds = soundSettings.system || {};

        const prizeSoundSelect = document.getElementById('prizeSound');
        if (prizeSoundSelect) {
            prizeSoundSelect.innerHTML = '<option value="">- Use Default (based on win/lose) -</option>' + sounds.map(s => `<option value="${s}">${s.split('/').pop()}</option>`).join('');
        }

        // Helper to create sound selector HTML
        const createSoundSelector = (id, label, currentPath, volume, showVolumeSlider = true) => {
            const currentFile = currentPath ? currentPath.split('/').pop() : 'Not set';
            return `
                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label style="font-weight: 600; color: var(--gold-2);">${label}</label>
                        <button class="btn btn-sm btn-info" onclick="previewSound('${id}')" title="Preview">&#9658; Play</button>
                    </div>
                    <select id="${id}Sound" style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white; margin-bottom: 8px;" onchange="updateSystemSound('${id}', this.value)">
                        <option value="">-- Select Sound --</option>
                        ${sounds.map(s => `<option value="${s}" ${currentPath === s ? 'selected' : ''}>${s.split('/').pop()}</option>`).join('')}
                    </select>
                    ${showVolumeSlider ? `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 12px; opacity: 0.7;">Volume:</span>
                        <input type="range" id="${id}Volume" min="0" max="100" value="${Math.round((volume || 1) * 100)}" style="flex: 1;" onchange="updateSystemSoundVolume('${id}', this.value)">
                        <span id="${id}VolumeDisplay" style="font-size: 12px; width: 35px;">${Math.round((volume || 1) * 100)}%</span>
                    </div>
                    ` : ''}
                </div>
            `;
        };

        const container = document.getElementById('soundsContainer');
        container.innerHTML = `
            <!-- Global Sound Settings -->
            <div style="margin-bottom: 25px; padding: 20px; background: rgba(255,215,0,0.08); border-radius: 12px; border: 1px solid rgba(255,215,0,0.2);">
                <h4 style="margin: 0 0 15px 0; color: #FFD700; font-family: 'Cinzel', serif;">&#127925; Global Sound Settings</h4>
                <div class="form-group toggle-group" style="margin-bottom: 15px;">
                    <label for="soundsEnabled" style="font-weight: 600;">Enable All Sounds</label>
                    <input type="checkbox" id="soundsEnabled" ${soundSettings.enabled !== false ? 'checked' : ''} onchange="updateSoundSettings()">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="masterVolume">Master Volume: <span id="volumeDisplay" style="color: var(--gold-1);">${soundSettings.master_volume || 75}</span>%</label>
                    <input type="range" id="masterVolume" min="0" max="100" value="${soundSettings.master_volume || 75}" style="width: 100%;" oninput="document.getElementById('volumeDisplay').textContent = this.value" onchange="updateSoundSettings()">
                </div>
            </div>

            <!-- System Sounds Configuration -->
            <div style="margin-bottom: 25px; padding: 20px; background: rgba(107,70,193,0.1); border-radius: 12px; border: 1px solid rgba(107,70,193,0.3);">
                <h4 style="margin: 0 0 15px 0; color: #FFD700; font-family: 'Cinzel', serif;">&#127926; System Sounds</h4>
                <p style="opacity: 0.6; font-size: 12px; margin-bottom: 15px;">Customize the sounds that play during wheel events. Upload your own MP3 files below!</p>

                ${createSoundSelector('spin', '&#128296; Wheel Spin Start Sound', systemSounds.spin?.path || '/static/sounds/spin.wav', systemSounds.spin?.volume || 1)}
                ${createSoundSelector('winner', '&#127942; Winner Sound', systemSounds.winner?.path || '/static/sounds/victory.wav', systemSounds.winner?.volume || 1)}
                ${createSoundSelector('loser', '&#128549; Try Again Sound', systemSounds.loser?.path || '/static/sounds/try-again.wav', systemSounds.loser?.volume || 1)}

                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <label style="font-weight: 600; color: var(--gold-2);">&#128264; Wheel Tick Sound</label>
                            <p style="opacity: 0.5; font-size: 11px; margin: 3px 0 0 0;">Plays as each segment passes the pointer</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label style="font-size: 12px;">Enabled:</label>
                            <input type="checkbox" id="tickEnabled" ${(systemSounds.tick && systemSounds.tick.enabled !== false) ? 'checked' : ''} onchange="updateSoundSettings()">
                            <button class="btn btn-sm btn-info" onclick="previewSound('tick')" title="Preview">&#9658;</button>
                        </div>
                    </div>
                    <select id="tickSound" style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white; margin-bottom: 8px;" onchange="updateSystemSound('tick', this.value)">
                        <option value="">-- Select Sound --</option>
                        ${sounds.map(s => `<option value="${s}" ${(systemSounds.tick?.path || '/static/sounds/tick.wav') === s ? 'selected' : ''}>${s.split('/').pop()}</option>`).join('')}
                    </select>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 12px; opacity: 0.7;">Volume:</span>
                        <input type="range" id="tickVolume" min="0" max="100" value="${Math.round((systemSounds.tick?.volume || 0.5) * 100)}" style="flex: 1;" oninput="document.getElementById('tickVolumeDisplay').textContent = this.value + '%'" onchange="updateSystemSoundVolume('tick', this.value)">
                        <span id="tickVolumeDisplay" style="font-size: 12px; width: 35px;">${Math.round((systemSounds.tick?.volume || 0.5) * 100)}%</span>
                    </div>
                </div>
            </div>

            <!-- Modal Popup Sounds -->
            <div style="margin-bottom: 25px; padding: 20px; background: rgba(255,193,7,0.1); border-radius: 12px; border: 1px solid rgba(255,193,7,0.3);">
                <h4 style="margin: 0 0 15px 0; color: #ffc107; font-family: 'Cinzel', serif;">&#127881; Modal Popup Sounds</h4>
                <p style="opacity: 0.6; font-size: 12px; margin-bottom: 15px;">Optional sounds that play when the winner/loser dialogue appears (separate from the landing sounds above).</p>

                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <label style="font-weight: 600; color: var(--gold-2);">&#127942; Winner Modal Sound</label>
                            <p style="opacity: 0.5; font-size: 11px; margin: 3px 0 0 0;">Plays when the winner popup appears</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label style="font-size: 12px;">Enabled:</label>
                            <input type="checkbox" id="modalWinnerSoundEnabled" ${systemSounds.modal_winner?.enabled ? 'checked' : ''} onchange="updateSoundSettings()">
                            <button class="btn btn-sm btn-info" onclick="previewSound('modal_winner')" title="Preview">&#9658;</button>
                        </div>
                    </div>
                    <select id="modal_winnerSound" style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white; margin-bottom: 8px;" onchange="updateSystemSound('modal_winner', this.value)">
                        <option value="">-- None (Disabled) --</option>
                        ${sounds.map(s => `<option value="${s}" ${systemSounds.modal_winner?.path === s ? 'selected' : ''}>${s.split('/').pop()}</option>`).join('')}
                    </select>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 12px; opacity: 0.7;">Volume:</span>
                        <input type="range" id="modal_winnerVolume" min="0" max="100" value="${Math.round((systemSounds.modal_winner?.volume || 1) * 100)}" style="flex: 1;" oninput="document.getElementById('modal_winnerVolumeDisplay').textContent = this.value + '%'" onchange="updateSystemSoundVolume('modal_winner', this.value)">
                        <span id="modal_winnerVolumeDisplay" style="font-size: 12px; width: 35px;">${Math.round((systemSounds.modal_winner?.volume || 1) * 100)}%</span>
                    </div>
                </div>

                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <label style="font-weight: 600; color: var(--gold-2);">&#128549; Loser Modal Sound</label>
                            <p style="opacity: 0.5; font-size: 11px; margin: 3px 0 0 0;">Plays when the try again popup appears</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label style="font-size: 12px;">Enabled:</label>
                            <input type="checkbox" id="modalLoserSoundEnabled" ${systemSounds.modal_loser?.enabled ? 'checked' : ''} onchange="updateSoundSettings()">
                            <button class="btn btn-sm btn-info" onclick="previewSound('modal_loser')" title="Preview">&#9658;</button>
                        </div>
                    </div>
                    <select id="modal_loserSound" style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white; margin-bottom: 8px;" onchange="updateSystemSound('modal_loser', this.value)">
                        <option value="">-- None (Disabled) --</option>
                        ${sounds.map(s => `<option value="${s}" ${systemSounds.modal_loser?.path === s ? 'selected' : ''}>${s.split('/').pop()}</option>`).join('')}
                    </select>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 12px; opacity: 0.7;">Volume:</span>
                        <input type="range" id="modal_loserVolume" min="0" max="100" value="${Math.round((systemSounds.modal_loser?.volume || 1) * 100)}" style="flex: 1;" oninput="document.getElementById('modal_loserVolumeDisplay').textContent = this.value + '%'" onchange="updateSystemSoundVolume('modal_loser', this.value)">
                        <span id="modal_loserVolumeDisplay" style="font-size: 12px; width: 35px;">${Math.round((systemSounds.modal_loser?.volume || 1) * 100)}%</span>
                    </div>
                </div>
            </div>

            <!-- Upload New Sound -->
            <div style="margin-bottom: 25px; padding: 20px; background: rgba(40,167,69,0.1); border-radius: 12px; border: 1px solid rgba(40,167,69,0.3);">
                <h4 style="margin: 0 0 15px 0; color: #28a745; font-family: 'Cinzel', serif;">&#128228; Upload Custom Sound</h4>
                <p style="opacity: 0.6; font-size: 12px; margin-bottom: 15px;">Upload MP3, WAV, OGG, or M4A files (max 16MB). After uploading, select it from the dropdowns above.</p>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="file" id="soundUpload" accept=".mp3,.wav,.ogg,.m4a" style="flex: 1; min-width: 200px;">
                    <button class="btn btn-success" onclick="uploadSound()">&#128228; Upload Sound</button>
                </div>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <button class="btn btn-sm btn-warning" onclick="testSoundPlayback()">&#128270; Test Sound System</button>
                    <span id="soundTestResult" style="margin-left: 10px; font-size: 12px;"></span>
                </div>
            </div>

            <!-- Available Sounds Library -->
            <div style="padding: 20px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                <h4 style="margin: 0 0 15px 0; color: #FFD700; font-family: 'Cinzel', serif;">&#127928; Sound Library</h4>
                <div id="soundLibraryList" style="max-height: 300px; overflow-y: auto;">
                    ${sounds.length > 0 ? sounds.map((s, idx) => {
                        const filename = s.split('/').pop();
                        const isDefault = ['spin.wav', 'victory.wav', 'try-again.wav', 'tick.wav'].includes(filename);
                        const escapedPath = escapeHtml(s);
                        return `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: rgba(0,0,0,0.2); margin-bottom: 6px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button class="btn btn-sm btn-info" data-sound-path="${escapedPath}" onclick="playSoundFromButton(this)" title="Play">&#9658;</button>
                                <span style="font-size: 13px;">${escapeHtml(filename)}</span>
                                ${isDefault ? '<span style="font-size: 10px; background: rgba(255,215,0,0.2); padding: 2px 6px; border-radius: 3px; color: var(--gold-2);">DEFAULT</span>' : ''}
                            </div>
                            ${!isDefault ? `<button class="btn btn-sm btn-danger" data-sound-path="${escapedPath}" onclick="deleteSoundFromButton(this)" title="Delete">&#128465;</button>` : ''}
                        </div>
                    `}).join('') : '<p style="opacity: 0.5; text-align: center;">No sounds found. Upload some MP3 files to get started!</p>'}
                </div>
            </div>
        `;

        // Update volume display listeners
        ['spin', 'winner', 'loser', 'tick', 'modal_winner', 'modal_loser'].forEach(id => {
            const slider = document.getElementById(id + 'Volume');
            if (slider) {
                slider.oninput = function() {
                    document.getElementById(id + 'VolumeDisplay').textContent = this.value + '%';
                };
            }
        });
    };

    // Test sound playback system
    async function testSoundPlayback() {
        const resultEl = document.getElementById('soundTestResult');
        resultEl.innerHTML = '<span style="color: #ffc107;">Testing...</span>';

        const testPath = '/static/sounds/tick.wav';
        const fullPath = BASE_PATH + testPath;

        console.log('=== Sound System Test ===');
        console.log('BASE_PATH:', BASE_PATH);
        console.log('Test path:', fullPath);

        // Step 1: Check if file exists via fetch
        try {
            const response = await fetch(fullPath, { method: 'HEAD' });
            console.log('Fetch response:', response.status, response.headers.get('content-type'));

            if (!response.ok) {
                resultEl.innerHTML = `<span style="color: #dc3545;">❌ File not found (HTTP ${response.status}). Path: ${fullPath}</span>`;
                return;
            }
        } catch (e) {
            resultEl.innerHTML = `<span style="color: #dc3545;">❌ Network error: ${e.message}</span>`;
            console.error('Fetch error:', e);
            return;
        }

        // Step 2: Try to load audio
        const audio = new Audio();

        const loadPromise = new Promise((resolve, reject) => {
            audio.oncanplaythrough = () => resolve('loaded');
            audio.onerror = () => reject(new Error('Audio load error: ' + (audio.error?.message || 'Unknown')));
            setTimeout(() => reject(new Error('Timeout loading audio')), 5000);
        });

        audio.src = fullPath;
        audio.load();

        try {
            await loadPromise;
            console.log('Audio loaded successfully');
        } catch (e) {
            resultEl.innerHTML = `<span style="color: #dc3545;">❌ Audio load failed: ${e.message}</span>`;
            console.error('Audio load error:', e, audio.error);
            return;
        }

        // Step 3: Try to play
        try {
            audio.volume = 0.5;
            await audio.play();
            resultEl.innerHTML = '<span style="color: #28a745;">✅ Sound system working! You should hear a tick.</span>';
            console.log('Audio played successfully');
        } catch (e) {
            resultEl.innerHTML = `<span style="color: #dc3545;">❌ Play failed: ${e.name} - ${e.message}</span>`;
            console.error('Audio play error:', e);
        }
    }

    // Helper to play sound from button data attribute
    function playSoundFromButton(button) {
        const path = button.getAttribute('data-sound-path');
        if (path) {
            playSound(path);
        }
    }

    // Helper to delete sound from button data attribute
    function deleteSoundFromButton(button) {
        const path = button.getAttribute('data-sound-path');
        if (path) {
            deleteSound(path);
        }
    }

    // Play a sound for preview
    function playSound(path) {
        if (!path) {
            console.log('No sound path provided');
            return;
        }

        // Normalize path - the API already includes BASE_PATH, but handle edge cases
        let soundPath = path;
        if (path.startsWith('/') && BASE_PATH && !path.startsWith(BASE_PATH)) {
            soundPath = BASE_PATH + path;
        }

        console.log('Playing sound:', soundPath);

        const audio = new Audio();
        audio.preload = 'auto';

        audio.oncanplaythrough = function() {
            audio.volume = (parseInt(document.getElementById('masterVolume')?.value || 75)) / 100;
            audio.play().then(() => {
                console.log('Sound playing successfully');
            }).catch(e => {
                console.error('Audio play failed:', e.name, e.message);
                showNotification('Failed to play sound: ' + e.message, 'error');
            });
        };

        audio.onerror = function(e) {
            console.error('Audio load error for:', soundPath, audio.error);
            showNotification('Failed to load sound file. Check if file exists.', 'error');
        };

        audio.src = soundPath;
        audio.load();
    }

    // Preview system sound
    function previewSound(type) {
        const select = document.getElementById(type + 'Sound');
        let soundPath;

        if (select && select.value) {
            soundPath = select.value;
        } else {
            // Use default paths
            const defaults = {
                spin: '/static/sounds/spin.wav',
                winner: '/static/sounds/victory.wav',
                loser: '/static/sounds/try-again.wav',
                tick: '/static/sounds/tick.wav'
            };
            soundPath = defaults[type];
        }

        if (soundPath) {
            // Ensure proper path with BASE_PATH
            if (soundPath.startsWith('/') && !soundPath.startsWith(BASE_PATH + '/') && BASE_PATH) {
                soundPath = BASE_PATH + soundPath;
            } else if (soundPath.startsWith('/') && !BASE_PATH) {
                // BASE_PATH is empty, path is fine
            }
            playSound(soundPath);
        }
    }

    // Update a system sound path
    async function updateSystemSound(type, path) {
        try {
            const custResponse = await fetch(BASE_PATH + '/api/customization');
            const custData = await custResponse.json();
            const customization = custData.customization || {};

            if (!customization.sounds) customization.sounds = {};
            if (!customization.sounds.system) customization.sounds.system = {};
            if (!customization.sounds.system[type]) customization.sounds.system[type] = {};

            customization.sounds.system[type].path = path;

            const response = await fetch(BASE_PATH + '/api/customization', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(customization)
            });

            if (response.ok) {
                showNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} sound updated!`);
            } else {
                showNotification('Failed to update sound.', 'error');
            }
        } catch (e) {
            showNotification('Error updating sound: ' + e.message, 'error');
        }
    }

    // Update a system sound volume
    async function updateSystemSoundVolume(type, volumePercent) {
        try {
            const custResponse = await fetch(BASE_PATH + '/api/customization');
            const custData = await custResponse.json();
            const customization = custData.customization || {};

            if (!customization.sounds) customization.sounds = {};
            if (!customization.sounds.system) customization.sounds.system = {};
            if (!customization.sounds.system[type]) customization.sounds.system[type] = {};

            customization.sounds.system[type].volume = parseInt(volumePercent) / 100;

            const response = await fetch(BASE_PATH + '/api/customization', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(customization)
            });

            if (!response.ok) {
                showNotification('Failed to update volume.', 'error');
            }
        } catch (e) {
            showNotification('Error updating volume: ' + e.message, 'error');
        }
    }

    // Delete a sound file
    async function deleteSound(path) {
        const filename = path.split('/').pop();
        if (!confirm(`Are you sure you want to delete "${filename}"?`)) return;

        try {
            const response = await fetch(BASE_PATH + '/api/sounds/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: path })
            });

            if (response.ok) {
                showNotification('Sound deleted successfully!');
                loadDashboardData();
            } else {
                const error = await response.json();
                showNotification('Failed to delete: ' + (error.error || 'Unknown error'), 'error');
            }
        } catch (e) {
            showNotification('Error deleting sound: ' + e.message, 'error');
        }
    }

    const renderSystem = () => {
        document.getElementById('systemContainer').innerHTML = `
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Export Data</label>
                <p style="opacity: 0.7; font-size: 14px; margin-bottom: 10px;">Download spin history as CSV file.</p>
                <a href="${BASE_PATH}/api/export/csv" class="btn btn-info" download>Download CSV</a>
            </div>
            <div class="form-group">
                <label>Reset Wheel State</label>
                <p style="opacity: 0.7; font-size: 14px; margin-bottom: 10px;">If the wheel is stuck in "spinning" or "cooldown" state, use this to force reset it.</p>
                <button class="btn btn-warning" onclick="resetWheelState()">Force Reset Wheel</button>
            </div>
            <div class="form-group">
                <label>Clear Spin History</label>
                <p style="opacity: 0.7; font-size: 14px; margin-bottom: 10px;">This will permanently delete all spin records. This action cannot be undone.</p>
                <button class="btn btn-danger" onclick="clearHistory()">Clear History</button>
            </div>
            <div class="form-group" style="margin-top: 30px; padding: 15px; background: rgba(255,215,0,0.1); border-radius: 8px;">
                <label>Version Info</label>
                <p style="opacity: 0.8; font-size: 14px;">Web Only Version - No Raspberry Pi/GPIO dependencies</p>
                <p style="opacity: 0.6; font-size: 12px;">Uses polling-based updates instead of WebSockets</p>
            </div>`;
    };

    // Modal Handling
    let allPrizes = [];
    async function openPrizeModal(prizeId = null) {
        const modal = document.getElementById('prizeModal');
        const form = document.getElementById('prizeForm');
        form.reset();

        const prizeResponse = await fetch(BASE_PATH + '/api/prizes');
        const prizeData = await prizeResponse.json();
        allPrizes = prizeData.prizes;

        // Refresh sounds list
        await renderSounds(allPrizes);

        if (prizeId) {
            const prize = allPrizes.find(p => p.id === prizeId);
            if (!prize) return showNotification('Prize not found!', 'error');
            document.getElementById('prizeModalTitle').textContent = 'Edit Prize';
            document.getElementById('prizeId').value = prize.id;
            document.getElementById('prizeName').value = prize.name;
            document.getElementById('prizeDescription').value = prize.description;
            document.getElementById('prizeWeight').value = prize.weight;
            document.getElementById('prizeColor').value = prize.color;
            document.getElementById('prizeSound').value = prize.sound_path || '';
            document.getElementById('prizeIsWinner').checked = prize.is_winner;
            document.getElementById('prizeEnabled').checked = prize.enabled;
        } else {
            document.getElementById('prizeModalTitle').textContent = 'Create New Prize';
            document.getElementById('prizeId').value = '';
            document.getElementById('prizeEnabled').checked = true;
            document.getElementById('prizeIsWinner').checked = true;
        }
        modal.style.display = 'flex';
    }
    const closePrizeModal = () => document.getElementById('prizeModal').style.display = 'none';

    // Preview prize sound
    function previewPrizeSound() {
        const select = document.getElementById('prizeSound');
        let soundPath;

        if (select && select.value) {
            soundPath = select.value;
        } else {
            // Play default based on winner/loser state
            const isWinner = document.getElementById('prizeIsWinner').checked;
            soundPath = isWinner ? '/static/sounds/victory.wav' : '/static/sounds/try-again.wav';
        }

        // Ensure proper path with BASE_PATH
        if (soundPath && soundPath.startsWith('/') && BASE_PATH && !soundPath.startsWith(BASE_PATH + '/')) {
            soundPath = BASE_PATH + soundPath;
        }

        playSound(soundPath);
    }

    // Upload sound for prize and select it
    async function uploadPrizeSound() {
        const fileInput = document.getElementById('prizeSoundUpload');
        if (fileInput.files.length === 0) {
            showNotification('Please select a sound file to upload.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        try {
            const response = await fetch(BASE_PATH + '/api/upload/sound', { method: 'POST', body: formData });
            const data = await response.json();

            if (response.ok && data.success) {
                showNotification('Sound uploaded successfully!');
                fileInput.value = '';

                // Refresh the sound list and select the new sound
                const soundResponse = await fetch(BASE_PATH + '/api/sounds/list');
                const soundData = await soundResponse.json();
                const sounds = soundData.sounds || [];

                const prizeSoundSelect = document.getElementById('prizeSound');
                prizeSoundSelect.innerHTML = '<option value="">- Use Default (based on win/lose) -</option>' + sounds.map(s => `<option value="${s}">${s.split('/').pop()}</option>`).join('');

                // Select the newly uploaded sound
                prizeSoundSelect.value = data.path;
            } else {
                showNotification('Upload failed: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (e) {
            showNotification('Error uploading sound: ' + e.message, 'error');
        }
    }

    // API Actions
    async function savePrize(event) {
        event.preventDefault();
        const prizeId = document.getElementById('prizeId').value;
        const prize = {
            name: document.getElementById('prizeName').value,
            description: document.getElementById('prizeDescription').value,
            weight: parseFloat(document.getElementById('prizeWeight').value) || 1,
            color: document.getElementById('prizeColor').value,
            sound_path: document.getElementById('prizeSound').value,
            is_winner: document.getElementById('prizeIsWinner').checked,
            enabled: document.getElementById('prizeEnabled').checked,
        };

        const url = prizeId ? `${BASE_PATH}/api/prizes/${prizeId}` : `${BASE_PATH}/api/prizes`;
        const method = prizeId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(prize)
        });

        if (response.ok) {
            showNotification(`Prize ${prizeId ? 'updated' : 'created'} successfully!`);
            closePrizeModal();
            loadDashboardData();
        } else {
            const error = await response.json();
            showNotification(`Error: ${error.error}`, 'error');
        }
    }

    async function deletePrize(prizeId, prizeName) {
        if (!confirm(`Are you sure you want to delete "${prizeName}"?`)) return;

        const response = await fetch(`${BASE_PATH}/api/prizes/${prizeId}`, { method: 'DELETE' });
        if (response.ok) {
            showNotification('Prize deleted successfully!');
            loadDashboardData();
        } else {
            showNotification('Failed to delete prize.', 'error');
        }
    }

    async function saveConfig(event) {
        event.preventDefault();
        const config = {
            spin_duration_seconds: parseInt(document.getElementById('spinDuration').value),
            cooldown_seconds: parseInt(document.getElementById('cooldown').value),
            volume: parseInt(document.getElementById('volume').value),
            modal_delay_ms: parseInt(document.getElementById('modalDelay').value),
            modal_auto_close_ms: parseInt(document.getElementById('modalAutoClose').value),
            winner_flash_duration_ms: parseInt(document.getElementById('winnerFlash').value)
        };

        const response = await fetch(BASE_PATH + '/api/config', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(config)
        });
        if (response.ok) {
            showNotification('Settings saved successfully! Reload the display page to see changes.');
        } else {
            showNotification('Failed to save settings.', 'error');
        }
    }

    async function uploadSound() {
        const fileInput = document.getElementById('soundUpload');
        if (fileInput.files.length === 0) return showNotification('Please select a file to upload.', 'error');

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);

        const response = await fetch(BASE_PATH + '/api/upload/sound', { method: 'POST', body: formData });
        if (response.ok) {
            showNotification('Sound uploaded successfully!');
            fileInput.value = '';
            loadDashboardData();
        } else {
            const error = await response.json();
            showNotification(`Upload failed: ${error.error}`, 'error');
        }
    }

    async function updateSoundSettings() {
        try {
            const custResponse = await fetch(BASE_PATH + '/api/customization');
            const custData = await custResponse.json();
            const customization = custData.customization || {};

            if (!customization.sounds) customization.sounds = {};
            if (!customization.sounds.system) customization.sounds.system = {};
            if (!customization.sounds.system.tick) customization.sounds.system.tick = {};
            if (!customization.sounds.system.modal_winner) customization.sounds.system.modal_winner = {};
            if (!customization.sounds.system.modal_loser) customization.sounds.system.modal_loser = {};

            customization.sounds.enabled = document.getElementById('soundsEnabled').checked;
            customization.sounds.master_volume = parseInt(document.getElementById('masterVolume').value);
            customization.sounds.system.tick.enabled = document.getElementById('tickEnabled').checked;
            customization.sounds.system.tick.volume = parseInt(document.getElementById('tickVolume').value) / 100;

            // Modal popup sounds
            customization.sounds.system.modal_winner.enabled = document.getElementById('modalWinnerSoundEnabled').checked;
            customization.sounds.system.modal_loser.enabled = document.getElementById('modalLoserSoundEnabled').checked;

            const response = await fetch(BASE_PATH + '/api/customization', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(customization)
            });

            if (response.ok) {
                showNotification('Sound settings saved!');
            } else {
                showNotification('Failed to save sound settings.', 'error');
            }
        } catch (e) {
            showNotification('Error saving sound settings: ' + e.message, 'error');
        }
    }

    async function clearHistory() {
        if (!confirm('ARE YOU SURE?\nThis will permanently delete all spin history.')) return;
        const response = await fetch(BASE_PATH + '/api/stats', { method: 'DELETE' });
        if (response.ok) {
            showNotification('Spin history cleared successfully!');
            loadDashboardData();
        } else {
            showNotification('Failed to clear history.', 'error');
        }
    }

    async function resetWheelState() {
        try {
            const response = await fetch(BASE_PATH + '/api/state/reset', { method: 'POST' });
            const data = await response.json();
            if (data.success) {
                showNotification('Wheel state reset! Refresh the display page.');
            } else {
                showNotification(data.error || 'Failed to reset state.', 'error');
            }
        } catch (error) {
            showNotification('Failed to reset wheel state.', 'error');
        }
    }

    // ==================== CUSTOMIZATION ====================
    let currentCustomization = {};
    let themePresets = [];

    async function loadCustomizationData() {
        try {
            const [customResponse, presetsResponse] = await Promise.all([
                fetch(BASE_PATH + '/api/customization'),
                fetch(BASE_PATH + '/api/presets/themes')
            ]);

            const customData = await customResponse.json();
            const presetsData = await presetsResponse.json();

            if (customData.success) {
                currentCustomization = customData.customization;
            }
            if (presetsData.success) {
                themePresets = presetsData.presets;
            }

            renderCustomization();
        } catch (error) {
            console.error('Failed to load customization:', error);
            showNotification('Failed to load customization data', 'error');
        }
    }

    function renderCustomization() {
        const container = document.getElementById('customizationContainer');
        const c = currentCustomization;
        const theme = c.theme || {};
        const colors = theme.colors || {};
        const branding = c.branding || {};
        const wheel = c.wheel || {};
        const effects = c.effects || {};
        const modal = c.modal || {};
        const advanced = c.advanced || {};

        container.innerHTML = `
            <!-- Theme Presets -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Theme Presets</div>
                <div class="customization-section-content">
                    <p style="font-size: 12px; opacity: 0.7; margin-bottom: 15px;">Select a preset theme to quickly change the look and feel of your wheel.</p>
                    <div class="preset-grid">
                        ${themePresets.map(preset => `
                            <div class="preset-card ${theme.preset === preset.id ? 'active' : ''}" onclick="applyPreset('${preset.id}')">
                                <div class="preset-colors">
                                    ${(preset.preview_colors || []).map(color => `<div class="preset-color" style="background: ${color}"></div>`).join('')}
                                </div>
                                <div class="preset-name">${escapeHtml(preset.name)}</div>
                                <div class="preset-desc">${escapeHtml(preset.description)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>

            <!-- Branding -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Branding</div>
                <div class="customization-section-content">
                    <div class="form-grid">
                        <div class="text-input-row">
                            <label>Title</label>
                            <input type="text" id="brandingTitle" value="${escapeHtml(branding.title || '')}" placeholder="Prize Wheel">
                        </div>
                        <div class="text-input-row">
                            <label>Subtitle</label>
                            <input type="text" id="brandingSubtitle" value="${escapeHtml(branding.subtitle || '')}" placeholder="Optional subtitle">
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="text-input-row">
                            <label>Logo URL</label>
                            <input type="text" id="brandingLogo" value="${escapeHtml(branding.logo_url || '')}" placeholder="/static/images/logo.png">
                        </div>
                        <div class="text-input-row">
                            <label>Badge Text</label>
                            <input type="text" id="brandingBadge" value="${escapeHtml(branding.badge_text || '')}" placeholder="Prize Wheel">
                        </div>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Show Branding Badge</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="brandingShowBadge" ${branding.show_branding_badge !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Theme Colors -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Theme Colors</div>
                <div class="customization-section-content">
                    <div class="color-grid">
                        ${renderColorInput('Primary', 'colorPrimary', colors.primary || '#FFD700')}
                        ${renderColorInput('Secondary', 'colorSecondary', colors.secondary || '#6B46C1')}
                        ${renderColorInput('Accent', 'colorAccent', colors.accent || '#FFA500')}
                        ${renderColorInput('Background', 'colorBackground', colors.background || '#0a0a14')}
                        ${renderColorInput('Background 2', 'colorBackgroundSecondary', colors.background_secondary || '#1a1a2e')}
                        ${renderColorInput('Text', 'colorTextPrimary', colors.text_primary || '#ffffff')}
                        ${renderColorInput('Success', 'colorSuccess', colors.success || '#4caf50')}
                        ${renderColorInput('Error', 'colorError', colors.error || '#f44336')}
                    </div>
                </div>
            </div>

            <!-- Wheel Appearance -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Wheel Appearance</div>
                <div class="customization-section-content">
                    <div class="form-grid">
                        <div class="slider-row">
                            <label>Wheel Size (vmin) <span class="slider-value" id="wheelSizeValue">${wheel.size?.size_vmin || 85}</span></label>
                            <input type="range" id="wheelSize" value="${wheel.size?.size_vmin || 85}" min="50" max="95" oninput="document.getElementById('wheelSizeValue').textContent = this.value">
                        </div>
                        <div class="slider-row">
                            <label>Max Size (px) <span class="slider-value" id="wheelMaxSizeValue">${wheel.size?.max_size_px || 900}</span></label>
                            <input type="range" id="wheelMaxSize" value="${wheel.size?.max_size_px || 900}" min="400" max="1200" step="50" oninput="document.getElementById('wheelMaxSizeValue').textContent = this.value">
                        </div>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Show Bezel</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="wheelBezelEnabled" ${wheel.bezel?.enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Show Studs</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="wheelStudsEnabled" ${wheel.studs?.enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Float Animation</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="wheelFloatEnabled" ${wheel.animation?.float_enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="slider-row" style="margin-top: 15px;">
                        <label>Number of Studs <span class="slider-value" id="studCountValue">${wheel.studs?.count || 32}</span></label>
                        <input type="range" id="studCount" value="${wheel.studs?.count || 32}" min="12" max="48" step="4" oninput="document.getElementById('studCountValue').textContent = this.value">
                    </div>
                    <div class="color-grid" style="margin-top: 15px;">
                        ${renderColorInput('Pointer', 'wheelPointerColor', wheel.pointer?.color || '#FFD700')}
                        ${renderColorInput('Center Icon', 'wheelCenterIconColor', wheel.center?.icon_color || '#FFD700')}
                    </div>
                </div>
            </div>

            <!-- Wheel Text & Fonts -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Wheel Text & Fonts</div>
                <div class="customization-section-content">
                    <p style="font-size: 12px; opacity: 0.7; margin-bottom: 15px;">Customize how prize names appear on the wheel segments.</p>

                    <div class="form-grid">
                        <div class="text-input-row">
                            <label>Font Family</label>
                            <select id="wheelTextFont" style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white;">
                                <option value="'Cinzel', serif" ${(wheel.text?.font_family || "'Cinzel', serif") === "'Cinzel', serif" ? 'selected' : ''}>Cinzel (Default)</option>
                                <option value="'Montserrat', sans-serif" ${wheel.text?.font_family === "'Montserrat', sans-serif" ? 'selected' : ''}>Montserrat</option>
                                <option value="'Arial Black', sans-serif" ${wheel.text?.font_family === "'Arial Black', sans-serif" ? 'selected' : ''}>Arial Black</option>
                                <option value="'Impact', sans-serif" ${wheel.text?.font_family === "'Impact', sans-serif" ? 'selected' : ''}>Impact</option>
                                <option value="'Georgia', serif" ${wheel.text?.font_family === "'Georgia', serif" ? 'selected' : ''}>Georgia</option>
                                <option value="'Trebuchet MS', sans-serif" ${wheel.text?.font_family === "'Trebuchet MS', sans-serif" ? 'selected' : ''}>Trebuchet MS</option>
                                <option value="'Verdana', sans-serif" ${wheel.text?.font_family === "'Verdana', sans-serif" ? 'selected' : ''}>Verdana</option>
                                <option value="'Comic Sans MS', cursive" ${wheel.text?.font_family === "'Comic Sans MS', cursive" ? 'selected' : ''}>Comic Sans</option>
                                <option value="'Courier New', monospace" ${wheel.text?.font_family === "'Courier New', monospace" ? 'selected' : ''}>Courier New</option>
                                <option value="'Roboto', sans-serif" ${wheel.text?.font_family === "'Roboto', sans-serif" ? 'selected' : ''}>Roboto</option>
                                <option value="'Open Sans', sans-serif" ${wheel.text?.font_family === "'Open Sans', sans-serif" ? 'selected' : ''}>Open Sans</option>
                                <option value="'Lato', sans-serif" ${wheel.text?.font_family === "'Lato', sans-serif" ? 'selected' : ''}>Lato</option>
                                <option value="'Oswald', sans-serif" ${wheel.text?.font_family === "'Oswald', sans-serif" ? 'selected' : ''}>Oswald</option>
                                <option value="'Playfair Display', serif" ${wheel.text?.font_family === "'Playfair Display', serif" ? 'selected' : ''}>Playfair Display</option>
                            </select>
                        </div>
                        <div class="text-input-row">
                            <label>Font Weight</label>
                            <select id="wheelTextWeight" style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,215,0,0.3); border-radius: 5px; color: white;">
                                <option value="400" ${wheel.text?.font_weight === '400' ? 'selected' : ''}>Normal (400)</option>
                                <option value="600" ${wheel.text?.font_weight === '600' ? 'selected' : ''}>Semi-Bold (600)</option>
                                <option value="700" ${wheel.text?.font_weight === '700' ? 'selected' : ''}>Bold (700)</option>
                                <option value="800" ${wheel.text?.font_weight === '800' ? 'selected' : ''}>Extra-Bold (800)</option>
                                <option value="900" ${(wheel.text?.font_weight || '900') === '900' ? 'selected' : ''}>Black (900) - Default</option>
                            </select>
                        </div>
                    </div>

                    <div class="slider-row" style="margin-top: 15px;">
                        <label>Maximum Font Size <span class="slider-value" id="wheelTextMaxSizeValue">${wheel.text?.max_font_size || 24}</span>px</label>
                        <input type="range" id="wheelTextMaxSize" value="${wheel.text?.max_font_size || 24}" min="12" max="48" oninput="document.getElementById('wheelTextMaxSizeValue').textContent = this.value">
                    </div>
                    <div class="slider-row">
                        <label>Minimum Font Size <span class="slider-value" id="wheelTextMinSizeValue">${wheel.text?.min_font_size || 8}</span>px</label>
                        <input type="range" id="wheelTextMinSize" value="${wheel.text?.min_font_size || 8}" min="6" max="20" oninput="document.getElementById('wheelTextMinSizeValue').textContent = this.value">
                    </div>

                    <div class="color-grid" style="margin-top: 15px;">
                        ${renderColorInput('Text Color', 'wheelTextColor', wheel.text?.color || '#ffffff')}
                        ${renderColorInput('Text Outline', 'wheelTextStroke', wheel.text?.stroke_color || 'rgba(0,0,0,0.8)')}
                    </div>

                    <div class="slider-row" style="margin-top: 15px;">
                        <label>Text Outline Thickness <span class="slider-value" id="wheelTextStrokeValue">${Math.round((wheel.text?.stroke_width_ratio || 0.125) * 100)}</span>%</label>
                        <input type="range" id="wheelTextStrokeWidth" value="${Math.round((wheel.text?.stroke_width_ratio || 0.125) * 100)}" min="0" max="30" oninput="document.getElementById('wheelTextStrokeValue').textContent = this.value">
                    </div>

                    <div class="toggle-row" style="margin-top: 15px;">
                        <span class="toggle-label">Use Text Gradient</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="wheelTextGradient" ${wheel.text?.use_gradient !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--gold-2);">Preview Text Style</label>
                        <div id="textPreview" style="font-size: 24px; text-align: center; padding: 15px; background: rgba(107,70,193,0.3); border-radius: 8px; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">
                            Sample Prize Text
                        </div>
                    </div>
                </div>
            </div>

            <!-- Effects -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Effects</div>
                <div class="customization-section-content">
                    <div class="toggle-row">
                        <span class="toggle-label">Enable Confetti</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="effectsConfetti" ${effects.confetti?.enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Winner Flash Effect</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="effectsWinnerFlash" ${effects.winner_flash?.enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Background Shimmer</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="effectsBackgroundShimmer" ${effects.background_shimmer?.enabled !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="slider-row" style="margin-top: 15px;">
                        <label>Winner Confetti Count <span class="slider-value" id="confettiWinnerValue">${effects.confetti?.winner_count || 150}</span></label>
                        <input type="range" id="confettiWinnerCount" value="${effects.confetti?.winner_count || 150}" min="50" max="300" step="10" oninput="document.getElementById('confettiWinnerValue').textContent = this.value">
                    </div>
                    <div class="slider-row">
                        <label>Loser Confetti Count <span class="slider-value" id="confettiLoserValue">${effects.confetti?.loser_count || 50}</span></label>
                        <input type="range" id="confettiLoserCount" value="${effects.confetti?.loser_count || 50}" min="0" max="150" step="10" oninput="document.getElementById('confettiLoserValue').textContent = this.value">
                    </div>
                </div>
            </div>

            <!-- Modal Customization -->
            <div class="customization-section">
                <div class="customization-section-title" onclick="toggleSection(this)">Winner Modal</div>
                <div class="customization-section-content">
                    <div class="form-grid">
                        <div class="text-input-row">
                            <label>Winner Badge Text</label>
                            <input type="text" id="modalWinnerBadge" value="${escapeHtml(modal.winner?.badge_text || 'WINNER')}">
                        </div>
                        <div class="text-input-row">
                            <label>Winner Title Text</label>
                            <input type="text" id="modalWinnerTitle" value="${escapeHtml(modal.winner?.title_text || 'Royal Victory')}">
                        </div>
                        <div class="text-input-row">
                            <label>Loser Badge Text</label>
                            <input type="text" id="modalLoserBadge" value="${escapeHtml(modal.loser?.badge_text || 'TRY AGAIN')}">
                        </div>
                        <div class="text-input-row">
                            <label>Loser Title Text</label>
                            <input type="text" id="modalLoserTitle" value="${escapeHtml(modal.loser?.title_text || 'Noble Effort')}">
                        </div>
                    </div>
                    <div class="slider-row" style="margin-top: 15px;">
                        <label>Modal Delay (ms) <span class="slider-value" id="modalDelayValue">${modal.delay_ms || 4500}</span></label>
                        <input type="range" id="modalDelay" value="${modal.delay_ms || 4500}" min="1000" max="10000" step="500" oninput="document.getElementById('modalDelayValue').textContent = this.value">
                    </div>
                    <div class="slider-row">
                        <label>Auto Close (ms) <span class="slider-value" id="modalAutoCloseValue">${modal.auto_close_ms || 6000}</span></label>
                        <input type="range" id="modalAutoClose" value="${modal.auto_close_ms || 6000}" min="2000" max="15000" step="500" oninput="document.getElementById('modalAutoCloseValue').textContent = this.value">
                    </div>
                </div>
            </div>

            <!-- Advanced -->
            <div class="customization-section collapsed">
                <div class="customization-section-title" onclick="toggleSection(this)">Advanced Settings</div>
                <div class="customization-section-content">
                    <div class="text-input-row">
                        <label>Custom CSS</label>
                        <textarea id="advancedCustomCss" placeholder="/* Add custom CSS styles here */">${escapeHtml(advanced.custom_css || '')}</textarea>
                    </div>
                    <div class="text-input-row">
                        <label>Custom JavaScript</label>
                        <textarea id="advancedCustomJs" placeholder="// Add custom JavaScript here">${escapeHtml(advanced.custom_js || '')}</textarea>
                    </div>
                    <div class="toggle-row">
                        <span class="toggle-label">Enable Keyboard Controls</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="accessibilityKeyboard" ${c.accessibility?.keyboard_controls !== false ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-success" onclick="saveCustomization()">Save Changes</button>
                <button class="btn btn-warning" onclick="resetCustomization()">Reset to Default</button>
                <a href="${BASE_PATH}/api/customization/export" class="btn btn-info" download>Export Config</a>
                <button class="btn btn-primary" onclick="document.getElementById('importFile').click()">Import Config</button>
                <input type="file" id="importFile" style="display: none;" accept=".json" onchange="importCustomization(this)">
            </div>
        `;

        // Set up live preview for text settings
        setupTextPreviewListeners();
    }

    function setupTextPreviewListeners() {
        const updatePreview = () => {
            const preview = document.getElementById('textPreview');
            if (!preview) return;

            const fontFamily = document.getElementById('wheelTextFont')?.value || "'Cinzel', serif";
            const fontWeight = document.getElementById('wheelTextWeight')?.value || '900';
            const fontSize = document.getElementById('wheelTextMaxSize')?.value || '24';
            const textColor = document.getElementById('wheelTextColorText')?.value || '#ffffff';
            const strokeColor = document.getElementById('wheelTextStrokeText')?.value || 'rgba(0,0,0,0.8)';
            const strokeWidth = (parseInt(document.getElementById('wheelTextStrokeWidth')?.value || '12') / 100) * parseInt(fontSize);

            preview.style.fontFamily = fontFamily;
            preview.style.fontWeight = fontWeight;
            preview.style.fontSize = fontSize + 'px';
            preview.style.color = textColor;
            preview.style.textShadow = `${strokeWidth}px ${strokeWidth}px ${strokeWidth * 2}px ${strokeColor}`;
        };

        // Attach listeners to all text setting inputs
        ['wheelTextFont', 'wheelTextWeight', 'wheelTextMaxSize', 'wheelTextMinSize',
         'wheelTextColor', 'wheelTextColorText', 'wheelTextStroke', 'wheelTextStrokeText',
         'wheelTextStrokeWidth', 'wheelTextGradient'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updatePreview);
                el.addEventListener('change', updatePreview);
            }
        });

        // Initial preview update
        setTimeout(updatePreview, 100);
    }

    function renderColorInput(label, id, value) {
        // Handle rgba values by extracting hex or using fallback
        let hexValue = value;
        if (value && value.startsWith('rgba')) {
            hexValue = '#ffffff';
        }
        return `
            <div class="color-input-group">
                <label>${label}</label>
                <input type="color" id="${id}" value="${hexValue}" onchange="document.getElementById('${id}Text').value = this.value">
                <input type="text" id="${id}Text" value="${value}" onchange="updateColorFromText('${id}', this.value)">
            </div>
        `;
    }

    function updateColorFromText(id, value) {
        const colorInput = document.getElementById(id);
        if (value && value.match(/^#[0-9A-Fa-f]{6}$/)) {
            colorInput.value = value;
        }
    }

    function toggleSection(titleEl) {
        titleEl.parentElement.classList.toggle('collapsed');
    }

    async function applyPreset(presetId) {
        try {
            const response = await fetch(BASE_PATH + '/api/customization/apply-preset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ preset_id: presetId })
            });

            const data = await response.json();
            if (data.success) {
                currentCustomization = data.customization;
                renderCustomization();
                showNotification('Theme preset applied! Reload the display page to see changes.');
            } else {
                showNotification(data.error || 'Failed to apply preset', 'error');
            }
        } catch (error) {
            showNotification('Failed to apply preset', 'error');
        }
    }

    async function saveCustomization() {
        const updates = {
            branding: {
                title: document.getElementById('brandingTitle').value,
                subtitle: document.getElementById('brandingSubtitle').value,
                logo_url: document.getElementById('brandingLogo').value,
                badge_text: document.getElementById('brandingBadge').value,
                show_branding_badge: document.getElementById('brandingShowBadge').checked
            },
            theme: {
                colors: {
                    primary: document.getElementById('colorPrimaryText').value,
                    secondary: document.getElementById('colorSecondaryText').value,
                    accent: document.getElementById('colorAccentText').value,
                    background: document.getElementById('colorBackgroundText').value,
                    background_secondary: document.getElementById('colorBackgroundSecondaryText').value,
                    text_primary: document.getElementById('colorTextPrimaryText').value,
                    success: document.getElementById('colorSuccessText').value,
                    error: document.getElementById('colorErrorText').value
                }
            },
            wheel: {
                size: {
                    size_vmin: parseInt(document.getElementById('wheelSize').value),
                    max_size_px: parseInt(document.getElementById('wheelMaxSize').value)
                },
                bezel: { enabled: document.getElementById('wheelBezelEnabled').checked },
                studs: {
                    enabled: document.getElementById('wheelStudsEnabled').checked,
                    count: parseInt(document.getElementById('studCount').value)
                },
                pointer: { color: document.getElementById('wheelPointerColorText').value },
                center: { icon_color: document.getElementById('wheelCenterIconColorText').value },
                animation: { float_enabled: document.getElementById('wheelFloatEnabled').checked },
                text: {
                    font_family: document.getElementById('wheelTextFont').value,
                    font_weight: document.getElementById('wheelTextWeight').value,
                    max_font_size: parseInt(document.getElementById('wheelTextMaxSize').value),
                    min_font_size: parseInt(document.getElementById('wheelTextMinSize').value),
                    color: document.getElementById('wheelTextColorText').value,
                    stroke_color: document.getElementById('wheelTextStrokeText').value,
                    stroke_width_ratio: parseInt(document.getElementById('wheelTextStrokeWidth').value) / 100,
                    use_gradient: document.getElementById('wheelTextGradient').checked
                }
            },
            effects: {
                confetti: {
                    enabled: document.getElementById('effectsConfetti').checked,
                    winner_count: parseInt(document.getElementById('confettiWinnerCount').value),
                    loser_count: parseInt(document.getElementById('confettiLoserCount').value)
                },
                winner_flash: { enabled: document.getElementById('effectsWinnerFlash').checked },
                background_shimmer: { enabled: document.getElementById('effectsBackgroundShimmer').checked }
            },
            modal: {
                delay_ms: parseInt(document.getElementById('modalDelay').value),
                auto_close_ms: parseInt(document.getElementById('modalAutoClose').value),
                winner: {
                    badge_text: document.getElementById('modalWinnerBadge').value,
                    title_text: document.getElementById('modalWinnerTitle').value
                },
                loser: {
                    badge_text: document.getElementById('modalLoserBadge').value,
                    title_text: document.getElementById('modalLoserTitle').value
                }
            },
            accessibility: {
                keyboard_controls: document.getElementById('accessibilityKeyboard').checked
            },
            advanced: {
                custom_css: document.getElementById('advancedCustomCss').value,
                custom_js: document.getElementById('advancedCustomJs').value
            }
        };

        try {
            const response = await fetch(BASE_PATH + '/api/customization', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updates)
            });

            const data = await response.json();
            if (data.success) {
                currentCustomization = data.customization;
                showNotification('Customization saved! Reload the display page to see changes.');
            } else {
                showNotification(data.error || 'Failed to save', 'error');
            }
        } catch (error) {
            showNotification('Failed to save customization', 'error');
        }
    }

    async function resetCustomization() {
        if (!confirm('Are you sure you want to reset all customization to defaults?')) return;

        try {
            const response = await fetch(BASE_PATH + '/api/customization/reset', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                currentCustomization = data.customization;
                renderCustomization();
                showNotification('Customization reset to defaults!');
            } else {
                showNotification(data.error || 'Failed to reset', 'error');
            }
        } catch (error) {
            showNotification('Failed to reset customization', 'error');
        }
    }

    async function importCustomization(input) {
        const file = input.files[0];
        if (!file) return;

        try {
            const text = await file.text();
            const config = JSON.parse(text);

            const response = await fetch(BASE_PATH + '/api/customization/import', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(config)
            });

            const data = await response.json();
            if (data.success) {
                currentCustomization = data.customization;
                renderCustomization();
                showNotification('Configuration imported successfully!');
            } else {
                showNotification(data.error || 'Failed to import', 'error');
            }
        } catch (error) {
            showNotification('Invalid configuration file', 'error');
        }

        input.value = '';
    }

    // Initialization - Polling-based updates instead of WebSocket
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Dashboard (Web Only) initialized');
        loadDashboardData();
        loadCustomizationData();

        // Poll for updates every 10 seconds
        setInterval(loadDashboardData, 10000);
    });
    </script>
</body>
</html>
