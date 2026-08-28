<?php

	$current_user = wp_get_current_user();
	$plugin_url = plugin_dir_url(dirname(__FILE__));

	/* standaard post types */
	$defaultPostTypes = array(
		'post' => 'post',
		'page' => 'page'
	);
	
	$publicPageCount = pk_get_published_count($defaultPostTypes);
	$createdPostTypes = pk_get_created_posts($defaultPostTypes);
	$recentDraft = pk_get_recent_draft($createdPostTypes);
		
	/* Ongelezen inzendingen via de form-summary: drie vaste queries in plaats van
	   een query per formulier, en zonder de standaard paginering van GFAPI::get_entries
	   (page_size 20) die de telling afkapte. */
	$unread_entries_count = 0;
	if (class_exists('GFFormsModel') && method_exists('GFFormsModel', 'get_form_summary')) {
		foreach (GFFormsModel::get_form_summary() as $form_summary) {
			if (!empty($form_summary['is_trash'])) {
				continue;
			}
			$unread_entries_count += (int) ($form_summary['unread_count'] ?? 0);
		}
	}

	$wc_open_orders = 0;
	if (function_exists('wc_orders_count')) {
		$wc_open_orders = (int) wc_orders_count('processing');
	}
	
?>

<div id="pk-widget">
	
	<div class="top">
		<div class="content">
			<h1 class="title">Hi <?= esc_html($current_user->display_name) ?> <icon>&#128075</icon></h1>
			<p class="text">Leuk dat je er bent. Wat gaan we vandaag doen? Een nieuwe pagina maken, 
			content aanscherpen of een nieuwe campagne lanceren?</p>
		</div>
		<div class="pkStripes"></div>
	</div>

	<div class="center">

		<div class="widgets-wrapper">

			<div class="big">
				<?php if ($recentDraft): ?>
					<?php $edit_link = get_edit_post_link($recentDraft['ID']); ?>
					<a href="<?= esc_url($edit_link) ?>" class="concept">
						<span>Ga direct verder met je laatste concept.</span>
						<div class="link">
							<icon></icon>
							<span class="post-title">
								<?= esc_html(!in_array($recentDraft['post_type'], ['page', 'post']) ? ucfirst($recentDraft['post_type']) . ' - ' : '') ?>
								<?= esc_html($recentDraft['post_title']) ?>
							</span>
						</div>
					</a>
				<?php endif; ?>

				<div class="pk-blocks">

					<a target="_blank" href="https://wiki.pageking.nl/docs/intro" class="pk-cta">
						<div class="content">
							<img src="<?= esc_url($plugin_url . 'img/magic.svg') ?>" alt="">
							<span class="block-title">PK Wiki</span>
							<p>Wij hebben een Wiki die je verder op weg helpt.</p>
							<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M1.99031 22L0 20.0097L19.9039 0.105713L21.8943 2.09603L1.99031 22Z" fill="white"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M19.1836 21.1111V0H21.9984V21.1111H19.1836Z" fill="white"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M0.890625 2.81481V0H22.0017V2.81481H0.890625Z" fill="white"/>
							</svg>
						</div>
					</a>

					<a target="_blank" href="https://pageking.nl/contact/" class="pk-cta">
						<div class="content">
							<img src="<?= esc_url($plugin_url . 'img/binocular.svg') ?>" alt="">
							<span class="block-title">Support team</span>
							<p>Hulp nodig? Ons support team staat voor je klaar!</p>
							<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M1.99031 22L0 20.0097L19.9039 0.105713L21.8943 2.09603L1.99031 22Z" fill="white"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M19.1836 21.1111V0H21.9984V21.1111H19.1836Z" fill="white"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M0.890625 2.81481V0H22.0017V2.81481H0.890625Z" fill="white"/>
							</svg>
						</div>
					</a>

				</div>

			</div>


			<div class="small">
				<div class="cockpit">

					<div class="content">
						<span class="block-title">Jouw cockpit</span>
						<div class="data-list">
							
							<?php if ($publicPageCount > 0): ?>
							<div class="link">
								<span class="count"><?= (int) $publicPageCount ?></span>
								<span class="label">Gepubliceerde <?= ($publicPageCount > 1) ? 'berichten' : 'bericht'?></span>
							</div>
							<?php endif; ?>

							<?php if ($unread_entries_count > 0): ?>
							<div class="link">
								<span class="count"><?= (int) $unread_entries_count ?></span>
								<span class="label">Ongelezen formulier-<?= ($unread_entries_count > 1) ? 'inzendingen' : 'inzending'?></span>
							</div>
							<?php endif; ?>

							<?php if ($wc_open_orders > 0): ?>
							<div class="link">
								<span class="count"><?= (int) $wc_open_orders ?></span>
								<span class="label">Open <?= ($wc_open_orders > 1) ? 'bestellingen' : 'bestelling'?></span>
							</div>
							<?php endif; ?>
							
						</div>
					</div>

				</div>
			</div>

		</div>
	</div>

	<div class="bottom">
		<img src="<?= esc_url($plugin_url . 'img/pk-logo.svg') ?>" alt="Logo" class="pk-logo">
		<span>Full Swing Digital Agency</span>
	</div>
</div>