<?php
declare(strict_types=1);
putenv('TRAVEL_QUOTE_SECRET=test-only-travel-signing-secret-123456789');
require_once __DIR__ . '/../php/booking-pricing.php';

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function rejected(array $input, string $message): void
{
    try { bookingPrice($input); } catch (InvalidArgumentException $error) { return; }
    throw new RuntimeException($message);
}

$input = ['category' => 'sedan', 'package' => 'sedan:0', 'visitType' => 'dropoff', 'amount' => 'R1'];
$price = bookingPrice($input);
check($price['totalCents'] === 70000, 'Drop-off must use the server package price.');
check($price['travel']['location'] === businessAddress(), 'Drop-off must use the business address.');
$input['visitType'] = 'housecall';
$input['location'] = '10 Example Street, Kempton Park';
$input['distanceKm'] = '12.5';
$price = bookingPrice($input);
check($price['totalCents'] === 76250, '12.5 km must add R62.50 to R700.');
check(travelFeeCents(1001) === 501, 'Half cents must round consistently.');
foreach (['', '-1', '0', 'NaN', 'Infinity', '10000', '1.2345', []] as $invalid) {
    rejected(array_merge($input, ['distanceKm' => $invalid]), 'Invalid distances must be rejected.');
}
rejected(array_merge($input, ['location' => '']), 'House calls need an address.');
rejected(array_merge($input, ['visitType' => 'free']), 'Invalid visit options must be rejected.');
rejected(array_merge($input, ['package' => 'suv:0']), 'Mismatched packages must be rejected.');
putenv('TRAVEL_QUOTE_SECRET=');
check(bookingPrice($input)['totalCents'] === 76250, 'Manual pricing must work without a signing secret.');
echo "Travel pricing checks passed.\n";
