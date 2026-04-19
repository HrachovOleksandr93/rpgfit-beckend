# Product Decisions — 2026-04-18

> **Статус:** APPROVED by founder. Авторитетний для всіх наступних BA-агентів,
> планів і дизайну.
> **Джерело:** feedback фаундера після читання `BA/outputs/01-market-research.md`
> і `docs/vision/health-aggregator-comparison.md`.

---

## D1. Health aggregator — native для beta ✅

- **Рішення:** для beta залишаємось на native integration (HealthKit + Health
  Connect) згідно з `memory/project_health_sync_strategy.md`.
- **Не беремо:** Terra API і Vital API.
- **Причина:** native безкоштовне, дає <1с latency для iOS HealthKit observer,
  Android polling вже спроектований у `plan2b-health-sync`. Terra як opt-in
  post-beta — відкладено.
- **Імплікація для 04/03:** аудит і roadmap health-шару орієнтуються на
  native. Фіксити DTO-баги (UPPERCASE vs lowercase, snake_case vs camelCase,
  `external_uuid` vs `externalUuid`), будувати `react-native-health` config
  plugin + Android foreground service, як у плані. Terra — НЕ beta.

## D2. Ingress-style portals — YES ✅

- **Рішення:** портали з Ingress як механіка **беремо**. Динамічні тріщини +
  статичні лендмарки, як описано в `docs/vision/portals.md`.
- **Імплікація:** `Portal` як новий bounded context + `PortalCreationKit`
  ItemType. Спочатку статичні MVP (10-20 локацій), динамічні відкладаємо.

## D3. NO factions, NO realm-lock identity ❌

- **Рішення:** **немає фракційного розколу** (типу Enlightened/Resistance
  в Ingress). Немає «6-realm faction identity» з обов'язковим вибором
  лояльності й +2% bonus проти «чужих».
- **Що лишається:** realm як **тематичний тег порталу і моба** (Олімп, Асгард,
  Татгарі/Ксай, Дуат, Інтіча, Юпакуру — світобудова, лор, арт-стилістика).
  Портал «з реалму Олімп» спавнить Олімп-мобів, дропає Олімп-артефакти.
- **Що не робимо:** юзер НЕ вибирає «свій реалм», НЕ отримує damage-бонусів
  за «свій реалм», нема PvP realm-vs-realm таборів.
- **Чому:** спрощує онбординг, не розриває ком'юніті на табори, краще
  відповідає історії «всі людство проти тріщини» (см. `Теорія_Світу_v1.1`
  — вороги спільні, не внутрішньовидові).
- **Імплікація для 02/03:** **викреслити P0-фічу «6-realm faction identity»**
  з beta-scope (це була рекомендація 01 і P0 у 02). Визволений enum-слот
  в онбордингу — не заповнювати нічим новим, обрізати крок.

## D4. Races — cut, everyone is Human ❌

- **Рішення:** **прибираємо 5 рас** (Human, Orc, Dwarf, DarkElf, LightElf)
  і 5 race-passive skills (`versatile-nature`, `blood-of-the-horde`,
  `mountain-born`, `shadow-instinct`, `sylvan-grace`).
- **Що лишається:** персонаж — людина. Character appearance = аватар/стать
  (якщо потрібна диференціація — лишити просто як скіни, без механічного
  ефекту).
- **Чому:**
  1. Simpler onboarding (мінус один вибір, мінус один скрін).
  2. Fits story — у лорі `Теорія_Світу_v1.1` Щілина ВЕРА прийшла в людський
     світ; ельфи/орки — це фантазія, не всесвіт RPGFit.
  3. Race passives були різними (+4/+2/+1 на різні стати) — це створювало
     balance-роботу і мета-domination однієї раси. Видаляємо проблему.
- **Імплікація:**
  - Backend: `CharacterRace` enum deprecate (крок 1: замінити всіх у БД на
    `human`; крок 2: видалити колонку `character_race` міграцією; крок 3:
    зняти `race` passive skills з `SeedSkillsCommand`).
  - Skills: 5 race-passive скілів з `skills-design.md §1` видалити. 2
    universal actives (`second-wind`, `battle-fury`) — лишити. Profession-
    skills — без змін.
  - RN client: видалити race-pick крок у `app/(onboarding)/index.tsx`,
    прибрати `characterRace` з `auth/types/*`, з profile UI, з тестів.
  - Plans: `plan1-foundation-auth.md` і `plan2a-onboarding-profile-leveling.md`
    оновити — race-related steps скасовано.

## D5. Social hooks + global events — elevated to P0 🔥

- **Рішення:** **соціальна компонента важливіша, ніж вважалося раніше**.
  Глобальні івенти (типу «День Розколу 31.10», coordinated raids, realm-wide
  worldboss) — це **головний retention driver**, не nice-to-have.
- **Що це значить для beta:**
  - **МІНІМУМ 1 live global event під час beta-window** (не тільки launch
    31.10 — ще один-два mid-beta боси/аномалії).
  - **Візуалізація «інші зараз граються»:** лічильник активних гравців на
    порталі, recent kills-feed, «ти #23 хто це зробив сьогодні».
  - **Cross-player participation** — якщо мобів порталу бʼють одночасно
    кілька людей, всі бачать це й отримують co-op bonus (малий, без PvP
    toxicity).
  - **Share-first UX** — Weekly Recap картка вже є в скоупі, але додати
    **Event-level share cards** — «Я пройшов Галдхьопіген», «Я дійшов до
    40-го рівня у тижні Дня Розколу».
- **Чого НЕ робимо:** chat, гільдії з chat, direct messages, friend system з
  вимогою один одного додавати. Це дорогі системи з модерацією. Соціальне
  у beta — через публічні feeds і share-cards, не direct-to-direct.
- **Імплікація для 02/03:**
  - `02-beta-scope.md` уже має «Launch event День Розколу» як P0 №1. Залишити,
    але додати **supporting infra**: feed-of-activity, active-players counter,
    event-participation badge, event-specific weekly recap.
  - Додати другий mid-beta event у скоуп (наприклад, weekend-boss через 3-4
    тижні після launch). Тригер для reactivation і «прийди ще раз».
- **Referенс з 01:** Ingress Anomaly pattern (перевірено 10+ років); Strava
  Monthly Challenge (Nike/Zwift branding дає +8-12% participation post-event).

## D6. Підсумок змін до `02-beta-scope.md` (для агента 03)

| Item | Було | Стало | Причина |
|------|------|-------|---------|
| 6-realm faction identity | P0 (rec #2 з 01) | **Cut** | D3 — нема фракцій |
| Race pick в онбординзі | P0 (у planах) | **Cut** | D4 — всі люди |
| Race passive skills (5) | In seed | **Cut** | D4 |
| Launch event 31.10 | P0 #1 | **P0 #1 + розширений infra** | D5 — активний лічильник, feed |
| Mid-beta event | Не було | **Новий P0** | D5 — retention між лаунч і нормальним циклом |
| Activity feed (public) | Не було | **Новий P1** | D5 — replaces faction identity як social glue |
| Event share-cards | Було як Weekly Recap | **Розширити на події** | D5 |
| Portal kit / realm-themed мобі | P0 | **P0, без змін** | D2 — механіка залишається |

## D7. Що НЕ змінюється

- XP-ставки, level curves, battle-damage formula — залишаються.
- Professions + їх tier-skills — залишаються (не race-залежні).
- Health data types (15) — залишаються.
- 6 реалмів як **лор і тематика** — залишаються (просто не як gameplay identity).
- Всі `docs/superpowers/plans/*.md` — valid, крім race-related кроків.

---

## Наступні дії

1. ~~Vision doc (цей файл) — written.~~
2. Пам'ять — оновити `MEMORY.md` з D1-D5.
3. BA agent 03 roadmap — запускається з цим файлом як авторитетним вхідним.
4. BA agent 02 — **перезапуск** після 03, щоб beta-scope синхронізувався з
   D3/D4/D5. Або робимо це manual edit `02-beta-scope.md` — рішення за
   фаундером.
5. Backend tickets для D4 — деприкейт race в два релізи (колонка → enum →
   seed cleanup), щоб не зламати існуючі тести.
6. RN cleanup для D4 — видалення race UI, типів, enum, тестів.

---

## Посилання

- `BA/outputs/01-market-research.md` §4 P0.2 — original realm-faction recommendation (reversed by D3)
- `BA/outputs/02-beta-scope.md` §2 (Opening 60s), §3 (6-realm identity) — cut per D3
- `docs/vision/portals.md` — portals mechanic, unchanged (D2)
- `rpgfit-beckend/src/Domain/User/Enum/CharacterRace.php` — to be deprecated (D4)
- `rpgfit-beckend/data/skills-design.md §1` — 5 race passives to be removed (D4)
