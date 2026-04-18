# RPGFit — React Native Rewrite Design Spec

## Overview

Rewrite the Flutter mobile app (rpgfit-app) to React Native with Expo, preserving all existing functionality and extending to cover the full Symfony backend API. Cover both frontend and backend with tests.

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Framework | Expo SDK 52+ (dev builds) |
| Navigation | Expo Router (file-based) |
| Server state | TanStack Query v5 |
| Local state | Zustand |
| UI components | React Native Paper (Material Design 3) |
| HTTP client | Axios + interceptors |
| Token storage | expo-secure-store |
| Health iOS | react-native-health |
| Health Android | react-native-health-connect |
| Tests (unit/components) | Jest + React Native Testing Library |
| Tests (E2E) | Maestro |
| Language | TypeScript strict mode |

## Project Structure

```
rpgfit-mobile/
├── app/                          # Expo Router file-based navigation
│   ├── _layout.tsx               # Root layout (providers, theme)
│   ├── index.tsx                 # Entry redirect
│   ├── (auth)/                   # Group: public screens
│   │   ├── _layout.tsx
│   │   ├── login.tsx
│   │   └── registration.tsx
│   ├── (onboarding)/             # Group: onboarding
│   │   ├── _layout.tsx
│   │   └── index.tsx             # 9-step wizard
│   └── (main)/                   # Group: protected screens
│       ├── _layout.tsx           # Tab/drawer nav + auth guard
│       ├── profile.tsx
│       ├── health.tsx
│       ├── workouts/
│       │   ├── index.tsx         # Plans list
│       │   └── [id].tsx          # Plan details
│       ├── battle/
│       │   ├── index.tsx         # Active battle
│       │   └── start.tsx         # Start battle
│       ├── inventory.tsx
│       ├── equipment.tsx
│       └── levels.tsx
├── src/
│   ├── features/
│   │   ├── auth/
│   │   │   ├── api/              # Axios calls (login, register, oauth)
│   │   │   ├── hooks/            # useLogin, useRegister, useProfile
│   │   │   ├── types/            # User, LoginRequest, etc.
│   │   │   ├── stores/           # authStore (Zustand - token, user)
│   │   │   └── __tests__/
│   │   ├── onboarding/
│   │   │   ├── api/
│   │   │   ├── hooks/
│   │   │   ├── types/
│   │   │   └── __tests__/
│   │   ├── health/
│   │   │   ├── api/
│   │   │   ├── hooks/            # useHealthSync, useHealthSummary
│   │   │   ├── types/
│   │   │   ├── services/         # HealthKit/HealthConnect abstraction
│   │   │   └── __tests__/
│   │   ├── workout/
│   │   │   ├── api/
│   │   │   ├── hooks/
│   │   │   ├── types/
│   │   │   └── __tests__/
│   │   ├── battle/
│   │   │   ├── api/
│   │   │   ├── hooks/
│   │   │   ├── types/
│   │   │   └── __tests__/
│   │   ├── equipment/
│   │   │   ├── api/
│   │   │   ├── hooks/
│   │   │   ├── types/
│   │   │   └── __tests__/
│   │   └── leveling/
│   │       ├── api/
│   │       ├── hooks/
│   │       ├── types/
│   │       └── __tests__/
│   └── shared/
│       ├── api/
│       │   ├── client.ts         # Axios instance + config
│       │   └── interceptors.ts   # JWT attach, 401 handling
│       ├── components/           # Shared UI components
│       ├── hooks/                # useAuth guard, etc.
│       ├── theme/                # Paper MD3 theme (deepPurple)
│       ├── types/                # Shared types, enums
│       ├── config/               # Environment config (dev/staging/prod)
│       └── utils/
├── __tests__/                    # Maestro E2E flows
│   ├── auth.yaml
│   ├── onboarding.yaml
│   └── health.yaml
├── app.json
├── tsconfig.json
└── package.json
```

## Screens & Functionality

### Phase 1 — Port from Flutter (existing features)

#### Login (`/login`)
- Fields: email, password
- Validation: email required, password required
- On success: JWT saved to expo-secure-store, redirect to `/profile`
- Link to registration page

#### Registration (`/registration`)
- Fields: email, password, displayName, height, weight, workoutType, activityLevel, desiredGoal, characterRace
- Validation: email regex, password 8+, displayName 3+, height/weight > 0
- On success: redirect to `/login`

#### Onboarding (`/onboarding`)
- 9-step wizard with horizontal paging:
  1. Display Name (Latin chars, 3+ length)
  2. Height + Weight (positive numbers)
  3. Gender (male, female)
  4. Character Race (human, orc, dwarf, dark_elf, light_elf)
  5. Training Frequency (none, light, moderate, heavy)
  6. Workout Type (strength, cardio, crossfit, gymnastics, martial_arts, yoga)
  7. Lifestyle (sedentary, moderate, active, very_active)
  8. Preferred Workouts (multi-select chips)
  9. Summary + Submit
- Auth guard: shown when `onboardingCompleted === false`
- Submits to `POST /api/onboarding`

#### Profile (`/profile`)
- Display all user data from `GET /api/user`
- Character stats (STR, DEX, CON)
- Level and XP progress from `GET /api/levels/progress`
- Navigation buttons to other screens
- Logout: clear tokens, redirect to `/login`

#### Health Dashboard (`/health`)
- Request HealthKit / Health Connect permissions
- Sync: read device data, upload via `POST /api/health/sync`
- Display daily summary from `GET /api/health/summary?date=`
- Cards: steps, calories, distance, sleep, heart rate, workout minutes
- Last sync timestamp

### Phase 2 — New screens (backend parity)

#### Workouts (`/workouts`)
- List workout plans (`GET /api/workout/plans`) with status filter
- Generate new plan (`POST /api/workout/generate`)
- Plan details with exercises, sets, rest periods
- Start/complete/skip plan
- Log sets per exercise

#### Battle (`/battle`)
- Start battle: select plan + mode (custom, recommended, raid)
- Active battle: mob display, HP, damage tracking
- Complete battle: results with XP, tier, loot
- Multi-mob encounters via next-mob
- Abandon battle option

#### Equipment (`/equipment`)
- List equipped items (`GET /api/equipment`)
- Equip/unequip items by slot
- 13 equipment slots

#### Inventory (`/inventory`)
- List user items from profile data
- Filter by type: equipment, scroll, potion
- Show quantity, durability, rarity

#### Levels (`/levels`)
- Full XP table for 100 levels (`GET /api/levels/table`)
- Current progress with visual progress bar (`GET /api/levels/progress`)

## API Layer & Authorization

### Axios Setup
- Axios instance with baseURL from environment config
- Request interceptor: reads JWT from expo-secure-store, sets `Authorization: Bearer <token>`
- Response interceptor: on 401, clears tokens, redirects to `/login`
- Timeouts: 30s (dev), 15s (staging), 10s (prod)

### TanStack Query Integration
- Each API endpoint = dedicated hook with query/mutation
- Automatic caching, background refetch, error/loading states
- Query keys scoped per feature

### API Endpoints (matching Symfony backend)

**Auth:**
- `POST /api/login` — JWT login
- `POST /api/v1/auth/register` — registration
- `POST /api/auth/oauth` — OAuth login
- `POST /api/auth/link-account` — link OAuth provider
- `GET /api/profile` — basic profile
- `GET /api/user` — full user with stats/inventory/skills
- `POST /api/onboarding` — complete onboarding

**Health:**
- `POST /api/health/sync` — upload health data points
- `GET /api/health/summary?date=` — daily summary
- `GET /api/health/sync-status` — last sync per data type

**Workouts:**
- `POST /api/workout/generate` — generate plan
- `GET /api/workout/plans` — list plans
- `GET /api/workout/plans/{id}` — plan details
- `POST /api/workout/plans/{id}/start` — start plan
- `POST /api/workout/plans/{id}/complete` — complete plan
- `POST /api/workout/plans/{id}/skip` — skip plan
- `POST /api/workout/plans/{planId}/exercises/{exerciseId}/log` — log set

**Battle:**
- `POST /api/battle/start` — start battle
- `GET /api/battle/active` — active session
- `POST /api/battle/complete` — complete battle
- `POST /api/battle/abandon` — abandon battle
- `POST /api/battle/next-mob` — next mob in multi-mob
- `GET /api/exercises` — exercise catalog

**Equipment:**
- `POST /api/equipment/equip/{inventoryId}` — equip item
- `POST /api/equipment/unequip/{inventoryId}` — unequip item
- `GET /api/equipment` — list equipped items

**Leveling:**
- `GET /api/levels/table` — XP table (public)
- `GET /api/levels/progress` — current progress

**Mobs:**
- `GET /api/mobs` — list mobs (public)
- `GET /api/mobs/{slug}` — mob details (public)

## Auth Guard

- Root `_layout.tsx` checks for token in expo-secure-store
- No token: redirect to `(auth)` group
- Token + `onboardingCompleted === false`: redirect to `(onboarding)`
- Token + onboarding done: allow `(main)` group
- On 401 from any API call: clear tokens, redirect to login

## Environment Configuration

```typescript
const configs = {
  development: {
    apiBaseUrl: 'https://rpgfit.local:8443',
    apiTimeout: 30000,
    enableLogging: true,
  },
  staging: {
    apiBaseUrl: 'https://staging-api.rpgfit.com',
    apiTimeout: 15000,
    enableLogging: true,
  },
  production: {
    apiBaseUrl: 'https://api.rpgfit.com',
    apiTimeout: 10000,
    enableLogging: false,
  },
};
```

## Critical Business Logic & Constants

### Validation Rules

**Login:**
- Email: required, non-empty
- Password: required, non-empty

**Registration:**
- Email regex: `^[^@\s]+@[^@\s]+\.[^@\s]+$`
- Password: minimum 8 characters
- Display name: minimum 3 characters
- Height: must be > 0 (backend: 50-300 cm)
- Weight: must be > 0 (backend: 20-500 kg)

**Onboarding:**
- Display name: 3-30 chars, Latin-only regex: `^[a-zA-Z0-9_\- ]+$`
- Height: 50-300 cm
- Weight: 20-500 kg
- Preferred workouts: minimum 1 item required

**Default form values:**
- workoutType: cardio (registration), strength (onboarding)
- activityLevel: moderate
- desiredGoal: maintain
- characterRace: human
- trainingFrequency: none
- lifestyle: sedentary

### Enums (must match backend exactly)

**CharacterRace:** human, orc, dwarf, dark_elf, light_elf (5 values)
**WorkoutType:** strength, cardio, crossfit, gymnastics, martial_arts, yoga (6 values)
**ActivityLevel:** sedentary, light, moderate, active, very_active (5 values)
**DesiredGoal:** lose_weight, gain_mass, maintain (3 values)
**Gender:** male, female (2 values)
**TrainingFrequency:** none, light, moderate, heavy (4 values)
**Lifestyle:** sedentary, moderate, active, very_active (4 values)

**HealthDataType (15 types):**
- steps (COUNT), heartRate (BEATS_PER_MINUTE), activeEnergyBurned (KILOCALORIE)
- distanceDelta (METER), weight (KILOGRAM), height (METER)
- bodyFatPercentage (PERCENT), sleepAsleep/Deep/Light/Rem (MINUTE)
- workout (MINUTE), flightsClimbed (COUNT), bloodOxygen (PERCENT), waterConsumption (LITER)

**BattleMode:** custom, recommended, raid
**SessionStatus:** active, completed, abandoned
**WorkoutPlanStatus:** pending, in_progress, completed, skipped
**ItemType:** equipment, scroll, potion
**ItemRarity:** common, uncommon, rare, epic, legendary
**EquipmentSlot:** weapon, shield, head, body, legs, feet, hands, bracers, bracelet, ring, shirt, necklace (12 slots)
**MuscleGroup:** chest, back, shoulders, biceps, triceps, quads, hamstrings, glutes, calves, core
**StatType:** str, dex, con

### Preferred Workout Options (multi-select chips)

running, powerlifting, crossfit, yoga, swimming, cycling, martial_arts, gymnastics, hiking, dancing, other

### Leveling System

**XP formula per level:** `xp(L) = floor(4.2 * L^2 + 28 * L)`
- L=1: 32 XP, L=10: 700 XP, L=30: 4620 XP, L=70: 20580 XP
- Max level: 100 (configurable)
- Cumulative XP = sum of xp(1) through xp(level)

**Level tiers (10 tiers):**
Novice → Apprentice → Journeyman → Veteran → Expert → Master → Champion → Legend → Mythic → Transcendent

### XP Award System (health data sync)

**XP conversion rates (all rounded down):**
- Steps: value / 1000 * rate_steps
- Active energy: value / 100 * rate_active_energy
- Workout: value / 10 * rate_workout
- Distance: value / 1000 * rate_distance (value in metres)
- Sleep (all types): min(value, max_hours * 60) / 60 * rate_sleep
- Flights: value * rate_flights
- Non-XP types (heart_rate, weight, height, body_fat, blood_oxygen, water): 0

**Daily XP cap:** 3000 (configurable via game_settings)

### Battle System

**Damage formula per set:**
```
IF reps > 0 AND weight > 0:
  damage = reps * weight * STRENGTH_DAMAGE_COEFFICIENT (0.1)
ELSE IF reps > 0:
  damage = reps (bodyweight exercise)
ELSE:
  damage = 0
IF duration > 0:
  damage += duration * CARDIO_DAMAGE_COEFFICIENT (0.5)
RETURN round(damage)
```

**Damage per tick by activity type:**
```
strength:  STR * 0.8 + CON * 0.2
dex:       DEX * 0.8 + CON * 0.2
mixed:     ((STR + DEX) / 2) * 0.7 + CON * 0.3
con:       CON * 0.7 + STR * 0.15 + DEX * 0.15
default:   ((STR + DEX + CON) / 3) * 0.5
```

**Mob selection:**
- Range: user level ±2
- Allowed rarities: custom/recommended = common, uncommon, rare; raid = rare, epic, legendary
- Raid mode: RAID_MULTIPLIER = 1.3 (+30% HP and XP)
- Rarity weights: common=1, uncommon=2, rare=3, epic=4, legendary=5

**Mob HP formula:** `base_hp = 20 * level^1.5 + 40` (±20% variance)
**Mob XP formula:** `xp_for_level / 15 * rarity_multiplier` (±10% variance)
**Rarity XP multipliers:** common=1.0, uncommon=1.3, rare=1.6, epic=2.0, legendary=3.0

**Performance tiers:**
- `failed` (< 50%): 0 XP, flag next plan 20% easier (difficulty_modifier = 0.8)
- `survived` (50-75%): base XP only
- `completed` (75-100%): base XP + loot eligible
- `exceeded` (> 100%): base * (1 + 10% + extra_mobs * 5%)
- `raid_exceeded` (> 100% in raid): base * (1 + extra_mobs * 10%)

**Reward tiers (workout completion):**
- Bronze: ≥3 exercises completed → bronze XP (partial: bronze XP / 2)
- Silver: all exercises completed → silver XP
- Gold: all exercises + extra sets → gold XP
- Tier bonus XP: gold +30%, silver +15%, bronze 0%

**Completion % calculation:**
- Cardio: (actual_distance / target_distance) * 100
- Exercises: per-exercise score = sets_ratio * 0.6 + reps_quality * 0.4
  - sets_ratio = completed/planned (capped at 1.5)
  - reps_quality includes weight bonus: if weight > benchmark, add min(0.5, (weight - benchmark) / benchmark)
  - Total = (sum of exercise_scores / exercise_count) * 100

**Anomaly filtering (training volume):**
- Weight capped at 300 kg
- Reps capped at 100
- Negative values clamped to 0

### Stat Calculation (Initial Character Stats)

**Target total:** exactly 30 stat points
**Base:** 5 per stat (15 total), remaining 15 from bonuses

**Workout type bonuses [STR, DEX, CON]:**
- strength: [5, 1, 2], cardio: [1, 3, 5], crossfit: [3, 3, 3]
- gymnastics: [1, 5, 2], mixed: [1, 5, 2], martial_arts: [3, 4, 2], yoga: [1, 5, 2]

**Lifestyle bonuses [STR, DEX, CON]:**
- sedentary: [0, 0, 0], moderate: [0, 1, 1], active: [1, 1, 1], very_active: [1, 1, 2]

**Training frequency bonuses [STR, DEX, CON]:**
- none: [0, 0, 0], light: [0, 0, 1], moderate: [1, 0, 1], heavy: [1, 1, 1]

**Normalization:** if sum > 30 proportionally reduce (floor), add remainder to highest; if sum < 30 add deficit to highest

### Equipment Slot Rules

- Default: 1 item per slot, equipping replaces existing
- Ring: max 2, oldest unequipped when max reached
- Bracelet: max 2, same as ring
- Two-handed weapon: occupies weapon slot + removes shield
- Shield: cannot coexist with two-handed, removes two-handed if present
- One-handed weapon: replaces two-handed, freeing both slots

### Health Data Sync Pipeline

1. Check last sync time from local storage (key: `health_last_sync_time`, ISO-8601 UTC)
2. If never synced, default window = last 7 days
3. Fetch data from device (HealthKit/Health Connect) with removeDuplicates
4. If data points exist, upload via `POST /api/health/sync`
5. Backend deduplicates via (user_id, external_uuid) unique constraint
6. Backend returns: accepted count, skipped (duplicate) count, XP awarded
7. Update last sync timestamp in local storage
8. Fetch today's summary from `GET /api/health/summary?date=YYYY-MM-DD`
9. All timestamps sent as UTC ISO-8601

### Health Sync Strategy Per Platform

**iOS — Native HealthKit Background Delivery:**
- Use `HKObserverQuery` + `enableBackgroundDeliveryForType` via Expo config plugin
- Push-model: HealthKit wakes the app when new data arrives (steps, heart rate, workout ended, etc.)
- App gets ~30 seconds to read new data and POST to backend
- Works even when app is killed — iOS relaunches it in background
- Frequency options: immediate, hourly, daily (use immediate for workout types, hourly for steps/heart rate)
- No polling needed — zero battery impact

**Android — Foreground Polling via Health Connect:**
- During active workout (battle in progress): poll every **10 seconds**
  - Enables near-real-time damage calculation from exercise data
  - Uses Health Connect Changes API to fetch only deltas
- No active workout (idle): poll every **5 minutes**
  - Catches steps, heart rate, sleep data accumulated between app opens
- On app open (AppState 'active'): immediate sync via Changes API
- Manual "Sync Now" button always available
- Polling managed by a HealthSyncService with configurable interval based on workout state

### Health Summary Display Formatting

- Distance: value / 1000, show as km with 2 decimal places
- Sleep/Workout: convert minutes to `${hours}h ${mins}m` format
- Steps: integer
- Active energy: kcal
- Heart rate: bpm

### OAuth Flow (prepared, not yet in UI)

**Providers:** Google, Apple, Facebook
**Flow:**
1. Provider returns auth token
2. Client sends to `POST /api/auth/oauth` with {provider, providerUserId, email, token}
3. Backend logic: find LinkedAccount → return JWT; else find User by email → auto-link + JWT; else create new User + LinkedAccount (onboardingCompleted=false)
4. Response: {token, onboardingCompleted, isNewUser}
5. If onboardingCompleted=false → redirect to /onboarding

**Account linking:** `POST /api/auth/link-account` (requires auth) — idempotent, already linked = success

### Registration vs OAuth Difference

- **Registration:** collects all profile data upfront, sets `onboardingCompleted = true` immediately
- **OAuth:** creates minimal user, sets `onboardingCompleted = false`, user must complete onboarding wizard

### Profession System (16 categories, 3 tiers each = 48 professions)

Categories: Combat, Running, Walking, Cycling, Swimming, Strength, Flexibility, Cardio, Dance, Winter Sports, Racquet Sports, Team Sports, Water Sports, Outdoor, Mind & Body, Other

Each has Lineage 2-inspired 3-tier class transfer (e.g., Combat = Fighter → Gladiator → Titan Breaker)

### Game Settings (44+ configurable parameters)

Stored in `game_settings` table as key-value pairs. Categories:
- xp_rates (7 keys), xp_caps (6 keys), leveling (4 keys)
- bonuses (6 streak/time-based), workout (9 keys), battle (12 keys)

### TODOs from Flutter Code

- Login page: `TODO: integrate Google Sign-In SDK`
- Login page: `TODO: integrate Apple Sign-In SDK`
- Login page: `TODO: integrate Facebook Login SDK`
- OAuth buttons currently show "coming soon" snackbars

## Testing Strategy

### React Native App

**Unit tests (Jest):**
- Types, enums, utility functions
- Zustand store logic (auth store)
- API request/response model transformations

**Hook tests (Jest + RNTL renderHook):**
- useLogin, useRegister, useProfile
- useHealthSync, useHealthSummary
- useOnboarding
- TanStack Query hooks with mocked API

**Component tests (RNTL):**
- Login form: validation, submission, error display
- Registration form: all fields, validation
- Onboarding wizard: step navigation, field validation, submission
- Profile page: data display, logout
- Health dashboard: permission flow, sync, summary display

**E2E tests (Maestro YAML):**
- Auth flow: register → login → profile
- Onboarding flow: complete all 9 steps
- Health flow: grant permissions → sync → view summary

### Symfony Backend (extending existing 70 tests)

**New controller tests:**
- WorkoutPlanController: generate, list, start, complete, skip, log
- BattleController: start, complete, abandon, next-mob
- EquipmentController: equip, unequip, list
- LevelController: table, progress
- MobController: list, detail

**Repository tests:**
- HealthDataPoint aggregation queries
- WorkoutPlan filtering/pagination
- UserInventory with soft deletes

**Service edge cases:**
- BattleService: damage calculation, raid mode scaling
- BattleResultCalculator: all performance tiers
- EquipmentService: slot conflict rules, two-handed logic
- LevelingService: boundary levels (1, 100), XP overflow
