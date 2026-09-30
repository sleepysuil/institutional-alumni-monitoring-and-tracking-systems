/* ========== USAT Alumni System - Main JS ========== */
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mobile Sidebar Toggle ---------- */
    const toggleBtn = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
            // Change icon
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('show')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close sidebar when overlay clicked
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
            const icon = toggleBtn.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        });

        // Close sidebar when a nav link is clicked (on mobile)
        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth < 768) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('active');
                    const icon = toggleBtn.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });
    }

    /* ---------- Auto-hide alerts after 5 seconds ---------- */
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);

    /* ---------- Responsive table: add scroll hint on mobile ---------- */
    if (window.innerWidth < 768) {
        document.querySelectorAll('.table-responsive').forEach(function (el) {
            el.style.overflowX = 'auto';
            el.style.webkitOverflowScrolling = 'touch';
        });
    }
});