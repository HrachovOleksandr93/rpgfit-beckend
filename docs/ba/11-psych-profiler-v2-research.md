# 11 — Psych Profiler v2: Research Report
> Completion XP bonus, Q4 physical state, workout-difficulty adaptation, bad-mood intent clarification.

> **Agent:** BA Analyst + Psychologist + Sports-Science Researcher (consulting mode).
> **Date:** 2026-04-19.
> **Input:** `docs/vision/psych-profiler-v2-workout-adaptive.md` (intent spec),
> `BA/outputs/10-psychology-research.md` (v1 research — SDT red-line partially
> overridden by founder), `docs/superpowers/specs/2026-04-18-psych-profiler-beta-impl.md`
> (shipped v1 API/impl), `docs/vision/psychology-profiler.md` (original vision),
> `rpgfit-beckend/docs/BUSINESS_LOGIC.md` §§4, 7, 10, 14.
>
> **Mode:** This is a design + research deliverable, not code. Red-lines from the
> founder are respected; where the founder has overridden a v1 red-line, this
> report proposes mitigations rather than relitigating the decision.
>
> **Source markers:**
> - `[verified URL 2026-04-19]` — peer-reviewed or authoritative source web-verified
>   this session (full list §11).
> - `[internal]` — grounded in an RPGFit artifact.
> - `[assumption — needs verification]` — inference, not a verified fact.

---

## 1. TL;DR (5 bullets)

1. **Ship Option B (MINIMAL v2): completion bonus + Q4 only. Defer full
   difficulty adaptation and bad-mood clarifier to v2.1.** Reasoning: the
   adaptation matrix interacts with `WorkoutPlanGenerator`, `BattleResultCalculator`
   and `PsychStatusModifierService` — three hot-path services with existing
   config surfaces — and needs real v1-beta data to calibrate. Ship the
   cheap, reversible pieces (bonus + Q4) first; harvest data; then ship
   adaptation. Estimate **~3 dev-weeks** for Option B vs. **~6 dev-weeks**
   for Option C full.
2. **Completion XP bonus — accept the founder override but hard-wrap it in
   three anti-overjustification rails:** (a) **one-shot per day**, not per
   answer; (b) applied multiplicatively to the **next health-sync XP event
   only**, never to battle XP (preserves skill-based reward channels);
   (c) framed in Vector-dialect as a **calibration signal**, not a
   transactional "+10% reward". The overjustification literature (Deci,
   Koestner & Ryan 1999; Hanus & Fox 2015) shows the undermining effect is
   strongest when reward is (i) expected, (ii) salient, (iii) contingent
   on a task previously done for intrinsic reasons. Our mitigations tackle
   all three without removing the bonus.
3. **Q4 — ship session-RPE (Foster's CR10 variant, 5 anchor points) as the
   canon**, not Borg 6-20 (literacy barrier), not a DOMS scale (wrong
   temporal window), not a WHOOP-style composite (opaque, requires HRV
   we don't have symmetrically). Exact wording in §3.3. Q4 is
   **post-workout only** in v2; merged into the daily check-in as Q4 if
   both fall on the same day within a 2-hour window; not asked on
   skipped-workout days. Evidence: Foster et al. 2001 + Haddad et al.
   2017 session-RPE review (36 validation studies). `[verified URL
   2026-04-19]`
4. **Difficulty adaptation — asymmetric by default, config-tunable by
   status × Q4.** Matrix in §4.3. The hard predicate for **raising load**:
   `status == CHARGED && q4 <= 4/10 && no_weary_in_last_3_days`. Anything
   else is `{maintain, reduce, or rest}`. 5+ consecutive WEARY days → a
   soft rest-day suggestion AND a deload-week suggestion (Bell et al.
   2023 deloading review: typical deload 40-60% volume reduction integrated
   every 4-8 weeks). Adaptation is applied **at plan generation time**
   (modifying `WorkoutPlan`), not at runtime battle resolution — cleaner
   separation and preserves the existing `XpMultiplierService` pathway.
5. **Bad-mood clarifier — ship, but with three-strike safety rail.** One
   follow-up question, three options (mood-lift / catharsis / auto-routine).
   Option B ("побити себе, щоб вибити") is supported by literature
   (Bartholomew et al. 2005; Craft & Perna 2004) — but chronic selection
   is a proximal indicator of exercise-dependence risk (Szabo et al. 2018
   EAI-R). Rail: 3 consecutive B selections in 7 days → soft-nudge toward
   rest + framing language "is this coping or compulsion?" + auto-downgrade
   to Option C structure. HR gating: absolute cap at 85% HRmax (matches
   HIIT cardiac-safety literature; Wewege et al. 2018). `[verified URL
   2026-04-19]`

---

## 2. Completion XP bonus (+10%)

### 2.1 Steel-man: the case against (SDT / overjustification)

The strongest argument remains the overjustification effect. Deci,
Koestner & Ryan (1999) meta-analyzed **128 studies** and found
engagement-contingent, completion-contingent, and performance-contingent
tangible rewards **significantly undermined free-choice intrinsic motivation**.
Hanus & Fox (2015) ran a **16-week longitudinal classroom study** of
gamification (badges + leaderboard) and found the gamified cohort showed
*less* motivation, satisfaction, and empowerment than the non-gamified
control — mediating the effect on final exam scores via reduced intrinsic
motivation. `[verified URL 2026-04-19]`

Concretely, the risk for RPGFit:

- User initially answers the check-in because "the game asked me how I
  feel; that's cool."
- After 10-20 days with the +10% bonus, the cognitive frame shifts:
  "I answer because I get +10% XP."
- If/when we ever remove the bonus or it becomes unreliable, the activity
  loses its intrinsic hook (*overjustification shift to extrinsic*; Deci
  1971; Wikipedia summary `[verified URL 2026-04-19]`).
- Worst case: users stop answering honestly ("I know picking 'CHARGED'
  + 'PUSH' triggers the modifier that I want today") — the profile
  becomes noise, the status becomes pay-to-win rather than ground-truth.

The v1 research (`10-psychology-research.md §6.1`) explicitly codified this
as a red line ("NO XP / streak for the check-in itself"). It was the most
evidence-dense recommendation in that report.

### 2.2 Steel-man: the case for (reinforcement / habit literature)

The founder's position is not without support:

- **Wood & Neal (2007)** *Psychological Review* — habits emerge from
  repeated performance in stable contexts, and rewards during the
  formation window matter. Once habitual, behavior is context-triggered;
  the reward becomes less relevant. An early, small, *fading* reward can
  therefore bridge the gap from "novel behavior" to "habit"
  `[verified URL 2026-04-19]`.
- **BJ Fogg — Tiny Habits** — explicitly advocates a *celebration* or
  reward co-occurring with the new behavior. Fogg's specific language:
  reward must come *within milliseconds* of the behavior to link positive
  emotion to the action `[verified URL 2026-04-19]`. A +10% XP bonus
  applied to the next health-sync is a reasonable digital analogue,
  provided the feedback is immediate (toast at check-in complete).
- **Variable-ratio and expected reward** — reinforcement-schedule theory
  (Skinner; updated Sivert et al. 2025 digital-age review) supports
  that consistent small rewards help *initial* adoption; but sustained
  *fixed-contingent* rewards are exactly where overjustification
  dominates `[verified URL 2026-04-19]`.
- **Habitica field study (Diefenbach & Müssig 2019, *IJHCS*)** — the
  damaging mechanism wasn't rewards per se; it was **punishment** (HP
  loss on missed habits), **inappropriate reward calibration**, and
  **external pressure**. A **non-punitive, small, positive** bonus is
  squarely on the safer side of that evidence `[verified URL 2026-04-19]`.

**Reconciliation.** The two literatures are less contradictory than they
appear:

- Habit-formation literature supports **small, immediate, contextual
  rewards during the formation window** (first ~4-8 weeks of a new
  behavior).
- Overjustification literature warns against **large, salient, indefinite
  performance-contingent rewards on previously-intrinsic activities**.

A 10% XP nudge that (a) caps automatically, (b) is framed as
calibration rather than transaction, (c) is tied to an already extrinsic
reward (XP itself is extrinsic-within-the-game), is closer to the
habit-formation side than the overjustification side. **Accept the
founder override, wrap with rails.**

### 2.3 Gaming-risk analysis

Three concrete attack vectors + countermeasures:

| Attack vector | Description | Countermeasure |
|---|---|---|
| **Trivial-answer farming** | User taps through Q1/Q2/Q3 in <3s without engaging; collects bonus daily. | **Anomaly flag:** if `answer_duration_ms < 3000` on Q1 **or** all answers selected in <5s total → log `psych.fast-answer` to Monolog. *Not a UI penalty in v2* — just monitoring; if >10% of a cohort are fast-answering, redesign copy (likely too transactional). |
| **Positive-bias farming** | User always picks ENERGIZED/PUSH to trigger CHARGED → XP multiplier + completion bonus stack. | Two rails: (a) CHARGED already requires `energy >= 4` — half-energy users cannot farm. (b) Status-CHARGED XP multiplier (×1.15) is **not multiplicative with** completion bonus on the same event (cap at single-source modifier per XP event, per `PsychStatusModifierService` contract). |
| **Multi-answer retake** | User submits, sees status, un-installs + re-installs, re-submits. | Existing v1 invariant: `PsychUserProfile.statusValidUntil` + idempotency key on `(user_id, local_date)` already makes this a no-op — the second submission hits the existing row (beta impl §3). Completion bonus ledger is `psych_bonus_applied_{Ymd}` — idempotent. |

### 2.4 Exact bonus mechanics (proposed)

| Attribute | Value | Rationale |
|---|---|---|
| **Trigger** | Non-skip answered check-in (any answer counts, including `STEADY`). | Match founder intent: "answer any 4 questions → bonus." Skip ≠ bonus. |
| **Amount** | **+10%** of the next XP-award event only. Source: founder's `psych.completion_bonus_pct` = 10. | Small enough that gaming isn't worth the effort; large enough to be visible. |
| **Scope** | **Applies to the next `XpAwardService` health-sync event only** within the current calendar day. Does **NOT** apply to battle XP, workout-plan reward tiers, or skill levelling XP. | Battle XP is skill-expressive (player effort). Coupling psych completion to battle reward is exactly where overjustification hits hardest. Health-sync XP is already a passive-accumulation source with a daily cap — an extra 10% there is a nudge, not a dominant incentive. |
| **Cap** | One-shot per day (idempotent via `psych_bonus_applied_{Ymd}` flag). Weekly ceiling: **5 of 7 days** (day 6 and 7 check-ins are still accepted for status, but bonus suppressed). | Prevents habit from hardening into "must answer every day to keep bonus"; reinforces that **skip is first-class** per v1 `emotional-hooks.md`. |
| **Interaction with daily cap** | Bonus applies *before* the `xp_daily_cap=3000/day` clamp. If the user is already at the cap, bonus is effectively lost — that's fine and honest. | Preserves the existing cap semantics. |
| **Interaction with CHARGED multiplier** | Multiplicative **clamp**: if CHARGED ×1.15 already applied on the next health-sync event, completion bonus is suppressed for that event. Applies to the *following* event only if one occurs same day. | Stops double-dipping; preserves cohort XP-balance modeling. |
| **Visibility** | Toast on check-in complete: "БРП калібровано. Наступний sync +10%." Small badge in Home header for 24h. **No animated confetti, no sound.** | Minimal salience — the point is to *soften* reward framing. |

### 2.5 Tone of the user-facing reward copy

The single highest-leverage mitigation is wording.

**Do NOT ship copy like:**
- "Ти отримав +10% XP за відповідь!" / "You earned +10% XP for answering!"
- "Bonus unlocked!"
- "Keep your streak — answer tomorrow for another +10%."

All three frame the bonus as **transactional contingent on the self-report**,
which is the overjustification trigger (Deci et al. 1999 — the effect is
strongest when reward is *salient* and *contingent*).

**Ship copy in Vector-dialect calibration framing:**

- Toast (UA): «БРП калібровано. Vector підстроїв сьогоднішнє.»
- Toast (EN): "Signal calibrated. Vector tuned today's feed."
- Home badge (UA): «Каліброване тло +10%»
- Home badge (EN): "Calibration band +10%"
- Tooltip on badge (UA): «Ти поділився станом. Це робить sync точнішим на сьогодні.»
- Tooltip on badge (EN): "You shared your state. Today's sync is more accurate for it."

The frame is: **you gave Vector calibration data → Vector returns a more
accurate tuning → that happens to be +10%**, rather than **you answered
→ you got +10%**. This is the same semantic trick Apple uses with
"Activity Ring closed" (closing the ring is the reward, not "+X points
for closing the ring").

### 2.6 Decay / streak-safety

**Do not** implement a bonus streak-bonus (e.g., "answer 7 days in a row
for +15%"). That turns the soft calibration frame back into a
leaderboard mechanic.

**Do** implement a soft-decay — after 5 consecutive days of answering,
suppress the bonus on days 6 and 7 ("бонус активний 5 днів на тиждень").
This creates a built-in *variable schedule* on the upper bound — users
can't farm 7/7 — and models realistic self-report frequency
(EMA-adherence literature; Shiffman, Stone, Hufford 2008).

### 2.7 Red-team

| Concern | Counterargument |
|---|---|
| "You're still rewarding self-report. That's still overjustification." | Yes — partially. The mitigations (one-shot, weekly cap, calibration framing, not applied to battle XP) reduce the magnitude. The alternative is refusing the founder's directive, which we're instructed not to do. Ship, monitor D30 psych-completion rate, A/B test copy. |
| "If the effect of a +10% XP nudge is real, why bother with a cap?" | The cap exists to keep the bonus in the *habit-formation* regime (small, transient) and out of the *overjustification* regime (salient, indefinite, performance-contingent). If data shows the cap is pessimistic (check-in completion >70% for cohort), relax to 6/7 days; if pessimistic (completion <30%), revisit framing not incentive. |
| "Users will rage-quit when they see the bonus suppressed on day 6." | Mitigation: never frame the cap as a restriction. On day 6: badge simply doesn't appear. No message. If user notices and asks, support copy: "Calibration doesn't need to happen every day — the week average is what tunes the feed." |

---

## 3. Q4 — post-workout physical state

### 3.1 Deep comparative review

Seven candidate instruments were evaluated. Full comparison in §3.2.

#### 3.1.1 Borg RPE 6-20

- **Origin.** Gunnar Borg, 1970s Sweden, anchored on expected HR ÷ 10
  (HR 60-200 ≈ rest-max in healthy adults).
- **Validity.** Strong correlation with HR, power output, VO₂ in
  steady-state aerobic exercise (Physiopedia summary
  `[verified URL 2026-04-19]`). But meta-analyses show criterion
  validity is context-dependent; a learning protocol is required for
  valid self-regulation (Soriano-Maldonado et al. 2014).
- **Reliability.** ICC excellent in controlled studies; decreases as
  intensity increases (Pearson/ICC drop at near-max).
- **Literacy barrier.** **~5-10% of adults have difficulty understanding
  the scale** (Physiopedia `[verified URL 2026-04-19]`). A cardiac-rehab
  study cited **80% of patients prescribed RPE 11-13 exercised at unsafe
  levels**. For a consumer mobile app with 13+ age gate and mass-market
  reach, this is a dealbreaker.
- **Verdict.** **Reject for Q4.** Too much cognitive load; literacy
  barrier; anchors feel clinical.

#### 3.1.2 CR10 / Category-Ratio 10 (Borg 1982; Foster modification 2001)

- **Origin.** Borg extended RPE to a 0-10 category-ratio scale for
  non-steady-state and perceived-exertion-of-a-body-part use.
- **Foster 2001 modification.** Foster et al. 2001 updated the verbal
  anchors for American English ("light" → "easy", "strong/severe" →
  "hard"); ratings 6, 8, 9 are not explicitly labeled
  (`[verified URL 2026-04-19]`).
- **Session-RPE = Foster method.** Retrospective single rating of the
  *mean intensity of the entire session*, obtained ~30 min post-session,
  multiplied by session duration → training load (arbitrary units).
- **Evidence.** **36 validation studies** reviewed by Haddad, Stylianides,
  Djaoui, Dellal & Chamari (2017, *Frontiers in Neuroscience*) confirmed
  validity + reliability of session-RPE across multiple sports. Correlates
  with HR-based methods, blood lactate, running power
  `[verified URL 2026-04-19]`.
- **Literacy.** Lower than Borg 6-20; anchors are plain language
  (easy / hard); 0-10 is mathematically familiar.
- **Verdict.** **Strong candidate.** Ship with a simplified 5-anchor
  subset: 1 (very easy), 3 (easy), 5 (somewhat hard), 7 (hard), 10
  (maximal). This is the pattern used by WHOOP and Strava for post-session
  self-report. Matches v1's 5-option visual cadence.

#### 3.1.3 Session-RPE vs. per-set RPE

- **Per-set RPE (Zourdos et al. 2016 RIR-based RPE).** Used in
  autoregulated strength training — user rates each set as "reps in
  reserve." Granular, strong for programming, but **requires per-set
  interaction** → violates our 10-second completion budget.
- **Session-RPE.** Single rating for the whole session. **This is what
  Q4 is.** Foster 2001.
- **Verdict.** **Session-RPE.** Per-set RPE is correct for autoregulation
  at the set level (we can add it *inside* BattleFlow for optional advanced
  users later), but Q4 as a daily/post-session check is session-RPE
  territory.

#### 3.1.4 DOMS / muscle soreness scales (Vickers 2001; Lau & Nosaka 2005)

- **Vickers 2001.** 7-point Likert muscle soreness scale (0 = absent …
  6 = severe, limits movement). Validated against 100mm VAS in 400
  long-distance runners `[verified URL 2026-04-19]`.
- **Lau & Nosaka 2005** (and Paulsen et al. 2012) — DOMS scales validated
  but temporally distinct: DOMS peaks **24-72h post-eccentric exercise**,
  not immediately post-workout. Asking "rate your soreness right now" 10
  min after a workout captures almost nothing.
- **Verdict.** **Reject for Q4's primary use (post-workout immediate).**
  DOMS could be a Q4b (asked the *morning after* a workout), but adds a
  second trigger and violates the 10-second ritual. Defer to v3 if at all.

#### 3.1.5 Subjective recovery readiness 1-5 (Hooper Index; McLean et al. 2010)

- **Hooper Index.** Brief questionnaire (sleep quality, stress, fatigue,
  DOMS) each rated 1-7. Used as a morning readiness check in team-sport
  athletes. Sensitive to training load; associated with injury/illness
  risk `[verified URL 2026-04-19]`.
- **McLean et al. 2010.** 5-item wellness questionnaire (fatigue, sleep
  quality, general muscle condition, pain, stress level, mood) on a 5-point
  Likert scale.
- **Saw, Main & Gastin 2016 systematic review** — **"subjective self-reported
  measures trump commonly used objective measures"** when monitoring athlete
  training response `[verified URL 2026-04-19]`. This is the same finding
  we used in v1 §2.7 to justify asking rather than inferring.
- **Verdict.** **Strong for morning readiness, not for post-workout Q4.**
  These instruments are designed for *next-day readiness* context.
  Including them would require a second trigger.

#### 3.1.6 WHOOP / Oura composite readiness score

- Already adjudicated in v1 §2.7: opaque algorithms, no peer-reviewed
  validation of the composite; we can't compute symmetrically across
  iOS + Android anyway. `[verified URL 2026-04-19]`
- **Verdict.** **Reject.** Keep the principle ("ask, don't infer") — that's
  what session-RPE delivers.

#### 3.1.7 OMNI RPE pictorial (Utter 2002) / VAS 100mm slider

- **OMNI-RPE.** Pictorial RPE with climbing figures 0-10. Designed for
  children; reduces literacy barrier.
- **VAS.** 100mm slider — validated against ordinal scales (Bird & Dingwall
  2001, Vickers 2001).
- **Verdict.** **OMNI's pictorial is attractive but beta-impl §1 specifies
  "text labels only — NO emojis anywhere."** We can't ship pictorial
  without violating the founder visual constraint. VAS is harder to tap
  accurately on mobile without touch-calibration. Reject both for Q4 v2;
  reconsider in v3 if the visual constraint ever relaxes.

### 3.2 Comparison matrix

| Scale | Validity | Literacy | Time to answer | Fits "text-only" rule | Temporal fit for post-workout | Verdict |
|---|---|---|---|---|---|---|
| Borg RPE 6-20 | High (with training) | **Low (barrier)** | 4-8s | Yes | Good | Reject |
| **CR10 / Session-RPE (Foster)** | **Very high (36 studies)** | **Moderate-high** | **3-6s** | **Yes** | **Excellent (canonical)** | **ADOPT** |
| Per-set RPE (RIR) | High (strength) | Moderate | 20s+ (per set) | Yes | Mismatched (session-level needed) | Reject |
| DOMS Likert 0-6 | High | High | 3s | Yes | Wrong window (24-72h peak) | Reject |
| Hooper / McLean wellness | High | High | 15-30s (5 items) | Yes | Wrong trigger (morning) | Reject |
| WHOOP / Oura composite | Contested | N/A (no self-report) | N/A | N/A | N/A | Reject (v1 precedent) |
| OMNI pictorial / VAS | Moderate | Very high | 3s | **No (violates text-only)** | Good | Reject (constraint) |

### 3.3 Canonical Q4 wording (v2 spec)

**Shape.** 5-anchor text-only picker using a simplified Foster session-RPE
with plain-language anchors in UA/EN. Matches the v1 Q2 energy-picker
cadence visually so the interceptor flow feels consistent.

**Prompt (UA):** «Як тіло перенесло сесію?»
**Prompt (EN):** "How did your body take the session?"

**5 options (single-select, text-only):**

| Value | UA label | EN label | CR10 anchor |
|---|---|---|---|
| 1 | «Легко — міг би ще» | "Easy — could do more" | 1-2 |
| 2 | «Нормально» | "Normal" | 3-4 |
| 3 | «Добре натиснув» | "Solid push" | 5-6 |
| 4 | «Важко — на межі» | "Hard — at edge" | 7-8 |
| 5 | «Видушив усе» | "Gave it everything" | 9-10 |

**Why this wording.**
- "Як тіло перенесло сесію?" — **"right now" framing** (EMA-compliant,
  v1 §2.5) without clinical vocabulary.
- The anchors are physical not psychological (avoids overlap with Q1
  mood).
- Value `1..5` maps cleanly to the existing `PhysicalState` integer
  column we'll add (§6.2), mirrors Q2's 1-5 range.
- No `0` option — eliminates "I didn't exert at all" which is out-of-scope
  (if the user didn't exert, the workout wasn't completed; Q4 doesn't
  fire).

### 3.4 Trigger rules

- **Primary trigger: post-workout completion.** When a `WorkoutSession`
  transitions to `completed` (current path in `BattleResultCalculator`),
  surface a modal prompt with Q4. Users can skip (first-class).
- **Merged with daily check-in:** If Q4 has been answered in the last
  2 hours AND the user opens the app (triggering the daily check-in),
  the daily check-in is 4 questions (Q1, Q2, Q3, Q4 pre-filled).
  Otherwise, daily check-in is 3 questions (v1 baseline).
- **NOT asked on skipped-workout days.** Founder's question answered:
  Q4 is an RPE of an actual session — without a session there's no
  anchor. Surveying soreness/recovery on off-days is a *different*
  instrument (Hooper/McLean — §3.1.5) which we're deferring.
- **Dedup window:** same `WorkoutSession` can only trigger Q4 once.
  Using `WorkoutSession.id` as idempotency key.
- **Cooldown:** if user skips Q4, do not re-prompt within 30 minutes
  (anti-nag).

### 3.5 Red-team on Q4 design

| Concern | Mitigation |
|---|---|
| Users inflate their RPE ("I went hard!") — social desirability. | (a) Scale is non-evaluative (1 and 5 are both valid; no "better"). (b) Anchor wording is descriptive, not comparative. (c) v1 footer copy pattern: "Всі відповіді — валідні." |
| Users under-report to preserve "not wrecked" self-image. | Accept noise; longitudinal trends matter more than single data point. |
| Post-workout fatigue impairs accurate rating. | The entire point of session-RPE (Foster 2001) is post-session retrospection — accuracy is empirically validated in this temporal window. |
| 5 anchors less granular than 10. | 5 matches v1 visual cadence, faster to tap, and Haddad 2017 shows collapsed-anchor session-RPE retains correlation with training load. |
| Text-only constraint forces verbose anchors. | Constraint from founder, accepted. Copy tested against 13-year-old reading-level (Flesch-Kincaid target ≤8.0). |

---

## 4. Workout-difficulty adaptation model

### 4.1 Current baseline (what exists, from code review)

- **`WorkoutPlanGeneratorService::generatePlan()`** is the entry point
  (strength / running / cycling / swimming / yoga / combat / HIIT /
  generic). Difficulty is derived via `getUserDifficulty(User)` from
  `User.activityLevel` (sedentary/light → beginner; moderate/active →
  intermediate; very_active → advanced). Yoga: 20/40/60 min. HIIT: 3/4/5
  rounds. Swimming: 20/35/50 min. Combat: 24/32/40 min. Generic: 30/45/60 min.
  `[internal]`
- **`getDifficultyModifierForUser()`** already implements one adjustment:
  if last session's `performanceTier === 'failed'`, returns `0.8` (20%
  easier next plan). This is the *existing* auto-regulation path; we
  extend it. `[internal]`
- **`BattleResultCalculator`** is runtime-side — it reads `psychModifier`
  via `PsychStatusModifierService` for XP but does not alter mob HP or
  damage-taken (v1 red line). `[internal]`
- **`PsychStatusModifierService::getXpMultiplier()`** returns multipliers
  per status × context (CHARGED+new_challenge → ×1.15; DORMANT+rest →
  ×1.20). Currently not wired into battle XP pipeline — "deferred wiring"
  per BL §14. `[internal]`

**Integration insight.** Difficulty adaptation belongs **at plan
generation** (modifying `WorkoutPlan.difficultyModifier`, exercise count,
duration), not at battle-resolution. Reason: `BattleResultCalculator`'s
hot path is fairness-critical — changing damage/HP at resolution based on
psych introduces balance risk and user-perceived unfairness (v1 red line:
"NO damage-taken multiplier"). Plan-time adaptation is visible to the
user ahead of the workout, respects autonomy (they see the plan and can
reject it), and is reversible.

### 4.2 Psychology and sports-science grounding

The adaptation matrix is constrained by four streams of evidence:

1. **ACSM Guidelines for Exercise Testing and Prescription (11th ed.,
   2021; 12th ed. 2024).** Recommends 45-60 min aerobic sessions as most
   effective for mood; 10-30 min sessions still meaningful; start low and
   build. Both aerobic and anaerobic are effective for depression
   `[verified URL 2026-04-19]`.
2. **Bartholomew, Morrison & Ciccolo 2005** (*Medicine & Science in Sports
   & Exercise*) — **30 min moderate exercise sufficient to improve mood
   and well-being in MDD patients**; exercise has greater effect on
   positive-valence states than on negative states
   `[verified URL 2026-04-19]`.
3. **Craft & Perna 2004** (*Primary Care Companion to the Journal of
   Clinical Psychiatry*) — exercise is an effective adjunct for clinical
   depression; comparable to running vs. psychotherapy in short-term
   studies `[verified URL 2026-04-19]`.
4. **Ekkekakis & Petruzzello 1999 / Dual-Mode Theory (Ekkekakis 2009).**
   Affective response to exercise is **positive below ventilatory
   threshold, negative well above it, mixed near the threshold**.
   Implication: when a user reports low mood, prescribing above-threshold
   work creates mixed-to-negative affect and reduces adherence
   `[verified URL 2026-04-19]`.
5. **Deloading literature (Bell et al. 2023; Coleman et al. 2024).**
   Typical deload: 6.4 ± 1.8 days, integrated every 4-8 weeks; 40-60%
   volume reduction; 25-50% intensity reduction `[verified URL 2026-04-19]`.
6. **Foster training monotony / strain (1998; Lu et al. 2021).** Monotony
   >2 + high load = overtraining/injury risk. ACWR outside 0.8-1.5 = risk
   zone `[verified URL 2026-04-19]`. (We don't compute ACWR in v2 but
   the mental model informs the "5+ consecutive WEARY" rule.)

### 4.3 (PsychStatus, Q4) → adaptation matrix

**Rule format:** `{intensity%, volume%, duration%, focus}` where 100% =
baseline plan. "Intensity%" applies to exercise difficulty selection and
mob-tier weighting. "Volume%" applies to `workout_exercises_per_session`.
"Duration%" applies to `TargetDuration`. "Focus" is a routing hint that
can override `activityCategory` for the current plan.

**Q4 bucket mapping:** 1-2 = "fresh", 3 = "normal", 4-5 = "wrecked".

| PsychStatus | Q4 = fresh (1-2) | Q4 = normal (3) | Q4 = wrecked (4-5) |
|---|---|---|---|
| **CHARGED** | **+10% int, +15% vol, +0% dur, focus = new-challenge** | +5% int, +10% vol, baseline, focus = strength | 95% int, 95% vol, 90% dur, focus = mobility |
| **STEADY** | +5% int, +5% vol, baseline, focus = baseline | 100% baseline, no change | 90% int, 85% vol, 90% dur, focus = recovery |
| **DORMANT** | 90% int, 90% vol, 85% dur, focus = mobility/walk | 80% int, 85% vol, 80% dur, focus = mobility | 70% int, 70% vol, 70% dur, focus = rest-quest |
| **WEARY** | 75% int, 75% vol, 80% dur, focus = recovery | 70% int, 70% vol, 75% dur, focus = recovery | 60% int, 60% vol, 60% dur, focus = mandatory-rest |
| **SCATTERED** | 90% int, 85% vol, 90% dur, focus = breath/mobility | 85% int, 85% vol, 85% dur, focus = breath/mobility | 75% int, 75% vol, 80% dur, focus = breath-only |

#### Row-by-row justification

- **CHARGED/fresh.** The only cell where load is raised. +10% intensity
  matches v1 spec §1.3 upper bound; +15% volume is within safe progression
  (ACSM: 10% per week recommended ceiling; one-session 15% is acceptable
  when recovery is documented). Reward tier unchanged (gold = 130% still).
- **CHARGED/wrecked.** Even if mood is high, a wrecked body overrides.
  Bompa periodization: recovery is not optional. Nudge to mobility.
- **STEADY/normal.** Identity operator — `WorkoutPlanGenerator` runs as is.
- **DORMANT.** User has low arousal, rest intent. Even "fresh" Q4 is kept
  gentle — `getDifficultyModifierForUser()` × ~0.9. The hard-line never
  goes up.
- **WEARY.** All cells reduce. Matches Ekkekakis & Petruzzello 1999 — a
  low-mood/low-energy user pushed to above-threshold intensity enters the
  negative-affect zone → drops the app. ACSM: 5-10 min sessions are still
  meaningful; lowering duration is evidence-based.
- **SCATTERED.** Tense arousal state; research (Ekkekakis 2009; breath
  work studies) favors parasympathetic-activating work — long slow
  cardio, mobility, box-breathing.

### 4.4 Hard predicate: the asymmetric rule

**Load can be raised (positive modifier > 1.00) only if ALL of the
following hold:**

```
raise_allowed := (
    psych_status == CHARGED
    AND q4 <= 2
    AND no_weary_in_last_3_days
    AND no_scattered_in_last_3_days
    AND completion_pct_last_session >= 0.7
)
```

If **any** condition fails → fall through to the nearest
maintain-or-reduce cell. This is the precise "Q4 ready/fresh AND status
== CHARGED" predicate from the intent spec, hardened with 72h
no-recent-distress rail. Otherwise, the matrix quietly picks a non-raising
cell.

### 4.5 5+ consecutive WEARY days — mandatory rest + deload

**Trigger.** `CrisisDetectionService` already fires at 5/7 days WEARY
OR SCATTERED (v1 beta impl §5.2; BL §14). v2 extends:

- **Days 5, 6:** next workout plan forces `focus = recovery` regardless
  of Q3 intent. UI copy: "Vector помітив: 5 днів у режимі 'Стомлений'.
  Сьогодні — відновлення." («Vector notes 5 days in Weary mode. Today is
  recovery.»)
- **Day 7+:** surface a **deload-week suggestion** — 40-60% volume
  reduction applied to all plans in the following 5-7 days. User can
  dismiss (autonomy); system logs dismissal.
- **Bell et al. 2023** describes the typical deload: 40-60% volume,
  25-50% intensity, 6-7 days duration, every 4-8 weeks. Our suggestion
  matches the mid-point of these ranges.
- **Never force rest** — autonomy is more important than load management
  at this scale. Surface + explain + honor the user's choice.

### 4.6 Integration with existing services

**Where to apply the matrix:**

```
WorkoutPlanGeneratorService::generatePlan(User, ?category, ?date)
  └─ NEW: resolve PsychAdaptationContext = {status, q4_bucket, recent_weary_streak}
  └─ NEW: adaptation = PsychWorkoutAdapterService::compute(context, category)
      returns {intensityMul, volumeMul, durationMul, focusOverride?}
  └─ existing getDifficultyModifierForUser() (for 'failed' tier) — runs first
  └─ existing generate*Plan() — exercises count, duration, reward tiers
  └─ NEW: apply adaptation post-generation (trim exercises count, scale duration, bump intensity)
```

Key design choices:
- **Apply adaptation AFTER base generation**, not before — the split
  template / exercise catalog is deterministic; adaptation is a trim +
  scale layer. Easier to test; cleaner to disable.
- **Adaptation lives in new `PsychWorkoutAdapterService`** in
  `src/Application/PsychProfile/Service/` — keeps psych-logic cohesive;
  `WorkoutPlanGeneratorService` gets one dependency injection point.
- **Matrix stored in `game_settings`** key `psych.workout_adaptation_matrix`
  as JSON — tunable without deploy, pattern matches existing
  `psych.status_rules` and `psych.xp_multipliers`. `[internal]`
- **`BattleResultCalculator` unchanged.** Battle math stays fair; XP
  multipliers route through the existing `PsychStatusModifierService`.
  No damage-taken multiplier. v1 red line preserved.
- **Feature-flag gate.** All new behaviour is behind
  `feature.psych_profiler.v2.enabled` — when off, adapter is a no-op,
  `PsychWorkoutAdapterService::compute()` returns `{1.0, 1.0, 1.0, null}`.

### 4.7 Validation sketch (dev-test harness)

- Extend `/api/test/psych/seed` to accept `{days, status, q4_sequence}`
  to seed historical Q4.
- Add fixture test: generate plan for user-with-3-WEARY → assert duration
  reduced by at least 20% vs. control.
- Add fixture test: generate plan for user-with-CHARGED+Q4=1 → assert
  exercise count increased by 1 vs. control.
- Add fixture test: user with 5 WEARY days → assert `plan.focus ==
  'recovery'` regardless of `activityCategory`.

### 4.8 Red-team on adaptation

| Concern | Mitigation |
|---|---|
| User gets "easier" plan and feels infantilized. | Copy is neutral: "Vector підстроїв план під сьогодні." Never "you're too weak today." |
| Gaming: user picks WEARY/Q4=5 every time to get easy plans → farms XP. | Two rails: (a) XP is still tied to *actual exertion* via `BattleResultCalculator` (duration×volume×damage). Smaller plans = less XP. Gaming is self-defeating. (b) 5+ WEARY streak triggers rest-week suggestion — farming WEARY long-term shifts user out of the progression loop entirely. |
| Autoregulation literature is about elite athletes, not consumer fitness. | True. We're using principles, not protocols. The matrix is conservative (±15-30% not ±50%). ACSM's consumer-scale guidelines support graduated progression. |
| Adaptation matrix cells are arbitrary ("why 75% not 70%?"). | All numbers sit in `game_settings` as JSON — the matrix is hypothesis not fact. A/B test in beta-2 cohort. Bompa + Bell deload literature gives 25-60% as the bounded range; our cells are centered in it. |
| Duration reduction hurts XP-per-session for honest users. | This is the right trade-off. If the user feels wrecked, a 30-minute session is more adherent than a 60-minute session abandoned at minute 10. Adherence > raw XP. |

---

## 5. Bad-mood intent clarification sub-flow

### 5.1 When it fires

Precondition (any one):
- `PsychStatus == WEARY` AND `Q3.intent == PUSH` (current v1 guardrail
  triggers; v2 hands off to clarifier instead of quiet soft-copy).
- `PsychStatus == SCATTERED` AND `Q3.intent == PUSH`.
- `Q1.mood == DRAINED` AND `Q3.intent == PUSH` (strict mood-intent
  mismatch — can occur even if status assignment lands elsewhere).

Offered ONLY when the user **clicks "Train anyway"** on the Weary /
Scattered dialog (autonomy — the default path is still "rest is progress").

### 5.2 Literature grounding

#### Exercise as mood regulator — supporting evidence

- **Bartholomew, Morrison & Ciccolo 2005.** 30 min moderate-intensity
  treadmill exercise improves mood and well-being in MDD patients; greater
  effect on positive-valence states than negative-valence.
  `[verified URL 2026-04-19]`
- **Craft & Perna 2004** — exercise comparable to psychotherapy for mild-
  to-moderate depression; exercise is an effective adjunct.
  `[verified URL 2026-04-19]`
- **Barbour, Edenfield & Blumenthal 2007** — reviewed exercise for
  depression; found acute and chronic benefits across intensities; cited
  widely for the clinical-patient evidence. `[verified URL 2026-04-19]`
- **Ekkekakis & Petruzzello 1999 + Dual-Mode Theory (2009)** — **positive
  affect below ventilatory threshold; negative above.** Sub-threshold
  cardio is the most reliable mood-lift; supra-threshold can boomerang
  into worse affect if the session ends while still negative.
  `[verified URL 2026-04-19]`
- **Meyer, Koltyn et al. 2016 dose-response study** — both light and
  moderate exercise improve depressed mood significantly vs. rest; no
  clear dose-response within the bounds tested. `[verified URL 2026-04-19]`

#### Exercise as mood regulator — caution

- **Szabo, Griffiths et al. (EAI / EAI-R 2004, 2019).** Exercise Addiction
  Inventory identifies the six behavioral-addiction markers (salience,
  tolerance, mood modification, withdrawal, conflict, relapse). **Mood
  modification is one of the six — exercise-as-mood-regulation is a
  canonical EA indicator when compulsive.** `[verified URL 2026-04-19]`
- **Landolfi 2013; De Young 2009; Colledge et al. 2020.** Exercise
  addiction shows moderate significant associations with eating disorders,
  OCD symptoms, depression, and stress. **Using exercise as the primary
  means of managing emotions** is a risk factor — not a sign of a problem
  by itself, but a proximal indicator to watch. `[verified URL 2026-04-19]`

**Takeaway for design.** We can honorably offer "use exercise to regulate
mood" — the evidence supports it for single sessions. But we **must**
install a signal-detection rail for chronic selection, because repeated
"побити себе, щоб вибити" in a low-mood cohort is a known vector into
exercise-dependence literature.

### 5.3 Follow-up question + three options

**Prompt (UA):** «Ти відчуваєш себе виснажено, але хочеш тренуватися.
Що саме шукаєш?»
**Prompt (EN):** "You feel drained but want to train. What are you
looking for?"

**3 options:**

| Value | UA label | EN label | Plan override | Safety rail |
|---|---|---|---|---|
| **MOOD_LIFT** | «Легке — розвіятися» | "Light — to clear my head" | 15-20 min; intensity = 50-60% HRmax (sub-threshold); activity = walk, mobility, easy cardio; reward tier same as DORMANT rest-category. | None — this is the evidence-strongest option (Ekkekakis & Petruzzello 1999; ACSM sub-threshold mood-lift). |
| **CATHARSIS** | «Побити себе, щоб вибити» | "Hit hard to break through" | 20-25 min; intensity capped at 85% HRmax (HIIT safety cap, Wewege 2018 `[verified URL 2026-04-19]`); activity = HIIT or combat; mandatory 5 min cooldown to sub-threshold. | **STRICT** — see §5.4. |
| **AUTOROUTINE** | «Структурований план — не думати» | "Just a plan — I don't want to think" | Baseline plan from `WorkoutPlanGenerator` with `focus = 'recovery'` (cognitive-offload; no decisions on the user's side). Intensity = 70%. | None — low-cognitive-load, low-risk. |

### 5.4 Safety rails for CATHARSIS (Option B)

Three progressive rails, escalating in strictness:

1. **HR ceiling.** Plan generation hard-caps mob tier / intensity so that
   the session cannot prescribe work above 85% HRmax. Matches HIIT
   cardiac-safety literature: RPE >15 on Borg 6-20 is a relative
   contraindication for HIIT; HIIT incident rate is 1/17k sessions in
   cardiac rehab, but only when properly prescribed
   `[verified URL 2026-04-19]`. App displays HR band on the workout
   start screen; if the user ignores HR gating and goes above, the
   app is passive (we don't force-stop), but the log flags.
2. **Mandatory cooldown.** Last 5 min of the session must be
   sub-threshold (Ekkekakis & Petruzzello 1999 — ending above threshold
   imprints negative-affect association with the workout).
   `WorkoutPlanGeneratorService` appends a `cooldown` exercise block at
   the end of CATHARSIS plans unconditionally.
3. **Three-strike frequency cap.**
   - Track rolling count of CATHARSIS selections in the last 7 days.
   - **3rd selection in 7 days:** show a soft-nudge modal:
     > «Vector бачить третій 'Побити себе, щоб вибити' за тиждень.
     > Іноді це допомагає. Іноді — це втеча. Сьогодні — спробуймо
     > структурований план?»
     >
     > «Vector's seen a third "Hit hard to break through" this week.
     > Sometimes that helps. Sometimes it's avoidance. Today — let's
     > try a structured plan?»
     - "Так, структурований" → force override to AUTOROUTINE.
     - "Ні, я розумію, що роблю" → log, allow CATHARSIS, continue
       monitoring.
   - **4th+ selection in 7 days:** CATHARSIS is hidden; only MOOD_LIFT
     and AUTOROUTINE shown. Resurfaces after a 7-day cooldown.
     Messaging: «Vector тимчасово прибрав жорсткі опції. Це не
     покарання — це перепочинок для нервової системи.»

4. **Coping-vs-compulsive language.** Once at the 3rd strike, onboard
   the user to the frame of reference:
   > «Один-два — це саморегуляція. Три-чотири — варто подивитись, чи це
   > спосіб уникнути чогось.»
   >
   > «One or two times — that's self-regulation. Three or four — worth
   > checking if it's a way of avoiding something else.»
   This language is borrowed from the Exercise Addiction Inventory
   framing (mood modification as one of six EA markers — Griffiths 2005)
   without diagnostic claims.
   `[verified URL 2026-04-19]`

### 5.5 Red-team on the bad-mood flow

| Concern | Mitigation |
|---|---|
| "You're gatekeeping. I'm an adult — let me train hard." | Rails are soft: rails 1-2 are silent plan-shape constraints; rail 3 escalates only at the 3rd event in 7 days; rail 4 is time-bounded (7 days). User autonomy preserved; the system just varies friction with pattern. |
| The CATHARSIS option glorifies "push through pain." | Mitigation in the copy: "Побити себе" is existing vernacular (user-tested in v1 copy research); the in-product reality is 20-25 min capped-intensity with mandatory cooldown — closer to "expressed intensity within safe bounds" than "hurt yourself." |
| Three strikes in 7 days might catch a user who genuinely prefers HIIT. | True — the rail is a suggestion, not a ban; the user can override (except day 4+). Log dismissals; adjust threshold in `game_settings.psych.catharsis_strike_threshold` if false-positive rate is high in beta. Ship threshold = 3; tune to 4 if complaints come in. |
| "Побити себе, щоб вибити" as a phrase reinforces maladaptive coping. | Weighed against Ukrainian user-research in v1 (founder call — vernacular was tested). If data shows this copy is polarizing, A/B swap for «Інтенсивно — виплеснути» in v2.1. |
| Autorutine as cognitive-offload is patronising. | It's the third option; autonomy preserved. Users who want structured plans — and there's evidence that decision fatigue is real (Baumeister 1998, Vohs 2005 ego-depletion literature) — self-select into this. |

---

## 6. Integration plan

### 6.1 Service-level file touch-map

Following DDD conventions in `rpgfit-beckend/src/` and `rpgfit-app/src/`.

#### Backend — `src/Application/PsychProfile/Service/`

| Service | Change | Est. LOC |
|---|---|---|
| `XpAwardService` (existing, `src/Application/Character/Service/`) | Accept optional `PsychCompletionBonus` modifier; apply before `xp_daily_cap` clamp; idempotent via `psych_bonus_applied_{Ymd}` per-user flag. | +60 |
| `CheckInService` (existing) | Extend `submit()` / `skip()`: on non-skip, fire `PsychCompletionBonusEvent`. Add Q4 persistence in `PhysicalStateAnswer` or extended `PsychCheckIn.q4PhysicalState` field. | +80 |
| `StatusAssignmentService` (existing) | **NO CHANGE** — Q4 does not alter status; status still driven by Q1/Q2/Q3. | 0 |
| `PsychStatusModifierService` (existing) | **NO CHANGE for battle XP** — keep current `getXpMultiplier()` contract. Add new `getCompletionBonusMultiplier(User, date)` method for the health-sync path. | +30 |
| `PsychWorkoutAdapterService` (**NEW**) | `compute(User, ?Q4): WorkoutAdaptation {intMul, volMul, durMul, focusOverride?}` reads matrix from `game_settings.psych.workout_adaptation_matrix`. | +120 (new) |
| `PhysicalStateService` (**NEW**) | `recordQ4(User, WorkoutSession, int 1-5): PhysicalStateAnswer`; idempotent by `WorkoutSession.id`. | +80 (new) |
| `WorkoutPlanGeneratorService` (existing, `src/Application/Workout/Service/`) | Inject `PsychWorkoutAdapterService`; after base-generate, apply `{intMul, volMul, durMul, focusOverride}` to trim exercises count / scale `TargetDuration` / tweak mob-tier selection. | +70 |
| `BattleResultCalculator` | **NO CHANGE.** Battle math stays fair. | 0 |
| `CrisisDetectionService` | Extend: compute `consecutive_weary_days_streak`; expose to plan generator. | +30 |

#### Backend — Domain + Infrastructure

| Path | Change |
|---|---|
| `src/Domain/PsychProfile/Entity/PsychCheckIn.php` | Add `q4PhysicalState: ?int (1-5)`. |
| `src/Domain/PsychProfile/Entity/PhysicalStateAnswer.php` | **NEW.** Post-workout Q4 separate from daily check-in: `id`, `userId`, `workoutSessionId` (unique FK), `physicalState: int 1-5`, `createdAt`. Separate from `PsychCheckIn` because it's triggered by workout-completion event, not daily check-in. |
| `src/Domain/PsychProfile/Enum/CatharsisSelection.php` | **NEW** enum: `MOOD_LIFT`, `CATHARSIS`, `AUTOROUTINE`. Storage column on `PsychCheckIn.catharsisSelection` (nullable). |
| `src/Infrastructure/PsychProfile/Repository/PhysicalStateAnswerRepository.php` | **NEW.** |

#### Backend — Controller + API

| Endpoint | Change |
|---|---|
| `POST /api/psych/check-in` | Body extended: `{mood?, energy?, intent?, q4?, catharsisChoice?, skipped}`. Response unchanged (still returns `assignedStatus + badge`). |
| `POST /api/psych/physical-state` (**NEW**) | Body `{workoutSessionId: uuid, q4: int 1-5}`. Returns `201`. Auth: ROLE_USER. Flag-gated. |
| `GET /api/psych/today` | Response extended: `{..., q4Due: bool, q4Context: {workoutSessionId?}}`. |
| `GET /api/workout/plan/today` (existing) | Auto-applies adaptation; response includes new `adaptation: {intMul, volMul, durMul, focusOverride?, reason: string}` payload so the UI can explain. |

#### Backend — Migration

`migrations/VersionYYYYMMDDHHMMSS_PsychProfilerV2.php`:
- `ALTER TABLE psych_check_ins ADD q4_physical_state TINYINT NULL`
- `ALTER TABLE psych_check_ins ADD catharsis_selection VARCHAR(32) NULL`
- `CREATE TABLE physical_state_answers (id BINARY(16) PRIMARY KEY, user_id BINARY(16) NOT NULL, workout_session_id BINARY(16) NOT NULL UNIQUE, physical_state TINYINT NOT NULL, created_at DATETIME NOT NULL, INDEX (user_id, created_at), FK user_id, FK workout_session_id)`
- Seed new `game_settings`:
  - `psych.completion_bonus_pct` = `10`
  - `psych.completion_bonus_weekly_cap` = `5`
  - `psych.workout_adaptation_matrix` = JSON (§4.3)
  - `psych.catharsis_strike_threshold` = `3`
  - `psych.catharsis_cooldown_days` = `7`
  - `psych.q4_merge_window_hours` = `2`
  - `psych.consecutive_weary_rest_trigger` = `5`

### 6.2 Entities

**Decision:** separate `PhysicalStateAnswer` table instead of bolting Q4
onto `PsychCheckIn`. Reasoning:
- Q4 is triggered by a *workout* event, not a *daily* event.
- Q4 can occur multiple times per day (if user does 2 workouts).
- `PsychCheckIn` is currently 1-per-local-date; adding a per-workout
  column violates the invariant.
- We **also** allow `PsychCheckIn.q4PhysicalState` as a convenience
  column for the "merged check-in" case — filled from the most recent
  PhysicalStateAnswer within the 2-hour merge window (denormalized for
  UI performance).

### 6.3 RN UX additions

| Component | Change |
|---|---|
| `ReflectFlow.tsx` (existing) | When `today.q4Due == true`, extend to 4-step flow; progress indicator "1 of 4" / ... / "4 of 4". |
| `PhysicalStatePicker.tsx` (**NEW**) | 5-option text-only picker matching v1 EnergyPicker cadence; UA + EN labels per §3.3. |
| `PostWorkoutQ4Modal.tsx` (**NEW**) | Triggered from BattleFlow completion screen. Contains PhysicalStatePicker + skip link. Dismissable; 30-min cooldown on re-prompt. |
| `CatharsisPicker.tsx` (**NEW**) | 3-option picker shown only from WEARY/SCATTERED "Train anyway" path. |
| `CompletionBonusBadge.tsx` (**NEW**) | Small text badge on Home header showing "Caliber +10%" for 24h after successful check-in. No animation, no sound. |
| `useTodayPlan()` (existing) | Pick up new `adaptation` payload and surface "Vector підстроїв план" hint on plan screen. |
| `_layout.tsx` (existing interceptor) | No change to interceptor logic itself; only to number-of-steps within ReflectFlow. |

### 6.4 Back-compat

- **Feature flag:** `feature.psych_profiler.v2.enabled` (new, default `false`).
  When off:
  - `/api/psych/check-in` accepts extended body but ignores `q4`,
    `catharsisChoice`.
  - `/api/psych/physical-state` returns `404`.
  - `PsychWorkoutAdapterService::compute()` returns identity
    (`{1.0, 1.0, 1.0, null}`).
  - `CompletionBonusMultiplier` returns `1.0`.
  - RN app omits Q4 step from ReflectFlow.
- **Env var:** `PSYCH_PROFILER_V2_ENABLED` mirrors the existing v1 pattern.
- **Migration is additive only** — no column drops, no type changes to
  existing columns. Rollback = disable flag; data already written is
  orphaned but harmless.

### 6.5 Testing

Mirror v1 test layout:

- `tests/Unit/Application/PsychProfile/PsychWorkoutAdapterServiceTest.php` —
  all 15 matrix cells × flag on/off.
- `tests/Unit/Application/PsychProfile/PhysicalStateServiceTest.php` —
  idempotency, 2h merge window.
- `tests/Unit/Application/Character/XpAwardServiceTest.php` (extended) —
  completion bonus one-shot/day, weekly cap, CHARGED suppression rule.
- `tests/Functional/PsychProfilerV2ControllerTest.php` — extended
  check-in flow, physical-state endpoint, feature-flag 404.
- `src/features/psych/__tests__/PhysicalStatePicker.test.tsx` — rendering,
  5-anchor text content, a11y.
- `src/features/psych/__tests__/CatharsisPicker.test.tsx` — strike
  counting mock + override.

---

## 7. Privacy / GDPR re-audit

### 7.1 Is Q4 special-category data?

**Q4 (session-RPE 1-5) is NOT health data under GDPR Art. 9 strict
reading.** It is exertion-feeling — an effort rating, not a clinical
indicator.

However (v1 §7.1 framework, Bincoletto 2022):
- Combined with Q1 mood + Q3 intent + longitudinal profile + health-sync
  data, the *aggregate* still meets Art. 35 high-risk-processing
  threshold → **DPIA still required** (already in scope from v1).
- Q4 does not add a new special-category classification — it is the
  *least* sensitive of the four questions (a felt-intensity rating is
  less revealing than mood or intent). `[verified URL 2026-04-19]`

### 7.2 Retention

- **Unchanged: 180 days** rolling, per v1 `psych.retention_days`.
- `physical_state_answers` table included in the same retention cycle
  (cron job `app:psych-purge` extended to purge this table).
- Aggregate derived stats (e.g., `mean_q4_30d`, if ever stored) anonymized
  via user-id removal when aggregation happens.

### 7.3 Export / delete

- **`GET /api/psych/export`** (existing) — extend JSON dump to include
  `physical_state_answers` + `catharsis_selection` history. Export is
  complete user-visible history.
- **`DELETE /api/psych/history`** (existing) — extend to hard-erase
  `physical_state_answers` rows for the user. No cascade to
  `workout_sessions` (those aren't psych data).
- **`POST /api/psych/opt-out?erase=1`** — unchanged semantics; v2 entities
  included in erase path.

### 7.4 Consent copy update

Update the consent card to enumerate Q4 explicitly:
> «Після тренування — одне коротке питання про те, як ти перенісся. Це
> допомагає Vector'у підстроїти наступний план.»

### 7.5 Special-category implications

- The CATHARSIS selection history is the most sensitive v2 data point —
  chronic selection is a proximal indicator of exercise-dependence risk
  (§5.2 literature). Even without a formal EAI screen, the implication
  is there.
- Mitigation: CATHARSIS history is **not exposed to the user in any
  trend view in beta**. Stored for the 3-strike algorithm only; purged
  at 180 days; never exported to 3rd parties.
- **No inference pipeline.** We do not classify users as
  "at-risk-for-exercise-dependence" and act on it — that would trigger
  clinical-app App Review flags and GDPR Art. 22 automated-decision-making
  rules. The 3-strike rail is deterministic and user-facing, not a hidden
  profile.

### 7.6 DPIA addendum

Write a short addendum to the v1 DPIA document covering:
- New data points (Q4, catharsis selection)
- New processing (workout adaptation matrix application)
- Risk re-assessment: still "high-risk processing" under Art. 35
  (combined mood+intent+physical+behavior profile); DPIA still required;
  no additional special-category data added.
- Retention / export / delete unchanged.

---

## 8. Open questions (founder decisions required)

1. **Completion bonus: one-shot per day or per answer?**
   - Recommendation: **one-shot per day**. Skip = 0. First non-skip answer
     = bonus. Second non-skip answer same day (extremely rare) = no bonus.
   - Rationale: preserves idempotency semantics, blocks trivial farming.
2. **Weekly cap: 5 of 7 days?**
   - Recommendation: **yes, 5/7**. Models realistic EMA adherence;
     keeps the bonus in habit-formation zone rather than dominating.
   - Alternative: no cap, but soft-decay after 5 consecutive days. More
     complex; same net effect; reject.
3. **Q4 triggered post-workout only, or also on skipped-workout days?**
   - Recommendation: **post-workout only**. Asking RPE without a session
     is meaningless.
   - Alternative v3: add a separate morning "readiness 1-5" (Hooper
     derivative) on off-days. Out of scope for v2.
4. **Difficulty adaptation: at plan-gen time or at runtime battle
   resolution?**
   - Recommendation: **at plan-gen time**. Cleaner, visible, reversible.
     Battle math stays fair.
5. **Option B frequency cap: 3 selections in 7 days or 4 in 7 days?**
   - Recommendation: **3 in 7 days**. Conservative; false-positive rate
     tolerable; tunable via `game_settings` without redeploy. Tune up to
     4 if beta shows friction.
6. **Should Q4 on a CHARGED-status user who reports Q4=5 trigger
   a soft-rest nudge for the next session?**
   - Recommendation: **yes** — the matrix already routes CHARGED+wrecked
     to 95%/95%/90%/mobility. No extra nudge needed; the plan itself
     signals.
7. **Do we ship v2 completion-bonus and v2 Q4 together, or in separate
   flag-gated phases?**
   - Recommendation: **together**, behind a single `v2.enabled` flag.
     Coupling simplifies testing; separate flags create 4 on/off
     permutations to QA. If data later shows one feature needs rollback
     independently, split the flag.

---

## 9. Red-team (aggressive self-critique)

### Failure mode 1: Overjustification boomerang

**Scenario.** Beta runs for 30 days. Users who initially answered daily
out of curiosity are now answering because of the +10%. Month 2, we
discover completion-rate correlates with bonus presence. When we try
to remove the bonus (because cohort data is noisy), answer-rate **drops
below v1's 52% baseline**.

**Signal.** D30 check-in completion rate > 70% (too high for EMA baseline
of 60-80% — suspicious); paired with a correlation between
`Q1.mood == ENERGIZED` and bonus-active days (suggests positive-bias
farming).

**Mitigation (ship-ready).**
- Analytics: track `psych_check_in_submitted` event with
  `{q1, q2, q3, q4, time_to_complete_ms}`. No PII; aggregates only.
- Dashboard: alert if `time_to_complete_ms p50 < 4000` OR
  `mood_ENERGIZED_rate > 60%` in the rolling week.
- Pre-rollback plan: flag is instant-off. If data shows regime shift
  toward extrinsic, disable bonus, monitor re-stabilization at v1
  baseline.

### Failure mode 2: Adaptation over-trims the plan, user loses progress

**Scenario.** User is WEARY for 4 days, then CHARGED. Our matrix cell
for CHARGED+fresh is +10%/+15%, but the user has just come off a
de-training week. +15% volume on a detrained user is *above* ACSM's
10%-per-week ceiling. Risk: injury, rage-quit.

**Mitigation.**
- Add a fourth column to the matrix: `recent_weary_days_adjustment`.
  If `consecutive_weary_last_7d >= 3`, the CHARGED+fresh cell is
  downgraded to CHARGED+normal (i.e., +5%/+10% not +10%/+15%).
- Encoded in the `raise_allowed` predicate (§4.4) as
  `AND no_weary_in_last_3_days`.

### Failure mode 3: CATHARSIS strike rail fires on legitimate HIIT users

**Scenario.** A user who genuinely prefers HIIT training comes off an
unrelated stressful week. They pick CATHARSIS 3x Mon-Wed. On the 4th
time Thursday, the rail hides the option and the user feels gaslit.

**Mitigation.**
- Track CATHARSIS selections only when they follow a bad-mood trigger
  (WEARY/SCATTERED/DRAINED + PUSH). Users selecting HIIT-style
  workouts directly from the plan flow do NOT count.
- Rail copy makes this explicit: "Vector помітив третій push-after-drain"
  — not "third HIIT this week." Distinguishes the regulated pattern from
  general intensity preference.
- Tunable threshold in `game_settings`; founder can widen to 4 or 5 if
  false-positive rate spikes in beta feedback.

### Failure mode 4: Merged check-in UX confuses

**Scenario.** User worked out at 9am, answered Q4 ("solid push"). At 11am
opens the app — daily check-in still pending. System shows 4-question
flow with Q4 pre-filled. User doesn't understand why.

**Mitigation.**
- On the merged flow's Q4 screen, show: "Твій post-workout Q4 — [answer].
  Залиш чи зміни?" — explicit, reversible.
- If 2h window elapsed, Q4 is re-asked as blank.
- Tune `psych.q4_merge_window_hours` via setting; default 2.

---

## 10. Scope recommendation

### Option A — REJECT expansion

- **Scope:** ship v1 as-is. No v2.
- **Pros:** Zero risk. Zero SDT-violation exposure. Team focuses on
  beta-scope (Day of Rozkolу, portals, mobs).
- **Cons:** Loses the founder's direction. Leaves Q4 (evidence-strong
  add) on the table. The completion-bonus risk exists only if we ship
  the bonus.
- **When to pick:** If beta-1 retention data is already strong without
  v2 OR if beta-1 found the v1 ritual had a compliance issue that v2
  can't fix. Not our recommendation.
- **Dev weeks:** 0.

### Option B — MINIMAL v2 (RECOMMENDED)

- **Scope:** completion bonus + Q4 only. No difficulty adaptation matrix.
  No bad-mood clarifier. Existing v1 guardrail (WEARY+PUSH → soft copy)
  stays.
- **Pros:** Ships the founder's two highest-priority items. Cheap,
  reversible, testable. Difficulty adaptation and catharsis flow can
  be v2.1 once we have a month of Q4 data to tune the matrix.
- **Cons:** Doesn't close the feedback loop on "Vector tunes the plan to
  how you feel." Some of the founder's narrative power is deferred.
- **Dev weeks (estimate):**
  - Backend: 4-5 days (Q4 entity + service, completion-bonus flow,
    XpAwardService wire-up, migration, tests).
  - RN: 3-4 days (PhysicalStatePicker, PostWorkoutQ4Modal,
    CompletionBonusBadge, ReflectFlow 4-step).
  - Privacy / DPIA addendum: 1 day.
  - QA: 2 days.
  - **Total: ~2.5-3 dev-weeks** (matches v1 implementation cadence).

### Option C — FULL v2

- **Scope:** everything in this report — completion bonus, Q4, full
  adaptation matrix, bad-mood clarifier with 3 options + 3-strike rail.
- **Pros:** Complete narrative: "Vector reads you, tunes the plan, guards
  your mood regulation." High retention / differentiation value.
- **Cons:** Largest surface area; highest test burden; adaptation matrix
  is a hypothesis not a fact — needs real data to tune. Shipping it
  blind risks plan quality regression.
- **Dev weeks:**
  - Backend: 9-11 days (B scope + PsychWorkoutAdapterService +
    CrisisDetectionService extension + WorkoutPlanGenerator integration +
    CatharsisSelection flow + strike logic).
  - RN: 6-8 days (B scope + CatharsisPicker + strike modal + plan
    "Vector tuned" hint UI).
  - Privacy: 1 day (same addendum).
  - QA: 4-5 days (matrix has 15 cells; strike logic has timing edge-cases).
  - **Total: ~5-6 dev-weeks.**

### Recommendation: **Option B.**

- Rationale:
  1. Completion bonus + Q4 are **independent**; Q4 doesn't require
     adaptation to be useful (it's also a pure signal for research).
  2. Adaptation matrix is better tuned with a month of real Q4 data;
     shipping blind is Bompa-on-paper, not Bompa-in-practice.
  3. Founder's red-line override is served (bonus ships). Research's
     counter-argument is honored via mitigations. Balance preserved.
  4. The bad-mood clarifier has the highest risk-surface (exercise
     addiction literature; nuanced copy); deserves careful A/B before
     locking in options and thresholds.
  5. Dev-weeks (3 vs. 6) delta lets beta-2 stabilize before v2.1 drops.

- Path to v2.1 (after ~4 weeks of Option B data):
  - Ship adaptation matrix with data-tuned cells.
  - Ship bad-mood clarifier with a narrower strike threshold informed by
    observed CATHARSIS selection distribution.
  - Cost: 3-4 more dev-weeks post-beta.

---

## 11. Sources

### Peer-reviewed / authoritative (verified 2026-04-19)

1. Foster, C. (1998). *Monitoring training in athletes with reference to
   overtraining syndrome*. Med Sci Sports Exerc, 30(7), 1164-1168.
   [PubMed 9662690](https://pubmed.ncbi.nlm.nih.gov/9662690/)
2. Foster, C., Florhaug, J. A., Franklin, J. et al. (2001). *A new
   approach to monitoring exercise training*. J Strength Cond Res, 15(1),
   109-115. [PubMed 11708692](https://pubmed.ncbi.nlm.nih.gov/11708692/)
3. Haddad, M., Stylianides, G., Djaoui, L., Dellal, A. & Chamari, K.
   (2017). *Session-RPE method for training load monitoring: validity,
   ecological usefulness, and influencing factors*. Front Neurosci, 11,
   612. [PMC5673663](https://pmc.ncbi.nlm.nih.gov/articles/PMC5673663/)
4. Borg, G. (1982). *Psychophysical bases of perceived exertion*. Med
   Sci Sports Exerc, 14(5), 377-381.
   [Physiopedia summary](https://www.physio-pedia.com/Borg_Rating_Of_Perceived_Exertion)
5. Soriano-Maldonado, A. et al. (2014). *A learning protocol improves the
   validity of the Borg 6-20 RPE scale during indoor cycling*. Int J
   Sports Med.
   [PubMed 24165960](https://pubmed.ncbi.nlm.nih.gov/24165960/)
6. Vickers, A. J. (2001). *Time course of muscle soreness following
   different types of exercise*. BMC Musculoskelet Disord, 2, 5.
   [PMC59671](https://pmc.ncbi.nlm.nih.gov/articles/PMC59671/)
7. Lau, W. Y. & Nosaka, K. (2005). *Convergent evidence for construct
   validity of a 7-point Likert scale of lower limb muscle soreness*.
   Clin J Sport Med, 17(6), 502-506.
   [PubMed 17993794](https://pubmed.ncbi.nlm.nih.gov/17993794/)
8. Hooper, S. L. & Mackinnon, L. T. (1995). *Monitoring overtraining in
   athletes: recommendations*. Sports Med, 20(5), 321-327 (via
   athletemonitoring.com reference).
9. McLean, B. D., Coutts, A. J., Kelly, V., McGuigan, M. R. & Cormack, S. J.
   (2010). *Neuromuscular, endocrine, and perceptual fatigue responses
   during different length between-match microcycles in professional
   rugby league players*. Int J Sports Physiol Perform.
   [Reference](https://pmc.ncbi.nlm.nih.gov/articles/PMC7534939/)
10. Saw, A. E., Main, L. C. & Gastin, P. B. (2016). *Monitoring the
    athlete training response: subjective self-reported measures trump
    commonly used objective measures: a systematic review*. Br J Sports
    Med. [PMC4789708](https://pmc.ncbi.nlm.nih.gov/articles/PMC4789708/)
11. Ekkekakis, P. & Petruzzello, S. J. (1999). *Acute aerobic exercise
    and affect: current status, problems and prospects regarding
    dose-response*. Sports Med, 28(5), 337-374.
    [PubMed 10593646](https://pubmed.ncbi.nlm.nih.gov/10593646/)
12. Ekkekakis, P. (2009). *The Dual-Mode Theory of affective responses
    to exercise in metatheoretical context: I*. Int Rev Sport Exerc
    Psychol, 2(1), 1-33.
    [Tandf](https://www.tandfonline.com/doi/abs/10.1080/17509840802705920)
13. Bartholomew, J. B., Morrison, D. & Ciccolo, J. T. (2005). *Effects of
    acute exercise on mood and well-being in patients with major
    depressive disorder*. Med Sci Sports Exerc, 37(12), 2032-2037.
    [Semantic Scholar](https://www.semanticscholar.org/paper/Effects-of-acute-exercise-on-mood-and-well-being-in-Bartholomew-Morrison/2fc8be5bafef8a690b881b45d67b9c55cf114f67)
14. Craft, L. L. & Perna, F. M. (2004). *The benefits of exercise for
    the clinically depressed*. Prim Care Companion J Clin Psychiatry,
    6(3), 104-111.
    [PMC474733](https://pmc.ncbi.nlm.nih.gov/articles/PMC474733/)
15. Barbour, K. A., Edenfield, T. M. & Blumenthal, J. A. (2007).
    *Exercise as a treatment for depression and other psychiatric
    disorders: a review*. J Cardiopulm Rehabil Prev, 27(6), 359-367.
    (Cited per AAFP / Medscape reviews
    [AAFP](https://www.aafp.org/pubs/afp/afp-community-blog/entry/the-power-of-prescription-exercise-and-mood.html))
16. Meyer, J. D., Koltyn, K. F. et al. (2016). *Influence of exercise
    intensity for improving depressed mood in depression: a dose-response
    study*. Behav Ther.
    [PubMed 27423168](https://pubmed.ncbi.nlm.nih.gov/27423168/)
17. Deci, E. L. (1971). *Effects of externally mediated rewards on
    intrinsic motivation*. J Pers Soc Psychol, 18(1), 105-115.
    [Ryan Ryan DiDomenico 2019 commemorative paper](https://selfdeterminationtheory.org/wp-content/uploads/2019/03/2019_RyanRyanDiDomencio_Deci1971.pdf)
18. Deci, E. L., Koestner, R. & Ryan, R. M. (1999). *A meta-analytic
    review of experiments examining the effects of extrinsic rewards
    on intrinsic motivation*. Psychol Bull, 125(6), 627-668.
    [PubMed 10589297](https://pubmed.ncbi.nlm.nih.gov/10589297/)
19. Deci, E. L., Koestner, R. & Ryan, R. M. (2001). *Extrinsic rewards
    and intrinsic motivation in education: reconsidered once again*.
    Rev Educ Res, 71(1), 1-27.
    [Sage](https://journals.sagepub.com/doi/10.3102/00346543071001001)
20. Hanus, M. D. & Fox, J. (2015). *Assessing the effects of gamification
    in the classroom: a longitudinal study on intrinsic motivation, social
    comparison, satisfaction, effort, and academic performance*. Comput
    Educ, 80, 152-161.
    [Semantic Scholar](https://www.semanticscholar.org/paper/Assessing-the-effects-of-gamification-in-the-A-on-Hanus-Fox/dff76a9862467d426113ec530f83942016ae3a97)
21. Wood, W. & Neal, D. T. (2007). *A new look at habits and the
    habit-goal interface*. Psychol Rev, 114(4), 843-863.
    [PubMed 17907866](https://pubmed.ncbi.nlm.nih.gov/17907866/)
22. Fogg, B. J. (2019). *Tiny Habits: The Small Changes That Change
    Everything*. Houghton Mifflin Harcourt.
    [Behavior Model](https://www.behaviormodel.org/)
23. Diefenbach, S. & Müssig, A. (2019). *Counterproductive effects of
    gamification: an analysis on the example of the gamified task manager
    Habitica*. Int J Hum Comput Stud, 127, 190-210.
    [ScienceDirect](https://www.sciencedirect.com/science/article/abs/pii/S1071581918305135)
24. Terry, A., Szabo, A. & Griffiths, M. (2004). *The Exercise Addiction
    Inventory: a new brief screening tool*. Addict Res Theory, 12(5),
    489-499.
    [PMC1725234](https://pmc.ncbi.nlm.nih.gov/articles/PMC1725234/)
25. Szabo, A., Demetrovics, Z. & Griffiths, M. D. (2019). *The
    psychometric evaluation of the Revised Exercise Addiction Inventory*.
    J Behav Addict, 8(1), 157-161.
    [PMC7044604](https://pmc.ncbi.nlm.nih.gov/articles/PMC7044604/)
26. Colledge, F., Sattler, I. et al. (2020). *Mental disorders in
    individuals with exercise addiction — a cross-sectional study*.
    Front Psychiatry.
    [Frontiers](https://www.frontiersin.org/articles/10.3389/fpsyt.2021.751550/full)
27. Wewege, M. A., Ahn, D., Yu, J., Liou, K. & Keech, A. (2018).
    *High-intensity interval training for patients with cardiovascular
    disease — is it safe? A systematic review*. J Am Heart Assoc, 7(21),
    e009305. [AHA](https://www.ahajournals.org/doi/10.1161/JAHA.118.009305)
28. Taylor, J. L., Holland, D. J., Spathis, J. G. et al. (2019).
    *Guidelines for the delivery and monitoring of high intensity
    interval training in clinical populations*.
    [PubMed 30685470](https://pubmed.ncbi.nlm.nih.gov/30685470/)
29. Shiffman, S., Stone, A. A. & Hufford, M. R. (2008). *Ecological
    momentary assessment*. Annu Rev Clin Psychol, 4, 1-32.
    (Cited from v1 §2.5 continuity.)
30. Bompa, T. & Haff, G. (2009). *Periodization: Theory and Methodology
    of Training* (5th ed.). Human Kinetics. (Cited per NASC review.)
    [NASC](https://www.nascresearch.com/periodization-explained-the-science-behind-peak-performance/)
31. Bell, L., Ruddock, A., Maden-Wilkinson, T. & Rogerson, D. (2023).
    *Deloading practices in strength and physique sports: a cross-
    sectional survey*. Int J Strength Cond.
    [PMC10948666](https://pmc.ncbi.nlm.nih.gov/articles/PMC10948666/)
32. Coleman, M., Schoenfeld, B. J. et al. (2024). *Gaining more from
    doing less? The effects of a one-week deload period during supervised
    resistance training on muscular adaptations*.
    [PMC10809978](https://pmc.ncbi.nlm.nih.gov/articles/PMC10809978/)
33. Lu, D., Howle, K. et al. (2021). *A novel approach to training
    monotony and acute-chronic workload index: a comparative study in
    soccer*. Front Sports Act Living, 3, 661200.
    [PMC8200417](https://pmc.ncbi.nlm.nih.gov/articles/PMC8200417/)
34. American College of Sports Medicine (2021/2024). *ACSM's Guidelines
    for Exercise Testing and Prescription* (11th/12th ed.). Wolters Kluwer.
    [ACSM](https://acsm.org/education-resources/books/guidelines-exercise-testing-prescription/)
35. Bincoletto, G. (2022). *Mental data protection and the GDPR*. J Law
    Biosci, 9(1), lsac006.
    [PMC9044203](https://pmc.ncbi.nlm.nih.gov/articles/PMC9044203/)
36. Ryan, R. M., Rigby, C. S. & Przybylski, A. (2006). *The motivational
    pull of video games: a self-determination theory approach*. Motiv
    Emot, 30(4), 344-360. (Cited from v1 §2.3 continuity.)
37. Hartmann, H., Naguib, L. et al. (2024). *Motivational Interviewing to
    promote healthy lifestyle behaviors: evidence, implementation, and
    digital applications*.
    [PMC12526391](https://pmc.ncbi.nlm.nih.gov/articles/PMC12526391/)

### Supporting / practitioner

38. Healthy with Science — *Autoregulation and periodization for
    resistance training progression*.
    [Lesson 6](https://www.healthierwithscience.com/general-exercise-course/lesson-6-autoregulation-and-periodization-for-resistance-training-progression/)
39. SimpliFaster — *Principles for the periodization of volume and
    intensity with autoregulation*.
    [SimpliFaster](https://simplifaster.com/articles/periodization-volume-intensity-autoregulation/)
40. Decision Lab — *Overjustification effect* (pedagogical summary).
    [The Decision Lab](https://thedecisionlab.com/biases/overjustification-effect)
41. TED Ideas — *How you can use the power of celebration to make new
    habits stick* (Fogg).
    [ideas.ted.com](https://ideas.ted.com/how-you-can-use-the-power-of-celebration-to-make-new-habits-stick/)
42. AI Competence — *Operant conditioning in gamification: the
    psychology of engagement*.
    [aicompetence.org](https://aicompetence.org/operant-conditioning-in-gamification/)
43. athletemonitoring.com — *Wellness monitoring, Hooper, POMS, REST-Q,
    DALDA* (Hooper summary).
    [athletemonitoring.com](https://www.athletemonitoring.com/wellness-monitoring/)

### Internal artifacts

- `[internal]` `docs/vision/psych-profiler-v2-workout-adaptive.md`
- `[internal]` `BA/outputs/10-psychology-research.md`
- `[internal]` `docs/superpowers/specs/2026-04-18-psych-profiler-beta-impl.md`
- `[internal]` `rpgfit-beckend/docs/BUSINESS_LOGIC.md` §§4, 7, 10, 14
- `[internal]` `rpgfit-beckend/src/Application/Workout/Service/WorkoutPlanGeneratorService.php`
- `[internal]` `rpgfit-beckend/src/Application/Battle/Service/BattleResultCalculator.php`
