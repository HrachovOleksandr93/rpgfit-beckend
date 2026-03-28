# RPGFit Backend -- System Architecture

## 1. Project Overview

RPGFit is a gamified fitness application that turns real-world exercise into an RPG experience. Users create characters, complete workouts to earn XP and defeat mobs, collect equipment, unlock skills, and progress through professions -- all driven by actual health and exercise data synced from Apple HealthKit (iOS) and Google Health Connect (Android).

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.3 (strict types throughout) |
| Framework | Symfony 7.x |
| ORM | Doctrine ORM (attribute-based mapping) |
| Database | MySQL 8.0 |
| Cache | Redis 7 |
| Auth | JWT via `lexik/jwt-authentication-bundle` |
| Admin | SonataAdminBundle |
| API Docs | API Platform (read-only resources) |
| Media | LiipImagineBundle (image resizing) |
| Web Server | Nginx (Alpine) |
| Container | Docker Compose (4 services: php, nginx, mysql, redis) |
| IDs | UUID v4 everywhere (Symfony UID component) |

### Docker Services

```
rpgfit-php    -- PHP-FPM, app code mounted at /var/www/html
rpgfit-nginx  -- Nginx Alpine, ports 8080 (HTTP) / 8443 (HTTPS)
rpgfit-mysql  -- MySQL 8.0, port 3306, database: rpgfit
rpgfit-redis  -- Redis 7 Alpine, port 6379
```

Volumes: `mysql_data`, `redis_data`, `composer_cache`. Network: `rpgfit` bridge.

---

## 2. Directory Structure (DDD Layers)

```
src/
  Domain/           -- Entities, Enums, Value Objects (pure domain model, no framework deps)
    Activity/Entity/    -- ActivityCategory, ActivityType, Profession, ProfessionSkill, UserProfession
    Battle/Entity/      -- WorkoutSession
    Battle/Enum/        -- BattleMode, SessionStatus
    Character/Entity/   -- CharacterStats, ExperienceLog
    Character/Enum/     -- StatType
    Config/Entity/      -- GameSetting
    Health/Entity/      -- HealthDataPoint, HealthSyncLog
    Health/Enum/        -- HealthDataType, Platform, RecordingMethod
    Inventory/Entity/   -- ItemCatalog, ItemStatBonus, UserInventory
    Inventory/Enum/     -- EquipmentSlot, ItemRarity, ItemType
    Media/Entity/       -- MediaFile
    Mob/Entity/         -- Mob
    Skill/Entity/       -- Skill, SkillStatBonus, UserSkill
    Training/Entity/    -- ExerciseType, ExerciseStatReward, WorkoutCategory, WorkoutLog
    User/Entity/        -- User, LinkedAccount, UserTrainingPreference
    User/Enum/          -- ActivityLevel, CharacterRace, DesiredGoal, Gender, Lifestyle,
                           OAuthProvider, TrainingFrequency, WorkoutType
    Workout/Entity/     -- Exercise, SplitTemplate, WorkoutPlan, WorkoutPlanExercise, WorkoutPlanExerciseLog
    Workout/Enum/       -- Equipment, ExerciseDifficulty, ExerciseMovementType, MuscleGroup,
                           SplitType, WorkoutPlanStatus

  Application/      -- Services, DTOs (orchestration logic, no framework coupling)
    Battle/Service/     -- BattleMobService, BattleResultCalculator, BattleService
    Battle/DTO/         -- BattleResult
    Character/Service/  -- LevelingService, StatCalculationService, XpAwardService, XpCalculationService
    Health/Service/     -- HealthSummaryService, HealthSyncService
    Health/DTO/         -- HealthDataPointDTO, HealthSyncDTO
    Inventory/Service/  -- EquipmentService
    Media/Service/      -- MediaUploadService
    User/Service/       -- OnboardingService, RegistrationService
    User/DTO/           -- OnboardingDTO, RegistrationDTO
    Workout/Service/    -- WorkoutPlanGeneratorService

  Infrastructure/   -- Doctrine Repositories (one per entity, database access)
    Activity/Repository/
    Battle/Repository/
    Character/Repository/
    Config/Repository/
    Health/Repository/
    Inventory/Repository/
    Media/Repository/
    Mob/Repository/
    Skill/Repository/
    Training/Repository/
    User/Repository/
    Workout/Repository/

  Controller/       -- Symfony HTTP controllers (JSON API endpoints)
  Command/          -- Symfony Console commands (seeders, generators)
  Admin/            -- SonataAdmin classes (CRUD panels)

config/
  packages/
    doctrine.yaml          -- 12 Doctrine ORM mappings (one per bounded context)
    security.yaml          -- JWT firewalls, access control
    sonata_admin.yaml      -- Dashboard groups
    liip_imagine.yaml      -- 4 image filter sets
    lexik_jwt_authentication.yaml
    nelmio_cors.yaml
```

---

## 3. Database Schema

All tables use UUID v4 primary keys (`uuid` Doctrine type). Timestamps are `datetime_immutable`.

### 3.1 User Context

#### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| login | varchar(180) | unique, email |
| password | varchar | bcrypt/argon2 hash |
| display_name | varchar(30) | unique, nullable (OAuth flow) |
| height | float | nullable, cm |
| weight | float | nullable, kg |
| gender | varchar(10) | nullable, enum: male/female |
| workout_type | varchar(20) | nullable, enum: 7 values |
| activity_level | varchar(20) | nullable, enum: 5 values |
| desired_goal | varchar(20) | nullable, enum: 3 values |
| character_race | varchar(20) | nullable, enum: 5 values |
| onboarding_completed | boolean | default false |
| created_at | datetime_immutable | |
| updated_at | datetime_immutable | auto-updated via @PreUpdate |

#### `linked_accounts`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | indexed |
| provider | varchar(20) | enum: google/apple/facebook |
| provider_user_id | varchar(255) | |
| email | varchar(180) | |
| linked_at | datetime_immutable | |
| **Unique** | (provider, provider_user_id) | |

#### `user_training_preferences`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | unique (1:1) |
| training_frequency | varchar(20) | nullable, enum: 4 values |
| lifestyle | varchar(20) | nullable, enum: 4 values |
| primary_training_style | varchar(30) | nullable, WorkoutType enum |
| preferred_workouts | json | nullable, array of slugs |

### 3.2 Character Context

#### `character_stats`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | unique (1:1) |
| strength | integer | default 0 |
| dexterity | integer | default 0 |
| constitution | integer | default 0 |
| level | integer | default 1 |
| total_xp | integer | default 0 |
| updated_at | datetime_immutable | |

#### `experience_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| amount | integer | XP points |
| source | varchar(50) | e.g. "battle", "health_sync", "workout_plan" |
| description | varchar(255) | nullable |
| earned_at | datetime_immutable | |
| **Index** | (user_id, earned_at) | |

### 3.3 Health Context

#### `health_data_points`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| external_uuid | varchar(255) | nullable, from HealthKit/Health Connect |
| data_type | varchar(50) | HealthDataType enum (15 values) |
| value | float | |
| unit | varchar(50) | |
| date_from | datetime_immutable | |
| date_to | datetime_immutable | |
| platform | varchar(20) | enum: ios/android |
| source_app | varchar(255) | nullable |
| recording_method | varchar(20) | enum: automatic/manual |
| synced_at | datetime_immutable | |
| **Unique** | (user_id, external_uuid) | dedup constraint |
| **Index** | (user_id, data_type, date_from) | |

#### `health_sync_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| data_type | varchar(50) | HealthDataType enum |
| last_synced_at | datetime_immutable | |
| points_count | integer | |
| **Unique** | (user_id, data_type) | |

### 3.4 Training Context (Legacy/Reference)

#### `workout_categories`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| description | text | nullable |

#### `exercise_types`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| category_id | uuid (FK -> workout_categories) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| description | text | nullable |

#### `exercise_stat_rewards`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| exercise_type_id | uuid (FK -> exercise_types) | indexed |
| stat_type | varchar(10) | enum: str/dex/con |
| points | integer | |

#### `workout_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| workout_type | varchar(100) | |
| duration_minutes | float | |
| calories_burned | float | nullable |
| distance | float | nullable |
| health_data_point_id | uuid (FK) | nullable |
| extra_details | json | nullable |
| performed_at | datetime_immutable | |
| created_at | datetime_immutable | |
| **Index** | (user_id, performed_at) | |

### 3.5 Workout Context (Plan Generation)

#### `exercises`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(150) | |
| slug | varchar(150) | unique |
| primary_muscle | varchar(20) | MuscleGroup enum |
| secondary_muscles | json | array of MuscleGroup values |
| equipment | varchar(20) | Equipment enum (16 values) |
| difficulty | varchar(20) | ExerciseDifficulty enum |
| movement_type | varchar(20) | compound/isolation |
| priority | integer | 1=heavy compound, 5=light isolation |
| is_base_exercise | boolean | fundamentals like bench/squat/deadlift |
| description | text | nullable |
| image_id | uuid (FK -> media_files) | nullable |
| default_sets | integer | default 3 |
| default_reps_min | integer | default 8 |
| default_reps_max | integer | default 12 |
| default_rest_seconds | integer | default 90 |
| activity_category | varchar(50) | nullable, links to ActivityCategory slug |
| default_weight | float | nullable, average kg |
| default_pace | float | nullable, min/km for cardio |
| default_duration | integer | nullable, seconds for timed exercises |
| **Indexes** | primary_muscle, difficulty, priority, activity_category | |

#### `split_templates`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| split_type | varchar(20) | SplitType enum |
| days_per_week | integer | 2-6 |
| day_configs | json | array of {day, name, muscleGroups[]} |
| description | text | nullable |

#### `workout_plans`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| name | varchar(150) | |
| status | varchar(20) | enum: pending/in_progress/completed/skipped |
| activity_type | varchar(100) | nullable |
| target_muscle_groups | json | nullable |
| planned_at | datetime_immutable | |
| started_at | datetime_immutable | nullable |
| completed_at | datetime_immutable | nullable |
| target_distance | float | nullable, meters |
| target_duration | integer | nullable, minutes |
| target_calories | float | nullable |
| reward_tiers | json | nullable, {bronze:{threshold,xp}, silver:..., gold:...} |
| difficulty_modifier | float | default 1.0 (0.8 = 20% easier after failure) |
| created_at | datetime_immutable | |
| **Indexes** | (user_id, planned_at), (user_id, status) | |

#### `workout_plan_exercises`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| workout_plan_id | uuid (FK -> workout_plans) | |
| exercise_id | uuid (FK -> exercises) | |
| order_index | integer | position in workout |
| sets | integer | |
| reps_min | integer | |
| reps_max | integer | |
| rest_seconds | integer | |
| notes | varchar(255) | nullable |
| **Index** | (workout_plan_id, order_index) | |

#### `workout_plan_exercise_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| plan_exercise_id | uuid (FK -> workout_plan_exercises) | |
| set_number | integer | |
| reps | integer | nullable |
| weight | float | nullable, kg |
| duration | integer | nullable, seconds |
| notes | varchar(255) | nullable |
| completed_at | datetime_immutable | |

### 3.6 Skill Context

#### `skills`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| description | text | nullable |
| icon | varchar(255) | nullable |
| image_id | uuid (FK -> media_files) | nullable |
| required_level | integer | default 1 |
| skill_type | varchar(20) | "passive" or "active", default "passive" |
| duration | integer | nullable, minutes (active skills) |
| cooldown | integer | nullable, minutes (active skills) |
| race_restriction | varchar(20) | nullable, CharacterRace enum |
| tier | integer | nullable, 1/2/3 for profession skills |
| is_universal | boolean | default false |
| is_race_skill | boolean | default false |

#### `skill_stat_bonuses`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| skill_id | uuid (FK -> skills) | indexed |
| stat_type | varchar(10) | enum: str/dex/con |
| points | integer | |

#### `user_skills`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| skill_id | uuid (FK -> skills) | |
| unlocked_at | datetime_immutable | |
| **Unique** | (user_id, skill_id) | |

### 3.7 Inventory Context

#### `item_catalog`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| description | text | nullable |
| item_type | varchar(20) | enum: equipment/scroll/potion |
| rarity | varchar(20) | enum: 5 tiers |
| icon | varchar(255) | nullable |
| slot | varchar(20) | nullable, EquipmentSlot enum (12 values) |
| durability | integer | nullable |
| duration | integer | nullable, minutes (consumables) |
| two_handed | boolean | default false |
| stackable | boolean | default false |
| max_stack | integer | default 1 |
| image_id | uuid (FK -> media_files) | nullable |

#### `item_stat_bonuses`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| item_catalog_id | uuid (FK -> item_catalog) | indexed |
| stat_type | varchar(10) | enum: str/dex/con |
| points | integer | |

#### `user_inventory`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| item_catalog_id | uuid (FK -> item_catalog) | |
| quantity | integer | default 1 |
| equipped | boolean | default false |
| equipped_slot | varchar(20) | nullable, EquipmentSlot enum |
| current_durability | integer | nullable |
| obtained_at | datetime_immutable | |
| expires_at | datetime_immutable | nullable (consumables) |
| deleted_at | datetime_immutable | nullable (soft delete) |
| **Indexes** | (user_id, item_catalog_id), (user_id, equipped), (user_id, deleted_at) | |

### 3.8 Activity / Profession Context

#### `activity_categories`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| slug | varchar(50) | unique |
| name | varchar(100) | |
| description | text | nullable |

#### `activity_types`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| slug | varchar(100) | unique |
| name | varchar(100) | |
| flutter_enum | varchar(100) | exact Flutter HealthWorkoutActivityType |
| ios_native | varchar(150) | nullable, HKWorkoutActivityType |
| android_native | varchar(150) | nullable, ExerciseSessionRecord type |
| platform_support | varchar(20) | universal/ios_only/android_only |
| fallback_slug | varchar(100) | nullable |
| category_id | uuid (FK -> activity_categories) | indexed |

#### `professions`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| tier | integer | 1/2/3 |
| description | text | nullable |
| primary_stat | varchar(20) | StatType enum |
| secondary_stat | varchar(20) | StatType enum |
| category_id | uuid (FK -> activity_categories) | |
| image_id | uuid (FK -> media_files) | nullable |
| **Index** | (category_id, tier) | |

#### `profession_skills`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| profession_id | uuid (FK -> professions) | |
| skill_id | uuid (FK -> skills) | |
| **Unique** | (profession_id, skill_id) | |

#### `user_professions`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| profession_id | uuid (FK -> professions) | |
| unlocked_at | datetime_immutable | |
| active | boolean | default true |
| **Unique** | (user_id, profession_id) | |
| **Index** | (user_id, active) | |

### 3.9 Battle Context

#### `workout_sessions`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| user_id | uuid (FK -> users) | |
| workout_plan_id | uuid (FK -> workout_plans) | |
| mob_id | uuid (FK -> mobs) | nullable |
| mode | varchar(20) | enum: custom/recommended/raid |
| mob_hp | integer | nullable, adjusted for raid |
| mob_xp_reward | integer | nullable, adjusted for raid |
| started_at | datetime_immutable | |
| completed_at | datetime_immutable | nullable |
| total_damage_dealt | integer | default 0 |
| xp_awarded | integer | default 0 |
| status | varchar(20) | enum: active/completed/abandoned |
| health_data | json | nullable |
| used_skill_slugs | json | nullable |
| used_consumable_slugs | json | nullable |
| mobs_defeated | integer | default 0 |
| total_xp_from_mobs | integer | default 0 |
| completion_percent | float | nullable |
| performance_tier | varchar(20) | nullable: failed/survived/completed/exceeded/raid_exceeded |
| bonus_xp_percent | float | default 0 |
| loot_earned | boolean | default false |
| super_loot_earned | boolean | default false |
| **Indexes** | (user_id, status), (user_id, started_at) | |

### 3.10 Mob Context

#### `mobs`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | varchar(100) | |
| slug | varchar(100) | unique |
| level | integer | 1-100 |
| hp | integer | |
| xp_reward | integer | |
| description | text | nullable |
| image_id | uuid (FK -> media_files) | nullable |
| rarity | varchar(20) | nullable, ItemRarity enum |
| created_at | datetime_immutable | |
| **Index** | level | |

### 3.11 Config Context

#### `game_settings`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| category | varchar(50) | indexed, e.g. xp_rates/xp_caps/leveling/bonuses |
| `key` | varchar(100) | unique |
| value | varchar(255) | string, cast by consumer |
| description | varchar(500) | nullable |

### 3.12 Media Context

#### `media_files`
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| original_filename | varchar(255) | |
| storage_path | varchar(500) | filename only (e.g. "abc123.png") |
| mime_type | varchar(50) | |
| file_size | integer | bytes |
| entity_type | varchar(50) | items/skills/characters/mobs |
| entity_id | varchar(36) | nullable, polymorphic UUID |
| uploaded_at | datetime_immutable | |
| **Index** | (entity_type, entity_id) | |

---

## 4. Enum Reference

### User Enums
| Enum | Values |
|------|--------|
| `CharacterRace` | human, orc, dwarf, dark_elf, light_elf |
| `Gender` | male, female |
| `WorkoutType` | cardio, strength, mixed, crossfit, gymnastics, martial_arts, yoga |
| `ActivityLevel` | sedentary, light, moderate, active, very_active |
| `DesiredGoal` | lose_weight, gain_mass, maintain |
| `Lifestyle` | sedentary, moderate, active, very_active |
| `TrainingFrequency` | none, light, moderate, heavy |
| `OAuthProvider` | google, apple, facebook |

### Character Enums
| Enum | Values |
|------|--------|
| `StatType` | str, con, dex |

### Health Enums
| Enum | Values |
|------|--------|
| `HealthDataType` | STEPS, HEART_RATE, ACTIVE_ENERGY_BURNED, DISTANCE_DELTA, WEIGHT, HEIGHT, BODY_FAT_PERCENTAGE, SLEEP_ASLEEP, SLEEP_DEEP, SLEEP_LIGHT, SLEEP_REM, WORKOUT, FLIGHTS_CLIMBED, BLOOD_OXYGEN, WATER |
| `Platform` | ios, android |
| `RecordingMethod` | automatic, manual |

### Inventory Enums
| Enum | Values |
|------|--------|
| `ItemType` | equipment, scroll, potion |
| `ItemRarity` | common, uncommon, rare, epic, legendary |
| `EquipmentSlot` | weapon, shield, head, body, legs, feet, hands, bracers, bracelet, ring, shirt, necklace |

### Workout Enums
| Enum | Values |
|------|--------|
| `MuscleGroup` | chest, back, shoulders, biceps, triceps, quads, hamstrings, glutes, calves, core |
| `Equipment` | barbell, dumbbell, cable, machine, bodyweight, kettlebell, resistance_band, no_equipment, mat, pool, bike, rowing_machine, jump_rope, punching_bag, racquet, outdoor |
| `ExerciseDifficulty` | beginner, intermediate, advanced |
| `ExerciseMovementType` | compound, isolation |
| `SplitType` | full_body, push_pull_legs, upper_lower, bro_split, custom |
| `WorkoutPlanStatus` | pending, in_progress, completed, skipped |

### Battle Enums
| Enum | Values |
|------|--------|
| `BattleMode` | custom, recommended, raid |
| `SessionStatus` | active, completed, abandoned |

---

## 5. API Endpoints

All endpoints return JSON. Auth = JWT Bearer token required unless marked PUBLIC.

### Authentication
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/login` | PUBLIC | JSON login (login + password) -> JWT token |
| POST | `/api/auth/oauth` | PUBLIC | OAuth login (provider + token + email) -> JWT |
| POST | `/api/auth/link-account` | JWT | Link additional OAuth provider to current user |
| POST | `/api/registration` | PUBLIC | Register new user with full profile |

### User Profile
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/profile` | JWT | Basic user profile |
| GET | `/api/user` | JWT | Full profile + stats + inventory + skills + XP |
| POST | `/api/onboarding` | JWT | Complete onboarding questionnaire (one-time) |

### Health Data
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/health/sync` | JWT | Batch sync health data from mobile app |
| GET | `/api/health/summary?date=YYYY-MM-DD` | JWT | Daily aggregated health metrics |
| GET | `/api/health/sync-status` | JWT | Last sync timestamp per data type |

### Leveling
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/levels/table` | PUBLIC | Full XP table for all 100 levels |
| GET | `/api/levels/progress` | JWT | Current user's level progress |

### Workout Plans
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/workout/generate` | JWT | Generate personalized plan (optional: activityCategory, date) |
| GET | `/api/workout/plans?status=&limit=&offset=` | JWT | List user's plans |
| GET | `/api/workout/plans/{id}` | JWT | Plan detail with exercises |
| POST | `/api/workout/plans/{id}/start` | JWT | Start a pending plan |
| POST | `/api/workout/plans/{id}/complete` | JWT | Complete plan, award XP |
| POST | `/api/workout/plans/{id}/skip` | JWT | Skip a plan |
| POST | `/api/workout/plans/{planId}/exercises/{exerciseId}/log` | JWT | Log a single set |

### Battle System
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/battle/start` | JWT | Start battle (workoutPlanId + mode) |
| GET | `/api/battle/active` | JWT | Get active session |
| POST | `/api/battle/complete` | JWT | Submit results + complete session |
| POST | `/api/battle/abandon` | JWT | Abandon active session |
| POST | `/api/battle/next-mob` | JWT | Defeat current mob, get next |
| GET | `/api/exercises?activityCategory=&muscleGroup=&search=&difficulty=` | JWT | List exercises grouped by muscle |

### Equipment
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/equipment/equip/{inventoryId}` | JWT | Equip item from inventory |
| POST | `/api/equipment/unequip/{inventoryId}` | JWT | Unequip item |
| GET | `/api/equipment` | JWT | List equipped items |

### Media
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/media/upload` | JWT | Upload image (multipart: file + entityType + entityId) |
| GET | `/api/media/{id}` | JWT | Get file metadata + thumbnail URLs |

### Mobs
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/mobs?level=&level_min=&level_max=&rarity=&limit=&offset=` | PUBLIC | List mobs |
| GET | `/api/mobs/{slug}` | PUBLIC | Get single mob |

---

## 6. Console Commands

| Command | Description |
|---------|-------------|
| `app:generate-mobs` | Procedurally generate 2000 mobs (20/level x 100 levels) with HP/XP formulas. Options: `--mobs-per-level=20`, `--dry-run` |
| `app:import-mobs` | Import mobs from a CSV file. Options: `--file=path` |
| `app:seed-battle-settings` | Seed game_settings with battle-related keys (tick frequency, damage factors, thresholds) |
| `app:seed-exercises` | Seed the exercises table with 216 exercises across 16 activity categories |
| `app:seed-professions` | Seed activity_categories (16), professions (48), activity_types (99), and profession_skills (160) |
| `app:seed-skills` | Seed skills (39 unique) with stat bonuses: 5 race passives per race, 2 universal actives, profession skills by tier |
| `app:seed-workout-settings` | Seed game_settings with workout-related keys (exercises per session, XP rates, progression rate) |

---

## 7. Sonata Admin Panels

Dashboard groups and their panels:

### Users
- **Users** -- CRUD for User entity
- **Character Stats** -- CRUD for CharacterStats (1:1 with User)
- **Linked Accounts** -- View/manage OAuth linked accounts
- **Training Preferences** -- CRUD for UserTrainingPreference

### Training Config
- **Workout Categories** -- Manage WorkoutCategory (Cardio, Strength, etc.)
- **Exercise Types** -- Manage ExerciseType within categories
- **Stat Rewards** -- Configure ExerciseStatReward (STR/DEX/CON per exercise)

### Logs
- **Experience Logs** -- View/query ExperienceLog entries
- **Workout Logs** -- View/query WorkoutLog entries

### Skills
- **Skills** -- CRUD for Skill definitions
- **Skill Bonuses** -- Configure SkillStatBonus (stat points per skill)
- **User Skills** -- View/manage UserSkill (unlocked skills)

### Inventory
- **Item Catalog** -- CRUD for ItemCatalog (equipment/scroll/potion)
- **Item Bonuses** -- Configure ItemStatBonus (stat points per item)
- **User Inventory** -- View/manage UserInventory (owned items)

### Mobs
- **Mobs** -- CRUD for Mob definitions

### Professions
- **Activity Categories** -- 16 categories (Combat, Running, etc.)
- **Professions** -- 48 professions (3 tiers x 16 categories)
- **Activity Types** -- 99 Flutter health activity types with platform mapping
- **User Professions** -- View/manage UserProfession (unlocked professions)

### Workout Plans
- **Exercises** -- 216 exercises with muscle targeting, equipment, defaults
- **Split Templates** -- Training split configurations (PPL, upper/lower, etc.)
- **Workout Plans** -- Generated plans per user
- **Plan Exercises** -- Exercises within plans
- **Exercise Logs** -- Set-by-set performance logs

### Battle
- **Battle Sessions** -- WorkoutSession entities (active/completed/abandoned)

### Media
- **Media Files** -- Uploaded images for items/skills/characters/mobs

### Config
- **Game Settings** -- Key-value game parameters (XP rates, caps, formulas)

---

## 8. Configuration (game_settings keys)

All values stored as strings in the `game_settings` table, cast to int/float by consuming services.

### XP Rates (category: xp_rates)
| Key | Default | Description |
|-----|---------|-------------|
| xp_rate_steps | 10 | XP per 1,000 steps |
| xp_rate_active_energy | 15 | XP per 100 kcal burned |
| xp_rate_workout | 20 | XP per 10 minutes of workout |
| xp_rate_distance | 12 | XP per km traveled |
| xp_rate_sleep | 5 | XP per hour of sleep |
| xp_rate_flights | 3 | XP per flight climbed |
| xp_sleep_max_hours | 10 | Max sleep hours counted for XP |

### XP Caps (category: xp_caps)
| Key | Default | Description |
|-----|---------|-------------|
| xp_daily_cap | 3000 | Maximum XP awardable per day |

### Leveling (category: leveling)
| Key | Default | Description |
|-----|---------|-------------|
| level_formula_quad | 4.2 | Quadratic coefficient in xp(L) = quad*L^2 + linear*L |
| level_formula_linear | 28 | Linear coefficient |
| level_max | 100 | Maximum achievable level |

### Battle (category: battle)
| Key | Default | Description |
|-----|---------|-------------|
| battle_tick_frequency | 6 | Ticks per minute (1 tick = 10 seconds) |
| battle_base_damage_multiplier | 1.0 | Global damage scaling factor |
| battle_strength_damage_factor | 0.8 | STR contribution to damage per tick |
| battle_dex_damage_factor | 0.8 | DEX contribution to damage per tick |
| battle_con_damage_factor | 0.7 | CON contribution to damage per tick |
| battle_fail_threshold | 0.50 | Below 50% completion = failed |
| battle_partial_threshold | 0.75 | 50-75% = survived |
| battle_success_threshold | 1.00 | 75-100% = completed, >100% = exceeded |
| battle_overperform_bonus | 0.10 | Base +10% XP bonus when exceeding 100% |
| battle_overperform_per_mob | 0.05 | +5% XP per extra mob defeated (normal) |
| battle_raid_overperform_per_mob | 0.10 | +10% XP per extra mob defeated (raid) |

### Workout (category: workout)
| Key | Default | Description |
|-----|---------|-------------|
| workout_exercises_per_session | 7 | Target exercises per generated plan |
| workout_base_xp_per_workout | 100 | Base XP for completing a workout plan |
| workout_volume_anomaly_max_weight | 300 | Max kg per set before clamping (anomaly filter) |
| workout_volume_anomaly_max_reps | 100 | Max reps per set before clamping |
