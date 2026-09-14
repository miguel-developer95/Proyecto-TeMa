<?php
/** Modal + script de aviso de expiración por inactividad (RF 1.8).
 *  Incluir justo antes de </body> en cada vista protegida. */
?>
<div id="session-timeout-modal" style="display:none;">
  <div class="session-timeout-box">
    <p>Tu sesión está por expirar en <span id="session-timeout-countdown"></span> segundos.</p>
    <button id="btn-continuar-sesion" type="button">Seguir conectado</button>
  </div>
</div>
<script>
  window.BASE_URL = "<?= e(BASE_URL) ?>";
</script>
<script src="<?= e(base_url('assets/js/session-timeout.js')) ?>"></script>