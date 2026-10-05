import 'bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const body = document.body;
const sidebarToggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
const sidebarCollapse = document.querySelector('[data-sidebar-collapse]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');
const themeToggle = document.querySelector('[data-theme-toggle]');
const themeIcon = document.querySelector('[data-theme-icon]');

const storedCollapsed = localStorage.getItem('edtech360-sidebar-collapsed') === 'true';
if (storedCollapsed && window.innerWidth >= 992) body.classList.add('sidebar-collapsed');

sidebarToggleButtons.forEach((button) => {
    button.addEventListener('click', () => body.classList.toggle('sidebar-open'));
});

backdrop?.addEventListener('click', () => body.classList.remove('sidebar-open'));

sidebarCollapse?.addEventListener('click', () => {
    body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('edtech360-sidebar-collapsed', String(body.classList.contains('sidebar-collapsed')));
});

const preferredTheme = localStorage.getItem('edtech360-theme')
    ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem('edtech360-theme', theme);
    if (themeIcon) themeIcon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
}

applyTheme(preferredTheme);

themeToggle?.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-bs-theme');
    applyTheme(current === 'dark' ? 'light' : 'dark');
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) body.classList.remove('sidebar-open');
});
