# 03 — Development Roadmap

> **Автор:** BA-агент 03 (tech lead / delivery planner).
> **Дата:** 2026-04-18.
> **Статус:** v1. Авторитетний вхід для розробки і для re-run `02-beta-scope.md`.
>
> **Методика.** Побудував план з дерева залежностей, а не wish-list:
> (а) вхід — `04-code-audit.md` gap-таблиця + `02-beta-scope.md` IN-15
> (скорегований під D3/D4/D5 з `product-decisions-2026-04-18.md`) +
> `01-market-research.md` P0/P1/P2, (б) часові оцінки — зовнішні джерела
> або явний `[команда — потребує підтвердження]`, (в) real-world
> параметри команди: **1 backend dev + 1 RN dev + founder як PM/design/content**.
>
> **Маркери:**
> - `[код: file:line]` — конкретне посилання на існуючий код (переважно з 04).
> - `[BL §X]` — `rpgfit-beckend/docs/BUSINESS_LOGIC.md`.
> - `[02:§X]` — `BA/outputs/02-beta-scope.md`.
> - `[04:§X]` — `BA/outputs/04-code-audit.md`.
> - `[D1..D5]` — founder-рішення з `docs/vision/product-decisions-2026-04-18.md`.
> - `[перевірено URL 2026-04-18]` — web-валідовано (див. §11).
> - `[команда — потребує підтвердження]` — моя оцінка без зовнішнього пруфу.

---

## 1. TL;DR (5 буллетів)

1. **Перші 2 тижні (T-28…T-26) — ТІЛЬКИ стабілізація, жодної нової фічі.**
   Week 1 — 4 критичних DTO-bug-и + OAuth placeholder + 2 Sonata-admin seeds
   для Portal/Realm-тегу. Week 2 — Battle set-logging UI + видалення
   race-домену (D4) + integration test full-battle-cycle. Без цього нічого
   інше не має сенсу — app зараз **не проходить health-sync** [04:§4.2 #1-4].

2. **Beta-ready дата — 24.10.2026 (soft) / 31.10.2026 (launch «День
   Розколу»), 27 тижнів роботи.** Це **реалістично для команди
   1+1+founder**, якщо (а) D3/D4 викинули ~3 тижні race/realm-identity
   роботи, (б) D5 додав ~2 тижні social-infra, (в) Portal Creation Kit
   + 10 static portals — в скоупі `[команда — потребує підтвердження]`.

3. **Critical path — не те, що ви думаєте.** Hero-feature «Battle=workout»
   вже на 75% готовий, але **блокується native health-sync** (зараз
   MockHealthService, 3-4 тижні на iOS+Android native) — це найдовша
   single-developer залежність і **вона блокує ВЕСЬ core loop**
   [04:§5]. Тому Weeks 3-6 RN-dev сидить на health-integration, backend-dev
   в цей час робить Realm-тег, Streak, Starter Artifacts і Event-entity
   паралельно.

4. **D5 social-infra (active-players counter, activity feed, co-op damage
   bonus, event share-cards) — новий P0 блок, 3-4 тижні backend + 2 тижні
   RN**, лягає у Weeks 11-16. Це ставить під тиск Week 17-22 launch event
   infrastructure, тому treba **фіксований scope-gate 15.08.2026**
   (T-11 тижнів до 31.10): якщо Portal Kit OR Launch Event infra не
   готові — режемо до cutdown-Lite (div §7).

5. **Stop-signals:** (a) 01.07.2026 — native health-sync не працює на
   physical device → переносимо launch. (b) 15.08.2026 — Launch Event
   infra не в code-freeze → тільки cosmetic event без raid Class III.
   (c) 15.09.2026 — Apple review не пройдено з HealthKit entitlement →
   beta тільки Android або перенос. (d) TestFlight beta (T-6, ~19.09) має
   <30% D7 retention → не pushemo public launch на 31.10, переносимо.

---

## 2. Скорегований IN-скоуп (після D3/D4/D5)

Нагадую — `02-beta-scope.md §4.1` мав 15 IN-фіч. `product-decisions-2026-04-18.md`
перезавантажив:

| # | Фіча (з 02 §4.1) | Статус після D-decisions | Джерело |
|---|-------------------|--------------------------|---------|
| 1 | Opening 60s (voice-over + 3 кадри + ~~faction-pick~~) | **IN**, але **без faction-pick кроку** (D3) — заміна на single "Пробудження" тап | D3 [product-decisions] |
| 2 | **Battle = workout** (Hero) | IN, без змін | — |
| 3 | ~~6-realm faction identity~~ | **CUT** (D3) — realm лишається лор+тег, не gameplay identity | D3 |
| 4 | Weekly Fitness Recap | IN, без змін | — |
| 5 | Portal Creation Kit × 1 | IN, без змін | D2 keeps portals |
| 6 | Starter Artifact Tier 1 + XP boost 24h | IN, без змін | — |
| 7 | 10-15 статичних порталів | IN, **cut до 10** (scope-discipline) | [команда — потребує підтвердження] |
| 8 | Launch event «День Розколу 31.10» | IN, **розширений infra** (D5) | D5 |
| 9 | Monthly «Day of Realm» | IN, без змін | — |
| 10 | Discord setup + bench | IN (org, не dev) | — |
| 11 | Referral: Portal Kit | IN, без змін | — |
| 12 | F2P-manifesto in-app | IN, без змін | — |
| 13 | Streak multiplier | IN, без змін | — |
| 14 | Named artifact за running-streak | IN, без змін | — |
| 15 | Onboarding flavor-texts (10) | IN, без змін | — |
| **NEW 16** | **Mid-beta event** (weekend-boss, ~T+3 тижні після launch) | **P0** новий | D5 |
| **NEW 17** | **Public activity feed** (recent kills-feed у portal-detail та global) | **P0** новий | D5 |
| **NEW 18** | **Active-players counter** на порталі (WebSocket або 30s poll) | **P0** новий | D5 |
| **NEW 19** | **Event share-cards** (окремо від Weekly Recap) | **P0** новий | D5 |
| **NEW 20** | **Co-op damage bonus** (якщо N юзерів бʼють mob одночасно) | **P1** новий | D5 |

**Чисте дельта після D-decisions:**

- **−1 складна фіча** (realm-pick onboarding крок, realm damage modifier,
  realm-lock UI).
- **−1 складна очистка** (race-домен: `CharacterRace` enum, 5 race-passive
  skills, race-pick крок, RN types, тести).
- **+3 P0 social-infra** (activity feed, active-players counter, event share-cards).
- **+1 P0 (mid-beta event)** — це **не** нова фіча infra, це **додатковий
  content-drop** на вже побудованому Event-entity (новий data, не новий код).
- **+1 P1 (co-op damage bonus)** — реально небагато backend-роботи,
  великий RN visual polish.

**Чистий ефект: ~~−3 тижні~~ + ~~+4-5 тижнів~~ = +1-2 тижні до скоупу
vs original 02-плану**, але scope **ближче до real team velocity** (1+1+F).

---

## 3. Матриця IN-beta × поточний стан

Скомпоновано з `04-code-audit.md §4.1`, §6 і скорегованого скоупу §2.

| # | Feature | Backend | RN client | Tests | Gap до beta-ready |
|---|---------|---------|-----------|-------|--------------------|
| **P0-FIX** | **HealthDataType enum case** [04:§4.2#1] | enum UPPERCASE [`src/Domain/Health/Enum/HealthDataType.php:10-24`] | client lowercase [`src/features/health/types/enums.ts:2-17`] | — | **Вирівняти на lowercase**. Migration `UPDATE health_data_points SET data_type=LOWER(...)`. ~1 день. |
| **P0-FIX** | **Battle exerciseSlug vs exerciseId** [04:§4.2#2] | слухає `exerciseSlug` [`BattleService.php:125`] | шле `exerciseId` [`rpgfit-app/src/features/battle/types/requests.ts:3`] | — | Змінити RN type на `exerciseSlug`. ~0.5 дня. |
| **P0-FIX** | **Workout activityCategory camelCase** [04:§4.2#3] | `WorkoutPlanController.php:69` camelCase | `workoutApi.ts:4` snake_case | — | RN клієнт переводимо в camelCase. ~0.5 дня. |
| **P0-FIX** | **Health Summary snake vs camel** [04:§4.2#4] | `HealthSummaryService.php:93-99` snake | client [`responses.ts:7-14`] camel | — | Backend → camelCase (контроллер має зробити convertKeys). ~1 день. |
| **P0-FIX** | **OAuth token verification placeholder** [04:§8.7] | `OAuthController.php:88-95` placeholder | OAuth UI не існує (TODO з specи) | — | Google token verify through `google/apiclient` (~1 день). Apple через JWKS (~1 день). Facebook можна після beta. **Security-must.** |
| **P0-FIX** | **Battle set-logging UI** [04:§4.1 Battle] | Works (API OK) | `app/(main)/battle/index.tsx:22` `exercises: []` hardcoded → 0 damage завжди | — | Треба Log Set UI (picker exercise + weight + reps). ~5-7 днів. |
| **P0-FIX** | **D4 race cleanup** [D4] | `CharacterRace` enum + 5 race-passives seeded | RN race-pick step в `(onboarding)` + `characterRace` в types | 4 unit тестів mention `CharacterRace` | Backend 2-reliz migration: колонка→`human`, видалити enum, прибрати seeding 5 passives. RN: прибрати race step, types. ~3-4 дні сумарно. |
| **1** | Opening 60s (single tap, без faction-pick) | — (нічого не треба) | Screen немає | — | Новий screen, 3 frame-анімація, voice-over (UA+EN). ~10-14 днів `[команда — потребує підтвердження]`. |
| **2** | Battle = workout | **90%** [04:§3.1 Battle] | **25%** через 3 DTO + no set-logging | 3 Unit + 1 Func | Після P0-FIX — **hero feature готова на 85%**. Real-time HR pending native health. |
| **4** | Weekly Fitness Recap | 0% | 0% | — | Backend cron + render (HTML→PNG або серверний GD), `/api/recap/weekly`, Expo push setup. ~10-12 днів. |
| **5** | Portal Creation Kit ItemType | 0% — enum лише `equipment/scroll/potion` | 0% | — | Enum + migration, `Portal` entity, `/api/portals/create` endpoint, expo-location UI. ~12-15 днів. |
| **6** | Starter Artifact Tier 1 + XP boost 24h | Partial: ItemType `potion` existing з duration | 0% UI для modal | — | Seed-command 3 artifacts + hook в registration flow + RN modal. ~3-4 дні. |
| **7** | 10 статичних порталів (cut з 15) | 0% | 0% | — | Поверх #5 — Portal seed з 10 locations + static fetch API + RN list/detail. ~7 днів поверх #5. |
| **8** | Launch event «День Розколу» 48h + D5 infra | 0% | 0% | — | `Event` entity, event-aware mob spawn, rare-drop logic, **active-players counter (WebSocket/SSE або 30s poll)**, **activity feed**, **event share-card**. ~18-21 днів (з D5). |
| **9** | Monthly «Day of Realm» | 0% (залежить від #8 Event) | 0% | — | Re-use Event entity, cron-схема ротації, +1 тиждень поверх #8. |
| **11** | Referral: Portal Kit both | 0% | 0% | — | `ReferralCode` entity, generate/redeem endpoints, RN referral screen. ~7 днів. |
| **12** | F2P-manifesto in-app | 0% (static) | 0% | — | Settings-screen з markdown-render. ~2 дні. |
| **13** | Streak multiplier | 0% impl, `[BL §4.3]` specs готові | 0% | — | `StreakService` + integration з `XpAwardService` + UI-badge. ~5-7 днів. |
| **14** | Named artifact streak-earn (Мйольнір = 7d+10km) | 0% | 0% | — | `ArtifactEarnRuleService` + cron check + notify. ~7 днів. |
| **15** | Onboarding flavor-texts | 0% (content) | Використовуються в screens | — | Copy-writer work + hook у screens. ~2-3 дні founder-content. |
| **16** | Mid-beta event (content) | 0% (infra з #8) | 0% | — | Лише content-drop (new event через Sonata admin + mob-balance). ~2 дні dev + 3 дні content. |
| **17** | Public activity feed | 0% | 0% | — | Включено в #8 estimate. Backend endpoint + RN screen. |
| **18** | Active-players counter | 0% | 0% | — | Включено в #8. WebSocket або polling. |
| **19** | Event share-cards | 0% | 0% | — | Спільна інфра з Weekly Recap #4 + додатковий template. ~3 дні поверх #4. |
| **20** | Co-op damage bonus | 0% | 0% | — | Mini: коли battle на порталі, backend перевіряє N юзерів-active у вікні 10хв → бонус. ~3-4 дні. **P1 — скидається якщо scope-tight.** |

**Додаткові P0 не-featury (з 04):**

- **Integration battle-flow тест** [04:§8.4] — 3 дні, Week 2.
- **Maestro E2E × 3 (auth / onboarding / battle happy-path)** [04:§8.4] —
  1 тиждень, Week 2-3.
- **iOS Info.plist + Android permissions config** [04:§7 tech-debt #8-9]
  — 1 день, Week 1-2.
- **`BattleService::awardXp` placeholder cleanup** [04:§7 #1, §1 TL;DR] —
  30 хв, Week 1 разом з DTO-fix.

---

## 4. Dependency graph (critical path)

```
              ┌───────────────────────────────────────────────────────────────────┐
              │                        WEEK 1 (T-27)                              │
              │  P0-FIX 4 DTO + OAuth + Sonata-admin portal/realm seeds          │
              │  + BattleService placeholder cleanup + D4 race cleanup Stage 1   │
              └────────────────────────────────┬─────────────────────────────────┘
                                               │
              ┌────────────────────────────────▼─────────────────────────────────┐
              │                        WEEK 2 (T-26)                              │
              │  Battle set-logging UI  +  D4 race cleanup Stage 2 (RN)          │
              │  + Integration battle-flow test + Maestro E2E × 3                │
              │  + iOS Info.plist + Android permissions configs                  │
              └────────────────────────────────┬─────────────────────────────────┘
                                               │
      ┌────────────────────────────┬───────────┴──────────────┬────────────────────┐
      │                            │                          │                    │
      ▼ BACKEND TRACK              ▼ RN / NATIVE TRACK        ▼ CONTENT TRACK     │
┌─────────────────────┐     ┌──────────────────────────┐  ┌─────────────────────┐ │
│ Week 3-4 Realm tag  │     │ Week 3-6 NATIVE HEALTH   │  │ Week 3+ Ongoing     │ │
│  on Mob + Portal    │     │  iOS HKObserverQuery +   │  │  - Flavor texts ×10 │ │
│  (D3 — tag only,    │     │  background delivery     │  │  - Mob realm tag    │ │
│   no bonus)         │     │  config-plugin           │  │    backfill         │ │
│                     │     │  Android Foreground      │  │  - 10 static portal │ │
│ Week 4-5 Streak +   │     │   Service + Changes API  │  │    locations + img  │ │
│  Starter Artifact   │     │  Week 6 DTO regression   │  │  - 10-15 artifact   │ │
│  seed + F2P-scrn    │     │   test (physical device) │  │    art + flavor     │ │
└─────────────────────┘     └──────────────────────────┘  └─────────────────────┘ │
      │                            │                          │                    │
      ▼                            ▼                          ▼                    │
┌─────────────────────┐     ┌──────────────────────────┐                            │
│ Week 6-8 Portal     │     │ Week 6-8 Opening 60s +   │                            │
│  Kit ItemType +     │     │  starter-artifact modal  │                            │
│  Portal entity +    │     │  + F2P-manifesto screen  │                            │
│  create endpoint    │     │                          │                            │
└──────────┬──────────┘     └──────────────┬───────────┘                            │
           │                               │                                        │
           └───────────┬───────────────────┘                                        │
                       ▼                                                            │
            ┌──────────────────────┐                                                │
            │ Week 9-10            │                                                │
            │ Portals list + detail│                                                │
            │ + virtual-replica    │                                                │
            │ + Create Portal flow │                                                │
            └───────────┬──────────┘                                                │
                        │                                                           │
                        ▼                                                           │
┌─────────────────────────────────────────────────────────────────────────┐         │
│ Week 11-13 D5 SOCIAL-INFRA (blocks Launch Event!)                       │         │
│   Public activity feed (backend endpoint + RN screen)                   │         │
│   Active-players counter (SSE or 30s poll)                              │         │
│   Event share-cards (re-use Weekly Recap template)                      │         │
│   Weekly Fitness Recap (cron + push + shareable card)                   │         │
└────────────────────────────────────┬────────────────────────────────────┘         │
                                     ▼                                              │
┌─────────────────────────────────────────────────────────────────────────┐         │
│ Week 14-17 LAUNCH EVENT INFRA (CRITICAL PATH)                           │         │
│   Event entity + event-aware mob spawn                                  │         │
│   Rare-drop logic (boss-mob named artifact)                             │         │
│   Monthly Day of Realm cron schedule                                    │         │
│   Referral: Portal Kit (generate + redeem)                              │         │
│   Named artifact streak-earn flow                                       │         │
└────────────────────────────────────┬────────────────────────────────────┘         │
                                     ▼                                              │
                       ┌─────────────────────────────┐                              │
                       │ Week 18-19                  │ ◀──── SCOPE-GATE 15.08 ──────┘
                       │ Co-op damage bonus (P1 —    │       (T-11 weeks to 31.10)
                       │  scope-cuttable)            │       GO/CUT decision
                       │ TestFlight closed-beta (50  │       — див. §7 Cutdown scope
                       │  Discord-seeds)             │
                       └─────────────┬───────────────┘
                                     ▼
                       ┌─────────────────────────────┐
                       │ Week 20-22                  │
                       │ Bug-bash + polish           │
                       │ Public Reddit beta-announce │
                       │ F2P-manifesto launch        │
                       │ Apple / Google submit       │
                       └─────────────┬───────────────┘
                                     ▼
                       ┌─────────────────────────────┐
                       │ Week 23-24                  │
                       │ App Store review (7 days)   │
                       │ Google Play review (1-3 d)  │
                       │ Discord bench final         │
                       └─────────────┬───────────────┘
                                     ▼
                       ┌─────────────────────────────┐
                       │ Week 25-27 (T-2…T)          │
                       │ Soft-launch 24.10.2026      │
                       │ Mid-beta event content      │
                       │ День Розколу 31.10-02.11    │
                       └─────────────────────────────┘
```

### Критичні залежності (по tier'ах)

1. **P0-FIX блокує все** — Week 1-2 не пропускаємо. Без DTO-fix навіть
   закритий beta-тест беззмістовний [04:§1, §4.2].
2. **Native health (Weeks 3-6) блокує Hero Battle=workout real-time HR.**
   Без native health — mock-дані, ніяких real XP, no retention
   [04:§5]. `react-native-health` Expo config-plugin **не підтримує
   background delivery** [перевірено [react-native-health/docs/Expo.md](https://github.com/agencyenterprise/react-native-health/blob/master/docs/Expo.md)
   2026-04-18] → потрібен custom-plugin або переключення на
   `kingstinct/react-native-healthkit` який `supports enableBackgroundDelivery()`
   frequency immediate/hourly/daily [перевірено [kingstinct](https://kingstinct.com/react-native-healthkit/)
   2026-04-18]. **Рекомендація: перейти на `kingstinct/react-native-healthkit`
   у Week 3**, це економить ~1 тиждень vs писати custom-plugin.
3. **Portal Kit blocks Static portals** (Weeks 6-10) — треба ItemType
   enum + Portal entity перед тим, як seedити 10 локацій.
4. **D5 social-infra (activity feed, active-players counter) — нова
   залежність для Launch Event.** Без них Event — це «просто подія»,
   не «подія з глядачами». Weeks 11-13 — це нова робота, яку 02 не
   враховував. Вона **блокує** Launch Event на Week 14-17, тому
   critical-path.
5. **Launch Event (Weeks 14-17) blocks launch-marketing** — без неї
   беті в launch-дні 31.10 нема чим SHARE-ити.
6. **App Store review 7 days typical** [team — з HealthKit entitlement,
   fitness app, потрібно явний privacy policy + `NSHealthShareUsageDescription`
   + `NSHealthUpdateUsageDescription` [перевірено [Apple Health & Fitness
   Design Guidelines](https://developer.apple.com/design/human-interface-guidelines/healthkit)
   2026-04-18]] — **має бути submit ≤ T-3 тижні (≤10.10.2026)**.

---

## 5. Roadmap Now / Next / Later

**Reference date:** 31.10.2026 = Day 0 = «День Розколу». Планую 27 тижнів
від 2026-04-18. Calendar-week номер відраховую від «01.05.2026 = Week 1».

**Команда (baseline):**
- **BE** — 1 backend dev (Symfony/PHP).
- **RN** — 1 React Native dev.
- **F** — founder (PM/design/content/community).

### 5.1. NOW (Weeks 1-4, 01.05–28.05)

**Ціль:** стабілізувати існуючий код. Нічого не ламати. Pre-ship all
critical fixes.

| Week | Хто | Робота | Блокери / Залежності | Checkpoint (готово = ?) |
|------|-----|--------|----------------------|-------------------------|
| **1** | BE | 4 DTO-fix [04:§4.2]: (a) HealthDataType → lowercase + migration, (b) workout `activityCategory` backend парсинг fallback на snake (temporary), (c) health summary → camelCase (convertKeys в controller), (d) BattleService placeholder cleanup [04:§7 #1]. OAuth Google token verify [04:§8.7]. Admin-screens seeds prep. D4 migration Stage 1 (UPDATE всіх users → human). | — | `vendor/bin/phpunit` green. Manual curl test /api/health/sync приймає lowercase. |
| **1** | RN | DTO-fix на клієнті: exerciseSlug rename, activityCategory camelCase, health types → camelCase. Видалити race-pick з `app/(onboarding)/index.tsx` + `characterRace` з types (D4 Stage 1). Jest snapshot update. | BE DTO done T+1d | `npm test` green (29 → ~29 тестів). `detox` smoke test (health-sync доходить до 200). |
| **1** | F | Redraft `BUSINESS_LOGIC.md §2` (прибираємо race з stats block, D4). Draft content-list: 10 flavor-рядків з 05:§7, imagery-brief для 10 portal locations. | — | BL diff-committed з D3/D4 mark. |
| **2** | BE | D4 Stage 2: видалити `CharacterRace` enum, прибрати 5 race-passives з `SeedSkillsCommand`. Apple OAuth JWKS verify. Integration test full battle-cycle [04:§8.4]. | D4 Stage 1 | `phpunit` green. New `tests/Functional/BattleFlowIntegrationTest.php` зі всіма 5 perfermance tier cases [04:§3.2 #1]. |
| **2** | RN | Battle set-logging UI: Log Set picker (exercise dropdown + weight + reps + SubmitBtn). iOS Info.plist через Expo config plugin: `NSHealthShareUsageDescription`, `NSHealthUpdateUsageDescription` [04:§7 #8]. Android app.json permissions: `WAKE_LOCK`, `FOREGROUND_SERVICE_HEALTH`, `activity.ViewPermissionUsageActivity` для Android 14+ [04:§5.3]. Maestro setup: 3 flows (auth, onboarding, battle). | DTO fix done | Maestro 3 flows green. Set-logging UI screenshot review з F. |
| **2** | F | Seed content: 10 portal locations (from 02:§8.2), 10 artifacts (Tier 1-2) з flavor і imagery URL (stock for MVP). Sonata admin-screen walk-through з BE. | Sonata admin working | Portal seed-CSV готовий, artifact seed-CSV. |
| **3** | BE | Mob `realm` тег (D3 — tag only, без damage bonus). Migration + backfill script (distribute existing 2000 mobs по 6 realm + neutral). Streak service [BL §4.3]: новий `StreakService`, integrate з `XpAwardService`, seed `bonuses` category в GameSetting. | Week 2 done | `SELECT COUNT(*), realm FROM mobs GROUP BY realm` shows balanced distribution. Streak test: log 3 days → 1.1x multiplier. |
| **3** | RN | **NATIVE HEALTH WORK STARTS.** Switch з `react-native-health` на `kingstinct/react-native-healthkit` (support background delivery) [перевірено [kingstinct.com/react-native-healthkit](https://kingstinct.com/react-native-healthkit/) 2026-04-18]. iOS: request permissions flow, HKObserverQuery setup для 15 types, enableBackgroundDelivery('immediate'). DEV build (`eas build --profile development`). | Week 2 configs done | iOS dev build installed на physical iPhone, sync-button returns real step count. |
| **3** | F | 10 flavor-рядків finalize [05:§7]. Voice-over brief для Opening 60s (UA + EN). Discord server create, 6 realm-channels + 4 functional. Hire community-mgr (or self-serve). | — | Discord server live with 20+ seed posts (fan-art, memes, lore teaser). |
| **4** | BE | `ItemType.portal_kit` enum + migration. `Portal` entity skeleton: `{id, name, type(static/dynamic/user), realm, lat, lng, radiusMeters, ownerUserId?, createdAt, expiresAt?}`. `/api/portals` list endpoint (static only). Starter Artifact seed-command (3 artifacts linked to `onboarding_gift`). | Week 3 done | `app:seed-starter-artifacts` works. Portal list returns 10 seeded static portals. |
| **4** | RN | Android: Health Connect integration. Foreground service with ongoing notification. `ChangesClient.getChanges` polling — **30 sec** під час workout (НЕ 10 sec, відповідно до Google guidance: max 15 min recommended [перевірено [Android Health Connect workouts](https://developer.android.com/health-and-fitness/health-connect/experiences/workouts) 2026-04-18], 30s є розумним компромісом для «real-time HR»), 5 min idle. Android 14+ permission rationale flow [04:§5.3]. | Week 3 iOS prereq | Android dev build installs on Pixel 7 / Android 14, permissions granted, sync returns steps. |
| **4** | F | 10 artifact imagery (stock-art allowed for beta). Record Opening 60s voice-over (UA perfect, EN later-acceptable). Discord bench: 2-3 volunteer moderators identified. | — | Voice-over files in `rpgfit-app/assets/audio/`. Discord mod-list. |

**NOW checkpoint (end of Week 4, 28.05.2026):**
- [ ] All 4 DTO + OAuth fixes shipped, phpunit + jest green.
- [ ] Battle set-logging UI live, integration test full-flow passes.
- [ ] D4 race cleanup done (backend + RN, enum deleted, tests updated).
- [ ] iOS + Android dev builds sync real health data (physical devices).
- [ ] Streak service working, verifiable via `/api/levels/progress`.
- [ ] 10 seed portals + 3 starter artifacts in DB.
- [ ] Discord server has ≥50 seeded members.
- **Якщо на 28.05 native health sync не працює на physical device — це
  critical STOP-signal (див. §8).**

### 5.2. NEXT (Weeks 5-17, 29.05–27.08)

**Ціль:** всі IN-фічі завершені, inкл. D5 social-infra та Launch Event.

| Week | Хто | Робота | Checkpoint |
|------|-----|--------|------------|
| **5** | BE | Weekly Recap cron: aggregate 7d health + battles + level-ups, generate JSON for RN render. `/api/recap/weekly?userId=...` endpoint. F2P-manifesto API (static markdown served). | Recap-preview endpoint returns proper JSON. |
| **5** | RN | Starter Artifact modal на first-login, XP boost 24h counter UI. F2P-manifesto screen (markdown viewer). Streak badge UI + streak warning push schedule. | Manual TestFlight: new user → gets modal → XP boost active. |
| **6** | BE | Native health regression test (physical device + 15 data types). Tighten race migration (drop column, verify no fall-back). | All 15 types sync on both platforms. |
| **6** | RN | **Opening 60s screen** (voice-over + 3-frame animation + «Пробудження» tap, без faction-pick per D3). Expo-av for audio. | Screen visible on first-launch on both iOS + Android. |
| **6** | F | Portal imagery finalize (10 pcs). Flavor-texts translated EN (founder + freelance). | Assets in repo. |
| **7** | BE | `Portal` POST /create endpoint (with geo-validation: accuracy ≤50m, speed-check <15km/h sanity). Portal Kit reward logic after battle (1 per 14d). | Create-portal works for test user, 14d cooldown enforced. |
| **7** | RN | Portal Creation screen (geo picker via `expo-location`, name input, realm dropdown for flavor). Referral screen skeleton (no backend yet). | Create-portal UI live. |
| **8** | BE | Portal list API with filtering (realm, type, bbox). Portal detail API (included artifact rewards). Portal-battle-entry endpoint (starts battle with portal.realm mobs). | Portal detail shows artifact reward list. |
| **8** | RN | Portals list screen + detail + virtual-replica flow ("ти далеко від Галдхьопігену — пройди віртуальний"). Weekly Recap shareable card (RN Skia або server-rendered PNG). | List renders 10 portals, tap → detail, virtual-battle starts. |
| **9** | BE | Named Artifact Streak Earn: `ArtifactEarnRuleService`, cron check 1×/day (7d streak + 10km running → grant Мйольнір). Event entity skeleton: `{id, name, type, startAt, endAt, modifiers: JSON, rewards: JSON}`. | Manual test: fake 7d streak in DB → next cron → Мйольнір granted. |
| **9** | RN | Weekly Recap push notifications via Expo Push Service. Shareable card screenshot flow. Named artifact grant-notification UI. | User with 7d streak gets push on day 8. |
| **10** | BE | Event-aware mob spawn in `BattleMobService`: check active events, apply spawn modifiers (realm-rare +20%). Referral codes: entity + generate/redeem endpoints. | Test event in DB → battle shows boosted rare-mob rate. |
| **10** | RN | Referral screen (generate code + share-link). Portal Creation referral flow (enter code → both get bonus). | Two test accounts complete referral handshake. |
| **11** | BE | **D5 SOCIAL-INFRA Phase 1.** Public Activity Feed: `/api/feed/recent?portalId=&realm=&limit=`. Event share-card API (stats → HTML → PNG via Puppeteer). | Feed returns last 20 kills per portal. |
| **11** | RN | Activity Feed screen (pull-to-refresh, infinite scroll, flavor-text per kill). | Feed renders with mock + real data. |
| **12** | BE | **D5 Phase 2.** Active-players counter: SSE endpoint `/api/portals/{id}/live-players` (counts sessions in last 10min). Co-op damage bonus calc in `BattleResultCalculator` (if ≥2 users hit same mob in 5min → +10% reward). | SSE streams 1-N updates per 30sec. |
| **12** | RN | Active-players UI on portal detail + battle screen. Event share-card preview screen (event result → "I did ..."). | Live counter ticks up when second user joins (test). |
| **13** | BE | **D5 Phase 3.** Monthly Day of Realm cron (`app:schedule-monthly-events` — schedule Oct/Nov/Dec event in DB). Mid-beta event content prep (weekend-boss named "Перший Йотун"). | Next-month event visible via admin. |
| **13** | RN | Day of Realm banner UI in home screen + push notification. F2P-manifesto in-app polish. | Banner visible when event active. |
| **14** | BE | **LAUNCH EVENT «День Розколу» infra.** Rare-boss mob logic (Class III, spawns only during event window), named-artifact drop (top 1000 per realm). Event-result aggregation (leader, top 100, etc.). | Test event in DB → run 100 fake battles → top-list generated. |
| **14** | RN | Event landing in-app (visible 7 days before). Event countdown + call-to-action. Global event share-card variant. | Landing visible in test schedule. |
| **15** | BE + RN | Cross-testing: Full event-cycle happy path (countdown → active → cooldown → results). Maestro E2E for event participation. | 1 Maestro flow for event. |
| **15** | F | **Reddit AMA draft** [02:§6.5], Discord bench now 6+ mods, recruit 500+ seed members. Announce closed TestFlight. | Discord at 500+ seeds. |
| **16** | BE | Android foreground service polish (battery optimisation, user-toggleable "High accuracy mode"). Performance: Redis for GameSetting cache [04:§7 #5] (only if load-test shows >500 req/sec). | Battery drain <5% per 1h workout [team — потребує підтвердження]. |
| **16** | RN | Polish pass: loading states, error boundaries, empty states, copy-review (all flavor from 05:§7 integrated). | Manual walkthrough: no "undefined"/"null" strings. |
| **17** | BE | Bug bash backend — fix critical issues from TestFlight beta. | 0 P0 issues in Linear/GitHub. |
| **17** | RN | Bug bash RN + accessibility pass (VoiceOver, TalkBack). | 0 P0 issues. |

**Scope-gate 15.08.2026** (end of Week 17, T-11 weeks): **GO / CUT
decision** (§7). If не на 80%+ на той момент — cut per §7.

### 5.3. LATER (Weeks 18-27, 28.08–31.10)

**Ціль:** bug-polish, TestFlight wide, Apple/Google submit, launch.

| Week | Хто | Робота | Checkpoint |
|------|-----|--------|------------|
| **18** | BE + RN | Co-op damage bonus (P1, can cut if scope-tight). | 2 users hit same mob → both see bonus. |
| **18** | F | Closed TestFlight 50 Discord-seeds. Collect feedback daily. | 50 active testers. |
| **19** | BE + RN | TestFlight feedback fixes (P0 only). Performance perf-test з 100 concurrent users. | <10 P0 bugs outstanding. |
| **19** | F | Reddit AMA publish (r/IndieGaming, r/gamification, r/fitness). F2P-manifesto link live. | ≥500 upvotes on AMA. |
| **20** | BE + RN | TestFlight 200 users wave 2. Fixes. | 200 active testers, D7 retention ≥40% [team — потребує підтвердження]. |
| **20** | F | Mid-beta event content (weekend-boss) prep in Sonata admin. | Event scheduled in DB for T+3w after launch. |
| **21** | BE + RN | Final polish. Privacy policy review. | — |
| **21** | F | Apple Developer app submission package. Google Play Console package. Legal review landmark imagery for portals. | Both builds submitted. |
| **22** | — | **App Store / Google Play review waiting.** Apple fitness apps з HealthKit entitlement — review time 24-48h typical but **нестабільно (від годин до 7+ днів)** [перевірено [Apple App Review Guidelines](https://developer.apple.com/app-store/review/guidelines/) 2026-04-18]. Google Play — 1-3 дні typical, Health Connect apps вимагають Health Connect Declaration (відповідь 1-7 днів) [перевірено [Play Console Health Permissions FAQ](https://support.google.com/googleplay/android-developer/answer/12991134?hl=en) 2026-04-18]. | Both approved OR fixed-rejection and re-submitted. |
| **23** | F | Soft-launch 24.10 — open public beta, no «День Розколу» yet. Discord announce. | Install funnel metrics captured. |
| **23** | BE + RN | On-call for launch issues. Monitor error rates. | <1% error rate. |
| **24** | F | «День Розколу» 31.10 live. 48h event. | 1000+ boss-kills per realm target. |
| **25** | BE + RN | Post-event: bug-fix from event-scale traffic. Weekly recap push first cycle. | First weekly recap delivered to all actives. |
| **26** | F | Mid-beta event (weekend-boss T+3w). | 40%+ of launch-cohort returns. |
| **27** | All | Stabilisation + data collection for 04-code-audit post-launch pass. | Metrics baseline captured. |

---

## 6. Параллелізація треків

Три треки йдуть переважно незалежно після Week 2:

### Track A — Backend (BE dev)
Week 1-2 stabilize. Week 3-5 Realm/Streak/Artifact. Week 6-10 Portals
+ Events. Week 11-13 D5 social-infra. Week 14-17 Launch Event infra.
Week 18+ polish.

### Track B — RN / Native (RN dev)
Week 1-2 stabilize + D4 cleanup. **Week 3-6 NATIVE HEALTH (critical
path, longest single lane).** Week 7-10 Portals UI + Weekly Recap UI.
Week 11-13 D5 UI (activity feed, active-players, share-cards). Week 14-17
Launch Event UI. Week 18+ polish.

### Track C — Content / Community (Founder)
**НЕ блокує dev**, якщо content готується +1-2 тижні випереджаючи
потребу. Parallel tasks:
- **Week 1-4 Foundation content:** 10 flavor, 10 portal locations + imagery,
  3 starter artifacts imagery, BL doc updates (D3/D4).
- **Week 3-7 Discord community seeding:** 6 realm channels, fan-art
  briefs, lore teasers, community-mgr (hire or self).
- **Week 5-10 Voice-over:** Opening 60s UA final, EN draft. Audio
  files in repo by Week 6.
- **Week 8-15 Event content:** Launch Event narrative, boss-mob names,
  mid-beta event weekend-boss theme, monthly realm rotation (Oct=Asgard,
  Nov=Olympus, Dec=Tatgari).
- **Week 15-21 Marketing:** Reddit AMA draft + publish, F2P-manifesto
  final, TikTok/Instagram video (Hero Battle screenshot + «Fitbit as
  weapon» headline).
- **Week 21-27 Launch coordination:** Store submissions, legal review,
  Discord growth push, launch-day ops.

### Критичні паралельні вікна

| Period | BE track | RN track | Content track | Синхронізація |
|--------|----------|----------|----------------|----------------|
| **Week 3-6** | Realm + Streak + Portal Kit infra | **NATIVE HEALTH (critical)** | Voice-over, portal imagery | Daily 15-min standup. Weekly sync on DTO changes. |
| **Week 7-10** | Portal entity + API + Event skeleton | Portals UI + Weekly Recap card | Event narratives | Weekly demo — портал-flow end-to-end. |
| **Week 11-13** | D5 Social-infra Phase 1-3 | D5 UI Phase 1-3 | Launch Event content | **Tight coupling**: feed endpoint + feed UI паралельно, ~3 днів lag. |
| **Week 14-17** | Launch Event infra | Launch Event UI | TestFlight recruit | **Scope-gate Week 17**. |

### Що **не** можна паралелити
- Week 1-2: DTO-fix — backend і RN одночасно через API-contract
  [memory `project_cross_project_rules`: «API-contract як джерело правди»].
- Native health (Week 3-6 RN): backend не може робити нову Health API
  роботу поки не стабілізована client-side integration.
- Launch Event (Week 14-17): event entity — backend, event UI — RN,
  ТІСНА coupling.

---

## 7. Scope-cutdown plan (Launch Event Scope-Gate 15.08.2026)

**Trigger:** якщо на Week 17 end (15.08.2026) статус:
- ≥3 з фіч #7, #8, #11, #17, #18 — **не завершені**;
- OR native health має >2 P0 issues на physical device;
- OR TestFlight feedback з Week 15 показує D3 retention <25%.

**Тоді виконуємо Cutdown Scope (Launch Lite):**

### 7.1. Cutdown scope (Launch Lite)

**Keep (P0 core, 10 items):**
1. Opening 60s (single-tap, no faction-pick) — D3
2. Battle = workout real-time (fix + set-logging + native health)
3. ~~Realm identity~~ — out per D3 — save weeks
4. Weekly Fitness Recap (MVP: text-only push, no shareable card)
5. Starter Artifact Tier 1 (3 items seed)
6. F2P-manifesto in-app
7. Discord community
8. Streak multiplier (baseline only, no named-artifact streak-earn)
9. Onboarding flavor-texts (10)
10. **Public Activity Feed (basic, no counter or share-cards)** — per D5

**Cut if we hit cutdown (5+3 items):**
- #5 Portal Creation Kit → **deferred to 1.0** (user-created portals cost
  moderation + geo-validation effort; save 2-3 weeks).
- #7 10 static portals → **reduce to 3 portals** (Галдхьопіген, Дніпро,
  Grand Canyon only). Virtual-replicas only, no geo-visit in v1.
- #8 Launch Event «День Розколу» → **«Lite»**: cosmetic rare-mob spawn
  +20%, no boss-mob, no named artifact. Save ~2 weeks.
- #9 Monthly Day of Realm → **deferred to post-beta** (re-use Event infra).
- #11 Referral → **deferred to post-beta**.
- #14 Named artifact streak-earn → **deferred**.
- #18 Active-players counter → **deferred** (no SSE/WebSocket infra).
- #19 Event share-cards → **deferred**.
- #20 Co-op damage bonus → **deferred**.

**Effect:** Lite Scope = 10 IN + 3 static portals + Launch Lite event.
~5-6 weeks less work vs full scope. Feasible for 1+1+F в 22 тижнів замість
27.

### 7.2. Alternative launch date: 01.01.2027

Якщо cutdown недостатній — **перенос на 01.01.2027** («Перший оператор
2027-го», New Year Розколу anniversary) дає +9 тижнів. Це підхід з
02:§10.5. Soft-launch 31.10 Lite Event + Grand Opening 01.01.

---

## 8. Risks & Stop-Signals

### 8.1. Ризики (інтеграційні, від 04 + нові)

| # | Ризик | Probability | Impact | Mitigation |
|---|-------|-------------|--------|------------|
| 1 | **Native health не працює на physical device до 01.07** | Medium | Critical | Week 3 rewrite to `kingstinct/react-native-healthkit`. Dedicated freelance-RN-dev spike (3 days) якщо RN-dev блокується. |
| 2 | **Apple App Review reject через HealthKit entitlement** | Medium | High | Explicit privacy policy by Week 18. `NSHealthShareUsageDescription` з specific value clause («track XP from real workouts»). Pre-submit TestFlight 500+ testers показує, що app is legitimate fitness [перевірено [Apple HIG HealthKit](https://developer.apple.com/design/human-interface-guidelines/healthkit) 2026-04-18]. Starting 2026 spring, Medical/Health apps can declare regulatory status in Review Portal [перевірено Apple 2026 updates]. |
| 3 | **Google Play Health Connect Declaration rejection** | Medium | High | Submit Health Connect Declaration Form by Week 20. Clear per-data-type justification [перевірено [Play Console Health Permissions](https://support.google.com/googleplay/android-developer/answer/12991134?hl=en) 2026-04-18]. |
| 4 | **TestFlight D7 retention <30%** | Medium-High | Critical | Weekly TestFlight review from Week 18. If <30% on any of 3 cohorts → trigger cutdown or date-slip decision. |
| 5 | **Android battery drain complaints** | Medium | Medium | Polling interval switched to 30s in-workout (not 10s) per Google guidance: "Maximum 15 min recommended" [перевірено [Android Workouts](https://developer.android.com/health-and-fitness/health-connect/experiences/workouts) 2026-04-18]. Combining foreground service + batching can reduce battery drain 63% [перевірено [React Native Location Battery Study](https://www.wellally.tech/blog/react-native-fix-location-tracking-battery-drain) 2026-04-18]. User-toggleable "High accuracy mode" for elite mode. |
| 6 | **Scope creep from founder during Weeks 8-14** | High | Critical | Weekly scope review, written no-new-features rule after Week 8. **Founder rules: any new idea → park in 1.0 doc.** |
| 7 | **DTO re-drift (new DTO bugs after Week 2 fix)** | Medium | Medium | Automate contract-test (OpenAPI schema compare) as Week 4 deliverable. Or manually cross-review every API PR for both repos. |
| 8 | **Launch Event backend can't handle 1000 concurrent on 31.10** | Medium | Critical | Redis caching for GameSettings in Week 16. Load test 500 concurrent on Week 19 TestFlight. If fails — hard-cap launch event to N users. php-fpm pool-size 20+ [04:§8.6]. |
| 9 | **Legal: landmark imagery rejection (Ангкор-Ват, Теотіуакан)** | Low-Medium | Medium | 3-portal MVP list (Галдхьопіген geoname, Дніпро Канів, Grand Canyon) — usually no issue. Legal review Week 21. |
| 10 | **Discord server DOA (<500 members on launch)** | Medium | High | Community-mgr hire by Week 3. Seeded fan-art briefs weekly. Pre-launch TestFlight referral → Discord conversion. If <500 by Week 17 → Reddit AMA pushed to Week 18 (earlier) for growth boost. |
| 11 | **OAuth API keys not approved** (Google Sign-In prod, Apple Sign In prod) | Low | High | Apply for API keys Week 1. Both typically 1-3d for Google, 1-2w for Apple App Store Connect identifiers. If delayed — delay is max 2 weeks, no blocker. |
| 12 | **Founder burnout (solo PM + content + community)** | High | Critical | **Hire community-mgr Week 3** (paid part-time). Flutter cleanup verified done — so no pull on dev. Delegate content copy-review to freelance. |
| 13 | **Expo SDK 54 → SDK 55 migration during beta** | Low | Medium | **Pin Expo SDK 54** for entire beta. Upgrade post-launch. Expo SDK 54 is production-ready with RN 0.81 + React 19.1 [перевірено [Expo SDK 54 changelog](https://expo.dev/changelog/sdk-54) 2026-04-18]. |
| 14 | **`kingstinct/react-native-healthkit` missing Android Health Connect support** | Certain | High | Library only does iOS. Android still uses `react-native-health-connect` (already in package.json). Verify both libraries coexist in Week 3 spike. |

### 8.2. Stop-Signals — «ми не дійдемо до 31.10»

Clear-criteria, якщо хоч одна triggers → **pause + decision**.

| Gate date | Signal | Action if failed |
|-----------|--------|------------------|
| **01.06.2026** (end Week 4) | Native health sync не синкає real data на physical iPhone 15 OR Pixel 7 | Hire freelance-RN-dev spike (1 week, ~$2-3k). If not fixed by 15.06 → **trigger Cutdown Scope or date-slip**. |
| **01.07.2026** (end Week 9) | >3 P0 bugs outstanding, or native health still mock on one platform | **Drop Android OR iOS for beta (single-platform beta)** OR slip to 01.01.2027. |
| **15.08.2026** (end Week 17, T-11w) | ≥3 of (Portal Kit, Launch Event, D5 social-infra, Referral, Weekly Recap) not code-complete | **Execute Cutdown Scope §7.1.** No new debates. |
| **15.09.2026** (end Week 22, T-6w) | Apple review rejects twice with same issue | **iOS-skip for 31.10 launch**: Android-only beta, iOS post-review. |
| **10.10.2026** (T-3w) | TestFlight wave 2 D7 retention <30% | **Slip launch to 01.01.2027**. Do Lite Event 31.10 as «soft preview». |
| **24.10.2026** (T-1w) | Crash rate >2% on either platform in TestFlight | **Block launch**, fix, aim for 07.11 re-launch window. |
| **31.10.2026** (day of) | Backend error rate >5% OR >3 concurrent-related crashes | Hard-cap concurrent users. Post-event retrospective, plan Grand Opening Q1 2027. |

---

## 9. Content-роботи track (не блокує dev)

Окремий трек, який йде **паралельно** до dev і не знаходиться на
critical path для API/UI. F (founder) veде з part-time freelance help.

### 9.1. Контент-deliverable timeline

| Content item | Week delivered | Source | Dependency |
|--------------|----------------|--------|------------|
| **10 onboarding flavor-texts** | Week 3 | [05:§7] | None |
| **10 portal locations + stock imagery** | Week 3 | [02:§8.2] + legal-safe list | Need legal check on 3 risky ones (Ангкор-Ват, Теотіуакан, Ватнайокутль) by Week 15 |
| **3 starter artifacts imagery + flavor** | Week 4 | [02:§8.3] Tier 1 starter | — |
| **100 flavor-strings for mobs** | Week 3-8 (incremental) | [05:§7 patterns] | ~15 per realm × 6 = 90+ minimum |
| **Voice-over Opening 60s UA + EN** | Week 6 | [05:§6] + founder brief | Voice actor (or founder-voice) |
| **Event narratives × 3** (Launch, Mid-beta, Monthly Day of Realm) | Week 8-13 | [02:§4.1 IN-8,9] + founder | — |
| **F2P-manifesto text (final)** | Week 5 | [02:§7] | Legal review by Week 8 |
| **Weekly Recap copy templates** (3 variants) | Week 8 | [01:§4 P0.3] | — |
| **10-15 artifact flavor + imagery Tier 2** | Week 8-12 | [02:§8.3] | Portal seed |
| **5 named artifact flavor Tier 3** | Week 12 | [05:§3.4] | Named-artifact earn flow done |
| **Reddit AMA draft** | Week 15 | [02:§6.5] | — |
| **Marketing TikTok/Instagram content** | Week 16-19 | [02:§6.2 faction campaign → alt "Пробудження" pre-reg] | — |
| **Discord onboarding templates** | Week 3-7 rolling | [02:§8.5] | — |
| **Mid-beta event content** (weekend-boss theme) | Week 13 | D5 | — |

### 9.2. Legal/licensing checks (separate, founder or freelance lawyer)

| Item | Deadline | Risk | Mitigation |
|------|----------|------|------------|
| **Landmark imagery** (Angkor, Teotihuacan, Vatnajokull) | Week 15 | Medium | Switch to stock/generic mountain imagery if needed |
| **Portal geonames** in-app text | Week 10 | Low | Use public geonames only |
| **HealthKit privacy policy** | Week 18 | Must | Use template from [Apple HIG HealthKit](https://developer.apple.com/design/human-interface-guidelines/healthkit) 2026-04-18 |
| **GDPR / data handling doc** | Week 18 | Must | Template from prior Symfony-based apps |
| **F2P-manifesto as consumer-protection-clear doc** | Week 5 | Low-Medium | Freelance lawyer review (~$500-1000, 1 week) [team — потребує підтвердження] |
| **Trademark check "RPGFit"** | Week 8 | Low | USPTO + EUIPO basic search |

### 9.3. Content STOP-signals

- **Week 8:** if <30 flavor-strings → push to founder, cannot continue
  to mob seed.
- **Week 12:** if legal rejects ≥2 portals → reduce portal count to 6.
- **Week 18:** if voice-over not recorded → Opening 60s скасовується
  до lite text-only version.

---

## 10. Post-beta (1.0+, reference only — not in this roadmap)

Для повноти картини — що робимо після 31.10 (не планую детально):

- **Q1 2027:** Dynamic portals (user-algorithmic spawns), Guild/Clubs
  Lite (Strava-style без PvP), Shiny-varian мобів, Season Pass (cosmetic
  only).
- **Q2 2027:** Real-time Class IV Raid (3-5 player coop), Audio narrative
  Vector voice, AR camera on static portals, Brand partnerships.
- **Q3 2027:** Terra API opt-in (from [product-decisions §D1]), Seasonal
  Arena ranked PvE, Orna-class deep progression UI.

Нічого з цього **не** пoтрапляє в 27-тиждневний beta-roadmap.

---

## 11. Посилання

### Внутрішні (прочитано)

- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/01-market-research.md` (405 рядків)
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/02-beta-scope.md` (894 рядки)
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/04-code-audit.md` (916 рядків)
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/05-lore-to-hook.md` (727 рядків — скан ключових секцій)
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/product-decisions-2026-04-18.md` (authoritative)
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/health-aggregator-comparison.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/portals.md`, `mobs.md`, `onboarding-gifts.md`, `emotional-hooks.md`, `beta-hype.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/ARCHITECTURE.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/BUSINESS_LOGIC.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan1-foundation-auth.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan2a-onboarding-profile-leveling.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan2b-health-sync.md` (critical parts)
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/plans/2026-04-04-plan2c-battle-workout-equipment.md`

### Зовнішні (валідовано через WebSearch 2026-04-18)

**Apple / HealthKit / App Store:**
1. [Apple App Review Guidelines](https://developer.apple.com/app-store/review/guidelines/) — 2026-04-18 — timing generally 24-48h typical, no SLA, fitness apps з HealthKit entitlement — extra scrutiny.
2. [Apple Health & Fitness — Developer](https://developer.apple.com/health-fitness/) — 2026-04-18 — Starting spring 2026, Medical/Health apps can declare regulatory status.
3. [HealthKit HIG](https://developer.apple.com/design/human-interface-guidelines/healthkit) — 2026-04-18 — privacy-policy requirement, purpose-strings.
4. [App Store Review Checklist 2025 — AppInstitute](https://appinstitute.com/app-store-review-checklist/) — 2026-04-18 — "40% of submissions face delays or rejection due to preventable errors".

**Android / Google Play / Health Connect:**
5. [Android Health Permissions — Play Console Help](https://support.google.com/googleplay/android-developer/answer/12991134?hl=en) — 2026-04-18 — Health Connect Declaration Form required; deadline 22.01.2025 for existing apps, new apps мають submit.
6. [Health Connect Publish Guidance — Android Developers](https://developer.android.com/health-and-fitness/health-connect/publish) — 2026-04-18 — per-data-type justification required.
7. [Health Connect Workouts Guide](https://developer.android.com/health-and-fitness/health-connect/experiences/workouts) — 2026-04-18 — **"For active workouts, write data as available OR at maximum interval of 15 minutes."** → 10s polling too aggressive, 30s is fine.
8. [Android Battery Technical Quality Enforcement 2026](https://android-developers.googleblog.com/2026/03/battery-technical-quality-enforcement.html) — 2026-04-18 — new enforcement rules March 2026, wake lock optimization required.

**React Native / Expo:**
9. [react-native-health Expo docs](https://github.com/agencyenterprise/react-native-health/blob/master/docs/Expo.md) — 2026-04-18 — **"Background processing is not currently supported by the Expo config plugin"** — confirmed limitation.
10. [kingstinct/react-native-healthkit](https://kingstinct.com/react-native-healthkit/) — 2026-04-18 — supports `enableBackgroundDelivery()` with immediate/hourly/daily. **Recommended switch from agencyenterprise.**
11. [Expo SDK 54 Changelog](https://expo.dev/changelog/sdk-54) — 2026-04-18 — SDK 54 production-ready, RN 0.81 + React 19.1.
12. [React Native Location Battery Study — wellally.tech](https://www.wellally.tech/blog/react-native-fix-location-tracking-battery-drain) — 2026-04-18 — 63% battery-drain reduction via foreground service + batching + adaptive GPS.

**Indie game dev / solo team velocity:**
13. [Wayline — Solo Dev Roadmap Without Burning Out](https://www.wayline.io/blog/solo-dev-roadmap-building-games-without-burning-out) — 2026-04-18 — scope control paramount for 6-mo targets.
14. [Wayline — Launch First Indie Game Solo Guide](https://www.wayline.io/blog/launch-first-indie-game-solo-dev-guide) — 2026-04-18 — MVP-first, cut aggressively.
15. [Indie Game Roadmap 2026 — Medium HawksandOwls](https://medium.com/@HawksandOwls/the-game-dev-roadmap-no-one-tells-you-about-in-2026-c05f89ac63b9) — 2026-04-18 — avg indie takes 2-3 years; 6mo feasible only with narrow scope.

### Не валідовано у цій сесії (треба re-check)

- Apple App Review SLA для fitness apps з HealthKit — no public SLA; варіює 24h-7d [community anecdotes — треба check recent beta-launches].
- Google Play Health Connect Declaration — review time not documented; community-reports 1-7 days [треба check].
- Expo EAS build time for iOS + Android — typically 5-15 min per build; not blocker.
- `kingstinct/react-native-healthkit` compatibility з Android Health Connect — only iOS, тому Android залишається на `matinzd/react-native-health-connect` (вже в package.json) [треба spike Week 3].

---

## 12. Post-scriptum для фаундера

### 12.1. 3 рішення, які треба ухвалити **цього тижня** (до Week 1 start)

1. **Чи переключаємося на `kingstinct/react-native-healthkit` для iOS?**
   Це економить ~1 тиждень vs custom config-plugin. Trade-off: один
   додатковий `npm install` + спроба на dev build. Рекомендую **так**,
   бо [офіційно підтверджено](https://kingstinct.com/react-native-healthkit/)
   `enableBackgroundDelivery()` support 2026-04-18.

2. **Наймаємо freelance RN-dev для Native Health spike на Week 3-6?**
   Поточний RN-dev не має нативного досвіду (z audit — бібліотеки тільки
   в package.json, 0% інтеграція). 3-4 тижні full-time специаліста з
   HealthKit + Health Connect — приблизно $8-15k
   `[команда — потребує підтвердження]`. Альтернатива — current dev
   вивчить в процесі, але це +2 тижні risk. **Рекомендую: найняти** (за
   умови бюджету) для зменшення critical-path risk.

3. **Hire community-mgr у Week 3 (paid part-time)?** За 02 §8.5 мінімум
   2-3к Discord seeds на launch. Founder solo не встигне робити
   dev-coord + content + community. Part-time CM ~$500-1000/міс x 6 міс
   = $3-6k `[команда — потребує підтвердження]`. **Рекомендую: так.**

### 12.2. Коли синхронізувати 02-beta-scope з D3/D4/D5

Передати `02-beta-scope.md` на **перезапуск BA-агента 02** з цим
(03-roadmap) як вхідним + product-decisions-2026-04-18. Іnakше `02`
лишиться stale (містить «6-realm faction identity» як P0 #3, а по D3 —
cut). **Рекомендований порядок:** 03 → re-run 02 (to sync with D3/D4/D5)
→ skeleton plan for each Week 1-4 as superpowers:plans → execute.

### 12.3. Пам'ятка з правил фаундера (MEMORY.md)

- **No approval for project commands** — ✅ всі dev-кроки в roadmap можна
  робити без additional sign-off.
- **Document business logic** — Week 1 F work включає BL update for D3/D4.
- **Flutter cleanup pending** — 04 підтверджує Flutter як RN вже done у
  `rpgfit-app/`; єдиний artifact — `ActivityType.flutter_enum` column
  (04:§7 #2) — можна rename після beta.

---

## Changelog

- **2026-04-18** — v1. Roadmap 27 тижнів (T-27 → T) побудовано на базі
  04-code-audit gap-таблиці + 02-beta-scope IN-15, скорегованого per D3/D4/D5
  з product-decisions-2026-04-18. 15 зовнішніх URL валідовано WebSearch.
  Команда-припущення: 1 BE + 1 RN + founder. Stop-signals на 7 gate-dates.
  Cutdown scope (Launch Lite) передбачено. Content-track паралельний, не
  блокує critical path.
