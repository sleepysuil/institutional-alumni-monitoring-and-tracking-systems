<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <!-- Mobile layout helpers. Placed BEFORE main.js on purpose: this script owns the menu toggle, so
         any older toggle code in main.js is bypassed instead of fighting with this one (double toggling). -->
    <script>
    (function () {
        var sidebar = document.getElementById('mainSidebar') || document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var toggle  = document.getElementById('mobileToggle');
        var DESKTOP = 992;

        // ---- Off-canvas menu ----
        if (!sidebar) {
            // page has no sidebar (e.g. a public page): hide the useless top bar
            var bar = document.querySelector('.mobile-topbar');
            if (bar) bar.style.display = 'none';
        } else {
            var setOpen = function (open) {
                sidebar.classList.toggle('show', open);
                if (overlay) overlay.classList.toggle('active', open);
                document.body.classList.toggle('sidebar-open', open);
                if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            // capture phase + stopImmediatePropagation: deterministic even if main.js also binds these elements
            document.addEventListener('click', function (e) {
                if (e.target.closest('#mobileToggle')) {
                    e.preventDefault(); e.stopImmediatePropagation();
                    setOpen(!sidebar.classList.contains('show'));
                } else if (e.target.closest('#sidebarOverlay')) {
                    e.stopImmediatePropagation();
                    setOpen(false);
                } else if (e.target.closest('.sidebar .nav-link')) {
                    setOpen(false);
                }
            }, true);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
            window.addEventListener('resize', function () { if (window.innerWidth >= DESKTOP) setOpen(false); });
        }

        // ---- Tables ----
        // 1) Tables with 5+ columns become stacked cards on small phones (labels come from the header row).
        // 2) The "swipe" hint only appears on tables that really overflow.
        function prepareTables() {
            document.querySelectorAll('.table-responsive table.table').forEach(function (t) {
                if (t.dataset.mobileReady) return;
                t.dataset.mobileReady = '1';
                var heads = t.querySelectorAll('thead th');
                var selectable = t.querySelector('thead input[type="checkbox"]');   // select-all tables keep their table layout
                if (heads.length >= 5 && !selectable && !t.classList.contains('no-stack')) {
                    var labels = Array.prototype.map.call(heads, function (th) { return th.textContent.trim(); });
                    t.querySelectorAll('tbody tr').forEach(function (tr) {
                        Array.prototype.forEach.call(tr.children, function (td, i) {
                            if (td.tagName === 'TD' && !td.hasAttribute('colspan')) td.setAttribute('data-label', labels[i] || '');
                        });
                    });
                    t.classList.add('table-stack');
                }
            });
        }
        function updateScrollHints() {
            document.querySelectorAll('.table-responsive').forEach(function (w) {
                var overflowing = w.scrollWidth > w.clientWidth + 2;
                w.classList.toggle('has-scroll', overflowing);
            });
        }
        function initTables() { prepareTables(); updateScrollHints(); }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initTables); else initTables();
        window.addEventListener('load', updateScrollHints);
        window.addEventListener('resize', updateScrollHints);
    })();
    </script>

    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/charts.js"></script>
</body>
</html>