<?php
/**
 * Apariencia - Theme configuration helper
 * Reads color/font settings from data/configuracion.json and outputs CSS variables.
 * Include this file in any page that should respect the theme.
 */

function getThemeConfig() {
    $configFile = __DIR__ . '/../data/configuracion.json';
    $config = file_exists($configFile) ? (json_decode(file_get_contents($configFile), true) ?: []) : [];

    $defaults = [
        'brand_name' => 'ShopRive',
        'brand_logo' => '',
        'brand_icon' => '',
        'brand_bg' => '',
        'color_primary' => '#6c5ce7',
        'color_accent' => '#fd79a8',
        'color_bg' => '#0a0a1a',
        'color_bg_card' => '#12122a',
        'color_bg_card_hover' => '#1a1a3e',
        'color_text' => '#ffffff',
        'color_text_muted' => '#888888',
        'color_success' => '#00b894',
        'color_border' => '#2a2a4a',
        'font_family' => "'Segoe UI', system-ui, -apple-system, sans-serif",
        'font_heading' => "'Segoe UI', system-ui, -apple-system, sans-serif",
        'font_size_base' => '16px',
    ];

    foreach ($defaults as $k => $v) {
        if (!isset($config[$k]) || trim($config[$k]) === '') {
            $config[$k] = $v;
        }
    }
    return $config;
}

function brandLogoUrl() {
    $c = getThemeConfig();
    return !empty($c['brand_logo']) ? 'assets/uploads/' . $c['brand_logo'] : '';
}

function brandIconUrl() {
    $c = getThemeConfig();
    return !empty($c['brand_icon']) ? 'assets/uploads/' . $c['brand_icon'] : '';
}

function brandBgUrl() {
    $c = getThemeConfig();
    return !empty($c['brand_bg']) ? 'assets/uploads/' . $c['brand_bg'] : '';
}

function brandName() {
    $c = getThemeConfig();
    return $c['brand_name'];
}

function renderFavicon() {
    $icon = brandIconUrl();
    if ($icon): ?>
        <link rel="icon" href="<?= $icon ?>" type="image/png">
    <?php endif;
}

function renderThemeStyles() {
    $c = getThemeConfig();
    ?>
    <style id="theme-styles">
      :root {
        --primary: <?= $c['color_primary'] ?>;
        --accent: <?= $c['color_accent'] ?>;
        --bg: <?= $c['color_bg'] ?>;
        --bg-card: <?= $c['color_bg_card'] ?>;
        --bg-card-hover: <?= $c['color_bg_card_hover'] ?>;
        --text: <?= $c['color_text'] ?>;
        --text-muted: <?= $c['color_text_muted'] ?>;
        --success: <?= $c['color_success'] ?>;
        --border: <?= $c['color_border'] ?>;
        --font-family: <?= $c['font_family'] ?>;
        --font-heading: <?= $c['font_heading'] ?>;
        --font-size-base: <?= $c['font_size_base'] ?>;
      }
      body {
        font-family: var(--font-family);
        font-size: var(--font-size-base);
        <?php if (!empty($c['brand_bg'])): ?>
        background: <?= $c['color_bg'] ?> url('assets/uploads/<?= $c['brand_bg'] ?>') center/cover fixed no-repeat;
        background-attachment: fixed;
        <?php endif; ?>
      }
      <?php if (!empty($c['brand_bg'])): ?>
      .header, .footer, .chat-window, .payment-modal, .toast, .admin-layout {
        background: rgba(<?= hexToRgb($c['color_bg_card']) ?>, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
      }
      <?php endif; ?>
      h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-heading);
      }
    </style>
    <?php
}

function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
}
