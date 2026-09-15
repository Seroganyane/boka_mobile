# Manual house-call kilometres

Customers choose Come to us (no travel charge) or House call (R5/km, one way).
The business address remains visible: 6385 Mothomo Crescent, Birch Acres, Kempton Park.
Customers check the distance themselves and enter kilometres in the form. The travel fee and full booking total update immediately.

For example, 12.5 km costs R62.50; with a R700 package the total is R762.50.
A positive distance is required, up to 9999 km and three decimal places. The fee is rounded to the nearest cent.
The server recalculates the price for cash and PayFast bookings. The customer-entered distance and travel charge are saved in booking notes and confirmation emails.

## Upload

Extract the updated website ZIP into public_html, replacing index.html and all files in css, js and php. Keep your existing private boka-config.php and its payment, email and database settings.

No Google Maps API key or travel signing secret is needed for new bookings. Existing travel settings can remain in the private configuration; the signing secret is used only for older online checkouts already in progress.
The retired travel-quote.php file must also be uploaded to replace the old automatic lookup endpoint.

Checks: php tests/travel-pricing-test.php; node --test tests/booking-travel.test.cjs; python tests/booking-endpoints.py.
