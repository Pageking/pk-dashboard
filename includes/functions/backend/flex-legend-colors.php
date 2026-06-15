<?php
if (!defined('ABSPATH')) exit;

// Categorie => kleur map. Uitbreidbaar via de 'pk_flex_category_colors' filter.
// Meerdere categorienamen per kleur mogelijk door een array van slugs te gebruiken.
function pk_flex_category_colors() {
    $groups = [
        '#5E66EA' => ['hero'],
        '#39F7B2' => ['sliders', 'swipers', 'carousel'],
        '#10b981' => ['Content', 'tekst', 'text'],
        '#64748b' => ['grid', 'grids', 'raster'],
        '#0ea5e9' => ['kaart', 'kaarten', 'map', 'maps'],
        '#0f0d0c' => ['accordion', 'accordeon', 'uitklap'],
        '#3b82f6' => ['CTA', 'cta', 'call-to-action'],
        '#8b5cf6' => ['Forms', 'Form', 'forms', 'form', 'Formulieren', 'Formulier', 'formulieren', 'formulier'],
        '#ec4899' => ['Vergelijkingen', 'Vergelijking', 'vergelijkingen', 'vergelijking', 'Comparison', 'comparison'],
        '#84cc16' => ['download', 'downloads'],
        '#d946ef' => ['smart-links', 'smart-link', 'slimme-links', 'slimme-link'],
        '#94a3b8' => ['sidebar', 'sidebars', 'zijbalk', 'zijbalken'],
        '#10b982' => ['list', 'lists', 'lijst', 'lijsten'],
        '#fbbf24' => ['logo-banner', 'logo-banners', 'logobalk', 'logo\'s'],
        '#a78bfa' => ['navigation', 'nav', 'navigatie', 'menu'],
        '#22d3ee' => ['search', 'zoeken', 'zoekbalk'],
        '#14b8a6' => ['usp', 'usps'],
        '#ef4444' => ['404', 'niet-gevonden'],
    ];

    $colors = [];
    foreach ($groups as $color => $slugs) {
        foreach ($slugs as $slug) {
            $colors[$slug] = $color;
        }
    }

    return apply_filters('pk_flex_category_colors', $colors);
}

// Geeft de kleur voor een categorie-slug; onbekende slugs krijgen een automatische kleur.
function pk_flex_category_color($slug) {
    $colors = pk_flex_category_colors();
    if (isset($colors[$slug])) return $colors[$slug];

    $hue = hexdec(substr(md5($slug), 0, 2)) / 255 * 360;
    return sprintf('hsl(%d, 55%%, 55%%)', (int) $hue);
}

// Bouwt een map van layout-slug => categorie-slug door alle ACF-veldgroepen te scannen.
function pk_flex_layout_categories() {
    $map = [];
    foreach (acf_get_field_groups() as $group) {
        pk_flex_scan_fields(acf_get_fields($group), $map);
    }
    return apply_filters('pk_flex_layout_categories', $map);
}

// Recursief: doorzoekt velden en sub-velden op flexible_content en slaat categorie per layout op.
function pk_flex_scan_fields(array $fields, array &$map) {
    foreach ($fields as $field) {
        $type = $field['type'] ?? '';

        if ($type === 'flexible_content') {
            foreach ($field['layouts'] as $layout) {
                $name = $layout['name'] ?? '';
                if (!$name) continue;

                $cat = $layout['acfe_flexible_category'] ?? '';
                if (is_array($cat)) $cat = reset($cat);
                if (!$cat) $cat = explode('_', $name)[0]; // fallback: eerste deel van de slug

                $map[$name] = sanitize_title($cat);
            }
        }

        if (!empty($field['sub_fields'])) {
            pk_flex_scan_fields($field['sub_fields'], $map);
        }
    }
}

// Injecteert CSS-attribuutselectoren zodat kleuren direct bij pageload worden toegepast (geen JS-delay).
add_action('admin_head', function () {
    if (!function_exists('acf_get_field_groups')) return;

    $screen = get_current_screen();
    if (!$screen || !in_array($screen->base, ['post', 'post-new'])) return;

    $layout_cats = pk_flex_layout_categories();
    $cat_colors  = pk_flex_category_colors();

    // Voeg auto-kleuren toe voor categorieën die niet in de vaste map staan.
    foreach ($layout_cats as $cat_slug) {
        if (!isset($cat_colors[$cat_slug])) {
            $cat_colors[$cat_slug] = pk_flex_category_color($cat_slug);
        }
    }

    echo "<style>\n";

    foreach ($layout_cats as $layout_slug => $cat_slug) {
        printf(
            ".acf-flexible-content .layout[data-layout=\"%s\"] { --pk-layout-color: %s; }\n",
            esc_attr($layout_slug),
            $cat_colors[$cat_slug]
        );
    }

    // 'i' flag = hoofdletterongevoelig, zodat "Sliders" matcht met sleutel "sliders".
    foreach ($cat_colors as $slug => $color) {
        printf(
            ".acfe-fc-categories .acfe-nav-tab[data-category=\"%s\" i] { --pk-layout-color: %s; }\n",
            esc_attr($slug),
            $color
        );
        // data-category is een JSON-array zoals ["Sliders"]; *= matcht op substring.
        printf(
            ".acfe-fc-layouts a[data-category*='\"" . esc_attr($slug) . "\"' i] { --pk-layout-color: %s; }\n",
            $color
        );
    }

    echo "</style>\n";
});
