 <?php
    if (isset($_POST['clear_cache'])) {
    $message = '';
    
    if (class_exists('Breeze_Admin')) {
        do_action('breeze_clear_all_cache');
        $message .= 'Breeze cache cleared. ';
    }
    
    if (function_exists('sg_cachepress_purge_cache')) {
        $sg_result = sg_cachepress_purge_cache();
        $message .= 'SiteGround cache cleared. ';
    }
    
    // Trigger the cooldown state after page reload
    if (!empty($message)) {
        echo '<script>
            sessionStorage.setItem("disableCacheBtnTimestamp", new Date().getTime().toString());
            console.log("Cache clearing result: ' . addslashes($message) . '");
        </script>';
    }
    }
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cacheBtn = document.getElementById('pk-cache-button');
        const form = document.getElementById('pk-cache-form');
        
        if (!cacheBtn || !form) return;
        
        const COOLDOWN_PERIOD = 30 * 1000; // 30 seconds
        const TEXT_CHANGE_DELAY = 3 * 1000; // 3 seconds
        const STORAGE_KEY = 'disableCacheBtnTimestamp';
        
        // Check for existing cooldown
        checkCooldownStatus();
        
        // Handle form submission
        form.addEventListener('submit', () => disableButton());
        
        function checkCooldownStatus() {
            const timestamp = sessionStorage.getItem(STORAGE_KEY);
            if (!timestamp) return;
            
            const elapsed = new Date().getTime() - parseInt(timestamp);
            if (elapsed >= COOLDOWN_PERIOD) return;
            
            // Apply cooldown state
            const remaining = COOLDOWN_PERIOD - elapsed;
            const percentComplete = 100 - ((remaining / COOLDOWN_PERIOD) * 100);
            
            // Disable button
            cacheBtn.setAttribute('disabled', '');
            
            // Update button text based on elapsed time
            if (elapsed < TEXT_CHANGE_DELAY) {
                cacheBtn.value = 'Caching legen';
                setTimeout(() => cacheBtn.value = 'Leeg caching', TEXT_CHANGE_DELAY - elapsed);
            } else {
                cacheBtn.value = 'Leeg caching';
            }
            
            // Update CSS variables for visual feedback
            document.documentElement.style.setProperty('--coolDownTime', (remaining/1000) + 's');
            document.documentElement.style.setProperty('--coolDownWidth', percentComplete + '%');
            
            // Schedule re-enable
            setTimeout(enableButton, remaining);
        }
        
        function disableButton() {
            cacheBtn.setAttribute('disabled', '');
            cacheBtn.value = 'Caching legen';
            sessionStorage.setItem(STORAGE_KEY, new Date().getTime().toString());
            
            setTimeout(() => cacheBtn.value = 'Leeg caching', TEXT_CHANGE_DELAY);
            setTimeout(enableButton, COOLDOWN_PERIOD);
        }
        
        function enableButton() {
            cacheBtn.removeAttribute('disabled');
            cacheBtn.value = 'Leeg caching';
            sessionStorage.removeItem(STORAGE_KEY);
            
            // Reset CSS 
            document.documentElement.style.setProperty('--coolDownTime', COOLDOWN_PERIOD + 's');
            document.documentElement.style.setProperty('--coolDownWidth', '0%');
        }
    });
</script>