<?php
$notifications = [];
foreach (['success', 'error', 'warning', 'info'] as $kind) {
    $message = session()->getFlashdata($kind);
    if (is_string($message) && $message !== '') {
        $notifications[] = ['icon' => $kind, 'text' => $message];
    }
}
?>
<script id="app-flash-messages" type="application/json"><?= json_encode($notifications, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<script src="<?= base_url('plugins/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= base_url('js/notifications.js') ?>?v=1"></script>
