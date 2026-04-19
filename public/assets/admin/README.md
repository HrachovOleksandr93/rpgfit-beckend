# VERA-ICS Admin — Asset Folder

Assets consumed by the Sonata Admin 07 Vector Field restyle.

## Files

### `vector-field.css`
Cascade-layer override loaded **after** Sonata's default stylesheets.
Remaps AdminLTE / Sonata tokens to the Vector Field palette + IBM Plex
typography. Injected via `templates/bundles/SonataAdminBundle/standard_layout.html.twig`.

### `favicon.ico` (deferred — binary asset)
Recommended dimensions: multi-size `.ico` containing 16×16, 32×32, 48×48 PNG frames.
Colour: copper mark `#D97B3E` on navy `#0B1420` background. Place file at
`public/assets/admin/favicon.ico`. Reference from the Sonata layout via the
`<link rel="icon">` tag — see `standard_layout.html.twig` override.

### `logo.svg` (deferred — optional)
Recommended: 200×40 SVG, stroke `#D97B3E`, background transparent. Place at
`public/assets/admin/logo.svg`. When added, point `sonata_admin.title_logo`
to `/assets/admin/logo.svg` in `config/packages/sonata_admin.yaml`.

## Cache busting

Sonata loads assets through Symfony's asset manager. Run
`bin/console asset-map:compile` (if AssetMapper is active) or
`bin/console assets:install public/` to refresh.

## Palette quick-reference

| Token          | Hex        | Role                         |
|----------------|-----------|------------------------------|
| `--vf-bg`      | `#0B1420` | Page background              |
| `--vf-surface` | `#14202E` | Header, sidebar, input bg    |
| `--vf-panel`   | `#1C2A3D` | Boxes, flash bg              |
| `--vf-panel-hi`| `#243449` | Dashboard blocks, hover rows |
| `--vf-copper`  | `#D97B3E` | Primary CTA, accents         |
| `--vf-sage`    | `#B8C775` | Success                      |
| `--vf-amber`   | `#E5A845` | Warning                      |
| `--vf-rust`    | `#C0413D` | Danger                       |
| `--vf-fg`      | `#E8DDCE` | Body text                    |
| `--vf-fg-muted`| `#A89DA8` | Secondary text               |
| `--vf-line`    | `#2A3A50` | Borders, dividers            |
