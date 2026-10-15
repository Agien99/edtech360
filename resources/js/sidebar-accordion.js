// Persist category expansion across navigation, independently of mobile/desktop markup.
const storageKey = 'edtech360-open-nav-groups';
const groups = [...document.querySelectorAll('.ed-nav-group[data-nav-group]')];
let preference = null;
try { const value = JSON.parse(localStorage.getItem(storageKey)); if (Array.isArray(value)) preference = value; } catch (_) {}
const activeKeys = new Set(groups.filter(group => group.querySelector('.ed-nav-subitem.active')).map(group => group.dataset.navGroup));
for (const group of groups) {
  const key = group.dataset.navGroup;
  // An active route must always be visible, regardless of previous preference.
  group.open = activeKeys.has(key) || (preference ? preference.includes(key) : group.open);
  group.addEventListener('toggle', () => {
    // Persist only after a user has interacted (not from initial DOM initialization).
  });
  group.querySelector('summary')?.addEventListener('click', () => {
    const shell = document.querySelector('.ed-shell');
    if (shell?.classList.contains('sidebar-collapsed') && group.closest('.ed-sidebar')) {
      shell.classList.remove('sidebar-collapsed');
      localStorage.setItem('edtech360-sidebar-collapsed', 'false');
    }
    // `click` runs before <details> changes its open property.
    const nextOpen = !group.open;
    const next = new Set(preference ?? groups.filter(g => g.open).map(g => g.dataset.navGroup));
    if (nextOpen) next.add(key); else next.delete(key);
    preference = [...next];
    try { localStorage.setItem(storageKey, JSON.stringify(preference)); } catch (_) {}
    // Keep the two renditions of the sidebar synchronized.
    queueMicrotask(() => {
      for (const other of groups) if (other !== group && other.dataset.navGroup === key) other.open = nextOpen;
    });
  });
}
