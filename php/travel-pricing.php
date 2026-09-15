<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function businessAddress(): string
{
    return configValue('BUSINESS_ADDRESS', '6385 Mothomo Crescent, Birch Acres, Kempton Park, South Africa');
}

function travelSecret(): string
{
    $secret = configValue('TRAVEL_QUOTE_SECRET');
    if (strlen($secret) < 32) throw new RuntimeException('House-call quotes are not configured yet. Please call us to book.');
    return $secret;
}

function travelFeeCents(int $meters): int
{
    // R5/km, rounded once to the nearest cent, using the full distance in metres.
    return (int) round($meters / 2);
}

function bookingTravel(array $input): array
{
    $type = (string) ($input['visitType'] ?? '');
    if ($type === 'dropoff') return ['type' => $type, 'location' => businessAddress(), 'meters' => 0, 'feeCents' => 0];
    if ($type !== 'housecall') throw new InvalidArgumentException('Please choose whether you are coming to us or need a house call.');
    $location = trim((string) ($input['location'] ?? ''));
    if ($location === '' || strlen($location) > 200) throw new InvalidArgumentException('Please enter your full address.');
    $distance = $input['distanceKm'] ?? '';
    if (!is_scalar($distance) || !preg_match('/^\d{1,4}(\.\d{1,3})?$/D', (string) $distance)) {
        throw new InvalidArgumentException('Please enter a valid distance in kilometres (up to three decimal places).');
    }
    $meters = (int) round((float) $distance * 1000);
    if ($meters <= 0 || $meters > 9999000) throw new InvalidArgumentException('Please enter a distance greater than zero and no more than 9999 kilometres.');
    return ['type' => $type, 'location' => $location, 'meters' => $meters, 'feeCents' => travelFeeCents($meters)];
}

function travelPaymentProof(string $reference, string $location, int $meters): string
{
    return hash_hmac('sha256', json_encode([$reference, $location, $meters]), travelSecret());
}

function travelDescription(array $travel): string
{
    if ($travel['type'] === 'dropoff') return 'Visit: Come to us. Travel charge: R0.00.';
    return 'Visit: House call. Customer-entered one-way distance: ' . number_format($travel['meters'] / 1000, 3, '.', '')
        . ' km at R5/km. Travel charge: R' . number_format($travel['feeCents'] / 100, 2, '.', '') . '.';
}
