# OAuth, Onboarding & Initial Stats Calculation Design

## Overview

Three interconnected features:
1. **OAuth Authentication** — Google/Apple/Facebook login + linked accounts table
2. **Onboarding Flow** — step-by-step questionnaire collected on client, sent as single POST
3. **Initial Stats Calculation** — service that distributes 30 stat points based on answers
4. **GET /api/user** — protected endpoint returning full user profile with stats, items, history

## 1. OAuth & Linked Accounts

### New Entity: LinkedAccount

Stores external OAuth provider accounts linked to a User. Allows a user to log in via multiple providers without losing data.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| user | User (ManyToOne) | FK |
| provider | Enum(google, apple, facebook) | OAuth provider |
| providerUserId | string(255) | User ID from the provider |
| email | string(180) | Email from the provider |
| linkedAt | DateTimeImmutable | When account was linked |

Unique constraint: (provider, providerUserId) — one provider account = one link
Index: (user_id) — find all linked accounts for a user

### New Enum: OAuthProvider

String-backed: google, apple, facebook

### New Enum: Gender

String-backed: male, female

### OAuth Flow

1. Mobile app authenticates with provider (Google/Apple/Facebook SDK)
2. App receives provider token + user info (email, provider user ID)
3. App sends to backend: `POST /api/auth/oauth` with `{ provider, token, providerUserId, email }`
4. Backend verifies token (placeholder — actual verification when keys are obtained)
5. Backend looks up LinkedAccount by (provider, providerUserId):
   - **Found** → return JWT for the linked User
   - **Not found** → check if email matches existing User:
     - **Email match** → auto-link account, return JWT
     - **No match** → create new User (onboarding_completed=false), link account, return JWT
6. After login, if `onboarding_completed=false` → app redirects to onboarding

### Link Account Endpoint

`POST /api/auth/link-account` (authenticated) — link additional provider to current user.

### User Entity Changes

Add to User:
- `gender` (Gender enum, nullable) — set during onboarding
- `onboardingCompleted` (bool, default false) — tracks if questionnaire is done
- `preferredWorkouts` (json, nullable) — array of preferred workout slugs (running, powerlifting, crossfit, etc.)
- `trainingFrequency` (Enum, nullable) — how often they train
- `lifestyle` (Enum, nullable) — daily activity outside training

### New Enum: TrainingFrequency

String-backed:
- none — "I don't exercise"
- light — "1-2 times per week"
- moderate — "2-4 times per week"
- heavy — "More than 4 times per week"

### New Enum: Lifestyle

String-backed:
- sedentary — "Mostly sitting (office, remote work)"
- moderate — "Mixed (some walking, light activity)"
- active — "On feet most of the day"
- very_active — "Physically demanding job/lifestyle"

## 2. Onboarding Flow

### Questionnaire Steps (client-side, all collected before sending)

1. **Display name** — Latin characters only, check uniqueness
2. **Height** (cm)
3. **Weight** (kg)
4. **Gender** — Male / Female
5. **Character race** — Human, Orc, Dwarf, Dark Elf, Light Elf
6. **Training frequency** — none / 1-2/week / 2-4/week / 4+/week
7. **Preferred training style** — strength, cardio, crossfit, gymnastics, martial_arts, yoga (pick primary)
8. **Lifestyle** — sedentary / moderate / active / very_active
9. **Preferred workouts** — multi-select: running, powerlifting, crossfit, yoga, swimming, cycling, martial_arts, gymnastics, hiking, dancing, other

### Endpoint

`POST /api/onboarding` (authenticated)

Request body:
```json
{
  "displayName": "HeroName",
  "height": 180.5,
  "weight": 75.0,
  "gender": "male",
  "characterRace": "orc",
  "trainingFrequency": "moderate",
  "workoutType": "strength",
  "lifestyle": "moderate",
  "preferredWorkouts": ["powerlifting", "crossfit", "running"]
}
```

Validation:
- displayName: NotBlank, Latin-only regex `^[a-zA-Z0-9_]+$`, Length(3-30), unique
- height: Positive, Range(50-300)
- weight: Positive, Range(20-500)
- gender: valid enum
- characterRace: valid enum
- trainingFrequency: valid enum
- workoutType: valid enum
- lifestyle: valid enum
- preferredWorkouts: array of strings, at least 1

Response 200: full user profile with calculated stats

## 3. Initial Stats Calculation

### Service: StatCalculationService

Distributes 30 total points across STR, DEX, CON.

**Base allocation:** 5 points each (minimum guaranteed) = 15 base
**Distributable pool:** 15 points based on answers

**Distribution matrix (workoutType → stat bonus):**

| workoutType | STR | DEX | CON |
|-------------|-----|-----|-----|
| strength | +5 | +1 | +2 |
| cardio | +1 | +3 | +5 |
| crossfit | +3 | +3 | +3 |
| gymnastics | +1 | +5 | +2 |
| martial_arts | +3 | +4 | +2 |
| yoga | +1 | +5 | +2 |

**Lifestyle modifier:**

| lifestyle | STR | DEX | CON |
|-----------|-----|-----|-----|
| sedentary | +0 | +0 | +0 |
| moderate | +0 | +1 | +1 |
| active | +1 | +1 | +1 |
| very_active | +1 | +1 | +2 |

**Training frequency modifier:**

| frequency | STR | DEX | CON |
|-----------|-----|-----|-----|
| none | +0 | +0 | +0 |
| light | +0 | +0 | +1 |
| moderate | +1 | +0 | +1 |
| heavy | +1 | +1 | +1 |

**Normalization:** After summing base + workout + lifestyle + frequency, normalize to exactly 30 total. If sum > 30, proportionally reduce. If sum < 30, distribute remainder to highest stat.

### Flow:
1. OnboardingService receives validated data
2. Updates User fields (displayName, height, weight, gender, etc.)
3. Calls StatCalculationService.calculateInitialStats()
4. Creates CharacterStats record (1:1 with User)
5. Sets user.onboardingCompleted = true
6. Returns updated User with stats

## 4. GET /api/user Endpoint

`GET /api/user` (authenticated) — returns ONLY the authenticated user's full profile.

Response:
```json
{
  "id": "uuid",
  "login": "email@example.com",
  "displayName": "HeroName",
  "gender": "male",
  "height": 180.5,
  "weight": 75.0,
  "characterRace": "orc",
  "workoutType": "strength",
  "trainingFrequency": "moderate",
  "lifestyle": "moderate",
  "activityLevel": "active",
  "desiredGoal": "maintain",
  "preferredWorkouts": ["powerlifting", "crossfit"],
  "onboardingCompleted": true,
  "stats": {
    "strength": 12,
    "dexterity": 8,
    "constitution": 10
  },
  "inventory": [...],
  "skills": [...],
  "totalXp": 0,
  "createdAt": "...",
  "updatedAt": "..."
}
```

Security: user can ONLY access their own data. No user ID in URL — always from JWT token.

## Scope

**In scope:**
- LinkedAccount entity + OAuthProvider enum
- Gender, TrainingFrequency, Lifestyle enums
- User entity changes (new fields)
- OAuth controller (POST /api/auth/oauth, POST /api/auth/link-account)
- Onboarding controller (POST /api/onboarding)
- StatCalculationService
- OnboardingService
- GET /api/user controller
- Sonata Admin for LinkedAccount
- Unit + functional tests
- Migration

**Out of scope:**
- Actual OAuth token verification (placeholder — keys added later)
- Onboarding UI in Flutter (separate task)
