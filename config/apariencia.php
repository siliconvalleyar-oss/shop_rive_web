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
      }
      h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-heading);
      }
    </style>
    <?php
}
