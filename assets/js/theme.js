(function () {
  const THEME_KEY = 'texsico_theme_mode';
  const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function getMode() {
    const saved = localStorage.getItem(THEME_KEY);
    return saved === 'light' || saved === 'dark' || saved === 'auto' ? saved : 'auto';
  }

  function resolveTheme(mode) {
    if (mode === 'light' || mode === 'dark') return mode;
    return media && media.matches ? 'dark' : 'light';
  }

  function labelForMode(mode) {
    return mode === 'auto' ? 'Auto theme enabled.' : (mode === 'light' ? 'Light mode enabled.' : 'Dark mode enabled.');
  }

  function updateIcon(mode, theme) {
    const btn = document.getElementById('themeToggleBtn');
    const icon = btn ? btn.querySelector('i') : null;
    if (!btn || !icon) return;
    btn.dataset.mode = mode;
    btn.setAttribute('aria-label', 'Theme: ' + mode + '. Tap to switch.');
    btn.title = mode === 'auto' ? 'Theme: Auto' : ('Theme: ' + (theme === 'dark' ? 'Dark' : 'Light'));
    if (mode === 'auto') {
      icon.className = 'fa-solid fa-circle-half-stroke';
    } else if (theme === 'light') {
      icon.className = 'fa-solid fa-sun';
    } else {
      icon.className = 'fa-solid fa-moon';
    }
  }

  function applyTheme(mode, announce) {
    const theme = resolveTheme(mode);
    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.themeMode = mode;
    updateIcon(mode, theme);
    if (announce && typeof window.showToast === 'function') {
      window.showToast(labelForMode(mode), 'success');
    }
  }

  window.toggleThemeMode = function toggleThemeMode() {
    const current = getMode();
    const next = current === 'auto' ? 'light' : (current === 'light' ? 'dark' : 'auto');
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next, true);
  };

  window.applySavedThemeMode = function applySavedThemeMode() {
    applyTheme(getMode(), false);
  };

  window.forceThemeMode = function forceThemeMode(mode) {
    if (!mode) return;
    localStorage.setItem(THEME_KEY, mode);
    applyTheme(mode, true);
  };

  if (media) {
    const listener = function () {
      if (getMode() === 'auto') {
        applyTheme('auto', false);
      }
    };
    if (typeof media.addEventListener === 'function') {
      media.addEventListener('change', listener);
    } else if (typeof media.addListener === 'function') {
      media.addListener(listener);
    }
  }

  applyTheme(getMode(), false);
})();
