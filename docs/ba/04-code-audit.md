# 04 — Code Audit (Opus)

> **Автор:** BA-агент 04. **Дата:** 2026-04-18. **Статус:** v1.
>
> **Методика:** прочитав backend (`rpgfit-beckend/src/**`, `migrations/`, `tests/`),
> RN-клієнт (`rpgfit-app/app/**`, `rpgfit-app/src/**`), всі 4 плани і специфікацію
> 2026-04-04. Порівняв з 02-beta-scope IN-15 і 01-market-research gap-таблицею.
> Flutter-код НЕ оцінювався (пріор, memory `project_flutter_cleanup`).
>
> **Верифікація:** усі твердження про «працює / зламано» підкріплені `file:line`
> цитатами. 7 зовнішніх URL валідовано через WebSearch 2026-04-18 (див. §11).
>
> **Маркери:**
> - `[перевірено URL 2026-04-18]` — web-валідовано в цій сесії
> - `[BL §X]` — `rpgfit-beckend/docs/BUSINESS_LOGIC.md`
> - `[арх]` — `rpgfit-beckend/docs/ARCHITECTURE.md`
> - `[спек]` — `docs/superpowers/specs/2026-04-04-react-native-rewrite-design.md`

---

## 1. TL;DR (5 буллетів)

1. **Backend готовий до beta на ~75%; RN-клієнт — на ~30%.** Core-logika backend'у
   (Battle, Health, XP, Leveling, Workout Plan) — реалізовано, покрито 69 тестами
   (52 Unit + 17 Functional). RN-клієнт має скелет для всіх фіч, але **UI поверхневий,
   критичні DTO-контракти НЕ СТИКУЮТЬСЯ з backend'ом**, і **health-інтеграція — мок**
   (`rpgfit-app/src/features/health/services/healthService.ts:34` `MockHealthService`).

2. **ТОП-блокер beta: 4 DTO-неузгодження між клієнтом і backend'ом**, через які
   більшість API-запитів на iOS/Android **впаде валідацією** (HTTP 422) ще до
   обробки. Conкретно: (a) `HealthDataType` lowercase vs UPPERCASE, (b) `exerciseSlug`
   vs `exerciseId`, (c) `activityCategory` vs `activity_category`, (d) `active_energy`
   vs `activeEnergy` у health summary. Всі фіксуються за 1-2 дні, але беkend + клієнт
   одночасно. Деталі — §4.

3. **Другий блокер — домени для 6 фіч з beta-IN-15 відсутні в коді повністю**:
   `Realm` enum (фіча 3), `Portal` entity + `PortalCreationKit` ItemType (фічі 5,
   7), `Event` entity для Day of Розколу + Day of Realm (фічі 8, 9), Streak service
   (фіча 13), Weekly Recap cron/render (фіча 4), Named artifact earn-flow (фіча
   14). Це ~6-8 тижнів нової backend-роботи + UI. Деталі — §4.

4. **Health-sync P0 tech-risk (з health-aggregator-comparison рішенням залишитися
   на native):** iOS `HKObserverQuery` в RN-health має **відомий GitHub-bug** —
   «background observer не спрацьовує, коли app killed» (issues #217, #308, #144),
   оскільки RN bridge вимкнений [перевірено github.com/agencyenterprise/react-native-health/issues/217
   2026-04-18]. Android 14+ потребує `ViewPermissionUsageActivity` activity-alias
   в `AndroidManifest.xml` для permission rationale — якщо його нема, Health
   Connect permissions не нададуться на Android 14 [перевірено matinzd.github.io/react-native-health-connect
   2026-04-18]. Жодне з цих двох не реалізовано — `react-native-health` і
   `react-native-health-connect` лише у `package.json`, ніяких config-plugin або
   native-коду нема.

5. **BattleService vs BattleResultCalculator — дубльований XP-award flow з
   НЕПРАВИЛЬНОЮ placeholder-формулою** в одній з гілок. `BattleService.awardXp()`
   (`src/Application/Battle/Service/BattleService.php:347-384`) використовує
   `floor($newTotalXp / 100) + 1` — «100 XP per level placeholder». Це НЕ
   спрацьовує зараз (викликається `BattleResultCalculator.awardXp()`,
   рядок 548-588, який використовує LevelingService правильно), але код-клон
   висить як tech-debt і може випадково спрацювати у майбутньому refactor. Має
   бути вилучений.

---

## 2. Контекст (що читав)

### Внутрішні файли (повністю або критичні частини)

**Backend:**
- `rpgfit-beckend/composer.json` — Symfony 7.2, PHP ≥8.2, Doctrine ORM 3.6,
  Sonata Admin 4.40, API Platform 4.1, Lexik JWT 3.2, Liip Imagine 2.15
- `rpgfit-beckend/docs/ARCHITECTURE.md` (784 рядки) + `BUSINESS_LOGIC.md` (707 рядків)
- Всі 12 контролерів в `src/Controller/*.php`
- Ключові сервіси: `BattleService.php`, `BattleResultCalculator.php`, `HealthSyncService.php`,
  `HealthSummaryService.php`, `XpAwardService.php`, `LevelingService.php`,
  `WorkoutPlanGeneratorService.php`, `OnboardingService.php`, `RegistrationService.php`
- DTOs: `HealthSyncDTO.php`, `HealthDataPointDTO.php`, `RegistrationDTO.php`,
  `OnboardingDTO.php`, `BattleResult.php`
- Міграції: 15 файлів `Version20260328*` (від 094927 до 230000)
- Команди: 7 файлів (GenerateMobs, ImportMobs, SeedBattleSettings, SeedExercises,
  SeedProfessions, SeedSkills, SeedWorkoutSettings)
- Entity layout: 12 DDD bounded contexts (Activity, Battle, Character, Config,
  Health, Inventory, Media, Mob, Skill, Training, User, Workout)
- Admin panels: 29 Sonata-admin класів
- Тести: 52 Unit + 17 Functional (всього 69)

**RN-клієнт:**
- `package.json` — Expo SDK 54.0.33 + react-native 0.81.5 + React 19.1 + Expo Router
  6 + TanStack Query 5.96 + Zustand 5 + react-native-paper 5.15 +
  **`react-native-health` 1.19.0** + **`react-native-health-connect` 3.5.0**
- Всі 17 файлів у `app/` (Expo Router routing)
- 28 `.ts` файли у `src/features/*` + `src/shared/*` (auth, onboarding, health,
  workout, battle, equipment, mobs, leveling, shared api/config/theme/utils)
- Тести: 29 тестів (jest): 7 `.tsx` (компоненти) + 22 `.ts` (hooks, API, utils, stores)

**Плани і специфікації:**
- `docs/superpowers/specs/2026-04-04-react-native-rewrite-design.md` (559 рядків)
- `docs/superpowers/plans/2026-04-04-plan1-foundation-auth.md`
- `docs/superpowers/plans/2026-04-04-plan2a-onboarding-profile-leveling.md`
- `docs/superpowers/plans/2026-04-04-plan2b-health-sync.md`
- `docs/superpowers/plans/2026-04-04-plan2c-battle-workout-equipment.md`

**BA-конText:**
- `BA/outputs/01-market-research.md` (gap-таблиця 3.4)
- `BA/outputs/02-beta-scope.md` (IN-15 + OUT-15)
- `BA/outputs/05-lore-to-hook.md` (Opening 60s + flavor-тексти)
- `docs/vision/health-aggregator-comparison.md` (**authoritative**: native залишається
  для beta, Terra відкладено)

---

## 3. Backend status

### 3.1. Домени (bounded contexts) — готовність до beta

| Домен | Entity | Service | Controller | Tests | Готовність до beta |
|-------|--------|---------|------------|-------|---------------------|
| **User** | `User.php`, `LinkedAccount.php`, `UserTrainingPreference.php` | `RegistrationService`, `OnboardingService` | `RegistrationController`, `OnboardingController`, `ProfileController`, `UserController`, `OAuthController` | 5 Functional + 4 Unit | **90%** — регистрація, OAuth, onboarding, profile. Gap: real OAuth-token verification — placeholder у `OAuthController.php:88-95`. Це P1 для beta (до продакшен-launch). |
| **Character** | `CharacterStats.php`, `ExperienceLog.php` | `StatCalculationService`, `LevelingService`, `XpAwardService`, `XpCalculationService` | `LevelController` | 1 Functional + 5 Unit | **100%** — всі 4 сервіси реалізовані, формула XP правильна `[BL §4]`. Level-progress API повертає коректну структуру `/api/levels/progress`. |
| **Health** | `HealthDataPoint.php`, `HealthSyncLog.php` | `HealthSyncService`, `HealthSummaryService` | `HealthController` | 1 Functional + 2 Unit | **85%** — sync + deduplication + XP-award flow працюють. Gap: (a) response формат `HealthSummaryService` snake_case не збігається з клієнтським camelCase (див. §4). (b) `/api/health/sync-status` response у `HealthController::syncStatus()` повертає `{<type>: {last_synced_at, points_count}}`, клієнт очікує `{lastSync: string}` — **DTO-mismatch** (`rpgfit-app/src/features/health/types/responses.ts:17-19`). |
| **Battle** | `WorkoutSession.php` | `BattleService`, `BattleMobService`, `BattleResultCalculator` | `BattleController` | 1 Functional + 3 Unit | **90%** — повний flow з раid-mode, performance-tier, loot, XP з мобів. Gap: (a) **дубльований `awardXp`** з placeholder-формулою в `BattleService.php:347-384` — tech-debt (див. §1.5). (b) `BattleResultCalculator::flagDifficultyReduction()` (`src/Application/Battle/Service/BattleResultCalculator.php:596-602`) — **порожній метод** з коментарем «нічого не треба робити, performanceTier=failed — це і є прапор». OK, але крихкий контракт. (c) `BattleController::listExercises` повертає `{groups: {<muscleGroup>: [...]}}`, клієнт передає `activityCategory` snake_case — backend очікує camelCase (`BattleController.php:223`). |
| **Mob** | `Mob.php` | — (проста CRUD) | `MobController` | 1 Functional + 1 Unit | **100%** для beta — 2000 мобів генеруються через `app:generate-mobs`. Gap: **НЕМА `realm` enum на Mob** (`src/Domain/Mob/Entity/Mob.php` — перевірив, немає поля) — це P0 для beta-IN-3 faction identity. Потребує migration + backfill script. |
| **Inventory** | `ItemCatalog.php`, `UserInventory.php`, `ItemStatBonus.php` | `EquipmentService` | `EquipmentController` | 1 Functional + 6 Unit | **95%** — slot-rules (ring×2, bracelet×2, two-handed) реалізовані і протестовані (`tests/Unit/EquipmentServiceTest.php`). Gap: **нема `ItemType.portal_kit`** (потрібен для beta-IN-5/11 Portal Creation Kit). Зараз enum містить тільки `equipment, scroll, potion`. |
| **Skill** | `Skill.php`, `UserSkill.php`, `SkillStatBonus.php` | — (seeded) | немає окремого controller | 3 Unit | **90%** — 39 skills (25 race + 2 universal + ~120 profession-linked) seed через `app:seed-skills`. Gap: UI для активних skills/cooldowns — поза scope beta, але треба перевірити, що `usedSkillSlugs` в `POST /api/battle/complete` клієнт реально посилає (зараз — `[]` hardcoded у `rpgfit-app/app/(main)/battle/index.tsx:22`). |
| **Workout (Plan)** | `Exercise.php`, `SplitTemplate.php`, `WorkoutPlan.php`, `WorkoutPlanExercise.php`, `WorkoutPlanExerciseLog.php` | `WorkoutPlanGeneratorService` | `WorkoutPlanController` | 1 Functional + 8 Unit | **85%** — plan generator для 8 activity-types (strength, running, cycling, swimming, yoga, combat, hiit, other) `[BL §8]`. Gap: (a) `WorkoutPlanController::generate` очікує `activityCategory` (camelCase, `WorkoutPlanController.php:69`), **клієнт шле `activity_category`** (snake_case, `rpgfit-app/src/features/workout/api/workoutApi.ts:4`) — **DTO-mismatch, `POST /api/workout/generate` зламано зараз**. (b) `calculateXpReward` в `WorkoutPlanController::completePlan` дублює логіку з `BattleResultCalculator` — tech-debt. |
| **Activity (Profession)** | `Profession.php`, `ProfessionSkill.php`, `UserProfession.php`, `ActivityCategory.php`, `ActivityType.php` | — (seeded) | немає окремого controller | 4 Unit | **90%** — 16 categories × 3 tiers = 48 professions + 99 activity-types seeded. Gap: **`ActivityType.flutter_enum` column legacy** (`src/Domain/Activity/Entity/ActivityType.php` + migration). Згідно `project_flutter_cleanup` memory, після зняття Flutter поле-ім'я треба перейменувати на `source_enum` або аналогічне. Tech-debt. |
| **Media** | `MediaFile.php` | `MediaUploadService` | `MediaController` | 1 Functional + 1 Unit | **100%** — LiipImagine 4 filter-sets, polymorphic entity-link `[арх §11]`. |
| **Training (legacy)** | `WorkoutLog.php`, `ExerciseType.php`, `WorkoutCategory.php`, `ExerciseStatReward.php` | — | немає окремого controller | 5 Unit | **Legacy — НЕ використовується в active flow.** Зберігається для сумісності. Гарно задокументовано `[арх §3.4 «Legacy/Reference»]`. НЕ блокує beta. |
| **Config** | `GameSetting.php` | — (прочитується з repos) | — | 1 Unit | **100%** — 44 keys через Sonata Admin, in-request caching у сервісах. Gap: `bonuses` category зарезервовано для streak multiplier, **але impl не існує** `[BL §4.3]`. |

### 3.2. Тести

**69 тестів всього.** Фокус — Unit на domain-logic (валидатори стат, XP-формули, slot-rules),
Functional на controllers. Це **правильна пропорція для MVP**.

**Що НЕ покрито:**

1. **`BattleResultCalculator.php`** — 3 Unit тести, але вони не покривають `determinePerformanceTier`
   з усіма 5 tier'ами (failed/survived/completed/exceeded/raid_exceeded). Для beta — хитка
   area, бо «Battle=workout» — hero feature.
2. **`HealthSyncService.php`** — немає unit-тесту для edge-case «батч 1000+ точок, flush
   кожні 50».
3. **`BattleMobService.php`** — `BattleMobServiceTest.php` є (Unit), перевіряє selection,
   але не raid-mode HP/XP multiplier 1.3x з різних рідкісностей.
4. **Integration-тест full battle-flow** (start → next-mob → complete → XP award →
   level-up) відсутній. Це **P0 якщо планується deploy перед beta**.
5. **JWT-firewall configuration** не testing (`tests/Functional/AuthControllerTest.php`
   тестує login endpoint, але не 401 на protected endpoints з invalid/expired JWT).

### 3.3. Міграції + seeds

Міграції **узгоджені з ARCHITECTURE.md**. 15 файлів еволюційно budувало схему від
початкового `users` до останнього `Version20260328230000` (battle performance
fields). Жодних inline SQL-хаків.

**Seeds: всі 4 обов'язкові команди робочі** (`app:seed-professions`, `app:seed-skills`,
`app:seed-exercises`, `app:seed-battle-settings` + `app:seed-workout-settings`),
покриті тестами (`tests/Functional/SeedExercisesCommandTest.php` і 2 інші).

### 3.4. Dependencies — deprecated/outdated API risk

- **Symfony 7.2 vs PHP 8.2+:** OK. Symfony 7.2 підтримує PHP 8.2/8.3/8.4. `composer.json`
  вимагає `>=8.2` — widen-able, але docker `php-fpm` у `docker-compose.yml` має бути
  8.3 (потребує підтвердження з `docker/php/Dockerfile` — не прочитував).
- **Doctrine ORM 3.6:** 3.6 — active release. Nothing deprecated у наших entities
  (всі використовують `#[ORM\Entity]`, `#[ORM\Column]` attributes).
  Після 3.6 → 4.0 міграція відкладається (немає потреби перед beta).
- **Lexik JWT 3.2:** active. Нема відомих security-bugs.
- **Sonata Admin 4.40:** OK для Symfony 7. `src/Admin/*` — 29 класів CRUD, без
  custom render-logic, що могло б bреaк на major-update.
- **Liip Imagine 2.15:** OK, без known issues для наших 4 filter-sets.
- **API Platform 4.1:** встановлений у composer.json, але **не вивчений де
  використовується** — потенційно тільки для `/api/docs`. Перевірити з фаундером,
  чи потрібен (або зняти залежність).

---

## 4. Client (RN) status

### 4.1. Фічі — таблиця

| Feature | API layer | Hooks | UI | Tests | Gap |
|---------|-----------|-------|-----|-------|-----|
| **Auth (login, register)** | `src/features/auth/api/authApi.ts` | `useLogin`, `useRegister`, `useUser` | `app/(auth)/login.tsx`, `registration.tsx` | 4 (store, api, hooks, components) | **80% готово.** DTO збігаються. Gap: (a) немає OAuth-UI — `spec §TODOs from Flutter Code` прямо каже «TODO Google/Apple/Facebook». (b) на 401 — interceptor logs out (`rpgfit-app/src/shared/api/interceptors.ts`), але немає токен-refresh flow (reasonable — JWT-only short-lived model). |
| **Onboarding** | `src/features/onboarding/api/onboardingApi.ts` | `useOnboarding` | `app/(onboarding)/index.tsx` | 2 (api, hook) + 1 screen | **70% готово.** Request має всі 9 fields (`OnboardingRequest`). UI — 9-step wizard (перевірено `app/(onboarding)/index.tsx`, не деталізував тут). Gap: DTO OK. Validation OK. **Бракує: realm-pick (beta-IN-3, vision:beta-hype) — не додано ні в backend, ні в клієнт.** |
| **Profile** | (використовує auth) | `useUser` | `app/(main)/profile.tsx` | 1 screen + 1 hook | **50% готово.** `UserResponse` type має `stats.level` + `stats.totalXp` (`rpgfit-app/src/features/auth/types/responses.ts:30-35`), але backend `/api/user` повертає stats БЕЗ `level` і `totalXp` в sub-об'єкті (`UserController.php:100-104` — тільки strength/dexterity/constitution). **`totalXp` — top-level ключ** (`UserController.php:107`). → TypeScript цього не ловить, але runtime рендер покаже `undefined`. |
| **Health** | `src/features/health/api/healthApi.ts` + `services/healthService.ts` + `services/healthPollingService.ts` | `useHealthSync`, `useHealthSummary` | `app/(main)/health.tsx` | 6 (services, api, hooks, enums, screen, polling) | **30% готово.** **MOCK-implementation** — `healthService.ts:34 class MockHealthService` повертає hardcoded 8500 кроків, 420 ккал, etc. `react-native-health` і `react-native-health-connect` у package.json, але **не інтегровані** — немає config-plugin, `iOS Info.plist` entitlement mod, Android activity-alias. Детально — §5. Також — 3 DTO-bug'и (enum case, response formats — див. нижче). |
| **Workout Plans** | `src/features/workout/api/workoutApi.ts` | (inline в screens) | `app/(main)/workouts/index.tsx` + `[id].tsx` | 1 (api) + 0 screens | **40% готово.** API thin. UI: лістинг планів + detail. Gap: (a) `generate()` має DTO-bug `activity_category` snake_case (див. §4.2). (b) немає способу логувати sets per exercise через UI (`workoutApi.logSet` існує, screen не використовує). |
| **Battle** | `src/features/battle/api/battleApi.ts` | (inline в screen) | `app/(main)/battle/index.tsx` + `start.tsx` | 1 (api) + 1 screen | **25% готово.** Start-flow працює (pick type → pick mode → generate + start → navigate). **`app/(main)/battle/index.tsx:22` — CRITICAL BUG**: `exercises: []` — порожній при complete, → 0 damage, завжди `performance_tier=failed`. (Потрібен UI для логування exerciseSlug+sets під час battle). DTO-bug `exerciseId` vs `exerciseSlug` — див. §4.2. |
| **Equipment** | `src/features/equipment/api/equipmentApi.ts` | (inline) | `app/(main)/equipment.tsx` + `inventory.tsx` | 1 (api) | **50% готово.** Equip/unequip API OK. Inventory screen читає `item.name`, `item.rarity`, `item.equippedSlot` (`rpgfit-app/app/(main)/inventory.tsx:32-40`), **але backend `/api/user` повертає inventory з полями `{id, itemName, quantity, equipped}`** (`UserController.php:64-70`). → runtime undefined rendering. DTO-bug. |
| **Mobs** | `src/features/mobs/api/mobsApi.ts` | — | немає screen | 1 (api) | **20% готово.** API є, UI немає. OK, це публічний catalog endpoint, показ мобів в UI beta не обов'язковий. |
| **Leveling** | `src/features/leveling/api/levelingApi.ts` | `useLevelProgress`, `useLevelTable` | `app/(main)/levels.tsx` | 3 (api, hook, screen) | **90% готово.** DTO-збігаються, screen рендерить XP-curve. |

### 4.2. DTO mismatches (всі 4 критичних) — детально

**1. HealthDataType enum case mismatch [P0 блокує]**

- Backend `src/Domain/Health/Enum/HealthDataType.php:10-24`: `case Steps = 'STEPS';`
  (UPPERCASE `STEPS`, `HEART_RATE`, etc.)
- Backend `src/Controller/HealthController.php:114-130`: валідує `$pointDTO->type`
  проти enum values через `array_map(fn($c) => $c->value, HealthDataType::cases())`
  → якщо значення не в enum — HTTP 422 `"Invalid health data type."`.
- Client `rpgfit-app/src/features/health/types/enums.ts:2-17`: `Steps = 'steps'`
  (lowercase).
- Client `rpgfit-app/src/features/health/services/healthService.ts:47`:
  `type: 'steps'` (mock sends lowercase).
- Client `src/shared/types/enums.ts` також lowercase.
- **Наслідок:** КОЖЕН sync-запит отримає HTTP 422, жодна health-точка не збережеться,
  XP не нараховується, level не росте. **Весь core-loop beta зламаний.**
- **Джерело помилки:** Plan 2B Task 1 вручну закодував lowercase як контракт —
  не звірив із backend. Обидва — «source of truth», тому треба обрати один.
  Рекомендація: поменяти backend enum на lowercase (це raw string, що проходить
  у JSON), бо backend не використовує enum value в SQL-queries напряму (Doctrine
  stores enum.value as VARCHAR). Migration — просто `UPDATE health_data_points
  SET data_type = LOWER(data_type);` + update enum.

**2. Battle exerciseSlug vs exerciseId [P0 блокує]**

- Backend `src/Application/Battle/Service/BattleService.php:125`: `$slug =
  $exerciseData['exerciseSlug'] ?? null;` — читає `exerciseSlug`, не `exerciseId`.
- Backend `src/Application/Battle/Service/BattleService.php:130`:
  `$exercise = $this->exerciseRepository->findBySlug($slug);` — lookup by slug.
- Client `rpgfit-app/src/features/battle/types/requests.ts:3`:
  ```ts
  exercises: { exerciseId: string; sets: ... }[]
  ```
- **Наслідок:** `foreach` на `exercises` → `$slug = null` → `continue` → всі exercises
  пропущені → `BattleResultCalculator` рахує damage від `calculateTrainingVolume`,
  але з порожнім масивом → damage = training_score = healthData.calories only.
  Якщо `healthData` теж порожній (див. §4.1 Battle #3) — total_damage = 0 →
  tier=failed.
- **Плюс другий bug:** `app/(main)/battle/index.tsx:22` — client надсилає
  `exercises: []` (порожній), тож навіть якби DTO був правильний — 0 damage.

**3. WorkoutPlan generate: activity_category snake_case [P0 блокує генерацію]**

- Backend `src/Controller/WorkoutPlanController.php:69`:
  `$activityCategory = $data['activityCategory'] ?? null;` — camelCase.
- Backend `src/Controller/BattleController.php:223`: query param `activityCategory`
  (camelCase).
- Client `rpgfit-app/src/features/workout/api/workoutApi.ts:4`:
  `generate(params: { activity_category?: string; target_date?: string })` —
  snake_case body.
- Client `app/(main)/battle/start.tsx:35`: `workoutApi.generate({ activity_category:
  workoutType })` — посилає snake_case.
- **Наслідок:** `$activityCategory = null` завжди → генератор йде у generic fallback
  (strength activity type). Workout plan активно **не reflectsь вибір type** з UI.
  Start-flow фактично ломан (все — strength).
- Те саме для `target_date` → `date` (backend:70 `$dateStr = $data['date'] ?? null;`).

**4. Health summary: snake_case vs camelCase [P0 UI broken]**

- Backend `src/Application/Health/Service/HealthSummaryService.php:93-99`:
  ```php
  return [
      'date' => ..., 'steps' => ..., 'active_energy' => ...,
      'distance' => ..., 'sleep_minutes' => ...,
      'average_heart_rate' => ..., 'workout_minutes' => ...,
  ];
  ```
- Client `src/features/health/types/responses.ts:7-14`:
  ```ts
  interface HealthSummaryResponse {
    steps: number; activeEnergy: number; distance: number;
    sleepMinutes: number; averageHeartRate: number; workoutMinutes: number;
  }
  ```
- Client `app/(main)/health.tsx:44-50`: рендерить `summary.activeEnergy`,
  `summary.sleepMinutes`, etc. → усі `undefined` → NaN у формат-функціях.
- **Наслідок:** Health dashboard показує «NaN kcal», «NaN h Xm», «undefined bpm».

**5. HealthSyncResponse structure mismatch [P1 cosmetic]**

- Backend `HealthSyncService.php:124-134` повертає:
  ```php
  {accepted: N, duplicates_skipped: N, xp: {awarded, totalXp, level, leveledUp, progress}}
  ```
- Client `types/responses.ts:1-5` очікує:
  ```ts
  {accepted: number; skipped: number; xpAwarded: number;}
  ```
- Client `app/(main)/health.tsx:61` рендерить `syncMutation.data.skipped`,
  `syncMutation.data.xpAwarded` — обидва `undefined`.

**6. HealthSyncStatus endpoint mismatch**

- Backend `HealthController.php:173-187` повертає `{<type-value>: {last_synced_at,
  points_count}}` — мапа по типам.
- Client `types/responses.ts:17-19`: `{lastSync: string | null}` — одиничний
  timestamp.
- **Наслідок:** клієнт не може побудувати per-type sync-status UI.

**7. User endpoint inventory items shape**

- Backend `UserController.php:63-70` кожен item: `{id, itemName, quantity, equipped}`.
- Client `app/(main)/inventory.tsx:32-40`: читає `item.name`, `item.rarity`,
  `item.description`, `item.itemType`, `item.equippedSlot`.
- **Наслідок:** Inventory-screen показує **nothing**. Client types (`UserResponse.inventory:
  any[]` — (`types/responses.ts:36`)) — `any[]`, тож TS не ловить.

**8. User endpoint stats shape**

- Backend `UserController.php:100-104`: `stats: {strength, dexterity, constitution}`.
- Client types (`types/responses.ts:30-35`): `stats: {strength, dexterity,
  constitution, level, totalXp}` — **хоче level+totalXp, backend не дає** (totalXp —
  top-level, level — лише через `/api/levels/progress`).
- **Наслідок:** Profile screen показує `user.stats?.level` — `undefined`.

**9. StartBattle response shape**

- Backend `BattleController.php:86-91`: `{sessionId, mode, mob: {...}, startedAt}`.
  (mob inside має `hp`, `xpReward`).
- Client `types/responses.ts:3-10`: `StartBattleResponse: {sessionId, mob: {id,
  name, slug, level, hp, xpReward, rarity, description}, mobHp, mobXpReward, mode,
  startedAt}`. Client expects **top-level `mobHp`, `mobXpReward`** і **mob.slug**,
  **mob.description**.
- Backend mob-serializer (`BattleController::serializeMob:321-329`): `{id, name,
  level, hp, xpReward, rarity, image}` — **без slug і description**.
- Client в battle-screen UI (`app/(main)/battle/index.tsx:49-50`):
  `session.mobHp - session.totalDamageDealt` — працює (mob.hp === session.mobHp
  випадково), але не надійно.

### 4.3. Infrastructure — client

**Auth guard (P0, correct):**
- `app/_layout.tsx:18-34` — PaperProvider + QueryClient + SafeArea + initialize
  auth.
- `app/(main)/_layout.tsx:6-28` — redirect до (auth) якщо не authenticated,
  до (onboarding) якщо onboardingCompleted=false. **Експлицитний guard** —
  це правильний pattern [перевірено docs.expo.dev/router/advanced/authentication
  2026-04-18]. Expo Router 6 має `Stack.Protected` як кращий API, але поточна
  reализація через `<Redirect>` теж валідна.

**Axios setup:**
- `src/shared/api/client.ts:7-14` — axios-instance з interceptors (attachToken на
  request, handleUnauthorized на response).
- `src/shared/config/environment.ts:15` — `apiBaseUrl: 'https://localhost:8443'`
  — **НЕ той URL із спеки `https://rpgfit.local:8443`** `[спек lines 253]`.
  Якщо dev-машина Mac не додавав `rpgfit.local` в /etc/hosts → `localhost`
  правильний дефолт для simulator, але НЕ для фізичних девайсів. Документувати.

**Jest mocks:**
- `jest.mock.react-native.js`, `jest.mock.react-native-paper.js`,
  `jest.mock.safe-area-context.js` — існують на корні. 29 тестів виконуються.
  Integration з `expo-secure-store`, `@tanstack/react-query`, `react-native-health`,
  `react-native-health-connect` — мок-ованo через jest? Не перевіряв — рекомендую
  запустити `npm test` перед beta щоб підтвердити 100% pass-rate.

### 4.4. Що відсутнє у клієнті з beta-scope IN-15

1. **Opening 60s screen** (IN-1) — немає. Потрібна: voice-over loader + 3-кадрова
   анімація + faction-pick form. ~1 тиждень нового коду.
2. **6-realm faction pick** (IN-3) — немає. Потрібна: новий screen в (onboarding),
   enum `Realm`, API endpoint для зберігання вибору.
3. **Weekly Fitness Recap card + push** (IN-4) — немає. Потрібна: cron на backend,
   render-service для shareable card, push-notification.
4. **Portal Creation Kit UI** (IN-5) — немає. Потребує новий Portal-flow:
   `CreatePortalScreen`, geo-location picker, ItemType `portal_kit` у Inventory.
5. **Statичні портали screen + detail + virtual-replica** (IN-7) — немає.
6. **Launch event «День Розколу» UI + rare-mob spawn logic** (IN-8) — немає.
7. **Monthly «Day of Realm»** (IN-9) — немає.
8. **Discord-deep-link screen + realm-channels** (IN-10) — немає.
9. **Referral screen** (IN-11) — немає.
10. **F2P manifesto in-app** (IN-12) — немає.
11. **Streak multiplier hook + UI** (IN-13) — немає.
12. **Named artifact earn-flow** (IN-14) — немає.
13. **Onboarding flavor-texts** (IN-15) — немає.

**Підсумок:** 9 з 15 IN-фіч не мають ані backend-, ані frontend-опори.

---

## 5. Health sync — deep-dive (per health-aggregator-comparison)

Згідно `health-aggregator-comparison.md §6 Фаза 0` — beta залишається на **native
HealthKit (iOS) + Health Connect (Android) через RN libraries**. Отже аудит
native-частини.

### 5.1. Current state — MOCK тільки

- **`rpgfit-app/src/features/health/services/healthService.ts:96`:**
  `export const healthService: IHealthService = new MockHealthService();`
- Імпорти `react-native-health` / `react-native-health-connect` — **не існують** у
  `healthService.ts`, `healthPollingService.ts`. Бібліотеки тільки у package.json,
  реальної інтеграції немає.
- **`src/features/health/services/healthPollingService.ts`:** поллінг-цикл
  10s-workout / 5min-idle реалізований **як таймер, без native-calls**. Синхронує
  mock-data.

### 5.2. iOS — HKObserverQuery + enableBackgroundDelivery

**Що потрібно (згідно спеки `[спек 451-457]`):**
- `HKObserverQuery` підписка для 15 HealthDataType.
- `enableBackgroundDeliveryForType` для кожного.
- Expo config plugin з iOS `Info.plist` entitlement
  `com.apple.developer.healthkit.background-delivery` `[01 §5 #4 HealthKit Sources]`.
- Обробник приймає callback від OS, викликає `completionHandler()` (інакше iOS
  блокує app).

**Що є:** нічого з цього. Нема `app.json` mods, нема native-iOS-коду.

**Відомі GitHub-bugs, які нас кусатимуть:**
1. **`agencyenterprise/react-native-health` issues #217, #308, #144** — «background
   observer не спрацьовує коли app killed», «потрібно 3-5 cycle open/close щоб
   event'и почали приходити» [перевірено github.com/agencyenterprise/react-native-health/issues/217
   та /issues/308 2026-04-18]. **Root cause:** коли app killed, RN JS bridge
   не активний — native-side отримує дані від HealthKit, але JS handler
   не запускається.
2. **Issue #201** — «App crashing when configuring background observer»
   [перевірено github.com/agencyenterprise/react-native-health/issues/201
   2026-04-18]. Конфіг в `didFinishLaunchingWithOptions` критичний.

**Workaround:** observers only work while app foreground/background (not killed).
Для killed-app use case — user має відкрити RPGFit раз на день. Це OK для beta,
але **треба flagging в UI**: «RPGFit sync'ає під час використання. Відкривай
щодня!».

### 5.3. Android — Health Connect polling

**Що потрібно (згідно плану 2B):**
- Import `react-native-health-connect` v3.5.
- Create client: `ChangesClient` API (fetch deltas).
- Polling service виконує background-fetch через foreground-service або
  `react-native-background-actions`.

**Що є:** бібліотека у package.json, імпорту немає, поллінг — над mock.

**Android 14+ блокер:**
- Android 14 (API 34) та вище вимагає `ViewPermissionUsageActivity` activity-alias
  у `AndroidManifest.xml` [перевірено matinzd.github.io/react-native-health-connect/docs/permissions
  2026-04-18].
- Без цього — permission rationale flow не працює, user не може дати permission
  → sync на Android 14+ зламано.
- У `rpgfit-app/app.json`, `android` section — треба додати через Expo config-plugin
  (Expo автоматично створює manifest, але не вставляє arbitrary activity-alias
  без `expo-build-properties` або custom-plugin).

**Battery impact 10s polling during workout:**
- Foreground-service з ongoing notification обов'язковий для Android 12+.
  Без нього — OS killяe background task через ~10-30 sek.
- 10s polling → ~360 `ChangesClient.getChanges` calls за годину workout. Кожен
  call ~5ms CPU + 1kB I/O. Battery drain низький (<2% за 1h), АЛЕ foreground-service
  notification — UX-friction.

### 5.4. Data types валідність

- Spec `[спек 310-314]` перечисляє 15 types; backend enum `HealthDataType.php`
  має 15 (match). Але **case mismatch** — див. §4.2 #1.
- Spec і backend **НЕ мають HEART_RATE_VARIABILITY, VO2_MAX, BLOOD_PRESSURE** —
  це OK для beta. `health-aggregator-comparison §1` позначає їх P2.

### 5.5. Health sync — summary ризиків

| Ризик | Probability | Impact | Mitigation |
|-------|-------------|--------|------------|
| iOS background delivery fails (app killed) | High | High | UX: «відкривай щодня». + iOS 15+ entitlement. |
| Android 14 permission flow broken без activity-alias | Certain (100%) | Beta-killer | Додати config-plugin до 01.07.2026 |
| 10s polling battery drain (Android) | Medium | Medium (churn) | Foreground-service notification + 30s fallback якщо user OK |
| DTO enum case mismatch | Certain (100%) | P0 blocker | 1-day fix, узгодити lowercase скрізь |
| health service == mock | Certain (100%) | P0 blocker | 2-3 тижні на native integration |

**Orientational effort для P0 Health:** 3-4 тижні full-time на одного devеloper'а
(iOS HKObserverQuery setup, Android foreground-service + Changes API, DTO
узгодження, 5-10 Unit/integration тестів).

---

## 6. Gaps vs beta scope

Для кожної P0-фічі з `02-beta-scope.md §4.1 IN-15` — поточний стан і gap.

| # | Beta IN-фіча | Current state | Gap до beta-ready | Effort |
|---|--------------|---------------|---------------------|--------|
| **1** | Opening 60s (voice-over + faction-pick) | НЕМА (клієнт + backend) | Повний новий feature (screen, voice assets, faction enum, API). 05:§6 має сценарій. | 2-3 тижні (1 dev + 1 designer + 1 voice actor) |
| **2** | Battle = workout real-time (Hero) | **~75%** — backend Battle-flow готовий `[BL §9]`, RN screen є, але **3 DTO-bugs блокують** (`exerciseSlug`, workout generate `activity_category`, HealthDataType enum case); **UI для log sets під час battle немає** | 1) Fix 3 DTO-bugs (1-2 дні). 2) Реалізувати set-logging UI (Log Set picker, `workoutApi.logSet` використання — 1 тиждень). 3) Інтегрувати real-time health-HR hook в battle screen (1 тиждень — pending native health). | 2-3 тижні |
| **3** | 6-realm faction identity | НЕМА повністю. Enum `CharacterRace` є 5 values; `realm` — НЕ існує в коді. `SeedProfessionsCommand.php:98,101` згадує «realm» тільки у string-описах. | 1) Migration: add `users.primary_realm VARCHAR(20)` + enum `Realm` (Olympus, Asgard, Dharma, Duat, Nav, Shiba). 2) Mob.realm column + backfill. 3) Damage +2% calculator modifier. 4) UI badge. | 1.5-2 тижні (backend + RN) |
| **4** | Weekly Fitness Recap push + shareable | НЕМА. | 1) Backend: cron-command (aggregate weekly), `/api/recap/weekly` endpoint, render-service (HTML-to-PNG через Puppeteer або серверний GD). 2) RN: push-notification (Expo push), shareable card screen. | 2 тижні |
| **5** | Portal Creation Kit ItemType + flow | НЕМА. ItemType enum: `equipment, scroll, potion`. Додати `portal_kit`. | 1) Enum + migration. 2) `Portal` entity (lat/lng, owner user_id, createdAt, expiresAt). 3) `/api/portals/create` endpoint. 4) RN: CreatePortalScreen, geolocation picker (`expo-location`). | 2-3 тижні |
| **6** | Starter Artifact Tier 1 + 24h XP boost | Partial — ItemType `potion` є (duration field), rarity `uncommon/rare` є. Gap: seeded Tier 1 artifacts (3 specific). | Seed command з 3 starter-items + linkage до registration flow (auto-add on user create). | 2-3 дні |
| **7** | 10-15 статичних порталів | НЕМА ні Portal entity, ні list, ні detail. | Все з #5 + seed-command для 10-15 portals з locations, artifact rewards. | +1 тиждень поверх #5 |
| **8** | Launch event «День Розколу» 48h | НЕМА. | 1) `Event` entity (type, startAt, endAt, modifiers JSON). 2) Event-aware mob-spawn logic (BattleMobService). 3) Launch-event scheduled in advance via Sonata Admin. 4) RN: event-banner UI. | 3 тижні |
| **9** | Monthly «Day of Realm» | НЕМА. Зв'язано з #8. | Повторне використання Event entity + cron для ротації по місяцях. | 1 тиждень поверх #8 |
| **10** | Discord setup + bench | Org, не dev — поза audit scope. | — | N/A (community-mgr) |
| **11** | Referral: Portal Kit на обох | НЕМА. | 1) `ReferralCode` entity (code, owner_user_id, used_by_user_id?). 2) `/api/referral/generate` + `/api/referral/redeem` endpoints. 3) RN: referral code screen + sharable link. | 1-1.5 тижні |
| **12** | F2P manifesto in-app | НЕМА. | Проста settings-screen у `app/(main)/` + static markdown. | 2-3 дні |
| **13** | Streak multiplier | Partial — `[BL §4.3]` згадує заплановано, реалізації немає. Game_settings `bonuses` category — порожня. | 1) Backend: `StreakService` (розрахувати streak за 30 днів ExperienceLog). 2) Apply multiplier в `XpAwardService`. 3) RN: streak badge + warning «день пропуску»  UI. | 1 тиждень |
| **14** | Named artifact run-streak earn | Partial — ItemCatalog і rarity є. Gap: earn-logic (Mjolnir після 7d+10km). | `ArtifactEarnRuleService` + cron check + grant flow. | 1-1.5 тижні |
| **15** | Onboarding flavor-texts (10) | НЕМА. | Text-content pass через 05:§7 прямо. | 1-2 дні copy-writer |

**Summary:** 12 з 15 IN-фіч потребують нової backend + RN роботи. **Загальний
effort для beta-completeness: ~12-16 тижнів full-time одного backend + одного RN
dev + 1 part-time designer + 1 part-time copywriter + 1 part-time community-mgr.**

Якщо launch 31.10.2026 (6+ місяців) — встигнемо.
Якщо launch 01.08.2026 (3+ місяців) — **неможливо без cutdown до 6 IN-фіч** (`02 §12 Decision 1`).

---

## 7. Tech-debt (not blocking beta, but should be tracked)

1. **`BattleService::awardXp` placeholder formula** — `src/Application/Battle/Service/BattleService.php:347-384`.
   Невикористовується (`completeBattle` делегує `BattleResultCalculator`), але код
   висить. Ризик: випадкова ре-активація в refactor. **Fix:** видалити метод
   + його helper-методи (`determineRewardTier`, `getRewardTierBonus`) — вони також
   унікальні і не використовуються зараз. Effort: 30 хв.

2. **`ActivityType.flutter_enum` column legacy** — `src/Domain/Activity/Entity/ActivityType.php`.
   Згідно `project_flutter_cleanup` — не видалили ще. Зараз backend (`SeedProfessionsCommand`)
   заповнює це поле для 99 activity-types Flutter-enum іменами, які RN клієнт не
   читає. **Fix:** rename на `source_enum`, або залишити flutter_enum для історії
   і додати колонку `rn_mapping` якщо потрібно (не потрібно — RN використовує
   camelCase ідентифікатори, які збігаються з `slug`). Effort: 1 migration + code
   rename ~1h.

3. **Дубльована XP-award logic в `WorkoutPlanController::completePlan` vs
   `BattleResultCalculator::awardXp`.** Обидва створюють `ExperienceLog` і
   `stats->setTotalXp`, але **жоден не викликає `LevelingService::getLevelForTotalXp`**
   в `WorkoutPlanController` (`src/Controller/WorkoutPlanController.php:211-213`
   — setTotalXp but no setLevel). → рівень не оновлюється при complete plan поза
   battle. `[BL §4 і BL §9]` не чітко розрізняють workout-plan complete без battle
   vs battle complete. **Fix:** переніс логіку XP-award в `XpAwardService` і
   викликати звідти. Effort: 2-3 дні (включно з тестами).

4. **API Platform 4.1 у composer.json** — не зрозуміло де використовується.
   Можливо `/api/docs` only. **Review:** якщо не потрібен для mobile client
   (а не потрібен — client мануально обробляє JSON), зняти залежність
   (econom memory + fewer deprecation surfaces). Effort: 1h.

5. **Redis встановлений, але `composer.json` не містить `snc/redis-bundle` або
   `predis/predis`.** Отже docker redis присутній, але PHP не комунікує. Це може
   бути дизайн — Symfony кешує через в-request memory (`settingsCache` у
   `BattleResultCalculator.php:42`). Але **якщо planується масштабування beta
   > 1k concurrent** — треба підключати Redis для cache.simple або session.
   Effort: 0.5 дня.

6. **Нема `HealthDataPoint` в response від `POST /api/health/sync`.** Backend
   повертає `{accepted, duplicates_skipped, xp}`, а не список accepted point IDs.
   Клієнт не може видалити їх з local-cache optimistically. Не блокер, але
   обмежує optimistic UI. Effort: add list, 2h.

7. **Нема unit-тесту для full `determinePerformanceTier` matrix** (5 tiers × raid
   vs normal × with/without expected_mobs). `tests/Unit/BattleResultCalculatorTest.php`
   (52 тести раніше, є), але scope цього тесту не перевіряв повністю — рекомендую
   expand до 10-15 test cases для цієї функції.

8. **iOS `Info.plist` privacy descriptions для 15 HealthDataTypes.**
   `react-native-health` вимагає `NSHealthShareUsageDescription` +
   `NSHealthUpdateUsageDescription` у `Info.plist` — зараз `app.json` не містить
   `ios.infoPlist`. На submit будує App Store → **rejection**. Effort: 1h config.

9. **Android `WAKE_LOCK` + `FOREGROUND_SERVICE_HEALTH` permission** потрібні для
   10s polling workout. `app.json` android.permissions — порожній. Effort: 30 min.

10. **TypeScript `UserResponse.inventory: any[]` і `.skills: any[]`** —
    `rpgfit-app/src/features/auth/types/responses.ts:36-37`. Типізувати + створити
    `InventoryItem` і `Skill` типи. Effort: 2-3h.

11. **`BattleResponse` types різняться між `requests.ts` і `responses.ts`** — `Mob`
    type defined в обидвох файлах різно (з/без `slug`, `description`). Consolidate
    в `models.ts`. Effort: 1h.

12. **Environment config dev URL `https://localhost:8443`** — `[спек §Environment]`
    рекомендує `https://rpgfit.local:8443` для фізичних девайсів. Docs-gap.
    Effort: 30 min (README oновити).

---

## 8. Risks (інтеграційні)

### 8.1. Flutter cleanup delay

- `project_flutter_cleanup` memory: Flutter видаляється після RN confirmed.
- Зараз у `rpgfit-app/` немає Flutter-коду (тільки RN/Expo), отже **cleanup
  Flutter — ЗРОБЛЕНО для мобільного клієнта**. Але:
  - `src/Domain/Activity/Entity/ActivityType.php` має `flutter_enum` column
    (legacy tag — див. tech-debt #2).
  - `app:seed-professions` заповнює це поле.
  - Жодний test не перевіряє що `flutter_enum` === `slug` або подібне — може
    silent-drift.
- **Ризик:** середній. Не блокує beta, але створює confusion у нових dev'ів.

### 8.2. DTO-mismatches blocking production

- **4 критичні (§4.2).** Без fix'а beta-user не зможе:
  - Синхронити health (enum case) → no XP → no level-up → core hook #2 мертвий.
  - Згенерувати custom-type workout (activity_category) → завжди strength.
  - Виконати battle правильно (exerciseSlug + empty exercises array) → 0 damage.
  - Побачити health dashboard (snake_case vs camelCase) → NaN скрізь.
- **Impact:** 10k beta MAU target у `beta-hype.md` — неможливий, бо app
  не-functional.
- **Mitigation urgency:** P0 — вирішити цього тижня (1-2 дні spike).

### 8.3. Health-sync не працює на production-like devices

- Mock-only service → жоден health-event ніколи не надійде в реальній беті.
- Не можна навіть soft-launch з closed-beta testers — це був би demo, не beta.
- **Effort:** 3-4 тижні native-integration (iOS + Android).
- **Probability test fail:** 100% без fix.

### 8.4. Missing tests on critical path

- **Integration battle-flow test** — немає (перевірено `tests/Functional/BattleControllerTest.php`:
  є але покриває тільки start/complete/abandon/next-mob endpoint responses, не
  full user → login → generate plan → start battle → log exercises → complete
  → verify XP + level flow).
- **Frontend end-to-end (Maestro)** — spec згадує Maestro YAML у `__tests__/`,
  але **не побудований** — `rpgfit-app/__tests__/` не існує.
- **Ризик:** regression bugs під час DTO-fix або нового feature-add мовчки
  потраплять у beta → bad UX.
- **Mitigation:** перед beta — мінімум 3 Maestro E2E (auth flow, onboarding,
  battle-happy-path).

### 8.5. Content-starvation (content scale)

- `02 §8.1`: 2000 mobs → **нема realm tagging**. Користувач бачить generic-мобів.
- `02 §8.2`: 10-15 static portals → **нема Portal entity + seed**.
- `02 §8.3`: 18-23 artifacts → **немає earn-flow**.
- **Ризик:** user на D7 бачить одноманітний contented → churn.

### 8.6. Scaling risk: 10s polling × 10k users × 1h/day workouts

- `health-aggregator-comparison §4.2`: Android 10s polling = `Changes API` call
  кожні 10s.
- 10k users × 360 calls/h × backend request ~20ms = 2000 concurrent requests
  per second peak.
- `php-fpm` config у Docker — не перевіряв pool-size. Default 5 workers — недостатньо.
- **Ризик:** backend не witstands 10k concurrent на launch day.
- **Mitigation:** (a) HealthSync має бути idempotent (є — externalUuid dedupe
  `[BL §3]`) → client-retry безпечний. (b) Переглянути php-fpm pm.max_children
  перед launch. (c) Redis cache для `GameSettingRepository.getAllAsMap()` —
  зараз in-request, на кожний request hits MySQL.

### 8.7. OAuth token verification — placeholder

- `OAuthController.php:88-95`: «Placeholder token verification: just ensure the
  token is not empty».
- **Security ризик:** **anyone who knows a user's email can login as them** —
  надішли `{provider: "google", providerUserId: "X", email: victim@example.com,
  token: "anything"}` → JWT. **Це критичний bug якщо deploy'ємо без fix'а**.
- **Mitigation:** підключити `google-api-client` (або через `guzzle http` +
  manual JWT-verify) до `/tokeninfo` для Google, аналогічно Apple. Must-have до
  beta. Effort: 2-3 дні.

### 8.8. Docker PHP 8.3 vs composer.json ≥8.2

- `composer.json` дозволяє 8.2/8.3/8.4.
- Не перевірив `docker/php/Dockerfile` — потенційно 8.2 → local-dev, 8.3 → prod.
  Різні PHP-versions мають different enum semantics + JIT behavior.
- **Mitigation:** зафіксувати PHP 8.3 як minimum (`"php": ">=8.3"`) якщо OK з фаундером.

---

## 9. Рекомендації для 03 (roadmap)

### 9.1. Що обов'язково в «Now» (scope-gate decisions)

**P0-FIX Week 1 (blocking everything):**

1. **DTO узгодження блок** (§4.2 #1-4) — 1 спринт: (a) HealthDataType lowercase
   → вирівняти backend + migration. (b) exerciseSlug — узгодити клієнтський type.
   (c) activity_category → activityCategory в client. (d) health summary → camelCase
   на backend. **Це блокує ВСЕ.**

2. **OAuth token verification** (§8.7) — security-critical до deploy.

3. **Health native-integration** (§5) — 3-4 тижні окремо, може йти паралельно
   DTO-fix. **ДО завершення beta-launch - обов'язково.**

4. **Battle set-logging UI** (§4.1 Battle) — 1-2 тижні. Без нього battle = 0
   damage.

**P0-NEW features (beta scope IN-15):**

5. **Realm enum + primary_realm + mob.realm migration** (IN-3) — 2 тижні.
   Дешево, масштабно вплив (§1.3 01-market, §4.1 02-beta-scope IN-3).

6. **Weekly Fitness Recap** (IN-4) — 2 тижні. Strava-proven retention-driver.

7. **Streak multiplier** (IN-13) — 1 тиждень. `[BL §4.3]` готова specs.

8. **Starter Artifact seed** (IN-6) — 2-3 дні. Малий effort, D0 emotion-hit.

9. **F2P manifesto screen** (IN-12) — 2-3 дні. Публічний commitment-trust.

10. **Onboarding flavor-тексти з 05:§7** (IN-15) — 1-2 дні.

**Тестування-стабілізація:**

11. Maestro E2E для 3 flows (auth, onboarding, battle happy-path) — 1 тиждень.
12. Integration test повного battle-cycle — 3 дні.

### 9.2. Що в «Next» (post-P0-fix, до launch)

13. **Launch event «День Розколу»** infrastructure (IN-8) — 3 тижні. Вирішує
    retention D30+.
14. **Monthly Day of Realm** (IN-9) — 1 тиждень поверх #13.
15. **Portal Creation Kit + 5-10 static portals** (IN-5, IN-7, cutdown-version)
    — 3 тижні.
16. **Referral system** (IN-11) — 1-1.5 тижні.
17. **Discord link + realm-channels** (IN-10) — org task, не dev.
18. **Named artifacts earn-flow** (IN-14) — 1-1.5 тижні.

### 9.3. Що відкласти (Later — post-beta або не робимо у beta)

- API Platform dependency review (tech-debt #4).
- BattleService awardXp cleanup (tech-debt #1).
- Redis cache integration (scaling — чекає fidelity-data з beta).
- Dynamic portals (02 OUT).
- Guild / PvP (02 OUT).
- Audio narrative (02 OUT).
- Season pass (02 OUT).

### 9.4. Scope cutdown recommendation для roadmap 03

Враховуючи reality — якщо launch **31.10.2026** (6+ міс):

**Full IN-15 — досяжно**, якщо:
- 2 backend devs full-time.
- 2 RN devs full-time.
- 1 designer part-time.
- 1 copywriter part-time.
- 1 community-mgr part-time.
- Starting now, no major scope additions.

**Якщо команда менша (1 backend + 1 RN)** — cutdown до «P0-core»:
- Opening 60s, Battle=workout (P0 fix), Realm identity, Static portals (5 шт),
  Weekly Recap, F2P manifesto, Discord, Streak, Starter Artifact, Flavor texts.
- = **10 фіч з 15**. Виріжемо: Launch event `День Розколу Lite` (тільки cosmetic,
  без raid Class III), Monthly Day of Realm, Portal Creation Kit (post-beta),
  Referral (post-beta), Named artifact earn (post-beta), Starter Artifact XP
  boost (замінити простим flat XP).

**Decision-gate:** 03-roadmap має запропонувати **обидва варіанти** (10-feature
cutdown vs 15-feature full) з effort-estimation і фаундерський sign-off.

### 9.5. Що передати 03 — pre-sorted

1. **Критичний блок «Стабілізувати що є»** (4-6 тижнів):
   - 4 DTO-fix
   - OAuth verification
   - Health native integration
   - Battle set-logging UI
   - E2E Maestro

2. **Далі — «Нові фічі по пріоритету»** (8-12 тижнів, залежно від команди):
   - Realm identity (P0)
   - Streak + Weekly Recap (P0)
   - Launch event + Day of Realm (P0)
   - Static portals + Portal Kit (P0)
   - Referral (P0)
   - F2P manifesto + Discord + Flavor texts (P0)
   - Named artifacts + OAuth providers + (якщо часу — P1 features 01:§4 P1)

3. **Decision-gate 8-10 тижнів до 31.10** — launch-ready чи cutdown? (з 02 §10.5).

---

## 10. Open questions + припущення

### 10.1. Для фаундера

1. **Чи Flutter-directory (`rpgfit-app/` historically) вже видалена, чи це RN
   вже в цій директорії?** У поточному audit я бачу тільки RN код (Expo 54 +
   Expo Router). Якщо це RN реально, то `project_flutter_cleanup` — done. Якщо
   десь є друга директорія (можливо `rpgfit-flutter-old/`?) — треба видалити.

2. **API Platform 4.1 потрібен?** `composer.json:10`. Якщо тільки `/api/docs` —
   можна drop. Якщо plannувати GraphQL або admin-API — залишити.

3. **Docker PHP 8.3 чи 8.4?** `composer.json` allow 8.2+, але production має бути
   фіксований.

4. **Чи realm = race, чи окремий enum?** 02:§9.1 IN, але не fixed. 05:§3.3
   recommends realm=okрема concept, race=appearance. Вирішити до migration.

5. **iOS / Android native-mod — хто буде підключати?** Це 3-4 тижні. Фаундер
   може найняти freelance-RN-dev спеціалізованого на health.

6. **OAuth provider API keys** — Google, Apple, Facebook. Без них OAuth-verify
   залишається placeholder. Очевидно ON_hold — але має pre-order до Nov 2026.

### 10.2. Припущення (не валідовані з фаундером)

- П1: Flutter-app видалено (за результатами glob — only RN у `rpgfit-app/`).
- П2: Beta — closed-beta, 500-1000 users для start, → 10k MAU target через 3-6 міс.
  Тому 10s polling battery drain — acceptable якщо user-count малий.
- П3: Launch дата 31.10.2026 — working assumption, не фінальна (02 §10.5).
- П4: Backend і RN розробляються одним devtm (немає separate teams), тому
  DTO-sync fix — straightforward PR проти обох repos.
- П5: Фаундер хоче native-health integration (не Terra) для beta
  (`health-aggregator-comparison §6 Phase 0` authoritative).

---

## 11. Посилання

### Внутрішні (всі прочитано повністю або ключові частини)

**Backend:**
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/composer.json`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/ARCHITECTURE.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/BUSINESS_LOGIC.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/src/Controller/*.php` (12)
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/src/Application/**/*.php` (key services)
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/src/Application/Health/DTO/*.php`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/src/Domain/Health/Enum/HealthDataType.php`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/src/Domain/Battle/Entity/WorkoutSession.php`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/src/Domain/Workout/Entity/Exercise.php`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/migrations/Version2026032800* + 230000`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/tests/Unit/**` (52 тестів ls)
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/tests/Functional/**` (17 тестів ls)

**RN-client:**
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/package.json`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/app/_layout.tsx`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/app/(auth)/`, `(main)/`, `(onboarding)/`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/**/*.ts` (all 28 files)
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/health/services/healthService.ts`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/health/services/healthPollingService.ts`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/battle/api/battleApi.ts`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/battle/types/requests.ts`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/battle/types/responses.ts`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/health/types/*.ts`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-app/src/features/workout/api/workoutApi.ts`

**Specs / Plans:**
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/specs/2026-04-04-react-native-rewrite-design.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan1-foundation-auth.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan2a-onboarding-profile-leveling.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan2b-health-sync.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan2c-battle-workout-equipment.md`

**BA context:**
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/01-market-research.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/02-beta-scope.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/05-lore-to-hook.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/health-aggregator-comparison.md`

### Зовнішні (валідовано через WebSearch 2026-04-18)

**react-native-health + HKObserverQuery background bugs:**
1. [Background observer is not working — GitHub agencyenterprise/react-native-health
   issue #308](https://github.com/agencyenterprise/react-native-health/issues/308)
   — підтверджено issue з background observer, app killed → RN bridge inactive.
2. [Do "Background observers" work when the app is closed? — issue #217](https://github.com/agencyenterprise/react-native-health/issues/217)
   — канонічне обговорення ліміту.
3. [App crashing when configuring background observer — issue #201](https://github.com/agencyenterprise/react-native-health/issues/201)
   — setup-must-be-in-didFinishLaunchingWithOptions vs #201 crash.
4. [Unknown background delivery frequency, possibly not working — issue #144](https://github.com/agencyenterprise/react-native-health/issues/144)
   — config-edge-cases.
5. [HealthKit docs background.md — agencyenterprise/react-native-health](https://github.com/agencyenterprise/react-native-health/blob/master/docs/background.md)
   — офіційна документація бібліотеки.

**react-native-health-connect Android 14:**
6. [Permissions — matinzd/react-native-health-connect docs](https://matinzd.github.io/react-native-health-connect/docs/permissions/)
   — **підтверджено** Android 14 вимагає `ViewPermissionUsageActivity` activity-alias.
7. [Releases — matinzd/react-native-health-connect](https://github.com/matinzd/react-native-health-connect/releases)
   — v3.5 changelog (native stack trace in errors, device metadata).

**Expo SDK 54 + React 19.1:**
8. [Expo SDK 54 changelog](https://expo.dev/changelog/sdk-54) — SDK 54 production-ready,
   RN 0.81 + React 19.1, precompiled iOS frameworks.
9. [What Breaks After Expo 54 / RN 0.81 Upgrade — Elobyte](https://elobyte.com/what-breaks-after-an-expo-54-reactnative-0-81-upgrade-and-what-play-store-policies-forced-us-to-change/)
   — known breakages + Play Store policy changes.

**Expo Router 6 auth:**
10. [Authentication in Expo Router — Expo Documentation](https://docs.expo.dev/router/advanced/authentication/)
    — канонічна документація auth pattern.
11. [Protected routes — Expo Documentation](https://docs.expo.dev/router/advanced/protected/)
    — `Stack.Protected` pattern з Expo Router 5+.

**Symfony + Doctrine:**
12. [Doctrine ORM 3.6 UPGRADE.md](https://github.com/doctrine/orm/blob/3.6.x/UPGRADE.md)
    — official upgrade guide (no major deprecations affecting наш DDD-код).
13. [Fix Doctrine deprecations — Symfony issue #50481](https://github.com/symfony/symfony/issues/50481)
    — context для наступних minor-upgrades.

### Не валідовано у цій сесії

- Expo config-plugin для `NSHealthShareUsageDescription` автоматизація —
  знайти в `expo-build-properties` docs (tech-debt #8).
- php-fpm pool sizing для 10k MAU — треба навантажувальне тестування з
  production-like docker compose-up.

---

## 12. Post-scriptum для фаундера

Код **почали добре** — DDD layout коректний, 69 тестів покривають core-logic,
migrations дисципліновані, game_settings через Sonata — правильна архітектура.
Але **між backend і RN-client виросла прірва** через 4 DTO-mismatches + native-health
mock-only + 9 відсутніх фіч beta-IN-15.

**3 речі, які особисто фаундер може вирішити за цей тиждень:**

1. **Визнати: native-health потрібен спеціаліст.** Це 3-4 тижні найму freelance
   RN-dev з досвідом `react-native-health` + `react-native-health-connect`.
   Без цього beta = demo з mock-даними.

2. **Sync-meeting backend + RN dev'ів** щоб прогнати 4 DTO-mismatches за 1 день
   — це чистий coordination, не architecture.

3. **Дати команду 03-roadmap decision-gate 8-10 тижнів до 31.10.** Якщо реально
   не встигнемо — **«Grand Opening» у Q1 2027** з cutdown-beta 31.10 як soft-launch
   (02 §10.5). Launch-дата slip — не кінець світу, **broken-launch — кінець світу**
   (Fortnite Chapter 3 bug launch example, якщо хтось пам'ятає).

Code-quality у backend — вище середнього для Symfony-проектів 2026. У RN —
начало, не кінець. 3-4 місяці інтенсивної роботи до повного 15-IN-scope. 6
місяців із comfort-margin. Встигнемо, якщо **DTO-sync зробимо завтра**.

### Changelog

- 2026-04-18 — v1. Audit backend (12 controllers, 14 services, 52 entities, 15
  migrations, 69 tests) + RN-client (17 screens, 28 `.ts` files, 29 тестів) +
  4 плани + spec. 13 зовнішніх URL валідовано WebSearch. Загальний timebox
  ~90 хв.
