function applyTheme(theme) {
    if (theme === 'system') {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
    localStorage.setItem('datachat_theme', theme);
}

// Load theme on start
let currentTheme = localStorage.getItem('datachat_theme') || 'system';
applyTheme(currentTheme);

// Listen for system theme changes if set to system
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (localStorage.getItem('datachat_theme') === 'system') {
        applyTheme('system');
    }
});

function setTheme(theme) {
    applyTheme(theme);
}
