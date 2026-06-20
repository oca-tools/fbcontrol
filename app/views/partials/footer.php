        </main>
        <footer class="mt-4 text-center text-muted small">
            <div>Grand Oca Maragogi Resort</div>
            <div><a href="/?r=privacidade/index" class="text-muted">Privacidade e LGPD</a></div>
            <div style="font-size:.72rem; color:#94a3b8; opacity:.75;">Desenvolvido por Gilson Matias</div>
        </footer>
    </div>
</div>
<div id="exportToastWrap" class="export-toast-wrap" aria-live="polite" aria-atomic="true"></div>
<div id="appAlertWrap" class="app-alert-wrap" aria-live="polite" aria-atomic="true"></div>
<?php
$globalFlash = $this->data['flash'] ?? null;
if (is_array($globalFlash) && !empty($globalFlash['message'])):
?>
    <script id="appFlashPayload" type="application/json"><?= json_for_html([
        'type' => (string)($globalFlash['type'] ?? 'info'),
        'message' => (string)($globalFlash['message'] ?? ''),
    ]) ?></script>
<?php endif; ?>
<?php require __DIR__ . '/footer_scripts.php'; ?>
</body>
</html>
