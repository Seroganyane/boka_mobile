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
│   ├── initialize-payment.php  # Creates Paystack Checkout sessions
│   └── payment-callback.php    # Verifies completed payments
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
- **Prices:** update `index.html`, `js/script.js`, and `php/initialize-payment.php` together. PHP owns the amount sent to Paystack, so the browser cannot change the charge.
- **Contact details / social links:** near the bottom of `index.html`, in the `#contact` section.

## Deploying it

Once you're happy with it, upload the whole folder to a PHP-capable host with PHP cURL enabled. Static-only hosts such as Netlify, Vercel, or GitHub Pages can show the design, but they cannot run the Paystack PHP endpoints. Follow `PAYSTACK_SETUP.md` for environment variables and test-mode checkout.
