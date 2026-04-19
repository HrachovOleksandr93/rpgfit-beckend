# Landing page assets

Static assets consumed by `templates/landing/index.html.twig`.

## Current

| File | Purpose | Notes |
|------|---------|-------|
| `landing.css` | Main stylesheet | Self-contained, ~460 lines, 07 Vector Field tokens |
| `favicon.svg` | SVG favicon (vector) | 64x64 viewBox, served via `<link rel="icon">` |
| `logo.svg` | Inline brand mark for Organization JSON-LD | 320x80 viewBox |

## Missing (placeholders expected at these paths)

| File | Dimensions | Format | Notes |
|------|-----------|--------|-------|
| `favicon.ico` | 16/32/48 multi-res | ICO | Legacy fallback for old Safari/IE |
| `og-image.png` | **1200 x 630** | PNG (< 300 KB) | Open Graph + Twitter card preview |
| `apple-touch-icon.png` | 180 x 180 | PNG | iOS home-screen |

**OG image composition guide:**
- Background `#0B1420` with a subtle copper radial gradient (15% 20%).
- Left half: giant "2042" number in IBM Plex Sans 700 copper `#D97B3E`, calipers ticks.
- Right half: `RPGFit · VERA-ICS` wordmark + tagline "Field instrument of the Rupture".
- Bottom-right corner: serial `VF-07/42 · 50.452°N · 30.523°E` in mono.
- AAA contrast for every text element.

Until real bitmaps are added, browsers will serve a broken-image for `og-image.png`
when pages are shared — this does NOT break the landing itself.

## Generating assets

Recommended pipeline (not checked in):
1. Design in Figma on the 07 Vector Field palette.
2. Export OG image as PNG at 2x, compress via `oxipng` / `pngquant`.
3. Drop files into this directory. No Twig changes needed.
