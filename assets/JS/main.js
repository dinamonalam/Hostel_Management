function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar) sidebar.classList.toggle('open');
}

document.addEventListener('click', (event) => {
  const sidebar = document.getElementById('sidebar');
  const menuButton = document.querySelector('.topbar-menu');

  if (!sidebar || !menuButton || window.innerWidth > 900) return;
  if (sidebar.contains(event.target) || menuButton.contains(event.target)) return;

  sidebar.classList.remove('open');
});
