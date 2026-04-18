# Master Implementation Plan — 2026-04-18

> **Goal:** Ship the backend foundation + 07 design + map + landing in
> coordinated phases. Tested through Playwright.
>
> **Input:**
> - `BA/outputs/04-code-audit.md` (4 critical DTO bugs — P0)
> - `BA/outputs/09-mob-bestiary.md` (mob extension design)
> - `BA/outputs/07-design-research.md` (WCAG, motifs, screens)
> - `docs/vision/product-decisions-2026-04-18.md` (D1-D5)
> - `docs/vision/design-principles-2026-04-18.md` (mass-audience rules)
> - `docs/vision/portals.md` (portal concept — static + dynamic)
> - `docs/lore-extracts/realms-canon.md` (6 realms authoritative)
> - `design/07-vector-field/` (ready-to-port mockups)

---

## Phase dependency graph

```
Phase 1 (Backend foundation)     ━━┓
  1A DTO fixes (4 critical bugs)   ┣━━ Phase 2 (Frontend)         ━━┓
  1B Races removal (D4)            ┃     2A 07 theme tokens          ┃
  1C Portal entity + API           ┃     2B Map screen + geo          ┃
  1D Mob entity extension          ┃     2C Apply theme to existing   ┃
  1E Artifact realm binding        ┃                                  ┣━━ Phase 4 (Tests)
                                   ┃                                  ┃     4A Playwright e2e
Phase 3 (Public surfaces)        ━━┫                                  ┃     4B Backend unit/integration
  3A Landing page (Symfony route) ━━                                 ━━
  3B Sonata admin restyle (CSS override)
```

Phase 1 & 3A can run concurrently (they touch disjoint files). Phase 2
needs 1C/1D done. Phase 4 last.

---

## Phase 1 — Backend foundation (architect agent)

### 1A — Fix 4 critical DTO bugs (blocking)

From `BA/outputs/04-code-audit.md`:

1. **HealthDataType case mismatch** — backend enum UPPERCASE, client lowercase.
   Fix: normalize client to UPPERCASE (align to backend enum), update tests.
   Files: `rpgfit-app/src/features/health/types/enums.ts`,
   `rpgfit-app/src/features/health/__tests__/healthApi.test.ts`.

2. **Battle `exerciseSlug` vs `exerciseId`** — backend reads `exerciseSlug`
   (`BattleService.php:125`), client sends `exerciseId`. Client Battle screen
   hardcodes `exercises: []`.
   Fix: client sends `exerciseSlug` from selected exercise; battle screen
   wires actual exercise list. Files: `rpgfit-app/src/features/battle/*`.

3. **Workout `activityCategory` case** — controller expects camelCase, client
   sends snake_case.
   Fix: align client to camelCase.
   Files: `rpgfit-app/src/features/workout/api/workoutApi.ts`.

4. **Health summary snake_case vs camelCase** — backend returns `active_energy`,
   client reads `activeEnergy`.
   Fix: client response type uses snake_case fields (matches backend).
   Files: `rpgfit-app/src/features/health/types/responses.ts`, Health screen.

### 1B — Races removal (D4)

- Doctrine migration: drop `character_race` column from `user` table.
- Remove `CharacterRace` enum from `src/Domain/User/Enum/CharacterRace.php`.
- Remove 5 race-passive skills from `SeedSkillsCommand` (Versatile Nature,
  Blood of the Horde, Mountain Born, Shadow Instinct, Sylvan Grace).
- Update `rpgfit-beckend/data/skills-design.md` to drop §1 "Race Passive
  Skills" table.
- Update `OnboardingDTO`, `OnboardingService` to skip race step.
- Client: remove race-pick UI from `app/(onboarding)/index.tsx`, update
  `auth/types/*`, update tests.
- Update `BUSINESS_LOGIC.md` to remove race references.

### 1C — Portal entity + API

**Domain (`src/Domain/Portal/`):**
- `Entity/Portal.php` with: `id`, `name`, `slug`, `type` (enum: static/dynamic),
  `realm` (enum from canon), `latitude`, `longitude`, `radius_m`, `tier`
  (1-3), `challenge_type`, `challenge_params` (json), `reward_artifact_slug`,
  `is_virtual_replica_of` (nullable FK to self), `created_by_user_id`
  (nullable), `expires_at` (nullable), `max_battles` (nullable).
- `Enum/PortalType.php` (static, dynamic, user_created).
- `Enum/Realm.php` (olympus, asgard, dharma, duat, nav, shiba, neutral).

**Application (`src/Application/Portal/`):**
- `Service/PortalService.php` — business logic.
- `Service/PortalSpawnService.php` — dynamic portal lifecycle.
- `DTO/PortalDTO.php` + request/response DTOs.

**Infrastructure (`src/Infrastructure/Portal/`):**
- `Repository/PortalRepository.php` with geo-query method
  `findWithinRadius(float $lat, float $lng, int $radiusKm): Portal[]` —
  uses Haversine (no PostGIS — we use MySQL, simple formula fine for P0).

**Controller (`src/Controller/PortalController.php`):**
- `GET /api/portals?lat={lat}&lng={lng}&radius_km={radius}` — geo-bounded
  list (max 20 portals per request; default radius 5km; max 25km to avoid
  API overload).
- `GET /api/portals/{slug}` — single portal detail.
- `POST /api/portals/dynamic` — create dynamic (requires user + Portal
  Creation Kit item consumed).
- `GET /api/portals/static` — curated static list (cached).

**Migration:** `migrations/VersionYYYYMMDD_AddPortals.php` — create `portal`
table with spatial-friendly index on `(latitude, longitude)`.

**Static portal seed:** `data/portals/static.yaml` — 15 landmarks from
`docs/vision/portals.md` (Галдхьопіген, Санторіні, Дікті, Пінд,
Ватнайокутль, Ерг-Шебі, Дніпро-Канев, Амазонія, Крим-Яйла, Гімалаї,
Теотіуакан, Ангкор-Ват + 3 more from major cities).

**Admin:** `src/Admin/PortalAdmin.php` for curating static portals in Sonata.

### 1D — Mob entity extension (from bestiary §1)

- Doctrine migration: add 8 columns to `mob` table per `09-mob-bestiary.md §1.1`
  (`realm`, `class_tier`, `behavior`, `archetype`, `visual_keywords` json,
  `is_champion` bool, `champion_decoration` string null, `accepts_champion` bool).
- Backfill existing 2000 mobs to `realm=neutral, class_tier=I, behavior=physical,
  archetype=beast, accepts_champion=true`.
- New enums under `src/Domain/Mob/Enum/`: `MobClassTier`, `MobBehavior`,
  `MobArchetype`. (`Realm` shared with Portal.)
- `src/Application/Mob/Service/MobSelectionService.php` with `maybeAsChampion()`
  method (10-15% roll, filter by `accepts_champion`, assign decoration).
- New command `app:seed-mobs` — reads `data/mobs/*.yaml`, upserts by slug.
- Port Olympus 40-mob pilot from `09-mob-bestiary.md §3` to `data/mobs/olympus.yaml`.
- Keep `app:import-mobs` (CSV) for one release; deprecation note in command help.

### 1E — Artifact realm binding (damage multiplier)

- `BattleResultCalculator` reads `artifact.realm` and `mob.realm`; if match
  → damage × 1.4. Per `BUSINESS_LOGIC.md` section to be added.
- Tests for match/no-match cases.

---

## Phase 2 — Frontend (foreground, per 07 Vector Field)

### 2A — 07 theme tokens

Create `rpgfit-app/src/shared/theme/` (if not exists):
- `colors.ts` — copper, cream, sage, cyan, rust, navy palette from
  `design/07-vector-field/concept.md`.
- `typography.ts` — IBM Plex Sans + IBM Plex Mono scale from research doc.
- `spacing.ts` — gap tokens (xs/sm/md/lg/xl).
- `paperTheme.ts` — adapt React Native Paper theme with custom colors.

Update `app/_layout.tsx` to provide the new theme.

### 2B — Map screen

- Install `react-native-maps` (or `expo-maps` if SDK 54 supports).
- Create `app/(main)/map.tsx` + `src/features/portals/` feature.
- `src/features/portals/api/portalsApi.ts` — typed portal API client.
- `src/features/portals/hooks/useNearbyPortals.ts` — TanStack Query with
  geolocation input; throttled to re-fetch only when user moves 500m+.
- Geolocation: `expo-location` — request permission, watchPosition, debounce.
- Map markers styled per 07 (copper circle, class-tier label).
- Tap marker → bottom sheet with portal detail + "Navigate to rupture" CTA.
- API coordinate strategy: send `lat, lng, radius_km=5`. Only re-fetch
  when user moves ≥ 500m or zooms to new area. Cache with 60s stale time.

### 2C — Apply 07 theme to existing screens

- Update `app/(onboarding)/index.tsx` — use new theme, add crosshair motif.
- Update `app/(auth)/registration.tsx` — remove race pick, apply theme.
- Update `app/(main)/profile.tsx` — match Character screen from 07 mockup.
- Update `src/features/health/HealthScreen.tsx` — match Settings pattern.
- Add missing screens: Event stub, Weekly Recap stub (future iterations).

---

## Phase 3 — Public surfaces

### 3A — Landing page (Symfony)

- Route `/` → `src/Controller/LandingController.php` — no auth.
- Template `templates/landing/index.html.twig` — 07 Vector Field design ported
  to server-side HTML (similar to design mockup).
- Content: hero "2042 · Year of the Rupture" + "Your Vector" pitch + 3
  feature blocks (Health · Rupture · Community) + CTA "Join the expedition".
- Static assets in `public/assets/landing/`.

### 3B — Sonata admin restyle

- `assets/admin/vector-field.css` — override Sonata's default bootstrap with
  07 palette + IBM Plex fonts.
- Load via `sonata_admin.yaml` or `base.html.twig` override.
- Keep Sonata layout intact, just colors + typography.

---

## Phase 4 — Tests (Playwright)

### 4A — Playwright e2e

- `tests/e2e/landing.spec.ts` — landing renders, CTA present, WCAG contrast
  pass (axe-core).
- `tests/e2e/app-onboarding.spec.ts` — Expo web, onboarding flow without race.
- `tests/e2e/app-map.spec.ts` — map renders with mocked geolocation, portals
  fetched from API.
- `tests/e2e/admin.spec.ts` — admin login, dashboard renders with new styles.

### 4B — Backend phpunit

- Unit: `PortalServiceTest`, `MobSelectionServiceTest` champion spawn.
- Integration: `PortalControllerTest` geo-query, `BattleControllerTest` DTO fix.

---

## Phase 1 → implementation order (architect-agent sequencing)

1. Feature branch: `feature/foundation-portals-mobs-races`
2. Bash: `git checkout -b feature/foundation-portals-mobs-races`
3. Phase 1A (DTO fixes) → commit `fix: align client DTO to backend contract (4 bugs from 04-audit)`
4. Phase 1B (races removal) → commit `feat: remove CharacterRace per D4 founder decision`
5. Phase 1D (mob extension + YAML seeder) → commit `feat: extend Mob entity with realm/class_tier/behavior/archetype/champion fields`
6. Phase 1C (Portal entity + API) → commit `feat: add Portal domain with static/dynamic types and geo-query`
7. Phase 1E (artifact realm multiplier) → commit `feat: realm-bound artifact +40% damage`
8. Run `vendor/bin/phpunit` — all green.

---

## Open decisions (flag to founder if relevant)

- **Dynamic portal creation quota:** 1 portal per user per 24h? 3?
- **Dynamic portal TTL:** 72h default? user-configurable?
- **Static portal global leaderboard:** "first 1000 to complete" per
  `portals.md §8.3`, or individual rewards? Impacts admin + API.
- **Map API overload budget:** targeting <1 req/60s steady state per user.
  Cache strategy: client-side 60s stale, server-side 5min at edge.
- **Mob legacy backfill:** keep 2000 "neutral" or retire after Olympus-40
  ships? (from bestiary §6 open questions)
- **Race skills refund:** existing users lose 6 stats — grant generic
  `+6 distribute freely` token? Or silently drop? (D4 didn't specify)

---

## Success criteria per phase

- **Phase 1 done:** backend tests green, no race refs in source, portal API
  returns JSON for `/api/portals?lat=50.45&lng=30.52&radius_km=5`, mob
  `app:seed-mobs` runs clean with Olympus 40.
- **Phase 2 done:** Expo web opens, map screen shows portals from API, 07
  theme visible on onboarding + profile + health.
- **Phase 3 done:** `/` renders landing with 07 design, admin login screen
  uses IBM Plex + copper accent.
- **Phase 4 done:** 8+ Playwright tests passing, coverage report generated.
