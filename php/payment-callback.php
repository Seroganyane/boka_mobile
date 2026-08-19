<?php
declare(strict_types=1);

function escapeHtml(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

$secretKey = getenv('PAYSTACK_SECRET_KEY');
$reference = preg_replace('/[^A-Za-z0-9.=_-]/', '', (string) ($_GET['reference'] ?? ''));
$message = 'We could not verify this payment. Please contact Boka Mobile Detailing.';
$success = false;

if ($secretKey && $reference !== '') {
    $curl = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt_array($curl, [CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $response = curl_exec($curl);
    curl_close($curl);
    $payment = json_decode((string) $response, true);
    $transaction = $payment['data'] ?? [];
    if (!empty($payment['status']) && ($transaction['status'] ?? '') === 'success' && ($transaction['currency'] ?? '') === 'ZAR' && (int) ($transaction['amount'] ?? 0) > 0) {
        $success = true;
      $metadata = $transaction['metadata'] ?? [];
        $name = is_array($metadata) ? (string) ($metadata['customer_name'] ?? '') : '';
        $message = 'Payment received' . ($name ? ', ' . $name : '') . '. Your booking request is on its way to Boka Mobile Detailing.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment status | Boka Mobile Detailing</title>
  <style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#071b34;color:#eaf3fb;font-family:Arial,sans-serif}main{max-width:560px;text-align:center;background:#0a2647;border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:40px;box-shadow:0 24px 55px rgba(0,0,0,.3)}.mark{font-size:42px;color:<?= $success ? '#f6b21b' : '#ff8f8f' ?>}.status{font-size:28px;margin:14px 0}.reference{opacity:.7;font-size:14px}.back{display:inline-block;margin-top:24px;border-radius:999px;padding:13px 22px;background:#f6b21b;color:#071b34;font-weight:bold;text-decoration:none}</style>
</head>
<body><main><div class="mark"><?= $success ? '✓' : '!' ?></div><h1 class="status"><?= $success ? 'Payment successful' : 'Payment not confirmed' ?></h1><p><?= escapeHtml($message) ?></p><?php if ($reference): ?><p class="reference">Reference: <?= escapeHtml($reference) ?></p><?php endif; ?><a class="back" href="../index.html#contact">Back to Boka Mobile Detailing</a></main></body>
</html>
