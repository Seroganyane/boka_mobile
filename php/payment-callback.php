<?php
declare(strict_types=1);
function escapeHtml(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
$reference = preg_replace('/[^A-Za-z0-9._-]/', '', (string) ($_GET['reference'] ?? ''));
$cancelled = isset($_GET['cancelled']);
$title = $cancelled ? 'Payment cancelled' : 'Payment submitted';
$message = $cancelled
    ? 'No payment was taken. You can return to the booking form and try again or choose cash.'
    : 'PayFast is processing your payment. Boka Mobile Detailing will confirm your booking after receiving the payment notification.';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= escapeHtml($title) ?> | Boka Mobile Detailing</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#071b34;color:#eaf3fb;font-family:Arial,sans-serif}main{max-width:560px;text-align:center;background:#0a2647;border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:40px;box-shadow:0 24px 55px rgba(0,0,0,.3)}.mark{font-size:42px;color:#f6b21b}.status{font-size:28px;margin:14px 0}.reference{opacity:.7;font-size:14px}.back{display:inline-block;margin-top:24px;border-radius:999px;padding:13px 22px;background:#f6b21b;color:#071b34;font-weight:bold;text-decoration:none}</style>
</head><body><main><div class="mark"><?= $cancelled ? '!' : '✓' ?></div><h1 class="status"><?= escapeHtml($title) ?></h1><p><?= escapeHtml($message) ?></p><?php if ($reference): ?><p class="reference">Reference: <?= escapeHtml($reference) ?></p><?php endif; ?><a class="back" href="../index.html#contact">Back to Boka Mobile Detailing</a></main></body></html>
