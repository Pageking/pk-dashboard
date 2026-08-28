<?php
if (!defined('ABSPATH')) exit;

// Guard: een tweede kopie van de plugin mag deze functies niet opnieuw declareren.
// Het blok is nodig: PHP hoist top-level functiedeclaraties, dus een vroege return
// zou de declaraties niet overslaan maar wel de add_action onderaan.
if (!function_exists('pk_flex_category_colors')) {

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
			'#3b82f6' => ['CTA', 'call-to-action'],
			'#8b5cf6' => ['Forms', 'Form', 'Formulieren', 'Formulier'],
			'#ec4899' => ['Vergelijkingen', 'Vergelijking', 'Comparison'],
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
				$colors[sanitize_title($slug)] = $color; // voeg altijd de gesanitizede variant toe
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
	// Dat kost bijna een seconde met koude ACF-caches, dus het resultaat gaat in een transient.
	function pk_flex_layout_categories() {
		// Zonder ACF (bijvoorbeeld een Beaver Builder-site) bestaan deze functies niet.
		if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
			return apply_filters('pk_flex_layout_categories', []);
		}

		$groups = acf_get_field_groups();

		// Signature over de groep-keys en hun modified-stempel. Let op: veldgroepen die via
		// PHP zijn geregistreerd (FieldsBuilder) hebben geen modified-stempel, dus een
		// categoriewijziging in code invalideert de cache niet vanzelf. Thema's kunnen via
		// onderstaande filter een eigen vingerafdruk toevoegen (bijvoorbeeld een filemtime),
		// anders vervalt de cache na een dag of via pk_flex_flush_layout_categories().
		$signature = [PK_DASHBOARD_VERSION];
		foreach ($groups as $group) {
			$signature[] = ($group['key'] ?? '') . ':' . ($group['modified'] ?? 0);
		}
		$signature = md5(implode('|', apply_filters('pk_flex_layout_categories_fingerprint', $signature)));

		$cached = get_transient('pk_flex_layout_categories');
		if (is_array($cached) && isset($cached['signature'], $cached['map'])
			&& $cached['signature'] === $signature && is_array($cached['map'])) {
			return apply_filters('pk_flex_layout_categories', $cached['map']);
		}

		$map = [];
		foreach ($groups as $group) {
			$fields = acf_get_fields($group);
			if (is_array($fields)) {
				pk_flex_scan_fields($fields, $map);
			}
		}

		set_transient(
			'pk_flex_layout_categories',
			['signature' => $signature, 'map' => $map],
			DAY_IN_SECONDS
		);

		return apply_filters('pk_flex_layout_categories', $map);
	}

	function pk_flex_flush_layout_categories() {
		delete_transient('pk_flex_layout_categories');
	}

	add_action('acf/update_field_group', 'pk_flex_flush_layout_categories');
	add_action('acf/delete_field_group', 'pk_flex_flush_layout_categories');
	add_action('switch_theme', 'pk_flex_flush_layout_categories');
	add_action('upgrader_process_complete', 'pk_flex_flush_layout_categories');

	// Recursief: doorzoekt velden en sub-velden op flexible_content en slaat categorie per layout op.
	function pk_flex_scan_fields(array $fields, array &$map) {
		foreach ($fields as $field) {
			$type = $field['type'] ?? '';

			if ($type === 'flexible_content' && !empty($field['layouts']) && is_array($field['layouts'])) {
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
	// Geen screen-check: dit print alleen attribuutselectoren die vanzelf niks
	// matchen zonder .acf-flexible-content, dus onschadelijk op elk scherm —
	// nodig omdat ACF-optiepagina's (zoals de archief-instellingen) geen
	// 'post'/'post-new' screen->base hebben en anders werden overgeslagen.
	add_action('admin_head', function () {
		if (!function_exists('acf_get_field_groups')) return;

		$layout_cats = pk_flex_layout_categories();
		$cat_colors  = pk_flex_category_colors();

		// Voeg auto-kleuren toe voor categorieën die niet in de vaste map staan. Zo komt een categorie nooit zonder kleur te staan.
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
				".acfe-fc-layouts a[data-category*='\"%s\"' i] { --pk-layout-color: %s; }\n",
				esc_attr($slug),
				$color
			);
		}

		echo "</style>\n";
	});

}
