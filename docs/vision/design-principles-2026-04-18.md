# Design Principles — 2026-04-18 (mass-audience review)

> **Джерело:** ChatGPT review (shared 2026-04-18) — критика дизайну UI-гри для
> масової аудиторії (casual→midcore). Фаундер підтвердив цей підхід як
> **обов'язковий для всіх варіантів RPGFit**.
> **Статус:** APPROVED — застосувати до 06, 07 негайно. 01-05 — при наступному
> рев'ю.

---

## Чому ці принципи важливі

RPGFit — **assistant first, game second**. Користувач — не hardcore gamer,
а людина що хоче стати кращою. Тому UI має бути:
- миттєво зрозумілим (не вимагати «розбиратись»)
- комфортним на 20+ хвилин (не втомлювати очі)
- мати ясну точку входу на кожному екрані («куди дивитись першим»)
- приглушеним за замовчуванням, з акцентами **лише на дії**

---

## Червоні правила (обов'язкові)

### 1. Один колір — одна роль
- **CTA колір (у 06 — violet-pink gradient; у 07 — copper `#D97B3E`) —
  ТІЛЬКИ для основної дії екрану.**
- НЕ використовувати цей колір для section-labels, tags, accent-numbers,
  borders. Для цього — cream / navy / muted-sage.
- Результат: коли юзер бачить copper/violet — він знає, що це **клікабельно**.

### 2. Текст ×1.5 для secondary content
- Raw numbers що з даних: bump з `--fs-10` (10px) → `--fs-11` (11px).
- Labels під великими числами: `--fs-11`.
- Body copy: ≥ `--fs-13`.
- Subtitle (під h1): ≥ `--fs-12`.
- **Ніколи** не `--fs-8` чи `--fs-9` для контенту який юзер має **прочитати**.
  Тільки для serial-numbers, watermark, chip-codes (decorative).

### 3. Hierarchy per screen: 1 + 2-3 + resto
- **1 головний блок** (hero) — найбільший, акцентований
- **2-3 secondary blocks** — panel-sized, читабельні
- **Решта (tertiary)** — приглушені, муте-колір, маленькі

Приклад для Battle-екрану:
- HERO = mob-card (великий, центральна панель, з crosshair)
- SECONDARY = HR pulse panel + set-complete damage panel
- TERTIARY = status-bar, footer tabs

### 4. Повітря > щільність
- `padding: 16-20px` для головних панелей (не 10-14)
- `gap: 12-16px` між панелями (не 6-8)
- `padding` навколо hero-числа ≥ 20px

### 5. Уникати overwhelm
- Максимум 3 анімації одночасно на екрані (одна continuous, дві peak-only)
- Максимум 2 різні неон-accent-кольори на один екран
- Максимум 4 рівні ієрархії (h1, section, body, caption)

---

## Чого уникати (red flags з review)

- ❌ Acc-колір скрізь (CTA тоне)
- ❌ Дрібний текст для контенту (<12px для читання)
- ❌ Усі блоки однакового розміру (немає "входу")
- ❌ Перевантаження неоном (4+ світяться одночасно)
- ❌ "Все технічне, нічого людського" — потрібне emotional warmth
- ❌ Розказати все на одному екрані (краще 2 екрани прості ніж 1 складний)
- ❌ Constant animation (glitch-flicker, shimmer, pulse + все разом)

---

## Що залишаємо (що працює з review)

- ✅ Великий display number в hero (2042, +3, 60%)
- ✅ Card structure (модулі, не monolithic layout)
- ✅ Visual rhythm (повторювані patterns — crosshair, section-label, panel-sm)
- ✅ Консистентна палітра (один brand-feel на весь app)

---

## Вплив на 06 Neo Glass

**Потрібно виправити:**
- `.mini-label` з `rgba(245,242,255,0.72)` — **OK** (bumped у попередньому кроку)
- CTA `btn-gradient` — єдиний CTA ✓
- Hero на Battle — `mob-card-rift` ✓
- **Але:** надто багато violet-accent на tab-icon-active, ring-progress,
  shimmer. Залишити violet на: CTA gradient + ring progress value + 1
  hero-glow. Прибрати з tab-icon (зробити cream muted).
- Animation count: HR-beat + shimmer + orbit + pulse-soft = 4 одночасно.
  **Зменшити:** HR-beat (keep), shimmer on legend only (keep), orbit
  (drop), pulse-soft на map-dots (keep з reduced-motion off).

## Вплив на 07 Vector Field

**Потрібно виправити:**
- `.section-label` зараз **copper** — це cannibalize CTA. **Зробити cream
  muted** (`--fg-muted` або темніший navy) — щоб copper світився тільки на
  кнопці.
- Секвенція на Battle-екрані: 4 panels рівнозначні. **Hierarchy:**
  mob-card (hero, великий, crosshair) → HR-panel (secondary) → damage-panel
  (secondary) → CTA (primary action).
- `font-size: var(--fs-10)` на subtitle — занадто дрібно. Bump до `--fs-12`.
- Tag з copper-bg — використовується на багато чого. Рестриктувати:
  `tag.copper` тільки для rupture-class indicator (1-2 на екран).
- Anim count: зараз `ecg-sweep` + `warn-pulse` (якщо лишиться) +
  crosshair fade. OK, не overload, keep.

---

## Наступні кроки

1. Застосувати ці principles до 07 Vector Field (CTA rule + text scale
   + section-label muted).
2. Зробити аналогічні правки в 06 Neo Glass (zoom out violet accent, 1
   CTA rule).
3. Оновити `shared/base.css` з text-scale-minimum guide.
4. Додати в `design/README.md` розділ "Mass-audience принципи" з посиланням
   на цей документ.
