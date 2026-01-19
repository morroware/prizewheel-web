<?php
/**
 * Prize Wheel Display Page - Web Only Version
 * Polling-based real-time updates (no Socket.IO/GPIO)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Castle Kingdom Prize Wheel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;800&family=Montserrat:wght@400;600;800&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --gold-1: #FFD700;
      --gold-2: #FFA500;
      --gold-3: #FF8C00;
      --royal-purple: #6B46C1;
      --royal-blue: #1E3A8A;
      --castle-stone: #8B7D6B;
      --silver-1: #C0C0C0;
      --silver-2: #A8A8A8;
      --bronze-1: #CD7F32;
      --bronze-2: #B87333;
      --dark-bg: #0a0a14;
    }

    body {
      background:
        radial-gradient(ellipse at top left, rgba(107,70,193,0.15) 0%, transparent 50%),
        radial-gradient(ellipse at top right, rgba(30,58,138,0.15) 0%, transparent 50%),
        radial-gradient(ellipse at bottom, rgba(255,215,0,0.08) 0%, transparent 40%),
        linear-gradient(135deg, var(--dark-bg) 0%, #1a1a2e 35%, #16213e 70%, var(--dark-bg) 100%);
      color: #fff;
      font-family: 'Montserrat', system-ui, sans-serif;
      min-height: 100vh;
      display: grid;
      place-items: center;
      overflow: hidden;
      position: relative;
    }

    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 20% 80%, rgba(255,215,0,0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(107,70,193,0.04) 0%, transparent 50%),
        repeating-linear-gradient(45deg, transparent, transparent 100px, rgba(255,215,0,0.005) 100px, rgba(255,215,0,0.005) 102px);
      pointer-events: none;
      animation: backgroundShimmer 20s ease-in-out infinite;
    }

    @keyframes backgroundShimmer {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    .stage {
      position: relative;
      width: min(85vmin, 900px);
      aspect-ratio: 1 / 1;
      z-index: 10;
      filter: drop-shadow(0 40px 120px rgba(0,0,0,0.8));
    }

    .wheel-container {
      position: relative;
      width: 100%;
      height: 100%;
      animation: gentleFloat 8s ease-in-out infinite;
    }

    @keyframes gentleFloat {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-8px) scale(1.005); }
    }

    .wheel-bezel {
      position: absolute;
      inset: -20px;
      pointer-events: none;
      border-radius: 50%;
      background:
        radial-gradient(circle at 25% 25%, rgba(255,255,255,0.3) 0%, transparent 25%),
        conic-gradient(from 0deg, var(--gold-1), var(--gold-2), var(--bronze-1), var(--gold-3), var(--gold-1));
      padding: 8px;
      box-shadow:
        inset 0 0 60px rgba(0,0,0,0.7),
        0 0 80px rgba(255,215,0,0.4),
        0 0 120px rgba(255,215,0,0.2);
      animation: bezelGlow 4s ease-in-out infinite;
    }

    @keyframes bezelGlow {
      0%, 100% {
        box-shadow:
          inset 0 0 60px rgba(0,0,0,0.7),
          0 0 80px rgba(255,215,0,0.4),
          0 0 120px rgba(255,215,0,0.2);
      }
      50% {
        box-shadow:
          inset 0 0 60px rgba(0,0,0,0.7),
          0 0 100px rgba(255,215,0,0.6),
          0 0 140px rgba(255,215,0,0.3);
      }
    }

    .wheel-bezel::before {
      content: '';
      position: absolute;
      inset: 8px;
      border-radius: 50%;
      background:
        radial-gradient(circle at 30% 30%, rgba(42,26,78,0.8), rgba(10,10,20,0.95));
    }

    .wheel-studs {
      position: absolute;
      inset: -12px;
      pointer-events: none;
    }

    .stud {
      position: absolute;
      width: 16px;
      height: 16px;
      background:
        radial-gradient(circle at 30% 30%, var(--gold-1), var(--gold-2)),
        radial-gradient(circle, var(--gold-2), var(--bronze-1));
      border-radius: 50%;
      box-shadow:
        0 4px 8px rgba(0,0,0,0.6),
        inset 0 2px 4px rgba(255,255,255,0.3),
        inset 0 -2px 4px rgba(0,0,0,0.3);
      animation: studShine 3s ease-in-out infinite;
    }

    @keyframes studShine {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    #wheelCanvas {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      display: block;
      border-radius: 50%;
      cursor: pointer;
      z-index: 1;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      outline: none;
    }

    #wheelCanvas:hover {
      transform: scale(1.03);
      filter: brightness(1.1);
    }

    #wheelCanvas:focus {
      box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.6);
      transform: scale(1.01);
    }

    #wheelCanvas.disabled {
      pointer-events: none;
      cursor: not-allowed;
    }

    .pointer {
      position: absolute;
      top: -30px;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 0;
      z-index: 30;
      border-left: 42px solid transparent;
      border-right: 42px solid transparent;
      border-top: 80px solid var(--gold-1);
      filter:
        drop-shadow(0 15px 40px rgba(0,0,0,0.9))
        drop-shadow(0 0 30px rgba(255,215,0,0.8));
      animation: pointerPulse 2s ease-in-out infinite;
    }

    .pointer::before {
      content: '';
      position: absolute;
      left: -36px;
      top: -80px;
      width: 72px;
      height: 72px;
      background:
        linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.2) 40%, transparent 70%);
      clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
    }

    .pointer::after {
      content: '';
      position: absolute;
      left: -8px;
      top: -60px;
      font-size: 16px;
      color: var(--dark-bg);
      font-weight: bold;
      text-shadow: 0 1px 2px rgba(255,255,255,0.5);
    }

    @keyframes pointerPulse {
      0%, 100% {
        filter:
          drop-shadow(0 15px 40px rgba(0,0,0,0.9))
          drop-shadow(0 0 30px rgba(255,215,0,0.8));
        transform: translateX(-50%) scale(1);
      }
      50% {
        filter:
          drop-shadow(0 20px 50px rgba(0,0,0,1))
          drop-shadow(0 0 40px rgba(255,215,0,1));
        transform: translateX(-50%) scale(1.05);
      }
    }

    #fxCanvas {
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
      pointer-events: none;
      z-index: 1200;
    }

    .winner-modal {
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse at center, rgba(107,70,193,0.4), rgba(0,0,0,0.95)),
        linear-gradient(45deg, rgba(255,215,0,0.05), transparent);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1100;
      backdrop-filter: blur(15px);
      cursor: pointer;
    }

    .winner-card {
      background:
        linear-gradient(135deg, rgba(26,26,46,0.98), rgba(42,26,78,0.98)),
        radial-gradient(circle at 30% 30%, rgba(255,215,0,0.08), transparent 60%);
      padding: 80px 50px 50px;
      border-radius: 30px;
      text-align: center;
      max-width: 700px;
      width: calc(100% - 40px);
      border: 4px solid;
      border-image: linear-gradient(135deg, var(--gold-1), var(--gold-2), var(--bronze-1)) 1;
      box-shadow:
        0 0 150px rgba(255,215,0,0.3),
        0 40px 120px rgba(0,0,0,0.8),
        inset 0 1px 0 rgba(255,255,255,0.1);
      position: relative;
      overflow: visible;
      animation: cardMajesticAppear 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
      cursor: default;
    }

    @keyframes cardMajesticAppear {
      from {
        transform: scale(0.7) rotateY(-20deg) translateY(100px);
        opacity: 0;
      }
      to {
        transform: scale(1) rotateY(0) translateY(0);
        opacity: 1;
      }
    }

    .castle-crest {
      position: absolute;
      top: -60px;
      left: 50%;
      transform: translateX(-50%);
      width: 120px;
      height: 120px;
      background:
        radial-gradient(circle at 30% 30%, var(--gold-1), var(--gold-2)),
        conic-gradient(from 0deg, var(--gold-1), var(--gold-2), var(--bronze-1), var(--gold-1));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 56px;
      box-shadow:
        0 0 60px rgba(255,215,0,0.6),
        0 15px 40px rgba(0,0,0,0.6),
        inset 0 4px 0 rgba(255,255,255,0.3);
      border: 6px solid var(--dark-bg);
      animation: crestRotate 20s linear infinite;
    }

    @keyframes crestRotate {
      from { transform: translateX(-50%) rotate(0deg); }
      to { transform: translateX(-50%) rotate(360deg); }
    }

    .winner-badge {
      position: absolute;
      top: 15px;
      right: 25px;
      padding: 8px 20px;
      background: linear-gradient(135deg, var(--gold-1), var(--gold-2));
      color: var(--dark-bg);
      font-family: 'Cinzel', serif;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 3px;
      border-radius: 25px;
      box-shadow:
        0 6px 20px rgba(255,215,0,0.5),
        inset 0 1px 0 rgba(255,255,255,0.3);
    }

    .winner-title {
      font-family: 'Cinzel', serif;
      font-size: 18px;
      letter-spacing: 4px;
      text-transform: uppercase;
      color: var(--gold-1);
      font-weight: 600;
      margin-bottom: 15px;
      opacity: 0.95;
      text-shadow: 0 2px 10px rgba(255,215,0,0.3);
    }

    .winner-name {
      font-family: 'Cinzel', serif;
      font-size: clamp(36px, 6vw, 56px);
      font-weight: 800;
      margin: 25px 0;
      line-height: 1.1;
      color: #fff;
      text-shadow:
        0 0 50px rgba(255,215,0,0.6),
        0 6px 12px rgba(0,0,0,0.6);
      letter-spacing: 2px;
    }

    .winner-description {
      font-family: 'Montserrat', sans-serif;
      font-size: 20px;
      color: rgba(255,255,255,0.95);
      margin: 30px 0;
      padding: 25px;
      background:
        linear-gradient(135deg, rgba(0,0,0,0.4), rgba(255,215,0,0.05));
      border-radius: 15px;
      border: 2px solid rgba(255,215,0,0.3);
      line-height: 1.5;
      backdrop-filter: blur(10px);
    }

    .winner-card.loser {
      border-image: linear-gradient(135deg, var(--silver-1), var(--silver-2), var(--bronze-1)) 1;
    }

    .winner-card.loser .castle-crest {
      background:
        radial-gradient(circle at 30% 30%, var(--silver-1), var(--silver-2)),
        conic-gradient(from 0deg, var(--silver-1), var(--silver-2), var(--bronze-1), var(--silver-1));
    }

    .winner-card.loser .winner-badge {
      background: linear-gradient(135deg, var(--bronze-1), var(--bronze-2));
    }

    .winner-card.loser .winner-title {
      color: var(--silver-1);
    }

    .wheel-status {
      position: absolute;
      top: 20px;
      left: 20px;
      padding: 8px 16px;
      background: rgba(0,0,0,0.8);
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      z-index: 100;
      border: 1px solid rgba(255,215,0,0.3);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .wheel-status.visible {
      opacity: 1;
    }

    .wheel-status.spinning {
      color: #ff9800;
      border-color: #ff9800;
    }

    .wheel-status.cooldown {
      color: #2196f3;
      border-color: #2196f3;
    }

    .wheel-status.ready {
      color: #4caf50;
      border-color: #4caf50;
    }

    /* Web-only badge */
    .web-only-badge {
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 8px 16px;
      background: rgba(0,0,0,0.7);
      border-radius: 20px;
      font-size: 11px;
      color: rgba(255,255,255,0.6);
      z-index: 50;
      border: 1px solid rgba(255,215,0,0.2);
    }

    @media (max-width: 768px) {
      .stage { width: 92vmin; }
      .winner-card { padding: 60px 30px 40px; }
    }
  </style>
</head>
<body>
  <canvas id="fxCanvas"></canvas>

  <div class="wheel-status" id="wheelStatus">Ready to spin</div>

  <div class="stage" id="mainStage">
    <div class="wheel-container">
      <div class="wheel-bezel"></div>
      <div class="wheel-studs" id="wheelStuds"></div>
      <canvas id="wheelCanvas" aria-label="Prize wheel" tabindex="0" autofocus></canvas>
      <div class="pointer"></div>
    </div>
  </div>

  <div class="winner-modal" id="winnerModal">
    <div class="winner-card" id="winnerCard">
      <div class="castle-crest" id="crestIcon">&#127942;</div>
      <div class="winner-badge" id="winnerBadge">WINNER</div>
      <div class="winner-title" id="winnerTitle">Royal Decree</div>
      <h1 id="winnerName" class="winner-name"></h1>
      <div class="winner-description" id="winnerDescription"></div>
    </div>
  </div>

  <div class="web-only-badge">Web Only Version</div>

  <script>
    // Base path for subdirectory support
    window.BASE_PATH = '<?php echo BASE_PATH; ?>';

    // Helper to normalize paths (prepend BASE_PATH if path starts with /)
    function normalizePath(path) {
      if (!path) return path;
      if (path.startsWith('/') && !path.startsWith(window.BASE_PATH)) {
        return window.BASE_PATH + path;
      }
      return path;
    }

    // Configuration from PHP
    window.WHEEL_CONFIG = {
      prizes: <?php echo json_encode($prizes); ?>,
      systemSounds: <?php
        $systemSounds = $config['system_sounds'] ?? [
          'spin' => '/static/sounds/spin.mp3',
          'winner' => '/static/sounds/victory.mp3',
          'loser' => '/static/sounds/try-again.mp3'
        ];
        // Prepend BASE_PATH to sound paths
        foreach ($systemSounds as $key => $path) {
          if (strpos($path, '/') === 0 && strpos($path, BASE_PATH) !== 0) {
            $systemSounds[$key] = BASE_PATH . $path;
          }
        }
        echo json_encode($systemSounds);
      ?>,
      volume: <?php echo $config['volume'] ?? 75; ?>,
      modalDelayMs: <?php echo $config['modal_delay_ms'] ?? 3000; ?>,
      modalAutoCloseMs: <?php echo $config['modal_auto_close_ms'] ?? 10000; ?>,
      winnerFlashDurationMs: <?php echo $config['winner_flash_duration_ms'] ?? 4000; ?>,
      spinDurationMs: <?php echo ($config['spin_duration_seconds'] ?? 8) * 1000; ?>,
      cooldownMs: <?php echo ($config['cooldown_seconds'] ?? 3) * 1000; ?>
    };

    // Normalize prize sound paths
    window.WHEEL_CONFIG.prizes = window.WHEEL_CONFIG.prizes.map(function(prize) {
      if (prize.sound_path) {
        prize.sound_path = normalizePath(prize.sound_path);
      }
      return prize;
    });
  </script>

  <script type="text/javascript">
    // Configuration
    let MODAL_DELAY_MS = window.WHEEL_CONFIG.modalDelayMs;
    let AUTO_CLOSE_MS = window.WHEEL_CONFIG.modalAutoCloseMs;
    let WINNER_FLASH_DURATION_MS = window.WHEEL_CONFIG.winnerFlashDurationMs;
    let SPIN_DURATION_MS = window.WHEEL_CONFIG.spinDurationMs;

    // Global State
    let prizes = window.WHEEL_CONFIG.prizes || [];
    let currentRotation = 0;

    let wheelSpinState = {
      isSpinning: false,
      modalVisible: false,
      cooldownActive: false
    };

    // DOM Elements
    const wheelCanvas = document.getElementById('wheelCanvas');
    const fxCanvas = document.getElementById('fxCanvas');
    const wctx = wheelCanvas.getContext('2d');
    const fctx = fxCanvas.getContext('2d');
    const winnerModal = document.getElementById('winnerModal');
    const winnerCard = document.getElementById('winnerCard');
    const winnerNameEl = document.getElementById('winnerName');
    const winnerDescription = document.getElementById('winnerDescription');
    const winnerTitle = document.getElementById('winnerTitle');
    const winnerBadge = document.getElementById('winnerBadge');
    const crestIcon = document.getElementById('crestIcon');
    const wheelStatus = document.getElementById('wheelStatus');

    // Sound Manager
    const SoundManager = {
        sounds: {},
        masterVolume: 1.0,

        init(config) {
            this.masterVolume = (config.volume || 75) / 100;
            const soundPaths = new Set();

            if (config.systemSounds) {
                Object.values(config.systemSounds).forEach(path => {
                    if (path) soundPaths.add(path);
                });
            }

            config.prizes.forEach(prize => {
                if (prize.sound_path) soundPaths.add(prize.sound_path);
            });

            soundPaths.forEach(path => {
                this.sounds[path] = new Audio(path);
                this.sounds[path].volume = this.masterVolume;
                this.sounds[path].preload = 'auto';
                this.sounds[path].onerror = () => {
                    console.warn('Failed to load sound: ' + path);
                };
            });

            console.log('SoundManager initialized with ' + soundPaths.size + ' sounds');
        },

        play(path, volume) {
            volume = volume || 1.0;
            if (!path) return;

            if (this.sounds[path]) {
                try {
                    this.sounds[path].currentTime = 0;
                    this.sounds[path].volume = this.masterVolume * volume;
                    this.sounds[path].play().catch(function(e) {
                        console.error('Audio play failed for ' + path + ':', e);
                    });
                } catch (e) {
                    console.error('Audio error for ' + path + ':', e);
                }
            }
        },

        getSound(key) {
            return window.WHEEL_CONFIG.systemSounds?.[key] || '';
        },

        playSystemSound(key, volume) {
            volume = volume || 1.0;
            const soundPath = this.getSound(key);
            if (soundPath) {
                this.play(soundPath, volume);
            }
        }
    };

    const highlight = { index: -1, active: false, intensity: 0 };

    function updateWheelStatus() {
      const isWheelBusy = wheelSpinState.isSpinning || wheelSpinState.modalVisible || wheelSpinState.cooldownActive;

      wheelCanvas.classList.toggle('disabled', isWheelBusy);

      if (wheelSpinState.isSpinning) {
        wheelStatus.textContent = 'Spinning...';
        wheelStatus.className = 'wheel-status visible spinning';
      } else if (wheelSpinState.modalVisible) {
        wheelStatus.textContent = 'Modal Open';
        wheelStatus.className = 'wheel-status visible spinning';
      } else if (wheelSpinState.cooldownActive) {
        wheelStatus.textContent = 'Cooldown...';
        wheelStatus.className = 'wheel-status visible cooldown';
      } else {
        wheelStatus.textContent = 'Ready to spin';
        wheelStatus.className = 'wheel-status visible ready';

        setTimeout(function() {
          if (!wheelSpinState.isSpinning && !wheelSpinState.modalVisible && !wheelSpinState.cooldownActive) {
            wheelStatus.classList.remove('visible');
          }
        }, 2000);
      }
    }

    // Request spin via API (polling-based)
    async function requestSpin() {
      if (wheelSpinState.isSpinning || wheelSpinState.modalVisible || wheelSpinState.cooldownActive) {
        console.log('Spin blocked - wheel is busy');
        wheelStatus.textContent = 'Please wait...';
        wheelStatus.className = 'wheel-status visible spinning';
        setTimeout(updateWheelStatus, 1000);
        return false;
      }

      console.log('Spin request sent');

      try {
        const response = await fetch(window.BASE_PATH + '/api/spin', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_info: 'display_' + Date.now() })
        });

        const data = await response.json();

        if (data.success) {
          handleSpinStart(data);
        } else {
          console.log('Spin rejected:', data.error);
          wheelStatus.textContent = data.error || 'Please wait';
          wheelStatus.className = 'wheel-status visible spinning';
          setTimeout(updateWheelStatus, 2000);
        }
      } catch (error) {
        console.error('Spin request failed:', error);
        wheelStatus.textContent = 'Error - try again';
        wheelStatus.className = 'wheel-status visible spinning';
        setTimeout(updateWheelStatus, 2000);
      }

      return true;
    }

    function handleSpinStart(data) {
      if (wheelSpinState.isSpinning) {
        console.log('Spin already in progress, ignoring');
        return;
      }

      wheelSpinState.isSpinning = true;
      wheelSpinState.modalVisible = false;
      wheelSpinState.cooldownActive = false;
      updateWheelStatus();

      console.log('Spin started');
      closeWinnerModal();

      SoundManager.playSystemSound('spin');

      // Normalize prize sound paths from API response
      if (data.prizes) {
        prizes = data.prizes.map(function(prize) {
          if (prize.sound_path) {
            prize.sound_path = normalizePath(prize.sound_path);
          }
          return prize;
        });
      }

      // Normalize winner sound path
      if (data.winner && data.winner.sound_path) {
        data.winner.sound_path = normalizePath(data.winner.sound_path);
      }

      const winner = data.winner;
      const winnerIndex = prizes.findIndex(function(p) { return p.id === winner.id; });

      if (winnerIndex === -1) {
        wheelSpinState.isSpinning = false;
        updateWheelStatus();
        return;
      }

      const segmentAngle = (Math.PI * 2) / prizes.length;
      const winnerAngle = winnerIndex * segmentAngle + (segmentAngle / 2);
      const pointerAngle = Math.PI * 1.5;
      const spins = 10 + Math.floor(Math.random() * 6);
      const targetRotation = (spins * Math.PI * 2) + pointerAngle - winnerAngle;

      const startTime = performance.now();
      const duration = data.spin_duration || SPIN_DURATION_MS;

      function animate(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut = 1 - Math.pow(1 - progress, 4);
        currentRotation = targetRotation * easeOut;

        drawWheel();

        if (progress < 1) {
          requestAnimationFrame(animate);
        } else {
          console.log('Wheel animation complete');

          if (winner.is_winner) {
            SoundManager.playSystemSound('winner');
          } else {
            SoundManager.playSystemSound('loser');
          }

          flashWinner(winnerIndex);

          // Show modal after delay
          setTimeout(function() {
            handleSpinComplete(winner, data.cooldown_duration);
          }, MODAL_DELAY_MS);
        }
      }

      requestAnimationFrame(animate);
    }

    function handleSpinComplete(winner, cooldownDuration) {
      wheelSpinState.modalVisible = true;
      wheelSpinState.isSpinning = false;
      updateWheelStatus();

      console.log('Modal displayed');

      populateWinnerModal(winner);
      winnerModal.style.display = 'flex';
      spawnConfetti(winner.is_winner ? 150 : 50, winner.is_winner);

      if (winner.sound_path) {
        const systemWinnerSound = SoundManager.getSound('winner');
        const systemLoserSound = SoundManager.getSound('loser');

        if (winner.sound_path !== systemWinnerSound && winner.sound_path !== systemLoserSound) {
          console.log('Playing custom prize sound:', winner.sound_path);
          SoundManager.play(winner.sound_path);
        }
      }

      startCooldown(cooldownDuration || window.WHEEL_CONFIG.cooldownMs);

      if (winnerModal._autoclose) clearTimeout(winnerModal._autoclose);
      winnerModal._autoclose = setTimeout(closeWinnerModal, AUTO_CLOSE_MS);

      // Notify server spin is complete
      fetch(window.BASE_PATH + '/api/spin/complete', { method: 'POST' }).catch(function() {});
    }

    function closeWinnerModal() {
      winnerModal.style.display = 'none';
      wheelSpinState.modalVisible = false;
      updateWheelStatus();

      if (winnerModal._autoclose) {
        clearTimeout(winnerModal._autoclose);
      }
    }

    function startCooldown(duration) {
      wheelSpinState.cooldownActive = true;
      updateWheelStatus();
      const endTime = Date.now() + duration;

      console.log('Cooldown started for ' + duration + 'ms');

      const interval = setInterval(function() {
        const remaining = endTime - Date.now();
        if (remaining <= 0) {
          clearInterval(interval);
          wheelSpinState.cooldownActive = false;
          updateWheelStatus();
          console.log('Cooldown complete');
        }
      }, 100);
    }

    function createStuds() {
      const container = document.getElementById('wheelStuds');
      container.innerHTML = '';
      const numStuds = 32;
      const radius = 52;

      for (let i = 0; i < numStuds; i++) {
        const angle = (i / numStuds) * 2 * Math.PI;
        const x = 50 + radius * Math.cos(angle - Math.PI/2);
        const y = 50 + radius * Math.sin(angle - Math.PI/2);

        const stud = document.createElement('div');
        stud.className = 'stud';
        stud.style.left = x + '%';
        stud.style.top = y + '%';
        stud.style.transform = 'translate(-50%, -50%)';
        stud.style.animationDelay = (i * 0.1) + 's';
        container.appendChild(stud);
      }
    }

    function resizeCanvas() {
      const container = wheelCanvas.parentElement;
      const size = container.clientWidth;
      const ratio = Math.min(window.devicePixelRatio || 1, 2);

      wheelCanvas.width = size * ratio;
      wheelCanvas.height = size * ratio;
      wheelCanvas.style.width = size + 'px';
      wheelCanvas.style.height = size + 'px';
      wctx.setTransform(ratio, 0, 0, ratio, 0, 0);

      fxCanvas.width = window.innerWidth * ratio;
      fxCanvas.height = window.innerHeight * ratio;
      fxCanvas.style.width = window.innerWidth + 'px';
      fxCanvas.style.height = window.innerHeight + 'px';
      fctx.setTransform(ratio, 0, 0, ratio, 0, 0);

      drawWheel();
    }

    function drawWheel() {
        if (!wctx || !prizes || !prizes.length) return;

        const size = wheelCanvas.width / (Math.min(window.devicePixelRatio || 1, 2));
        const num = prizes.length;
        const radius = (size / 2) * 0.94;
        const center = size / 2;
        const segmentAngle = (Math.PI * 2) / num;

        wctx.clearRect(0, 0, size, size);
        wctx.save();
        wctx.translate(center, center);
        wctx.rotate(currentRotation);

        for (let i = 0; i < num; i++) {
            const startAngle = i * segmentAngle;
            const endAngle = startAngle + segmentAngle;
            const prize = prizes[i];

            const gradient = wctx.createRadialGradient(0, 0, radius * 0.2, 0, 0, radius);
            const baseColor = prize.color || '#4CAF50';
            gradient.addColorStop(0, lightenColor(baseColor, 35));
            gradient.addColorStop(0.4, lightenColor(baseColor, 15));
            gradient.addColorStop(0.8, baseColor);
            gradient.addColorStop(1, darkenColor(baseColor, 25));

            wctx.beginPath();
            wctx.moveTo(0, 0);
            wctx.arc(0, 0, radius, startAngle, endAngle);
            wctx.closePath();
            wctx.fillStyle = gradient;
            wctx.fill();

            wctx.strokeStyle = 'rgba(0,0,0,0.4)';
            wctx.lineWidth = 3;
            wctx.stroke();

            wctx.strokeStyle = 'rgba(255,255,255,0.15)';
            wctx.lineWidth = 1;
            wctx.stroke();

            if (highlight.active && highlight.index === i) {
                wctx.save();
                wctx.beginPath();
                wctx.moveTo(0, 0);
                wctx.arc(0, 0, radius, startAngle, endAngle);
                wctx.closePath();
                wctx.fillStyle = 'rgba(255, 255, 255, ' + (highlight.intensity * 0.5) + ')';
                wctx.fill();
                wctx.restore();
            }

            wctx.save();
            wctx.rotate(startAngle + segmentAngle / 2);

            const text = prize.name || 'Prize';
            const textRadius = radius * 0.65;
            const maxWidth = radius * 0.7;
            const availableAngle = segmentAngle * 0.8;
            const maxTextHeight = radius * Math.sin(availableAngle / 2) * 1.6;

            let fontSize = Math.min(radius / 10, 24);
            let lines = [];

            do {
                wctx.font = '900 ' + fontSize + 'px "Cinzel", serif';

                const words = text.split(' ');
                lines = [];
                let currentLine = '';

                for (const word of words) {
                    const testLine = currentLine ? currentLine + ' ' + word : word;
                    const metrics = wctx.measureText(testLine);

                    if (metrics.width > maxWidth && currentLine) {
                        lines.push(currentLine);
                        currentLine = word;
                    } else {
                        currentLine = testLine;
                    }
                }

                if (currentLine) lines.push(currentLine);

                const lineHeight = fontSize * 1.2;
                const totalHeight = lines.length * lineHeight;

                if (totalHeight <= maxTextHeight && lines.every(function(line) { return wctx.measureText(line).width <= maxWidth; })) {
                    break;
                }

                fontSize -= 1;
            } while (fontSize > 8);

            const lineHeight = fontSize * 1.2;
            const startY = -(lines.length - 1) * lineHeight / 2;

            wctx.textAlign = 'center';
            wctx.textBaseline = 'middle';

            lines.forEach(function(line, index) {
                const yPos = startY + index * lineHeight;

                wctx.strokeStyle = 'rgba(0,0,0,0.8)';
                wctx.lineWidth = Math.max(4, fontSize / 8);
                wctx.strokeText(line, textRadius, yPos);

                wctx.strokeStyle = darkenColor(prize.color || '#4CAF50', 40);
                wctx.lineWidth = Math.max(2, fontSize / 12);
                wctx.strokeText(line, textRadius, yPos);

                const textGradient = wctx.createLinearGradient(0, yPos - fontSize/2, 0, yPos + fontSize/2);
                textGradient.addColorStop(0, '#FFFFFF');
                textGradient.addColorStop(0.5, '#F8F8FF');
                textGradient.addColorStop(1, '#E6E6FA');

                wctx.fillStyle = textGradient;
                wctx.fillText(line, textRadius, yPos);
            });

            wctx.restore();
        }

        const centerGradient = wctx.createRadialGradient(0, 0, 0, 0, 0, radius * 0.18);
        centerGradient.addColorStop(0, '#4a4a6e');
        centerGradient.addColorStop(0.3, '#2a2a3e');
        centerGradient.addColorStop(0.7, '#1a1a2e');
        centerGradient.addColorStop(1, '#0a0a14');

        wctx.beginPath();
        wctx.arc(0, 0, radius * 0.15, 0, Math.PI * 2);
        wctx.fillStyle = centerGradient;
        wctx.fill();

        wctx.strokeStyle = '#FFB300';
        wctx.lineWidth = 4;
        wctx.stroke();

        wctx.strokeStyle = '#FFD700';
        wctx.lineWidth = 2;
        wctx.stroke();

        wctx.fillStyle = '#FFD700';
        wctx.font = (radius * 0.08) + 'px serif';
        wctx.textAlign = 'center';
        wctx.textBaseline = 'middle';
        wctx.fillText('\u2654', 0, 0);

        wctx.restore();
    }

    function lightenColor(color, percent) {
      const num = parseInt(color.slice(1), 16);
      const amt = Math.round(2.55 * percent);
      const R = Math.min(255, (num >> 16) + amt);
      const G = Math.min(255, (num >> 8 & 0x00FF) + amt);
      const B = Math.min(255, (num & 0x0000FF) + amt);
      return '#' + (0x1000000 + (R << 16) + (G << 8) + B).toString(16).slice(1);
    }

    function darkenColor(color, percent) {
      const num = parseInt(color.slice(1), 16);
      const amt = Math.round(2.55 * percent);
      const R = Math.max(0, (num >> 16) - amt);
      const G = Math.max(0, (num >> 8 & 0x00FF) - amt);
      const B = Math.max(0, (num & 0x0000FF) - amt);
      return '#' + (0x1000000 + (R << 16) + (G << 8) + B).toString(16).slice(1);
    }

    const confetti = { particles: [], active: false };
    const WINNER_COLORS = ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#9370DB', '#FF6347', '#32CD32'];
    const LOSER_COLORS = ['#C0C0C0', '#A8A8A8', '#D3D3D3', '#B8860B'];

    function spawnConfetti(count, isWinner) {
      isWinner = isWinner !== false;
      const colors = isWinner ? WINNER_COLORS : LOSER_COLORS;
      for (let i = 0; i < count; i++) {
        confetti.particles.push({
          x: Math.random() * window.innerWidth,
          y: -20,
          vx: (Math.random() - 0.5) * 8,
          vy: Math.random() * 4 + 2,
          gravity: 0.12,
          width: Math.random() * 10 + 6,
          height: Math.random() * 14 + 8,
          angle: Math.random() * Math.PI * 2,
          angleVel: (Math.random() - 0.5) * 0.3,
          color: colors[Math.floor(Math.random() * colors.length)],
          life: 0,
          maxLife: 400,
          shape: Math.random() > 0.3 ? 'rect' : 'circle',
          shimmer: Math.random() * 0.5 + 0.5
        });
      }
      if (!confetti.active) {
        confetti.active = true;
        animateConfetti();
      }
    }

    function animateConfetti() {
      fctx.clearRect(0, 0, fxCanvas.width, fxCanvas.height);

      confetti.particles = confetti.particles.filter(function(p) {
        p.vy += p.gravity;
        p.x += p.vx;
        p.y += p.vy;
        p.angle += p.angleVel;
        p.life++;

        const alpha = (1 - (p.life / p.maxLife)) * p.shimmer;

        fctx.save();
        fctx.translate(p.x, p.y);
        fctx.rotate(p.angle);
        fctx.globalAlpha = alpha;

        fctx.shadowColor = p.color;
        fctx.shadowBlur = 10;
        fctx.fillStyle = p.color;

        if (p.shape === 'rect') {
          fctx.fillRect(-p.width / 2, -p.height / 2, p.width, p.height);
        } else {
          fctx.beginPath();
          fctx.arc(0, 0, p.width / 2, 0, Math.PI * 2);
          fctx.fill();
        }

        fctx.restore();

        return p.life < p.maxLife && p.y < window.innerHeight + 100;
      });

      if (confetti.particles.length > 0) {
        requestAnimationFrame(animateConfetti);
      } else {
        confetti.active = false;
      }
    }

    function flashWinner(index) {
      highlight.index = index;
      highlight.active = true;

      const startTime = performance.now();
      function flash(currentTime) {
        const elapsed = currentTime - startTime;
        highlight.intensity = Math.abs(Math.sin(elapsed * 0.015)) * 0.7 + 0.3;
        drawWheel();

        if (elapsed < WINNER_FLASH_DURATION_MS) {
          requestAnimationFrame(flash);
        } else {
          highlight.active = false;
          drawWheel();
        }
      }

      requestAnimationFrame(flash);
    }

    function populateWinnerModal(prize) {
      if (!prize) return;

      const isWinner = prize.is_winner !== false;
      winnerCard.classList.toggle('loser', !isWinner);

      crestIcon.innerHTML = isWinner ? '&#127942;' : '&#128737;';
      winnerBadge.textContent = isWinner ? 'WINNER' : 'TRY AGAIN';
      winnerTitle.textContent = isWinner ? 'Royal Victory' : 'Noble Effort';
      winnerNameEl.textContent = prize.name || 'Mystery Prize';
      winnerDescription.textContent = prize.description ||
        (isWinner ? 'Congratulations! Claim your royal reward!' : 'Fortune favors the persistent. Try again!');
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Prize Wheel (Web Only) Initializing...');

      SoundManager.init(window.WHEEL_CONFIG);

      const ensureCanvasFocus = function() {
        wheelCanvas.focus();
      };

      ensureCanvasFocus();
      setTimeout(ensureCanvasFocus, 100);

      createStuds();
      resizeCanvas();
      window.addEventListener('resize', resizeCanvas);

      wheelCanvas.addEventListener('click', function(e) {
        e.preventDefault();
        wheelCanvas.focus();
        requestSpin();
      });

      winnerModal.addEventListener('click', closeWinnerModal);

      window.addEventListener('keydown', function(e) {
        if (e.code === 'Space') {
          e.preventDefault();

          if (wheelSpinState.modalVisible) {
            closeWinnerModal();
          } else {
            wheelCanvas.focus();
            requestSpin();
          }
        }

        if (e.code === 'Escape' && wheelSpinState.modalVisible) {
          closeWinnerModal();
        }
      });

      updateWheelStatus();

      console.log('Prize Wheel initialized with ' + prizes.length + ' prizes');
    });
  </script>
</body>
</html>
