# Boka Mobile Detailing — Website

A single-page marketing site for Boka Mobile Detailing, built with plain HTML/CSS/JS (no build step, no dependencies).

## Project structure

```
boka-mobile-detailing/
├── index.html      # Page structure & content
├── css/
│   └── styles.css  # All styling (colors, layout, animations)
├── js/
│   └── script.js   # Price estimator logic
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
- **Prices:** both in `index.html` (the pricing tables) and `js/script.js` (the `packages` object for the quick estimator) — keep these two in sync if you update a price.
- **Contact details / social links:** near the bottom of `index.html`, in the `#contact` section.

## Deploying it

Once you're happy with it, you can host this folder for free on Netlify, Vercel, GitHub Pages, or any standard web host — no build step required, just upload the files as-is.
