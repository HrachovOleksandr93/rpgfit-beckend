# 08 — Mental Stats from Mindfulness Health Data: BA Research

> **Agent:** BA (standalone scoping + market-research hybrid).
> **Date:** 2026-04-18.
> **Input:** `docs/vision/mental-stats-mindfulness.md` (founder draft).
> **Goal:** answer the four research areas + give founder three framed options to
> decide IN-beta / post-beta / REJECT.
>
> **Source markers:**
> - **[verified URL 2026-04-18]** — fact verified by WebSearch in this session
>   (URL list in §6).
> - **[assumption — needs verification]** — my inference, not a verified fact.
> - **[from 01/02/product-decisions]** — grounded in earlier RPGFit artifacts.
>
> **Scope discipline:** this report covers ONLY the mindfulness-to-mental-stats
> idea. It does not re-litigate D3 (no factions), D4 (all-human), D5 (social
> events elevated).

---

## TL;DR (5 bullets)

1. **Platform coverage is asymmetric and unreliable.** iOS HealthKit exposes
   `mindfulSession` (iOS 9+), `sleepAnalysis` with REM/Deep/Core stages, HRV
   (SDNN), `handwashingEvent` (Apple Watch S4+ only), and `StateOfMind` (iOS
   17+) [verified URL 2026-04-18]. Android Health Connect only added
   `MindfulnessSessionRecord` with **Android 16** (late 2025); sleep stages are
   supported but population is uneven across OEMs [verified URL 2026-04-18].
   Net: **we can not ship symmetrical mental-stats on Android for beta** —
   anything mindfulness-flavored will be iOS-leaning or require manual log.
2. **Market precedents validate the mechanic, not the split.** WHOOP
   Recovery, Oura Readiness, Fitbit Stress Management all collapse
   sleep + HRV + resting HR + respiration into **one score** (0-100 or
   green/yellow/red), not four stats [verified URL 2026-04-18]. No mass-market
   app has shipped WIS/INT/WIL/FOC separation. Finch (virtual-pet self-care)
   and SuperBetter (gamified CBT) both succeed by **compassionate, non-punitive
   design** — this is a hard UX constraint, not a suggestion.
3. **Gamifying meditation carries a specific reputational risk.** Academic
   and journalistic critique (`McMindfulness`, Purser) and Habitica research
   (Diefenbach & Müllensiefen 2019) show that **gamified self-care works for
   initial engagement but decays and can become counterproductive when it
   creates guilt** [verified URL 2026-04-18]. RPGFit's existing red-line
   rules (`emotional-hooks.md`) — no streak-loss penalty, no FOMO on daily
   — are the right guardrails and must extend to any mental-stats system.
4. **Four stats is too many for beta.** Current beta already has STR/DEX/CON,
   15 HealthKit types, 48 professions × 3 tiers, 39 skills, realm/portal/mob
   stack, Day of Rozkolу launch event. Adding WIS/INT/WIL/FOC = battle-formula
   rebalance + 4 new UI surfaces + 4 new onboarding explanations. 02-beta-scope
   already cut 6-realm faction identity to avoid onboarding bloat [from
   product-decisions D3]. Same logic applies here.
5. **Recommendation: Option B — MINIMAL IN-BETA (sleep→recovery buff/debuff
   only).** One new mechanic (`RecoveryState` entity, derived from existing
   sleep data already in BL §3), no new stats, no new UI category, ~2 dev-weeks.
   Reserves the richer WIS/INT/WIL/FOC concept for a post-beta "Inner Strength"
   expansion where it can be designed, balanced, and localised without
   blocking 31.10. Full argumentation in §5.

---

## 1. Research area 1 — Platform data coverage (technical feasibility)

### 1.1. iOS HealthKit — mindfulness-category data types

| HealthKit identifier | iOS version | Device dependency | Who writes to it |
|----------------------|-------------|-------------------|-------------------|
| `HKCategoryTypeIdentifier.mindfulSession` | iOS 9.0+ [verified URL 2026-04-18] | iPhone (any); Watch Mindfulness app amplifies | Apple Mindfulness, Calm, Headspace, Insight Timer, Balance, many smaller apps [verified URL 2026-04-18] |
| `HKCategoryTypeIdentifier.sleepAnalysis` with stages (`asleepCore`, `asleepDeep`, `asleepREM`, `asleepUnspecified`, `awake`, `inBed`) | Stages added in iOS 16 (2022) [verified URL 2026-04-18] | Apple Watch S3+ needed for auto-tracked stages; iPhone can store manual entries | Apple Sleep, AutoSleep, Sleep Cycle, Oura (via HealthKit bridge), WHOOP, Pillow |
| `HKQuantityTypeIdentifier.heartRateVariabilitySDNN` | iOS 11+ | Apple Watch only for passive collection [verified URL 2026-04-18]; no way to trigger manual measurement from 3rd-party app | Apple (Breathe app), Welltory, HRV4Training, Elite HRV |
| `HKQuantityTypeIdentifier.respiratoryRate` | iOS 13+ | Apple Watch S3+ (sleep mode) | Apple, some 3rd-party sleep apps |
| `HKCategoryTypeIdentifier.handwashingEvent` | iOS 14 / watchOS 7 | **Apple Watch Series 4 or higher ONLY** [verified URL 2026-04-18] | Apple only; no meaningful 3rd-party write ecosystem |
| `HKStateOfMind` (valence + labels: happy, anxious, drained, grateful, etc.) | iOS 17+ (mid-2023) [verified URL 2026-04-18] | Works on iPhone alone; Watch logging in watchOS 10.1+ | Apple Health journaling; limited 3rd-party write ecosystem (very new, `[assumption — ecosystem still thin as of 2026-04]`) |

**Key iOS gaps for RPGFit:**
- HRV is **Apple-Watch-only** for passive collection; iPhone-only users will
  have no data. 3rd-party apps cannot trigger a measurement — they can only
  read what Apple already pushed [verified URL 2026-04-18]. Any WIL mechanic
  gated on HRV excludes all iPhone-without-Watch users.
- Handwashing requires Apple Watch Series 4+; excludes all non-Watch users
  and legacy Series 1-3 owners. A FOC mechanic tied to handwashing would be
  silently dead for ~60% of iOS install base `[assumption — based on rough
  Apple Watch penetration 40% of iPhone owners]`.
- State of Mind requires iOS 17+. As of 2026-04 this is probably 80-90%+ of
  active iOS base `[assumption — Apple typically 80-90% adoption 2 years
  post-release]`, so the gap is narrowing but still present.

### 1.2. Android Health Connect — mindfulness equivalents

| Health Connect record | Android / HC version | Status | Who writes to it |
|----------------------|----------------------|--------|-------------------|
| `MindfulnessSessionRecord` (types: meditation, yoga, breathing, stretching, music, unguided) | **Added in Android 16** (late 2025) [verified URL 2026-04-18] | `FEATURE_MINDFULNESS_SESSION` must be feature-checked at runtime. Older Android = missing | Very few apps have migrated yet `[assumption — ecosystem <1 year old]` |
| `SleepSessionRecord` with stages (UNKNOWN, AWAKE, SLEEPING, OUT_OF_BED, AWAKE_IN_BED, LIGHT, DEEP, REM) | Health Connect 1.0+ | Stages support is optional; many writers skip stages | Samsung Health, Google Fit, Fitbit, Sleep as Android, Oura, Lifesum, Withings [verified URL 2026-04-18] |
| `HeartRateVariabilityRmssdRecord` | Health Connect 1.1+ | Supported | Samsung Health, Oura, WHOOP (via HC bridge). **Wear OS has very thin HRV story** vs Apple Watch. |
| `RespiratoryRateRecord` | Supported | — | Samsung Health, Oura, WHOOP |
| Handwashing equivalent | **None** [verified URL 2026-04-18] | — | N/A |
| State-of-mind equivalent | **None** [verified URL 2026-04-18] | — | N/A |

**Key Android gaps for RPGFit:**
- `MindfulnessSessionRecord` is brand new (Android 16, late 2025). Of users
  with older Android versions or OEM skins slow to update, the API is simply
  missing. `FEATURE_MINDFULNESS_SESSION` runtime check required.
- No handwashing data type at all.
- No state-of-mind data type at all.
- Health Connect itself had ~50 integrated apps as of 2023 announcement
  [verified URL 2026-04-18]; mindfulness-write ecosystem is thinner than
  sleep-write.

### 1.3. Gap analysis iOS vs Android (summary table)

| Stat (as proposed) | Data source | iOS coverage | Android coverage | Shippable symmetrically? |
|-------------------|-------------|---------------|-------------------|---------------------------|
| **WIS** (sleep quality + mindful minutes) | `sleepAnalysis` + `mindfulSession` / `SleepSessionRecord` + `MindfulnessSessionRecord` | Good (iOS 16+ for stages) | **Partial** — sleep OK, mindfulness only Android 16+ | **No** (Android 16 gap) |
| **INT** (REM sleep + breathing rate stability) | `sleepAnalysis.asleepREM` + `respiratoryRate` / `SleepSessionRecord.REM` + `RespiratoryRateRecord` | Watch-only for respiration | Requires sleep-tracker with REM + respiration writer | **No** (Watch + Wear-OS thin) |
| **WIL** (mindfulness streaks + HRV) | `mindfulSession` + `heartRateVariabilitySDNN` / equivalents | **Watch-only** for HRV | **Wear-OS thin for HRV** | **No** (heavy Watch/Wear dependency) |
| **FOC** (handwashing + hydration) | `handwashingEvent` (no Android parallel); water intake exists both sides | Apple Watch S4+ only | None for handwashing | **No** (platform-exclusive) |

**Conclusion:** the full 4-stat proposal cannot ship with parity on Android
for beta. A cut-down "sleep→recovery" mechanic (using only
`sleepAnalysis` and `SleepSessionRecord`, which are both well-populated) is
the only thing that works symmetrically today.

### 1.4. Privacy / consent UX intrusiveness

- iOS HealthKit: users grant **per-data-type** permissions; the share sheet
  lists each requested type (Mindful Minutes, Sleep, HRV, etc.)
  [verified URL 2026-04-18]. Adding 5+ mindfulness types to the existing 15
  physical types = **20+ checkbox list**, which will feel intimidating and
  suppresses grant rates. Specific grant-rate data not available in this
  search `[assumption — community figure of 10-20% refusal cited in 01:§5
  still holds; adding sensitive data types likely worsens]`.
- Health Connect: similarly per-type with `READ_MINDFULNESS`,
  `WRITE_MINDFULNESS`, sleep, HRV separate permissions.
- Sensitive-data category: HRV, Sleep, State-of-Mind land in the
  "extra-sensitive" bucket (mental health data). HIPAA-like jurisdictions
  (US, UK, EU member states) treat these as special-category personal data
  under GDPR Art.9 `[assumption — based on general GDPR knowledge; needs
  legal review]`. RPGFit's existing GDPR posture handles 15 physical types;
  adding mental-health types requires an **updated Privacy Policy + DPIA
  (Data Protection Impact Assessment)**.
- UX cost: Apple's App Review historically flags health apps that "request
  more permissions than needed for core function". Requesting HRV +
  State-of-Mind + Handwashing for a fitness-RPG could draw App Review
  questions. Mitigation: request **only what you use right now**, defer
  others.

---

## 2. Research area 2 — Market precedents

### 2.1. Fitness-mental-health hybrids (professional / wearable)

| Product | Mental-data use | Gameplay / gamification | Key lesson for RPGFit |
|---------|-----------------|--------------------------|------------------------|
| **Oura Readiness Score** | 9 contributors: sleep quality, HRV balance (14-day weighted), body temp, RHR, activity balance, previous-day movement [verified URL 2026-04-18] | Single 0-100 score, color-coded | **Do not split into 4 stats.** Oura users already find one score hard to interpret; the industry has converged on single recovery metric. |
| **WHOOP Recovery Score** | 4 inputs: sleep (~10%), HRV (~70%), RHR (~20%), respiratory rate; calculated during deepest sleep [verified URL 2026-04-18] | Green/yellow/red, affects daily "strain target" recommendation | **HRV dominates recovery math.** Any stat gated on HRV will skew towards Apple Watch hardcore users — not the casual RPGFit audience. |
| **Fitbit Stress Management Score** | HRV + exertion + sleep → 1-100 score. Premium gets detailed breakdown; free sees only the number [verified URL 2026-04-18] | Part of larger Premium subscription; linked to 400+ mindfulness sessions | **Single score, paywalled depth.** Fitbit tried splitting and collapsed to one score before shipping. |
| **Samsung Health stress** | Continuous HRV-driven stress level, prompts breathing exercise when elevated [verified URL 2026-04-18] | Challenges/teams gamification for activity, NOT for mindfulness | Samsung's choice to gamify activity but NOT mental-health is a tell — they avoided the reputational risk. |
| **Apple Fitness+ × Strava (Jan 2025)** [verified URL 2026-04-18] | Workouts sync, but Calm meditation content is a **theme inside Fitness+**, NOT scored or gamified | Social share of activity, not of mindfulness | Industry pattern: mindfulness is **content layer**, not a scored stat. |
| **Strava + Oura / Open (Mar 2024)** [verified URL 2026-04-18] | Imports readiness / mindfulness as context, not as score impacting placement | Athletes see readiness alongside training, but KOM remains purely physical | Confirms physical leaderboards stay physical; mental data is a contextual overlay. |
| **Strava Meditation activity type** | Requested by community 2023-24, not shipped; Strava staff response: "researching" [verified URL 2026-04-18] | Community wants it, Strava deliberately cautious | Even the category leader in fitness gamification is treading carefully. |

**Pattern:** every major player collapses mental-health signals into **one
score**, never into four separate stats. WIS/INT/WIL/FOC split is a D&D
projection that no health-tech product has validated.

### 2.2. Gamified mental-health apps (direct precedents)

| Product | Model | Scale / evidence | Design lesson for RPGFit |
|---------|-------|------------------|--------------------------|
| **Habitica** | Todo-list = quest; miss a habit = HP damage; party raid bosses | Studied in Diefenbach & Müllensiefen 2019 [verified URL 2026-04-18]: **only 49% rate reward system as appropriate**, all participants reported some counterproductive effect. | **Punitive mechanics (HP loss for missed habit) backfire.** RPGFit's existing "no streak-loss penalty" rule is right. |
| **Finch (Self-Care Pet)** | Virtual bird grows via self-care tasks (breathing, journaling, mood check-in) | Top-grossing self-care app 2023-25 (retained users months+); designed around **"compassionate tech"** — missed days do NOT punish, only encourage return [verified URL 2026-04-18] | **Compassionate framing is the moat.** If RPGFit adds mental mechanics, missed meditation must never reduce a stat — only bonus disappears. |
| **SuperBetter** | Challenges + power-ups + allies, CBT-framed | Penn / Ohio State RCTs: 30 days of SuperBetter → significant reduction in depression/anxiety; scored 76/100 on Health Behavior Theory inclusion (avg app = 15) [verified URL 2026-04-18] | Evidence exists that RPG-framing of mental health can work clinically, but **the "power" comes from CBT content, not gamification alone** (meta-analysis caveat in same source). |
| **Insight Timer** | Meditation library, meditation-only | D30 retention 16% — nearly double Calm/Headspace (~8.5%) [verified URL 2026-04-18] | Simplicity + community > advanced gamification for retention. |
| **Mindfulness apps overall** | Various | Median D30 retention 4.7% (mindfulness category); 80%+ open-rate drop D1→D10 [verified URL 2026-04-18] | Mental-health / mindfulness retention is **structurally weak**. Tying RPGFit progress to this signal risks inheriting their churn. |

### 2.3. Backlash / "gamifying meditation" criticism

Multiple mainstream critiques of gamified mindfulness as "spiritual capitalism"
and "McMindfulness":
- Ronald Purser, "Mindfulness Is a Capitalist Scam" (Vice interview)
  [verified URL 2026-04-18].
- Multiple pieces in Psyche / Fast Company / CBC Radio on corporate
  co-optation of meditation [verified URL 2026-04-18].
- Key critiques directly relevant to RPGFit:
  1. **Self-surveillance / monitoring anxiety** — constantly scoring your
     mental state erodes the actual practice.
  2. **Competitive rewards are incompatible with meditation's ethical frame**
     — awards/badges for "minutes meditated" contradict the practice itself.
  3. **Deflection from structural causes** — gamifying stress-relief implies
     stress is the user's individual problem to solve.

**Mitigation if RPGFit ever ships mental mechanics:**
- Never show a "mindfulness leaderboard" or public meditation-minute compare.
- Frame as "Inner Strength" / "Clarity" (founder's note in
  `mental-stats-mindfulness.md §4` already anticipates this) — not "WIS
  stat went up +1 you outclass 40% of Asgard".
- Make mental-stat contribution **opt-in**, separate toggle from physical.
- No penalty mechanic ever.

### 2.4. Market size (TAM expansion argument)

- Global meditation-apps market: $1.6B in 2024 → projected $2.25B in 2025,
  CAGR 18.5% [verified URL 2026-04-18].
- Global mental-health-apps market: $7.22B in 2024 → $8.54B in 2025 → $33.5B
  by 2035 (CAGR 14.7%) [verified URL 2026-04-18].
- Calm: ~4M paying subscribers as of 2024, $7.7M monthly in-app revenue
  (Jan 2024) [verified URL 2026-04-18].
- Headspace: ~$4M monthly (Jan 2024) [verified URL 2026-04-18].

**Implication:** there IS a real addressable audience for a "fitness + mental
wellness" hybrid, and the category is growing faster than fitness-only. But:
- The existing players (Calm, Headspace, Insight Timer) own the mindfulness
  brand; RPGFit can not out-Calm Calm.
- The realistic TAM-expansion hook is **"rest days matter"** — retain
  existing fitness-users on non-training days, not acquire meditation-first
  users.

---

## 3. Research area 3 — Gameplay design

### 3.1. Does 4 stats (WIS/INT/WIL/FOC) add or reduce complexity?

**Current beta onboarding load:**
- Character race pick — **cut** (D4).
- Realm faction identity — **cut** (D3).
- 3 physical stats (STR/DEX/CON) — shipped.
- 15 HealthKit data types — shipped.
- Portals, mobs, professions, skills, equipment slots, battle modes — all
  shipped per BUSINESS_LOGIC.

Beta onboarding has been deliberately trimmed twice (D3, D4) to reduce the
number of "picks and concepts" the new user must absorb in Opening 60s +
first session.

**Adding WIS/INT/WIL/FOC means:**
- 4 new stat UI cells (profile screen + character sheet).
- 4 new level-up animations and contribution explanations.
- 4 new battle-formula coefficients to balance.
- 4 new "gateway" explanations in onboarding (or a new "mental tab" users
  discover later, adding a new unlock moment — see founder's own note in
  `mental-stats-mindfulness.md §4`: "показувати поступово, unlock на 3-4
  level").
- Probable scope conflict with existing character progression design.

**Verdict:** 4 stats **adds complexity**, reversing the recent trim strategy.

### 3.2. Single "Mind" stat vs full split

**Single-stat model (industry-standard, per §2.1):**
- `mentalRecovery` 0-100, derived from sleep + mindful minutes + HRV (if
  available) — mirrors WHOOP Recovery, Oura Readiness, Fitbit Stress.
- Maps to **one** gameplay effect: "recovery rate between battles" or
  "stamina regen".
- Can ship on iOS with partial signal (fall back to sleep-only when no
  mindful data).
- Can ship on Android 16+ with similar fallback; older Android = sleep-only.

**Four-stat split (founder's proposal):**
- 4 × gameplay effects, 4 × UI, 4 × balance work.
- Mechanical differentiation (WIS ≠ INT ≠ WIL ≠ FOC) is D&D-native but has
  no validated parallel in health-tech.
- Equipment tier gating by WIS (founder's example: Trishula requires WIS ≥15)
  is **pay-to-win by workout-type** — it forces users who don't meditate to
  meditate to wear certain gear. That's a grind-wall dressed as gameplay.

**Verdict:** single "Recovery" / "Clarity" stat is **more defensible** both
technically (partial data works) and reputationally (no forced meditation).

### 3.3. Balance interaction with battle formula and D5 social events

- Battle damage formula (BL §9) is balanced around STR/DEX/CON + Performance
  Tier (Bronze/Silver/Gold/Platinum). Adding 4 mental stats requires reworking
  damage coefficients, profession-skill interactions, mob resistance
  formulas, AND testing that mental-heavy and physical-heavy builds both
  remain viable. **This is a >1-month balance pass**, not shippable for
  31.10.
- D5 social events (Day of Rozkolу launch + mid-beta event): current design
  is around physical-damage contribution to shared bosses (`feed-of-activity`,
  `active-players counter`, co-op bonus). Mental stats would need separate
  "mental-damage" mobs (founder's Asgard "Whisper God" / Duat "Sleep Guard"
  example) to matter — which requires new mob subtypes, new art, new flavor
  strings. That's content scope creep on top of the 2000-mob realm-mapping
  already in beta plan.
- **Verdict:** battle + D5 scope is already saturated for beta. Mental stats
  must NOT enter the battle-formula for beta.

### 3.4. MVP cut-down: sleep → recovery only

The minimum viable slice that:
- Uses data already in BL §3 (`SLEEP_ASLEEP`, `SLEEP_DEEP`, `SLEEP_LIGHT`,
  `SLEEP_REM` — already awarding 5 XP/hour capped at 10h).
- Ships symmetrically on iOS and Android (sleep is well-populated both).
- Doesn't require new stats, new UI category, new permissions.
- Doesn't add a new onboarding pick.

= "**Rest Recovery buff/debuff**" mechanic:
- `RecoveryState` entity (Daily snapshot).
- Input: sleep hours from existing HealthKit/HC sync.
- Output: multiplier on next-day stamina / battle damage.
  - <5h sleep → "Fatigued" debuff, −20% stamina regen, 24h. Flavor: "Вектор
    чує втому. Молот важчий сьогодні."
  - 5-7h → neutral (no buff, no debuff).
  - 7h+ REM > 60min → "Rested" buff, +10% XP on next battle, 24h.
- No new stat to display, no new numeric UI. Shows up as a status icon on
  character (existing UI slot) + one push notification at 8am.

This is the floor of the proposal that actually delivers on the founder's
core intuition ("retention-драйвер для пасивних днів") without any of the
scope cost of the full WIS/INT/WIL/FOC system.

### 3.5. Onboarding treatment if shipped

If Option B (§5) is picked, the onboarding change is **zero new picks**.
The existing sleep-data permission (already requested) implicitly unlocks
Recovery. First time a user sees the status icon, a tooltip explains:
> "Вектор зчитує твій БРП-стан. Сон — частина системи."

No new concept to teach in Opening 60s.

---

## 4. Research area 4 — Business / scope

### 4.1. Beta fit (31.10.2026) vs post-beta

Re-reading `02-beta-scope.md §5 (7-day retention plan)` and §8 (content
scale): the current IN-list is already 15 features that must ship by 31.10.
`03-roadmap.md` and `product-decisions-2026-04-18.md §D6` have already
trimmed to focus on Day of Rozkolу + supporting infra.

**Adding Option C (full 4-stat system) would:**
- Add estimated 4-6 dev-weeks backend (4 entities, 4 formula passes,
  migration, seed command).
- Add 2-3 dev-weeks RN (4 UI cells, new mental tab, onboarding slot).
- Add 2+ weeks balance + QA.
- Add 1-2 weeks copy + translation (UA/EN).
- Add legal review for mental-health-data DPIA.
- **Total: 8-12 dev-weeks from a cold start.** We have ~27 weeks to 31.10,
  but 02-scope already allocates them.

**Adding Option B (sleep→recovery only):**
- 3-5 days backend (1 entity, 1 service, 1 migration, 1 push rule).
- 2-3 days RN (1 status icon + 1 tooltip + 1 push notification).
- 2-3 days balance + QA.
- ~0 extra copy (single flavor line, UA/EN).
- Legal: no new permission, no new DPIA trigger.
- **Total: ~2 dev-weeks.** Fits into a spare sprint.

### 4.2. Rough dev-weeks estimates

| Option | Backend | RN | Balance / QA | Copy / i18n | Legal / privacy | **Total** |
|--------|---------|-----|---------------|--------------|-------------------|-----------|
| **A: REJECT/PARK** | 0 | 0 | 0 | 0 | 0 | **0 weeks** |
| **B: MINIMAL (sleep→recovery)** | 3-5 d | 2-3 d | 2-3 d | ~0 | 0 | **~2 weeks** |
| **C: FULL post-beta** | 4-6 w | 2-3 w | 2+ w | 1-2 w | 1 w | **~8-12 weeks** |

### 4.3. TAM expansion estimate

- Fitness-app global users: hundreds of millions (Strava alone 135M
  registered, §01 market research).
- Meditation-app users: Calm ~4M paying + Insight Timer ~25M registered
  `[assumption — Insight Timer often cited at 25M, not verified this session]`.
- Overlap with fitness users: significant but hard to measure.
- **RPGFit realistic TAM lift from mental stats:** NOT "acquire Calm users"
  (they won't switch meditation primary). Instead, **retention of existing
  RPGFit users on rest days**. Founder's intuition in vision doc §3 is
  correct about this. TAM doesn't expand meaningfully; **retention on
  non-workout days** is the real lever.

### 4.4. Monetization (premium tier?)

- F2P manifesto (`02-beta-scope.md §7`) forbids any SKU with stat-advantage.
  Mental stats must be inside that rule — cannot sell "Inner Strength Boost".
- Legitimate monetization hooks post-beta:
  - Cosmetic skin for "Calm state" aura.
  - Extended historical graph for mental trends (Fitbit Premium pattern —
    free sees number, premium sees trend breakdown).
  - Partner content (if a Calm/Headspace/Oura partnership ever emerges —
    post-beta, not before).
- For beta: **no monetization attached to mental stats.** Same as the rest
  of IN-scope.

---

## 5. Final — Recommendation

### 5.1. Three options framed

#### Option A — REJECT / PARK

- Do nothing in beta and post-beta roadmap. Sleep XP stays as-is (simple
  per-hour XP with cap), no Recovery state, no mental stats.
- **Pros:** zero scope cost; zero reputational risk; founder's "holistic
  wellness" story can still be told via marketing without ships.
- **Cons:** misses the genuine retention opportunity on rest-days; loses a
  differentiator vs fitness-only competitors; fails to cash in on growing
  mental-health TAM.
- **When to pick:** if 31.10 is at risk and zero extra features can be
  absorbed.

#### Option B — MINIMAL IN-BETA (sleep → recovery only)

- Ship one mechanic: `RecoveryState` buff/debuff derived from existing
  sleep data. No new stats. No new UI tab. No new permissions. 2 dev-weeks.
- Flavor-wrap as "Vector sensing your БРП state" — integrates with existing
  lore, no mindfulness framing.
- Reserves WIS/INT/WIL/FOC rich design for post-beta "Inner Strength"
  expansion, where it can be designed and balanced properly.
- **Pros:** delivers on founder's core intuition (retention driver for
  passive days); symmetric across iOS/Android; no onboarding bloat;
  respects all existing red lines (no grind, no FOMO, no monetization
  gate); minimal scope risk.
- **Cons:** doesn't fully express the mental-RPG vision; may disappoint
  D&D-minded players who want the 4-stat split; narrative-light for the
  "Calm audience" TAM argument.
- **When to pick:** default recommendation for beta.

#### Option C — FULL POST-BETA PROGRAM

- Keep beta clean of mental stats (ship Option B at most), then in a
  post-beta season (Q1 or Q2 2027) launch "Inner Strength" with 2-4 mental
  stats, dedicated UI, balance pass, and optional Calm/Headspace/Oura
  partnership.
- Give the team space to do this right: 8-12 dev-weeks + user testing +
  legal DPIA + copywriter for mindful framing without grind/FOMO.
- **Pros:** full expression of vision; leverages learnings from beta data
  and community feedback; potential partnership / TAM play.
- **Cons:** requires commitment in roadmap now to avoid becoming "P2 that
  never ships"; still carries mindfulness-gamification reputational risk
  that must be managed with UX.
- **When to pick:** post-beta planning horizon; NOT a beta decision.

### 5.2. Recommended option: **B (MINIMAL IN-BETA) + explicit "Inner Strength" post-beta slot (Option C as Q1-2027 commitment)**

**Rationale:**
1. Platform coverage (§1) forbids 4-stat symmetry for beta. Android
   `MindfulnessSessionRecord` is too new, HRV and handwashing are
   Apple-Watch-exclusive. Shipping 4 stats would mean iOS-Watch-users get
   full game, everyone else gets a crippled version. That violates the
   "everyone progresses" F2P posture.
2. Market precedents (§2) show that single-score recovery is the
   industry pattern. No mass-market app has validated the 4-stat split.
   RPGFit is not the place to invent that category while also shipping a
   31.10 beta.
3. Gameplay (§3) is saturated. Battle formula, onboarding, social events
   are already fully scoped. Adding 4 stats = rebalance everything.
4. Business (§4) math is clear: Option B is 2 weeks; Option C is 8-12
   weeks. 2 weeks is absorbable; 8-12 is not.
5. Option B still delivers the founder's strongest argument: "гравець що
   тренується і спить 7–8 год, отримує більше XP, ніж той, що спав 4 год"
   (`emotional-hooks.md §Grind-контроль`) — this is already in the
   game's spirit; making it visible via a buff/debuff status finally
   closes that loop.
6. Deferring WIS/INT/WIL/FOC to Option C (post-beta slot) lets the team
   observe D7/D30 beta retention on the recovery mechanic alone, then
   invest in the full split only if the data supports it. This is the
   low-regret sequencing.

### 5.3. Concrete decision package for founder

- **IN beta (Option B):** one `RecoveryState` mechanic per §3.4.
  Owner: backend (Health BC) + RN (status icon). Scope 2 dev-weeks.
  Copy: "Втомлений" / "Відпочилий" flavor. No new permissions.
- **POST-beta slot reserved (Option C):** "Inner Strength" expansion
  Q1/Q2-2027. Pre-work: observe beta data on rest-day engagement; run
  2-3 user interviews with fitness+meditation overlap audience; brief
  legal on mental-health DPIA; draft framing ("Clarity" / "Inner
  Strength", NOT "WIS stat"). Go / no-go decision after 60 days of
  beta data.
- **OUT of consideration for beta:** any mechanic gated on HRV,
  handwashing, State-of-Mind, or streak-based mindfulness. These are
  post-beta-only conversations.

### 5.4. What to tell the founder in 2 sentences

> "The 4-stat split is a great long-term idea but Android's Mindfulness
> API is too new for beta parity, and industry (WHOOP/Oura/Fitbit) has
> converged on a single recovery score. Ship sleep→recovery buff/debuff
> for beta (2 dev-weeks, no new UI category), reserve 'Inner Strength'
> for a Q1-2027 post-beta expansion after we see if rest-day retention
> actually lifts."

---

## 6. Sources (web-verified 2026-04-18)

### HealthKit / Health Connect (platform coverage)

- [mindfulSession — Apple Developer Documentation](https://developer.apple.com/documentation/healthkit/hkcategorytypeidentifier/mindfulsession) — iOS 9.0+ availability.
- [HKStateOfMind — Apple Developer Documentation](https://developer.apple.com/documentation/healthkit/hkstateofmind) — iOS 17+ introduction.
- [Explore wellbeing APIs in HealthKit — WWDC24](https://developer.apple.com/videos/play/wwdc2024/10109/) — State of Mind, GAD-7, PHQ-9 APIs.
- [handwashingEvent — Apple Developer Documentation](https://developer.apple.com/documentation/healthkit/hkcategorytypeidentifier/handwashingevent) — category sample.
- [Set up Handwashing on Apple Watch — Apple Support](https://support.apple.com/guide/watch/set-up-handwashing-apdc9b9f04a8/watchos) — Series 4+ only.
- [sleepAnalysis — Apple Developer Documentation](https://developer.apple.com/documentation/HealthKit/HKCategoryTypeIdentifier/sleepAnalysis) — sleep stage types.
- [Track mindfulness — Android Developers](https://developer.android.com/health-and-fitness/guides/health-connect/develop/mindfulness) — `MindfulnessSessionRecord` API.
- [Mindfulness — Health Connect](https://developer.android.com/health-and-fitness/health-connect/features/mindfulness) — feature check `FEATURE_MINDFULNESS_SESSION`.
- [Health Connect is adding support for saving your yoga and meditation sessions — Android Authority](https://www.androidauthority.com/health-connect-mindfulness-3501729/) — timing context.
- [Google Expands Health Connect With Symptom and Alcohol Tracking Support — Android Headlines](https://www.androidheadlines.com/2025/12/google-expands-health-connect-with-symptom-and-alcohol-tracking-support.html) — Android 16 mindfulness addition.
- [Develop Sleep Experiences with Health Connect — Android Developers](https://developer.android.com/health-and-fitness/health-connect/experiences/sleep) — 8 sleep stages.
- [Health Connect brings together Peloton, ŌURA, and Lifesum — Android Developers Blog](https://android-developers.googleblog.com/2023/08/health-connect-brings-together-peloton-oura-lifesum-for-deeper-health-and-fitness-insights.html) — ~50 integrated apps.
- [On Heart Rate Variability and the Apple Watch — Marco Altini](https://medium.com/@altini_marco/on-heart-rate-variability-and-the-apple-watch-24f50e8e7bc0) — HRV Apple-Watch dependency, no 3rd-party trigger.
- [What You Can (and Can't) Do With Apple HealthKit Data — Momentum](https://www.themomentum.ai/blog/what-you-can-and-cant-do-with-apple-healthkit-data) — accuracy + stream limits.

### Market precedents — fitness / wearable

- [Your Oura Readiness Score & How To Measure It — Oura](https://ouraring.com/blog/readiness-score/) — 9 contributors, single score.
- [Readiness Contributors — Oura Help](https://support.ouraring.com/hc/en-us/articles/360057791533-Readiness-Contributors) — 14-day weighted averages.
- [WHOOP Recovery: How It Works — WHOOP](https://www.whoop.com/us/en/thelocker/how-does-whoop-recovery-work-101/) — 4-input formula.
- [WHOOP Recovery Explained — Plait](https://www.plait.fit/whoop-recovery-explained-guide.html) — HRV 70% weight breakdown.
- [Fitbit stress score explained — Wareable](https://www.wareable.com/fitbit/fitbit-brings-stress-score-to-all-devices-8394) — 1-100 score, HRV+exertion+sleep.
- [Find Your Calm with Fitbit Premium — Calm Blog](https://www.calm.com/blog/find-your-calm-with-fitbit-premium) — Premium + 400+ mindfulness sessions.
- [Measure your stress level with Samsung Health — Samsung](https://www.samsung.com/us/support/answer/ANS10001394/) — HRV-driven stress + breathing exercise prompt.
- [Strava and Apple Fitness+ collaborate — Strava Press](https://press.strava.com/articles/strava-and-apple-fitness-collaborate-to-motivate-and-reach-more-active) — Jan 2025 integration.
- [Strava New Integrations — Strava Press](https://press.strava.com/articles/stravas-new-integrations-unlock-a-holistic-view-of-training-for-athletes-on) — ŌURA, Open integration Mar 2024.
- [Add Meditation as a new activity — Strava Community Hub](https://communityhub.strava.com/t5/ideas/add-meditation-as-a-new-activity-sport-type/idi-p/6274) — community request, Strava cautious.
- [Apple Health — Calm Help Center](https://support.calm.com/hc/en-us/articles/115005140814-Apple-Health) — Calm writes mindful minutes to HealthKit.
- [How do I connect Headspace to the iOS Health App — Headspace](https://help.headspace.com/hc/en-us/articles/115003793088-How-do-I-connect-Headspace-to-the-iOS-Health-App) — Headspace writes mindful minutes to HealthKit.

### Gamified mental-health apps (direct precedents)

- [Counterproductive effects of gamification: Habitica — ScienceDirect](https://www.sciencedirect.com/science/article/abs/pii/S1071581918305135) — Diefenbach & Müllensiefen 2019 research.
- [Habitica App Review 2024 — Choosing Therapy](https://www.choosingtherapy.com/habitica-app-review/) — guilt/self-criticism concerns.
- [Finch: Self-Care Pet — App Store](https://apps.apple.com/us/app/finch-self-care-pet/id1528595748) — product reference.
- [Why Gamifying Self-Care with a Virtual Pet Works for Finch — LinkedIn](https://www.linkedin.com/pulse/why-gamifying-self-care-virtual-pet-works-finch-heather-arbiter-gjkpe) — compassionate tech design.
- [UX Teardown: Finch Self-Care App — Medium](https://medium.com/@deepthi.aipm/ux-teardown-finch-self-care-app-18122357fae7) — trigger-action-reward loop.
- [Backed by Science — SuperBetter](http://superbetter.com/the-science/) — Penn / Ohio State RCT references.
- [Examining the Effectiveness of Gamification in Mental Health Apps for Depression — JMIR](https://mental.jmir.org/2021/11/e32199) — meta-analysis caveats.

### Backlash / critique

- [Mindfulness Is a Capitalist Scam — Vice interview with Purser](https://www.vice.com/en/article/mindfulness-is-the-capitalist-spirituality-ronald-purser-interview/).
- [When mindfulness meets capitalism — Psyche](https://psyche.co/ideas/when-mindfulness-meets-capitalism-it-loses-its-way).
- [McMindfulness — Psychotherapy.net](https://www.psychotherapy.net/article/mcmindfulness-how-mindfulness-became-new-capitalist-spirituality).
- [Meditation hijacked by capitalism — Medium](https://09o32ry7238389.medium.com/meditation-has-been-hijacked-by-capitalism-110182944db2).

### Market size / retention

- [Meditation Management Apps Market — Grand View Research](https://www.grandviewresearch.com/industry-analysis/meditation-management-apps-market-report) — $1.6B 2024 baseline, CAGR 18.5%.
- [Wellness Apps Market Size — Precedence Research](https://www.precedenceresearch.com/wellness-apps-market) — $11.18B → $45.65B by 2034.
- [Top health and meditation apps by revenue 2024 — Statista](https://www.statista.com/statistics/1239670/top-health-and-meditation-apps-by-revenue/) — Calm $7.7M Jan 2024, Headspace $4M.
- [Global Mental Health Apps Market — Roots Analysis](https://www.rootsanalysis.com/reports/mental-health-apps-market.html) — $7.22B → $33.5B by 2035.
- [App Retention Benchmarks for 2026 — Enable3](https://enable3.io/blog/app-retention-benchmarks-2025) — mindfulness D30 median 4.7%; Insight Timer 16%.
- [The Meditation App Revolution — PMC](https://pmc.ncbi.nlm.nih.gov/articles/PMC12333550/) — retention + engagement data.
- [Rates of attrition and engagement in RCTs of mindfulness apps — ScienceDirect](https://www.sciencedirect.com/science/article/pii/S0005796723001699) — 24.7% weighted attrition, 38.7% larger-study.

### React Native / implementation references

- [react-native-health — getSleepSamples docs](https://github.com/agencyenterprise/react-native-health/blob/master/docs/getSleepSamples.md) — sleep stage reading.
- [@kingstinct/react-native-healthkit](https://github.com/kingstinct/react-native-healthkit) — supports mindful sessions + sleep analysis.
- [react-native-health-link](https://github.com/xmartlabs/react-native-health-link) — cross-platform HealthKit + Health Connect.
- [MindfulnessSessionRecord — Android API reference](https://developer.android.com/reference/android/health/connect/datatypes/MindfulnessSessionRecord) — record fields.

### Internal files grounding this report

- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/mental-stats-mindfulness.md` — founder draft (mandatory input).
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/01-market-research.md` — market baseline (§3.1, §3.4, §5).
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/02-beta-scope.md` — current beta IN/OUT tables (§4, §5, §7, §8).
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/product-decisions-2026-04-18.md` — D1-D5 founder decisions (especially D3 cut, D4 cut — relevant precedent for cutting scope).
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/emotional-hooks.md` — red lines (no streak-loss penalty, no FOMO, no pay-to-win).
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/BUSINESS_LOGIC.md` §3 — existing sleep XP rates (5 XP/hour, cap 10h, daily cap 3000).

---

## 7. Post-scriptum for founder and next agents

### 7.1. For the founder — the one decision to make

**Pick A, B, or C.**
- A = do nothing (safest for 31.10).
- B = sleep→recovery buff/debuff (recommended, 2 dev-weeks).
- C = full 4-stat Inner Strength (recommended only as post-beta Q1-2027).

Answer needed before next sprint planning to let 03-roadmap and 04-code-audit
update their plans.

### 7.2. For 03-roadmap

If Option B is picked:
- Insert "RecoveryState mechanic" into Now (beta slot), estimated 2 dev-weeks.
- Dependencies: existing `HealthSync` sleep data, existing `XpCalculationService`,
  existing status-icon slot on character UI.
- No new onboarding slot, no new permission request.

If Option C (post-beta) is picked:
- Reserve a named slot "Inner Strength Q1-2027", dependent on beta retention
  data; add a 30-60-day observation window post-launch.

### 7.3. For 04-code-audit

If Option B is picked, the following should be audited for readiness:
- `Mob` / `Character` entities: is there a status-effect slot (buff/debuff
  list) the UI can render? If not, new entity `CharacterStatusEffect` is
  needed.
- `XpCalculationService`: currently caps sleep at 10h and awards 5 XP/hour.
  RecoveryState should NOT duplicate XP; it should apply a **multiplier on
  next-day Battle XP or stamina regen**, not award new XP.
- Push notification infra: a morning push at 8am local with flavor text —
  does the existing push service support scheduled flavored pushes? If not,
  add.
- `HealthSyncService`: already reads sleep stages (per BL §3). No new data
  type needed.

### 7.4. Explicit non-goals of this report

- This report does NOT research specific UX wireframes for the Recovery
  status icon — that is design work.
- This report does NOT pick the specific numeric thresholds (5h / 7h / 60min
  REM) — those are design/balance calls informed by `game_settings` table.
- This report does NOT audit which specific copy lines to use — the
  05-lore-to-hook flavor patterns already give a voice; copywriter can
  draft 2-3 lines per state ("Втомлений", "Відпочилий", neutral) in 1 day.
- This report does NOT propose any new DPIA or legal docs — Option B
  doesn't trigger them; Option C would.

### 7.5. Changelog

- 2026-04-18 — v1. Timebox ~45 min. 20+ URLs verified in this session (§6).
  Grounded in 01, 02, product-decisions-2026-04-18, emotional-hooks,
  BUSINESS_LOGIC §3. Assumption markers applied where web verification was
  not conclusive.
