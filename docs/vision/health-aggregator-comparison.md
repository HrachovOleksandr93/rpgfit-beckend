# Health Aggregator Investigation: Terra vs Vital vs Native

> **Статус:** ✅ APPROVED by founder 2026-04-18 — **Native for beta** (Фаза 0).
> Terra і Vital — не beta. Terra розглядається як post-beta opt-in (Фаза 1).
> Див. `docs/vision/product-decisions-2026-04-18.md §D1`.
> **Дата:** 2026-04-18.
> **Автор:** Claude (research), ревʼю — фаундер.
> **Пов'язані документи:**
> - `memory/project_health_sync_strategy.md` (поточна стратегія: native)
> - `docs/superpowers/plans/2026-04-04-plan2b-health-sync.md` (план реалізації)
> - `rpgfit-beckend/docs/BUSINESS_LOGIC.md §3` (XP з health data)

---

## 0. TL;DR — що обирати?

1. **Для RPGFit beta — залишитися на native (HealthKit + Health Connect).**
   Real-time heart rate нам потрібен тільки всередині активного battle-workout
   (кілька хвилин), а саме для цього native API безкоштовне й найнизькошарне.
   Поріг переходу на Terra — коли треба **офіційно-одобрені wearables бренди**
   (Garmin, WHOOP, Polar Vantage) або **backend-side ingestion** без
   присутності мобільного клієнта.
2. **Vital (Junction) — не наш кейс.** Їх сильна сторона — labs + clinical. Ми
   — fitness RPG, labs не потрібні.
3. **Terra — запасний план №1.** Переключитися можна фіча-за-фічою: спочатку
   додати Garmin/WHOOP як opt-in для power-users, native залишити як дефолт.
4. **Вартість Terra для нашої шкали (до ~2500 MAU):** ~$399/міс. (annual) +
   можливо $0.005/credit за overage — але overage запускається тільки якщо
   юзер активно коннектиться до кількох wearables і ми рахуємо webhook
   events. У beta-скоупі це ~$0.
5. **Найсильніший аргумент за агрегатор у майбутньому:** Garmin/WHOOP/Polar
   коннекти без написання окремих OAuth-флоу — це місяці економії, якщо ми
   вирішимо підтримати «pro athlete» сегмент.

---

## 1. Контекст: що нам треба від health-шару?

З `BUSINESS_LOGIC.md §3` + `project_health_sync_strategy`:

| Потреба | Частота | Критичність |
|---|---|---|
| Steps daily summary | 1×/день (idle sync) | P0 — XP |
| Active energy | 1×/день | P0 — XP |
| Workout sessions | При старті/завершенні | P0 — battle trigger |
| Heart rate **під час battle** | real-time, 10-30s latency max | **P0** — mob damage |
| Sleep | 1×/день | P1 — XP |
| Distance / flights | 1×/день | P1 — XP |
| HRV, VO2max, BP | опційно | P2 — future artifacts |

**Ключове:** у нас одна real-time потреба — heart rate під час активного
battle-workout. Все інше можна синхронити batch-ом.

---

## 2. Terra API (`tryterra.co`) — аналіз

### 2.1 Плюси для RPGFit

- **Real-time streaming heart rate + GPS** — це єдиний аггрегатор, який явно
  цим хизується. Для нашого battle-flow це ідеально.
- **First-party React Native SDK** — працює з Expo через custom dev client
  (Expo Go ні). Це означає: **ми все одно робимо dev-build** (ми й так мусимо
  для HealthKit), але замість двох окремих нативних модулів
  (`react-native-health` + `react-native-health-connect`) — один Terra SDK.
- **150+ wearables з одного SDK** — коли з'явиться pro-athlete сегмент і люди
  захочуть Garmin, WHOOP, Polar, Oura — не треба писати OAuth-коннектори.
- **Predictable pricing:** $399/міс (annual) + 100K credits free. Active auth
  = 200 credits/міс на юзера → ~500 юзерів з коннектом безкоштовно. 95%
  клієнтів не платять за events (перші 400 events/auth безкоштовні).
- **HIPAA, GDPR, SOC 2 Type II** — якщо колись підемо на європейський/US
  ринок медичний, вже compliant.
- **Webhook push на наш backend** — значить Android може перестати робити
  polling 10с/5хв; замість цього сервер отримує push і оновлює battle state
  через WebSocket/SSE.

### 2.2 Мінуси для RPGFit

- **Apple Health / Health Connect все одно вимагають mobile SDK.** Тобто
  Terra не економить нам нативну інтеграцію — вона просто її **обгортає**.
  Якщо користувач не коннектить Garmin/Fitbit/тд, Terra = наклад зверху
  native API без вигоди.
- **$399/міс на старті beta з ~100 юзерами** — це $4/юзер/міс. Native
  безкоштовне.
- **Додаткова dependency.** Якщо Terra падає — наш battle-flow падає. Native
  API на пристрої — single point of failure = сам пристрій.
- **Real-time streaming latency не задокументовано публічно.** «Real-time»
  маркетинг — але чи це 2с, 10с, 30с? Треба перевіряти емпірично. Native
  HealthKit observer query — <1с від запису.
- **Terra credits конвертують з events** — якщо power-user має підключено
  5 девайсів і синкається часто, перевищить 400 free events/auth. Модель
  передбачувана, але не лінійна.
- **Vendor lock-in на DTO-форматі Terra.** Якщо переходимо назад на native
  — переписувати mapping-шар.

### 2.3 Критичний тест — чи справжній у Terra real-time?

Перед переключенням зробити spike:
1. Підʼєднати Apple Watch через Terra SDK на dev-build.
2. Запустити Workout на годиннику, моніторити webhook delay.
3. Мета — HR на бекенді не пізніше ніж 10с від удару серця.

Якщо >30с — для battle-механіки Terra не годиться, і native залишається
єдиним шляхом для HR.

---

## 3. Vital / Junction (`tryvital.com`) — аналіз

### 3.1 Плюси

- **500+ девайсів** — більше ніж Terra (але для fitness-audience зайве).
- **Lab testing API** — унікальний фіт, якщо колись підемо у health-scoring
  через blood panels (бустимо stats по реальних біомаркерах). Не beta.
- **React Native + Flutter SDK.**
- **Простіше цінове зниження з масштабом:** Grow tier <$0.50/юзер/міс.

### 3.2 Мінуси

- **Real-time streaming явно позначено як «limited»** — не підходить для
  нашого battle-flow (HR під час тренування).
- **Primary focus — clinical / healthcare**, а не fitness/gaming. Це видно
  в документації і tier-and: «Vital iOS app», уточнені SLA — для мед-кейсів.
- **Launch tier: $0.50/user/mo, minimum $300/міс** — дорожче за Terra на
  нашій шкалі, бо $300 мінімум при ~100-600 юзерів.
- Rebrand (`tryvital.io → Junction`) вказує на **стратегічний shift від
  wearables до lab workflows** — wearables можуть стати другорядним продуктом.

### 3.3 Вердикт

Vital — це рішення для telemedicine, не для fitness RPG. Якщо ми не
плануємо labs, цей варіант пропускаємо.

---

## 4. Native (current plan) — baseline

### 4.1 Плюси

- **Безкоштовно. Назавжди.**
- **iOS HealthKit observer** — справжній real-time push (<1с).
- **Повний контроль над маппінгом у наш DTO і XP-логіку.**
- **Відсутність vendor-залежності.** Якщо Apple або Google ламають API —
  це проблема всіх, ми в rowing boat with everyone else.
- Вже є план (`plan2b-health-sync`) + `healthApi.ts` stub.

### 4.2 Мінуси

- **Android polling = батарея.** 10с під час workout — foreground service
  з notification іконкою. Частина користувачів її закриє.
- **Кожен ще-один-source = окрема інтеграція.** Якщо завтра користувач каже
  «я хочу коннектити Garmin напряму» — писати власний OAuth (Garmin Connect
  Cloud API вимагає enterprise partnership).
- **Health Connect background delivery ще сироват** (Android 14+ vs 15).
  Треба тестувати на дефолтних девайсах.
- **Ми пишемо і підтримуємо дві кодбази health-інтеграції** (iOS + Android).

---

## 5. Порівняльна таблиця

| Критерій | Terra | Vital | Native |
|---|---|---|---|
| **Вартість (100 MAU, monthly)** | $399 (annual plan) | $300 (min) | $0 |
| **Вартість (1000 MAU)** | $399 + можливі credits | ~$500 | $0 |
| **Real-time HR під час workout** | Заявлено, треба тестувати | Limited | Так (iOS push, Android 10s poll) |
| **HealthKit підтримка** | Через SDK | Через SDK | Native |
| **Health Connect підтримка** | Через SDK | Через SDK | Native |
| **Garmin / WHOOP / Polar** | З коробки (150+) | З коробки (500+) | Окрема інтеграція (місяці) |
| **React Native / Expo** | Так, dev build | Так, dev build | Так, dev build |
| **Webhook push до backend** | Так | Так | Ні (клієнт push-ить) |
| **Lab biomarkers** | Ні | Так | Ні |
| **HIPAA / SOC 2** | Так | Так | Не застосовно (self-managed) |
| **Vendor lock-in** | Середній (DTO + webhooks) | Середній | Нульовий |
| **Час на інтеграцію з нуля** | ~1 тиждень SDK setup | ~1 тиждень | ~3-4 тижні (обидві платформи) |

---

## 6. Рекомендація — фазована стратегія

### Фаза 0 (зараз, beta) → Native
- Причина: real-time через HealthKit observer безкоштовне і дає <1с latency.
  Android polling вже спроектовано в plan2b. Затрати на Terra (~$400-500/міс)
  нічим не окупаються, бо у beta ми саме тестуємо, чи працює механіка.
- **Дія:** виконуємо `plan2b-health-sync` як є. Не переписуємо.

### Фаза 1 (post-beta, якщо є power-user запит) → Terra як opt-in
- Тригер: 5+ feedback на тему «хочу коннектити Garmin/WHOOP».
- Підхід: Terra додається **поруч** з native. Native залишається дефолтом
  (безкоштовний path для iPhone+Apple Watch та Android+Wear OS).
- В UI: «Advanced → Connect more devices» → Terra Link widget.
- Залежність: перед цим зробити A/B emprirical test Terra real-time latency
  (див. §2.3).

### Фаза 2 (growth, 5000+ MAU) → перегляд
- Якщо Terra events/credits cost виходить нелінійно дорогим → власний Garmin
  partnership або переглядати архітектуру.
- Якщо Terra надає надійний real-time для всіх wearables → можливо
  відмовитися від власного Android polling і робити все через Terra webhooks.

---

## 7. Відкриті питання / ризики

- **Terra real-time latency невідомий — треба spike з Apple Watch.** Без цього
  рішення Фази 1 підвішене.
- **Terra credits дорогий кейс** — якщо фаундер-комунікація активно пушить
  multi-device sync (наприклад, «синкай Garmin + Apple Health + Oura»), events
  зростуть експоненційно. Треба модель costs/user з прогнозами.
- **Native Android 10s polling УЖЕ може виявитися проблемним** — ми ще не
  виміряли battery drain на реальних девайсах. Якщо >3% batt/тренування —
  це churn-ризик. У такому випадку Terra може перевести це на webhook push.
- **HealthKit background delivery** на iOS 17+ має кейси з неспрацьовуванням
  (Reddit r/iOSProgramming); перед beta тестуємо окремо.
- **Policy risk:** Apple і Google змінюють правила доступу до health data
  (Apple 17→18 обмежив деякі категорії). Агрегатор тут _може_ страхувати
  нас від раптових breaking changes, _якщо_ він сам встигає адаптуватися.

---

## 8. Вплив на beta-скоуп (посилання на `02-beta-scope.md`)

- **Без змін для P0.** Battle flow, health sync, XP з health — native.
- **Розглянути для P1 (post-beta):** «Connect external wearable» як
  premium-фіча через Terra. Маркетингова приманка для power-users.
- **P2 / Later:** якщо Vital piviotує повністю в labs і ми колись будемо
  робити blood-based stat-boost (RPG → био-лут) — оцінити Vital тоді.

---

## 9. Джерела

- [Terra Docs — Pricing](https://docs.tryterra.co/health-and-fitness-api/pricing)
- [Terra Docs — Setting up data sources (HealthKit/Connect mobile SDK required)](https://docs.tryterra.co/health-and-fitness-api/integration-setup/setting-up-data-sources)
- [Terra Docs — React Native SDK](https://docs.tryterra.co/reference/health-and-fitness-api/sdk-references/react-native)
- [Terra — Apple HealthKit integration page](https://tryterra.co/integrations/apple-health)
- [Vital (Junction) — Pricing](https://www.tryvital.com/pricing)
- [Vital — Apple Health API docs](https://tryvital.io/wearables-api/apple-health-kit)
- [Vital Health SDK (Junction docs)](https://docs.tryvital.io/wearables/sdks/vital-health)
- [NextBuild — Terra vs Vital comparison](https://nextbuild.co/blog/terra-vs-vital-unified-wearable-apis)
- [HumanITcare — 3 best APIs for wearables 2025](https://humanitcare.com/en/the-3-best-apis-for-wearables-and-medical-devices-in-2025/)
