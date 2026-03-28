# Mob Data Files

## CSV Structure for Mob Import

| Column | Type | Required | Description |
|--------|------|----------|-------------|
| name | string | yes | Display name of the mob |
| slug | string | yes | Unique identifier (lowercase, hyphens) |
| level | int | yes | Mob level (1-100) |
| hp | int | yes | Base hit points |
| xp_reward | int | yes | Base XP awarded for defeating |
| rarity | string | no | Rarity tier: common, uncommon, rare, epic, legendary |
| description | string | no | Mob description |

## Import Command

```bash
docker-compose exec php php bin/console app:import-mobs data/mobs.csv
```

Options:
- `--update` — update existing mobs (matched by slug) instead of skipping
- `--dry-run` — validate CSV without importing

## HP Guidelines by Level

Approximate base HP formula: `hp ≈ 20 * level^1.5 + 40`
Add ±20% random variation for variety.

| Level | Base HP | Range (±20%) |
|-------|---------|-------------|
| 1 | 60 | 48-72 |
| 5 | 264 | 211-317 |
| 10 | 672 | 538-807 |
| 20 | 1,829 | 1,463-2,195 |
| 30 | 3,327 | 2,662-3,993 |
| 50 | 7,111 | 5,689-8,533 |
| 70 | 11,753 | 9,402-14,103 |
| 90 | 17,117 | 13,694-20,541 |
| 100 | 20,040 | 16,032-24,048 |

## XP Reward Guidelines

Approximate: `xp_reward ≈ hp * 0.08`
Rarity multipliers: common=1x, uncommon=1.3x, rare=1.6x, epic=2x, legendary=3x
