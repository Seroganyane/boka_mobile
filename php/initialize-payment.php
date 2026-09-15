<?php
declare(strict_types=1);

require_once __DIR__ . '/booking-pricing.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function payFastEncode(string $value): string
{
    return str_replace('%7E', '~', urlencode(trim($value)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['message' => 'Method not allowed.']);

$merchantId = configValue('PAYFAST_MERCHANT_ID');
$merchantKey = configValue('PAYFAST_MERCHANT_KEY');
$passphrase = configValue('PAYFAST_PASSPHRASE');
$sandbox = filter_var(configValue('PAYFAST_SANDBOX', 'false'), FILTER_VALIDATE_BOOLEAN);
if ($merchantId === '' || $merchantKey === '') respond(500, ['message' => 'Online payments are not configured yet.']);

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) respond(400, ['message' => 'Invalid booking request.']);

$name = trim((string) ($input['name'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$location = trim(substr((string) ($input['location'] ?? ''), 0, 200));
$category = (string) ($input['category'] ?? '');
$package = (string) ($input['package'] ?? '');
$date = trim((string) ($input['date'] ?? ''));
$notes = trim((string) ($input['notes'] ?? ''));
if ($name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $date === '') {
    respond(422, ['message' => 'Please complete all required booking details.']);
}
$bookingDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$bookingDate || $bookingDate->format('Y-m-d') !== $date) respond(422, ['message' => 'Please select a valid booking date.']);

try {
    $price = bookingPrice($input);
} catch (InvalidArgumentException $error) {
    respond(422, ['message' => $error->getMessage()]);
} catch (RuntimeException $error) {
    respond(503, ['message' => $error->getMessage()]);
}
$location = $price['travel']['location'];
$selectedPackage = [$price['package'], $price['totalCents'] / 100];

$appUrl = rtrim(configValue('APP_URL'), '/');
if ($appUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? '');
    $appUrl = $host ? $scheme . '://' . $host : '';
}
if ($appUrl === '') respond(500, ['message' => 'A website URL is required to start online payments.']);

$nameParts = preg_split('/\s+/', $name, 2);
$reference = 'boka_' . bin2hex(random_bytes(10));
$fields = [
    'merchant_id' => $merchantId,
    'merchant_key' => $merchantKey,
    'return_url' => $appUrl . '/php/payment-callback.php?reference=' . rawurlencode($reference),
    'cancel_url' => $appUrl . '/php/payment-callback.php?cancelled=1&reference=' . rawurlencode($reference),
    'notify_url' => $appUrl . '/php/payfast-itn.php',
    'name_first' => $nameParts[0] ?? $name,
    'name_last' => $nameParts[1] ?? '',
    'email_address' => $email,
    'cell_number' => preg_replace('/[^0-9+]/', '', $phone),
    'm_payment_id' => $reference,
    'amount' => number_format((float) $selectedPackage[1], 2, '.', ''),
    'item_name' => 'Boka: ' . $selectedPackage[0],
    'item_description' => substr(trim('Preferred date: ' . $date . ($notes !== '' ? '; ' . $notes : '')), 0, 255),
    'custom_str1' => $category,
    'custom_str2' => (string) $price['index'],
    'custom_str3' => $location,
    'custom_str4' => $price['travel']['type'],
    'custom_str5' => $price['travel']['type'] === 'housecall'
        ? 'manual:' . $price['travel']['meters'] : '',
];

$signatureParts = [];
foreach ($fields as $key => $value) {
    if ((string) $value !== '') $signatureParts[] = $key . '=' . payFastEncode((string) $value);
}
$signatureString = implode('&', $signatureParts);
if ($passphrase !== '') $signatureString .= '&passphrase=' . payFastEncode($passphrase);
$fields['signature'] = md5($signatureString);

respond(200, [
    'process_url' => $sandbox ? 'https://sandbox.payfast.co.za/eng/process' : 'https://www.payfast.co.za/eng/process',
    'fields' => $fields,
]);
