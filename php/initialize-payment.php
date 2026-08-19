<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['message' => 'Method not allowed.']);
}

$secretKey = getenv('PAYSTACK_SECRET_KEY');
if (!$secretKey) {
    respond(500, ['message' => 'Online payments are not configured yet.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['message' => 'Invalid booking request.']);
}

$name = trim((string) ($input['name'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$category = (string) ($input['category'] ?? '');
$package = (string) ($input['package'] ?? '');
$date = trim((string) ($input['date'] ?? ''));
$notes = trim((string) ($input['notes'] ?? ''));

if ($name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['message' => 'Please provide a name, phone number, and valid email address.']);
}

/* These are the server-side prices. Never accept a payment amount from the browser. */
$packages = [
    'sedan' => [['Interior deep clean', 700], ['Interior + engine bay wash', 850], ['Interior + normal car wash', 800], ['Exterior wash & shine + interior', 1100], ['Ceramic coating (add-on)', 400]],
    'suv' => [['Interior deep clean', 800], ['Interior + engine bay wash', 900], ['Interior + normal car wash', 950], ['Exterior wash & shine + interior', 1300], ['Ceramic coating (add-on)', 450]],
    'bakkie' => [['Interior deep clean', 800], ['Interior + engine bay wash', 900], ['Interior + normal car wash', 950], ['Exterior wash & shine + interior', 1300], ['Ceramic coating (add-on)', 450]],
    'minibus' => [['Exterior wash', 150], ['Interior deep clean', 1400], ['Interior deep clean + exterior wash', 1550]],
    'home' => [['Couch — 2 seats', 250], ['Couch — 3 seats', 350], ['Couch — 4 seats', 450], ['Couch — L shape', 500], ['Mattress — single bed', 350], ['Mattress — double/queen/king', 500]],
];

if (!preg_match('/^([a-z]+):(\d+)$/', $package, $matches) || $matches[1] !== $category || !isset($packages[$category][$matches[2]])) {
    respond(422, ['message' => 'Please select a valid package.']);
}

$selectedPackage = $packages[$category][(int) $matches[2]];
$appUrl = rtrim((string) getenv('APP_URL'), '/');
if ($appUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? '');
    $appUrl = $host ? $scheme . '://' . $host : '';
}
if ($appUrl === '') {
    respond(500, ['message' => 'A website URL is required to start online payments.']);
}

$reference = 'boka_' . bin2hex(random_bytes(12));
$transaction = [
    'email' => $email,
    'amount' => (string) ($selectedPackage[1] * 100), // Paystack accepts ZAR in cents.
    'currency' => 'ZAR',
    'reference' => $reference,
    'callback_url' => $appUrl . '/php/payment-callback.php',
    'metadata' => json_encode(['customer_name' => $name, 'phone' => $phone, 'service_category' => $category, 'package' => $selectedPackage[0], 'preferred_date' => $date, 'notes' => $notes], JSON_UNESCAPED_UNICODE),
];

$curl = curl_init('https://api.paystack.co/transaction/initialize');
curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($transaction), CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey, 'Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
$response = curl_exec($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

$paystack = json_decode((string) $response, true);
if ($response === false || $httpCode < 200 || $httpCode >= 300 || empty($paystack['status']) || empty($paystack['data']['authorization_url'])) {
    error_log('Paystack initialization failed: ' . ($curlError ?: (string) $response));
    respond(502, ['message' => 'Unable to start secure payment. Please try again or select cash.']);
}

respond(200, ['authorization_url' => $paystack['data']['authorization_url']]);
