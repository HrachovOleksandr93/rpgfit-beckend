# Mental Stats from Mindfulness Health Data — Vision Draft

> **Статус:** DRAFT. Нова ідея фаундера від 2026-04-18. Потрібен BA-research
> перед прийняттям рішення. НЕ імплементувати без подальшого sign-off.
> **Власник ідеї:** фаундер.
> **Research task:** див. нижче §5 — запит до BA-агентів.

---

## 1. Ідея коротко

В HealthKit / Health Connect є дані **ментального здоров'я / mindfulness**:
- Sleep (stages: REM, Deep, Light, Awake)
- Mindful sessions (minutes of meditation)
- Breathing / respiratory rate
- Handwashing (Apple Watch)
- HRV (Heart Rate Variability)
- State of mind / mood logging (iOS 17+)

**Пропозиція:** прив'язати їх до **ментальних характеристик персонажа** в
RPGFit, аналогічно тому, як зараз STR / DEX / CON отримуються з фізичної
активності.

## 2. Потенційний gameplay-вплив

### 2.1 Нові статистики (чорновик)

| Stat | Джерело health data | Ефект у грі (чорновик) |
|------|---------------------|------------------------|
| **WIS** (wisdom) | Mindful minutes + sleep quality | Recovery speed, stamina-регенерація між battles |
| **INT** (intellect) | Sleep REM + breath-rate stability | Час cooldown скіллів, crit-chance з `focused-mind` |
| **WIL** (willpower) | Consecutive mindfulness days, HRV | Resist debuffs від мобів (поizon, fear) |
| **FOC** (focus) | Handwashing streak, hydration | Accuracy / damage variance reduction |

### 2.2 Як впливає на механіки

- **Recovery:** якщо недоспав (<5h sleep) → stamina-регенерація −30%,
  «втомлений» debuff на 24h. Виспаний (7h+, REM >= 90min) → +15% recovery.
- **Equipment requirements:** Tier III artifacts починають потребувати
  **мінімум WIS/WIL level**, не тільки фізичний level. Наприклад, «Трішула
  Вішну» = потребує WIS ≥ 15 — не можна просто задоволнити гантелями,
  треба медитувати.
- **Skill unlocks:** деякі passive skills розблоковуються тільки при певних
  mindfulness-streak (наприклад, `clear-mind` passive +2 INT потребує 14
  днів підряд з meditation session ≥5min).
- **Mob resistances:** деякі Rift-моби (ментальні босси — Асгард «Бога
  Шепоту», Дуат «Страж Снів») мають mental-damage resistance — без WIS/INT
  їх майже не пошкодити.

### 2.3 Event hooks (див. D5 social events)

- **«Тиждень медитації»** — event де всі гравці які набрали 60+ min meditation
  за тиждень, отримують cosmetic-бейдж «Просвітлений».
- **Sleep-challenge** — 7 днів ≥7h = відкривається rare Tier III artifact
  flavor-chain.

## 3. Чому це цікаво (upside)

- **Розширює цикл гри за межі «фізкультури»** — не всі юзери зможуть/хочуть
  тренуватися щодня, але майже всі спатимуть і можуть медитувати.
- **Health-halo ефект** — асоціює RPGFit не тільки з fitness, а з holistic
  wellness. Розширює TAM (target audience) на Calm/Headspace-users.
- **Глибший RPG-смисл** — справжній D&D характер має Mental stats. Додаємо
  WIS/INT/WIL і закриваємо «тільки м'язи» асоціацію.
- **Retention-драйвер для «пасивних» днів** — навіть якщо юзер не тренувався,
  його персонаж може рости через сон і дихальні практики. Зменшує churn у
  rest-day.
- **Etнічно-позитивний hook** — заохочує здорові звички (сон, медитація) за
  межами просто сили. Потрапляє в «5» — «я здоровий я» з emotional-hooks.

## 4. Ризики (downside)

- **Ускладнює онбординг.** Вже є STR/DEX/CON + XP + realm + portal + battle.
  Ще 3-4 mental stats — перевантаження. Мітигація: показувати їх
  **поступово** (unlock на 3-4 level).
- **HealthKit/Health Connect coverage нерівне.**
  - iOS: Mindful sessions є (від iOS 10), state-of-mind новий (iOS 17),
    не всі юзери оновлені.
  - Android Health Connect: mindfulness підтримка **обмежена** — data type
    `Meditation` існує, але мало apps пишуть туди (mostly Samsung Health,
    Google Fit). Треба перевірити.
  - Watch-only data (handwashing, HRV high-fidelity) — тільки Apple Watch
    юзери, Android Wear поступається.
- **«Gamification медитації» може сприйматися цинічно.** Calm / Headspace
  аудиторія чутлива до «achievements за духовну практику». Мітигація:
  опціонально вимкнути цю вітку, називати її не «mental stats» а «inner
  strength» / «clarity» (менше ML-babble).
- **Складніша balance-робота.** Ще 3-4 стати у формулах battle/damage/
  cooldown. Збільшує QA-навантаження.
- **Privacy concerns.** Sleep / HRV — sensitive. Мусимо бути явними: що
  зберігаємо, що ні, як агрегуємо. Бекенд вже GDPR-friendly per ARCHITECTURE,
  але mental-health дані — extra чутлива категорія (HIPAA-like навіть у
  non-US).

## 5. Research запит до BA-агентів

### Питання до агента 01 (market-research)

1. **Чи існують фітнес-аплікації що вже роблять це?** Calm+fitness hybrids,
   WHOOP+mental, Oura+mental. Що вони пропонують, яка retention.
2. **Які apps пишуть в HealthKit `Mindful` i Health Connect `Meditation`?**
   Як розподілена категорія (Apple Mindfulness, Headspace, Calm, Insight
   Timer, Balance). Це визначає coverage.
3. **Яка платіжна готовність** за «mental health gamification»? Чи це
   free-tier функція чи premium-hook.
4. **Чи є прецеденти критики?** Наприклад, негатив у пресі на «перетворення
   духовності на gameplay».

### Питання до агента 02 (beta-scope) або окремого scoping-agent

1. **Scope це беремо в beta чи post-beta?** MVP має бути малий — але
   пропозиція атрактивна для hype post-launch.
2. **Яке мінімальне вхідне МVP?** Тільки sleep? Тільки mindfulness?
   Плюс debuff/buff як перший крок?
3. **Чи відкладаємо state-of-mind і handwashing як T2 фічу?**
4. **Чи конфліктує з D4 (все люди) чи D5 (social events)?** Не напряму —
   mental stats stack з human-only архетипом.

### Питання до агента 04 (code-audit)

1. Що в поточному `BUSINESS_LOGIC.md §3` і `HealthDataType` enum треба
   додати для mindfulness?
2. Чи існуючий `HealthSyncService` (native per D1) готовий читати
   `HKCategoryTypeIdentifierMindfulSession` і `HealthConnectRecord.Meditation`?
3. Чи потрібні нові domain entities: `MentalStat`, `RecoveryState`,
   `SleepDebuff`?

### Питання до агента 03 (roadmap)

1. Якщо беремо — куди вписати (Now / Next / Later)?
2. Чи змістить інші пріоритети (напр. social events D5 vs mental stats)?
3. Який delta-час на балансування 4 нових стат у battle formulae?

## 6. Відкриті technical-питання

- Як зберігати sleep-quality аргрегат? Daily record чи per-session?
- Mindful session = 1 record з duration, чи ми розбиваємо на streaks?
- Як обробляти Android users без Health Connect `Meditation` data? Fallback
  на manual entry? Чи просто не показувати stats без data-source?
- Чи дозволяти **manual entry** для mental-stats (як у D&D: я помедитував
  10 хв — «trust me bro»)? Ризик cheating.

## 7. Наступні кроки

1. **Запустити BA-research** (agent 01 + додатковий scoping-agent) з цим
   документом як інпут.
2. **Дочекатися звіту** — приблизно 30-45 хв роботи.
3. **Фаундер читає** звіт і приймає рішення: IN-scope для beta / post-beta
   experiment / відкладаємо.
4. Якщо IN — оновлюємо `02-beta-scope.md` і `03-roadmap.md`.

## 8. Посилання

- [Apple HealthKit — Mindful Session](https://developer.apple.com/documentation/healthkit/hkcategorytypeidentifier/mindfulsession)
- [Apple HealthKit — State of Mind (iOS 17+)](https://developer.apple.com/documentation/healthkit/hkstateofmind)
- [Google Health Connect — Data types](https://developer.android.com/health-and-fitness/guides/health-connect/plan/data-types)
- `docs/vision/product-decisions-2026-04-18.md` — current beta decisions
- `rpgfit-beckend/docs/BUSINESS_LOGIC.md §3` — XP from health data
- `rpgfit-beckend/src/Domain/Health/Enum/HealthDataType.php` — existing 15 types
