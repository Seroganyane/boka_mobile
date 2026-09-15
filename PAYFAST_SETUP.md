# PayFast setup

The booking form supports cash or secure PayFast checkout. PHP signs the checkout request, and `php/payfast-itn.php` validates PayFast's server-to-server payment notification.

## Environment variables

Configure these on your PHP host (never put them in HTML or JavaScript):

```text
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_MERCHANT_KEY=your_merchant_key
PAYFAST_PASSPHRASE=your_passphrase
PAYFAST_SANDBOX=true
APP_URL=https://bokamobiledetailing.co.za
STAFF_EMAIL=nickmametja6@gmail.com
BOOKING_FROM_EMAIL=Boka Bookings <bookings@bokamobiledetailing.co.za>
RESEND_API_KEY=re_your_resend_api_key
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASSWORD=your_database_password
```

## HOSTAFRICA DirectAdmin setup

For HOSTAFRICA Web_Starter, upload the website folders and files into your domain's `public_html` directory. In DirectAdmin File Manager, copy `boka-config.example.php`, move the copy into the directory immediately above `public_html`, rename it to `boka-config.php`, and fill in the real values. The resulting layout should look like this:

```text
yourdomain.co.za/
├── boka-config.php       # private credentials; outside the public website
└── public_html/
    ├── index.html
    ├── css/
    ├── js/
    ├── php/
    └── assets/
```

Delete `boka-config.example.php` from `public_html` after creating the private copy. Enable Let's Encrypt SSL in DirectAdmin and make sure `APP_URL` uses the final `https://` domain. Select PHP 8.1 or newer and ensure the `curl` extension is enabled. If cURL is not visible in the PHP selector, ask HOSTAFRICA support to confirm that PHP cURL and outbound HTTPS requests are enabled for the Web_Starter account.

Email uses Resend when its API key and sender are configured. If Resend rejects a message, the website automatically retries it through the HOSTAFRICA PHP `mail()` service. For reliable delivery, verify the sending domain in Resend and add SPF and DKIM records in DirectAdmin DNS Management.

`STAFF_EMAIL` is the owner's inbox and receives the customer's name, phone, email, location, service, package, preferred date, payment method, amount, notes, and booking reference. The customer also receives a successful-booking confirmation at the email entered on the form. Replies to the owner's notification go directly to the customer. For Resend, verify the sending domain first and set `BOOKING_FROM_EMAIL` to an address on that domain.

Cash bookings call the server immediately and notify staff. Online bookings notify staff only after PayFast sends and passes the verified payment notification. Notification failures are written to the PHP error log and no API secret is sent to the browser.

## MySQL booking storage

In DirectAdmin, create a MySQL database and database user, then grant that user access to the database. Open phpMyAdmin, select the new database, choose **Import**, and import `mysql-schema.sql`. Add the resulting database name, username, and password to the private `boka-config.php` above `public_html`. PHP saves the booking successfully before sending email. The unique payment reference prevents repeated PayFast notifications from creating duplicate bookings.

The passphrase must exactly match the one configured in the PayFast dashboard. Set `PAYFAST_SANDBOX=false` and use live credentials only after sandbox testing. `APP_URL` must be a public HTTPS URL so PayFast can reach the ITN endpoint. The host needs PHP cURL.

## Test the flow

1. Add PayFast sandbox credentials and set `PAYFAST_SANDBOX=true`.
2. Publish the complete project on a public PHP-capable HTTPS host.
3. Select a package and **Pay online**, then finish sandbox checkout.
4. Confirm the transaction in the PayFast dashboard and check the PHP log for `Verified PayFast payment`.
5. Switch to live credentials and `PAYFAST_SANDBOX=false` when ready.

Prices are controlled in `php/booking-pricing.php`; update that file, `php/payfast-itn.php`, `index.html`, and `js/script.js` together. Do not send paid-booking alerts from the browser return page: only a successfully validated ITN confirms payment and triggers the staff notification.

For house-call distance calculation and the R5/km travel charge, follow [TRAVEL_SETUP.md](TRAVEL_SETUP.md).
