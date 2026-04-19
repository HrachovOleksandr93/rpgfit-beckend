# Mobs Carry "Our-World" Devices — Vision Draft

> **Статус:** IDEA. Зафіксовано 2026-04-18 фаундером. НЕ імплементувати до
> подальшого sign-off. Додати у scope BA-агентам під час наступних
> обговорень.
> **Пов'язане:** `docs/vision/mobs.md`, `docs/vision/portals.md`,
> `rpgfit-beckend/docs/BUSINESS_LOGIC.md §10` (mobs), §11 (loot).

---

## 1. Ідея коротко

Щілини ВЕРА перетинають наш світ і світи реалмів. Через це **моби, які
приходять з реалмів, іноді приносять із собою пристрої нашого світу** —
предмети, що «зачепилися» за них при переході через Rupture.

## 2. Наративне обґрунтування

- Тріщина — це не одностороння двері. Вона «всмоктує» дрібні предмети зі
  свого оточення. Моб, що проходить через неї, може вийти з нашого боку
  з чимось дивним у руці/пасті/лусці.
- Приклади «наших» речей, які може носити моб:
  - **Apple Watch** (працює зсередини порталу — batteries заряджаються
    rift-енергією, але гадальне годинник завжди показує час у Ель-Посві)
  - **Powerbank** (з корпусом зі шрамами опіків)
  - **Смартфон з розбитим екраном** (всередині log останнього туриста,
    який зник)
  - **Гиря-16кг** (на голови моба прикована як зброя)
  - **Бігові кросівки** (моб носить їх як намиста-трофей)
  - **Каска будівельника** (на голові, з наклейкою української компанії)
  - **Кишеньковий ліхтарик** / **велосипедна фара**
  - **USB-накопичувач** з фрагментами чиєїсь дисертації
  - **Hydration pack** (моб п'є з нього як з поранького соску)
  - **Книга / підручник** з підкресленнями у дивних місцях

## 3. Чому це цікаво (upside)

- **Непередбачуваний loot.** Замість стандартних «меч +3 str», деякий loot
  — «Apple Watch Rift-touched», «Powerbank of Last Hiker». Кожен предмет
  має маленьку story-картку. Це тягне hook **«колекція»** (6) і
  **«таємниця»** (7).
- **Humour + horror контраст.** Дивний меч у Йотуна — це OK. Apple Watch
  на хвості Йотуна — це запам'ятовується. Це те, що юзери скринять і
  постять у Twitter.
- **World-building через items.** Кожен лut розкриває маленький фрагмент
  лору (чий підручник? що за USB?). Створює tsypnly-effect — юзер хоче
  більше.
- **Easter-egg opportunity.** Можна ховати real-world references (USB з
  фрагментом Теорії Світу v1.1, книга вашої університетської групи) —
  community buzz.
- **Share-card-ready.** «Я забрав у Йотуна свою гирю назад» — мемабельно.

## 4. Mechanics — як це працює

### 4.1 Loot-drop table

- Кожен mob має базовий loot-pool (artifacts, essence).
- Додатково кожен mob має **cross-world drop-chance** (напр., 5-10%).
- Cross-world items йдуть зі **другого pool** — list «our-world objects».
- Items мають **mechanical effect** (small buff) + **flavor story**.

### 4.2 Mechanical ефекти (приклади)

| Item | Drop | Effect |
|------|------|--------|
| Apple Watch (rift-touched) | Rare | +2 CON, passive: показує HR другого мобу у battle |
| Powerbank (scarred) | Uncommon | Після battle — +10% stamina recovery (once/day) |
| Гиря-16кг (моба-броня) | Rare | +3 STR, -1 DEX (heavy) |
| Кросівки (моба-трофей) | Uncommon | +2 DEX, run-distance multiplier +5% |
| USB (cryptic) | Very rare | Unlocks 1 lore-fragment у Vector codex |
| Hydration pack (chewed) | Common | +1 stamina max |
| Ліхтарик | Uncommon | У night-mode battles +5% crit |
| Будівельна каска | Common | +1 CON, DEX -1 |
| Підручник (marked) | Rare | +1 INT (якщо приймемо mental-stats ідею) |
| Smartphone (cracked) | Very rare | Reveals 1 hidden Rupture на мапі |

### 4.3 Story-cards

Кожен cross-world item має `flavor_text` 1-2 sentence:

> «Apple Watch. Крапля крові Йотуна ще на ремінці. Дата в налаштуваннях —
> 14 березня 2031. Цього дня у нашому світі було 18 квітня 2026.»

> «Гиря 16кг з "Sportmaster". На дні вигравіруване "ДРУЗЯМ З ГРУПИ МЕ-2023".
> Чия вона була?»

### 4.4 Inventory UI

- Cross-world items мають **окремий icon-style** — real-world фото-силует
  (а не fantasy-glyph) на картці.
- В inventory — розділ «Relics of Our Side» або просто окрема category.

## 5. Ризики (downside)

- **Жанровий дисонанс.** Частина аудиторії чекає чистого fantasy/sci-fi.
  Apple Watch у монстра — може здатись jarring. Мітигація: баланс —
  cross-world drop rare (<10%), не щоразу.
- **Brand-references тонкощі.** «Apple Watch» як item-name може бути
  проблемою з Apple IP. Мітигація: використовувати generic «smartwatch
  (rift-touched)» в назвах, але дозволяти artistic references у flavor.
- **Production cost на flavor.** Кожен item потребує flavor-text, ідеально
  localized. 20-30 cross-world items × 2 мови = 40-60 коротких story-cards.
- **Can feel gimmicky.** Якщо drop-rate надто високий або story-тексти
  поверхневі → відчуття «loot-joke». Мітигація: гатькана curated list,
  кожен story має hook.

## 6. Відкриті питання

- Чи можна дизайнити cross-world items яко **collection** — 30 штук,
  колекційна ачівка «Collected all 30 cross-world relics»?
- Чи повертати ці items у real-life store — «RPGFit cross-world fridge
  magnet set» як merch post-launch?
- Взаємодія з **Portal Creation Kit** (див. `onboarding-gifts.md`) —
  якщо юзер ставить портал у своєму домі, чи може «свій» загублений
  предмет з'явитися на мобові?
- Легальність щодо реальних брендів (McDonald's упаковка, Nike кросівки).
  Мітигація: лише generic + fictional brands.

## 7. Пов'язаність з іншими рішеннями

- **D5 (social events):** cross-world items — ідеальний share-content.
  Event «Тиждень Дивовижних Речей» де drop-rate cross-world +3х.
- **Mental-stats idea (в research):** деякі cross-world items логічно
  впливають на WIS/INT (книга, USB). Синергія.
- **Portals (D2):** static portals можуть мати унікальні cross-world loot
  (Галдхьопіген — норвезький подорожній наплічник, Теотіуакан — старий
  турист-путівник іспанською).

## 8. Наступні кроки

1. BA-агенти (02/03) — оцінити, чи входить у beta-scope чи post-beta.
2. Content-team — зібрати список 20-30 cross-world items з flavor.
3. Backend — додати `ItemType::CrossWorld` і окрему loot-table logic
   (після sign-off).
4. Design — підготувати real-world style icon-pack для UI (фото-силуети,
   не glyph).

## 9. Посилання

- `docs/vision/mobs.md` — базовий mob-design
- `docs/vision/portals.md` — de-escalation з порталами
- `docs/vision/emotional-hooks.md` §6-7 (колекція, таємниця)
- `rpgfit-beckend/docs/BUSINESS_LOGIC.md §10, §11` — мoби, loot
