// Dark Mode Management
document.addEventListener('DOMContentLoaded', function() {
    // Ensure the DOM is fully loaded before initializing dark mode
    initializeDarkMode();
});

function initializeDarkMode() {
    // Apply theme on page load
    applyTheme();
    
    // Add toggle event listener
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    } else {
        // Log to console if the element is not found (for debugging purposes)
        console.error('Theme toggle button not found!');
    }
}

function toggleTheme() {
    const html = document.documentElement;
    const isDarkMode = !html.classList.contains('dark-mode');
    
    // Toggle class
    html.classList.toggle('dark-mode');
    
    // Save preference
    localStorage.setItem('darkMode', isDarkMode);
    
    // Update icon
    updateThemeIcon();
    
    // Dispatch event for other components
    document.dispatchEvent(new CustomEvent('themeChanged', { 
        detail: { isDarkMode } 
    }));
}

function applyTheme() {
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    const html = document.documentElement;
    
    if (isDarkMode) {
        html.classList.add('dark-mode');
    } else {
        html.classList.remove('dark-mode');
    }
    
    updateThemeIcon();
}

function updateThemeIcon() {
    const icon = document.getElementById('theme-icon');
    const isDarkMode = document.documentElement.classList.contains('dark-mode');
    
    if (icon) {
        icon.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
    }
}

// Handle system preference changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (!localStorage.getItem('darkMode')) {
        applyTheme();
    }
});