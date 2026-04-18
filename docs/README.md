# RPGFit Backend — Documentation Index

All design, BA analysis, vision, and lore copied from the workspace root so
the backend repo is self-contained after `git clone`.

## Authoritative docs

- [`ARCHITECTURE.md`](./ARCHITECTURE.md) — DDD layering, entity map, DB schema overview
- [`BUSINESS_LOGIC.md`](./BUSINESS_LOGIC.md) — all game formulas (HP/XP/battle/leveling)

## BA analyses (`ba/`)

Business-analyst agent reports that informed backend decisions.

- [`ba/01-market-research.md`](./ba/01-market-research.md) — Strava, Ingress, Orna, Pokémon GO benchmark
- [`ba/02-beta-scope.md`](./ba/02-beta-scope.md) — what ships on 31.10.2026
- [`ba/03-roadmap.md`](./ba/03-roadmap.md) — 27-week plan
- [`ba/04-code-audit.md`](./ba/04-code-audit.md) — backend + client gap analysis (4 critical DTO bugs)
- [`ba/05-lore-to-hook.md`](./ba/05-lore-to-hook.md) — how lore translates into UX hooks
- [`ba/07-design-research.md`](./ba/07-design-research.md) — 06/07 design deep-dive (cross-ref only — backend dev doesn't touch design)
- [`ba/08-mental-stats-research.md`](./ba/08-mental-stats-research.md) — mindfulness data research → Option B recommended
- [`ba/09-mob-bestiary.md`](./ba/09-mob-bestiary.md) — Mob entity extension + Olympus pilot + migration plan

## Vision (`vision/`)

Founder-approved product decisions and concept drafts.

- [`vision/product-decisions-2026-04-18.md`](./vision/product-decisions-2026-04-18.md) — **D1-D5 authoritative**: native health, no races, no factions, portals yes, social P0
- [`vision/design-principles-2026-04-18.md`](./vision/design-principles-2026-04-18.md) — mass-audience UI rules (1 CTA color, text floor, hierarchy)
- [`vision/health-aggregator-comparison.md`](./vision/health-aggregator-comparison.md) — Terra vs Vital vs native (approved native)
- [`vision/portals.md`](./vision/portals.md) — portal concept (static + dynamic)
- [`vision/mobs.md`](./vision/mobs.md) — mob vision
- [`vision/mobs-champion-variants.md`](./vision/mobs-champion-variants.md) — champion-mob decoration mechanic
- [`vision/mental-stats-mindfulness.md`](./vision/mental-stats-mindfulness.md) — not yet approved, research only
- [`vision/emotional-hooks.md`](./vision/emotional-hooks.md) — 7 emotional hooks that design targets
- [`vision/beta-hype.md`](./vision/beta-hype.md) — launch event messaging
- [`vision/onboarding-gifts.md`](./vision/onboarding-gifts.md) — first-session items and kits

## Lore extracts (`lore-extracts/`)

- [`lore-extracts/realms-canon.md`](./lore-extracts/realms-canon.md) — **canonical 6 realms** (olympus, asgard, dharma, duat, nav, shiba)
- [`lore-extracts/teoria-svitu-v1.1-text.txt`](./lore-extracts/teoria-svitu-v1.1-text.txt) — full text extract from universe docx

## Implementation plans (`superpowers/plans/`)

- [`superpowers/plans/2026-04-04-plan1-foundation-auth.md`](./superpowers/plans/2026-04-04-plan1-foundation-auth.md)
- [`superpowers/plans/2026-04-04-plan2a-onboarding-profile-leveling.md`](./superpowers/plans/2026-04-04-plan2a-onboarding-profile-leveling.md)
- [`superpowers/plans/2026-04-04-plan2b-health-sync.md`](./superpowers/plans/2026-04-04-plan2b-health-sync.md)
- [`superpowers/plans/2026-04-04-plan2c-battle-workout-equipment.md`](./superpowers/plans/2026-04-04-plan2c-battle-workout-equipment.md)
- [`superpowers/plans/2026-04-18-portals-mobs-races-landing-impl.md`](./superpowers/plans/2026-04-18-portals-mobs-races-landing-impl.md) — **current active plan**

## Specs (`superpowers/specs/`)

- [`superpowers/specs/2026-04-04-flow-summary.md`](./superpowers/specs/2026-04-04-flow-summary.md)
- [`superpowers/specs/2026-04-04-react-native-rewrite-design.md`](./superpowers/specs/2026-04-04-react-native-rewrite-design.md) — mobile rewrite spec (relevant for API contracts)

## Project snapshots

- [`partner-report-2026-04-18.md`](./partner-report-2026-04-18.md) — plain-language status for founder's partner

## Keeping in sync

The workspace root (`/Users/oleksandr/PhpstormProjects/rpgfit/`) is NOT a git
repo. These docs are mirrored here from:
- Root `BA/outputs/` → `docs/ba/`
- Root `docs/vision/` → `docs/vision/`
- Root `docs/lore-extracts/` → `docs/lore-extracts/`
- Root `docs/superpowers/` → `docs/superpowers/`

When root docs change, re-copy with:
```bash
cp /path/to/rpgfit/BA/outputs/*.md docs/ba/
cp /path/to/rpgfit/docs/vision/*.md docs/vision/
# etc.
```

For now treat this copy as authoritative **inside the backend repo**. Root
remains the working scratch space where the BA agents write new artifacts.
