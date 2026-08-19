# Paystack setup

The booking form gives customers two choices: **Pay cash** or **Pay online**. The online option initializes a Paystack transaction from PHP, redirects to Paystack Checkout, and verifies the result when Paystack redirects back.

## Before you deploy

Use a PHP-capable host. Static-only hosting, such as GitHub Pages, cannot run the two files in `php/`.

## Where to add the secret key

Add the key as a server environment variable in your PHP host's control panel. The code reads it with `getenv('PAYSTACK_SECRET_KEY')` inside `php/initialize-payment.php` and `php/payment-callback.php`.

Add these variables in the host's environment/settings area:

- `PAYSTACK_SECRET_KEY`: Use your Paystack test secret key first, then replace it with your live secret key when the site is ready. Do not put this key in HTML or JavaScript.
- `APP_URL`: The final HTTPS site address, for example `https://www.yourdomain.co.za`. This creates the Paystack callback URL.

Example values (use your real test key, but never commit it to this project):

```text
PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxx
APP_URL=https://www.yourdomain.co.za
```

Do not paste the secret into `index.html`, `js/script.js`, or any public `.js` file. If your host does not provide environment variables, ask its support team for the PHP `getenv`/environment-variable setup method before going live.

Upload the whole project, including the `php` folder. Your host must have PHP cURL enabled.

## Test the payment flow

1. In Paystack, use your **test** secret key as `PAYSTACK_SECRET_KEY`.
2. Publish the site on an HTTPS PHP host; Paystack cannot redirect to a local `file://` preview.
3. Choose a category and package in the booking form, select **Pay online**, and submit.
4. Complete the test checkout. You should return to the payment-status page with a successful verification.
5. Check the transaction in your Paystack dashboard before replacing the test key with your live secret key.

The PHP endpoint has its own price list and calculates the amount in cents. This means customers cannot change the amount in the browser. Whenever prices change, update `index.html`, `js/script.js`, and `php/initialize-payment.php` together.
