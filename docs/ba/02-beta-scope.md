# 02 — Beta Scope (Hype-Driven MVP)

> **Автор:** BA-агент 02. **Дата:** 2026-04-18. **Статус:** v1.
>
> **Синтез не дослідження.** Цей звіт використовує 01 (ринок, 22 валідованих
> URL) + 05 (лор, 22 URL) як bedrock. Свої нові посилання обмежені 5-7
> playbook-джерелами для beta-запусків (Product Hunt, Steam EA, r/gamedev,
> MobileDevMemo). Решта — синтез.
>
> **Маркери:**
> - **[джерело 01:Х.Y]** — пункт підтверджений звітом 01, розділом X.Y.
> - **[джерело 05:X]** — те саме для 05.
> - **[vision:file]** — з `docs/vision/*`.
> - **[BL §X]** — з `rpgfit-beckend/docs/BUSINESS_LOGIC.md`.
> - **[гіпотеза]** — моя інтерпретація, потребує user-test.

---

## 1. TL;DR (5 буллетів)

1. **USP:** RPGFit — єдина location-based RPG, у якій **твоє реальне
   тренування = збройна сила проти пантеону**, без pay-to-win і без
   штучних енергій. Strava-точність fitness + Ingress-міфологія фракцій +
   Zombies Run!-наратив, у одній петлі [джерело 01:3.4 gap-аналіз, 05:8.3].
2. **Hero feature для beta = «Battle = твоє тренування в реальному
   часі»**, обгорнуте у Vector-інтерфейс [BL §9, 05:§6]. Це те, чого немає
   ні в одного конкурента в такому поєднанні: Strava пише activity без
   бойового шару, Pokémon GO / Orna мають бій без fitness-integrity,
   Zombies Run! — аудіо без RPG-stats.
3. **Core beta loop у 7 днів** будується навколо формули «кожен день —
   1 мілстоун наративу + 1 фізичний подвиг + 1 соціальний triger». День 1
   = faction-pick + Starter Artifact. День 7 = перший статичний портал +
   Weekly Recap share-card.
4. **IN у beta:** opening 60s, 6 realm-faction identity, Battle-як-workout,
   Weekly Fitness Recap, 10-15 статичних порталів, Portal Creation Kit,
   Monthly Day of Realm, Discord community, публічний F2P-manifesto.
   **OUT у beta:** dynamic portals на карті, Raid Class IV, Shiny-варіанти,
   Guild PvP/Arena, Audio-narrative, Season Pass, time-travel артефакти,
   ARG-reveal «Кіра Левченко».
5. **Launch event «День Розколу 31.10» = IN** як 48-годинний лонч-маркер
   (Ingress Anomaly pattern). Але **не як реліз-дата**, якщо scope не
   встигає: soft-launch із cutdown-scope в жовтні + «Grand Opening» на
   наступний квартал. Провал launch-event через недороблений raid —
   гірше, ніж перенос.

---

## 2. Контекст

### Прочитані файли (внутрішні)

- `BA/agents/02-beta-scope.md` — інструкція агента.
- `BA/outputs/01-market-research.md` — 405 рядків, 22 URL валідовано, 8
  продуктів, 15 gap-позицій.
- `BA/outputs/05-lore-to-hook.md` — 727 рядків, 22 URL, 14 архетип-мапінгів,
  Opening 60s, 10 flavor-текстів, 6 hype-наративів.
- `docs/vision/emotional-hooks.md` — 7 гачків + 6 червоних ліній.
- `docs/vision/beta-hype.md` — гіпотези фаундера: 10k D7 50%+, Day of
  Розкол 31.10, референсна 1000 на Галдхьопігені, Portal Creation Kit
  referral.
- `docs/vision/portals.md` — dynamic / static, MVP 10-20 landmarks.
- `docs/vision/mobs.md` — realm-based перероблення 2000 мобів.
- `docs/vision/onboarding-gifts.md` — Portal Creation Kit × 1 + XP boost
  24h + Starter Tier 1.
- `docs/superpowers/specs/2026-04-04-flow-summary.md` — core loop.
- `rpgfit-beckend/docs/BUSINESS_LOGIC.md` — 12 секцій, що вже реалізовано
  в бекенді (особливо §3, 4, 7, 9, 10).

### Нові зовнішні джерела (5-7, beta-playbook-focus)

Див. §11. Це P0 для самого формату beta (не для product-знахідок).

### Головні trade-off цього звіту

- **Ambition vs shippability.** Кожна vision-ідея круто звучить, але
  якщо в beta потрапить 12 фіч — жодна не буде добре полірована.
  Принцип: 1 сильна ідея (Battle=workout) + 2 підсилювача (realm-identity +
  weekly recap) + 1 дистрибуційний event (День Розколу).
- **Grind vs emotion.** Всі фічі в IN мають «1 фіз-подвиг = 1 емоційний
  hit». Якщо треба зробити N повторів одного action для 1 reward — це OUT
  або переформатувати.
- **Monetization risk vs growth.** Beta-monetization обмежена
  косметикою + Portal Creation Kit referral, ніяких SKU з stat-преф.
  Ціль beta = WOM + D30 retention, не LTV.

---

## 3. USP + Hero feature

### 3.1. USP (одне речення)

> **«RPGFit — location-based RPG, де твоє реальне тренування миттєво
> конвертується в бойовий урон проти богів, які повернулись у 2042-му.
> Без pay-to-win, без штучних енергій: твоя форма — єдина зброя.»**

Обґрунтування:
- «Location-based RPG» — розміщує RPGFit поруч з Ingress / Pokémon GO
  (знайома категорія для Reddit/Product Hunt) [джерело 01:3.1].
- «Твоє реальне тренування» — витісняє Orna і Habitica, які дають RPG
  без health-truth [джерело 01:3.1, 3.4].
- «Миттєво конвертується в бойовий урон» — унікальна механіка RPGFit
  [BL §9.4 damage tick + §3 health→XP]. Жоден конкурент цього не має.
- «Боги повернулись 2042-му» — лор-хук B з 05:§3.2, перевірений
  American Gods / Thor / God of War [джерело 05:§5 Carta].
- «Без pay-to-win, без штучних енергій» — свідомий positioning
  проти Orna / Pokémon GO remote raid pass скандалу 2023
  [джерело 01:3.1, 3.4 #HearUsNiantic].

### 3.2. Hero feature = «Battle = твоє тренування в реальному часі»

**Чому одна фіча:** app store-обкладинка дає 3 секунди на розуміння.
3 секунди = 1 фіча + 1 емоція. Вибір «Battle=workout» — бо:

1. **Це єдине, чого немає в жодного конкурента в такому поєднанні**
   [джерело 01:3.4 «Battle=workout унікальна»].
2. **Стискує 3 з 7 emotional hooks одночасно:** §2 Рости (XP+damage),
   §3 Досягнення (мобів переміг), §5 Здоровий я (реальне тренування)
   [vision:emotional-hooks].
3. **Технічно вже працює в beckend** [BL §9]: mob HP, damage-per-tick,
   Performance Tier, XP-award. Не треба писати з нуля.
4. **Повідомлення «Твій Fitbit — тепер зброя»** (з 05:§8.6) —
   provocative, share-able, відрізняє від Strava («просто записую
   активність») і Pokémon GO («ловлю покемонів»).

**Візуал обкладинки:**
- Кадр активного Battle: мобільний екран з VECTOR CRT-UI + силует моба +
  HP bar, поряч — фотоснайпшот real юзера у плотинні у спортзалі з
  Apple Watch.
- Хедлайн: «Боги повернулись. У тебе 60 секунд.» [джерело 05:§8.6 —
  рекомендована ставка].

**Альтернативи які відкинуто:**
- Hero = realm-identity (faction choice screen). Красиво, але без бою
  скрін виглядає як «вибір аватара», що не передає унікальність.
- Hero = статичний портал у Галдхьопігені. Для beta — недостатньо юзерів,
  які туди доберуться; обгортка «гори + AR» зіграє як друга іконка, не
  перша.
- Hero = Weekly Recap. Утилітарно-Strava-like, не приваблює RPG-core.

---

## 4. IN / OUT таблиці

### 4.1. IN — у публічний beta-scope

Кожна фіча має: (а) який з 7 емоційних гачків посилює, (б) risk grind
yes/no + mitigation, (в) вплив на paying-вартість (монетизаційна логіка).

| # | Фіча | Гачок | Grind risk | Paying impact | Джерело |
|---|------|-------|------------|---------------|---------|
| 1 | **Opening 60s** (voice-over + 3 кадри + faction-pick) | §1 історія, §7 таємниця | Ні | Zero. Це онбординг. | 05:§6 |
| 2 | **Battle = workout у real-time** (Hero feature) | §2 рости, §3 досягнення, §5 здоровий я | Ні (fitness daily cap = 3000 XP [BL §4]) | Zero. Core free. | BL §9, 01:3.4 |
| 3 | **6-realm faction identity** (primaryRealm enum + +2% damage проти свого реалму) | §1 історія, §4 гільдія, §3 досягнення | Ні (no collect-all pressure; один вибір) | Косметика-скіни per realm = optional IAP post-beta | 01:§4 P0.2, 05:§3.3 |
| 4 | **Weekly Fitness Recap** (push + shareable card) | §2 рости, §5 здоровий я | Ні | Zero. Retention driver. | 01:§4 P0.3 |
| 5 | **Portal Creation Kit × 1** в стартовому пакеті | §3 досягнення, §4 гільдія (своє місце) | Ні | Kit×2 додатковий — референс-reward (НЕ продаж) | vision:onboarding-gifts, vision:beta-hype §Реферальна |
| 6 | **Starter Artifact Tier 1** (рандомний з 3) + XP boost 24h | §6 колекція, §1 історія | Ні (one-time) | Zero | vision:onboarding-gifts |
| 7 | **10-15 статичних порталів** з workout-challenge + virtual-реплікою | §3 досягнення, §4 гільдія, §7 таємниця | Low (max 10-15 — не «збирай 200») | Косметика-skin per portal = post-beta IAP | vision:portals, 01:§4 P1.1, 05:§5 |
| 8 | **Launch event «День Розколу 31.10»** (48 год, rare мобів всіх 6 реалмів) | §1 історія, §3 досягнення, §4 гільдія, §7 таємниця | Низький (річний event) | Zero. Ticketing = ні | vision:beta-hype, 01:§4 P0.1, 05:§8.1 |
| 9 | **Monthly «Day of Realm»** (1-й суботу місяця, 1 реалм = +20% XP спавн) | §4 гільдія, §2 рости | Низький (раз на місяць) | Zero у beta. Partnership-sponsorship post-beta | 01:§4 P0.6 (Pokémon GO Community Day pattern) |
| 10 | **Discord-server + 6 realm-каналів** + 2-3k seeded members pre-launch | §4 гільдія | Ні | Zero | 01:§4 P0.5 |
| 11 | **Referral: Portal Creation Kit на обох гравців** | §4 гільдія (growth) | Ні | Не прямо (growth-loop) | vision:beta-hype, 01:§4 P0.4 |
| 12 | **F2P-manifesto public** (store description + Reddit post + in-app) | §5 довіра до продукту | Ні | **Негативний** (обмежує майбутні SKU — і це свідомо) | 01:§4 P0.7 |
| 13 | **Streak multiplier** (3d=1.1x, 7d=1.2x, 14d=1.3x, 30d=1.5x) [BL §4.3 планово — impl!] | §2 рости | **Помірний** (streak pressure) | Zero direct | BL §4.3, 01:§4 P1.3 |
| 14 | **Named artifact за running-streak** (Мйольнір = 7d streak + 10 км) — але **без collect-all-fragments** | §3 досягнення, §6 колекція | Ні (earned-moment, не collect-50-shards) | Zero | 05:§3.4, 05:§7.6 |
| 15 | **Onboarding flavor-texts (первинні 10)** — moб Class I, перший артефакт, перший портал | §1 історія, §7 таємниця | Ні | Zero (text content) | 05:§7 |

**Обґрунтування для кожної фічі (узагальнено):**

- **Фіча 2 (Hero):** прибрати = beta розпадається. Все інше живе навколо.
- **Фіча 3 (realms):** якщо прибрати — втрачаємо §4 гільдія повністю (бо
  Discord-каналів нема з чого робити) + лоро-brand розпадається.
  Реалізація дешева: 1 enum + 1 damage modifier + 6 UI-бейджів.
- **Фіча 7 (портали):** прибрати = втрачаємо shareable-moment і
  ARG-component з launch event. 10-15 — нижня межа (див. §8).
- **Фіча 13 (streak):** помірний grind-risk. **Mitigation:** strict rule
  з emotional-hooks.md — «втрата streak != втрата рівня/лута, лише
  bonus-мультиплікатор». Rest-day grace (1 день буфера на тиждень)
  — post-beta.

### 4.2. OUT — не в beta

| # | Фіча | Чому OUT | Коли вводимо |
|---|------|----------|---------------|
| 1 | **Dynamic portals** (алгоритмічні тріщини на карті у реальному часі) | Вимагає geo-модуль з geofencing + push-нотифікації + антиспуф; не встигнемо до 31.10. Плюс — коли мобів у тебе на карті надто багато, це створює content-churn проблему (гравець «проходить» всі щілини за 2 тижні) [джерело 01:3.4, 01:5 «content-starvation»]. | 1.0 (Q1 після beta) |
| 2 | **Class IV Raid (Elite gods: Зевс, Тор, Ра)** | Raid-coop infrastructure (3-5 players sync) не готова в BL. Запустити Class IV без полірованого coop = community-вибух «impossible boss» [джерело 01:5 Ризики]. | 1.0+ anniversary launch |
| 3 | **Shiny-варіанти мобів 1/512** | Добра ідея, але без Collection Book UI та share-flow у beta це просто додатковий loot. Відкладаємо разом з Collection UI. | 1.0 |
| 4 | **Guild/Clan system** (з PvP, internal chat, guild-bank) | Strava Clubs у P1 (01:§4). Для beta достатньо Discord + faction identity. Повний guild-домен = місяці роботи | Post-beta (3-6 міс) |
| 5 | **Audio-narrative (Vector voice під Battle)** | Production-heavy (voice-actor + sound design). Beta без цього працює. | 1.5+ |
| 6 | **Season Pass + 90-day seasons** | Монетизація на beta заборонена за рамками «косметика + referral». Season Pass = subscription = mixed signal для F2P-manifesto-positioning [джерело 01:3.1, 01:4 P0.7]. | Post-beta (6-9 міс) |
| 7 | **Time-travel artifacts / темпоральні координати** | Narrative + dev complexity. Просторових достатньо для beta. [джерело 05:§9 OUT] | 1.0+ |
| 8 | **ARG «Де Кіра Левченко» + Season finale reveal** | Потребує dedicated community-mgr + 7-day teaser-campaign. Якщо бюджет обмежений — skip. Reveal — season finale post-beta. [05:§8.5, 05:§9 OUT] | Season 1 finale (90 днів після beta) |
| 9 | **Seasonal Arena (Orna-style ranked)** | Orna-аудиторія — не primary для beta (20-35 fitness+RPG, не hardcore RPG) [vision:beta-hype, 01:§4 P2.4]. | Post-beta 1.0+ |
| 10 | **AR-камера на статичних порталах** | Добре для share, але техніка ARKit/ARCore + 10-15 портал-assets. Перевіряємо спершу, чи портали зайшли загалом. | 1.0+ |
| 11 | **Brand partnerships (Nike/Decathlon/gym chains)** | Product-market fit спершу. Brand намагається post-product-traction. | Post-beta 1.0+ |
| 12 | **Паркруни / 5km event integrations** | Потребують B2B-угод; для beta — Discord-ком'юніті-run як альтернатива [05:§8.1 local running клуби] | 1.0 partner channel |
| 13 | **Pet/companion система** | Немає в лорі, vision не згадує. Не вигадуємо. | Not planned |
| 14 | **Mentor / coach AI assistant** | Scope-creep поза core. | Not planned |
| 15 | **Повна taxonomy 9 відкритих питань лору** | Spoil'ити = витрачати [джерело 05:§3.5]. Розкидаємо натяки, розгадки — post-beta. | Season finales |

---

## 5. 7-day retention plan (день за днем)

**Ціль:** юзер залишається граючим на D7 з probability ≥ 50% (target з
`beta-hype.md`). Структура кожного дня = 1 наратив-beat + 1 фіз-ціль +
1 соц-trigger.

### День 0 (install → perform → keep)

- **T+0 c:** Opening 60s (voice-over + 3 realm-кадри + faction-pick) [05:§6].
- **T+60 c:** HealthKit / Health Connect permission з flavor «Вектор
  підключається до твого БРП».
- **T+3 хв:** Перший Battle Class I мобів — flavor «Дрібна нечисть
  вискочила з тріщини» [05:§7.8]. Мета — перша victory ~5 хв workout.
- **T+5 хв:** Starter Artifact Tier 1 (рандом з 3) + XP boost 24h
  [vision:onboarding-gifts]. Flavor «Ця річ чекала. Тепер — твій хід»
  [05:§7.3].
- **T+7 хв:** Portal Creation Kit у інвентарі з tooltip «Створи перший
  портал у своєму місці». Кнопка «Створити пізніше».
- **T+8 хв:** Запрошення в Discord-realm-канал (6 варіантів) + link.
- **Метрика дня:** permission granted + 1 Battle complete + 50%+ бачать
  artifact-modal.

### День 1 — «Я щодня росту»

- **Push-нотифікація вранці:** «Оракул сканує. Йотун зафіксований у 1 км
  від тебе. Готовий?» [05:§7.2 + 7.10]. (Не geo — плейсхолдер для beta.)
- **Квест:** 2-й Battle + перший weekly recap teaser («ще 5 днів до
  першого звіту»).
- **Ranked naratyv:** Level 2. Перший level-up animation.
- **Соц-trigger:** Discord «представитись у своєму реалмі» — bot-привітання
  з flavor.

### День 2 — «Перша слабкість»

- **Push:** Якщо юзер пропустив День 1 → «Молот важчий сьогодні. Ти
  втомлений? Чи він випробовує?» [05:§7.10]. М'який, не страхітливий.
- **Квест:** перший Class I Elite (uncommon моб) з unique loot drop.
- **Streak start:** якщо 2 дні підряд тренувань → badge «Пробудження»
  (+1.1x XP на День 3, якщо продовжиш) [BL §4.3].

### День 3 — «Таємниця з'являється»

- **Flavor drop у Battle:** «Оракул мовчить уже 4 години. Це незвично.»
  [05:§7.10 hint на Оракул-підмінника].
- **Квест:** 3-й Battle + можливість обрати Recommended mode (server
  генерує план).
- **Мілстоун:** Rare drop з Class I Elite → Silver-tier чек-бокс у
  Collection (перший коллекційний момент).
- **Push:** якщо streak 3 дні → «+1.1x XP. Продовжуй.»

### День 4 — «Маленька спільнота»

- **Соц-trigger:** в Discord-realm-каналі публікується «сьогодні в Асгарді
  3 нових оператори, привітайте». Bot-згадує username.
- **Квест:** Raid-mode відкривається (якщо level ≥ 5). Flavor «Клас III.
  Одному — смерть. Збери трьох. Або пройди повз — але тоді воно вийде
  саме.» [05:§7.5]. **Але без реального co-op infra** — це solo-Raid
  мод з підвищеною difficulty [BL §9.2 Raid mode].
- **Мілстоун:** перший Raid complete → epic drop.

### День 5 — «Я у великій історії»

- **Flavor drop:** «Тріщина-104 і тріщина-289 горять синхронно. Так не
  повинно бути.» [05:§7.10 Сет+Тескатліпока hint].
- **Квест:** Level 7-8 (casual 312 XP/day [BL §3.4.1]). Unlock перших
  2 активних skill (з Profession Tier 1) [BL §5, §6].
- **Share-prompt:** перший shareable-card «Я на рівні 8 в Асгарді. Level
  up made by [date].»

### День 6 — «Streak bonus +1.2x»

- **Push:** 6-day-streak hit → «+1.2x XP на сьогодні. Молот важить по
  заслугам.»
- **Квест:** 6-й Battle, перша спроба Portal Creation Kit
  («створи у своєму спортзалі») — UX-тест скільки юзерів реально
  створює.
- **Статичний портал teaser:** у Vector-UI з'являється мапа з 10-15
  точками → «статичні портали — місця, де можна здобути Tier 2 артефакт.
  Найближчий — 3.2 км.»

### День 7 — «Weekly Recap + Static Portal»

- **Ранкова push:** «Цього тижня ти пройшов +15 km, +8 битв, +3 рівні.
  Ти у топ-40% активних Асгарду.» [01:§4 P0.3 Strava pattern].
- **Shareable card:** автоматично генерується (stats + realm-brand +
  avatar + XP-curve).
- **Квест:** перша спроба дістатися статичного порталу (якщо 10-15 км —
  там реальний, якщо далі — virtual-репліка з тим самим сюжетом) [vision:portals].
- **Мілстоун:** якщо юзер добирається до static-portal і завершує його
  workout-challenge → Tier 2 артефакт + «Я був на Галдхьопігені» badge у
  профіль (share trigger) [01:3.3 «я був тут» pattern].
- **Referral prompt:** «У тебе є +1 Portal Creation Kit для друга. Хто
  хоче?»

**Чому саме такий порядок:**

- День 0–2: юзер залишається у грі через **прогрес** (hook §2 Рости) +
  **історія** (§1) + **колекція** (§6 starter artifact).
- День 3–4: розблоковується **таємниця** (§7) і **гільдія** (§4 Discord).
- День 5–6: **streak** дає перший bonus-hit, який хочеться не втратити
  (економічний якір, але не жорсткий — без streak-loss-penalty).
- День 7: **досягнення** (§3 mi Галдхьопіген) + **здоровий я** (§5
  Weekly Recap). Коло замкнулося — всі 7 hooks задіяні за тиждень.

**Ризик і mitigation:**

- **Якщо юзер «ламає» streak у день 3–4** (типово — буднева перевтома):
  streak reset, але рівень/лут зберігаються. Push «Молот зачекає. Повертайся
  завтра» (м'який, без FOMO) [vision:emotional-hooks червона лінія].
- **Якщо HealthKit permission не наданий:** fallback на manual workout
  logging (BL вже підтримує). День 0 Battle працює через manually-logged
  sets. Але Weekly Recap стає беззубим — soft-onboarding на День 2–3
  повертає prompt «дай Вектору доступ до БРП для повного сканування».

---

## 6. Hype triggers (3-5 механік)

Пріоритизовано за ROI (impact на 10k beta target / effort). Використати
**мінімум 3 з 5**, ідеально 4.

### 6.1. Launch event «День Розколу 31.10» (P0, MUST)

**Механіка:** 48-годинний global event, 31.10–02.11. Всі 6 реалмів
одночасно spawn-ять Class II-III rare мобів. Перші **1000 у кожному
реалмі**, хто переможе boss-moba — отримують named Tier 2 artifact.

**Чому зайде:**
- Ingress Anomaly = 254,184 IRL-attendees у 2015 [джерело 05:§8.2, 01:3.1].
- Halloween-дата — природній поп-культурний wrap (боги + нечисть).
- Лорна дата — «лор стає реальністю» [джерело 05:§8.1].

**Execution risk:**
- Rare моби мусять бути у production ДО event (не «під час»).
- Discord-модератори на 48 годин на зміну.
- Якщо core-scope не встигає — **не пускати** раїд + Class III, залишити
  тільки Class II + cosmetic celebrate. Провалений raid = ширший ризик
  ніж перенос.

**Mitigation:** Альтернативний «День Розколу Lite» — без boss-mob,
тільки підсилений спавн + cosmetic reward + spillover-hype наратив
«оператори зібрались».

**Джерела (мої, не 01/05):** Steam Early Access playbook (див. §11, 1)
рекомендує «one high-emotion moment in first 30 days» — це воно.

### 6.2. «Обери сторону» faction pre-launch campaign (P0)

**Механіка:** landing-сторінка з 6 realm-варіантами + 5-питань Pottermore-style
quiz [джерело 05:§8.4 Sorting Hat reference]. Юзер обирає → email-capture →
AR-filter-маска свого реалму + reminder за 1 день до launch.

**Чому зайде:**
- Pottermore Sorting = 50M+ users історично (перевіряю по ref у 05:§8.4).
- Factions створюють «племʼя» до того, як юзер відкрив app = community
  seeding.
- 6 Discord-каналів вже будуть заповнені fan-art коли юзер зайде.

**Execution:** landing + quiz — 1 дизайнер + 1 розробник, 2 тижні. AR-філтр —
Snapchat Lens Studio (безкоштовно) або Instagram filter.

### 6.3. «Перші 1000 на Галдхьопігені» (P1 — умовний)

**Механіка:** 1 named artifact (Мйольнір) на континент = 5-6 унікальних
destinations (Галдхьопіген-ЄС, Санторіні-Медіт, Амазонія-LATAM,
Каньйон Фіш-Рівер-Афр, Ангкор-Ват-ЮВА, Дніпро-UA) [джерело 05:§8.2].
Перші 1000 юзерів у кожній локації, які **фізично** дійшли і виконали
workout-challenge → серіалізована cosmetic-репліка артефакту.

**Чому зайде:**
- «я був тут» shareable momentum [джерело 01:3.3].
- Stream/TikTok-готовий контент (travel + fitness + RPG).

**Ризик:**
- Ексклюзивна локація = демотивація тих, хто далеко.
- Legal-ризик імагії landmark [джерело 05:§3.4 risk Cambodian heritage].

**Mitigation:**
- Параллельно — 5-6 локацій per-continent.
- Virtual-репліка для remote-юзерів (lower-tier artifact, той самий
  сюжет) [vision:portals B.4].

**Умовний бо:** потребує gps-verification + anti-spoof. Якщо tech-scope
не встигає — відкласти на post-beta launch anniversary.

### 6.4. Discord-seed pre-launch (P0)

**Механіка:** Discord-сервер за **2-3 місяці ДО** beta. 6 realm-каналів,
moderator-bench (volunteer + 1 paid community-mgr), bot для event-sync.

**Чому зайде:**
- Discord-first community =  P0 для location-based [джерело 01:3.3, 01:§4 P0.5].
- 2-3k активних членів на launch = достатньо [джерело 01:5 припущення П3].

**Execution:** найняти community-mgr зараз. Перший контент — art-briefs
для 6 реалмів, мем-seeding, fan-theory threads.

### 6.5. F2P-manifesto + Reddit AMA (P0)

**Механіка:** публікація **перед** beta-launch на r/IndieGaming + r/gamification
+ r/fitness: «Ось наш SKU-list. Pay-to-win? Ось красна лінія. Стресом
бета — протестуйте і кажіть, де зрадимо.»

**Чому зайде:**
- Trust-builder проти скепсису Orna / Niantic-refugees [джерело 01:3.4 P0.7].
- Reddit-аудиторія P0 для growth [vision:beta-hype].

**Ризик:** якщо потім порушимо manifesto — backlash гірший ніж без нього.
**Mitigation:** BA-review кожного SKU, legal review перед public publish.

**Джерела (мої, не 01/05):** Product Hunt best-practices (§11, 2-3):
«authentic maker narrative» + «ship early access, not polished launch» —
F2P-manifesto = the маркетинг-hook що підходить цій аудиторії.

### (Альтернативно) 6.6. ARG «Де Кіра Левченко» (P2 — skip-able)

**Механіка:** 7-day Twitter/X тизер-серія з «місць зникнення Кіри», шифр
у фото, reveal-пост → landing «Шукай її в RPGFit 31.10» [джерело 05:§8.5].

**Чому skip-able:** потребує dedicated community-mgr + copywriter +
shoot-budget. Якщо community-mgr вже завантажена Discord-seed — не
робити.

---

## 7. F2P-manifesto (червоні лінії)

Публічний документ, обовʼязковий для beta-announce. Короткий формат
(фото-пост, ~6 пунктів). Синтез з `emotional-hooks.md §Червоні лінії` +
01:§4 P0.7.

### Обіцянки RPGFit у beta і post-beta

1. **Жоден IAP не дає stat-переваги.** Твій рівень, твій damage, твій
   loot — лише функція фіз-тренувань. Купити «Vector+» не існує.
   [vision:emotional-hooks §Pay-to-win]
2. **Жоден loot-box не існує.** Усі нагороди = прозорі, прив'язані до дії.
   Зробив workout → отримав drop. Немає «відкрив crate → random».
   [vision:emotional-hooks §Лут-бокси]
3. **Жодна «енергія» не купується.** Твоя енергія — реальна, з HealthKit.
   Не можна «купити ще 5 Battle сьогодні». Stamina-система у RPGFit не
   існує як монетизована.
   [vision:emotional-hooks §Грайндні стіни за гроші]
4. **Streak-loss не стирає прогрес.** Пропустив день — втрачаєш лише
   bonus-мультиплікатор. Рівень, лут, колекція — залишаються.
   [vision:emotional-hooks §Темний патерн]
5. **FOMO-таймери — тільки weekly/monthly, не daily.** Daily reward
   не «зникає через 24h з приниженням». Тиждень — так (Day of Realm),
   день — ні.
   [vision:emotional-hooks §FOMO-агресивний дизайн]
6. **Косметика — це все.** Скіни Вектора, avatar-ефекти, realm-cosmetic
   — це повний діапазон IAP на beta. Subscription / Season Pass — не у
   beta.
   [vision:emotional-hooks §Монетизація]

**Що за рамками маніфесту (дозволено):**
- Косметичні скіни (за Ті 3-6) — pay to look, не to win.
- Додаткові inventory slots (QoL) — post-beta, не beta.
- Referral reward (Portal Creation Kit) — free на обох сторонах.
- Partner-brand cosmetic — «Nike-skin Vector» — post-beta.

**Цінова дисципліна:** будь-який SKU cosmetic < $9.99 (уникає
Orna-style overpricing-backlash [джерело 01:3.2 Orna]).

---

## 8. Content scale для beta (мінімум)

Ціль: **уникнути content-starvation на D14** [джерело 01:§5 Ризики].
Casual 312 XP/day → рівень 10 за 15 днів [BL §2 Level Progression]. За
цей час юзер бачить ~150+ мобів. Тому content-density має витримувати
30 днів повторної engagement.

### 8.1. Моби

| Axis | Minimum for beta | Джерело |
|------|------------------|---------|
| Загальна кількість | **2000** (вже є в BL, 20/level × 100 levels) | BL §10 |
| Realm affiliation | **Всі 2000 прив'язані до 1 з 7 (6 реалмів + neutral)** | vision:mobs, P0 для realm-identity |
| Flavor-text | **Мінімум 100 унікальних flavor-рядків** (по 15-20 per realm) | 05:§7 flavor patterns |
| Class I (common+uncommon) | **~1400** (70%) — casual engagement | BL §10 |
| Class II (rare) | **~400** (20%) — rare-hunt driver | BL §10 |
| Class III (epic) | **~160** (8%) — Raid-unlocks Day 4+ | BL §10 |
| Class IV (legendary) | **~40** (2%) — post-beta reveal | BL §10 OUT for beta |
| Rarity visual | **Distinct art per rarity + realm** (~ 40-50 unique silhouettes total, color-recoloring OK) | vision:mobs |

**Що саме «на вчора» з існуючого BL:**
- Добавити `Mob.realm` enum (7 values). Backfill — ~1 день роботи +
  distribution script (рівний split по realm + neutral для levels 1-5).
- Добавити `Mob.classTier` (I-IV), map з rarity [vision:mobs §2].
- 100 flavor-рядків — copywriter-завдання, 1-2 тижні.

### 8.2. Портали (статичні)

| Axis | Minimum | Джерело |
|------|---------|---------|
| Кількість | **10-15** | vision:portals MVP |
| Гео-розподіл | **Min 1 per continent** (5+) + **1 UA-specific (Дніпро-Кий-Велеса)** | vision:portals, 05:§8.2 |
| Workout-challenge variety | **3-5 challenge-типів** (run 5km / 10 exercises / HIIT 10min / yoga-streak / swim) | vision:portals §B |
| Artifact reward | **Tier 2 unique per portal** = 10-15 унікальних артефактів | vision:portals §B.4 |
| Virtual-репліка | **Всі 10-15 мають virtual-variant** для remote-users | vision:portals §B.4 |

**Конкретний список (мій рекомендований):**
1. Галдхьопіген (Норв) — Мйольнір [05:§7.7]
2. Санторіні (Греція) — Егіда [05:§9b Егіда з docx]
3. Теотіуакан (Мексика) — Ятаган Тескатліпоки
4. Ангкор-Ват (Камбоджа) — Тришула
5. Ватнайокутль (Ісландія) — Гунгнір [05:§9b]
6. Амазонія (BR) — Посох Анубіса (переместити у LATAM для balance)
7. Гімалаї (Непал) — alternative Тришула / bigger-yoga challenge
8. Каньйон Фіш-Рівер (Намібія) — unique UA/Afr
9. **Дніпро біля Канева (UA) — Кий Велеса** [05:§9b — ключовий для UA]
10. Крим-Яйла (UA) — UA-secondary
11. Ерг-Шебі (Марокко) — Сокира Перуна
12. Lake District (UK) — Лук Артеміди
13. Grand Canyon (US) — Тризуб
14. Uluru (Australia) — Tier 2 generic (if needed)
15. Mt Fuji (Japan) — Tier 2 generic (if needed)

**Legal-аудит:** на 12+ потрібен juristconsent (heritage-laws
Камбоджа/Мексика) [05:§10 Припущення 5]. **Альтернатива:** назвати
locations по координатах + geo-name, не спекулюючи художніми деталями
храмів.

### 8.3. Артефакти

| Axis | Minimum | Джерело |
|------|---------|---------|
| Tier 1 starter (random 3) | **3** (Старий меч / Амулет / Кістяна маска) | vision:onboarding-gifts §4 |
| Tier 2 з static portals | **10-15** (1 per portal) | vision:portals §B.4 |
| Tier 3 earned (e.g., Мйольнір = 7d streak) | **5** (Мйольнір, Тризуб, Сокира Перуна, Кий Велеса, Лук Артеміди) | 05:§3.4 |
| Tier 4 — Class IV boss drops | **0 у beta** (OUT) | OUT §4.2 |
| **Всього в beta** | **18-23 унікальних артефакти** | синтез |

### 8.4. Професії та Skills

**Без змін до BL** — 48 професій × 3 tier + 39 skills вже є [BL §5, §6].
Для beta — показати UI (profession tree) для T1+T2, T3 — пізніше.

### 8.5. Discord content

| Axis | Minimum | Джерело |
|------|---------|---------|
| Канали | **6 realm + 4 функціональні** (general, help, art-share, raid-lfg) | 01:§4 P0.5 |
| Pre-launch членів (seed) | **2-3k** | 01:§5 П3 |
| Модератори | **Min 6** (1 per realm) + 1 paid community-mgr | синтез |
| Fan-art briefs | **6** (по 1 per realm) | 05:§8.4 |

### 8.6. Подсумок content-scale

Для уникнення content-starvation на D14+ критично:
- 2000 мобів з flavor (у існуючому BL), realm-mapping.
- 10-15 статичних порталів.
- 18-23 артефакти (Tier 1-3).
- 100+ flavor-рядків.
- 1 launch event + 1 monthly Day of Realm (жовтень-грудень 2026 = 3
  cycle до кінця року).

---

## 9. Оцінка ідей фаундера з `docs/vision/`

Явно, як вимагає інструкція.

### 9.1. Realm-mapping мобів (vision:mobs) — **IN**

- **Рішення:** IN у beta. Вся 2000-mob колекція отримує `realm` enum.
- **Чому:** без realm-mapping 6-realm identity (faction) стає декларативним
  skin-ом без ігрового значення. А з ним — realm-artifacts дають +40%
  damage проти свого realm-моба = ігрова логіка для §4 гільдія [джерело
  01:§4 P0.2, vision:mobs §1-2].
- **Grind-risk:** Низький. Реалм = tag, не «збери всіх 6 реалмів».
- **Paying impact:** Нуль у beta. Realm-скіни (cosmetic) — post-beta IAP.
- **Effort:** ~2 тижні (migration + backfill + seeding script + UI-badge).

### 9.2. Статичні портали з workout-challenge (vision:portals) — **IN (10-15)**

- **Рішення:** IN у beta. Мінімум 10, ідеал 15. Virtual-replica для
  remote.
- **Чому:** unique «я був тут» shareable-moment [джерело 01:3.3],
  Ingress-pattern validated [01:3.1]. §3 досягнення + §4 гільдія
  найяскравіше задіяно.
- **Grind-risk:** Помірний, якщо портали вимагають «N повторів для
  Tier 2». Mitigation: **один portal = один workout-challenge = один
  artifact** (або Bronze/Silver/Gold tier за performance [BL §8 Reward
  Tiers]).
- **Paying impact:** Нуль у beta. Portal-skin cosmetic — post-beta.
- **Effort:** **P1 у 01:§4** — тобто не беззастережний MUST. Якщо
  scope-tight, urizzes **8-10 static portals** без virtual-replica. Якщо
  зовсім tight — відкласти на 1.0 і зробити «портал = Weekly Challenge»
  як manual-quest (без geo).
- **Minimum viable:** 10 порталів, workflow challenge-типу «виконай X
  вправу Y хвилин», artifact Tier 2 як reward.

### 9.3. Portal Creation Kit як онбординг-подарунок (vision:onboarding-gifts) — **IN**

- **Рішення:** IN у beta як **ключова частина onboarding gift + referral
  reward**.
- **Чому:** дає «моє місце» з першого дня [vision:onboarding-gifts §1],
  підсилює §3 досягнення + §4 гільдія. Referral-multiplier для beta growth
  [vision:beta-hype].
- **Grind-risk:** Дуже низький — юзер отримує × 1 kit на onboarding +
  може заробити ще × 1 за referral. Cap: 2 kit per user per month у
  beta, щоб не створити «portal-spam» content.
- **Paying impact:** Ключовий referral driver. Сам kit не продається у
  beta. Post-beta — multi-kit для event-ів.
- **Effort:** 1 новий `ItemType = portal_kit` + UI-flow «створення
  порталу» + location-permissions. ~1-2 тижні.
- **Risks:** 
  - «Мертвий портал» (юзер створив і не повернувся) — auto-delete через
    14 днів [vision:onboarding-gifts §Відкриті питання].
  - Geo-spoof (створити портал у Антарктиді) — validation через GPS
    accuracy + speed-check.

### 9.4. «День Розколу 31.10» як launch event (vision:beta-hype) — **IN (з умовою)**

- **Рішення:** IN як **48-годинний launch-event**, але **НЕ обовʼязково
  як реліз-дата**.
- **Чому IN:** перевірений pattern (Ingress Anomaly, Fortnite Black
  Hole) [джерело 01:3.1, 05:§8.1]. Лорна дата = natural storytelling
  hook. Retention booster: глобальна одночасна activity у 6 realms.
- **Чому умовно:** якщо core-scope (Battle-as-workout + realm-mapping +
  Static portals) не встигає до 31.10 — **soft-launch 31.10 з
  cutdown-event** (тільки підсилений спавн + cosmetic) + «Grand Opening»
  на наступний квартал з повним raid + Class III.
- **Grind-risk:** Дуже низький (raз на рік).
- **Paying impact:** Нуль. Partnerships з паркрунами / gym-chains —
  post-beta.
- **Effort:** подієва механіка = 3-4 тижні (event-schedule entity,
  rare-spawn modifier, event-reward distribution).
- **Критичний decision-point:** фаундер має прийняти рішення **8-10
  тижнів до 31.10** — launch-ready чи cutdown.

### 9.5. Додаткові ідеї з vision, які не в питаннях вище

- **`vision:portals` dynamic portals** — **OUT** у beta. Потребують
  повного geo-модуля + moderation. 1.0.
- **`vision:mobs` Class IV = Elite gods** — **OUT** у beta. Raid-coop
  infra не готова.
- **`vision:mobs` Oracle-task behavior type** — **IN частково**. Існуючий
  Recommended-mode у BL §9 вже це робить (server-generated workout plan).
  Просто flavor-переклеїти в «Оракул видав завдання».
- **`vision:beta-hype` Реальні спонсори** — **OUT** у beta. Partner-deals
  = post-launch.
- **`vision:beta-hype` PvP між реалмами** — **OUT** у beta (явно
  [vision:beta-hype §Post-beta]).

---

## 10. Ризики + mitigation

Синтез з 01:§5 + 05:§10 + нові для beta-specific.

### 10.1. Monetization-scandal (Orna-style cosmetic-pricing, Niantic-style
#HearUsNiantic)

- **Ризик:** будь-який SKU з stat-benefit = Reddit-вибух.
- **Probability:** Low (F2P-manifesto prevents), але **Impact: Critical**.
- **Mitigation:** (1) Publish F2P-manifesto перед launch.
  (2) BA-review кожного SKU перед release.
  (3) Cosmetic pricing < $9.99 (Orna-lesson) [01:3.2].

### 10.2. Content-starvation на D14

- **Ризик:** юзер за 14 днів бачить усіх мобів / всі портали → churn.
- **Probability:** Medium.
- **Mitigation:** (1) 2000 mobs з procedural variants (prefix × base)
  [BL §10]. (2) 10-15 static portals + 6 Day of Realm monthly + 1 Launch
  Event = content що «додає» після D14. (3) Streak multiplier як
  engagement-layer на top of exists content. (4) Virtual-replica portals
  = access-equity для remote-users.

### 10.3. HealthKit / Health Connect permission refusal

- **Ризик:** 10-20% iOS юзерів відмовляють [01:§5 #4 гіпотеза]. Без
  нього RPGFit = manual log only.
- **Probability:** Medium-High.
- **Mitigation:** (1) Soft-onboarding — prompt через 60 с після opening
  (юзер вже «всередині») [05:§6 design-принципи]. (2) Fallback manual-log
  flow (BL підтримує). (3) iOS entitlement `com.apple.developer.healthkit.background-delivery`
  обов'язковий [01:§5 HealthKit sources]. (4) Reprompt на День 3
  з flavor wrap «Вектор сканує частково — дай повний доступ для БРП».

### 10.4. GPS-spoofing на статичних порталах

- **Ризик:** cheater-community появляється миттєво на location-based
  продуктах [01:3.2 Strava 4.45M fake activity purge].
- **Probability:** Medium.
- **Mitigation:** (1) GPS як optional layer — core XP тільки з HealthKit
  [01:§5 #3]. (2) Speed-threshold detection для portal visits (>15 км/год
  через gate = суспільно). (3) Tier 2 artifact з static portal не ламає
  баланс (Tier 3 earned-through-fitness, не через portal).

### 10.5. Launch date slip (31.10 не встигаємо)

- **Ризик:** Beta не готова до жовтня 2026.
- **Probability:** Medium-High (синтез з BL scope — 15 IN фіч).
- **Mitigation:** (1) **Decision-gate 8-10 тижнів до 31.10** — фаундер
  приймає «launch-ready чи cutdown». (2) Cutdown scope: Opening 60s +
  Battle-as-workout + 3-5 static portals + 6-realm identity + Discord
  +F2P-manifesto. Все інше — перенос на «Grand Opening». (3) Alternative
  launch-date: **01.01.2027** (Новий рік = «Перший оператор 2027-го») як
  culturally-salient альтернатива 31.10.

### 10.6. Niantic / competitive response (fitness-mode для Pokémon GO)

- **Ризик:** Pokémon GO (або Adventure Sync-розширення) додає
  fitness-integrity первинно [01:§5 #5].
- **Probability:** Low-Medium (Niantic доволі повільні у features-expansion).
- **Mitigation:** **RPG-depth** (48 профій × 3 tier, 39 skills, 12
  equipment slots) = те, що Niantic-продукти не мають [01:§5 #5].
  RPGFit позиціонується не як competitor Pokémon GO, а як complement
  Strava (01:3.1).

### 10.7. Legal: landmark imagery / heritage-laws

- **Ризик:** згадка Ангкор-Ват / Теотіуакан з художньою інтерпретацією
  — потенційний героїджевий legal-issue [05:§3.4].
- **Probability:** Low (geo-names public).
- **Mitigation:** (1) Artistic-original design (не recreating actual храми).
  (2) Legal review списку 10-15 порталів з юристом **до public announce**.
  (3) Fallback: заміна сумнівних localization на geo-coord-only.

### 10.8. Discord-server «dead on arrival»

- **Ризик:** менше 1k активних членів на launch = «ghost town» ефект,
  юзер заходить, бачить 3 online, churn.
- **Probability:** Medium.
- **Mitigation:** (1) Community-mgr hire 2-3 міс до launch. (2) Fan-art
  briefs, meme-seeding pre-launch (джерело 05:§8.4). (3) Pre-launch
  faction campaign email-capture → auto-redirect to Discord → conversion
  funnel. (4) MIN threshold to launch: **2k seeded members** [01:§5 П3].

### 10.9. Streak pressure → burnout / injury

- **Ризик:** fitness-integrity. Юзер тренується щодня щоб не втратити
  streak = ризик overtraining.
- **Probability:** Medium (для 10-20% hardcore audience).
- **Mitigation:** (1) Rest-day bonus (+10% XP на наступну сесію після
  rest day) [vision:emotional-hooks §Grind-контроль]. (2) Diminishing
  returns після 60 хв тренування/день. (3) Sleep XP cap = 10 годин
  [BL §3]. (4) Flavor текст «Молот важчий сьогодні. Ти втомлений? Чи
  він випробовує?» [05:§7.10] — soft-nudge rest-day.

### 10.10. Content localization (UA/EN/RU)

- **Ризик:** beta-target UA+Global. Flavor, opening, voice-over —
  дублюється на EN. Якщо тільки UA — global-reach блокується.
- **Probability:** High (by design).
- **Mitigation:** (1) UA voice-over + EN voice-over = обов'язковий
  для opening 60s. (2) 100+ flavor text translations UA→EN (copywriter
  + native reviewer, 2 тижні). (3) Discord — 2 сервери (UA, EN) або 1
  з-channels. Відкрите питання [01:§5 Відкриті #5].

---

## 11. Посилання

### Внутрішні (обов'язкові, прочитано)

- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/agents/02-beta-scope.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/01-market-research.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/05-lore-to-hook.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/emotional-hooks.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/beta-hype.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/portals.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/mobs.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/onboarding-gifts.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/BUSINESS_LOGIC.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/specs/2026-04-04-flow-summary.md`

### З 01 (топ-джерел використаних у синтезі)

- Pokémon GO lifetime revenue — Sensor Tower
  (sensortower.com/blog/pokemon-go-6-billion-revenue) — 01:§3.1
- Community Day 3h formate — Pokémon GO Wiki
  (pokemongo.fandom.com/wiki/Community_Day) — 01:§3.1
- Remote Raid Pass price hike 2023 — TechCrunch
  (techcrunch.com/2023/03/30/pokemon-go-will-raise-the-price-of-remote-raid-passes/) — 01:§3.1
- Strava paywall leaderboards Nov 2024 — BikeRadar
  (bikeradar.com/news/strava-leaderboards-routes-subscription) — 01:§3.1
- Ingress Anomaly 4h Saturday format — FevGames
  (fevgames.net/ingress/ingress-guide/concepts/anomaly/) — 01:§3.1
- Strava 135M users — Business of Apps
  (businessofapps.com/data/strava-statistics/) — 01:§3.1
- Monster Hunter Now 10M DL month 1 — Game World Observer
  (gameworldobserver.com/2023/10/19/monster-hunter-now-downloads-10-million-niantic-capcom) — 01:§3.1

### З 05 (топ-джерел використаних у синтезі)

- TV Tropes «Only the Worthy May Pass»
  (tvtropes.org/pmwiki/pmwiki.php/Main/OnlyTheWorthyMayPass) — 05:§3.4 artefacts
- American Gods Wiki «Deities» (thought-forms)
  (americangods.fandom.com/wiki/Deities) — 05:§5 Carta
- Niantic «Three Years of Ingress» 254k IRL attendees
  (nianticlabs.com/news/three-years) — 05:§8.2
- Fortnite Black Hole 7M concurrent — The Verge
  (theverge.com/2019/10/23/20929589/fortnite-black-hole-event-season-11-viewers-twitch-twitter-youtube-live) — 05:§8.1
- Pottermore Sorting 27-question model — Snidget Seeker
  (snidgetseeker.gitlab.io/pottermore/) — 05:§8.4
- Screen Rant Thor worthy psychology
  (screenrant.com/thor-worthy-psychology-philosophy-expert-reaction/) — 05:§5 Carta
- CBR Pokémon GO walking-egg meme-economy
  (cbr.com/pokmon-go-hatch-eggs-crochet/) — 05:§5 Carta
- TV Tropes «Gods Need Prayer Badly»
  (tvtropes.org/pmwiki/pmwiki.php/Main/GodsNeedPrayerBadly) — 05:§5 Carta

### Нові джерела 02 (beta-playbook-focus, 5 штук)

Мої додаткові, які не було в 01/05. Валідовані через WebSearch 2026-04-18
(контекст — playbook для beta-запусків, не знахідки про продукт):

1. **MobileDevMemo — «Mobile game launch strategy» (Eric Seufert, 2024)**
   — https://mobiledevmemo.com/mobile-game-launch-strategy-part-i-the-soft-launch/ —
   основа playbook «soft-launch → optimize → geo-expand → global», яка
   дає template для 31.10 soft-launch vs Grand Opening. Використано у
   §5 (retention plan) + §10.5 (launch date slip).
2. **Product Hunt «How to launch» Maker Guide** —
   https://www.producthunt.com/launch — community-seeding pattern
   (seed у Discord/subreddits до launch, email-capture landing,
   launch-day scheduling). Валідує §6.2 Faction campaign і §6.5 Reddit
   AMA.
3. **Steam Early Access Best Practices — Valve Developer Wiki** —
   https://partner.steamgames.com/doc/store/earlyaccess — «ship a
   minimum viable, let community feedback drive scope». Валідує
   scope-discipline §4 IN/OUT (1 strong idea + 2 amplifiers, не 12
   фіч).
4. **r/gamedev топ-пост «What I learned from a failed indie launch» (2024)** —
   https://www.reddit.com/r/gamedev/comments/18j3n6s/what_i_learned_from_a_failed_indie_launch_10_mo/ —
   уроки: (a) community-seed 3-6 міс pre-launch, (b) feedback-loop 1-week,
   (c) monetization-trust публічно. Використано в §10.1 + §10.8.
5. **VentureBeat / GamesBeat «Fortnite Black Hole Event Analysis (2019)»** —
   https://venturebeat.com/games/fortnite-the-end-event-was-live-gaming-at-its-best/ —
   urgent one-time event drives cross-platform virality. Доповнює 05:§8.1
   цифри у §6.1 Launch Event обґрунтування.
6. **r/IndieDev «Discord community seeding case study» (2024)** —
   https://www.reddit.com/r/IndieDev/comments/163nu9o/i_grew_a_discord_server_to_10k_members_before/ —
   community-mgr spending, art-brief, mem-seed patterns. Валідує §6.4
   Discord-seed + §10.8 mitigation.

### Що НЕ дочитано (винос для наступних агентів)

- App Store / Google Play «recently launched» Fitness / Adventure —
  як позиціонують USP. Не робив web-dive, бо 02 — scope, не research.
  **03 roadmap** може перевірити перед store-copy fin.
- MobileDevMemo deep-dive (paid-subscription) — для ціноутворення
  post-beta SKU. **03/04** може знадобитись.
- r/Ingress та r/orna top-posts 2024 (lefted as needs-web-verify у 01) —
  для community-sentiment.

---

## 12. Посіт-scriptum для фаундера та наступних агентів

### Для фаундера — 3 рішення, які треба прийняти **до** переходу в 03-roadmap

1. **Decision 1 (scope-gate):** 15 IN-фіч = realistically 4-6 місяців.
   Якщо launch 31.10 — почати сьогодні з command commit. Якщо сумніви —
   звузити до P0-core: Opening 60s + Battle-as-workout + realm-identity
   + static-portals-5 + F2P-manifesto + Discord = 6 фіч, 3 місяці.
   **Які з IN-15 готов прибрати ти?**
2. **Decision 2 (launch-date):** 31.10.2026 чи перенос?
3. **Decision 3 (monetization для beta):** підтверджуєш F2P-manifesto
   публічно? Якщо потім порушиш — backlash. Чи є **будь-які**
   потенційні SKU, які хочеш залишити «на всякий» post-beta які ламають
   manifesto? Краще сказати зараз.

### Для 03-roadmap

Вхід: §4 IN-table (15 фіч) + §8 content-scale + §9 vision-idea-decision.
Вихід роадмепу має рішити: (a) timeline на кожну з 15, (b) dependencies,
(c) team allocation (frontend/backend/copywriter/community-mgr),
(d) decision-gates (scope-gate 8-10 тижнів до 31.10).

### Для 04-code-audit

Вхід: §4 IN-table + 01:§3.4 gap-таблиця.
Особливо перевірити готовність до beta:
- `Mob.realm` enum (нема — треба migration).
- `CharacterRace` vs `primaryRealm` co-existence (BL §2 race-passives
  vs new realm-bonus overlap?).
- `Streak` service (BL §4.3 заплановано, не impl).
- `PortalCreationKit` ItemType (нема — треба новий ItemType + flow).
- Portal entity (повністю нова).
- Event-schedule entity для Day of Розколу + Day of Realm.
- Weekly Recap cron + rendering (нова фіча).

### Changelog

- 2026-04-18 — v1. Синтез 01 + 05 + vision + BL. 5 нових beta-playbook
  URL. Timebox ~40 хв дотримано. Обмеження: не читано Theорія_Світу.docx
  повний (дистилят з 05).
