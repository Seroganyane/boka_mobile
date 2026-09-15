<?php
declare(strict_types=1);

require_once __DIR__ . '/booking-notifications.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/travel-pricing.php';

function payFastEncode(string $value): string
{
    return str_replace('%7E', '~', urlencode(trim($value)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = $_POST;
$receivedSignature = (string) ($data['signature'] ?? '');
unset($data['signature']);
$parts = [];
foreach ($data as $key => $value) {
    if ((string) $value !== '') $parts[] = $key . '=' . payFastEncode((string) $value);
}
$parameterString = implode('&', $parts);
$signatureString = $parameterString;
$passphrase = configValue('PAYFAST_PASSPHRASE');
if ($passphrase !== '') $signatureString .= '&passphrase=' . payFastEncode($passphrase);
if ($receivedSignature === '' || !hash_equals(md5($signatureString), $receivedSignature)) {
    error_log('Rejected PayFast ITN: invalid signature.');
    http_response_code(400);
    exit;
}

$sandbox = filter_var(configValue('PAYFAST_SANDBOX', 'false'), FILTER_VALIDATE_BOOLEAN);
$validationUrl = $sandbox ? 'https://sandbox.payfast.co.za/eng/query/validate' : 'https://www.payfast.co.za/eng/query/validate';
$curl = curl_init($validationUrl);
curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $parameterString, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
$validation = curl_exec($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
if ($httpCode !== 200 || trim((string) $validation) !== 'VALID') {
    error_log('Rejected PayFast ITN: server validation failed.');
    http_response_code(400);
    exit;
}

$merchantId = configValue('PAYFAST_MERCHANT_ID');
$prices = [
    'sedan' => [700, 850, 800, 1100, 400],
    'suv' => [800, 900, 950, 1300, 450],
    'bakkie' => [800, 900, 950, 1300, 450],
    'minibus' => [150, 1400, 1550],
    'home' => [250, 350, 450, 500, 350, 500],
];
$category = (string) ($_POST['custom_str1'] ?? '');
$packageIndex = filter_var($_POST['custom_str2'] ?? null, FILTER_VALIDATE_INT);
$visitType = (string) ($_POST['custom_str4'] ?? '');
$travel = ['type' => 'dropoff', 'meters' => 0, 'feeCents' => 0];
if ($visitType === 'housecall') {
    $proof = explode(':', (string) ($_POST['custom_str5'] ?? ''));
    if (count($proof) === 2 && $proof[0] === 'manual' && ctype_digit($proof[1]) && strlen($proof[1]) <= 7 && (int) $proof[1] > 0 && (int) $proof[1] <= 9999000) {
        // These fields are covered by the verified PayFast signature above.
        $meters = (int) $proof[1];
    } else {
        // Accept signed travel metadata from checkouts started before manual entry.
        try {
            if (count($proof) !== 2 || !ctype_digit($proof[0]) || strlen($proof[0]) > 9
                || !hash_equals(travelPaymentProof((string) ($_POST['m_payment_id'] ?? ''), (string) ($_POST['custom_str3'] ?? ''), (int) $proof[0]), $proof[1])) {
                http_response_code(400);
                exit;
            }
        } catch (RuntimeException $error) {
            http_response_code(503);
            exit;
        }
        $meters = (int) $proof[0];
    }
    $travel = ['type' => 'housecall', 'meters' => $meters, 'feeCents' => travelFeeCents($meters)];
} elseif ($visitType !== '' && $visitType !== 'dropoff') {
    http_response_code(400);
    exit;
}
$expectedAmount = $packageIndex !== false && isset($prices[$category][$packageIndex])
    ? number_format($prices[$category][$packageIndex] + $travel['feeCents'] / 100, 2, '.', '')
    : '';
$paidAmount = number_format((float) ($_POST['amount_gross'] ?? 0), 2, '.', '');
$valid = $merchantId !== '' && hash_equals($merchantId, (string) ($_POST['merchant_id'] ?? ''))
    && ($_POST['payment_status'] ?? '') === 'COMPLETE'
    && ($_POST['m_payment_id'] ?? '') !== ''
    && $expectedAmount !== ''
    && hash_equals($expectedAmount, $paidAmount);
if (!$valid) {
    error_log('Rejected PayFast ITN: payment is incomplete or merchant does not match.');
    http_response_code(400);
    exit;
}

error_log('Verified PayFast payment ' . preg_replace('/[^A-Za-z0-9._-]/', '', (string) $_POST['m_payment_id']) . ' for R' . (string) ($_POST['amount_gross'] ?? '0.00'));
$packageNames = [
    'sedan' => ['Interior deep clean', 'Interior + engine bay wash', 'Interior + normal car wash', 'Exterior wash & shine + interior', 'Ceramic coating (add-on)'],
    'suv' => ['Interior deep clean', 'Interior + engine bay wash', 'Interior + normal car wash', 'Exterior wash & shine + interior', 'Ceramic coating (add-on)'],
    'bakkie' => ['Interior deep clean', 'Interior + engine bay wash', 'Interior + normal car wash', 'Exterior wash & shine + interior', 'Ceramic coating (add-on)'],
    'minibus' => ['Exterior wash', 'Interior deep clean', 'Interior deep clean + exterior wash'],
    'home' => ['Couch - 2 seats', 'Couch - 3 seats', 'Couch - 4 seats', 'Couch - L shape', 'Mattress - single bed', 'Mattress - double/queen/king'],
];
$description = (string) ($_POST['item_description'] ?? '');
$preferredDate = '';
if (preg_match('/Preferred date:\s*([^;]+)/i', $description, $dateMatch)) $preferredDate = trim($dateMatch[1]);
$notes = preg_replace('/^Preferred date:\s*[^;]+;?\s*/i', '', $description);
$booking = [
    'name' => trim((string) (($_POST['name_first'] ?? '') . ' ' . ($_POST['name_last'] ?? ''))),
    'phone' => (string) ($_POST['cell_number'] ?? 'Not provided'),
    'email' => (string) ($_POST['email_address'] ?? 'Not provided'),
    'location' => (string) ($_POST['custom_str3'] ?? 'Not provided'),
    'service' => ucfirst($category),
    'package' => $packageNames[$category][$packageIndex] ?? (string) ($_POST['item_name'] ?? 'Not provided'),
    'date' => $preferredDate ?: 'Not provided',
    'notes' => ($visitType !== '' ? travelDescription($travel) . "\n" : '') . ($notes ?: 'No additional notes'),
    'payment' => 'PayFast - paid',
    'amount' => 'R' . $paidAmount,
    'reference' => (string) $_POST['m_payment_id'],
];
try {
    $isNewBooking = saveBooking($booking);
} catch (RuntimeException $error) {
    error_log('Verified payment, but booking storage failed: ' . $error->getMessage());
    http_response_code(500);
    exit;
}
if (!$isNewBooking) {
    http_response_code(200);
    exit;
}
$emailSent = notifyStaff($booking);
if (!$emailSent) error_log('Verified payment, but the staff email notification failed.');
$confirmationSent = notifyCustomer($booking);
if (!$confirmationSent) error_log('Verified payment, but the customer confirmation email failed.');
http_response_code(200);
