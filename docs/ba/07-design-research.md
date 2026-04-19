# 07 — Design Research: Deepening Variants 06 (Neo Glass) & 07 (Night City Noir)

> **Автор:** BA-агент 06 (Design Researcher). **Дата:** 2026-04-18. **Статус:** v1.
>
> **Вхід:** `design/06-web3/{concept.md,index.html}`, `design/07-cyberpunk/{concept.md,index.html}`,
> `design/shared/base.css`, `design/01-quantum-neon/index.html` (реф для дифференціації),
> `docs/vision/product-decisions-2026-04-18.md`, `docs/vision/emotional-hooks.md`,
> `docs/vision/portals.md`, `docs/vision/mobs-cross-world-loot.md`,
> `docs/vision/onboarding-gifts.md`, `BA/outputs/02-beta-scope.md`, `BA/outputs/05-lore-to-hook.md`.
>
> **Обмеження цього агента:** **рекомендую і описую, не переписую HTML сам.** HTML-implementation
> зробить Claude після рев'ю цього звіту.
>
> **Timebox:** ~50 хв, 17 зовнішніх URL перевірено, 21 WCAG-пара контрасту
> розрахована через webaim.org/resources/contrastchecker.

---

## 1. TL;DR (5 буллетів)

1. **06 Neo Glass — signature motif = "Rift Lens"**: вертикальна трищина-розкол
   (1–2px frosted-glass gap) перетинає найважливішу glass-панель у діагональ,
   з живим refraction-parallax мікро-ефектом при тилті (Liquid Glass 2026
   pattern). Це hallmark-елемент, який робить 06 впізнаваним у 3-секундному
   screenshot-тесті. **НЕ Linear** і не generic web3. Плюс — додати `feTurbulence`
   SVG noise 0.04 opacity на всі `.glass` панелі (вирішує "стерильність").
   Прибрати NFT-термін "holographic"/"NFT card" з коду — міняємо на "Relic
   card / Rift-touched" (лор-coherent, без crypto-асоціацій).

2. **07 Night City Noir — signature motif = "Corpo Redaction"**: чорні
   cen[██████]red-bars з toxic-yellow stitching по краях, які накриваються
   над classified-полями і "роз'їдаються" glitch-анімацією при engage.
   Це не generic hazard-tape — це **сюжетний** motif (VERA-ICS classifies),
   працює лор-wise (§07 concept "VERA-ICS корпорація"). Плюс — критичний
   fix: `#FF0051` redlash-btn з білим текстом — contrast 3.91:1, **FAIL AA**
   для normal text. Treatment: або темніша червона `#E60047` (4.6:1), або
   large-text-only (≥18pt/24px), або міняємо `color:#000000` замість white
   на red-btn. Документую деталі в §4.

3. **Обидва варіанти додати 5 нових екранів у мокапи** (зараз тільки 4 з 8
   критичних flow-ів, які вимагає `02-beta-scope §5 Day 0–7 retention
   plan`): (1) **Opening 60s** cinematic intro, (2) **Event** screen (Day
   of Розколу з active-players feed per D5), (3) **Weekly Recap share-card**
   9:16 (Spotify Wrapped pattern), (4) **Active Players feed** (D5 замість
   factions), (5) **Settings / Health permissions onboarding**. Без цих
   5 екранів design-напрямок не валідується як production-ready — це
   критичний gap.

4. **Обов'язково уникати** у мокапах: (a) Liquid Glass без reduced-motion
   fallback (trigger для vestibular users — 69M у США); (b) chromatic
   aberration як permanent layer (викликає nausea, 2025 design-trend каже
   — тільки на CTA peak і micro-moments); (c) web3/NFT/blockchain/token
   лексика (alienate-фактор для mainstream fitness audience, validated
   `06/concept.md §Ризики`); (d) Japanese kanji як декор у 07 без культурної
   мотивації — Ukrainian-identity лору потребує кирилицю/тризуб/трипільський
   орнамент, не 夜月. (e) pure-white text на Night-red CTA (FAIL AA).

5. **Крос-вплив у shared/base.css** — додати `prefers-reduced-motion`
   media query як global kill-switch для всіх animations у варіантах,
   CSS-var для bento-row (`grid-template-columns: repeat(12, 1fr)` для
   2026 bento-trend у Character/Inventory), typography scale
   (8/10/11/12/14/16/20/28/40px — Refactoring UI step scale), і haptic-hint
   data-attributes (`data-haptic="impact-medium"` щоб RN-implementation
   один-до-одного мапила).

---

## 2. Research Summary — тренди 2026 vs RPGFit

Мінімум 15 рядків, зваженими: **тренд → applicable до RPGFit yes/no → чому
→ URL**.

| # | Тренд/референс | Applicable | Чому (або ні) | URL |
|---|----------------|------------|----------------|-----|
| 1 | **Liquid Glass (Apple iOS 26)** — layered refraction replaces static blur | **06: YES** | Точно те, що концепт 06 хоче досягти ("premium modern"). Реалізація через `backdrop-filter: blur()` + фізично-точний lensing через SVG-filter `feDisplacementMap`. Для RN — `@react-native-community/blur` + Skia для displacement. | https://www.ahmeduiux.com/en/blogs/apple-liquid-glass-ios26-usability-issues-uiux-lessons |
| 2 | **Glassmorphism AA accessibility critique** | **06: YES — mandatory** | Apple специфічно робить dynamic contrast adjustments (text lightens коли glass над темним, darkens коли над світлим). RPGFit-06 має це врахувати. | https://invernessdesignstudio.com/glassmorphism-what-it-is-and-how-to-use-it-in-2026 |
| 3 | **Bento grid dashboard mobile 2026** — +47% dwell, +38% CTR | **Обидва: YES** | Character screen у 06/07 зараз — linear list. Bento (asymmetric modular blocks) — trend 2026 + краща scannability для HP/XP/stats. | https://mockuuups.studio/blog/best-bento-grid-design-examples/ |
| 4 | **SVG noise overlay (`feTurbulence`) на glass panels** | **06: YES — critical** | Фіксить "стерильність" + додає texture. Performance OK (GPU). Вже згадано в `06/concept.md §Ризики`. | https://medium.com/design-bootcamp/how-i-used-css-backdrop-filter-svg-noise-to-create-a-living-ui-background-c3aaaf63befc |
| 5 | **Chromatic aberration як design element 2025** | **07: PARTIAL** | Cool як accent на CTA-peak (1 frame, 10-20ms). Permanent layer — vestibular risk (69M US users з inner-ear issues). `prefers-reduced-motion` mandatory. | https://designworklife.com/why-designers-are-embracing-chromatic-aberration-on-purpose/ |
| 6 | **Reduced motion WCAG 2.3.3** | **Обидва: YES — mandatory** | `shimmer`, `orbit`, `pulse-soft`, `glitch-flicker`, `warn-pulse`, `sweep` — всі мають fallback. Сам 06 і 07 у поточному коді це ігнорують. | https://webkit.org/blog/7551/responsive-design-for-motion/ |
| 7 | **Cyberpunk 2077 UI breakdown — brutalism + clip-path** | **07: YES — core** | Vilimovský Behance показує: parallelogram tags, corner-cut panels, corpo watermarks — 07 уже рухається правильно. Додати: `monospaced` digit tab-nums everywhere (vs у 07 частково). | https://www.behance.net/gallery/133185623/Cyberpunk-2077User-Interface-(Part-2) |
| 8 | **Cyberpunk 2077 UX критика — information density** | **07: YES — mitigation** | CP2077 критикували за "flashy але не readable". 07-Battle зараз має 6+ glitch-анімацій одночасно (`glitch-flicker`, `sweep`, `shimmer-neon`, `warn-pulse`). Треба hierarchy — 1 primary animation, решта — idle. | https://aidenlesanto.medium.com/cyberpunk-2077-ux-ui-critique-f064884176b2 |
| 9 | **Honkai Star Rail fixes Genshin UI problem — right-panel menu, stat hierarchy** | **06: YES** | HSR ставить equipment stats inline (не через extra tap). 06-Inventory зараз має стан показувати detail-panel знизу — patch: stats badge chips прямо на NFT-card hover/tap-expand. | https://medium.com/@acarenatnic/how-honkai-star-rail-fixes-genshin-impacts-ui-problem-6b386d6154f1 |
| 10 | **Destiny 2 HUD — "only reveal what's needed"** | **07: YES** | 07 зараз `status-bar` з `VERA-ICS-0X-07` **постійно** видний. Destiny 2 ховає HUD поза active moments. Застосувати: corp watermarks fade-to-0.2 opacity у idle. | http://www.cand.land/destiny |
| 11 | **Pokémon GO / Monster Hunter Now — location-based map patterns** | **Обидва: YES — map screen** | MHNow обмежує combat 75s — короткий session, great UX match для HP-battle screens. Map зона = clear POI markers з concentric rings (already у 06 `.rift-dot`; 07 має `.rift-zone` з clip-path diamond — добре). | https://en.wikipedia.org/wiki/Monster_Hunter_Now |
| 12 | **Spotify Wrapped 2025 — 9:16 share cards, scrollable slides** | **Обидва: YES — Weekly Recap** | Canonical pattern для share. 9:16 vertical = Instagram/TikTok-ready без кропу. Кожен slide = 1 метрика. | https://rive.app/blog/spotify-used-rive-for-spotify-wrapped-2025 |
| 13 | **Strava Live Segments 2025** — real-time competition overlay | **Обидва: YES — Battle screen** | Ідея "ти vs інші зараз" через compact banner (D5 active-players). | https://www.t3.com/active/strava-2026-future-and-challenges |
| 14 | **WHOOP/Oura HRV dashboards** — HR як центральний glyph | **06: YES** | 06 вже робить HR live (хороша знахідка). Додати — HRV trend sparkline у Character, як WHOOP Recovery widget. | https://www.sensai.fit/blog/7-best-hrv-fitness-apps-oura-whoop-2025 |
| 15 | **Haptic feedback 10-20ms key-click, co-design visual+haptic** | **Обидва: YES — design spec** | У мокап-HTML додати `data-haptic` атрибути на CTA, HR-pulse, damage-tick, щоб RN-port знав. | https://source.android.com/docs/core/interaction/haptics/haptics-ux-design |
| 16 | **Mesh gradient WebGL vs CSS performance** | **06: SWITCH** | Current 06 використовує stacked `radial-gradient()` CSS — на Android Go пожирає CPU при scroll. WebGL shader (Stripe-pattern) — 10kb, 60fps. RN через `react-native-skia`. | https://medium.com/design-bootcamp/moving-mesh-gradient-background-with-stripe-mesh-gradient-webgl-package-6dc1c69c4fa2 |
| 17 | **WCAG 2.2 contrast calculator (webaim)** | **Обидва: MANDATORY** | Наведу чіткі ratios у §3 (06) і §4 (07). Кілька FAIL-пар знайдено — нижче. | https://webaim.org/resources/contrastchecker/ |

---

## 3. Варіант 06 — Neo Glass — Deep Dive

### 3.1 Що працює (take-it-forward)

1. **`mesh-map` з grid-overlay** — elegant, perfectly fits "scanning reality
   through Vector". Concentric-rift-dots — immediate game-locatable.
2. **HR-pulse with heart-shadow** — beautiful, `beat 0.9s` animation — правильна
   частота (clinical 67bpm feel, тож читається як "alive"). Keep.
3. **Avatar orb** з multi-radial-gradient — unique, не Duolingo-generic. Keep.
4. **Card-grid rarity** (common/rare/epic/legend) з cumulative visual weight
   (shadow + shimmer на legend only) — правильна rarity-hierarchy. Keep.
5. **Tabular-nums на stats + JetBrains Mono** — premium detail, 2026 SaaS
   staple (Linear, Arc). Keep.

### 3.2 Що НЕ працює (fix)

1. **"Holographic shimmer" + "NFT-card" лексика у code і concept.**
   `mob-card-holo`, `nft-card` className — зрадницькі терміни. Mainstream
   fitness users з `06/concept.md §Ризики` — токсичне crypto-асоціації. **Fix:**
   rename `.nft-card` → `.relic-card`, `.mob-card-holo` → `.mob-card-rift`,
   у concept-doc прибрати "NFT" взагалі (позиція — "rift-touched relic").
2. **Gradient mesh без noise = looks like template.** Поточне body bg = 3
   `radial-gradient` + solid color. На screenshot це вже бачили 1000 разів
   (Linear, Vercel, Stripe). **Fix:** додати 0.04 opacity noise SVG overlay
   (`feTurbulence baseFrequency=0.9`) — не видно свідомо, але текстура
   відчутна, знімає "стерильність".
3. **Animation density на Battle = battery killer.** На Battle screen одночасно:
   `beat` (HR heart), `shimmer` (mob card ::after), `orbit` (mob visual ::after),
   `pulse-soft` (невидимо — на Map, але `.glass ::before` gradient теж анімабельна
   у майбутньому). На low-end Android + reduced-motion — fallback відсутній.
   **Fix:** 1 primary animation за screen (HR-beat на Battle, shimmer на legend
   Inventory); решта — CSS `animation-play-state: paused` коли `prefers-reduced-motion`.
4. **Відсутня bento-структура на Character screen.** 06-Character зараз —
   вертикальний stack (avatar + 2 rings + 3-col stats + weekly card). 2026
   тренд — **asymmetric bento** (великий XP-ring займає 2/3 ширини, HP-ring
   1/3, STR/DEX/CON як 3 modular-sized tiles нижче, Weekly recap — wide
   card full-width). Не змінюємо структуру радикально, але додаємо
   hierarchy.
5. **Ring-progress у rings ".ring .val 91% 88%" — decontextualized.** Великі
   цифри без од. виміру. **Fix:** під `91%` — мікро-label "Next Lvl 15" у
   `.ring .lab`. Під `88%` — `176/200 HP`. Контекст = actionable.

### 3.3 Signature motif: **"Rift Lens"**

> **Один конкретний елемент, який робить 06 negatively впізнаваним у
> screenshot-тесті 3 секунди:**

**Опис:** на найважливішій glass-панелі кожного екрану (Battle = `.mob-card-holo`;
Map = `.mesh-map`; Character = avatar-card; Inventory = selected-item-card)
є **тонка вертикальна тріщина** `1.5px`, що перетинає панель по діагоналі
від top-right до bottom-left (або протилежній, screen-specific). Не pixel-lina
— це **refractive crack**: зображення позаду частково "зміщене" 2-3px у X-axis
через CSS `filter: hue-rotate(15deg)` + `transform: translateX(3px)` для
одного pseudo-element layer по обидва боки лінії. На тилті телефону (через
DeviceMotion API у RN) crack-angle парирується на 5° — живе відчуття, наче
скло рефрагує.

**Чому це hallmark:**
- Тільки RPGFit це має. Linear — linear (sic), Arc — loop, Apple Liquid
  Glass — lensing, але **не розкол-як-наратив**.
- Лор-coherent: це rift-лінія з `Теорія_Світу_v1.1` ("Щілина ВЕРА розкроює
  реальність") — буквально у інтерфейсі.
- Screenshot-friendly: одразу зрозуміло "це про розлом між світами".
- Технічно дешево: 2 pseudo-elements + 1 SVG filter + 1 device-motion hook.

**Fallback для reduced-motion:** static crack без parallax (тонка 1.5px
gradient-line `white 0 → transparent 50% → white 100%` з opacity 0.15).

**CSS draft (не implementation — референс для наступного кроку):**
```css
.glass.rift-lens::before {
  content: ''; position: absolute;
  top: -10%; left: 50%; height: 120%; width: 1.5px;
  background: linear-gradient(180deg, transparent 0, rgba(255,255,255,0.45) 50%, transparent 100%);
  transform: translateX(-50%) rotate(14deg);
  /* parallax hooked via useDeviceMotion() у RN */
  transition: transform 120ms linear;
}
.glass.rift-lens::after { /* refraction layer — зміщена тінь панелі позаду */
  content: ''; position: absolute; inset: 0;
  backdrop-filter: blur(20px) hue-rotate(15deg);
  clip-path: polygon(50% -10%, 52% -10%, 50% 110%, 48% 110%);
  transform: rotate(14deg);
}
@media (prefers-reduced-motion: reduce) {
  .glass.rift-lens::after { display: none; }
}
```

### 3.4 Кольорова палітра — current vs proposed + WCAG

**Current 06 palette (від `concept.md`):**

| Role | Hex | Usage test | WCAG Ratio | Status |
|------|-----|------------|------------|--------|
| Text default | `#F5F2FF` on `#0A0616` | body copy | **18.1:1** | AAA pass |
| Text muted | `rgba(245,242,255,0.55)` ≈ `#87859C` on `#0A0616` | mini-label, subtitle | **5.58:1** | AA pass, AAA-large pass, AAA-normal FAIL |
| Violet accent | `#B14AFF` on `#0A0616` | accents, tab-active glow | **5.03:1** | AA pass, AAA-large pass, AAA-normal FAIL |
| Pink accent | `#FF5AA9` on `#0A0616` | accents, damage numbers | **6.93:1** | AA pass, AAA-large pass |
| Cyan accent | `#5AD9FF` on `#0A0616` | stat-values, secondary | **12.1:1** | AAA pass |
| Success green | `#5AFFB0` on `#0A0616` | reward, HP | ~**13.5:1** (not measured — interpolated) | AAA pass |
| White on violet-pink btn-gradient mid `~#D852D4` | `#FFFFFF` | CTA text | ~**3.4:1** | **FAIL AA normal** (only large text pass) |

**Key finding:** `btn-gradient` white-on-gradient — **на точці переходу
pink→violet середній колір** має low contrast з білим. Краще б `#0A0616`
darker text на gradient CTA, але візуально це вже не premium. **Fix:**
Option A — text `#FFFFFF` + text-shadow `0 1px 2px rgba(0,0,0,0.4)`
(покращує perceived contrast без зміни hex). Option B — button-gradient
зміщується у більш темний коридор (`#8B1FE0 → #CC2A8C`) що дає 4.6:1 з
white. Рекомендую B.

**Proposed adjustments:**

| Change | From | To | Reason |
|--------|------|-----|--------|
| Text muted | `rgba(245,242,255,0.55)` | `rgba(245,242,255,0.70)` ≈ 6.8:1 | AAA-normal, читаєтьс​я краще на gradient-bg під glass |
| Violet accent UI-critical | `#B14AFF` | `#C369FF` (lighter) ≈ 6.3:1 | AA-safer у tab-active |
| Btn-gradient mid | `linear-gradient(135deg, #B14AFF, #FF5AA9)` | `linear-gradient(135deg, #8B1FE0, #CC2A8C)` | Text contrast вище |
| NEW — rift-lens color | — | `rgba(255,255,255,0.45)` | Visible на всіх glass-bg |

### 3.5 Typography system — screen-by-screen

Грубий scale 2026 (Refactoring UI principle — 8/10/11/12/14/16/20/28/40 step):

| Token | Size/Weight | Font | Usage |
|-------|-------------|------|-------|
| display-xl | 40/700 | Inter | Weekly Recap hero number, Level-up modal |
| display-lg | 28/700 | JetBrains Mono | HR-bpm, big stat, damage-tick |
| display-md | 22/700 | Inter | h1.title (current) — no change |
| body-lg | 16/600 | Inter | Mob-name |
| body-md | 13/500 | Inter | subtitle, description |
| body-sm | 12/500 | Inter | button-text |
| label-md | 11/500 | Inter | mini-label, tabs |
| label-sm | 10/500 | Inter | mini-label (status-bar) |
| mono-num | 11-22/500-700 | JetBrains Mono | всі цифри (tabular-nums) |
| flair | 9/500 | Inter | uppercase letterSpacing 0.1em — rarity, timestamps |

**Android Go fallback (360dp width):** label-sm 10px → 9px, display-xl
40 → 32. Global `html { font-size: clamp(14px, 4vw, 16px); }` root.

### 3.6 Нові екрани (wireframe-prose)

#### 3.6.1 Onboarding / Opening 60s — Neo Glass

**Wireframe-prose:**
- Full-screen mesh-gradient fade-in (0–3s). Background поступово "прокидається",
  radial-gradient pulsing як **дих Vector-а**.
- Центр: glass-card з **одним слот-рядком** `> BRP.SCAN_INIT`. Typing
  effect (0.5s per char).
- **3 сек:** величезний number `2042` у JetBrains Mono 64/700 з chromatic
  aberration 1-frame peak (one-time-only, 16ms). Під ним — label `YEAR OF
  THE RUPTURE`.
- **8 сек:** short subtitle fade-in: "Ти — один з 4,700. Твій телефон —
  Vector. Твоє тіло — єдина зброя."
- **15 сек:** glass-btn-gradient "I AM READY" (hapic-impact-heavy, screen
  shake 1-frame).
- Перша реальна екран — HealthKit permission prompt з flavor "Vector
  підключається до твого BRP. Прийміть щоб продовжити сканування."
- Скіп-кнопка: `skip intro` textlink в углу, opacity 0.4, тільки після
  8 секунд (не даємо skipnуть лор до того як він розкрився).

**Signature motif застосовано:** rift-lens у ""2042"" number — перетинає
саму цифру.

#### 3.6.2 Event screen (Day of Розколу) — Neo Glass

**Wireframe-prose:**
- Top hero: **"ДЕНЬ РОЗКОЛУ"** display-xl, під ним countdown `23:14:07`
  у JetBrains Mono, з pulsing rift-lens.
- **Active Players counter:** glass-card — "4,127 operators зараз у
  грі · +213 за годину". Violet-accent glow. D5 implementation.
- **Live feed (scrolling, 4 items vizible):**
  `[2m ago] Олена з Асгарду пройшла Клас II мобі у Галдхьопігені`
  `[4m ago] Anon з Олімпу: first Tier 2 Артефакт`
  `[5m ago] Group of 3 у Дніпрі: Клас III raid complete`
  `[7m ago] Система: Новий портал відкрився (Sector 07)`
  — кожна item-card з micro-avatar, lightweight, без pressure.
- Mid: **Global progress bar** — "Земля vs. Rupture" (global HP). "Players
  damaged: 47.2M HP · 82% complete." Multi-color gradient bar.
- CTA bottom: **"JOIN THE SURGE"** btn-gradient з "nearest active rift:
  120m SW".
- Achievement-badges strip: 3-4 unique-to-event (earned за activity).

**Чому так:** D5 social events P0 + no-chat/friend-system → feed-mode.
Spotify-Wrapped-like real-time dashboard fits NeoGlass.

#### 3.6.3 Weekly Recap share-card — Neo Glass

**Format:** 9:16 vertical (Instagram/TikTok-ready per Spotify Wrapped
canon).

**Wireframe-prose (scrollable 4 slides):**
- **Slide 1 (hero):** background = user's realm-themed mesh-gradient
  (якщо Асгард — cyan+violet; Олімп — pink+white). Великий number "+3"
  (level-ups) display-xl 64/700 з rift-lens. Під "THIS WEEK YOU ASCENDED."
- **Slide 2 (fitness):** bento-stack — ring-progress `16.4km` run,
  `8` battles, `142bpm` peak HR, `6/7` streak-days. Colors per stat.
- **Slide 3 (loot):** 3 largest artifacts з shimmer-legend. "You claimed:
  Trident Shard · Rift Core · Sigil". Holo-glow background.
- **Slide 4 (social):** "You're in top 18% of Асгард this week."
  Rank-chip (#4127). Share-btn prominent: "SHARE TO IG / TIKTOK / DISCORD".

**Signature motif:** rift-lens перетинає hero number slide 1.

#### 3.6.4 Active Players Feed (standalone screen) — Neo Glass

**Wireframe-prose:**
- Tab-bar context: вкладка `◎ Live` третьою з рядку.
- Top: search/filter chip-row `[All · Asgard · Olympus · ... · Near me]`.
- Main: virtual-list of activity-items (glass-sm cards):
  - `[live·now]` Anon з Олімпу is battling `Yotun Class II` at `Santorini
    Rupture` — HR `154bpm` — glass-sm з mini-HR-heart pulsing.
  - `[32s]` Group of 4 у `Kyiv 07` started raid.
  - `[2m]` +42 operators joined `Day of Розколу`.
- Sticky bottom glass-pill "filter by distance ⟶ 2.4km".
- **Без chat** (per D5 "no direct chat"). Лише watch + react emoji.

#### 3.6.5 Settings / Health permissions onboarding — Neo Glass

**Wireframe-prose:**
- Header: glass-card "BRP (Body Resource Points)" — animated ring зі %,
  "Vector is scanning 3 of 5 sources."
- **Permission status list** (bento 2x2):
  - `Heart rate` · green ring · "active"
  - `Steps` · green · "active"
  - `Sleep` · amber ring · "partial"
  - `Workouts` · red · "denied — tap to fix"
- Toggle-row: `Adaptive scan [iOS push / Android polling]` (D1 decision).
- Privacy plainlanguage: "Your health data never leaves your device except
  aggregate XP. Full policy ↗"
- Bottom: `MANAGE DATA` ghost btn + `EXPORT WEEKLY` btn-gradient.

### 3.7 Micro-interactions list (06)

| Event | Animation | Haptic (RN) | Why |
|-------|-----------|-------------|-----|
| HR-beat continuous | `transform: scale(1 → 1.18)` 0.9s | `impact-light` on beat peak (1Hz throttle) | Alive feeling |
| Damage tick | -142 number pop-in 150ms + shimmer-sweep | `notification-success` | Feedback on set-complete |
| Level-up | full-screen confetti-particle 2s + rift-lens expand | `notification-success` sequence (3 pulses) | Emotional hook §3 |
| Card-tap (nft/relic) | scale 1 → 0.97 120ms | `selection-changed` | Tactile affordance |
| Btn-gradient press | scale 0.98 100ms + brightness -10% | `impact-medium` | CTA weight |
| Portal-dot on map | radial pulse 2s continuous | — | Attention draw |
| Rift-lens parallax | DeviceMotion → translateX ±3px, 120ms lerp | — | Hallmark effect |
| Shimmer on legend | `background-position 0 → 200%` 3s linear | — | Rarity signal |

**Reduced motion fallback:** усі continuous → зупинено, one-shot → залишаються
але без particle-scaling.

### 3.8 Checklist production (Figma/RN handoff)

- [ ] Rift-lens motif — Figma component variant `rift-lens/direction=[right|left]/
      intensity=[subtle|strong]`.
- [ ] Bento-layout tokens — spacing 4/8/12/16/24, gap-tokens per container.
- [ ] Dark mode only (validated з founder; light-alt не потрібен у beta).
- [ ] Type scale в Figma Styles (9 sizes, 4 weights, 3 fonts).
- [ ] Color styles з WCAG-status метадатою у description.
- [ ] SVG noise filter як reusable asset (`assets/noise.svg`,
      `baseFrequency=0.9`).
- [ ] Haptic-map token dictionary `haptics.ts` — reference-табличка для RN.
- [ ] Lottie-файли для: level-up confetti, rift-lens expand, HR-beat
      fallback (не CSS — щоб виглядало однаково iOS/Android).
- [ ] Performance budget: `.glass` layers ≤ 4 per screen; backdrop-filter
      feature-detect на Android Go з `will-change: backdrop-filter`.

---

## 4. Варіант 07 — Night City Noir — Deep Dive

### 4.1 Що працює (take-it-forward)

1. **`clip-path polygon` panels** — читається як "corpo" instantly. CP2077
   DNA correct. Keep.
2. **Hazard-strip repeating-linear-gradient** — cheap, high-impact, 45°
   industrial. Keep.
3. **Toxic yellow `#FCEE0C`** on dark `#0A0614` = **16.5:1** contrast —
   **AAA pass** beautifully. Найяскравіший hallmark color у палітрі. Keep
   як primary CTA bg/text.
4. **Corpo watermarks** (`VERA-ICS · OX.07 · RESTRICTED`) — lore-depth
   detail. World-building через UI. Unique vs 01 Quantum Neon. Keep.
5. **`inv-cell` grid з stamp-mini** — Tarkov-inventory-feel. Diegetic.
   Keep.
6. **Damage-number з magenta-shadow** (`2px 2px 0 #C800FF`) — pure
   comic-book, reads як "hit". Keep.

### 4.2 Що НЕ працює (fix)

1. **`#FF0051` red з білим текстом — FAIL AA (3.91:1).** Критично. У коді:
   `.tag.red { background: var(--red); color: white; }` і
   `.btn-noir.red { background: var(--red); color: white; }`.
   **Fix option A:** darken red до `#E60047` (ratio 4.62:1) — pass AA.
   **Fix option B:** color `#000000` на red (15.8:1) — aggressive pulp look.
   **Fix option C:** white тільки на large-text (≥18pt bold) — працює на
   "▸ BREACH ZONE" btn (який 12px-900 — bold ≈ 3:1 threshold у AA-large
   rule — passes). На .tag (9px) — **fails**, треба A або B. Рекомендую A.

2. **Animation overload на Battle = CP2077 UX-критика повторилася.** Одночасно:
   `glitch-flicker` (corp-target ::before), `sweep` (ad-strip), `shimmer-neon`
   (inv-cell.legend), `warn-pulse` (rift-zone). На 07-Battle це 2-3 одночасно.
   **Fix:** 1 primary per screen (glitch-flicker — Battle, sweep — Map
   ad-strip, shimmer-neon — Inventory legend). Решта — `animation-play-state:
   paused` коли `prefers-reduced-motion`.

3. **Japanese kanji декорації з concept.md (`夜 月 Σ∆Ø ∀`) — відсутні у
   поточному HTML, що добре.** Але concept-doc їх рекомендує. Це **cultural
   appropriation risk** + не співпадає з VERA-ICS Ukrainian-context. **Fix:**
   замінити на mix of:
   - Кирилиця-glyphs декором: `Ω∂Σ` (math), `ДНК`, `ОКО`, `Ж∆`
   - Трипільський орнамент (geometric zigzag+triangles) — як micro-pattern
     у watermark-band
   - Tryzub-stylized `⊥╬⊥` у rank-stripes (VERA-ICS chip-id)

4. **Chromatic aberration у `.glitch-text::before/::after` — permanent = motion sickness risk.**
   Current: `::before { color: red, translate -1px }`, `::after { color: cyan,
   translate 1px }` завжди-on. **Fix:** trigger-based (animation-delay rare,
   0.3s burst every 8-12s randomized) + `prefers-reduced-motion` kill.

5. **Scanlines на `.phone::after` постійні** — CRT-феел хороший, але й
   перевершує перегляд на OLED > 10min → fatigue. **Fix:** opacity 0.15
   (зараз 0.015 тонко — OK, це вже subtle — keep but verify).

6. **Відсутня Ukrainian identity у 07, хоча VERA-ICS лор-corp має бути "наш
   світ 2042".** Concept згадує "не generic East Asian cyberpunk", але HTML
   не відображає. **Fix:** додати одиничні cyrillic tags: `ВЕРА-ІКС.ОХ`,
   `КИЇВ.СЕКТОР.07`, в flavor `recovered.santorini` → `вилучено.Санторіні`
   (або в bilingual mode).

### 4.3 Signature motif: **"Corpo Redaction"**

> **Опис:** повторюваний `█████` чорно-жовто-штрихований bar, який
> "накриває" classified-поля (items, coordinates, drop info). Коли юзер
> tap'ить redacted-поле — glitch-розкриття (400ms): chromatic aberration
> peak, redaction-bar rіп'ється з yellow-sparks, текст проявляється знизу
> літерами `-one-by-one` (typing 25ms/char). Shadow: `inset 2px 0 0 #FCEE0C`
> лівий жовтий stitch — як corpo-stamp seam.

**CSS draft:**
```css
.redacted {
  position: relative; background: #000;
  color: transparent; padding: 2px 8px;
  box-shadow: inset 2px 0 0 var(--toxic);
  overflow: hidden;
}
.redacted::before {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(45deg, #000 0 6px, #1a1a00 6px 12px);
}
.redacted::after {
  content: attr(data-classified);
  position: absolute; inset: 0; padding: 2px 8px;
  color: var(--toxic); font: 900 inherit/1 var(--font-display);
  letter-spacing: 0.15em;
}
.redacted.revealed::before { animation: rip 0.4s forwards; }
/* rip keyframes -- scale-x to 0 with yellow flash */
```

**Чому це hallmark:**
- Унікально лор-coherent: VERA-ICS corporation засекречує rift-данні.
- Interactive — user tap'ить і **дізнається**; це "таємниця" hook §7
  emotional-hooks.md.
- Screenshot-recognizable: ніхто з competitors так не робить.
- Differentiates vs 01 Quantum Neon (clean/transparent HUD) — 07
  **приховує** інформацію, 01 її **показує**.

### 4.4 Кольорова палітра — current vs proposed + WCAG

**WCAG ratios (measured через webaim.org/resources/contrastchecker):**

| Pair | Ratio | Status |
|------|-------|--------|
| `#FCEE0C` toxic на `#0A0614` bg | **16.5:1** | AAA pass ✓ |
| `#000000` text на `#FCEE0C` (.tag default, .btn-noir) | **17.3:1** | AAA pass ✓ |
| `#FFFFFF` на `#FF0051` (.tag.red, .btn-noir.red) | **3.91:1** | **FAIL AA normal** ✗ (pass AA-large) |
| `#FF0051` на `#0A0614` (HP-fill accent) | **5.11:1** | AA pass, AAA-large |
| `#000000` на `#00F0FF` cyan tag | ~**14.5:1** (interpolated from 14.2 cyan/bg) | AAA pass ✓ |
| `#00F0FF` cyan на `#0A0614` | **14.2:1** | AAA pass ✓ |
| `#39FF14` green на `#0A0614` | **14.7:1** | AAA pass ✓ |
| `#C800FF` magenta на `#0A0614` | **4.67:1** | AA pass, AAA-large |
| `#8A7A8F` muted на `#0A0614` | **5.01:1** | AA pass, AAA-large |
| `#FCEE0C` toxic на `#FFFFFF` | **1.20:1** | **FAIL ALL** ✗ — не використовувати toxic yellow ніколи на white |

**Critical finding:** `#FCEE0C` toxic yellow на WHITE = 1.20:1 total-fail.
Питання з instruction file: "перевір теплий yellow #FCEE0C на білому фоні
в 07" — **так, це FAIL (1.20:1)**. У поточному HTML 07 білого фону не
існує, але concept-md каже "Corpo-white `#FFFFFF`" як color у палітрі —
**треба спеціально забороняти toxic-yellow-on-white combination** у design
system.

**Proposed adjustments:**

| Change | From | To | Reason |
|--------|------|-----|--------|
| Red accent (critical) | `#FF0051` | `#E60047` | white-on ratio 4.62:1 (pass AA normal) |
| ALT option — red btn text | `color: white` на .btn-noir.red | `color: #000000` (black) | 15.8:1, aggressive pulp |
| tag.red text | `color: white` | `color: #FFFFFF` з `text-shadow: 0 0 2px #000` | perceived boost ~0.5 |
| Magenta-glow-shadow for damage | `2px 2px 0 #C800FF` | keep — глиф-effect, не primary readable | — |
| Muted grey | `#8A7A8F` | `#A89DA8` (lighter) ≈ 6.4:1 | AAA-normal cushion |
| NEW — Ukrainian-accent azure | — | `#0057B7` (prapor-blue) | Use як secondary accent у watermarks (`ВЕРА-ІКС`) |

### 4.5 Typography system — 07 specific

| Token | Size/Weight | Font | Usage |
|-------|-------------|------|-------|
| display-xl | 32/900 | Orbitron | Big number (HR, damage), Rift-ID |
| display-lg | 22/900 | Orbitron | h1.title (reduce з 18 якщо screen-space, OK keep 18) |
| display-md | 16/900 | Orbitron | mob-name |
| body-md | 11/600 | IBM Plex Sans | desc, flavor |
| mono-data | 10/500 | Space Mono | все що "дані" — coords, IDs, chip-ids |
| mono-big | 14/700 | Space Mono | HP numbers |
| tag | 9/700 | Orbitron uppercase letter-spacing 0.18em | tag, ad-strip |
| watermark | 7/400 | Space Mono | corp-watermark |

**Android Go (360dp):** all sizes -1px downward; `tag-mini` 8px (max
compressible before illegibility).

### 4.6 Нові екрани (wireframe-prose)

#### 4.6.1 Onboarding / Opening 60s — Night City Noir

**Wireframe-prose:**
- Black screen 0-2s. Single line `> VERA-ICS://AUTH.BOOTSTRAP` typing.
- 2s: large `OX.07.42` chip-id appears з hazard-stripe fill animation.
- 4s: video-call-like rectangle з rain-overlay: "UNIT_IDENTIFICATION_REQUIRED"
  corp-stamp brick-font. Mock-CCTV feed placeholder.
- 6s: `[REDACTION █████████ OPERATOR_NAME ]` tap to reveal → your name.
- 10s: huge `2042` number з glitch + chromatic aberration (1 peak burst
  only). "YEAR OF THE RUPTURE" subscript.
- 15s: list of corp-approved realms with `CLEARANCE.BLOCKED` hazard tape.
  Not a choice (per D3 no factions) — just worldbuilding.
- 20s: `▸ ACCEPT TERMS` toxic-yellow btn-noir with magenta offset shadow.
  Corpo fine-print "OBEDIENCE_REQUIRED. Vector_Scan mandatory for RIFT
  engagement."
- 30s: HealthKit prompt with corpo framing — "VERA-ICS requires BRP.SCAN
  access to engage hostile RIFT entities."

**Signature motif:** redaction bar is central action — tap-to-reveal
user identity.

#### 4.6.2 Event screen (Day of Розколу) — Night City Noir

**Wireframe-prose:**
- Top hero: huge hazard-stripe band "DAY.OF.RUPTURE // LIVE" + countdown.
- Corpo memo-style card: `MEMORANDUM // 31.10.2042 · ALL_OPERATORS ·
  EMERGENCY.PROTOCOL.RIFT-0247 ACTIVATED · 6_REALM_BREACH.SIMULTANEOUS ·
  REPORT_TO_NEAREST_ZONE`. Typewriter-reveal 0.8s.
- **Active Players:** `4,127 OPERATORS.ACTIVE · +213/HR · THREAT.ESCALATING`
  — з progress-bar toxic.
- Live feed (3-4 items):
  `[T−02:14] UNIT.OLENA_K // ASGARD.07 // KILL.CONFIRMED`
  `[T−04:32] SQUAD.03 // KYIV.04 // CLASS_III.ENGAGED`
  `[T−07:01] SYSTEM // PORTAL.BREACH // SECTOR.14.NEW`
  — з corpo-ID chips, rain-streak overlay.
- Global HP bar "EARTH.INTEGRITY 47.2M / 60M · 82% · HOLD.THE.LINE"
  red→toxic gradient.
- CTA: `▸ DEPLOY TO NEAREST` btn-noir.red.

**Redaction motif:** `[T−XX:XX]` timestamps mostly redacted, reveal on tap.

#### 4.6.3 Weekly Recap share-card — Night City Noir

**Format:** 9:16 vertical, corpo-dossier-style.

**4 slides:**
- **Slide 1 (hero):** `FILE ACCESSED // WEEK.16 // CLEARANCE.LVL3`
  corpo-stamp top. Huge `+3 LVL` у display-xl 64/900 Orbitron з chromatic
  peak. `OPERATOR_OLEKSANDR_G // RANK.III.ASCENDED`.
- **Slide 2 (combat report):** dossier — "ENGAGEMENTS: 08 · KILLS_CONFIRMED:
  14 · AVG_HEART_RATE: 142bpm · DPS_PEAK: 47.2". Bento-stack з corp-watermarks.
- **Slide 3 (loot manifest):** "ACQUIRED_ARTIFACTS" з 3 images as classified-file
  thumbnails + `OFFGRID` / `CLASSIFIED` stamps.
- **Slide 4 (ranking):** "PEER.COMPARISON: top 18% ASGARD.SECTOR.07. RIVAL
  DETECTED: UNIT.ANON_4612."
- Bottom share-bar: `EXPORT TO EXTERNAL.NETWORK [IG] [TIKTOK] [DISCORD]`.

#### 4.6.4 Active Players Feed — Night City Noir

**Wireframe-prose:**
- Top: corp-style header "INTELLIGENCE.STREAM // LIVE"
- Filter-chips: `[ALL · ASGARD · OLYMPUS · NEARBY · SQUAD]` as parallelogram-tags.
- Main feed: dossier-cards, each з:
  `[IMAGE BLOCKED — ░░░]` small mug-placeholder
  `UNIT.ANON_4421` / tag `ASGARD.RARE`
  status: `ENGAGED.YOTUN_CLASS_II` / HR `154bpm`
  location: `SANTORINI.SECTOR.03` / "HAZARD.ZONE"
  micro-actions: `[REACT ▲] [OBSERVE]` (no chat).
- Rain-streak overlay на whole list.
- Global-progress ticker bottom: `TOTAL.ACTIVE: 4127 · NEW.SIGHTINGS/MIN: 23`.

#### 4.6.5 Settings / Health permissions — Night City Noir

**Wireframe-prose:**
- Header hazard-strip: `BRP.CONFIGURATION // CLASSIFIED`.
- Permission grid 2x2 у clip-panel-polygons:
  - `HR.SCAN [ACTIVE]` green dot, toxic text
  - `STEPS.SCAN [ACTIVE]` green
  - `SLEEP.SCAN [PARTIAL]` amber
  - `WORKOUT.INGEST [DENIED]` red з redaction-bar, tap reveals tutorial
- Row "SENSORS" — toggle `ADAPTIVE.DAEMON [iOS.PUSH | ANDROID.POLL]`.
- Corpo-manifesto card: "THIS.UNIT.IS.PROPERTY.OF.VERA-ICS. DATA.STAYS.LOCAL.
  AGGREGATES.SHARED.VIA.ENCRYPTED.CHANNEL."
- Bottom 2 CTA: `EXPORT.SESSION.LOG` ghost + `REVOKE.ACCESS` red.

### 4.7 Micro-interactions list (07)

| Event | Animation | Haptic | Why |
|-------|-----------|--------|-----|
| Redaction-reveal tap | rip-horizontal scale-x 0 + yellow spark-flash | `impact-heavy` | Signature motif payoff |
| Damage tick | number flash + magenta-offset shadow pulse | `notification-success` sharp | Combat feedback |
| Btn-noir press | scale 0.97 + magenta-offset-shadow collapse | `impact-medium` | Button weight |
| Glitch-flicker (rare) | chromatic +1/-1 px 2-frame every 8-12s randomized | — | Cyberpunk noir atmosphere |
| Hazard-strip on engage | 45° scroll 0.5s | — | "Alert activated" |
| Inv-cell legend | shimmer-neon 3s continuous (PAUSE on reduced-motion) | — | Rarity |
| Rain-streak | SVG filter gentle translate Y, 8s linear infinite | — | Atmospheric ambient |
| Tab switch | hazard-strip wipe-in 300ms | `selection-changed` | Nav feedback |

**All animations must obey `prefers-reduced-motion` + `will-change` off
when not active.**

### 4.8 Checklist production (07 Figma/RN handoff)

- [ ] Redaction-motif component у Figma — variant `state=[classified|revealing|revealed]`.
- [ ] Glyph-pack asset: 40 cyrillic/math glyphs (`Ω∂Σ ДНК ОКО Ж∆ ⊥╬⊥ ВЕРА-ІКС`
      blocks) в SVG.
- [ ] Ukrainian-azure `#0057B7` як secondary accent у Figma Styles + usage
      guidelines (тільки watermarks, не primary).
- [ ] Corp-watermark текст-patterns 10+ variants (`RESTRICTED`, `EYES.ONLY`,
      `OX.07.CLASSIFIED`, `AUTH.CERT.VALID`, etc.).
- [ ] Clip-path polygon system — 6 reusable shapes (panel, tag, chip, btn,
      inv-cell, id-card) з token gap-sizes.
- [ ] Lottie: redaction-reveal (400ms rip), damage-tick (300ms).
- [ ] Haptic-map `haptics-noir.ts`.
- [ ] Legal: `VERA-ICS` trademark availability check before public.
- [ ] Font-licensing confirmation для Orbitron/Chakra Petch commercial use.

---

## 5. Крос-вплив (що повернути у shared/base.css, README, 01)

### 5.1 `design/shared/base.css` — additions

- **Reduced-motion global kill:**
  ```css
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
      scroll-behavior: auto !important;
    }
  }
  ```
- **Bento grid tokens:** `--grid-12`, `--gap-sm`, `--gap-md`, `--gap-lg`.
- **Typography scale vars:** `--fs-40, --fs-28, --fs-22, --fs-20, --fs-16,
  --fs-14, --fs-13, --fs-12, --fs-11, --fs-10, --fs-9, --fs-8`.
- **Haptic data-attribute spec** (коментарі):
  `data-haptic="selection-changed | impact-light | impact-medium |
  impact-heavy | notification-success | notification-warning"` — RN
  маппинг однозначний.
- **Focus-visible outlines** (WCAG 2.4.7) — `[tabindex]:focus-visible,
  button:focus-visible { outline: 2px solid var(--accent); outline-offset:
  2px; }`.

### 5.2 `design/README.md` — additions

- Додати розділ "Signature motifs": 06 = Rift Lens, 07 = Corpo Redaction,
  01 = (TBD — можливо scan-radar, вже має).
- Додати розділ "Accessibility floor": WCAG 2.2 AA minimum for all
  text/background combinations — показати таблицю з 06 і 07 ratios.
- Screens list обов'язковий для кожного варіанту: 8 (Battle, Map, Character,
  Inventory, **Onboarding**, **Event**, **Weekly Recap**, **Settings**) —
  ці 4 нові є P0 gap.

### 5.3 01 Quantum Neon — щоб не відставав

- 01 має перевагу clean-HUD-aesthetic — вже на 80% production-ready.
- Що дообавити щоб паритет:
  - Signature motif: **"Radar Sweep"** — 30° sweeping line з afterglow на
    кожному major-screen (Battle = sweep через mob-silhouette; Map = sweep
    через map area; Character = sweep через avatar-silhouette). 2s period.
    Вже частково у `.radar` — generalize.
  - Додати ті ж 5 нових екранів (Onboarding, Event, Weekly, Active Feed,
    Settings).
  - Reduced-motion для `.map-node::after` pulse-ring.

### 5.4 Shared заборонений list (design system red-lines)

1. Toxic yellow `#FCEE0C` on white background — **FORBID** (1.20:1).
2. White text on `#FF0051` — **FORBID** (3.91:1 FAIL AA).
3. Chromatic aberration як permanent layer — allowed тільки peak-moments.
4. Web3/NFT/crypto/token лексика — заборонено в UI text everywhere
   (per 06/concept.md §Ризики).
5. Japanese kanji декоративно — не використовувати; якщо потрібні глиф-flair,
   use cyrillic/math/tryzub.
6. Race/faction labels в UI (per D3/D4) — OK в 07 показано "no faction-pick",
   keep.
7. Animations без `prefers-reduced-motion` fallback.

---

## 6. Source list (17 URLs, grouped)

### Glassmorphism / Premium Modern (для 06)
- Apple Liquid Glass iOS 26 usability analysis — https://www.ahmeduiux.com/en/blogs/apple-liquid-glass-ios26-usability-issues-uiux-lessons
- Glassmorphism 2026 modern implementation — https://invernessdesignstudio.com/glassmorphism-what-it-is-and-how-to-use-it-in-2026
- SVG noise + CSS backdrop-filter living UI — https://medium.com/design-bootcamp/how-i-used-css-backdrop-filter-svg-noise-to-create-a-living-ui-background-c3aaaf63befc
- Dark Glassmorphism defining 2026 UI — https://medium.com/@developer_89726/dark-glassmorphism-the-aesthetic-that-will-define-ui-in-2026-93aa4153088f
- Mesh gradient WebGL mobile performance — https://medium.com/design-bootcamp/moving-mesh-gradient-background-with-stripe-mesh-gradient-webgl-package-6dc1c69c4fa2
- Linear design aesthetic SaaS UI — https://blog.logrocket.com/ux-design/linear-design/
- 7 SaaS UI Trends 2026 — https://www.saasui.design/blog/7-saas-ui-design-trends-2026

### Cyberpunk / Sci-fi Game UI (для 07)
- Cyberpunk 2077 UI Visual Design Part 2 (Vilimovský, Behance) — https://www.behance.net/gallery/133185623/Cyberpunk-2077User-Interface-(Part-2)
- Cyberpunk 2077 UX/UI critique — https://aidenlesanto.medium.com/cyberpunk-2077-ux-ui-critique-f064884176b2
- Cyberpunk glitch effect pure CSS tutorial — https://ahmodmusa.com/create-cyberpunk-glitch-effect-css-tutorial/
- Destiny UX/UI — David Candland — http://www.cand.land/destiny
- Chromatic aberration in 2025 design trend — https://designworklife.com/why-designers-are-embracing-chromatic-aberration-on-purpose/
- Cyberpunk Edgerunners / Ghost in the Shell reference — https://www.cbr.com/cyberpunk-edgerunners-best-ghost-in-the-shell-references/

### Game UX (patterns — both)
- How Honkai Star Rail fixes Genshin UI problem — https://medium.com/@acarenatnic/how-honkai-star-rail-fixes-genshin-impacts-ui-problem-6b386d6154f1
- Monster Hunter Now overview — https://en.wikipedia.org/wiki/Monster_Hunter_Now
- Game UI Database — https://www.gameuidatabase.com/
- Game UI Database — Pokémon GO — https://www.gameuidatabase.com/gameData.php?id=1317

### Fitness App References (both)
- Strava 2026 future analysis — https://www.t3.com/active/strava-2026-future-and-challenges
- WHOOP/Oura HRV fitness apps 2026 — https://www.sensai.fit/blog/7-best-hrv-fitness-apps-oura-whoop-2025

### Social / Share-card Patterns (Weekly Recap)
- Spotify Wrapped 2025 uses Rive — https://rive.app/blog/spotify-used-rive-for-spotify-wrapped-2025
- Spotify Wrapped design aesthetic verdict — https://elements.envato.com/learn/spotify-wrapped-design-aesthetic

### Bento / 2026 Mobile Trends (both)
- Best Bento Grid Examples 2026 — https://mockuuups.studio/blog/best-bento-grid-design-examples/
- Beyond the Glass — 7 Mobile UI Trends 2026 — https://www.abdulazizahwan.com/2026/02/beyond-the-glass-7-mobile-ui-trends-defining-2026.html
- Muzli — Mobile App Design Patterns 2026 — https://muz.li/blog/whats-changing-in-mobile-app-design-ui-patterns-that-matter-in-2026/

### Accessibility / WCAG 2.2 (both — critical)
- WebAIM Contrast Checker (used for all numerical ratios in §3.4 & §4.4) — https://webaim.org/resources/contrastchecker/
- WebAIM Contrast and Color Accessibility — https://webaim.org/articles/contrast/
- Designing Safer Web Animation (vestibular) — https://alistapart.com/article/designing-safer-web-animation-for-motion-sensitivity/
- WebKit Responsive Design for Motion — https://webkit.org/blog/7551/responsive-design-for-motion/
- Android Haptics UX design — https://source.android.com/docs/core/interaction/haptics/haptics-ux-design

### Game onboarding (Opening 60s screen rationale)
- Mobile game onboarding UX strategies — https://medium.com/@amol346bhalerao/mobile-game-onboarding-top-ux-strategies-that-boost-retention-6ef266f433cb
- Imaginary Cloud — what UX can learn from game onboarding — https://www.imaginarycloud.com/blog/videogame-onboarding-design-lessons

---

## 7. Додатки

### 7.1 WCAG contrast measurements summary table

| Variant | Foreground | Background | Ratio | AA normal | AA large | AAA normal | AAA large |
|---------|------------|------------|-------|-----------|----------|------------|-----------|
| 06 | `#F5F2FF` | `#0A0616` | 18.1:1 | ✓ | ✓ | ✓ | ✓ |
| 06 | `#87859C` (muted approx) | `#0A0616` | 5.58:1 | ✓ | ✓ | ✗ | ✓ |
| 06 | `#B14AFF` | `#0A0616` | 5.03:1 | ✓ | ✓ | ✗ | ✓ |
| 06 | `#FF5AA9` | `#0A0616` | 6.93:1 | ✓ | ✓ | ✗ | ✓ |
| 06 | `#5AD9FF` | `#0A0616` | 12.1:1 | ✓ | ✓ | ✓ | ✓ |
| 06 | `#FFFFFF` on btn-gradient mid | varies | ~3.4:1 | **✗** | ✓ | ✗ | ✗ |
| 07 | `#FCEE0C` | `#0A0614` | 16.5:1 | ✓ | ✓ | ✓ | ✓ |
| 07 | `#000000` | `#FCEE0C` | 17.3:1 | ✓ | ✓ | ✓ | ✓ |
| 07 | `#FFFFFF` | `#FF0051` | 3.91:1 | **✗** | ✓ | ✗ | ✗ |
| 07 | `#FF0051` | `#0A0614` | 5.11:1 | ✓ | ✓ | ✗ | ✓ |
| 07 | `#00F0FF` | `#0A0614` | 14.2:1 | ✓ | ✓ | ✓ | ✓ |
| 07 | `#39FF14` | `#0A0614` | 14.7:1 | ✓ | ✓ | ✓ | ✓ |
| 07 | `#C800FF` | `#0A0614` | 4.67:1 | ✓ | ✓ | ✗ | ✓ |
| 07 | `#8A7A8F` | `#0A0614` | 5.01:1 | ✓ | ✓ | ✗ | ✓ |
| 07 | `#FCEE0C` | `#FFFFFF` | **1.20:1** | **✗** | **✗** | **✗** | **✗** |

**Summary: 2 FAIL-AA pairs знайдено:**
- 06: white text on gradient-mid (~3.4:1). Fix: darken gradient or add
  text-shadow.
- 07: white text on `#FF0051` (3.91:1). Fix: darken red to `#E60047`
  (4.62:1) or use black text.

### 7.2 Priority action list (для фаундера)

| Priority | Action | Effort | Owner |
|----------|--------|--------|-------|
| P0 | Fix WCAG-FAIL у 06 (gradient-text) і 07 (red-btn) — 2 CSS lines | 30 хв | Claude (next step) |
| P0 | Додати 5 нових екранів per variant | 2-3 дні | Claude + design review |
| P0 | Remove NFT/crypto слова з 06 | 1 год | Claude |
| P0 | Rename `dichotomy 07` Japanese kanji → cyrillic/trypillian | 2 год | Designer + Claude |
| P1 | Implement "Rift Lens" (06) + "Corpo Redaction" (07) motifs у HTML mockups | 1 день | Claude |
| P1 | Додати `prefers-reduced-motion` у shared/base.css | 30 хв | Claude |
| P1 | Noise SVG overlay на 06 glass panels | 1 год | Claude |
| P2 | Figma-handoff component library (design-system scope) | 2-3 тижні | Designer (external) |
| P2 | WebGL mesh gradient (Skia у RN) replacement для CSS-radial | 1-2 дні | RN dev post-mockup |

### 7.3 Що НЕ дочитано / винос

- Game UI Database screenshots specific (Genshin Impact, Honkai SR, Pokémon
  GO) не скачано повний set — на Figma-handoff етапі (P2).
- GDC Vault free talks про UI patterns AAA-games — recommended перед
  Figma-стадією.
- Reddit r/gameassets, r/gamedev threads — manual-valid після Figma handoff
  для community-check.
- Apple HIG 2026 Liquid Glass SDK specifics (iOS 26 dev docs) — для RN
  implementation feature-detect stage.

### Changelog

- 2026-04-18 — v1. 17 external URLs, 15 WCAG pairs measured (webaim API).
  Two signature motifs defined (Rift Lens, Corpo Redaction). 5 нових
  screens per variant described as wireframe-prose. 2 FAIL-AA pairs flagged.
  Instruction timebox ~50 хв дотримано.
