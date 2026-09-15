<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function notifyStaff(array $booking): bool
{
    $lines = [
        'New Boka booking',
        'Customer: ' . ($booking['name'] ?? 'Not provided'),
        'Phone: ' . ($booking['phone'] ?? 'Not provided'),
        'Email: ' . ($booking['email'] ?? 'Not provided'),
        'Location: ' . ($booking['location'] ?? 'Not provided'),
        'Service: ' . ($booking['service'] ?? 'Not provided'),
        'Package: ' . ($booking['package'] ?? 'Not provided'),
        'Preferred date: ' . ($booking['date'] ?? 'Not provided'),
        'Payment: ' . ($booking['payment'] ?? 'Not provided'),
        'Amount: ' . ($booking['amount'] ?? 'Not provided'),
        'Notes: ' . ($booking['notes'] ?? 'No additional notes'),
        'Reference: ' . ($booking['reference'] ?? 'Not provided'),
    ];
    $message = implode("\n", $lines);
    $customerName = preg_replace('/[\r\n]+/', ' ', (string) ($booking['name'] ?? 'Customer'));
    $replyTo = filter_var($booking['email'] ?? '', FILTER_VALIDATE_EMAIL)
        ? (string) $booking['email']
        : '';
    $subject = 'New Boka booking - ' . $customerName;
    return sendStaffEmail($subject, $message, $replyTo);
}

function notifyCustomer(array $booking): bool
{
    $recipient = (string) ($booking['email'] ?? '');
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        error_log('Customer confirmation skipped: customer email is invalid.');
        return false;
    }

    $name = trim((string) ($booking['name'] ?? ''));
    $message = implode("\n", [
        'Hi' . ($name !== '' ? ' ' . $name : '') . ',',
        '',
        'Your Boka Mobile Detailing booking has been successfully received.',
        '',
        'Service: ' . ($booking['service'] ?? 'Not provided'),
        'Package: ' . ($booking['package'] ?? 'Not provided'),
        'Preferred date: ' . ($booking['date'] ?? 'Not provided'),
        'Location: ' . ($booking['location'] ?? 'Not provided'),
        'Payment: ' . ($booking['payment'] ?? 'Not provided'),
        'Amount: ' . ($booking['amount'] ?? 'Not provided'),
        'Reference: ' . ($booking['reference'] ?? 'Not provided'),
        '',
        'Booking details: ' . ($booking['notes'] ?? 'No additional notes'),
        '',
        'We will contact you to confirm the appointment time.',
        '',
        'Boka Mobile Detailing',
    ]);

    return sendEmail($recipient, 'Your Boka booking was received', $message);
}

function sendStaffEmail(string $subject, string $message, string $replyTo = ''): bool
{
    $recipient = configValue('STAFF_EMAIL', 'nickmametja6@gmail.com');
    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        error_log('Booking email skipped: STAFF_EMAIL is not configured.');
        return false;
    }
    return sendEmail($recipient, $subject, $message, $replyTo);
}

function sendEmail(string $recipient, string $subject, string $message, string $replyTo = ''): bool
{
    $apiKey = configValue('RESEND_API_KEY');
    $from = configValue('BOOKING_FROM_EMAIL');
    if ($apiKey !== '' && $from !== '' && function_exists('curl_init')) {
        $payload = ['from' => $from, 'to' => [$recipient], 'subject' => $subject, 'text' => $message];
        if ($replyTo !== '') $payload['reply_to'] = $replyTo;
        $curl = curl_init('https://api.resend.com/emails');
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($status >= 200 && $status < 300) return true;
        error_log('Resend booking email failed for ' . $recipient . ': HTTP ' . $status . ' ' . ($error ?: (string) $response) . '. Trying PHP mail().');
    }

    return sendPhpMail($recipient, $subject, $message, $replyTo, $from);
}

function sendPhpMail(string $recipient, string $subject, string $message, string $replyTo = '', string $from = ''): bool
{
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if ($from !== '') $headers[] = 'From: ' . $from;
    if ($replyTo !== '') $headers[] = 'Reply-To: ' . $replyTo;
    $sent = mail($recipient, $subject, $message, implode("\r\n", $headers));
    if (!$sent) error_log('Booking email failed using PHP mail() for ' . $recipient . '.');
    return $sent;
}
