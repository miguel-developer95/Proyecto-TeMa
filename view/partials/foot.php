<?php
// view/partials/foot.php
?>
    <footer class="app-footer">
        &copy; <?= date('Y') ?> Tentaciones Marlly — Mini Tienda de Consumo Diario.
    </footer>
    </main>
</div>
    <!-- Script para cerrar sesión al retroceder -->
    <script>
        window.addEventListener("pageshow", function(event) {
            var historyTraversal = event.persisted ||
                (typeof window.performance != "undefined" && window.performance.navigation.type === 2);
            if (historyTraversal) {
                window.location.replace("/Proyecto-TeMa/index.php?action=logout");
            }
        });
    </script>
    <!-- Script para control del menú lateral responsive y ocultable (Desktop & Mobile) -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var toggle = document.getElementById("sidebarToggle");
            var sidebar = document.getElementById("appSidebar");
            var overlay = document.getElementById("sidebarOverlay");
            var closeBtn = document.getElementById("sidebarCloseBtn");
            var collapseBtn = document.getElementById("sidebarCollapseBtn");
            var layout = document.querySelector(".layout");

            // Sincronizar estado en escritorio
            if (window.innerWidth >= 992) {
                if (localStorage.getItem("sidebar_collapsed") === "true") {
                    if (layout) layout.classList.add("sidebar-collapsed");
                    document.documentElement.classList.add("sidebar-is-collapsed");
                }
            }

            function notifyResize() {
                setTimeout(function() {
                    window.dispatchEvent(new Event("resize"));
                    window.dispatchEvent(new Event("sidebarToggle"));
                }, 310);
            }

            function toggleSidebarDesktop() {
                if (!layout) return;
                var isCollapsed = layout.classList.toggle("sidebar-collapsed");
                if (isCollapsed) {
                    document.documentElement.classList.add("sidebar-is-collapsed");
                    localStorage.setItem("sidebar_collapsed", "true");
                } else {
                    document.documentElement.classList.remove("sidebar-is-collapsed");
                    localStorage.setItem("sidebar_collapsed", "false");
                }
                notifyResize();
            }

            function openSidebarMobile() {
                if (sidebar) sidebar.classList.add("active");
                if (overlay) overlay.classList.add("active");
                document.body.style.overflow = "hidden";
            }

            function closeSidebarMobile() {
                if (sidebar) sidebar.classList.remove("active");
                if (overlay) overlay.classList.remove("active");
                document.body.style.overflow = "";
            }

            function handleMainToggle() {
                if (window.innerWidth >= 992) {
                    toggleSidebarDesktop();
                } else {
                    if (sidebar && sidebar.classList.contains("active")) {
                        closeSidebarMobile();
                    } else {
                        openSidebarMobile();
                    }
                }
            }

            if (toggle) toggle.addEventListener("click", handleMainToggle);
            if (collapseBtn) collapseBtn.addEventListener("click", toggleSidebarDesktop);
            if (closeBtn) closeBtn.addEventListener("click", closeSidebarMobile);
            if (overlay) overlay.addEventListener("click", closeSidebarMobile);

            document.addEventListener("keydown", function(e) {
                if (e.key === "Escape") {
                    if (window.innerWidth < 992) closeSidebarMobile();
                }
            });

            window.addEventListener("resize", function() {
                if (window.innerWidth >= 992) {
                    closeSidebarMobile();
                    if (localStorage.getItem("sidebar_collapsed") === "true") {
                        if (layout) layout.classList.add("sidebar-collapsed");
                        document.documentElement.classList.add("sidebar-is-collapsed");
                    }
                } else {
                    if (layout) layout.classList.remove("sidebar-collapsed");
                    document.documentElement.classList.remove("sidebar-is-collapsed");
                }
            });
        });
    </script>
    <script src="/Proyecto-TeMa/assets/js/session-timeout.js"></script>
</body>
</html>