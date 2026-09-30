// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    // ===== Mobile sidebar toggle =====
    const sidebar  = document.querySelector('.sidebar');
    const toggle   = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !toggle || !backdrop) return;

    const icon = toggle.querySelector('i');

    function setOpen(open) {
        sidebar.classList.toggle('show', open);
        backdrop.classList.toggle('show', open);
        document.body.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (icon) {
            icon.classList.toggle('fa-bars', !open);
            icon.classList.toggle('fa-times', open);
        }
    }

    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('show')));
    backdrop.addEventListener('click', () => setOpen(false));
    sidebar.querySelectorAll('.nav-link').forEach(link =>
        link.addEventListener('click', () => setOpen(false))
    );
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
    window.addEventListener('resize', () => { if (window.innerWidth > 991) setOpen(false); });
});