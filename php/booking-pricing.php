<?php
declare(strict_types=1);
require_once __DIR__ . '/travel-pricing.php';

function bookingPrice(array $input): array
{
    /* Server-owned prices prevent customers from changing the charge in the browser. */
    $packages = [
        'sedan' => [['Interior deep clean', 700], ['Interior + engine bay wash', 850], ['Interior + normal car wash', 800], ['Exterior wash & shine + interior', 1100], ['Ceramic coating (add-on)', 400]],
        'suv' => [['Interior deep clean', 800], ['Interior + engine bay wash', 900], ['Interior + normal car wash', 950], ['Exterior wash & shine + interior', 1300], ['Ceramic coating (add-on)', 450]],
        'bakkie' => [['Interior deep clean', 800], ['Interior + engine bay wash', 900], ['Interior + normal car wash', 950], ['Exterior wash & shine + interior', 1300], ['Ceramic coating (add-on)', 450]],
        'minibus' => [['Exterior wash', 150], ['Interior deep clean', 1400], ['Interior deep clean + exterior wash', 1550]],
        'home' => [['Couch — 2 seats', 250], ['Couch — 3 seats', 350], ['Couch — 4 seats', 450], ['Couch — L shape', 500], ['Mattress — single bed', 350], ['Mattress — double/queen/king', 500]],
    ];
    $category = (string) ($input['category'] ?? '');
    $package = (string) ($input['package'] ?? '');
    if (!preg_match('/^([a-z]+):(\d+)$/', $package, $matches) || $matches[1] !== $category || !isset($packages[$category][(int) $matches[2]])) {
        throw new InvalidArgumentException('Please select a valid package.');
    }
    $selected = $packages[$category][(int) $matches[2]];
    $travel = bookingTravel($input);
    return ['category' => $category, 'index' => (int) $matches[2], 'package' => $selected[0],
        'baseCents' => $selected[1] * 100, 'totalCents' => $selected[1] * 100 + $travel['feeCents'], 'travel' => $travel];
}
