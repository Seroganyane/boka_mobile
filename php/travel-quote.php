<?php
// Retired: overwrite the old endpoint on upload so it cannot make maps API calls.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode(['message' => 'Please refresh the booking page and enter your distance in kilometres.']);
