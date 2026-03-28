# RPGFit Backend -- Business Logic

## 1. User Registration & OAuth Flow

### Standard Registration (POST /api/registration)

The mobile app sends a single JSON payload with account credentials and full RPG profile data. The flow:

1. **RegistrationController** parses JSON into `RegistrationDTO`
2. Symfony Validator checks constraints: email format, password >= 8 chars, display name 3-30 chars, all required fields present
3. Enum strings (workoutType, activityLevel, desiredGoal, characterRace) are converted via `::tryFrom()`
4. **RegistrationService** checks uniqueness of login (email) and display name against the database
5. Creates `User` entity, hashes password (bcrypt/argon2 via Symfony password hasher)
6. Sets `onboardingCompleted = true` (standard registration collects all data upfront)
7. Persists and returns the user profile as JSON (HTTP 201)

Conflicts return HTTP 409 with a message identifying whether login or display name was taken.

### OAuth Login (POST /api/auth/oauth)

Used when logging in via Google, Apple, or Facebook. The mobile app handles the provider SDK flow and sends the server:
- `provider`: google/apple/facebook
- `providerUserId`: the unique user ID from the provider
- `email`: the user's email from the provider
- `token`: the provider access token (placeholder verification -- actual verification pending API keys)

Flow:
1. Look up `LinkedAccount` by (provider, providerUserId)
2. If found: return JWT for the linked user
3. If not found, look up `User` by email (login field):
   - If user exists: create `LinkedAccount` linking this provider, return JWT
   - If no user: create new `User` with random password, `onboardingCompleted = false`, create `LinkedAccount`, return JWT
4. Response includes `token` (JWT), `onboardingCompleted` flag, and `isNewUser` flag

The mobile app checks `onboardingCompleted` -- if false, it redirects to the onboarding questionnaire.

### Linked Accounts System (POST /api/auth/link-account)

Authenticated users can link additional OAuth providers. The endpoint:
1. Validates the provider account is not already linked to a different user (HTTP 409 if so)
2. If already linked to the current user, returns idempotent success
3. Otherwise creates a new `LinkedAccount`

### Onboarding Questionnaire (POST /api/onboarding)

Required for OAuth users who were created without profile data. Can only be called once per user (HTTP 409 if already completed). Collects:

- `displayName` (3-30 chars, alphanumeric + underscore only)
- `height` (50-300 cm), `weight` (20-500 kg)
- `gender` (male/female)
- `characterRace` (human/orc/dwarf/dark_elf/light_elf)
- `workoutType` (cardio/strength/mixed/crossfit/gymnastics/martial_arts/yoga)
- `trainingFrequency` (none/light/moderate/heavy)
- `lifestyle` (sedentary/moderate/active/very_active)
- `preferredWorkouts` (array of workout slugs, min 1)

**OnboardingService** orchestrates:
1. Validate display name uniqueness
2. Update User entity with profile fields
3. Create `UserTrainingPreference` (separate entity for training-specific config)
4. Calculate initial stats via `StatCalculationService`
5. Create `CharacterStats` entity (1:1 with User)
6. Set `onboardingCompleted = true`

---

## 2. Character System

### Initial Stat Calculation

**StatCalculationService** distributes exactly **30 total stat points** across STR, DEX, CON based on three onboarding answers.

**Base allocation:** 5 per stat (15 total), then bonuses applied:

**Workout type bonuses [STR, DEX, CON]:**
| Type | STR | DEX | CON |
|------|-----|-----|-----|
| strength | +5 | +1 | +2 |
| cardio | +1 | +3 | +5 |
| crossfit | +3 | +3 | +3 |
| gymnastics | +1 | +5 | +2 |
| mixed | +1 | +5 | +2 |
| martial_arts | +3 | +4 | +2 |
| yoga | +1 | +5 | +2 |

**Lifestyle bonuses [STR, DEX, CON]:**
| Lifestyle | STR | DEX | CON |
|-----------|-----|-----|-----|
| sedentary | 0 | 0 | 0 |
| moderate | 0 | +1 | +1 |
| active | +1 | +1 | +1 |
| very_active | +1 | +1 | +2 |

**Training frequency bonuses [STR, DEX, CON]:**
| Frequency | STR | DEX | CON |
|-----------|-----|-----|-----|
| none | 0 | 0 | 0 |
| light | 0 | 0 | +1 |
| moderate | +1 | 0 | +1 |
| heavy | +1 | +1 | +1 |

**Normalization:** After summing base + all bonuses, the total is normalized to exactly 30:
- If sum > 30: proportionally reduce each stat (floor), add remainder to the highest stat
- If sum < 30: add deficit to the highest stat
- If sum = 30: use as-is

**Example:** A strength/active/heavy user gets base 5+5+5=15, workout +5+1+2=8, lifestyle +1+1+1=3, frequency +1+1+1=3, raw total = 29. Deficit of 1 goes to strength (highest). Final: STR=12, DEX=8, CON=10.

### Race Passives

Each race has 5 passive skills (always active, providing stat bonuses). These are seeded by `app:seed-skills` and auto-applied based on the user's `characterRace`. Race skills have `is_race_skill = true` and `race_restriction` set.

### Level Progression

**Formula:** `xp(L) = floor(4.2 * L^2 + 28 * L)` where L is the level being completed.

Coefficients come from `game_settings` keys `level_formula_quad` (default 4.2) and `level_formula_linear` (default 28).

**Cumulative XP to reach level L** = sum of xp(1) through xp(L).

**Sample milestones at 312 XP/day (casual gym-goer):**
- Level 10: ~4,700 XP total -> ~15 days
- Level 30: ~39,060 XP total -> ~125 days (~4 months)
- Level 50: ~108,500 XP total -> ~348 days (~1 year)
- Level 70: ~216,580 XP total -> ~694 days (~2 years)
- Level 100: ~444,080 XP total -> ~1,423 days (~4 years)

Max level: 100 (configurable via `level_max` game setting).

**LevelingService** provides: `getXpForLevel(L)`, `getTotalXpForLevel(L)`, `getLevelForTotalXp(xp)`, `getLevelProgress(xp)` (returns level, XP in bracket, XP to next, percent), and `getFullLevelTable()` for all 100 levels.

---

## 3. Health Data Integration

### Data Flow

```
Health Platform (HealthKit / Health Connect)
    |
    v
Flutter Mobile App (reads via health package)
    |
    v  POST /api/health/sync
Backend API (HealthController)
    |
    v
HealthSyncService
    |
    +-- deduplication (by externalUuid per user)
    +-- persist HealthDataPoint entities (batch flush every 50)
    +-- update HealthSyncLog per data type
    +-- award XP via XpAwardService
```

### Supported Data Types (15 values of HealthDataType)

**XP-awarding types:**
1. STEPS -- XP per 1,000 steps
2. ACTIVE_ENERGY_BURNED -- XP per 100 kcal
3. WORKOUT -- XP per 10 minutes
4. DISTANCE_DELTA -- XP per km
5. SLEEP_ASLEEP, SLEEP_DEEP, SLEEP_LIGHT, SLEEP_REM -- XP per hour (capped at xp_sleep_max_hours)
6. FLIGHTS_CLIMBED -- XP per flight

**Tracking-only types (no XP):**
7. HEART_RATE, WEIGHT, HEIGHT, BODY_FAT_PERCENTAGE, BLOOD_OXYGEN, WATER

### Deduplication

Each health data point has an `externalUuid` -- the original record ID from HealthKit or Health Connect. A unique constraint on `(user_id, external_uuid)` prevents duplicate storage. The `HealthSyncService` checks for existing records before inserting and counts skipped duplicates in the response.

### XP Rates (from game_settings)

| Metric | Rate | Unit |
|--------|------|------|
| Steps | 10 XP | per 1,000 steps |
| Active Energy | 15 XP | per 100 kcal |
| Workout | 20 XP | per 10 minutes |
| Distance | 12 XP | per km |
| Sleep | 5 XP | per hour (max 10 hrs) |
| Flights | 3 XP | per flight |

### Daily XP Cap

Total XP from health sync is capped at `xp_daily_cap` (default 3000 XP/day). The cap is applied in `XpCalculationService.calculateDailyXp()` after summing all individual XP amounts.

---

## 4. Leveling & XP System

### XP Formula Per Level

`xp(L) = floor(4.2 * L^2 + 28 * L)`

| Level | XP to Next | Cumulative |
|-------|-----------|------------|
| 1 | 32 | 32 |
| 5 | 245 | 777 |
| 10 | 700 | 3,290 |
| 20 | 2,240 | 13,720 |
| 30 | 4,620 | 31,290 |
| 50 | 11,900 | 85,750 |
| 70 | 20,860 | 165,060 |
| 100 | 44,600 | ~444,080 |

### XP Sources

1. **Health sync** (via XpAwardService): Calculated from each health metric at the configured rate
2. **Battle rewards** (via BattleResultCalculator): XP from defeating mobs, scaled by performance tier
3. **Workout plan completion** (via WorkoutPlanController): XP from reward tiers (bronze/silver/gold)

### Streak Bonuses

Streak bonuses are designed for game_settings but the multiplier logic is not yet implemented in services. The intended design:
- 3-day streak: 1.1x multiplier
- 7-day streak: 1.2x multiplier
- 14-day streak: 1.3x multiplier
- 30-day streak: 1.5x multiplier

### Daily Cap

Maximum 3000 XP per day from health sync (configurable via `xp_daily_cap`). Battle XP is not subject to this cap.

---

## 5. Skill System

### Skill Categories

1. **Race skills** (5 per race, 25 total): Passive, always active, restricted to one race via `race_restriction`. Flagged with `is_race_skill = true`.

2. **Universal skills** (2 total): Active skills (have duration and cooldown), available to all players. Flagged with `is_universal = true`.

3. **Profession skills** by tier:
   - T1 (tier 1): 3 skills per profession
   - T2 (tier 2): 4 skills per profession
   - T3 (tier 3): 2 ultimate skills + 1 passive per profession
   - Total: ~160 profession-skill links via the `profession_skills` junction table

### Skill Types

- **Passive** (`skill_type = 'passive'`): Always active once unlocked. Stat bonuses permanently applied.
- **Active** (`skill_type = 'active'`): Must be activated during a battle session. Has `duration` (minutes) and `cooldown` (minutes). The user sends `usedSkillSlugs` in the battle complete request.

### Skill Stat Bonuses

Each skill can have multiple `SkillStatBonus` entries mapping to STR/DEX/CON with point values. When calculating effective stats for battle, the system:
1. Applies all passive skill bonuses automatically
2. Applies active skill bonuses only if the skill slug is in `usedSkillSlugs`

### Seeding

The `app:seed-skills` command creates all 39 unique skills with their stat bonuses and links them to professions via `profession_skills`.

---

## 6. Profession System

### Activity Categories (16 total)

Seeded by `app:seed-professions`: combat, running, cycling, swimming, strength, flexibility, yoga, dance, winter, racquet_sports, team_sports, water_sports, outdoor, mind_body, hiit, other.

### Activity Types (99 total)

Each maps a Flutter `HealthWorkoutActivityType` enum value to native iOS (HKWorkoutActivityType) and Android (Health Connect ExerciseSessionRecord) identifiers. Platform-specific types include a `fallback_slug` pointing to the nearest universal equivalent.

Platform support values: `universal` (both platforms), `ios_only`, `android_only`.

### 3-Tier Profession System (48 professions)

Each of the 16 activity categories has 3 profession tiers:
- **Tier 1** (starter): Unlocked first, easier requirements
- **Tier 2** (intermediate): Stronger stat bonuses, more skills
- **Tier 3** (master): Best stat bonuses, ultimate skills

Each profession defines:
- `primaryStat`: The main stat boosted (STR/DEX/CON)
- `secondaryStat`: The secondary stat boosted
- `category`: Which activity category it belongs to

### User Profession Tracking

`UserProfession` tracks which professions a user has unlocked, when, and whether the profession is `active` in its category (only one active per category per user).

---

## 7. Equipment & Inventory

### Item Types

| Type | Purpose |
|------|---------|
| `equipment` | Wearable gear with a slot, provides stat bonuses |
| `scroll` | Consumed to unlock skills |
| `potion` | Consumable with time-limited buff (duration in minutes) |

### Rarity Tiers

common < uncommon < rare < epic < legendary

Affects drop rates, stat bonus magnitude, and mob XP multipliers.

### Equipment Slots (12)

weapon, shield, head, body, legs, feet, hands, bracers, bracelet, ring, shirt, necklace

### Slot Rules (enforced by EquipmentService)

- **Default**: 1 item per slot. Equipping replaces the existing item (auto-unequip).
- **Ring**: Max 2 simultaneously. When at max, the oldest ring is unequipped.
- **Bracelet**: Max 2 simultaneously. Same behavior as ring.
- **Two-handed weapon**: Equipping occupies the weapon slot AND removes any shield.
- **Shield**: Cannot coexist with a two-handed weapon. Equipping a shield removes any two-handed weapon.
- **One-handed weapon**: If replacing a two-handed weapon, the two-handed weapon is removed (freeing both weapon and shield slots).

### Consumables

Potions have a `duration` field (minutes). When used during a battle session (sent as `usedConsumableSlugs`), their `ItemStatBonus` entries are added to the effective stats calculation for that session. The `expiresAt` field on `UserInventory` tracks when a consumed item's buff ends.

### Soft Delete

`UserInventory.deletedAt` is used for soft deletion. Items are never hard-deleted, preserving history. Queries for active items filter `WHERE deleted_at IS NULL`.

---

## 8. Workout Plan Generation

**Service:** `WorkoutPlanGeneratorService`

### Split Selection by Training Frequency

| Frequency | Days/Week | Split Type |
|-----------|-----------|------------|
| none | 3 | Push/Pull/Legs |
| light | 3 | Push/Pull/Legs |
| moderate | 4 | Upper/Lower |
| heavy | 5 | Push/Pull/Legs |

Day count to split mapping:
- 1-2 days: Full Body
- 3 days: Push/Pull/Legs
- 4 days: Upper/Lower
- 5-6 days: Push/Pull/Legs (doubled or with accessories)

### Muscle Synergy Rules

When generating a strength plan, primary muscle groups pull in synergistic secondary groups:

```
chest    -> also trains triceps
back     -> also trains biceps
quads    -> also trains shoulders
hamstrings -> also trains shoulders
glutes   -> also trains shoulders
```

### Exercise Selection Algorithm

1. Query exercises matching target muscle groups
2. Filter by difficulty (beginner exercises for beginners, all for advanced)
3. Exclude recently used exercises (from last session of same muscle group) for variety
4. Sort by priority: compound movements first (priority 1-2), isolation last (3-5)
5. Select up to `workout_exercises_per_session` exercises (default 7)

### Difficulty Adaptation

After each battle session, the `performance_tier` is checked:
- If `failed` (< 50% completion): next generated plan gets `difficulty_modifier = 0.8` (20% easier)
- Otherwise: `difficulty_modifier = 1.0` (normal)

The modifier is stored on the `WorkoutPlan` entity.

### Cardio Plans (Running, Cycling)

Progressive overload based on recent performance:
1. Analyze last 14 days of running/cycling data from `WorkoutLog`
2. Calculate average distance
3. Apply safe progression rate: +10% per week (configurable)
4. Set target distance and duration

Cycling distances default to 3x the running distance equivalent.

### Activity-Specific Plan Generation

| Activity | Method | Duration Scaling |
|----------|--------|-----------------|
| Strength | Split template + exercise selection | 60 min |
| Running | Progressive distance targets | Based on history |
| Cycling | Progressive distance (3x running) | 1.5x running duration |
| Swimming | Exercise catalog selection | Beginner 20min, Intermediate 35min, Advanced 50min |
| Yoga | Priority-ordered (warm-up -> cool-down) | Beginner 20min, Intermediate 40min, Advanced 60min |
| Combat | Round-based (3 min work / 1 min rest) | Beginner 24min (6 rounds), Intermediate 32min (8), Advanced 40min (10) |
| HIIT | Circuit (30s work / 15s rest) | Beginner 3 rounds, Intermediate 4, Advanced 5 |
| Other | Generic category exercises | Beginner 30min, Intermediate 45min, Advanced 60min |

### Reward Tiers

Each plan has `reward_tiers` JSON with bronze/silver/gold thresholds:
- **Bronze** (~60% completion): Base XP
- **Silver** (100% completion): Full XP
- **Gold** (~130% completion): Bonus XP

For strength plans, tiers are based on exercise completion count. For cardio, on distance achieved vs target.

---

## 9. Battle System (Core Gameplay Loop)

### High-Level Flow

```
1. Select activity -> 2. Generate plan -> 3. Choose mode -> 4. Start battle ->
5. Perform exercises (warm-up optional) -> 6. Submit results -> 7. Calculate results -> 8. Award rewards
```

### Battle Modes

| Mode | Mob Selection | Mob Stats | Rarity Pool |
|------|--------------|-----------|-------------|
| Custom | Near user level +/-2 | Base HP/XP | common, uncommon, rare |
| Recommended | Near user level +/-2 | Base HP/XP | common, uncommon, rare |
| Raid | Near user level +/-2, prefer rarer | +30% HP, +30% XP | rare, epic, legendary |

### Mob Selection (BattleMobService)

1. Get user's current level from `CharacterStats`
2. Query mobs where `level BETWEEN (userLevel - 2) AND (userLevel + 2)`
3. Filter by allowed rarities based on mode
4. For raid mode: sort by rarity weight, pick from top third (rarest)
5. For other modes: pick randomly
6. Apply raid multiplier if applicable: `HP *= 1.3`, `XP *= 1.3`

### Session Lifecycle

```
Active -> Completed (via POST /api/battle/complete)
Active -> Abandoned (via POST /api/battle/abandon)
```

Only one active session per user at a time. Starting a new battle auto-abandons any existing active session.

### Exercise Execution

**Recommended/Raid mode**: Follow the generated workout plan exercises. After completing planned exercises, can switch to custom mode.

**Custom mode**: Pick exercises freely from the grouped exercise list. The API supports filtering by activityCategory, muscleGroup, search text, and difficulty.

Each submitted exercise includes:
```json
{
  "exerciseSlug": "bench-press",
  "sets": [
    {"setNumber": 1, "reps": 10, "weight": 80, "duration": 0},
    {"setNumber": 2, "reps": 8, "weight": 85, "duration": 0}
  ]
}
```

### Mid-Session Mob Defeat (POST /api/battle/next-mob)

When the client determines a mob is defeated (damage >= mob HP):
1. Current mob's XP added to session running total
2. Mobs defeated counter incremented
3. New mob selected via BattleMobService
4. Session updated with new mob data
5. Response includes new mob and running totals

### Client-Side Combat Display

The mobile app renders combat as:
- User's effective stats + equipment + active buffs displayed
- Damage tick every 10 seconds (configurable via `battle_tick_frequency`)
- Mob HP bar decreasing
- When mob HP reaches 0, POST /api/battle/next-mob, show victory animation, present next mob

### Server-Side Battle Result Calculation (BattleResultCalculator)

When POST /api/battle/complete is called:

#### Step 1: Effective Stats
```
effective_stats = base_stats (CharacterStats)
                + equipment_bonuses (all equipped items' ItemStatBonus)
                + passive_skill_bonuses (all unlocked passive skills' SkillStatBonus)
                + active_skill_bonuses (only skills in usedSkillSlugs)
                + consumable_bonuses (items in usedConsumableSlugs' ItemStatBonus)
```

#### Step 2: Training Volume
```
volume = SUM(weight * reps) for all sets across all exercises
  -- with anomaly filtering: weight capped at 300kg, reps capped at 100
training_score = calories + (volume / 100)
```

#### Step 3: Damage Calculation
```
seconds_per_tick = 60 / tick_frequency  (default tick_frequency=6, so 10 sec/tick)
ticks = floor(duration_seconds / seconds_per_tick)

damage_per_tick depends on activity type:
  strength:  STR * 0.8 + CON * 0.2
  dex:       DEX * 0.8 + CON * 0.2
  mixed:     ((STR + DEX) / 2) * 0.7 + CON * 0.3
  con:       CON * 0.7 + STR * 0.15 + DEX * 0.15
  default:   ((STR + DEX + CON) / 3) * 0.5

damage_per_tick *= base_damage_multiplier (default 1.0)
total_damage = round(ticks * damage_per_tick + training_score)
```

#### Step 4: Mobs Defeated & Raw XP
```
mobs_defeated = floor(total_damage / mob_hp)
xp_from_mobs = mobs_defeated * mob_xp_reward
```

#### Step 5: Completion Percentage

**For cardio plans** (with target_distance):
```
completion% = (actual_distance / target_distance) * 100
```

**For exercise-based plans:**
```
For each planned exercise:
  - sets_ratio = min(1.5, completed_sets / planned_sets) -- 60% weight
  - avg_reps_score = average(min(1.5, actual_reps / planned_reps_min) + weight_bonus) -- 40% weight
  - exercise_score = sets_ratio * 0.6 + avg_reps_score * 0.4

completion% = (sum of exercise_scores / planned_exercise_count) * 100
```

#### Step 6: Performance Tiers

| Tier | Completion | XP Awarded | Loot | Next Plan |
|------|-----------|------------|------|-----------|
| `failed` | < 50% | 0 XP | None | 20% easier |
| `survived` | 50-75% | base XP (xp_from_mobs) | None | Normal |
| `completed` | 75-100% | base XP | Normal loot | Normal |
| `exceeded` | > 100% | base XP + bonus | Normal loot | Normal |
| `raid_exceeded` | > 100% (raid) | base XP + raid bonus | Super loot | Normal |

**Exceeded bonus calculation:**
```
expected_mobs = mobs at 100% completion
extra_mobs = mobs_defeated - expected_mobs

Normal exceeded: bonus = 10% + (extra_mobs * 5%)
Raid exceeded:   bonus = extra_mobs * 10%

xp_awarded = round(xp_from_mobs * (1 + bonus))
```

#### Step 7: XP Award & Level Update

1. Create `ExperienceLog` entry with source "battle"
2. Update `CharacterStats.totalXp += xpAwarded`
3. Recalculate level via `LevelingService.getLevelForTotalXp()`
4. Update `CharacterStats.level`
5. Report whether level-up occurred

#### Step 8: Difficulty Adjustment

If performance tier is "failed", the system flags difficulty reduction. On next plan generation, `WorkoutPlanGeneratorService` checks the user's most recent completed session -- if it was "failed", the new plan gets `difficulty_modifier = 0.8` (20% easier).

### Battle Result Response (BattleResult DTO)

```json
{
  "performanceTier": "completed",
  "completionPercent": 87.5,
  "mobsDefeated": 3,
  "totalDamage": 4500,
  "xpFromMobs": 300,
  "bonusXpPercent": 0,
  "xpAwarded": 300,
  "lootEarned": true,
  "superLootEarned": false,
  "levelUp": false,
  "newLevel": 15,
  "totalXp": 12500,
  "message": "Victory! You completed the challenge."
}
```

---

## 10. Mob System

### Generation

**Command:** `app:generate-mobs --mobs-per-level=20`

Uses a base list of 100 creature names combined with 104 prefix modifiers to create unique variants per level. Prefixes adjust effective level (-2 to +2) and assign rarity.

### HP Formula

```
base_hp = 20 * level^1.5 + 40
actual_hp = base_hp * random(0.8, 1.2)  -- +/-20% variance
```

### XP Formula

```
xp_for_level = 4.2 * level^2 + 28 * level  (matches leveling curve)
base_xp = xp_for_level / 15 * rarity_multiplier
actual_xp = base_xp * random(0.9, 1.1)  -- +/-10% variance
```

This means approximately 15 common mob kills = 1 level worth of XP.

### Rarity XP Multipliers

| Rarity | Multiplier |
|--------|-----------|
| common | 1.0x |
| uncommon | 1.3x |
| rare | 1.6x |
| epic | 2.0x |
| legendary | 3.0x |

### Scale

2,000 mobs total: 20 per level across 100 levels. Mix of rarities with common being most frequent.

---

## 11. Media System

### Storage

Files stored locally in `public/uploads/{entityType}/` where entityType is one of: `items`, `skills`, `characters`, `mobs`.

Filename format: `{uuid}.{extension}` (e.g., `a1b2c3d4-e5f6-7890-abcd-ef1234567890.png`)

### Allowed Types

- image/png (.png)
- image/jpeg (.jpg)
- image/webp (.webp)

### DB Schema

The `media_files` table stores only the filename in `storage_path`. The full path is derived: `{entityType}/{storagePath}`. A polymorphic `entity_type` + `entity_id` pair allows linking to any game entity without hard foreign keys.

### Image Resizing (LiipImagineBundle)

4 filter sets configured:

| Filter | Size | Mode | Quality |
|--------|------|------|---------|
| `icon` | 64x64 | outbound (crop) | 85% |
| `thumbnail` | 150x150 | outbound (crop) | 85% |
| `medium` | 400x400 | inset (fit) | 85% |
| `large` | 800x800 | inset (fit) | 90% |

Originals served by nginx from `/uploads/{entityType}/{filename}`.
Resized variants served by LiipImagine from `/media/cache/resolve/{filter}/{entityType}/{filename}`.

The `MediaController` API response includes thumbnail URLs for icon, thumbnail, and medium sizes.

### Entities with Image Support

- `ItemCatalog.image` (ManyToOne -> MediaFile)
- `Skill.image` (ManyToOne -> MediaFile)
- `Exercise.image` (ManyToOne -> MediaFile)
- `Mob.image` (ManyToOne -> MediaFile)
- `Profession.image` (ManyToOne -> MediaFile)

---

## 12. Game Settings (Configurable via Sonata Admin)

All tunable game parameters are stored in the `game_settings` table as key-value pairs. Services read them via `GameSettingRepository.getAllAsMap()` (returns `array<string, string>`) and cache the result in-memory for the duration of each request.

### Categories

| Category | Purpose |
|----------|---------|
| xp_rates | XP earned per health metric unit |
| xp_caps | Daily XP limits |
| leveling | Level formula coefficients, max level |
| bonuses | Streak multipliers (planned) |
| workout | Plan generation parameters |
| battle | Damage formulas, performance thresholds |

### How Settings Are Used

1. **XpCalculationService** reads `xp_rate_*` keys to convert health metrics to XP
2. **LevelingService** reads `level_formula_quad`, `level_formula_linear`, `level_max` for the leveling curve
3. **BattleResultCalculator** reads `battle_*` keys for damage calculation, performance thresholds, and bonus percentages
4. **WorkoutPlanGeneratorService** reads `workout_*` keys for plan generation parameters

### Changing Settings

Settings are managed via the Sonata Admin "Game Settings" panel (under the Config group). Changes take effect immediately on the next request -- no code deploy required. Each setting has a human-readable description field explaining what it controls.

### Seeding

Two commands populate initial settings:
- `app:seed-battle-settings`: Battle tick frequency, damage factors, performance thresholds
- `app:seed-workout-settings`: Exercises per session, base XP, anomaly thresholds, XP rates
