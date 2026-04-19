# Mob YAML schema

Each YAML file under `data/mobs/` describes one realm's bestiary. The
`app:seed-mobs` command reads every `*.yaml` file here and upserts mobs
by slug.

> Source design doc: `BA/outputs/09-mob-bestiary.md`.
>
> HP/XP formulas are in `docs/BUSINESS_LOGIC.md` §10 and remain
> **untouched**: `realm` / `class_tier` / `behavior` / `archetype` are
> metadata only. Realm-artifact damage multiplier (+40%) is applied at
> battle-result time, not at seed time.

## Top-level structure

```yaml
realm: olympus           # informational; overridable per-mob
mobs:
  - slug: satyr_marsh    # unique across the DB
    name_ua: "Сатир-браконьєр"
    name_en: "Marsh Satyr"
    rarity: common       # common | uncommon | rare | epic | legendary
    realm: olympus       # olympus | asgard | dharma | duat | nav | shiba | neutral
    class_tier: I        # I | II | III | IV
    behavior: physical   # physical | ritual | oracle_task | team
    archetype: humanoid  # humanoid | beast | spirit | undead | construct | divine | chimera | swarm
    visual_keywords: [goat-legs, flute-shard, wine-stained]
    accepts_champion: true
    flavor_ua: "..."
    flavor_en: "..."
    level_range: [1, 5, 1]   # [min, max, step] — expands to one DB row per level
```

## Level expansion

`level_range: [1, 5, 1]` generates five DB rows — one per level — with
slug `"{base_slug}_lvl{N}"` (e.g. `satyr_marsh_lvl3`). HP/XP are computed
deterministically from `BUSINESS_LOGIC §10`:

- `HP = round(20 * level^1.5 + 40)`
- `XP = round((4.2*level^2 + 28*level) / 15 * rarity_multiplier)`

The seeder does not apply the ±20% / ±10% jitter during seeding so runs
are idempotent; jitter is a runtime concern for spawn resolution.

## Champion eligibility

`accepts_champion: false` excludes the mob from the 10–15% champion roll
performed by `MobSelectionService::maybeAsChampion()`. Rule of thumb:

- **false** for `spirit`, `swarm`, and tier-IV `divine` mobs (no
  Apple Watch jokes on Ares).
- **true** for body-bearing archetypes: `humanoid`, `beast`, `undead`,
  `construct`, `chimera`.

## Running the seeder

```bash
# All realms
php bin/console app:seed-mobs

# Only one realm
php bin/console app:seed-mobs --realm=olympus

# Dry-run (no DB writes)
php bin/console app:seed-mobs --dry-run
```

The legacy CSV command `app:import-mobs` is deprecated and retained
for one release as a read-only fallback.
