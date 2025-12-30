document.addEventListener('DOMContentLoaded', function() {
    // Ensure the welcome panel is visible
    var welcomePanel = document.getElementById('welcome-panel');
    if (welcomePanel) {
        welcomePanel.classList.remove('hidden');
    }
    [
        '.welcome-panel-content', // "welkom tekst" //
        '#wpfooter', // standaard panel footer //
        '#dashboard-widgets-wrap', // de boxes waar je widgets in kan plaatsen //
        // '#screen-meta', 
        '#screen-meta-links', // schermopties //
        '#contextual-help-link-wrap',
        '.welcome-panel-close', 
        '.notice-warning',
        '.wrap h1',
    ].forEach(function(selector) {
        var element = document.querySelector(selector);
        if (element) {
            element.remove();
        }
    });
    
    const welcomeLabel = document.querySelector('label[for="wp_welcome_panel-hide"]');
    if (welcomeLabel) {
        const checkbox = welcomeLabel.querySelector('input');
        welcomeLabel.innerHTML = '';
        welcomeLabel.appendChild(checkbox);
        welcomeLabel.appendChild(document.createTextNode('PK Dashboard'));
    }

    // Ensure your custom welcome panel content is visible
    var customWelcomeContent = document.querySelector('.custom-welcome-panel-content');
    if (customWelcomeContent) {
        customWelcomeContent.style.display = 'block';
    }
    
    // panel forceren om aan te zetten.
    document.getElementById("welcome-panel").classList.remove("hidden");
    document.getElementById("welcome-panel-toggle").checked = true;
});