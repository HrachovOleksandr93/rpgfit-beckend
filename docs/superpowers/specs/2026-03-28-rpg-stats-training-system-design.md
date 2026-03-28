# RPG Character Stats & Training System Design

## Overview

Add RPG character statistics, experience tracking, workout logging, and exercise configuration system to the backend. Today's scope: entities, relationships, migrations, Sonata Admin CRUD. No calculation logic — that comes tomorrow.

All health data from Health API is preserved. Workout logs link to HealthDataPoint when the source is Health API.

## New Entities

### 1. CharacterStats (1:1 with User)

Flat table storing current character attributes.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| user | User (OneToOne) | Owning side, unique FK |
| strength | int, default 0 | STR stat |
| dexterity | int, default 0 | DEX stat |
| constitution | int, default 0 | CON stat |
| updatedAt | DateTimeImmutable | Auto-updated |

### 2. ExperienceLog (N:1 with User)

Records each XP gain event.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| user | User (ManyToOne) | FK |
| amount | int | XP gained |
| source | string | Category: workout, achievement, bonus, etc. |
| description | string, nullable | Human-readable detail |
| earnedAt | DateTimeImmutable | When XP was earned |

### 3. WorkoutLog (N:1 with User, N:1 with HealthDataPoint nullable)

Training session log. Populated from Health API data and/or manual entry.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| user | User (ManyToOne) | FK |
| workoutType | string | From Health API (running, strength, yoga, etc.) |
| durationMinutes | float | Session length |
| caloriesBurned | float, nullable | From Health API if available |
| distance | float, nullable | From Health API if available |
| healthDataPoint | HealthDataPoint (ManyToOne), nullable | Link to raw Health API data |
| extraDetails | JSON, nullable | Future: sets, reps, weights |
| performedAt | DateTimeImmutable | When workout happened |
| createdAt | DateTimeImmutable | Record creation time |

### 4. WorkoutCategory (config, managed by admin)

Grouping of exercise types.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| name | string | Display name (Strength, Cardio, Yoga, Flexibility...) |
| slug | string, unique | URL/code-friendly identifier |
| description | text, nullable | Admin notes |

### 5. ExerciseType (N:1 with WorkoutCategory)

Individual exercises within a category.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| workoutCategory | WorkoutCategory (ManyToOne) | FK |
| name | string | Display name (Bench Press, Split, Running...) |
| slug | string, unique | URL/code-friendly identifier |
| description | text, nullable | Admin notes |

### 6. ExerciseStatReward (N:1 with ExerciseType)

Points awarded per stat for completing an exercise.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| exerciseType | ExerciseType (ManyToOne) | FK |
| statType | Enum(str, con, dex) | Which character stat |
| points | int | How many points awarded |

## New Enum

### StatType

String-backed PHP enum:
- `str` — Strength
- `con` — Constitution
- `dex` — Dexterity

## Relationships Diagram

```
User 1──1 CharacterStats
User 1──N ExperienceLog
User 1──N WorkoutLog
WorkoutLog N──1 HealthDataPoint (nullable)
WorkoutCategory 1──N ExerciseType 1──N ExerciseStatReward
```

## Sonata Admin

Full CRUD admin panels for all entities:

1. **UserAdmin** — list/edit users, inline view of character stats
2. **CharacterStatsAdmin** — view/edit stats per user
3. **ExperienceLogAdmin** — list/filter XP logs by user, source, date
4. **WorkoutLogAdmin** — list/filter workout logs, view linked health data
5. **WorkoutCategoryAdmin** — manage categories with inline exercise types
6. **ExerciseTypeAdmin** — manage exercises with inline stat rewards
7. **ExerciseStatRewardAdmin** — manage stat point configs

Admin features:
- List views with filters (user, date range, type)
- Forms for creating/editing all entities
- Inline editing where parent-child (category→exercises, exercise→rewards)

## Database Indexes

- `character_stats`: unique index on `user_id`
- `experience_logs`: index on `(user_id, earned_at)`
- `workout_logs`: index on `(user_id, performed_at)`
- `exercise_types`: unique index on `slug`
- `workout_categories`: unique index on `slug`
- `exercise_stat_rewards`: index on `exercise_type_id`

## Scope Boundaries

**In scope (today):**
- All entities, enums, relationships, migrations
- Sonata Admin installation and full CRUD for all entities
- Seed data for workout categories and sample exercises

**Out of scope (tomorrow):**
- XP calculation logic
- Stat point accumulation from workouts
- Achievement system
- Workout plan generation
- JSON extraDetails population logic
