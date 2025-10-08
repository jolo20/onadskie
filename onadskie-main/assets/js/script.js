document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Set active navigation based on current URL
  const currentPath = window.location.pathname;
  // Remove trailing slash for comparison
  const normalizedPath = currentPath.endsWith('/') && currentPath.length > 1 
    ? currentPath.slice(0, -1) 
    : currentPath;

  // Find active link by checking both exact match and normalized path
  let activeLink = document.querySelector(`.nav-link[href="${currentPath}"]`) ||
                   document.querySelector(`.nav-link[href="${normalizedPath}"]`);

  // Manual override for 'draftmeasurestask.php' to always activate the 'Incoming Task' tab
  if (currentPath.includes('/draftmeasurestask.php')) {
    activeLink = document.querySelector('.nav-link[href*="?t=Incoming%20Task"]');
  }

  if (activeLink) {
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    activeLink.classList.add('active');
    
    // Open the parent nav group
    const group = activeLink.closest('.nav-group');
    if (group) group.classList.add('open');
  }

  // Mobile sidebar functionality
  const burger = document.getElementById('burger');
  const backdrop = document.getElementById('backdrop');
  const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;

  if (burger) burger.addEventListener('click', () => {
    body.classList.toggle('mobile-open');
  });
  if (backdrop) backdrop.addEventListener('click', () => body.classList.remove('mobile-open'));

  // Keep sidebar state on reload
  const groups = document.querySelectorAll('.nav-group');
  const storageKeyGroup = 'openGroups';
  const openGroups = new Set(JSON.parse(localStorage.getItem(storageKeyGroup) || '[]'));
  
  // Keep Dashboard always open & exclude it from being closed
  const dashboardHref = 'dashboard.php';
  const dashboardGroup = Array.from(groups).find(g =>
    !!g.querySelector(`.sublist a[href="${dashboardHref}"]`)
  );

  if (dashboardGroup) {
    const dashIdx = Array.from(groups).indexOf(dashboardGroup);
    dashboardGroup.classList.add('open');
    openGroups.add(`g${dashIdx}`);
    localStorage.setItem(storageKeyGroup, JSON.stringify([...openGroups]));
  }

  groups.forEach((g, idx) => {
    const btn = g.querySelector('.group-toggle');
    const key = `g${idx}`;
    if (openGroups.has(key)) g.classList.add('open');

    btn.addEventListener('click', () => {
      if (g === dashboardGroup) return;

      groups.forEach((otherG, otherIdx) => {
        if (otherG !== g && otherG !== dashboardGroup) {
          otherG.classList.remove('open');
          openGroups.delete(`g${otherIdx}`);
        }
      });

      g.classList.toggle('open');
      if (g.classList.contains('open')) {
        openGroups.add(key);
      } else {
        openGroups.delete(key);
      }
      localStorage.setItem(storageKeyGroup, JSON.stringify([...openGroups]));
    });
  });

  // Handle dropdown menus
  const profileDropdown = document.querySelector('.header .dropdown');
  if (profileDropdown) {
    profileDropdown.addEventListener('click', (e) => {
      profileDropdown.classList.toggle('open');
      e.stopPropagation();
    });
    document.addEventListener('click', () => {
      profileDropdown.classList.remove('open');
    });
  }

    // Logout functionality
  const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
  if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener('click', () => {
      window.location.href = '/lgu-2-main-main/logout.php';
    });
  }

});