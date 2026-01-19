<?php
/**
 * Prize Wheel Display Page - Fully Customizable Version
 * Uses customization.json for all theming and styling
 */

// Load customization settings
$customization = getCustomization();
$c = $customization; // Shorthand

// Extract commonly used values
$theme = $c['theme'] ?? [];
$colors = $theme['colors'] ?? [];
$gradients = $theme['gradients'] ?? [];
$fonts = $theme['fonts'] ?? [];
$wheel = $c['wheel'] ?? [];
$effects = $c['effects'] ?? [];
$modal = $c['modal'] ?? [];
$branding = $c['branding'] ?? [];
$sounds = $c['sounds'] ?? [];
$statusIndicator = $c['status_indicator'] ?? [];
$advanced = $c['advanced'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($branding['title'] ?? 'Prize Wheel'); ?></title>
  <?php if (!empty($branding['favicon_url'])): ?>
  <link rel="icon" href="<?php echo htmlspecialchars($branding['favicon_url']); ?>">
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?php echo htmlspecialchars($fonts['google_fonts_url'] ?? 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;800&family=Montserrat:wght@400;600;800&display=swap'); ?>" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      /* Theme Colors */
      --color-primary: <?php echo $colors['primary'] ?? '#FFD700'; ?>;
      --color-secondary: <?php echo $colors['secondary'] ?? '#6B46C1'; ?>;
      --color-accent: <?php echo $colors['accent'] ?? '#FFA500'; ?>;
      --color-background: <?php echo $colors['background'] ?? '#0a0a14'; ?>;
      --color-background-secondary: <?php echo $colors['background_secondary'] ?? '#1a1a2e'; ?>;
      --color-text-primary: <?php echo $colors['text_primary'] ?? '#ffffff'; ?>;
      --color-text-secondary: <?php echo $colors['text_secondary'] ?? 'rgba(255,255,255,0.7)'; ?>;
      --color-success: <?php echo $colors['success'] ?? '#4caf50'; ?>;
      --color-error: <?php echo $colors['error'] ?? '#f44336'; ?>;
      --color-warning: <?php echo $colors['warning'] ?? '#ff9800'; ?>;

      /* Fonts */
      --font-heading: <?php echo $fonts['heading'] ?? "'Cinzel', serif"; ?>;
      --font-body: <?php echo $fonts['body'] ?? "'Montserrat', sans-serif"; ?>;

      /* Wheel */
      --wheel-max-size: <?php echo ($wheel['size']['max_size_px'] ?? 900) . 'px'; ?>;
      --wheel-size-vmin: <?php echo ($wheel['size']['size_vmin'] ?? 85) . 'vmin'; ?>;
      --wheel-mobile-size: <?php echo ($wheel['size']['mobile_size_vmin'] ?? 92) . 'vmin'; ?>;
      --wheel-bezel-width: <?php echo ($wheel['bezel']['width'] ?? 20) . 'px'; ?>;
      --wheel-pointer-color: <?php echo $wheel['pointer']['color'] ?? '#FFD700'; ?>;
      --wheel-pointer-glow: <?php echo $wheel['pointer']['glow_color'] ?? 'rgba(255,215,0,0.8)'; ?>;

      /* Status Indicator */
      --status-spinning: <?php echo $statusIndicator['spinning_color'] ?? '#ff9800'; ?>;
      --status-cooldown: <?php echo $statusIndicator['cooldown_color'] ?? '#2196f3'; ?>;
      --status-ready: <?php echo $statusIndicator['ready_color'] ?? '#4caf50'; ?>;

      /* Focus */
      --focus-color: <?php echo $c['accessibility']['focus_outline_color'] ?? 'rgba(255, 215, 0, 0.6)'; ?>;
    }

    body {
      background: <?php echo $gradients['background'] ?? 'linear-gradient(135deg, #0a0a14 0%, #1a1a2e 35%, #16213e 70%, #0a0a14 100%)'; ?>;
      color: var(--color-text-primary);
      font-family: var(--font-body);
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
        <?php echo $gradients['overlay_top_left'] ?? 'radial-gradient(ellipse at top left, rgba(107,70,193,0.15) 0%, transparent 50%)'; ?>,
        <?php echo $gradients['overlay_top_right'] ?? 'radial-gradient(ellipse at top right, rgba(30,58,138,0.15) 0%, transparent 50%)'; ?>,
        <?php echo $gradients['overlay_bottom'] ?? 'radial-gradient(ellipse at bottom, rgba(255,215,0,0.08) 0%, transparent 40%)'; ?>;
      pointer-events: none;
      <?php if ($effects['background_shimmer']['enabled'] ?? true): ?>
      animation: backgroundShimmer <?php echo ($effects['background_shimmer']['duration_s'] ?? 20) . 's'; ?> ease-in-out infinite;
      <?php endif; ?>
    }

    @keyframes backgroundShimmer {
      0%, 100% { opacity: 1; }
      50% { opacity: <?php echo $effects['background_shimmer']['min_opacity'] ?? 0.7; ?>; }
    }

    .branding-header {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      text-align: center;
      z-index: 50;
    }

    .branding-logo {
      max-height: 60px;
      margin-bottom: 10px;
    }

    .branding-title {
      font-family: var(--font-heading);
      font-size: 28px;
      color: var(--color-primary);
      text-shadow: 0 2px 10px rgba(0,0,0,0.5);
    }

    .branding-subtitle {
      font-size: 14px;
      color: var(--color-text-secondary);
      margin-top: 5px;
    }

    .stage {
      position: relative;
      width: min(var(--wheel-size-vmin), var(--wheel-max-size));
      aspect-ratio: 1 / 1;
      z-index: 10;
      filter: drop-shadow(0 40px 120px rgba(0,0,0,0.8));
    }

    .wheel-container {
      position: relative;
      width: 100%;
      height: 100%;
      <?php if ($wheel['animation']['float_enabled'] ?? true): ?>
      animation: gentleFloat <?php echo ($wheel['animation']['float_duration_s'] ?? 8) . 's'; ?> ease-in-out infinite;
      <?php endif; ?>
    }

    @keyframes gentleFloat {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-<?php echo ($wheel['animation']['float_amplitude_px'] ?? 8) . 'px'; ?>) scale(1.005); }
    }

    <?php if ($wheel['bezel']['enabled'] ?? true): ?>
    .wheel-bezel {
      position: absolute;
      inset: calc(-1 * var(--wheel-bezel-width));
      pointer-events: none;
      border-radius: 50%;
      background:
        radial-gradient(circle at 25% 25%, rgba(255,255,255,0.3) 0%, transparent 25%),
        conic-gradient(from 0deg, <?php echo implode(', ', $wheel['bezel']['colors'] ?? ['#FFD700', '#FFA500', '#CD7F32', '#FF8C00']); ?>);
      padding: 8px;
      box-shadow:
        inset 0 0 60px rgba(0,0,0,0.7),
        0 0 <?php echo ($wheel['bezel']['glow_size'] ?? 80) . 'px'; ?> <?php echo $wheel['bezel']['glow_color'] ?? 'rgba(255,215,0,0.4)'; ?>,
        0 0 120px <?php echo str_replace('0.4', '0.2', $wheel['bezel']['glow_color'] ?? 'rgba(255,215,0,0.4)'); ?>;
      <?php if ($wheel['bezel']['animation_enabled'] ?? true): ?>
      animation: bezelGlow 4s ease-in-out infinite;
      <?php endif; ?>
    }

    @keyframes bezelGlow {
      0%, 100% {
        box-shadow:
          inset 0 0 60px rgba(0,0,0,0.7),
          0 0 80px <?php echo $wheel['bezel']['glow_color'] ?? 'rgba(255,215,0,0.4)'; ?>,
          0 0 120px <?php echo str_replace('0.4', '0.2', $wheel['bezel']['glow_color'] ?? 'rgba(255,215,0,0.4)'); ?>;
      }
      50% {
        box-shadow:
          inset 0 0 60px rgba(0,0,0,0.7),
          0 0 100px <?php echo str_replace('0.4', '0.6', $wheel['bezel']['glow_color'] ?? 'rgba(255,215,0,0.4)'); ?>,
          0 0 140px <?php echo str_replace('0.4', '0.3', $wheel['bezel']['glow_color'] ?? 'rgba(255,215,0,0.4)'); ?>;
      }
    }

    .wheel-bezel::before {
      content: '';
      position: absolute;
      inset: 8px;
      border-radius: 50%;
      background: radial-gradient(circle at 30% 30%, var(--color-background-secondary), var(--color-background));
    }
    <?php endif; ?>

    <?php if ($wheel['studs']['enabled'] ?? true): ?>
    .wheel-studs {
      position: absolute;
      inset: -12px;
      pointer-events: none;
    }

    .stud {
      position: absolute;
      width: <?php echo ($wheel['studs']['size'] ?? 16) . 'px'; ?>;
      height: <?php echo ($wheel['studs']['size'] ?? 16) . 'px'; ?>;
      background:
        radial-gradient(circle at 30% 30%, <?php echo ($wheel['studs']['colors'][0] ?? '#FFD700'); ?>, <?php echo ($wheel['studs']['colors'][1] ?? '#FFA500'); ?>),
        radial-gradient(circle, <?php echo ($wheel['studs']['colors'][1] ?? '#FFA500'); ?>, <?php echo ($wheel['studs']['colors'][2] ?? '#CD7F32'); ?>);
      border-radius: 50%;
      box-shadow:
        0 4px 8px rgba(0,0,0,0.6),
        inset 0 2px 4px rgba(255,255,255,0.3),
        inset 0 -2px 4px rgba(0,0,0,0.3);
      <?php if ($wheel['studs']['animation_enabled'] ?? true): ?>
      animation: studShine 3s ease-in-out infinite;
      <?php endif; ?>
    }

    @keyframes studShine {
      0%, 100% { transform: translate(-50%, -50%) scale(1); }
      50% { transform: translate(-50%, -50%) scale(1.1); }
    }
    <?php endif; ?>

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
      transform: scale(<?php echo $wheel['animation']['hover_scale'] ?? 1.03; ?>);
      filter: brightness(<?php echo $wheel['animation']['hover_brightness'] ?? 1.1; ?>);
    }

    <?php if ($c['accessibility']['focus_outline_enabled'] ?? true): ?>
    #wheelCanvas:focus {
      box-shadow: 0 0 0 4px var(--focus-color);
      transform: scale(1.01);
    }
    <?php endif; ?>

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
      border-left: <?php echo (($wheel['pointer']['width'] ?? 84) / 2) . 'px'; ?> solid transparent;
      border-right: <?php echo (($wheel['pointer']['width'] ?? 84) / 2) . 'px'; ?> solid transparent;
      border-top: <?php echo ($wheel['pointer']['height'] ?? 80) . 'px'; ?> solid var(--wheel-pointer-color);
      <?php if ($wheel['pointer']['glow_enabled'] ?? true): ?>
      filter:
        drop-shadow(0 15px 40px rgba(0,0,0,0.9))
        drop-shadow(0 0 30px var(--wheel-pointer-glow));
      <?php endif; ?>
      <?php if ($wheel['pointer']['animation_enabled'] ?? true): ?>
      animation: pointerPulse 2s ease-in-out infinite;
      <?php endif; ?>
    }

    .pointer::before {
      content: '';
      position: absolute;
      left: -<?php echo (($wheel['pointer']['width'] ?? 84) / 2 - 6) . 'px'; ?>;
      top: -<?php echo ($wheel['pointer']['height'] ?? 80) . 'px'; ?>;
      width: <?php echo (($wheel['pointer']['width'] ?? 84) - 12) . 'px'; ?>;
      height: <?php echo (($wheel['pointer']['width'] ?? 84) - 12) . 'px'; ?>;
      background:
        linear-gradient(180deg, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.2) 40%, transparent 70%);
      clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
    }

    @keyframes pointerPulse {
      0%, 100% {
        filter:
          drop-shadow(0 15px 40px rgba(0,0,0,0.9))
          drop-shadow(0 0 30px var(--wheel-pointer-glow));
        transform: translateX(-50%) scale(1);
      }
      50% {
        filter:
          drop-shadow(0 20px 50px rgba(0,0,0,1))
          drop-shadow(0 0 40px var(--wheel-pointer-glow));
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
        radial-gradient(ellipse at center, rgba(107,70,193,0.4), <?php echo $modal['backdrop_color'] ?? 'rgba(0,0,0,0.95)'; ?>),
        linear-gradient(45deg, rgba(255,215,0,0.05), transparent);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1100;
      <?php if (($modal['backdrop_blur'] ?? 15) > 0): ?>
      backdrop-filter: blur(<?php echo ($modal['backdrop_blur'] ?? 15) . 'px'; ?>);
      <?php endif; ?>
      cursor: pointer;
    }

    .winner-card {
      background: <?php echo $modal['card']['background'] ?? 'linear-gradient(135deg, rgba(26,26,46,0.98), rgba(42,26,78,0.98))'; ?>;
      padding: <?php echo $modal['card']['padding'] ?? '80px 50px 50px'; ?>;
      border-radius: <?php echo ($modal['card']['border_radius'] ?? 30) . 'px'; ?>;
      text-align: center;
      max-width: <?php echo ($modal['card']['max_width'] ?? 700) . 'px'; ?>;
      width: calc(100% - 40px);
      border: 4px solid;
      border-image: linear-gradient(135deg, <?php echo implode(', ', $modal['winner']['border_gradient'] ?? ['#FFD700', '#FFA500', '#CD7F32']); ?>) 1;
      box-shadow:
        0 0 150px rgba(255,215,0,0.3),
        0 40px 120px rgba(0,0,0,0.8),
        inset 0 1px 0 rgba(255,255,255,0.1);
      position: relative;
      overflow: visible;
      animation: cardMajesticAppear <?php echo $modal['animation']['entrance_duration'] ?? '0.8s'; ?> cubic-bezier(0.34, 1.56, 0.64, 1);
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
        radial-gradient(circle at 30% 30%, <?php echo ($modal['winner']['crest_colors'][0] ?? '#FFD700'); ?>, <?php echo ($modal['winner']['crest_colors'][1] ?? '#FFA500'); ?>),
        conic-gradient(from 0deg, <?php echo implode(', ', $modal['winner']['crest_colors'] ?? ['#FFD700', '#FFA500', '#CD7F32']); ?>, <?php echo ($modal['winner']['crest_colors'][0] ?? '#FFD700'); ?>);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 56px;
      box-shadow:
        0 0 60px rgba(255,215,0,0.6),
        0 15px 40px rgba(0,0,0,0.6),
        inset 0 4px 0 rgba(255,255,255,0.3);
      border: 6px solid var(--color-background);
      <?php if ($modal['animation']['crest_rotation_enabled'] ?? true): ?>
      animation: crestRotate <?php echo $modal['animation']['crest_rotation_duration'] ?? '20s'; ?> linear infinite;
      <?php endif; ?>
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
      background: <?php echo $modal['winner']['badge_background'] ?? 'linear-gradient(135deg, #FFD700, #FFA500)'; ?>;
      color: var(--color-background);
      font-family: var(--font-heading);
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
      font-family: var(--font-heading);
      font-size: 18px;
      letter-spacing: 4px;
      text-transform: uppercase;
      color: var(--color-primary);
      font-weight: 600;
      margin-bottom: 15px;
      opacity: 0.95;
      text-shadow: 0 2px 10px rgba(255,215,0,0.3);
    }

    .winner-name {
      font-family: var(--font-heading);
      font-size: clamp(36px, 6vw, 56px);
      font-weight: 800;
      margin: 25px 0;
      line-height: 1.1;
      color: var(--color-text-primary);
      text-shadow:
        0 0 50px rgba(255,215,0,0.6),
        0 6px 12px rgba(0,0,0,0.6);
      letter-spacing: 2px;
    }

    .winner-description {
      font-family: var(--font-body);
      font-size: 20px;
      color: var(--color-text-primary);
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
      border-image: linear-gradient(135deg, <?php echo implode(', ', $modal['loser']['border_gradient'] ?? ['#C0C0C0', '#A8A8A8', '#CD7F32']); ?>) 1;
    }

    .winner-card.loser .castle-crest {
      background:
        radial-gradient(circle at 30% 30%, <?php echo ($modal['loser']['crest_colors'][0] ?? '#C0C0C0'); ?>, <?php echo ($modal['loser']['crest_colors'][1] ?? '#A8A8A8'); ?>),
        conic-gradient(from 0deg, <?php echo implode(', ', $modal['loser']['crest_colors'] ?? ['#C0C0C0', '#A8A8A8', '#CD7F32']); ?>, <?php echo ($modal['loser']['crest_colors'][0] ?? '#C0C0C0'); ?>);
    }

    .winner-card.loser .winner-badge {
      background: <?php echo $modal['loser']['badge_background'] ?? 'linear-gradient(135deg, #CD7F32, #B87333)'; ?>;
    }

    .winner-card.loser .winner-title {
      color: <?php echo ($modal['loser']['crest_colors'][0] ?? '#C0C0C0'); ?>;
    }

    <?php if ($statusIndicator['enabled'] ?? true): ?>
    .wheel-status {
      position: absolute;
      top: 20px;
      <?php if (($statusIndicator['position'] ?? 'top-left') === 'top-left'): ?>
      left: 20px;
      <?php else: ?>
      right: 20px;
      <?php endif; ?>
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

    .wheel-status.visible { opacity: 1; }
    .wheel-status.spinning { color: var(--status-spinning); border-color: var(--status-spinning); }
    .wheel-status.cooldown { color: var(--status-cooldown); border-color: var(--status-cooldown); }
    .wheel-status.ready { color: var(--status-ready); border-color: var(--status-ready); }
    <?php endif; ?>

    .web-only-badge {
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 8px 16px;
      background: rgba(0,0,0,0.7);
      border-radius: 20px;
      font-size: 11px;
      color: var(--color-text-secondary);
      z-index: 50;
      border: 1px solid rgba(255,215,0,0.2);
    }

    @media (max-width: 768px) {
      .stage { width: var(--wheel-mobile-size); }
      .winner-card { padding: 60px 30px 40px; }
      .branding-header { display: none; }
    }

    /* Custom CSS from settings */
    <?php echo $advanced['custom_css'] ?? ''; ?>
  </style>
</head>
<body>
  <canvas id="fxCanvas"></canvas>

  <?php if (!empty($branding['logo_url']) || !empty($branding['title'])): ?>
  <div class="branding-header">
    <?php if (!empty($branding['logo_url'])): ?>
    <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="Logo" class="branding-logo">
    <?php endif; ?>
    <?php if (!empty($branding['title'])): ?>
    <h1 class="branding-title"><?php echo htmlspecialchars($branding['title']); ?></h1>
    <?php endif; ?>
    <?php if (!empty($branding['subtitle'])): ?>
    <p class="branding-subtitle"><?php echo htmlspecialchars($branding['subtitle']); ?></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($statusIndicator['enabled'] ?? true): ?>
  <div class="wheel-status" id="wheelStatus">Ready to spin</div>
  <?php endif; ?>

  <div class="stage" id="mainStage">
    <div class="wheel-container">
      <?php if ($wheel['bezel']['enabled'] ?? true): ?>
      <div class="wheel-bezel"></div>
      <?php endif; ?>
      <?php if ($wheel['studs']['enabled'] ?? true): ?>
      <div class="wheel-studs" id="wheelStuds"></div>
      <?php endif; ?>
      <canvas id="wheelCanvas" aria-label="Prize wheel - click or press space to spin" tabindex="0" autofocus></canvas>
      <div class="pointer"></div>
    </div>
  </div>

  <div class="winner-modal" id="winnerModal">
    <div class="winner-card" id="winnerCard">
      <div class="castle-crest" id="crestIcon"><?php echo $modal['winner']['crest_icon'] ?? '&#127942;'; ?></div>
      <div class="winner-badge" id="winnerBadge"><?php echo htmlspecialchars($modal['winner']['badge_text'] ?? 'WINNER'); ?></div>
      <div class="winner-title" id="winnerTitle"><?php echo htmlspecialchars($modal['winner']['title_text'] ?? 'Royal Victory'); ?></div>
      <h1 id="winnerName" class="winner-name"></h1>
      <div class="winner-description" id="winnerDescription"></div>
    </div>
  </div>

  <?php if ($branding['show_branding_badge'] ?? true): ?>
  <div class="web-only-badge"><?php echo htmlspecialchars($branding['badge_text'] ?? 'Prize Wheel'); ?></div>
  <?php endif; ?>

  <script>
    // Base path for subdirectory support
    window.BASE_PATH = '<?php echo BASE_PATH; ?>';

    // Helper to normalize paths
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
        foreach ($systemSounds as $key => $path) {
          if (strpos($path, '/') === 0 && strpos($path, BASE_PATH) !== 0) {
            $systemSounds[$key] = BASE_PATH . $path;
          }
        }
        echo json_encode($systemSounds);
      ?>,
      volume: <?php echo $config['volume'] ?? 75; ?>,
      modalDelayMs: <?php echo $modal['delay_ms'] ?? $config['modal_delay_ms'] ?? 4500; ?>,
      modalAutoCloseMs: <?php echo $modal['auto_close_ms'] ?? $config['modal_auto_close_ms'] ?? 6000; ?>,
      winnerFlashDurationMs: <?php echo $effects['winner_flash']['duration_ms'] ?? $config['winner_flash_duration_ms'] ?? 4000; ?>,
      spinDurationMs: <?php echo ($config['spin_duration_seconds'] ?? 8) * 1000; ?>,
      cooldownMs: <?php echo ($config['cooldown_seconds'] ?? 3) * 1000; ?>
    };

    // Customization settings
    window.CUSTOMIZATION = <?php echo json_encode($customization); ?>;

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
    const CUSTOMIZATION = window.CUSTOMIZATION;
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
        enabled: <?php echo ($sounds['enabled'] ?? true) ? 'true' : 'false'; ?>,

        init(config) {
            if (!this.enabled) return;

            this.masterVolume = (config.volume || <?php echo $sounds['master_volume'] ?? 75; ?>) / 100;
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
            if (!this.enabled) return;
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
      if (!wheelStatus) return;

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

        <?php if ($statusIndicator['auto_hide_ready'] ?? true): ?>
        setTimeout(function() {
          if (!wheelSpinState.isSpinning && !wheelSpinState.modalVisible && !wheelSpinState.cooldownActive) {
            wheelStatus.classList.remove('visible');
          }
        }, <?php echo $statusIndicator['auto_hide_delay_ms'] ?? 2000; ?>);
        <?php endif; ?>
      }
    }

    async function requestSpin() {
      if (wheelSpinState.isSpinning || wheelSpinState.modalVisible || wheelSpinState.cooldownActive) {
        console.log('Spin blocked - wheel is busy');
        if (wheelStatus) {
          wheelStatus.textContent = 'Please wait...';
          wheelStatus.className = 'wheel-status visible spinning';
          setTimeout(updateWheelStatus, 1000);
        }
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
          if (wheelStatus) {
            wheelStatus.textContent = data.error || 'Please wait';
            wheelStatus.className = 'wheel-status visible spinning';
            setTimeout(updateWheelStatus, 2000);
          }
        }
      } catch (error) {
        console.error('Spin request failed:', error);
        if (wheelStatus) {
          wheelStatus.textContent = 'Error - try again';
          wheelStatus.className = 'wheel-status visible spinning';
          setTimeout(updateWheelStatus, 2000);
        }
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

      if (data.prizes) {
        prizes = data.prizes.map(function(prize) {
          if (prize.sound_path) {
            prize.sound_path = normalizePath(prize.sound_path);
          }
          return prize;
        });
      }

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
      const minSpins = CUSTOMIZATION?.spin?.min_rotations || 10;
      const maxExtra = CUSTOMIZATION?.spin?.max_extra_rotations || 6;
      const spins = minSpins + Math.floor(Math.random() * maxExtra);
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

          <?php if ($effects['winner_flash']['enabled'] ?? true): ?>
          flashWinner(winnerIndex);
          <?php endif; ?>

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

      <?php if ($effects['confetti']['enabled'] ?? true): ?>
      const winnerCount = <?php echo $effects['confetti']['winner_count'] ?? 150; ?>;
      const loserCount = <?php echo $effects['confetti']['loser_count'] ?? 50; ?>;
      spawnConfetti(winner.is_winner ? winnerCount : loserCount, winner.is_winner);
      <?php endif; ?>

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

    <?php if ($wheel['studs']['enabled'] ?? true): ?>
    function createStuds() {
      const container = document.getElementById('wheelStuds');
      if (!container) return;
      container.innerHTML = '';
      const numStuds = <?php echo $wheel['studs']['count'] ?? 32; ?>;
      const radius = 52;

      for (let i = 0; i < numStuds; i++) {
        const angle = (i / numStuds) * 2 * Math.PI;
        const x = 50 + radius * Math.cos(angle - Math.PI/2);
        const y = 50 + radius * Math.sin(angle - Math.PI/2);

        const stud = document.createElement('div');
        stud.className = 'stud';
        stud.style.left = x + '%';
        stud.style.top = y + '%';
        stud.style.animationDelay = (i * 0.1) + 's';
        container.appendChild(stud);
      }
    }
    <?php else: ?>
    function createStuds() {}
    <?php endif; ?>

    function resizeCanvas() {
      const container = wheelCanvas.parentElement;
      const size = container.clientWidth;
      const ratio = Math.min(window.devicePixelRatio || 1, <?php echo $advanced['canvas_pixel_ratio_max'] ?? 2; ?>);

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

        const size = wheelCanvas.width / (Math.min(window.devicePixelRatio || 1, <?php echo $advanced['canvas_pixel_ratio_max'] ?? 2; ?>));
        const num = prizes.length;
        const radius = (size / 2) * 0.94;
        const center = size / 2;
        const segmentAngle = (Math.PI * 2) / num;

        // Customization settings for wheel
        const wheelSettings = CUSTOMIZATION?.wheel || {};
        const segmentSettings = wheelSettings.segments || {};
        const textSettings = wheelSettings.text || {};
        const centerSettings = wheelSettings.center || {};

        wctx.clearRect(0, 0, size, size);
        wctx.save();
        wctx.translate(center, center);
        wctx.rotate(currentRotation);

        for (let i = 0; i < num; i++) {
            const startAngle = i * segmentAngle;
            const endAngle = startAngle + segmentAngle;
            const prize = prizes[i];

            const baseColor = prize.color || '#4CAF50';

            <?php if ($wheel['segments']['gradient_enabled'] ?? true): ?>
            const gradient = wctx.createRadialGradient(0, 0, radius * 0.2, 0, 0, radius);
            gradient.addColorStop(0, lightenColor(baseColor, <?php echo $wheel['segments']['gradient_lighten_center'] ?? 35; ?>));
            gradient.addColorStop(0.4, lightenColor(baseColor, <?php echo $wheel['segments']['gradient_lighten_mid'] ?? 15; ?>));
            gradient.addColorStop(0.8, baseColor);
            gradient.addColorStop(1, darkenColor(baseColor, <?php echo $wheel['segments']['gradient_darken_edge'] ?? 25; ?>));
            <?php else: ?>
            const gradient = baseColor;
            <?php endif; ?>

            wctx.beginPath();
            wctx.moveTo(0, 0);
            wctx.arc(0, 0, radius, startAngle, endAngle);
            wctx.closePath();
            wctx.fillStyle = gradient;
            wctx.fill();

            wctx.strokeStyle = '<?php echo $wheel['segments']['border_color'] ?? 'rgba(0,0,0,0.4)'; ?>';
            wctx.lineWidth = <?php echo $wheel['segments']['border_width'] ?? 3; ?>;
            wctx.stroke();

            wctx.strokeStyle = '<?php echo $wheel['segments']['inner_border_color'] ?? 'rgba(255,255,255,0.15)'; ?>';
            wctx.lineWidth = 1;
            wctx.stroke();

            if (highlight.active && highlight.index === i) {
                wctx.save();
                wctx.beginPath();
                wctx.moveTo(0, 0);
                wctx.arc(0, 0, radius, startAngle, endAngle);
                wctx.closePath();
                wctx.fillStyle = 'rgba(255, 255, 255, ' + (highlight.intensity * <?php echo $effects['winner_flash']['intensity'] ?? 0.5; ?>) + ')';
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

            let fontSize = Math.min(radius / 10, <?php echo $wheel['text']['max_font_size'] ?? 24; ?>);
            let lines = [];

            do {
                wctx.font = '<?php echo $wheel['text']['font_weight'] ?? '900'; ?> ' + fontSize + 'px <?php echo addslashes($wheel['text']['font_family'] ?? "'Cinzel', serif"); ?>';

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
            } while (fontSize > <?php echo $wheel['text']['min_font_size'] ?? 8; ?>);

            const lineHeight = fontSize * 1.2;
            const startY = -(lines.length - 1) * lineHeight / 2;

            wctx.textAlign = 'center';
            wctx.textBaseline = 'middle';

            lines.forEach(function(line, index) {
                const yPos = startY + index * lineHeight;

                wctx.strokeStyle = '<?php echo $wheel['text']['stroke_color'] ?? 'rgba(0,0,0,0.8)'; ?>';
                wctx.lineWidth = Math.max(4, fontSize * <?php echo $wheel['text']['stroke_width_ratio'] ?? 0.125; ?>);
                wctx.strokeText(line, textRadius, yPos);

                wctx.strokeStyle = darkenColor(prize.color || '#4CAF50', 40);
                wctx.lineWidth = Math.max(2, fontSize / 12);
                wctx.strokeText(line, textRadius, yPos);

                <?php if ($wheel['text']['use_gradient'] ?? true): ?>
                const textGradient = wctx.createLinearGradient(0, yPos - fontSize/2, 0, yPos + fontSize/2);
                <?php
                $gradientColors = $wheel['text']['gradient_colors'] ?? ['#FFFFFF', '#F8F8FF', '#E6E6FA'];
                echo "textGradient.addColorStop(0, '{$gradientColors[0]}');";
                echo "textGradient.addColorStop(0.5, '{$gradientColors[1]}');";
                echo "textGradient.addColorStop(1, '{$gradientColors[2]}');";
                ?>
                wctx.fillStyle = textGradient;
                <?php else: ?>
                wctx.fillStyle = '<?php echo $wheel['text']['color'] ?? '#ffffff'; ?>';
                <?php endif; ?>
                wctx.fillText(line, textRadius, yPos);
            });

            wctx.restore();
        }

        // Draw center
        const centerSizeRatio = <?php echo $wheel['center']['size_ratio'] ?? 0.15; ?>;
        const centerGradient = wctx.createRadialGradient(0, 0, 0, 0, 0, radius * (centerSizeRatio + 0.03));
        <?php
        $centerBg = $wheel['center']['background_gradient'] ?? ['#4a4a6e', '#2a2a3e', '#1a1a2e', '#0a0a14'];
        echo "centerGradient.addColorStop(0, '{$centerBg[0]}');";
        echo "centerGradient.addColorStop(0.3, '{$centerBg[1]}');";
        echo "centerGradient.addColorStop(0.7, '{$centerBg[2]}');";
        echo "centerGradient.addColorStop(1, '{$centerBg[3]}');";
        ?>

        wctx.beginPath();
        wctx.arc(0, 0, radius * centerSizeRatio, 0, Math.PI * 2);
        wctx.fillStyle = centerGradient;
        wctx.fill();

        wctx.strokeStyle = '<?php echo $wheel['center']['border_color_outer'] ?? '#FFB300'; ?>';
        wctx.lineWidth = <?php echo $wheel['center']['border_width_outer'] ?? 4; ?>;
        wctx.stroke();

        wctx.strokeStyle = '<?php echo $wheel['center']['border_color_inner'] ?? '#FFD700'; ?>';
        wctx.lineWidth = <?php echo $wheel['center']['border_width_inner'] ?? 2; ?>;
        wctx.stroke();

        wctx.fillStyle = '<?php echo $wheel['center']['icon_color'] ?? '#FFD700'; ?>';
        wctx.font = (radius * <?php echo $wheel['center']['icon_size_ratio'] ?? 0.08; ?>) + 'px serif';
        wctx.textAlign = 'center';
        wctx.textBaseline = 'middle';
        wctx.fillText('<?php echo $wheel['center']['icon'] ?? '\u2654'; ?>', 0, 0);

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

    <?php if ($effects['confetti']['enabled'] ?? true): ?>
    const confetti = { particles: [], active: false };
    const WINNER_COLORS = <?php echo json_encode($effects['confetti']['winner_colors'] ?? ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#9370DB', '#FF6347', '#32CD32']); ?>;
    const LOSER_COLORS = <?php echo json_encode($effects['confetti']['loser_colors'] ?? ['#C0C0C0', '#A8A8A8', '#D3D3D3', '#B8860B']); ?>;
    const CONFETTI_GRAVITY = <?php echo $effects['confetti']['gravity'] ?? 0.12; ?>;
    const CONFETTI_MAX_LIFE = <?php echo $effects['confetti']['particle_max_life'] ?? 400; ?>;

    function spawnConfetti(count, isWinner) {
      isWinner = isWinner !== false;
      const colors = isWinner ? WINNER_COLORS : LOSER_COLORS;
      for (let i = 0; i < count; i++) {
        confetti.particles.push({
          x: Math.random() * window.innerWidth,
          y: -20,
          vx: (Math.random() - 0.5) * 8,
          vy: Math.random() * 4 + 2,
          gravity: CONFETTI_GRAVITY,
          width: Math.random() * 10 + 6,
          height: Math.random() * 14 + 8,
          angle: Math.random() * Math.PI * 2,
          angleVel: (Math.random() - 0.5) * 0.3,
          color: colors[Math.floor(Math.random() * colors.length)],
          life: 0,
          maxLife: CONFETTI_MAX_LIFE,
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
    <?php else: ?>
    function spawnConfetti() {}
    <?php endif; ?>

    <?php if ($effects['winner_flash']['enabled'] ?? true): ?>
    function flashWinner(index) {
      highlight.index = index;
      highlight.active = true;

      const startTime = performance.now();
      const flashFrequency = <?php echo $effects['winner_flash']['frequency'] ?? 0.015; ?>;

      function flash(currentTime) {
        const elapsed = currentTime - startTime;
        highlight.intensity = Math.abs(Math.sin(elapsed * flashFrequency)) * 0.7 + 0.3;
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
    <?php else: ?>
    function flashWinner() {}
    <?php endif; ?>

    function populateWinnerModal(prize) {
      if (!prize) return;

      const isWinner = prize.is_winner !== false;
      winnerCard.classList.toggle('loser', !isWinner);

      crestIcon.innerHTML = isWinner
        ? '<?php echo $modal['winner']['crest_icon'] ?? '&#127942;'; ?>'
        : '<?php echo $modal['loser']['crest_icon'] ?? '&#128737;'; ?>';
      winnerBadge.textContent = isWinner
        ? '<?php echo addslashes($modal['winner']['badge_text'] ?? 'WINNER'); ?>'
        : '<?php echo addslashes($modal['loser']['badge_text'] ?? 'TRY AGAIN'); ?>';
      winnerTitle.textContent = isWinner
        ? '<?php echo addslashes($modal['winner']['title_text'] ?? 'Royal Victory'); ?>'
        : '<?php echo addslashes($modal['loser']['title_text'] ?? 'Noble Effort'); ?>';
      winnerNameEl.textContent = prize.name || 'Mystery Prize';
      winnerDescription.textContent = prize.description ||
        (isWinner ? 'Congratulations! Claim your royal reward!' : 'Fortune favors the persistent. Try again!');
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Prize Wheel (Customizable) Initializing...');

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

      <?php if ($c['accessibility']['keyboard_controls'] ?? true): ?>
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
      <?php endif; ?>

      updateWheelStatus();

      console.log('Prize Wheel initialized with ' + prizes.length + ' prizes');
    });

    <?php if (!empty($advanced['custom_js'])): ?>
    // Custom JavaScript from settings
    <?php echo $advanced['custom_js']; ?>
    <?php endif; ?>
  </script>
</body>
</html>
