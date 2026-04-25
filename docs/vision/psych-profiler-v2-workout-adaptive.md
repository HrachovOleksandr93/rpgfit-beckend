# Psych Profiler v2 — 4-Q + Workout-Adaptive Plan

> **Статус:** IDEA — research required. Founder-driven expansion of the
> beta-ready Psych Profiler (`docs/vision/psychology-profiler.md` +
> `docs/superpowers/specs/2026-04-18-psych-profiler-beta-impl.md`).
> **Date:** 2026-04-19.
> **Target:** phase 2 of the profiler, ships AFTER beta-1 stabilises.

## 1. What's new vs beta v1

### 1.1 Completion XP bonus (founder override)

**Decision:** **+10% XP bonus** for the day when the user **answers** the
check-in (any answer counts — skip ≠ bonus).

**Override:** beta-1 research (`BA/outputs/10-psychology-research.md §4.4`)
explicitly avoided rewarding check-in completion (SDT red-line: Ryan &
Deci; Hanus & Fox 2015 — "extrinsic reward for self-report undermines
intrinsic motivation"). Founder accepts the risk — **the bonus is small
enough that it's a nudge, not a paywall**. Research agent must propose
mitigations (e.g. cap the bonus at a reasonable per-week ceiling, soft-cap
after 5 days of answering, or frame it as "BRP calibration reward" not
"psych reward").

**Implementation seed:**
- Add `psych.completion_bonus_pct` to `game_settings`, default `10`.
- CheckInService emits a `PsychCompletionBonus` domain event on a non-skip
  answered check-in.
- XpAwardService reads a per-user one-shot flag `psych_bonus_applied_{Ymd}`
  and applies the %age multiplicatively to the day's next health-sync XP.
- Idempotent: even if user syncs health 3 times after answering, the bonus
  applies once.

### 1.2 Q4 — post-workout physical state

**Concept:** after each workout AND once daily (merged into check-in when
both happen same day), ask "how does your body feel right now?"

**Candidate scales to research:**
- **RPE Borg 6-20** — gold standard but requires explanation.
- **CR10 (Category-Ratio 10)** — simpler "how hard" scale.
- **Session-RPE** (Foster et al. 2001) — single post-session rating,
  validated for training load.
- **Muscle Soreness DOMS 1-5** — subjective soreness.
- **Recovery readiness 1-5** — felt recovery for next session.

Research agent must:
1. Weigh validity, literacy requirements, test-retest reliability.
2. Pick ONE canon for beta-2 (or a composite of 2 items).
3. Define wording (UA + EN, text-only, matching v1 style).
4. Map answers to the existing `PsychStatus` enum OR a new parallel
   `PhysicalState` enum.

**Trigger rules:**
- Daily check-in extends from 3 to 4 questions.
- Extra prompt appears **after** any completed workout (session-RPE
  pattern) IF the user has opted in — separate from daily check-in.

### 1.3 Workout-difficulty adaptation

**Requirement:** given current `PsychStatus` + physical-state answer,
adjust the next generated workout plan so it's **passable but not
trivial**.

**Axes to tune:**
- Volume (sets × reps)
- Intensity (mob tier / weight percentage)
- Duration (minutes / exercises count)
- Cardio–strength mix
- Rest intervals

**Starter mapping (research agent must validate):**

| Status                 | Intensity | Volume   | Duration | Focus            |
|------------------------|-----------|----------|----------|-------------------|
| CHARGED                | +10 %     | +15 %    | baseline | new-challenge    |
| STEADY                 | baseline  | baseline | baseline | baseline          |
| DORMANT                | −20 %     | −15 %    | −20 %    | mobility / walk  |
| WEARY                  | −30 %     | −30 %    | −25 %    | recovery         |
| SCATTERED              | −10 %     | −15 %    | −10 %    | focus / breath   |

Physical Q4 can further modulate ± up to 10 %.

**Red line:** never RAISE difficulty for a user who says they feel bad.
Adjustments are asymmetric — Psych can only add load when status is
Charged AND physical-state is Ready. Everything else biases toward
reduction or maintenance.

### 1.4 Bad-mood intent clarification sub-flow

**Scenario:** user has `WEARY` or `SCATTERED` status (or answered
`DRAINED` mood) AND still selects Intent = `PUSH`.

**Currently (beta v1):** this combination is **rejected** — the rule
engine assigns `WEARY` and a soft-copy guardrail is shown.

**v2 proposal:** ask one follow-up question to clarify intent:

> "Ти відчуваєш себе виснажено, але хочеш тренуватися. Що саме шукаєш?"

Options:

- **"Легке — розвіятися"** → low-intensity mobility workout, 15-20 min,
  status ≈ DORMANT effects.
- **"Побити себе, щоб вибити"** → classic "mood regulation through
  exertion". Literature (Ekkekakis 2009, Barbour 2007) supports this
  pathway BUT cautions on overreach. Offer a capped-intensity structured
  session with HR gating.
- **"Структурований план — не думати"** → offer a predefined routine,
  auto-populate everything.

**Rule:** the follow-up question is offered ONLY if the user explicitly
clicks "Train anyway" from the Weary/Scattered dialog. Default path is
still "rest is progress".

---

## 2. Research scope for the agent

Please produce `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/11-psych-profiler-v2-research.md` covering:

### 2.1 Completion XP bonus
- Literature review on extrinsic reward for behavioural self-monitoring:
  steelman BOTH sides (Hanus/Fox vs. reinforcement-based habit literature
  e.g. Wood & Neal 2007).
- Design trade-offs: fixed 10 % vs weekly cap vs streak-decay.
- Gaming risk: can a user farm this by answering trivially? (Yes → add
  `answered_in_<10s` anomaly log; not required for v2 ship).
- Founder's ruling locked; agent must only recommend **mitigations**, not
  reopen the decision.

### 2.2 Q4 physical state
- Full comparative review of RPE / CR10 / Session-RPE / DOMS / Recovery
  readiness scales.
- Pick ONE + exact wording (UA + EN).
- Validate with a brief red-team: literacy, response time ≤ 10 s,
  social-desirability bias.

### 2.3 Workout-difficulty adaptation
- Map PsychStatus × Q4 → adaptation formula (tune the starter table).
- Interaction with existing `BattleResultCalculator` difficulty tiers
  and `WorkoutPlanGenerator`.
- Asymmetric-adjustment rule: precise upper-bound for load increase.
- Edge cases: streak of 5+ WEARY days — offer a mandatory rest day?

### 2.4 Bad-mood intent clarification
- Literature on mood regulation via exercise (Ekkekakis, Petruzzello).
- Design the follow-up question (one question only, max 3 options).
- Safety rail: if user picks "побити себе" AND their last 3 workouts
  were already in that mode → soft-nudge toward rest.

### 2.5 Privacy + GDPR
- Q4 physical state is not sensitive medical data, but the aggregate is.
  Re-run the DPIA section to include the 4th question.
- Retention policy stays 180 days.

### 2.6 Implementation plan
- Phase-map against the beta-1 code that already ships
  (`docs/superpowers/specs/2026-04-18-psych-profiler-beta-impl.md`).
- Identify minimum viable v2 subset (founder ships when?).

### 2.7 Open questions (5-7 decisions founder must answer)

---

## 3. Until research completes

**v2 is NOT scheduled.** Beta ships with the current 3-Q profile (already
committed on main). All v2 work blocks on the research report.

**Do NOT** implement the completion XP bonus, Q4, or difficulty adapter
until the research doc is reviewed and a founder sign-off sits in a
follow-up decision doc.

## 4. Related

- `docs/vision/psychology-profiler.md` — beta v1 vision
- `BA/outputs/10-psychology-research.md` — beta v1 research (red-lines
  that this spec knowingly overrides in §1.1)
- `docs/superpowers/specs/2026-04-18-psych-profiler-beta-impl.md` — beta
  v1 impl spec (active)
- `rpgfit-beckend/docs/BUSINESS_LOGIC.md §14` — psych profiler entry
- Workout-plan generation — `rpgfit-beckend/src/Application/Workout/`
