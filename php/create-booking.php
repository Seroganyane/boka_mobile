<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/booking-notifications.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/booking-pricing.php';

function respond(int $statusCode, array $data): void { http_response_code($statusCode); echo json_encode($data); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['message' => 'Method not allowed.']);
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) respond(400, ['message' => 'Invalid booking request.']);
try {
    $price = bookingPrice($input);
} catch (InvalidArgumentException $error) {
    respond(422, ['message' => $error->getMessage()]);
} catch (RuntimeException $error) {
    respond(503, ['message' => $error->getMessage()]);
}
$input['location'] = $price['travel']['location'];
$input['service'] = ucfirst($price['category']);
$input['package'] = $price['package'];
$booking = [];
foreach (['name', 'phone', 'email', 'location', 'service', 'package', 'date', 'notes'] as $field) {
    $booking[$field] = trim(substr((string) ($input[$field] ?? ''), 0, $field === 'notes' ? 1000 : 200));
}
if ($booking['name'] === '' || $booking['phone'] === '' || !filter_var($booking['email'], FILTER_VALIDATE_EMAIL) || $booking['location'] === '' || $booking['service'] === '' || $booking['package'] === '' || $booking['date'] === '') {
    respond(422, ['message' => 'Please complete all required booking details.']);
}
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $booking['date']);
if (!$date || $date->format('Y-m-d') !== $booking['date']) respond(422, ['message' => 'Please select a valid booking date.']);
$booking['payment'] = 'Cash on arrival';
$booking['amount'] = 'R' . number_format($price['totalCents'] / 100, 2, '.', '');
$booking['notes'] = travelDescription($price['travel']) . "\n" . $booking['notes'];
$booking['reference'] = 'cash_' . bin2hex(random_bytes(8));
try {
    saveBooking($booking);
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    respond(500, ['message' => 'We could not save your booking. Please call us to book.']);
}
$emailSent = notifyStaff($booking);
if (!$emailSent) respond(503, ['message' => 'We could not email the team. Please call us to book.']);
$confirmationSent = notifyCustomer($booking);
if (!$confirmationSent) error_log('Booking accepted, but the customer confirmation email failed for ' . $booking['reference']);
respond(201, ['message' => 'Booking received. The team has been notified.', 'reference' => $booking['reference']]);
