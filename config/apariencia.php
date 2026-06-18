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

function getPaletas() {
    return [
        'default' => ['label' => 'Predeterminado (ShopRive)', 'colors' => ['#6c5ce7','#fd79a8','#0a0a1a','#12122a','#1a1a3e','#ffffff','#888888','#00b894','#2a2a4a']],
        'sunset_orange' => ['label' => '1. Sunset Orange', 'colors' => ['#ff6b35','#e85d26','#1a0f0a','#2a1a10','#3a2518','#fff5f0','#c4a89a','#00b894','#4a3528']],
        'teal_breeze' => ['label' => '2. Teal Breeze', 'colors' => ['#00b4d8','#48cae4','#0a1a1f','#0f2a30','#183a42','#ffffff','#8ab4b8','#00b894','#1a4a52']],
        'gray_steel' => ['label' => '3. Gray Steel', 'colors' => ['#4a90d9','#5a9fe6','#0d0d0d','#1a1a1a','#2a2a2a','#f5f5f5','#888888','#3aaf85','#3a3a3a']],
        'brown_earth' => ['label' => '4. Brown Earth', 'colors' => ['#c17817','#d4891a','#1a1410','#2a2018','#3a2c20','#faf3eb','#a09080','#5a9e6f','#4a3c30']],
        'ocean_blue' => ['label' => '5. Ocean Blue', 'colors' => ['#0077b6','#00b4d8','#050a14','#0a1528','#10203c','#f0f8ff','#6a8fa8','#00b894','#1a3050']],
        'pink_blossom' => ['label' => '6. Pink Blossom', 'colors' => ['#ff6b9d','#ff4081','#1a0a12','#2a121e','#3a1a2a','#fff5f8','#b8889a','#00b894','#4a2538']],
        'purple_mist' => ['label' => '7. Purple Mist', 'colors' => ['#b388ff','#7c4dff','#0a0818','#12102a','#1c1840','#f8f5ff','#8a7aaa','#00b894','#282450']],
        'black_flame' => ['label' => '8. Black Flame', 'colors' => ['#e74c3c','#c0392b','#000000','#0a0a0a','#1a1a1a','#f0f0f0','#666666','#2ecc71','#2a2a2a']],
        'navy_mirage' => ['label' => '9. Navy Mirage', 'colors' => ['#1a237e','#283593','#050814','#0a1020','#101830','#e8edf5','#68708a','#00b894','#182040']],
        'golden_leaf' => ['label' => '10. Golden Leaf', 'colors' => ['#f1c40f','#f39c12','#1a1408','#2a2010','#3a2c18','#fef9e7','#a09060','#27ae60','#4a3c20']],
        'rust_autumn' => ['label' => '11. Rust Autumn', 'colors' => ['#d35400','#e67e22','#140e0a','#241a12','#342618','#faf0e8','#9a8070','#00b894','#443228']],
        'ice_sky' => ['label' => '12. Ice Sky', 'colors' => ['#85c1e9','#aed6f1','#0a1218','#101e28','#182a38','#ffffff','#7898a8','#00b894','#203848']],
        'rosewood' => ['label' => '13. Rosewood', 'colors' => ['#8e1b3f','#6b1530','#120a10','#201018','#2e1822','#fdf5f7','#8a707a','#00b894','#3c202c']],
        'emerald_forest' => ['label' => '14. Emerald Forest', 'colors' => ['#2ecc71','#27ae60','#081408','#102410','#183418','#f0faf0','#6a8a6a','#f1c40f','#204420']],
        'sand_dune' => ['label' => '15. Sand Dune', 'colors' => ['#d4a373','#b8885a','#14100c','#221a12','#322418','#faf5f0','#9a8a7a','#00b894','#423428']],
        'lavender_dream' => ['label' => '16. Lavender Dream', 'colors' => ['#c9b1ff','#b392f0','#0e0c18','#1a1430','#261c42','#faf8ff','#8a7aaa','#00b894','#30265a']],
        'copper_glow' => ['label' => '17. Copper Glow', 'colors' => ['#d9734e','#c0623e','#14100e','#221a14','#34241c','#faf3ee','#9a8070','#00b894','#443028']],
        'skyline_gray' => ['label' => '18. Skyline Gray', 'colors' => ['#5a7d9a','#4a6a8a','#080808','#121212','#202020','#e8e8e8','#7a7a7a','#00b894','#2a2a2a']],
        'berry_punch' => ['label' => '19. Berry Punch', 'colors' => ['#e91e63','#c2185b','#140810','#221018','#321822','#fef5f8','#9a6a7a','#00b894','#422030']],
        'mint_fresh' => ['label' => '20. Mint Fresh', 'colors' => ['#1abc9c','#16a085','#081410','#10241c','#183428','#f0faf5','#6a9a8a','#f1c40f','#204430']],
    ];
}
