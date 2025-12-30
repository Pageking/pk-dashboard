<?php

    $current_user = wp_get_current_user();
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    /* check of er caching is. zo ja dan true */
    // $show_caching = is_plugin_active('sg-cachepress/sg-cachepress.php') || is_plugin_active('breeze/breeze.php');
    $show_caching = false;

    /* standaard post types */
    $defaultPostTypes = array(
        'post' => 'post',
        'page' => 'page'
    );
    
    $publicPageCount = get_published_count($defaultPostTypes);
    $createdPostTypes = get_created_posts($defaultPostTypes);
    $recentDraft = get_recent_draft($createdPostTypes);
        
    $unread_entries_count = 0;
    if (class_exists('GFAPI')) {
        $forms = GFAPI::get_forms();
        foreach ($forms as $form) {
            $search_criteria = array('status' => 'active');
            $entries = GFAPI::get_entries($form['id'], $search_criteria);
            foreach ($entries as $entry) {
                if (!isset($entry['is_read']) || $entry['is_read'] === '0' || $entry['is_read'] === 0 || $entry['is_read'] === '') {
                    $unread_entries_count++;
                }
            }
        }
    }
        
    // Get WooCommerce orders with data
    $orders_data = array();
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => 'processing',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    
        foreach ($orders as $order) {
            $orders_data[] = array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s')
            );
        }
    }
        
    $wc_open_orders = 0;
    if (class_exists('WooCommerce')) {
        $wc_open_orders = wc_orders_count('processing');
    }
    
?>

<div id="pk-widget">
    
    <div class="top">
        <div class="content">
            <h1 class="title">Hi <?= $current_user->display_name?> <icon>&#128075</icon></h1>
            <p class="text">Leuk dat je er bent. Wat gaan we vandaag doen? Een nieuwe pagina maken, 
            content aanscherpen of een nieuwe campagne lanceren?</p>
        </div>
        <div class="pkStripes">
            <?php if ($show_caching): ?>
            <div class="caching">
                <span>Laatste wijzigingen niet zichtbaar? Klik hier.
                    <!-- <?= $plugin_url ?>functions/clearCache.php -->
                </span>
                <!-- <a href="#" id="clear-cache">Leeg caching</a> -->
                <form method="post" id="pk-cache-form">
                    <div class="cache-button-container">
                        <input type="submit" id="pk-cache-button" name="clear_cache" value="Leeg caching">
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="center">

        <div class="widgets-wrapper">

            <div class="big">
                <?php if ($recentDraft): ?>
                    <?php $edit_link = get_edit_post_link($recentDraft['ID']); ?>
                    <a href="<?= $edit_link ?>" class="concept">
                        <span>Ga direct verder met je laatste concept.</span>
                        <div class="link">
                            <icon></icon>
                            <span class="post-title">
                                <?= (!in_array($recentDraft['post_type'], ['page', 'post']) ? ucfirst($recentDraft['post_type']) . ' - ' : '') ?>
                                <?= $recentDraft['post_title'] ?>
                            </span>
                        </div>
                    </a>
                <?php endif; ?>

                <div class="pk-blocks">

                    <a target="_blank" href="https://wiki.pageking.nl/docs/intro" class="pk-cta">
                        <div class="content">
                            <img src="<?= $plugin_url ?>img/magic.svg">
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
                            <img src="<?= $plugin_url ?>img/binocular.svg">
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
                            
                            <?php if ($publicPageCount && $publicPageCount > 1): ?>
                            <div class="link">
                                <span class="count"><?= $publicPageCount ?></span>
                                <span class="label">Gepubliceerde <?= ($publicPageCount > 1) ? 'berichten' : 'bericht'?></span> 
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($unread_entries_count && $unread_entries_count > 0): ?>
                            <div class="link">
                                <span class="count"><?= $unread_entries_count ?></span>
                                <span class="label">Ongelezen formulier-<?= ($unread_entries_count > 1) ? 'inzendingen' : 'inzending'?></span> 
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($wc_open_orders && $wc_open_orders > 0): ?>
                            <div class="link">
                                <span class="count"><?= $wc_open_orders ?></span>
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
        <img src="<?= $plugin_url ?>img/pk-logo.svg" alt="Logo" class="pk-logo">
        <span>Full Swing Digital Agency</span>
    </div>
</div>