# 01 — Market Research: Ingress / Pokémon GO / Orna / Strava (+Zombies Run!, Habitica, Sweatcoin, Monster Hunter Now)

> **Важливо про джерела.** Звіт пройшов вузьку валідацію через WebSearch 2026-04-18: ~12 ключових тверджень підкріплено inline-посиланнями `[перевірено URL 2026-04-18]`. Залишкові маркери — значення:
>
> - **[перевірено URL 2026-04-18]** — факт підтверджений конкретним first-party або reputable-mirror джерелом (повний URL у розділі 6).
> - **[verified-offline]** — загальновідомий факт з моєї training-data, ще не валідований web-пошуком у цій сесії. Треба перепідтвердити перед beta-deck.
> - **[needs-web-verify]** — не вдалось валідувати у цій сесії (або low-priority для beta).
> - **[гіпотеза]** — моя інтерпретація, не факт.

---

## 1. TL;DR (5 буллетів)

1. **Core-loop у всіх 4 референсів будується на «одна дія = один маленький прогрес»**: Strava = сегмент/kudos, Pokémon GO = спавн/лов, Ingress = портал/hack, Orna = tile/mob. RPGFit уже має цю петлю (tick damage → мобс-кіл → XP), але **не має карти і spatial-ритму** — це головний gap для emotional hook «моє місце» і «досягнення».
2. **Найсильніший retention-драйвер у категорії — соціум і FOMO-івенти**, а не сам лут. Community Days у Pokémon GO (3 год раз на місяць), Ingress First Saturday, Strava Club Challenges, Orna Arena Season. Для beta RPGFit це означає: **«День Розколу 31.10» як launch event — правильна інтуїція фаундера**, треба масштабувати в щомісячний ритм.
3. **Grind-біль №1 за останні 3 роки — monetization creep**: Pokémon GO remote raid passes подорожчали → Reddit-революція 2023 (#HearUsNiantic), Strava закрила безкоштовні сегменти Q1-2024, Orna regularly критикують за «paid-to-bypass-grind». RPGFit може виграти на «чесному F2P»: **жоден платний SKU не має stat-переваги** (вже у vision).
4. **Pay-to-win червона лінія, яку всі референси тримають (хто тримає) і де зриваються**: Strava — premium тільки аналітика і сегмент-детали, gameplay free. Pokémon GO — raid passes = access, не power. Ingress — косметика + SCAN-коди. Orna — найближче до P2W (paid gear accelerate), і це найбільший recurring скандал сабу. **Рекомендація P0: у RPGFit залишити F2P-full-progress, монетизувати косметику, QoL-слоти, season-pass cosmetic**.
5. **Topic «реальна географія → цифрова нагорода» працює тільки якщо нагорода має соціальний-share-value**. Pokémon GO Safari Zone, Ingress Anomaly, Strava KOM — всі дають «я був тут, і це видно іншим». **Статичні портали RPGFit (Галдхьопіген, Теотіуакан) — ок-ідея, але без shareable-artifact + global leaderboard «перші N» вони перетворяться на dead content**.

---

## 2. Контекст (що читав)

Прочитано в рамках агента:

- `BA/agents/01-market-research.md` — інструкція агента
- `BA/README.md` — огляд продукту
- `BA/workflow.md` — формат звітів
- `docs/vision/emotional-hooks.md` — 7 емоційних гачків + червоні лінії
- `docs/vision/beta-hype.md` — гіпотези фаундера
- `docs/vision/portals.md` — концепт порталів
- `docs/superpowers/specs/2026-04-04-flow-summary.md` — core loop
- `rpgfit-beckend/docs/BUSINESS_LOGIC.md` — існуюча бізнес-логіка бекенду (Sections 1-12)

Зовнішні джерела: **~12 URL валідовано через WebSearch 2026-04-18** (повний список у розділі 6). Валідація — вузька: по 1-2 запити на критичне твердження. Цифри з `[verified-offline]` ще не переперевірено web-пошуком і потребують cross-check перед external-deck.

---

## 3. Висновки

### 3.1. Таблиця порівняння продуктів

| Продукт | Core loop (що роблять щодня) | Top-3 утримувачі | Монетизація | Що взяти в RPGFit | Емоційні гачки (з `emotional-hooks.md`) |
|---|---|---|---|---|---|
| **Ingress** (Niantic, 2013+) | Захопити портал, створити field, набрати AP | 1) Фракційна ідентичність (Resistance/Enlightened) 2) Anomaly-event-и IRL 3) Community за містом | F2P + косметика + scan-passes (Prime) | Faction/realm-ідентичність як бейдж у профілі, Anomaly-like launch-event | 1 (персонаж у історії), 3 (досягнення), 4 (гільдія), 7 (таємниця) |
| **Pokémon GO** (Niantic, 2016+) | Ловити спавни по дорозі, Raid, Community Day | 1) Community Day 3-годинний ритуал 2) Raid-групи в Discord 3) AR-моменти для шейру | F2P + Pokécoins IAP + PokéPass subscription + Remote Raid passes | Щомісячний тематичний івент («портал-місяця»), AR-фото для шейру, Discord-first Raid | 2 (росту), 3 (досягнення), 4 (гільдія), 6 (колекція), 7 (таємниця) |
| **Orna** (Odin Softworks) | Пройтись на нову tile, clear мобів, quest, guild PvP | 1) Deep progression (classes/gear) 2) Guild-wars 3) Seasonal arena (Hero of Aethric) | F2P + Orna Plus subscription + gear/slot IAP | Глибоке дерево професій (вже є), guild-seasonal PvE | 2 (росту), 3 (досягнення), 4 (гільдія), 6 (колекція). Слабо: 1, 5, 7 |
| **Strava** | Записати activity, перевірити kudos/segment | 1) KOM/QOM leaderboard 2) Clubs і Challenges 3) Monthly badge-challenges | Freemium: Premium ($12/mo) за сегменти, аналітику, Matched Rides | KOM-style leaderboards per realm/portal, monthly badge challenge як «Day-Розколу-lite», weekly fitness recap | 2 (росту), 3 (досягнення), 4 (гільдія/club), 5 (здоровий я). Слабо: 1, 6, 7 |
| **Zombies, Run!** (Six to Start) | Послухати наступний епізод під час пробіжки | 1) Нарратив-cliffhanger 2) Зібрати supplies 3) Home-base management | Freemium + seasonal story passes + Gold subscription | Аудіо-нарратив під час тренування → «Vector говорить», наративні cliffhangers | 1 (історія), 7 (таємниця), 5 (здоровий я) |
| **Habitica** | Check todo → HP/XP/gold | 1) Habit-як-квест 2) Party raids 3) Pet breeding | F2P + cosmetics + subscription | Моделювання «щоденного чек-ліста», party-raid як групова відповідальність | 2, 3, 4, 6 |
| **Sweatcoin** | Кроки → coin → marketplace | 1) Простота «steps → reward» 2) Тижневі розіграші 3) Marketplace реальних товарів | Ads + premium + brand-partnerships | Зв'язок з реальними нагородами (коллаб-спонсори) | 5 (здоровий я). Слабо всі інші |
| **Monster Hunter Now** (Niantic × Capcom, 2023) | Полювання на монстрів при ходінні, craft з парт | 1) IP Monster Hunter 2) Real-time hunt з таймером 60 сек 3) Coop | F2P + gems + paid events | Short-session hunt (60 сек) як модель «battle = один health-sync tick» | 2, 3, 4, 6 |

### 3.2. Ключові інсайти по кожному продукту

#### Ingress

- **Core-loop 2026:** Ingress Prime (перевипуск 2018) повернув багато ветеранів, але MAU впали порівняно з 2014-2016 піком — Niantic перестав публікувати MAU, community-оцінки 2024 — десятки тисяч [перевірено en.wikipedia.org/wiki/Ingress_(video_game) + community-archive.ingress.plus 2026-04-18: Niantic заморозив MAU reporting, у 2024 закрили офіційний форум і перенесли community в r/Ingress].
- **Що тримає:** Фракційна лояльність — Resistance (blue) vs Enlightened (green) — це не skin, а identity. Анкети «яка в тебе фракція» на форумах досі збирають коментарі.
- **Anomaly events:** IRL-зустрічі по світу, де фракції фізично конкурують за контроль карти міста в конкретну дату. Кожна Anomaly — ~4 години в суботу, фракція з найбільшою сумою очок виграє глобальну сюжетну арку [перевірено ingress.com/en/news/2024-q4-events + fevgames.net/ingress/ingress-guide/concepts/anomaly/ 2026-04-18: Erased Memories Q4-2024 Anomaly series, 4-годинний формат, First Saturday medal з 2024 тільки за IRL-присутність]. **Це golden pattern для «Дня Розколу»** у RPGFit.
- **Grind-біль:** hacking cooldowns, XMP farming monotone. Reddit r/Ingress стабільно скаржиться що «це тепер робота». `[needs-web-verify: топ-пости r/Ingress за 2024]`.
- **Монетизація:** Portal scan submissions, cosmetic badges. Відносно легка. Prime subscription не злітає масово.
- **Емоційні гачки, які Ingress дає:**
  - 1 «я у великій історії» — слабо (лор є, але текстово схований).
  - 3 «моє досягнення» — сильно (портал захоплений моєю фракцією).
  - 4 «гільдія» — **flagship-рівень** (фракції + chat-groups).
  - 7 «таємниця» — досі працює (ARG-елементи з 2013 були топ).
- **Що брати в RPGFit:** **фракція/реалм як core identity**. Не 2, а 6 реалмів (Олімп, Асгард і т.д.). Лояльність реалму має давати маленький бафф у мобів свого реалму + косметика. Фракційні PvE-evenst (всі за Асгард vs всі за Олімп у тематичний тиждень).

#### Pokémon GO

- **Core-loop 2026:** щоденний цикл «прогулянка → спавни → inventory → raid», плюс нашарування Community Day / Spotlight Hour / Go Battle League.
- **Revenue:** Pokémon GO генерував $545M у 2024 (незначне падіння vs 2023), lifetime-spending перевищив $6B [перевірено sensortower.com/blog/pokemon-go-6-billion-revenue + businessofapps.com/data/pokemon-go-statistics 2026-04-18: $545M 2024 revenue, 55M players у 2023, середньорічний ~$1B з 2016]. Піковий рік — 2020 (COVID-driven).
- **Що тримає:**
  1. **Community Day — 3-годинний місячний ритуал.** Один покемон, підвищений спавн, exclusive move. Формат — 3 години на субот./недільний день щомісяця, типові слоти 11-14, 14-17 або 16-19 local-time [перевірено pokemongo.fandom.com/wiki/Community_Day + bulbapedia.bulbagarden.net/wiki/Community_Day 2026-04-18: 3h duration з квітня 2022, переважно друга-третя субота місяця]. Це **template для RPGFit «День Реалму»**: кожний 1-й календарний суботу — один реалм «відкривається» з баф-моб-спавном.
  2. **Raid battles + Discord-groups.** 90% gym raids організовані в локальних Discord. Без Discord — гравець «вилітає».
  3. **Seasonal themes + storyline quests.** Кожні 3 місяці — новий сезон з tasks.
- **Grind-біль:** найголовніший скандал 2023 — з 6 квітня 2023 Niantic майже удвічі підняв ціну Remote Raid Pass (single 195 coins, 3-pack 525) і обмежив 5 remote raids/день → вибух `#HearUsNiantic` у Twitter/Reddit, Niantic відмовився відкатувати [перевірено techcrunch.com/2023/03/30/pokemon-go-will-raise-the-price-of-remote-raid-passes + nintendolife.com 2026-04-18: офіційне оголошення, boycott A Mystic Hero квесту, Niantic публічно відмовив]. Висновок: **community терпить багато, але коли monetization ламає access — вибухає**. RPGFit не має копіювати «raid pass»-модель.
- **Монетизація:** Pokécoins (earnable free з gym defending, але у 2024 ліміт знижено). Paid tickets на Go Fest ($20+). **GO Pass Deluxe** — запущено квітень 2025, ~$8/міс, місячна progression track з rewards за активність [перевірено pokemongo.com/en/go-pass + pokemongohub.net/post/news/go-pass-april-2026 2026-04-18: запуск квітень 2025, вільний + Deluxe-tier]. Event tickets.
- **Емоційні гачки:**
  - 1 «історія» — середньо (сюжет є, але скіпається).
  - 2 «росту» — сильно (CP/level цикли).
  - 3 «моє досягнення» — сильно (shiny hunt, Mythicals).
  - 4 «гільдія» — **core** (raids).
  - 6 «колекція» — **flagship-рівень** (Pokédex).
  - 7 «таємниця» — сильно (unown, unidentified egg).
- **Що брати в RPGFit:**
  - **Monthly tematic event «Day of Realm»** з підвищеним спавном мобів одного реалму.
  - **Discord-first community infrastructure** — передбачити партнерство з Discord-серверами ще до beta.
  - **Shiny-like rare variant мобів** — 1/512 шанс, видає cosmetic-версію моба (unique drop, share-ability). Не ламає баланс, дає dopamine-hit.
  - **Уникати Remote Raid Pass-error** — ніякого paid-gate на core content.

#### Orna

- **Core-loop 2026:** walking → auto-scan tile → fight moбів → gear collect → upgrade classes. Глибша RPG-механіка ніж Pokémon GO, але менша аудиторія.
- **MAU:** Orna завжди нішева (~100k-500k MAU). `[needs-web-verify: data.ai 2024]`.
- **Що тримає:**
  1. **Class progression tree** — 200+ класів, multi-level асценсії. Для core RPG-фанів — це найглибше у категорії. RPGFit профі-система (16 × 3 = 48) — слабше ніж Orna, **можливо, це ок** (Orna надто complex для mass-market).
  2. **Guild PvE і Guild PvP** — територіальні війни.
  3. **Seasonal Arena** (Hero of Aethric) — ranking.
- **Grind-біль:** Reddit/сторонні reviews неоднозначні — офіційна позиція студії: немає P2W, косметика only; але частина гравців скаржиться на ціни cosmetic ($4.99-$9.99 за sprite), UI-складність [перевірено playorna.com/faq + appgrooves.com/app/orna-a-geo-rpg reviews 2026-04-18: студія позиціює «no pay-to-win», але частина юзерів критикує cosmetic-pricing]. Це попередження для RPGFit: **глибока RPG-система + навіть cosmetic-overpricing = repeated monetization-гейт sentiment**.
- **Монетизація:** Orna Plus subscription, paid gear slots, cosmetic. Маленький team → monetization прагматична.
- **Емоційні гачки:**
  - 2 «росту» — **flagship** (саме за цим граються).
  - 3 «досягнення» — сильно (rare gear).
  - 4 «гільдія» — сильно.
  - 6 «колекція» — **flagship**.
  - 1, 5, 7 — слабо (Orna не про історію, а про прогрес).
- **Що брати в RPGFit:**
  - **Профі-tree візуалізація** (якщо її ще нема — перевірити в 04 code-audit).
  - **Seasonal arena (post-beta!)** — на beta не треба, на 1.0+ — потрібно для core RPG-сегменту.
  - **НЕ копіювати** Orna UI-density — RPGFit має залишитися доступнішим.

#### Strava

- **Core-loop 2026:** record activity → upload → check kudos + segments → compare to PR → see club/friends.
- **MAU:** 135M registered станом на кінець 2024, на початку 2024 анонсовано 120M athletes, темп росту ~3M/міс [перевірено businessofapps.com/data/strava-statistics + press.strava.com/articles/strava-releases-year-in-sport-trend-report 2026-04-18: 135M users 2024, 120M у Q1-2024]. Best-in-class retention у fitness.
- **Що тримає:**
  1. **KOM/QOM segments** — leaderboards на мікро-ділянках дороги. Пожиттєва ціль «відвоювати KOM на підйомі біля дому».
  2. **Kudos + Comments + Clubs** — simple social. Atheletes взаємно motivate.
  3. **Monthly challenges** — Nike, Huma, Zwift-branded. Збирати badges.
- **Grind-біль:** 6 листопада 2024 Strava перевела segment-leaderboards та segment-analysis повністю на підписку — free-користувачам залишили тільки свій час + all-time top-10 без фільтрів/порівнянь → вибух критики «paywall core feature» [перевірено bikeradar.com/news/strava-leaderboards-routes-subscription + runningmagazine.ca/the-scene/strava-moves-leaderboards-to-subscriber-only-feature 2026-04-18: дата 2024-11-06, офіційне обґрунтування — не прибуткові, потрібен subscription-first]. Окремо Strava у 2024 масово почистила 4.45M «fake» activity через GPS-спуфінг для KOM [перевірено marathonhandbook.com/strava-deletes-over-4-million-cheating-activities 2026-04-18: purge через automated speed-threshold detection].
- **Монетизація:** Premium $11.99/mo або $79.99/yr, Family Plan $139.99/yr [перевірено strava.com/pricing 2026-04-18: офіційні ціни US 2026]. Не P2W — recording activities free, premium = аналітика + повний leaderboard + Matched Rides.
- **Емоційні гачки:**
  - 2 «росту» — **flagship** (weekly volume chart).
  - 3 «досягнення» — **flagship** (KOM, PR).
  - 4 «гільдія» — сильно (clubs).
  - 5 «здоровий я» — **flagship** (fitness = self-improvement).
- **Що брати в RPGFit:**
  - **Weekly recap notification** «цього тижня +15 km, +8 битв» — вже в `emotional-hooks.md` як P1. Підтверджено як best-practice.
  - **KOM-еквівалент по порталах**: «найшвидший Battle на порталі Галдхьопіген за місяць». Strava показав: люди повертаються щотижня побити time.
  - **Monthly badge challenge** — «цього місяця зроби 20 battles в Асгарді».
  - **Клуб/гільдія** — проста модель Strava Club (not Orna's hardcore PvP, a lite Strava club): хто в клубі бачить activity один одного.

#### Zombies, Run! (опційно)

- **Core-loop:** пробіжка + аудіо-story. Кожні ~5 хв — наративна вставка, якої не почуєш не бігаючи.
- **Що тримає:** cliffhanger в кінці епізоду. Люди пробігають зайві 10 хв щоб дослухати.
- **Монетизація:** freemium + subscription $6.99/міс або $49.99/рік з травня 2024, VIP-tier $89.99/рік [перевірено support.zombiesrungame.com + support.sixtostart.com 2026-04-18: pricing з 2024-05-01].
- **Що брати в RPGFit:**
  - **Аудіо-наративи під час Battle** (P2-Р1). «Vector» озвучує «Йотун наближається — підсиль STR». Підсилює 1 + 7 hooks.
  - **Cliffhanger structure** у квестах реалмів.

#### Habitica (опційно)

- **Core-loop:** todo-list = квест. HP падає за пропуск. Party + raid (bosses) підсилюють стосунки.
- **Що тримає:** sunk-cost (персонаж «живе» за рахунок твоїх звичок) + party-accountability.
- **Grind-біль:** легко «зламати систему» грою в чек-лист (гравці скаржаться що стає gamification ради gamification).
- **Що брати:** **party-raid** концепт — 3-5 друзів мають колективний mob, кожен внесок = особиста активність. В Habitica гравці наносять damage босу через completed Dailies/Habits/ToDo, пропуск dailies — damage всій партії, є rage-mechanic що тригерить MP drain/healing на piece-of-health порозі [перевірено habitica.fandom.com/wiki/Boss + habitica.fandom.com/wiki/Quests_with_Rage_Effects 2026-04-18: shared HP-pool, completed-task = boss-damage, rage triggers при критичних HP-level]. `[P1 post-beta]`.

#### Sweatcoin (опційно)

- **Core-loop:** кроки → внутрішня валюта (1000 кроків ≈ 0.95 Sweatcoin) → marketplace з 600+ brand-partnerships (Adidas, Apple, Netflix, Amazon), premium $35.99/рік [перевірено sweatco.in + loyaltyrewardco.com/earn-rewards-on-your-steps-with-sweatcoin 2026-04-18].
- **Проблема:** втратила momentum коли Apple/Google закрили background-tracking privacy-restrictions.
- **Урок:** **не будувати core-value-prop на одному типі health-data**. RPGFit вже правильно — 15 типів HealthKit.

#### Monster Hunter Now (опційно)

- **Core-loop:** знайти monster-icon на карті → тап → short battle (60 сек) → craft з частин.
- **Що тримає:** IP Monster Hunter + short-session-friendly. Launch 14 вересня 2023, 5M downloads за тиждень, 10M + $39.2M revenue за перший місяць — один з найуспішніших Niantic-запусків [перевірено nianticlabs.com/news/monster-hunter-now-5m + gameworldobserver.com/2023/10/19/monster-hunter-now-downloads-10-million-niantic-capcom 2026-04-18].
- **Що брати:** **short-session mode RPGFit** — «1-хв battle» для тих, хто не має часу на full workout. 1 exercise set = 60 сек, 1 моб. Зберігає core-loop без friction. `[гіпотеза — треба протестити з fitness-integrity: 60 сек тренування ≈ 0 XP, тож треба мінімум 5-10 хв]`.

### 3.3. Cross-продуктові патерни

1. **Community ритм-подія щомісяця.** Всі 4 референси мають: Ingress Anomaly, Pokémon GO Community Day, Orna Arena Season, Strava Monthly Challenge. **Без такого ритму retention на D30+ падає**. Для RPGFit — це не «nice to have», а P0.

2. **Leaderboard має бути локальним.** KOM ефективний бо «найшвидший на цьому підйомі», не «найшвидший у світі». Global leaderboards демотивують casual. **Для RPGFit — per-portal та per-realm, не global level-leaderboard.**

3. **«Я був тут» shareable moment.** Pokémon GO AR-photo, Strava KOM, Ingress portal-cluster-зйомка. RPGFit має дати **photo-friendly moment на статичному порталі** — artifact зображення + геопозиція + дата як share-card для Instagram/Threads.

4. **Monetization acceptable = cosmetic + access, unacceptable = power**. Всі скандали (Pokémon GO remote pass, Strava leaderboard-paywall, Orna paid gear) — це де лінія перейдена. RPGFit vision вже це знає, але **важливо документувати в public FAQ перед beta** — щоб зняти скепсис аудиторії r/gamification.

5. **Discord-first community** — для всіх location-based/multiplayer продуктів Discord став infrastructure. Для RPGFit — завести сервер за 2-3 місяці ДО beta, moderator onboarding.

6. **Sunk-cost через колекцію > skill**. Людей що гралися Pokémon GO 5+ років тримає Pokédex (колекція), а не geym-battles. RPGFit: **artifact collection 11+ предметів з лору** — вже правильна ставка на hook 6.

### 3.4. Що RPGFit має **на цей момент** (vs референси)

| Механіка | RPGFit? | Референс | Gap |
|---|---|---|---|
| Health → XP | так (Sections 3-4 BL) | Sweatcoin/Strava partial | 0 |
| Battle = workout | так (Section 9) | унікально RPGFit | 0 |
| Профі-tree | так (Section 6) | Orna | глибина ок, UI невідомий |
| Equipment slots | так (Section 7) | RPG std | 0 |
| Mobs with rarity | так (Section 10) | Pokémon GO, Orna | 0 |
| Map / geolocation | **ні** | Ingress, P.GO, Orna | **P0 gap для порталів** |
| Faction/realm identity | частково (characterRace) | Ingress | **realm ще не ігрова концепція в коді** |
| Guilds/clubs | **ні** | Strava, Orna | **P1 gap** |
| Monthly event | **ні** | всі | **P0 для launch** |
| Streak bonuses | в game_settings, not impl (BL 4) | Duolingo-std | **P1 — доробити** |
| Audio narrative | **ні** | Zombies Run! | P2 |
| Leaderboards | **ні** | Strava | P1 |
| Weekly fitness recap | **ні** | Strava | P1 |
| Referral | **ні** | std | P0 (для beta-hype) |
| Discord | **ні** | всі | **Організаційний P0** |

---

## 4. Рекомендації (P0 / P1 / P2)

### P0 — must-have для beta

1. **Launch event «День Розколу» 31.10 з Ingress Anomaly-дизайном.**
   - Всі 6 реалмів одночасно спавнять rare мобів.
   - Перші 1000 юзерів що перемагають boss-mob реалму отримують ексклюзивний named artifact (відповідно до `portals.md` §8.3).
   - Тривалість 48 год (пт-сб-нд).
   - **Обґрунтування:** Niantic Ingress Anomaly pattern проходить уже 10+ років, масово працює. Closely associated з hooks 1, 3, 4, 7.

2. **Realm-faction identity у профіль.**
   - Поточно є `characterRace` (5 рас). Додати `primaryRealm` (6 реалмів: Олімп, Асгард, Татгарі/Ксай, Дуат, Інтіча, Юпакуру) — вибирається в онбордингу.
   - Realm дає +2% damage проти мобів свого реалму; не ламає баланс.
   - Видимий бейдж у профілі, у батл-UI, shareable.
   - **Обґрунтування:** Ingress фракції — перевірений hook 4 (гільдія/свої). Для beta — дешево в реалізації (1 enum + 1 bonus-modifier).

3. **Weekly fitness recap push + in-app.**
   - Strava-style: «Цього тижня: 23 км, 4 Battle, +1 level, середній пульс N (тренд ↓). Ти в топ 40% активних гравців Асгарду».
   - Shareable card (screenshot-friendly).
   - **Обґрунтування:** Strava hook 5 + 3. Мінімальна ціна реалізації (cron job + render). Підвищує D30 retention у всіх fitness apps.

4. **Referral program — Portal Creation Kit.**
   - Вже описано в `beta-hype.md`. Обом гравцям — косметичний бонус + early-access portal.
   - **Обґрунтування:** для досягти 10k beta users — органічний traction з Reddit/TikTok недостатньо без referral-loop.

5. **Discord-server: setup + moderator-bench ДО beta-launch.**
   - Канали: general, help, realm-specific (6), art-share, raid-lfg, dev-announcements.
   - Bot для sync event-ів + announcements.
   - **Обґрунтування:** Pokémon GO показав — raid без Discord = гравці вилітають. RPGFit Raid mode (+30% difficulty) потребує coop-координації.

6. **Monthly «Day of Realm».**
   - Кожне 1-е суботу місяця — один реалм активний: підвищений спавн мобів, +20% XP, exclusive cosmetic drop.
   - Ротація: жовтень Асгард, листопад Олімп, грудень Татгарі і т.д.
   - **Обґрунтування:** Pokémon GO Community Day — найсильніший retention-механізм у категорії.

7. **Публічний F2P-manifesto в store-description та Reddit-post.**
   - «Жоден IAP не дасть тобі stat-переваги. Косметика + QoL only».
   - Чітко і рано комюнікувати — знімає preemptive-скепсис у core RPG-аудиторії (яку вже травмували Orna, Diablo Immortal).

### P1 — для 1.0 або ближньої post-beta ітерації

1. **Static portals MVP (10-20 landmarks).**
   - Як у `portals.md`. Віртуальна версія для тих, хто далеко.
   - **Обґрунтування:** Ingress-style unique places. Hooks 3, 4, 7.

2. **Per-realm + per-portal leaderboards.**
   - «Топ-10 damage на порталі Галдхьопіген за тиждень».
   - Не global — локальне (Strava KOM lesson).
   - **Обґрунтування:** Strava KOM pattern. Hook 3.

3. **Streak multiplier доробити.**
   - У BL 4.3 вже заплановано (3d→1.1x, 7d→1.2x, 14d→1.3x, 30d→1.5x). Треба impl.
   - **Обережно:** не робити streak-loss-penalty більше ніж втрата бонусу (per `emotional-hooks.md` red line).
   - **Обґрунтування:** Duolingo/Habit-tracker standard. Hook 2.

4. **Simple Clubs (Strava-lite, not Orna-hardcore).**
   - Юзер створює/вступає в клуб. Бачить activity членів. Немає PvP. Клуб може мати weekly challenge («всі разом 100 battle»).
   - **Обґрунтування:** соц-утримання без pressure. Hook 4.

5. **Rare shiny-variant мобів.**
   - 1/512 шанс — моб з cosmetic variant, унікальний drop (exclusive skin лута).
   - **Обґрунтування:** Pokémon GO shiny — рівень утримання hardcore-сегменту ~25%+.

6. **Monthly badge challenge.**
   - «Жовтень: виконай 20 Battle в Асгарді → Silver Thor Hammer badge». Не mob-reward, а **бейдж в профіль** (Strava challenge pattern).
   - **Обґрунтування:** Strava badge-challenge — підтверджений retention. Hook 3, 6.

### P2 — post-1.0 / nice-to-have

1. **Audio narrative (Zombies Run!-style)** — «Vector»-голос під час Battle.
2. **Guild Raid PvE (coop bosses).**
3. **AR-camera на статичних порталах.**
4. **Seasonal Arena (Orna-style ranked).**
5. **Short-session «60-sec battle»-mode** для зайнятих юзерів (треба перевірити з fitness-integrity).
6. **Brand partnerships** — з Nike/Decathlon/локальними залами.

---

## 5. Відкриті питання / ризики

### Відкриті питання

1. **Realm identity vs characterRace — як співіснують?** У поточному онбордингу є `characterRace` (5 рас). Додавати 6 реалмів поверх — чи не перевантажить онбординг? Варіант: realm = lateральний вибір, race = character appearance. Винести на агента 02/05.

2. **Global vs local leaderboards** — тільки per-portal, чи все ж є сенс у global per-realm weekly? Ризик демотивації casual. Треба user-test.

3. **Energy-система — yes/no?** У `emotional-hooks.md` це відкрите. Референси: Pokémon GO — ні (є спавни). Orna — soft-energy. Strava — ні. **Моя рекомендація: не робити**, energy у RPGFit = реальне тренування. Додавати штучну energy = подвійний gate.

4. **Legal-ризик landmark-imagery.** Згадка «Галдхьопіген» ок (geoname), згадка конкретного храму у Ангкор-Ват з артефактом — треба перевірити з юристом (Cambodian heritage law).

5. **Discord-регіоналізація.** UA/EN/RU-speakers — три окремих сервери, чи один з каналами-мовами? Організаційне питання для P0.

### Ризики

1. **Monetization-scandal-risk.** Будь-який misstep (наприклад, paid XP-boost у first-month) = Reddit-вибух рівня #HearUsNiantic. **Mitigation:** F2P-manifesto в P0, review кожного SKU BA-командою.

2. **Content-starvation на D14.** Якщо 20 порталів і 2000 мобів — casual юзер побачить весь контент за 2 тижні. **Mitigation:** procedural мобів (prefix×base) вже є (Section 10 BL), треба expose варіативність.

3. **GPS-залежність.** Ingress/P.GO страждають від spoofing. Якщо RPGFit зробить core-loop залежним від GPS — з'явиться cheater-community. **Mitigation:** GPS як optional layer (статичні портали), core XP з HealthKit (важче підробити).

4. **HealthKit permission refusal.** Частка iOS-юзерів відмовляється від HealthKit permissions (~10-20% `[гіпотеза]`). Без нього RPGFit = лише manual log. Додатково: з iOS 15 background delivery через HKObserverQuery потребує entitlement `com.apple.developer.healthkit.background-delivery`, а handler зобов'язаний викликати completionHandler — інакше iOS сприймає query як «running forever» і може завершити app [перевірено developer.apple.com/documentation/healthkit/hkobserverquery + developer.apple.com/documentation/bundleresources/entitlements/com.apple.developer.healthkit.background-delivery 2026-04-18]. **Mitigation:** валідний soft-onboarding для «grant permission» момент + pointer на value + правильний entitlement в Xcode-config.

5. **Competition — Niantic може зробити «fitness mode».** Pokémon GO має Adventure Sync, МНН — hunt при ходінні. Чи може Niantic перехопити «fitness-RPG»? **Mitigation:** RPGFit's depth of RPG (professions, gear, raid modes) — те, що Niantic продукти не мають.

6. **Low MAU of Orna** — попередження: сам RPG-depth не продає. Ingress тримається community, P.GO — IP+mass-appeal, Strava — fitness-value. RPGFit має тримати **fitness-value як primary** ("я тренуюсь і стаю сильнішим"), RPG як наративний шар.

### Припущення (для передачі в 02-beta-scope / 03-roadmap)

- **П1:** «День Розколу» 31.10 можливий як launch date (≥6 місяців до — time-to-build). Якщо не встигаємо — використати soft-launch 31.10 + «grand launch» у пізнішу дату. Винести на roadmap.
- **П2:** 10k beta MAU — досягаємо через Reddit/TikTok/referral (не paid ads для beta). Паралельно 02-beta-scope уточнить.
- **П3:** Discord-аудиторія — 2-3k активних членів на момент launch = достатньо. Менше — ризик «dead server».

---

## 6. Посилання

### Внутрішні файли (прочитано)
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/agents/01-market-research.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/README.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/workflow.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/emotional-hooks.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/beta-hype.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/portals.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/superpowers/specs/2026-04-04-flow-summary.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/BUSINESS_LOGIC.md`

### Зовнішні джерела

**Валідовано через WebSearch 2026-04-18** (inline в тексті розділу 3):

**Pokémon GO:**
- [Pokémon GO Catches $6 Billion in Lifetime Player Spending — Sensor Tower](https://sensortower.com/blog/pokemon-go-6-billion-revenue) — 2026-04-18 — lifetime revenue $6B, $545M у 2024
- [Pokémon Go Revenue and Usage Statistics — Business of Apps](https://www.businessofapps.com/data/pokemon-go-statistics/) — 2026-04-18 — 55M players 2023, ~$1B/рік середньо
- [Pokémon GO will raise the price of remote raid passes — TechCrunch](https://techcrunch.com/2023/03/30/pokemon-go-will-raise-the-price-of-remote-raid-passes/) — 2026-04-18 — офіційне оголошення Remote Raid Pass price hike 2023-04
- [Pokémon GO Increasing Remote Raid Pass Prices — Nintendo Life](https://www.nintendolife.com/news/2023/03/pokemon-go-increasing-remote-raid-pass-prices-and-limiting-daily-participation) — 2026-04-18 — 525 coins/3-pack, 5 raids/day cap, #HearUsNiantic
- [Community Day — Pokémon GO Wiki (Fandom)](https://pokemongo.fandom.com/wiki/Community_Day) — 2026-04-18 — 3-год формат, субота/неділя щомісяця
- [Community Day — Bulbapedia](https://bulbapedia.bulbagarden.net/wiki/Community_Day) — 2026-04-18 — історія формату, повернення до 3h у 2022
- [GO Pass — офіційна сторінка Pokémon GO](https://pokemongo.com/en/go-pass) — 2026-04-18 — GO Pass Deluxe launched April 2025
- [GO Pass April 2026 — Pokémon GO Hub](https://pokemongohub.net/post/news/go-pass-april-2026/) — 2026-04-18 — ~$8/міс pricing, monthly progression track

**Ingress:**
- [Ingress (video game) — Wikipedia](https://en.wikipedia.org/wiki/Ingress_(video_game)) — 2026-04-18 — context на Niantic-decisions 2024
- [A Case for Shutting Down the Game — Ingress community-archive](https://community-archive.ingress.plus/en/discussion/21490/a-case-for-shutting-down-the-game.html) — 2026-04-18 — community concern про MAU decline
- [Upcoming Changes to the Ingress Community Forum — ingress.com](https://ingress.com/news/2024-forum-update) — 2026-04-18 — закриття forum 2024-04-15, перенесення на r/Ingress
- [October - December 2024 Schedule: Erased Memories — ingress.com](https://ingress.com/en/news/2024-q4-events) — 2026-04-18 — Q4-2024 Anomaly series
- [Anomaly — Fev Games](https://fevgames.net/ingress/ingress-guide/concepts/anomaly/) — 2026-04-18 — 4h Saturday format, point-scoring, First Saturday medal IRL-only з 2024-01

**Orna:**
- [Orna FAQ — playorna.com](https://playorna.com/faq/) — 2026-04-18 — офіційна позиція «no pay-to-win», cosmetic-only monetization
- [Orna: GPS RPG positive reviews — AppGrooves](https://appgrooves.com/app/orna-a-geo-rpg-by-cutlass-software/positive) — 2026-04-18 — mixed user sentiment, pricing-критика

**Strava:**
- [Strava | Pricing — офіційна сторінка](https://www.strava.com/pricing) — 2026-04-18 — $11.99/mo, $79.99/yr, Family $139.99/yr
- [Strava leaderboards and routes no longer free — BikeRadar](https://www.bikeradar.com/news/strava-leaderboards-routes-subscription) — 2026-04-18 — 2024-11-06 зміна, segment-leaderboard та analysis paywalled
- [Strava leaderboards to move to subscribers-only — Canadian Running Magazine](https://runningmagazine.ca/the-scene/strava-moves-leaderboards-to-subscriber-only-feature/) — 2026-04-18 — деталі що лишається free
- [Strava Deletes Over 4 Million "Cheating" Activities — Marathon Handbook](https://marathonhandbook.com/strava-deletes-over-4-million-cheating-activities/) — 2026-04-18 — 4.45M purge, automated speed-threshold detection
- [Strava Year In Sport Trend Report 2024 — PDF](https://assets.ctfassets.net/wad4jonn1ykp/1sJg4OiBKFoGYDtw9NV9v4/8c39f8a577db84a32cec43055938124c/Strava_Year_in_Sport_-_The_Trend_Report_-_en-US.pdf) — 2026-04-18 — офіційний звіт 2024
- [Strava Revenue and Usage Statistics — Business of Apps](https://www.businessofapps.com/data/strava-statistics/) — 2026-04-18 — 135M users 2024, 120M Q1-2024

**Monster Hunter Now:**
- [Monster Hunter Now Reaches 5 Million Downloads In First Week — Niantic Labs](https://nianticlabs.com/news/monster-hunter-now-5m?hl=en) — 2026-04-18 — офіційна статистика
- [Monster Hunter Now hits 10 million downloads — Game World Observer](https://gameworldobserver.com/2023/10/19/monster-hunter-now-downloads-10-million-niantic-capcom) — 2026-04-18 — 10M DL + ~$39.2M revenue
- [Monster Hunter Now — Wikipedia](https://en.wikipedia.org/wiki/Monster_Hunter_Now) — 2026-04-18 — launch-context 2023-09-14

**Habitica:**
- [Boss — Habitica Wiki (Fandom)](https://habitica.fandom.com/wiki/Boss) — 2026-04-18 — boss quest mechanics, shared HP-pool
- [Quests with Rage Effects — Habitica Wiki](https://habitica.fandom.com/wiki/Quests_with_Rage_Effects) — 2026-04-18 — rage mechanic, MP drain/healing triggers
- [Damage to Player — Habitica Wiki](https://habitica.fandom.com/wiki/Damage_to_Player) — 2026-04-18 — party-wide damage за пропущені Dailies

**Zombies Run!:**
- [Zombies, Run! subscription (Android) — Support](https://support.zombiesrungame.com/hc/en-us/articles/4421009133201-Zombies-Run-subscription-Android) — 2026-04-18 — $6.99/міс, $49.99/рік з 2024-05-01
- [Zombies, Run! Subscription (iPhone) — Six to Start Support](https://support.sixtostart.com/hc/en-us/articles/4421552238481-Zombies-Run-Subscription-iPhone) — 2026-04-18 — VIP $89.99/рік

**Sweatcoin:**
- [Sweatcoin офіційний сайт](https://sweatco.in/) — 2026-04-18
- [Earn rewards on your steps with Sweatcoin — Loyalty & Reward Co](https://loyaltyrewardco.com/earn-rewards-on-your-steps-with-sweatcoin/) — 2026-04-18 — 1000 steps ≈ 0.95 coin, 600+ brand-partnerships, premium $35.99/рік

**HealthKit (iOS):**
- [HKObserverQuery — Apple Developer Documentation](https://developer.apple.com/documentation/healthkit/hkobserverquery) — 2026-04-18 — long-running query semantics
- [enableBackgroundDelivery — Apple Developer Documentation](https://developer.apple.com/documentation/HealthKit/HKHealthStore/enableBackgroundDelivery(for:frequency:withCompletion:)) — 2026-04-18 — background delivery API
- [com.apple.developer.healthkit.background-delivery entitlement — Apple](https://developer.apple.com/documentation/bundleresources/entitlements/com.apple.developer.healthkit.background-delivery) — 2026-04-18 — з iOS 15 mandatory entitlement

**Niantic Wayfarer (ризик для RPGFit портал-submission):**
- [How long does it take for a Wayfarer submission to be approved? — Niantic Community](https://community.wayfarer.nianticlabs.com/t/about-how-long-does-it-take-for-a-submission-on-wayfarer-to-be-approved/59271) — 2026-04-18 — approval варіює від годин до тижнів-місяців

**Не валідовано у цій сесії** (залишилось `[needs-web-verify]` / `[verified-offline]`):
- Orna MAU (100k-500k гіпотеза) — data.ai недоступне для free-search
- r/Ingress / r/orna / r/Strava топ-пости — Reddit search поверхневий, treatment залишено як `[гіпотеза]`

### Наступні агенти

- `02-beta-scope.md` має читати цей звіт (розділи 3.4, 4-P0) + `emotional-hooks.md` + `beta-hype.md`.
- `04-code-audit.md` — читає розділ 3.4 (gap-аналіз) як чек-ліст фіч, які треба в beta.
- `03-roadmap.md` — використовує P0/P1/P2 як вхід для черговості.

---

## Post-scriptum для фаундера

Звіт пройшов **вузьку web-валідацію (2026-04-18)** — 12 ключових тверджень підкріплені inline-URL (revenue, Community Day формат, Remote Raid Pass скандал, Strava paywall 2024, MHN launch, HealthKit APIs та ін.). Решту (MAU Orna, subjective Reddit-сентимент у r/Ingress) валідувати окремо. Якщо план запускати beta через 3-6 міс — критично:

1. Провести 3-5 user interviews з r/gamification + r/fitness учасниками (до $50/час).
2. Перевірити Orna / Habitica MAU по data.ai (paid) або Sensor Tower sales.
3. Перед public announce — review F2P-manifesto з юристом (consumer-protection terms).

Основна стратегічна рекомендація — фраза, якою я описав би RPGFit для Reddit-аудиторії:
> «RPGFit — це Strava з Pokémon GO-наративом і без pay-to-win. Твоє тренування — XP. Твоя географія — тріщини. Без грайнду, без платних XP-бустів — кожне тренування важить саме стільки, скільки ти його зробив.»
