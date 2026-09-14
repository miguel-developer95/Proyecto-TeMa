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
    <script src="/Proyecto-TeMa/assets/js/session-timeout.js"></script>
</body>
</html>