# Boka Mobile Detailing — Website

A single-page marketing site for Boka Mobile Detailing, built with plain HTML/CSS/JS (no build step, no dependencies).

## Project structure

```
boka-mobile-detailing/
├── index.html      # Page structure & content
├── css/
│   └── styles.css  # All styling (colors, layout, animations)
├── js/
│   └── script.js   # Price estimator and booking flow
├── php/
│   ├── initialize-payment.php  # Creates signed PayFast checkout requests
│   ├── payfast-itn.php         # Validates PayFast payment notifications
│   └── payment-callback.php    # Handles customer return/cancellation
└── README.md
```

## How to open & edit in VS Code

1. Unzip the downloaded folder anywhere on your computer.
2. Open VS Code → **File → Open Folder…** → select `boka-mobile-detailing`.
3. Edit `index.html` for content/copy, `css/styles.css` for design, `js/script.js` for the price calculator logic.

## How to preview it

- Easiest: install the **Live Server** extension in VS Code, right-click `index.html` → **Open with Live Server**.
- Or just double-click `index.html` to open it directly in your browser.

## Where to change things

- **Colors / fonts:** top of `css/styles.css`, in the `:root { ... }` block.
- **Prices:** update `index.html`, `js/script.js`, and `php/booking-pricing.php`, and `php/payfast-itn.php` together. PHP owns the amount sent to PayFast, so the browser cannot change the charge.
- **Contact details / social links:** near the bottom of `index.html`, in the `#contact` section.

## Deploying it

Once you're happy with it, upload the whole folder to a PHP-capable host with PHP cURL enabled. Static-only hosts such as Netlify, Vercel, or GitHub Pages can show the design, but they cannot run the PayFast PHP endpoints. Follow `PAYFAST_SETUP.md` for environment variables and sandbox checkout.

The booking form emails the owner after a cash booking is submitted or after an online payment is verified. Set the owner's inbox in `STAFF_EMAIL`; see `PAYFAST_SETUP.md` for the private server configuration.

PHP saves cash bookings and verified PayFast bookings in MySQL before sending the owner and customer emails. Import `mysql-schema.sql` into the hosting database and configure the `DB_*` values in the private `boka-config.php` file.

For house-call distance calculation and the R5/km travel charge, follow [TRAVEL_SETUP.md](TRAVEL_SETUP.md).
