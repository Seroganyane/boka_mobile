<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function database(): PDO
{
    static $connection;
    if ($connection instanceof PDO) return $connection;

    $host = configValue('DB_HOST', 'localhost');
    $port = configValue('DB_PORT', '3306');
    $name = configValue('DB_NAME');
    $user = configValue('DB_USER');
    $password = configValue('DB_PASSWORD');
    if ($name === '' || $user === '') throw new RuntimeException('MySQL is not configured.');

    try {
        $connection = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4',
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $error) {
        error_log('MySQL connection failed: ' . $error->getMessage());
        throw new RuntimeException('The booking database is unavailable.');
    }
    return $connection;
}

function saveBooking(array $booking): bool
{
    $sql = 'INSERT INTO bookings
        (reference, customer_name, phone, email, location, service, package_name, preferred_date, notes, payment_method, amount, status)
        VALUES
        (:reference, :customer_name, :phone, :email, :location, :service, :package_name, :preferred_date, :notes, :payment_method, :amount, :status)';
    try {
        $statement = database()->prepare($sql);
        return $statement->execute([
            ':reference' => (string) ($booking['reference'] ?? ''),
            ':customer_name' => (string) ($booking['name'] ?? ''),
            ':phone' => (string) ($booking['phone'] ?? ''),
            ':email' => (string) ($booking['email'] ?? ''),
            ':location' => (string) ($booking['location'] ?? ''),
            ':service' => (string) ($booking['service'] ?? ''),
            ':package_name' => (string) ($booking['package'] ?? ''),
            ':preferred_date' => (string) ($booking['date'] ?? ''),
            ':notes' => (string) ($booking['notes'] ?? ''),
            ':payment_method' => (string) ($booking['payment'] ?? ''),
            ':amount' => (string) ($booking['amount'] ?? ''),
            ':status' => 'pending',
        ]);
    } catch (PDOException $error) {
        // PayFast can retry the same valid ITN; the unique reference prevents duplicates.
        if ((string) $error->getCode() === '23000') return false;
        error_log('Booking insert failed: ' . $error->getMessage());
        throw new RuntimeException('The booking could not be saved.');
    }
}
