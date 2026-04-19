# 10 — Psychology Profiler: Daily 3-Question Status Assignment — Research Report

> **Agent:** BA Analyst + Psychologist Researcher (consulting / expert-witness mode).
> **Date:** 2026-04-18.
> **Input:** `docs/vision/psychology-profiler.md` (founder direction, starter schema),
> `BA/outputs/08-mental-stats-research.md` (prior mental-data analysis),
> `docs/vision/product-decisions-2026-04-18.md` (D1-D5), `emotional-hooks.md`,
> `docs/lore-extracts/realms-canon.md`, `BUSINESS_LOGIC.md`.
> **Tone:** consulting / expert-witness. Recommendations are actionable.
>
> **Source markers:**
> - `[verified URL 2026-04-18]` — web-verified in this session (full URL list §12).
> - `[assumption — needs verification]` — inference, not a verified fact.
> - `[internal]` — grounded in an RPGFit artifact.

---

## 1. TL;DR (5 bullets)

1. **Canon: ship a 3-question pictorial check-in anchored on *mood (valence)*,
   *energy (arousal)*, and *intent (Fogg ability × motivation)* — with a
   Pick-A-Mood / emoji visual for Q1.** This is the minimum set that samples
   Russell's 2-D affect grid plus BJ Fogg's behavioral readiness without
   drifting into clinical territory. Drop the "5-point valence → 5-point
   arousal" identical-shape design from the starter schema; use a
   **Pick-A-Mood-style character picker** for Q1 (four mood quadrants + neutral)
   and a **clear energy slider 1-5** for Q2. Q3 stays **rest / maintain /
   push** as a 3-option enum. Total time to complete < 10 s (EMA-compliant;
   Shiffman & Stone 2008 frequency guidance).
2. **Taxonomy: ship FIVE named statuses** — **Charged, Steady, Dormant, Weary,
   Scattered** — derived deterministically from Q1×Q2×Q3 with a tie-breaker
   rule. Five is the sweet spot between "too few = no signal" (2-3) and "too
   many = confusing UI + taxonomy drift" (7+). The starter schema's 6-status
   table (Dormant/Steady/Charged/Weary/Anxious/Content) is good but we **drop
   "Anxious" (clinical framing risk)** and **merge "Content" into "Steady"
   with a sub-flavor**. Full matrix §5.
3. **Top-3 risks (with mitigations):**
   - **Clinical overreach / "McMindfulness" backlash** (Purser 2019,
     mental-health data sensitivity). Mitigation: **no diagnostic language
     anywhere**, no "score," no leaderboard, rename badges with lore (Vector
     dialect) not psychology jargon.
   - **GDPR Art. 9 exposure.** Under the Oxford JLB analysis (Bincoletto 2022),
     bare mood/emotion data is often *not* Art. 9 special-category — but our
     combination of mood + intent + game-behavior profiling hits the
     "systematic profiling / high-risk processing" thresholds that still
     trigger a DPIA and stronger consent. Mitigation: DPIA before launch,
     opt-in default, first-class export/delete, encryption at rest.
   - **Survey fatigue + skip spiral.** Even 3 questions, asked every day,
     compound. Mitigation: **ask max once per calendar day**, skip is always
     free, after 3 consecutive skips the modal hides for 7 days before
     re-offering.
4. **Scope: recommend Option B — MINIMAL IN-BETA** (see §10). Ship Q1/Q2/Q3
   + 5-status taxonomy + single XP multiplier + one notification-tone variant.
   **Do NOT ship difficulty tuning, streak bundling, or trend sparklines in
   beta.** ~3 dev-weeks total. Enables the **daily ritual** — the real
   retention mechanism — without the reputational and balance risk of a full
   rules engine.
5. **One-sentence value case for founder:** "A 10-second daily check-in that
   gives every player a named in-game status ('Charged', 'Weary'...) which
   gently tunes their XP and push-copy that day — costing 3 dev-weeks, adding
   a D7-retention hook no fitness competitor has, and integrating cleanly
   with the Vector lore without any clinical or diagnostic language."

---

## 2. Psychological frameworks (evidence-weighted)

This section scores each framework on two axes: **evidentiary strength**
(peer-reviewed / replicated vs. practitioner-popular) and **fit for RPGFit's
3-question daily check-in**. The weight we place on each drives §4 and §5.

### 2.1 PERMA (Seligman; Butler & Kern 2016 PERMA-Profiler)

- **Model.** Seligman's "flourishing" framework: Positive emotion, Engagement,
  Relationships, Meaning, Accomplishment. The PERMA-Profiler is the 23-item
  measurement tool: 15 pillar items (3 per domain) + 8 fillers covering
  negative emotion, loneliness, physical health, overall wellbeing, answered
  on a 0-10 Likert scale.
- **Evidence.** **Strong.** Butler & Kern (2016) built the instrument across
  3 studies, N=7,188; validated across 8 additional studies, N=31,966;
  acceptable fit, cross-time consistency, content/convergent/divergent
  validity `[verified URL 2026-04-18]`. Subsequent cross-cultural validations
  (PERMA-Profiler Mexico 2022; adolescent version 2024; PERMA+4 short-form
  IRT 2023) have replicated the structure.
- **What we borrow.** The *principle* that wellbeing is multi-domain (not
  just mood). We explicitly **do NOT** embed the full PERMA-Profiler — it is
  23 items, violates our 3-question ceiling, and is a research tool not a
  consumer-facing ritual. We *may* use a PERMA-5 short form as an **optional
  monthly pulse** (§9), not daily.
- **Red-team.** PERMA has been criticized as overlapping heavily with generic
  "positive affect" — Goodman et al. (2018) showed the 5 factors load onto
  a single dimension in many samples. So PERMA's multi-dimensionality may be
  more claim than measurement. Fine for our purposes: we use *one* Q to
  capture positive affect (Q1), *one* for engagement-readiness (Q3), and
  treat "relationships / meaning / accomplishment" as emergent from in-game
  behavior, not from a survey.

### 2.2 Russell's circumplex model of affect (1980)

- **Model.** All affect sits in a 2-D space: valence (pleasure ↔ displeasure)
  × arousal (high ↔ low). "Anxious" = low-valence/high-arousal; "calm" =
  high-valence/low-arousal. Russell 1980 is one of the most-cited papers in
  emotion research `[verified URL 2026-04-18]`.
- **Evidence.** **Very strong** as a dimensional summary — replicated across
  cultures, age groups (including ASD samples, PMC 2015), neuroimaging
  (Posner/Russell 2005 integrative review). The 2-D structure is robust.
- **Criticism.**
  - Discrete emotions (disgust, surprise, awe) don't fit neatly on the 2-D
    plane — some argue a third dimension (dominance / potency) is needed.
  - "Word boundaries" are fuzzy: Russell himself noted emotion words don't
    have hard category edges.
  - Pure self-report is noisy; cultural differences in emotion vocabulary
    matter (Barrett's emotional-granularity work).
- **What we borrow.** Q1 (valence) + Q2 (arousal) are a direct application.
  The **four quadrants** of the circumplex become the backbone of our
  status taxonomy:
  - High-valence / high-arousal → **Charged** (excited/energized)
  - High-valence / low-arousal → **Steady / Content** (calm/content)
  - Low-valence / high-arousal → **Scattered** (tense/anxious — but we avoid
    the clinical word)
  - Low-valence / low-arousal → **Dormant / Weary** (tired/sad)

  This gives our taxonomy a recognized psychological skeleton rather than an
  ad-hoc one.

### 2.3 Self-Determination Theory (SDT; Ryan & Deci; Ryan, Rigby, Przybylski 2006)

- **Model.** Humans have three innate psychological needs: **Autonomy**
  (self-direction), **Competence** (sense of mastery), **Relatedness**
  (connection). Intrinsic motivation flows from meeting them.
- **Evidence.** **Very strong** in gaming. Ryan, Rigby & Przybylski (2006),
  "The Motivational Pull of Video Games: A Self-Determination Theory
  Approach" (*Motivation and Emotion*, 30:344-360) ran 4 studies on
  motivation and wellbeing before/after game play and found autonomy +
  competence perceptions predict enjoyment, preferences, and post-play
  wellbeing change. Led to the PENS (Player Experience of Need Satisfaction)
  scale `[verified URL 2026-04-18]`. Replicated many times since.
- **What we borrow.** Not a question, but a **design constraint**: the
  3-question check-in must preserve autonomy (skippable, no forced answers),
  signal competence (status framed as "your strength right now", not a
  deficit), and support relatedness (the status can feed co-op bonuses in
  social events per D5, without publicly exposing individual psych data).
- **SDT is also the answer to the "gamification of meditation" worry.** The
  literature (Ryan & Deci 2000; Hanus & Fox 2015) is clear that extrinsic
  rewards (badges, points) *undermine* intrinsic motivation when they become
  the reason to do the thing. Implication: **never reward the check-in with
  XP, streaks, or badges for answering truthfully.** The status *itself*
  and its in-game effects are the reward; we don't pay users to self-report.

### 2.4 Fogg Behavior Model (B = M × A × P)

- **Model.** Behavior = Motivation × Ability × Prompt. A prompt only triggers
  behavior above the "action line" where motivation and ability together are
  sufficient.
- **Evidence.** **Weak-to-moderate.** The scoping review by Molan et al.
  (2025, BMC Public Health) `[verified URL 2026-04-18]` found FBM has been
  *used* as a design framework in behavior-change interventions, but the
  model itself has not been validated by RCTs testing its specific
  predictions. The 2009 paper is a conference paper, not peer-reviewed.
  Critiques: FBM ignores reflective motivation (attitudes, beliefs), doesn't
  distinguish intrinsic vs. extrinsic motivation, and assumes motivation/
  ability are independent when they are likely coupled.
- **What we borrow.** Treat FBM as a useful *design heuristic* (it maps
  cleanly to our Q3: "How much are you willing to do?" probes the
  motivation × ability axis), not as a measurement tool with psychometric
  rigor. Q3 answers rest / maintain / push is a 3-point Fogg-ability
  proxy: "What ability-band is the user self-reporting for today?"
- **Red-team.** One could argue Q3 is just duplicating Q2 (energy). We
  separate them because Q2 = felt arousal state, Q3 = willingness to expend.
  A user can have low energy but high push intent ("I'm tired but I want
  to prove something today") — that combination *is* meaningful (→ "Weary"
  status, see §5).

### 2.5 Ecological Momentary Assessment (EMA; Stone & Shiffman 1994; Shiffman, Stone, Hufford 2008)

- **Model.** Ask about current state, in natural context, repeatedly. The
  gold standard for self-report that avoids retrospective bias. Shiffman
  et al. 2008 *Annual Review of Clinical Psychology* is the canonical
  reference.
- **Evidence.** **Strong and foundational** for anything involving repeated
  daily self-report.
- **Key best-practice takeaways for us `[verified URL 2026-04-18]`:**
  - Adherence > 80% is the benchmark for "good" compliance.
  - Adherence drops sharply when burden increases (45-minute intervals or
    multi-minute surveys see adherence <80%).
  - **Event-based + time-based sampling** (a single time-anchored daily
    check-in, triggered by "first daily app open") is well-supported.
  - The question must focus on **current state** ("How are you *right now*?"),
    not recall ("How were you yesterday?").
- **What we borrow.**
  - One check-in per calendar day, triggered by first daily app-open. Not
    multiple daily prompts. Our product loop is daily, not multi-daily.
  - Each question references "right now" / "today", not retrospection.
  - **Target completion time: ≤ 10 s.** This is the EMA-minimization band
    that keeps adherence high.

### 2.6 Emotional granularity (Lisa Feldman Barrett; Kashdan, Barrett, McKnight 2015)

- **Model.** Emotional granularity = the capacity to experience emotions
  with specificity ("frustrated" vs. "angry" vs. "resentful"). High
  granularity is associated with better mental-health outcomes, less
  alcohol/substance use, fewer hospitalizations.
- **Evidence.** **Strong** (Kashdan, Barrett & McKnight 2015 review;
  Tugade, Fredrickson & Barrett 2004). Barrett's theory of "constructed
  emotion" (2017) is actively debated but the granularity finding is
  replicated.
- **What we borrow.** Q1 shouldn't be a bare happy/sad binary. We offer
  a **Pick-A-Mood-style pictorial** with 4-5 distinguishable mood states
  in the four quadrants, which **gently invites granularity** without
  asking the user to type. This is precisely what the "How We Feel" app
  (Yale Center for Emotional Intelligence; RULER program; Mood Meter)
  ships `[verified URL 2026-04-18]`.
- **Red-team.** Full emotional-granularity training is a multi-session
  intervention, not a check-in. We're not teaching granularity; we're just
  not actively suppressing it with a blunt slider. Modest claim.

### 2.7 HRV-anchored subjective recovery (WHOOP / Oura / Fitbit)

- **Model.** Derive a single 0-100 "recovery" or "readiness" score from
  HRV + sleep + RHR + respiratory rate. WHOOP weights HRV ~56% of variance;
  Oura weights HRV ~5%, RHR ~29% `[verified URL 2026-04-18]`.
- **Evidence.** **Contested.** Wellnesspulse and DeGruyter Brill 2025
  reviewed composite recovery scores: no manufacturer discloses the full
  algorithm, no peer-reviewed validation of the composite score predicting
  meaningful outcomes, and in one elite-swimmer study WHOOP's recovery
  score was **not** associated with perceived recovery or stress even
  though HRV itself was.
- **Key finding highly relevant to our design.** The literature's consensus
  closing line is that **"subjective measures trump objective measures
  every time in multiple studies"** when it comes to recovery
  `[verified URL 2026-04-18]`. This is a direct justification for asking
  the user rather than inferring from HRV — especially because RPGFit
  can NOT rely on HRV data symmetrically (per `08-mental-stats-research.md`
  §1, HRV is Apple-Watch-exclusive for passive collection).
- **What we borrow.** The *conclusion* that subjective self-report is a
  legitimate data source for recovery/readiness. The psychology profiler
  is, in part, our version of a "subjective readiness score" — priced at
  3 questions instead of 9 sensor inputs.

### 2.8 Weighting summary

| Framework | Evidence tier | Adoption by us |
|-----------|---------------|-----------------|
| Russell circumplex | Tier 1 (foundational) | Primary — drives Q1+Q2 + quadrant taxonomy |
| SDT + PENS (Ryan & Rigby) | Tier 1 | Primary — design constraint (no extrinsic reward for check-in) |
| EMA (Shiffman & Stone) | Tier 1 | Primary — cadence + wording discipline |
| Emotional granularity (Barrett) | Tier 2 (growing) | Secondary — Pick-A-Mood style for Q1 |
| PERMA (Seligman / Butler & Kern) | Tier 2 | Secondary — optional monthly pulse only (§9) |
| Subjective readiness (WHOOP/Oura literature) | Tier 2 (commercial, contested) | Supporting — justifies "why ask, not infer" |
| Fogg BMAP | Tier 3 (popular, weak evidence) | Design heuristic — shapes Q3 wording, not a metric |

---

## 3. Existing-product analysis

### 3.1 Finch (Self-Care Pet)

- **Hooks:** Daily check-in = "How are you feeling?" 5-point emoji scale →
  bird pet's mood reflects yours. Missing a day does **not** punish; pet
  just waits. Design is built on *compassionate tech* — no HP-loss, no
  streak reset on miss.
- **Scale.** "Top-grossing self-care app" 2023-25; 500K+ reviews across
  App Store + Google Play; 5-star rating both stores `[verified URL
  2026-04-18]`. Public DAU/DAR not disclosed.
- **What we steal.** (a) No-punishment missed day. (b) Translate the
  check-in result into a visible in-world state (pet mood) — for us, the
  Vector's tone / status badge. (c) The language is kind, not clinical.
- **What we avoid.** Finch drives multiple interactions per day (goals,
  breathing exercises, journaling). We want a **single morning ritual**,
  not a second meditation app.
- **Neurodivergent-friendly signal.** Reviews repeatedly cite Finch as
  "ADHD/PTSD-friendly" because it is low-pressure. This is a positive
  signal for our no-streak-penalty design.

### 3.2 Daylio

- **Hooks:** Mood + activity log. 5 mood faces (😄🙂😐🙁😞) + taggable
  activities ("work", "gym"). Long-term trends + charts.
- **Scale.** **18M total downloads** `[verified URL 2026-04-18]`; 4.8-star
  rating 393K reviews Google Play, 4.8-star 45.7K App Store; 76K downloads/
  month; 5+ years as category leader. Wikipedia entry exists.
- **What we steal.** (a) 5-face mood scale is the category default —
  users recognize it instantly. (b) Pairing mood with context (activity
  tag) — for us, context = in-game action / streak / event. (c) Minimal
  daily commitment (<30s) as a design target.
- **What we avoid.** Daylio is a *journaling app*. We are not. We don't
  store long-form notes and don't need a mood chart for every user —
  trend view is optional.

### 3.3 How We Feel (Yale CCCA / RULER)

- **Hooks:** Mood Meter 4-quadrant picker (red, yellow, blue, green)
  mapped to Russell's circumplex. User picks one of ~100 granular emotion
  labels. HealthKit integration. Designed with Dr. Marc Brackett, Yale
  Center for Emotional Intelligence `[verified URL 2026-04-18]`.
- **Evidence moat.** Mood Meter is the instrument used in RULER, an
  evidence-based SEL program in 4000+ schools across 27 countries.
  Academically defensible.
- **What we steal.** (a) **Russell-quadrant pictorial for Q1**. (b) On-device
  data storage default. (c) "Tag what's contributing" optional context
  step — we can add a single one-tap context chip (e.g. "work / home /
  training") if we want extra signal, or skip entirely for the 10-second
  budget.
- **What we avoid.** The full 100-label granularity is too much for a
  daily 10-second check. We stay at 5 states (one per quadrant + neutral),
  not 100.

### 3.4 Headspace Stress Check (brief check-in)

- **Hooks:** Brief check-in scale; historically Headspace piloted
  PHQ-2 / PHQ-4-variant checks.
- **PHQ-4 reference:** Kroenke et al. 2009 — 4-item validated anxiety+
  depression screen, 1-2 minute completion, 0-12 scoring
  `[verified URL 2026-04-18]`.
- **What we DO NOT do.** PHQ-4 is a clinical screening tool. Using it
  (or a thin variant) in our game instantly makes us a "pseudo-medical
  app" — App Store will flag, GDPR Art. 9 applies unambiguously, and we
  violate the red-line in the starter schema ("no diagnostic claims").
  We keep our wording **explicitly non-clinical**.

### 3.5 Oura Readiness Score

- **Hooks:** Single 0-100 score from 9 contributors. Color-coded.
- **Lesson.** Single score > multiple dimensions for user comprehension.
  And yet, as §2.7 shows, the academic validity of composite scores is
  weak. Takeaway: we give the user **one named status** (not a numeric
  score) — easier to grasp than a number and less pseudo-medical.

### 3.6 WHOOP Recovery

- **Hooks:** Recovery Score (green/yellow/red) drives daily "strain
  target" recommendation.
- **Lesson.** The **tight loop "today's score → today's suggested
  intensity"** is the pattern that drives retention for WHOOP. Our
  equivalent: today's status → today's XP multiplier + push tone. This
  loop is the core retention hook, not the score itself.

### 3.7 Fitbit Stress Management

- **Hooks:** 1-100 stress score. Free users see number; Premium sees
  breakdown + 400+ mindfulness sessions.
- **Lesson.** Fitbit *tried* splitting (earlier drafts had sub-scores)
  and collapsed to one score pre-ship. Confirms the taxonomy argument
  (§5): small number of named states > many numeric sub-scores.

### 3.8 Pipedrive / HubSpot — lead status as conceptual model

- **Model.** Pipeline (macro: Subscriber → Lead → MQL → SQL → Customer)
  + Lead Status (micro: New / Contacted / Qualified / Lost) within a
  stage. Stage tells marketing where the person is; status tells sales
  what to do next `[verified URL 2026-04-18]`.
- **Why it works in CRM.** It gives ambiguous human behavior a finite
  label set that a system can act on. That label then drives automation
  (email sequences, assignment rules). Sales reps operate on label, not
  vibes.
- **Direct analogue for us.** User's long-term trend = pipeline (we do
  NOT expose this in beta — too abstract). User's today-status = lead
  status (Charged, Steady...). The status drives **automated rules**:
  XP multiplier, push tone, content recommendation. Behind-the-scenes
  logic is identical to CRM.
- **What to copy carefully.** CRMs love status proliferation (30+
  lead-status options in HubSpot setups). This is a failure mode. Keep
  our taxonomy at **5 statuses** (see §5 ablation).

### 3.9 Habitica — cautionary tale

- **Study.** Diefenbach & Müssig 2019 (*IJHCS*) — 2-week field study of
  45 Habitica users. All participants experienced counterproductive
  effects; only 49% rated the reward system as appropriate; punitive
  mechanics (HP loss for missed habits) correlated with motivation
  decline `[verified URL 2026-04-18]`.
- **Lesson.** Any check-in mechanic tied to punishment or HP loss
  backfires within 2 weeks. Our check-in must **never** punish — skip
  is free, a "Weary" status is *not* worse than "Charged" (just
  different modifier path). Already aligned with `emotional-hooks.md`
  "no streak-loss penalty" rule.

### 3.10 SuperBetter (Jane McGonigal)

- **Evidence.** RCT (Roepke et al. 2015, *Games for Health*): 283
  adults with depressive symptoms; 30 days of SuperBetter significantly
  reduced depression symptoms. Meta-analysis (JMIR 2017) found
  SuperBetter had largest effect size among 22 apps for depression.
  South African undergraduate RCT showed remission differences at 3
  and 6 months `[verified URL 2026-04-18]`.
- **Caveat.** SuperBetter's effect is likely carried by its CBT-framed
  content (Quests, Power-ups, Allies) not by the gamification wrapper
  alone. Taking that finding to justify "gamification of mental health
  is fine" is over-reach.
- **What we steal.** The concept that game-framing *can* be net-positive
  for mental state *when* the framing is empowering ("powers", "quests")
  and non-punitive. For us: status names should empower ("Charged") or
  be neutrally caring ("Dormant"), never shaming ("Lazy", "Weak", "Off").

### 3.11 What NOT to ape (summary)

- **PHQ-9 / GAD-7** (full clinical screeners). Legal/medical exposure,
  App Review rejection risk.
- **Calm's mood slider.** Too generic; no actionable output; high risk
  of users tapping middle to dismiss.
- **Corporate "mindfulness leaderboard"** (any app that ranks users by
  meditation minutes). McMindfulness critique hits hardest here; Purser
  2019 specifically calls this out.

---

## 4. Question canon recommendation

### 4.1 Design principles (from §2)

Each question must:
- Reference "right now" or "today" (EMA); not retrospection.
- Be answerable in ≤ 3 seconds (cumulative ≤ 10 s).
- Be pictorial / low-cognitive-load (emotional granularity, accessibility).
- Avoid clinical vocabulary (GDPR Art. 9 tactical + App Review risk).
- Avoid social-desirability bias ("Tell us how great you feel today!").
- Avoid leading wording ("You're ready to crush it, aren't you?").

### 4.2 Q1 — Mood (valence axis + granularity invitation)

**Recommended wording:**
- **UA:** «Як ти себе почуваєш прямо зараз?»
- **EN:** "How are you feeling right now?"

**Scale (Russell-quadrant Pick-A-Mood pictorial, 5 options):**

| Slot | Character expression | Quadrant | Label UA (soft) | Label EN (soft) |
|------|----------------------|----------|------------------|------------------|
| 1 | 😔 / slumped | Low V / Low A | «Виснажений» | "Drained" |
| 2 | 😌 / calm-smile | High V / Low A | «Спокійний» | "At ease" |
| 3 | 😐 / neutral | Center (neutral) | «Рівно» | "Neutral" |
| 4 | 😊 / bright | High V / High A | «Живий» | "Energized" |
| 5 | 😣 / tense-scowl | Low V / High A | «Нап'ятий» | "On edge" |

**Scoring.** Each slot maps to (valence, arousal) coords: 1=(−1,−1),
2=(+1,−1), 3=(0,0), 4=(+1,+1), 5=(−1,+1). These are the **five canonical
Russell points**, not a 1-5 linear Likert. This is important — a linear
Likert collapses the two axes into one; a Pick-A-Mood picker preserves
them.

**Why this wording.**
- "How are you feeling right now?" is Apple Health's State-of-Mind
  wording and How We Feel's wording — familiar language, no innovation
  required.
- Pictorial characters (Pick-A-Mood; Desmet et al. 2016) tested to be
  culturally robust and accessible for low-literacy users
  `[verified URL 2026-04-18]`.
- No value judgment ("good" / "bad") — Russell-quadrant presentation
  implies "all four quadrants are valid states", not a performance
  measure.

**Alt candidates rejected.**
- *Emoji slider 😞→😊 (starter schema)*. Rejected: collapses arousal
  into valence, loses the "anxious vs. weary" distinction, invites
  middle-tap-to-dismiss.
- *"Rate your mood 1-10"*. Rejected: arithmetic task; WHOOP-style
  number-anxiety; not pictorial.
- *"Choose the word that matches your feeling: [list of 20 words]"*.
  Rejected: 10-second budget violation.

### 4.3 Q2 — Energy (explicit arousal, separated from mood)

**Recommended wording:**
- **UA:** «Скільки в тобі енергії зараз?»
- **EN:** "How much energy do you have right now?"

**Scale (5-point battery pictorial):**

| Slot | Icon | Label UA | Label EN | Mapping |
|------|------|----------|----------|---------|
| 1 | 🪫 empty | «Майже нуль» | "Almost empty" | arousal = 1 |
| 2 | 🔋 low | «Нижче норми» | "Low" | arousal = 2 |
| 3 | 🔋 mid | «Робоча» | "Okay" | arousal = 3 |
| 4 | 🔋 high | «Висока» | "High" | arousal = 4 |
| 5 | ⚡ full | «Переповнена» | "Buzzing" | arousal = 5 |

**Why Q2 exists separately from Q1's arousal axis.** Q1 pictorial already
encodes arousal in the mood-quadrant, but users often don't cleanly
communicate "arousal" in a mood picture. Q2 as an explicit battery
disambiguates: a user could pick 😐 (neutral mood) + battery=1 (drained)
or + battery=5 (coiled spring) — different statuses. Oura's "readiness"
literature supports this: subjective energy and subjective mood are only
weakly correlated (`[assumption — based on Stone/Shiffman day-to-day
diary studies that find mood & energy are separable dimensions]`).

**Red-team.** Two arousal-adjacent questions? Is this redundant? No —
Q1 captures *felt state* (valence+arousal gestalt), Q2 captures
*available resource* (can-do-today). The combination distinguishes
"high mood, low energy" (Content / Dormant-happy) from "high mood,
high energy" (Charged). Both are "good" moods; only one is
ready-to-push.

**Alt rejected.** *Single 1-10 energy slider* — less recognizable,
more arithmetic, no icon anchor.

### 4.4 Q3 — Intent (Fogg-style ability × motivation, today)

**Recommended wording:**
- **UA:** «Що для тебе сьогодні?»
- **EN:** "What do you want from today?"

**Scale (3-option enum):**

| Option | Icon | Label UA | Label EN | Fogg map |
|--------|------|----------|----------|----------|
| rest | 🛌 | «Відпочити» | "Rest" | ability=low OR motivation=recovery |
| maintain | 🚶 | «Утримати ритм» | "Keep rhythm" | ability=mid, motivation=consistency |
| push | 🔥 | «Натиснути» | "Push" | ability+motivation=high |

**Why this wording.** Shifts from "what can you do" (ability probe) to
"what do you *want*" (intent) — respects autonomy (SDT), avoids the
shame of "I can't today". Motivational framing is the empowering frame
(SuperBetter-style).

**Alt rejected.**
- *"How hard will you train today?"* — fitness-specific, doesn't
  accommodate rest/meditation as valid.
- *"Would you rather relax or challenge yourself?"* — leading wording
  (implies challenge = better).

### 4.5 Dynamic Q-slot (future, NOT in beta)

The starter vision allows "one question may be dynamic (seasonal rotation)".
**Not in scope for beta.** Reasoning: adds a config-seeding chore, needs
copywriter each rotation, and confounds the retention A/B (new question =
new variable). Defer to post-beta v1.1 — then we can swap Q3 seasonally
(e.g. around Day of Rozkolу: "What does today's event mean to you?").

### 4.6 Social desirability & leading-question tests

| Question | Social-desirability risk | Leading risk | Verdict |
|----------|--------------------------|--------------|---------|
| Q1 (mood pictorial) | Low (all 5 equally valid, pictorial) | Low | Pass |
| Q2 (energy battery) | Low (no "good"/"bad" anchor) | Low | Pass |
| Q3 (rest/maintain/push) | **Moderate** — users may default "push" to feel good about themselves | Low | Mitigation: rest-icon (🛌) must be **equally prominent** and copy surrounding must never frame push as "best". Tooltip: "All three are valid wins today." |

---

## 5. Status taxonomy recommendation

### 5.1 How many statuses?

Starter schema listed 6 (Dormant/Steady/Charged/Weary/Anxious/Content).
We recommend **5** after these adjustments:

- **Drop "Anxious"** → rename & re-semantic to **"Scattered"**. Reason:
  "anxious" is clinical, has connotation with anxiety disorder, and would
  trigger App Review flags + sensitive-data framing. "Scattered" captures
  the same psychological state (tense, arousal-high, valence-low) in
  non-clinical language.
- **Merge "Content" into "Steady"** as a sub-flavor. Reason: they have
  the same in-game effects (baseline, no modifier), and distinguishing
  them adds taxonomy load without gameplay benefit. If we want
  granularity in UI, the badge text can read "Steady · content" for
  that specific combo, but the underlying status enum is one value.

### 5.2 The 5 statuses (with Vector-dialect naming)

Lore integration: in `realms-canon.md` the core antagonist of the world
is the Rupture (Щілина ВЕРА); Vector is the player's enigmatic guide
reading "biological state." Status names should feel like terms Vector
would use — brief, sensory, physical-not-psychological.

| # | Status (EN) | Status (UA) | Vector dialect / badge copy | Quadrant (Russell) |
|---|------------|--------------|-------------------------------|--------------------|
| 1 | **Charged** | «Зарядженний» | "Spark is on. Burn it." | High V / High A |
| 2 | **Steady** | «У ритмі» | "Signal's clean. Hold line." | High V / Low A + neutrals |
| 3 | **Dormant** | «У спокої» | "The core is resting. Don't force it." | High V / Low A (with rest intent) |
| 4 | **Weary** | «Стомлений» | "Battery's low. Walk, don't run." | Low V / Low A |
| 5 | **Scattered** | «Розсіяний» | "Pulse is jagged. One breath at a time." | Low V / High A |

### 5.3 Decision tree (Q1 × Q2 × Q3 → Status)

The mapping is **deterministic** (seeded rules engine) so research
iterations can be tuned without code deploy — the rules table lives in
`game_settings` (see §8).

```
Denote:
  mood = Q1 quadrant from Russell points {LL, HL, N, HH, LH}
         where first char = valence, second char = arousal
         (HL = High-valence/Low-arousal = "at ease")
  energy = Q2 (1..5)
  intent = Q3 ∈ {rest, maintain, push}

Resolved status = by order (first match wins):

  if mood == LH:                                       → Scattered
  if mood == LL and energy ≤ 2:                        → Weary
  if mood == LL and energy ≥ 3:                        → Weary (still)
  if (mood == HL or mood == N) and intent == rest:     → Dormant
  if mood == HH and intent == push and energy ≥ 4:     → Charged
  if mood == HH and (intent != push or energy < 4):    → Steady
  else:                                                → Steady
```

The table below materializes the full 5 × 5 × 3 = 75-combination space.
(Not all are equally likely; we only enumerate anchor rows.)

| Mood (Q1) | Energy (Q2) | Intent (Q3) | → Status |
|-----------|-------------|-------------|----------|
| Drained (LL) | 1 | rest | Weary |
| Drained (LL) | 2 | push | **Weary** (guardrail — warn) |
| Drained (LL) | 4 | push | **Weary** (still — high energy + low mood = risky push) |
| At ease (HL) | 2 | rest | Dormant |
| At ease (HL) | 3 | maintain | Steady |
| Neutral (N) | 3 | maintain | Steady |
| Neutral (N) | 1 | rest | Dormant |
| Energized (HH) | 4 | push | **Charged** |
| Energized (HH) | 5 | push | **Charged** (peak) |
| Energized (HH) | 2 | rest | Steady (not Charged — low energy gates it) |
| On edge (LH) | any | any | **Scattered** (always — regardless of intent) |

### 5.4 Status persistence

- Each check-in's status is valid **for the current calendar day** (until
  user's local midnight).
- If user skips the check-in: **inherit previous day's status** with a
  5% decay toward Steady each consecutive skip (after 7 skips, user is
  reset to Steady as default). This is a low-cost anti-skip-spiral
  mechanism that doesn't punish skipping but also doesn't lock a player
  into a stale "Scattered" forever.
- Once assigned, status is **not re-changeable** that day (no "I don't
  like this answer, let me re-take"). Integrity > flexibility; retakes
  invite gaming the system.

### 5.5 Visual badge (RN app)

- Small **pill component** in top-right of the profile and map screens:
  `[icon] Charged` — icon differs per status, color palette matches
  quadrant (warm for HH, cool for LL, neutral for HL/N, red-edge for LH).
- Badge tap-to-expand shows Vector's one-line dialect copy.
- User can **hide badge** via settings (not full opt-out — status still
  drives modifiers — just visual hide).
- Full **opt-out of the check-in entirely** is also in settings: "Do
  not ask me to check in."

---

## 6. In-game effects — specific formulas

All numbers below are **starting values** for A/B testing. They live in
`game_settings` (see §8) and can be tuned without deploy.

### 6.1 XP modifier per status

| Status | Battle XP multiplier | Sleep XP multiplier | Rest/non-battle XP multiplier |
|--------|----------------------|---------------------|--------------------------------|
| Charged | **× 1.10** | × 1.00 | × 1.00 |
| Steady | × 1.00 | × 1.00 | × 1.00 |
| Dormant | × 1.00 | **× 1.10** | **× 1.20** (meditation/walk XP) |
| Weary | × 0.95 | **× 1.10** | **× 1.15** (rest favored) |
| Scattered | × 1.00 | × 1.00 | **× 1.10** (if user chooses meditation micro-action) |

**Bounding principle.** Range is ±10-20% per activity. This is **smaller
than the existing daily streak bonus (BL §4.216)**, so status
modulates but does not dominate XP. No status is "better" overall —
each status favors different activity types. This is the anti-pay-to-win
and anti-meta-dominance rule: no one can game the system by always
answering "Charged".

### 6.2 Recovery / stamina regen modifier

| Status | Stamina regen rate | Workout cap suggestion |
|--------|---------------------|------------------------|
| Charged | × 1.0 | +10% "push harder" nudge in UI |
| Steady | × 1.0 | Normal |
| Dormant | × 1.1 (faster regen) | -10% nudge — "light today" |
| Weary | × 1.2 | -20% — "rest is progress" copy shown |
| Scattered | × 1.0 | Normal, but breathing micro-action prompt added |

(These interact with existing `XpCalculationService` diminishing-returns
on >60 min sessions, BL §4 + emotional-hooks "Grind-контроль.")

### 6.3 Notification tone variant selection

Each push-notification template has 2 variants: `default` and `tuned`.
Tuned is selected by status per this table:

| Status | Morning push tone | Missed-day push tone |
|--------|-------------------|-----------------------|
| Charged | Energetic / bold — "Today's yours. The Rupture is watching." | "Your spark waits. 24h left." |
| Steady | Neutral — "One session keeps the line. Vector sees you." | "The rhythm's still yours. Come back." |
| Dormant | Gentle / quiet — "Rest IS a form of progress. Vector approves." | (suppressed — no push for 48h) |
| Weary | Soft — "Today is for walking. That counts." | (suppressed — no push for 72h) |
| Scattered | Grounding — "A single breath. That's the mission today." | "One small thing. Any thing." |

**Key rule.** Weary and Dormant **suppress or soften** push notifications;
we do NOT push harder on tired users. This is directly derived from the
Habitica counterproductive-effects finding (Diefenbach & Müssig 2019) —
punishing tired users with more prompts is where gamification goes toxic.

### 6.4 Content / mob-selection tuning

| Status | `MobSelectionService` nudge | Content recommendation |
|--------|------------------------------|-------------------------|
| Charged | +5% probability of harder-tier mob; +1 rare drop chance | Show "Challenge of the day" prompt |
| Steady | Baseline | Baseline daily quest |
| Dormant | -10% probability of Class III+ mobs | Suggest collection / catalog browsing |
| Weary | -15% HP on assigned mobs (softer day) | Recovery-themed micro-quest (walk-only) |
| Scattered | Baseline | Offer one-tap breathing micro-action (30s) that awards trivial XP (10 XP) |

**Integration point.** `MobSelectionService.selectForToday(user)` in
backend reads `user.current_psych_status` and modulates the selection
weights. Config-driven via `game_settings.psych.mob_tuning_*`.

### 6.5 Mob difficulty tuning (damage taken)

We do **NOT** recommend adjusting damage-taken based on status in beta.
Reasoning: fairness expectations. A player who honestly reports "Weary"
shouldn't get a damage-taken penalty (punishes honesty) or a damage-
taken buff (incentivizes lying low). Keep battle math fair; let XP
multiplier carry the status effect. Post-beta, consider cosmetic effects
only (visual mob aura that reflects your status, not mechanics).

### 6.6 Edge cases

| Case | Handling |
|------|----------|
| User skipped check-in today | Inherit yesterday's status with 5% decay toward Steady per consecutive skip |
| User hasn't checked in ≥ 7 days | Reset to Steady (default) |
| User answers are contradictory (Q1=😔 Q2=⚡ Q3=push) | Decision tree already handles: → Weary (low mood + push = flagged guardrail) |
| User with no history (first ever check-in) | Default status before first answer = Steady |
| Tie-breaking (rare; rules are deterministic so ties shouldn't exist) | Decision tree evaluates top-down, first match wins |
| Session spans midnight | Status frozen at moment of battle-start; new day → new prompt |

### 6.7 Longitudinal trends (NOT for beta gameplay, but stored)

Store rolling 7/30/90-day dominant status on `PsychUserProfile`. This
feeds the (post-beta) trend sparkline UI and the therapist-referral
trigger (§7.7). In beta we compute and store it but do not show it —
just collect to validate §9 metrics.

---

## 7. Privacy & ethics audit

### 7.1 GDPR Article 9 classification — the nuanced view

Bincoletto (2022), *Mental data protection and the GDPR* (Oxford JLB)
`[verified URL 2026-04-18]`, is the authoritative academic analysis.
Key findings relevant to us:

- **Mood and emotion self-reports are often NOT special-category data**
  under Art. 9(1) strict reading — Art. 9 covers specific categories
  (racial origin, political opinions, health data, sexual life, etc.).
  "Mood" or "emotion" as such is only Art. 9 if it reveals a *health
  condition*.
- **BUT** the classification is context-dependent. The EDPB 2023
  guidelines clarify: same data point (HR) has different treatment in
  a fitness app vs. a cardiac-monitoring app, based on *processing
  purpose*.
- **AND** even if the raw data isn't Art. 9, the combination
  (mood + intent + game behavior + longitudinal profile) qualifies as
  *high-risk processing* under Art. 35 → **DPIA required** before
  launch.
- **Tactical conclusion.** We **cannot** rely on "just mood data, not
  Art. 9". We treat the data as sensitive-by-default:
  - **Explicit opt-in consent** (separate toggle, clear copy) — per
    Art. 9(2)(a) procedure even if Art. 9 is debatable.
  - **Legitimate interest** is NOT sufficient for this processing.
  - **DPIA performed pre-launch**, reviewed by founder + external
    privacy counsel.
  - **Special-category handling in data layer**: encryption at rest
    (beyond baseline), separate DB column encryption or table isolation,
    access audit log.

### 7.2 Data minimization — what we DO store

Minimal set to make the feature work:
- `psych_check_in.user_id` (FK)
- `psych_check_in.created_at` (timestamp, date-part used for "once per
  day" rule)
- `psych_check_in.q1_valence` (int, derived from Pick-A-Mood slot)
- `psych_check_in.q1_arousal` (int, derived from same)
- `psych_check_in.q2_energy` (int 1-5)
- `psych_check_in.q3_intent` (enum)
- `psych_check_in.assigned_status` (enum — denormalized for query perf)
- `psych_check_in.skipped` (bool)
- `psych_user_profile.user_id` (PK/FK)
- `psych_user_profile.dominant_7d` / `_30d` / `_90d` (enums)
- `psych_user_profile.last_consent_ts` (timestamp)

### 7.3 Data minimization — what we DO NOT store

- No free-text emotion labels. Q1 is slot-based only.
- No IP, geolocation, or device fingerprint attached to check-in.
- No sharing with any 3rd party (analytics, ads). Events to analytics
  (Mixpanel/Amplitude etc.) are **only counts** ("check-in_submitted"),
  never the status value.
- No backend ML inference on the data (for beta). A human reads
  aggregates only, for balance tuning.
- No audio, no heart-rate readings tied to the check-in in beta.

### 7.4 Retention policy

- Individual check-in rows: retained for **180 days** then auto-purged
  (cron job). Aggregate fields on `PsychUserProfile` (dominant_*) are
  already anonymized-by-aggregation, retained until account deletion.
- Account deletion (`DELETE /api/me`): cascades and purges
  `psych_check_in` + `psych_user_profile` within 30 days per GDPR Art.
  17. Implementation via existing account-deletion flow extended with
  PsychProfile BC handler.

### 7.5 Export & delete UX (first-class)

- **GDPR Art. 15 (access):** Settings → Privacy → "Download my psych
  data" → produces JSON of all check-ins + profile. Available at any
  time; no approval needed; throttled to 1 request per 7 days.
- **GDPR Art. 17 (erasure):** Settings → Privacy → "Delete my psych
  data" — separate from full account delete. Deletes check-ins + profile
  + future opt-out (you'd have to re-opt-in to resume). Confirmation
  dialog (2-tap).
- **GDPR Art. 20 (portability):** Same as Art. 15 export; format is
  machine-readable JSON.

### 7.6 Teen / minor handling

US COPPA protects <13; COPPA 2.0 expected to extend to <17 in 2026
`[verified URL 2026-04-18]`. UK-GDPR: 13. EU: 13-16 depending on member
state. Texas App Store Accountability Act (2025) adds age categories
(Child / younger teen / older teen / adult) with parental consent
gates `[verified URL 2026-04-18]`.

**Policy for RPGFit:**
- Age gate on registration (we already have birth date in `User` for
  profession-tier prerequisites). Use it.
- **If `user.age < 16`:** psych profiler is **opt-out by default**
  (disabled; user must actively enable in settings). No push notifications
  reference the profiler for this cohort.
- **If `user.age < 13`:** feature **disabled** regardless of settings.
  Vector-dialect status references are suppressed.
- Parental consent flow: out of scope for beta. `[assumption — beta is
  18+ primarily; age <16 cohort likely < 5%]` — still gate, but full
  parental consent UX can be v1.1.

### 7.7 Crisis-pattern handling — the "Weary/Scattered 14-days" question

The founder's starter brief flags: "if user's pattern is Weary/Scattered
for 14+ consecutive days, soft nudge with therapist resources."

**Rigor check.** This is a genuinely hard problem. Over-triggering (every
tired week shows crisis line) trivializes crisis help; under-triggering
(we never mention it) violates ethical design. Current best practice
from SAMHSA 988 Safety Policy guidance `[verified URL 2026-04-18]`:

- Mental-health apps SHOULD include crisis resources prominently in
  settings, always accessible.
- Apps SHOULD NOT attempt clinical screening (we agreed).
- Apps that detect elevated-risk patterns SHOULD offer, not force,
  resources — language is "would you like to talk to someone?" not "you
  need help."

**RPGFit implementation (beta):**
1. **Always-accessible crisis resources page** (Settings → Help → Crisis
   Support). Lists per-locale: US 988, UK Samaritans 116 123, UA Lifeline
   7333, EU 112 / country lifelines. Surfaced in onboarding as "find help"
   chip.
2. **Trigger rule (beta minimum):** If user's rolling 7-day status is
   Weary OR Scattered on ≥ 5 of 7 days, show a single soft prompt on
   next check-in: "Some days are harder. If you want to talk to someone,
   here are people who listen." → link to crisis resources page. One
   prompt per 30 days; dismissible; no repeat within window.
3. **NO automated escalation** (no email to founder, no clinician
   outreach, no AI chatbot response). This is the most dangerous bug
   class — auto-response in a crisis context without licensed oversight
   is an ethical and legal minefield.
4. **Copy discipline:** Never "you are depressed" / "you need help" /
   "seek professional assistance" (clinical). Do: "talk to someone" /
   "if today is heavy" / "people who listen" (caring, non-diagnostic).

### 7.8 Anti-"McMindfulness" design rules

From Purser 2019 / Psychotherapy.net / Vice `[verified URL 2026-04-18]`,
the specific pitfalls to avoid:

| McMindfulness pitfall | Our guardrail |
|------------------------|---------------|
| Self-surveillance / score anxiety | No numeric score. Named status only. No public comparison. |
| Competitive-reward framing | No leaderboards, no badges for "consistent check-ins", no XP reward for answering |
| Privatizing structural problems ("just meditate") | Status narrative never says "try harder" — it acknowledges the state |
| Co-opted spirituality | Integrate with *lore* (Vector, Rupture), not with mindfulness vocabulary ("mindful minutes", "guided meditation") |
| Shaming missed days | No streak reset. No penalty. Skip is first-class. |

### 7.9 Messaging red lines (hard rules)

- **Never** use words: "depression", "anxiety" (as noun referring to user),
  "diagnosis", "symptoms", "disorder", "therapy", "treatment", "clinical".
- **Never** claim the app "helps with mental health" in App Store copy or
  push. Copy: "help you see your day clearly", "tunes the game to you
  today".
- **Never** show "health score improving" / "your mental health is X".
- **Always** offer "prefer not to say" / skip as equally valid path.

---

## 8. Technical blueprint refinement

This section refines the vision doc (§Technical blueprint) with the
research-grounded changes.

### 8.1 Entity model (backend DDD)

```
src/Domain/PsychProfile/
  Entity/PsychCheckIn.php
    - id: UUID
    - userId: UUID (FK User)
    - createdAt: \DateTimeImmutable
    - localDate: \DateTimeImmutable (user's local calendar day, for dedup)
    - q1Valence: int (-1, 0, +1)  — derived from Pick-A-Mood slot
    - q1Arousal: int (-1, 0, +1)
    - q1Slot: int (1..5) — raw answer, for audit
    - q2Energy: int (1..5) or null if skipped
    - q3Intent: enum UserIntent {REST, MAINTAIN, PUSH} or null
    - skipped: bool
    - assignedStatus: enum PsychStatus (CHARGED, STEADY, DORMANT, WEARY, SCATTERED)
    - engineVersion: string (e.g. "v1.0") — for audit when rules change

  Entity/PsychUserProfile.php
    - userId: UUID (PK/FK)
    - lastCheckInAt: \DateTimeImmutable
    - currentStatus: PsychStatus (denormalized for fast reads)
    - dominant7d: PsychStatus
    - dominant30d: PsychStatus
    - dominant90d: PsychStatus
    - weeklyTrendRaw: json (7-day array of statuses, for trend UI)
    - lastCrisisPromptAt: \DateTimeImmutable | null (30-day cooldown tracker)
    - consentGrantedAt: \DateTimeImmutable | null
    - optedOut: bool (explicit disable)

  Enum/MoodValence.php (ints, discrete)
  Enum/MoodArousal.php
  Enum/UserIntent.php
  Enum/PsychStatus.php
```

### 8.2 Config-driven rules (research-tunable without deploy)

```
game_settings entries (new group: 'psych'):
  psych.status_matrix_json   — JSON mapping (q1, q2_bucket, intent) → status
  psych.xp_multiplier_charged_battle  — float  (starts 1.10)
  psych.xp_multiplier_weary_battle    — float  (starts 0.95)
  psych.xp_multiplier_dormant_rest    — float  (starts 1.20)
  ... (one per matrix cell in §6.1-6.4)
  psych.skip_decay_rate      — float  (5% per skipped day)
  psych.crisis_trigger_window_days — int (7)
  psych.crisis_trigger_threshold   — int (5)  — 5 of 7 days Weary/Scattered
  psych.crisis_prompt_cooldown_days — int (30)
```

All modifiers read via `game_settings` service; Sonata admin can tune
post-launch without redeploy. Pattern matches existing `battle_settings`
and `workout_settings` (BL §12).

### 8.3 Services

```
src/Application/PsychProfile/Service/
  CheckInService.php
    ::isCheckInDue(UserId): bool
    ::submit(UserId, q1Slot, q2, q3): PsychCheckIn
    ::skip(UserId): PsychCheckIn
    ::inheritStatusIfSkipped(UserId, now): PsychStatus

  StatusAssignmentService.php
    ::computeStatus(q1Valence, q1Arousal, q2Energy, q3Intent): PsychStatus
    ::loadMatrixFromSettings(): array (called once per request, memoized)

  ProfileTrendService.php
    ::rollAggregates(UserId, window: int): PsychStatus  (computes dominant)
    ::shouldShowCrisisPrompt(UserId): bool
    ::recordCrisisPromptShown(UserId): void

  XpMultiplierService.php  (or extend existing XpCalculationService)
    ::getBattleXpMultiplier(User, timestamp): float
    ::getSleepXpMultiplier(User, timestamp): float
    — reads PsychUserProfile.currentStatus, returns multiplier from settings

  NotificationToneService.php  (extend existing)
    ::selectVariant(User, templateKey): string
    — returns 'default' or 'tuned-<status>'
```

### 8.4 API endpoints + DTOs

```
GET /api/psych/today
  Response:
    {
      "dueToday": bool,
      "currentStatus": "charged" | "steady" | ...,
      "lastCheckInAt": iso8601 | null,
      "consented": bool
    }
  Used by app _layout.tsx to decide whether to show the modal.

POST /api/psych/check-in
  Body:
    { "q1": 1..5, "q2": 1..5, "q3": "rest"|"maintain"|"push" }
  or skip:
    { "skipped": true }
  Response:
    {
      "assignedStatus": "charged",
      "vectorDialect": "Spark is on. Burn it.",
      "xpMultiplierPreview": 1.10,  // informative for UI
      "badgeConfig": { "icon": "⚡", "colorHex": "#FFAB00" }
    }

GET /api/psych/trend?window=7d|30d|90d
  Response:
    { "dominant": "steady", "history": ["charged","steady",...] }
  (NOT in beta UI, but endpoint available for post-beta.)

POST /api/psych/consent
  Body: { "granted": bool }
  Updates psych_user_profile.consentGrantedAt or sets optedOut.

DELETE /api/psych/data
  GDPR Art. 17 erasure. Deletes all check-ins + profile fields except
  user ID link.

GET /api/psych/export
  GDPR Art. 15 + 20. JSON blob of all user check-ins.
```

### 8.5 Feature-flag strategy

- Feature flag: `feature.psych_profiler.enabled` (server-side, per
  `User.featureFlags` or tenant config).
- Default OFF for beta-1. Manually enabled for beta test cohort
  (founder-selected 50-100 users).
- Beta-2: default ON with explicit consent gate (user sees consent
  modal once).
- Post-beta: default ON opt-in remains first-class.

### 8.6 RN UX (app/(main)/reflect.tsx)

- **When to show:** On first app open of the calendar day (user's local
  tz), AFTER onboarding completion, IF user has consented, IF
  `GET /api/psych/today.dueToday == true`.
- **When to skip:**
  - User not consented → no modal; show onboarding consent chip
    first-daily once, user can accept or dismiss "maybe later" (30-day
    cooldown).
  - User has skipped 3 consecutive days → suppress modal for 7 days;
    show a small "Check in" button in profile instead.
  - User is <13 → never show.
  - User is 13-15 → never show unless opt-in toggle is on.
- **Screen layout (single screen, 3 cards swipe-forward):**
  1. Q1 card: Pick-A-Mood picker (5 characters in quadrant layout).
  2. Q2 card: Energy battery (5 battery icons).
  3. Q3 card: Intent (3 large cards).
  4. Summary card: "Vector reads your state: **Charged** — Spark is on."
     + 1 tap "Let's go."
  - Persistent "Skip today" link bottom of every card.
  - Back button allowed between cards.
- **First-ever consent flow (Day 1 onboarding):**
  - Card: "Would you like Vector to read your daily state?"
  - Benefits bullets: "Your XP tunes to how you actually feel today",
    "Push notifications quiet down when you're tired", "Fully
    optional — skip any day".
  - Privacy bullet: "Your answers never leave your account. Export
    or delete anytime in Settings."
  - 2 buttons: "Yes, read my state" / "Not now".

### 8.7 Integration points with existing services

| Existing | Integration | Change scope |
|----------|-------------|---------------|
| `HealthSyncService` | None — psych is independent of health data | 0 LOC |
| `XpCalculationService` (BL §3, §4) | Inject `XpMultiplierService` — wrap final awarded XP with psych multiplier | ~30 LOC |
| `MobSelectionService` (BL §9) | Read `PsychUserProfile.currentStatus` in selection weighting | ~50 LOC |
| Notification / push service | Variant-selection call in each push template | ~40 LOC |
| `OnboardingQuestionnaire` (BL §1) | Add single consent step (accept / later) | ~20 LOC |
| Sonata admin | New `PsychProfileAdmin` for SUPERADMIN to view aggregated cohort data (never individual PII) | ~100 LOC |

### 8.8 Telemetry / eval signals (consent-gated)

Events (Mixpanel or whatever analytics): `psych_consent_granted`,
`psych_check_in_submitted`, `psych_check_in_skipped`, `psych_status_assigned`
(value: enum, NOT aggregated by user_id externally). **No identifying
payload.** Used for §9 evaluation.

---

## 9. Evaluation plan

### 9.1 North-star metric

**D7 retention lift** in the cohort that consents to the profiler, vs.
the cohort that does not (random 50/50 consent-assignment during
beta-test). Expected delta: +3-7 pp D7 retention `[assumption — based
on Finch retention halo + Daylio longevity data]`.

### 9.2 Secondary metrics

| Metric | Target benchmark | Source of benchmark |
|--------|------------------|----------------------|
| Opt-in rate (consent granted) | ≥ 50% | `[assumption — Finch-like apps are 60-80% opt-in for check-ins; we're more optional so lower bound 50%]` |
| Check-in completion rate (when modal shown) | ≥ 65% | EMA adherence literature (>80% is excellent, 60-80% is normal for consumer apps) |
| Skip rate by question | Q1 < Q2 < Q3 (users skip later questions more) | EMA fatigue pattern |
| Daily return rate on Charged status cohort | +10% vs Steady | Status self-matching `[assumption]` |
| Daily return rate on Weary status cohort | Not worse than Steady | Critical — if Weary users churn, design failed |
| Crisis-resources page views | >0 after trigger | Must be reached if triggered |
| `psych_data_delete` API hits | < 2% of consented users | High deletion = trust signal failure |

### 9.3 Experimental design

Within beta test cohort (say 500 users):
- Random 50% assigned to `psych_profiler=on` (with consent prompt).
- 50% control (no prompt; status always Steady under-the-hood).
- A/B compare D1, D7, D30 retention.
- Also A/B within opt-in cohort: status-driven push-tone vs. default
  push-tone (tests whether tone variation itself drives the lift).

### 9.4 Optional monthly PERMA-5 pulse (not primary)

Once per 30 days, offer an **optional** 5-question PERMA-short-form
(adapted from Butler & Kern PERMA-5 derivations, 2016). This is
self-reported wellbeing, collected for **research and content tuning**,
not surfaced as a status. Opt-in within opt-in. Drives long-term
product-impact story: "our users in month 3 reported +0.4 on the
flourishing scale" — nice for PR but not a gating metric.

### 9.5 Qualitative

- 10-15 user interviews during beta month 2 with opt-in users. Focus:
  Did the status ever feel wrong? Did you game it? Did the notification
  tone change feel like your friend or like surveillance?
- Key phrase to listen for: "it felt like the game knew" (positive) vs.
  "it felt like it was judging me" (negative → redesign copy).

### 9.6 Kill-switches

Ship with a feature flag that can be disabled globally with one Sonata
toggle. If crisis-prompt rate spikes > 10% of DAU in any day, flag
auto-trips (alert to founder; feature paused). If post-deletion rate
exceeds 5% in any week, investigate.

---

## 10. Scope recommendation

### 10.1 Option A — REJECT

Do not ship psych profiler. Not even as post-beta.

- **Pros:** Zero reputational / privacy / scope risk. Team focuses on
  Day of Rozkolу + existing IN-scope (02-beta-scope).
- **Cons:** Lose the differentiator. Lose a D7 retention lever.
  Competitors without this have a generic daily loop; we'd match, not
  exceed.
- **When to pick:** only if 31.10 is already at risk *and* the Psychologist
  Researcher concludes the concept is more risk than value. Not our
  conclusion.

### 10.2 Option B — MINIMAL IN-BETA (recommended)

Ship: 3-question check-in (Q1 Pick-A-Mood, Q2 battery, Q3 intent) +
5-status taxonomy + XP multiplier + one push-tone variant. Behind
feature flag, opt-in. Crisis-resources page always available. GDPR
export/delete flows.

**Not in beta:**
- Trend sparkline UI (data collected, not shown).
- Mob difficulty tuning beyond selection bias.
- Content recommendation engine (beyond single `content.focus` hint).
- Seasonal dynamic Q-slot.
- PERMA-5 monthly pulse (defer to v1.1).
- Partner/friend-status matching (defer).
- Sonata cohort dashboard (defer; logs file is enough for beta).

**Dev weeks:**
- Backend: 5-7 days (entities, service, rules engine, 4 endpoints,
  migration, settings keys, tests).
- RN: 3-5 days (reflect.tsx 4-card flow, consent modal, settings
  toggle, badge pill, push-tone wiring).
- Privacy / DPIA: 2-3 days (write DPIA doc, legal review, privacy
  policy amendment).
- QA + balance: 2-3 days.
- **Total: ~2.5-3 dev-weeks.**

**Why this wins:**
- Delivers founder's core intuition (daily ritual → status → tuned
  experience).
- Respects every red line in emotional-hooks + starter vision.
- Technically independent of HealthKit/Health Connect coverage gaps
  (the 08 research's main blocker for mental-stats).
- A/B-able in beta within the existing test cohort.
- Leaves room to upgrade to Option C post-beta based on real data.

### 10.3 Option C — FULL POST-BETA

Same as B plus: trend UI, seasonal dynamic Q, mob difficulty tuning,
cohort dashboards, partner-matching, PERMA-5 pulse. 6-8 dev-weeks
post-beta (v1.1 or v1.2).

- **When:** after beta shows D7 lift ≥ +3pp on opt-in cohort *and* delete
  rate <2% *and* no App Review flags.
- **If beta data doesn't support** → revert to B (keep as shipped) or
  deprecate the feature cleanly.

### 10.4 Recommendation

**Option B**, with an **explicit pre-registered Option C roadmap slot**
conditional on beta results. 3 dev-weeks. Scheduled after current Phase
1-8 cycle ships (per starter vision).

### 10.5 Counter-argument / red-team

The honest counter-argument to even Option B:
- **"3 dev-weeks that could go to polishing core mobs/battles."**
  Valid — our 02-beta-scope already has 15 IN items. Adding a 16th
  has real opportunity cost.
- **"Another modal on first daily open" — UX friction.** Our 02-scope
  already has a welcome-back flow, streak notification, push-noti. The
  profiler modal is one more gate before the player plays.
- **"Privacy surface area grows."** Even if Art. 9 is arguable, a DPIA
  is real work, and a privacy incident risk now includes psych data.

**Why we still choose B.** The D7 retention problem is the single
biggest risk to 31.10 success (01-market + 02-scope both highlight it).
A daily ritual with a named state is the highest-leverage intervention
we could add at 3 dev-weeks cost. Competitive fitness-RPG apps ship
nothing like this — it is a legitimate moat. The UX friction is
mitigated by skip being first-class. Privacy risk is manageable with
the DPIA + data-minimization in §7.

---

## 11. Open questions for founder

Decisions that need founder input before the Implementation Architect
agent takes this spec forward:

1. **Crisis trigger: 5-of-7 days or 10-of-14?** Current rec is 5/7.
   More conservative (10/14) = fewer triggers, stronger signal; less
   conservative = more caring but more trivial. Your call.
2. **Does the check-in run on web admin / landing page too, or RN
   only?** Rec: RN only. Lean surface.
3. **Consent copy: founder voice or marketing voice?** We have a
   founder-first Vector voice in the lore. The consent modal would
   feel warmer in that voice. Want Vector saying "Let me read you",
   or a neutral "RPGFit would like to learn how you feel today"?
4. **PERMA-5 monthly pulse: in or out of v1.0?** Currently we recommend
   out (v1.1). If you want it for research / PR, +2 dev-days.
5. **A/B randomization seed: auto by userId hash, or opt-in-only?**
   Opt-in-only = no control cohort; auto = we get clean A/B but
   50% of consenting users have their status ignored (could feel
   wrong if they answer and nothing changes). Trade-off: rigor vs.
   user experience.
6. **Admin visibility: what cohort-level view does founder want in
   Sonata for beta?** Rec: status-distribution histogram, check-in
   rate over time, skip-funnel. NO individual PII.
7. **Crisis resources page content: who owns the copy + links?**
   Rec: founder + someone with local mental-health resource knowledge
   per UA / EN. Avoid auto-translate; use canonical 988 / Samaritans /
   Telefon Dovira wording.
8. **Opt-out language: "I don't want to share my mood" or "I don't
   want Vector to read me"?** Narrative-native vs. cold-mechanical.
   Rec: offer both side by side in Settings copy, pick the lore
   version as primary.
9. **Status badge on public profile: show or hide by default?** Rec:
   hide (opt-in to public). Some users might want to brag about being
   Charged 10 days in a row; most won't. Default-private is safer.
10. **Post-beta Option C decision gate: what metric thresholds close
    the loop?** Rec above: D7 lift ≥ +3pp & delete <2% & no App Review
    flag. Sign off these thresholds now so the post-beta go/no-go is
    unambiguous.

---

## 12. Sources

### Peer-reviewed psychology (Tier 1)

- Russell, J.A. (1980). A circumplex model of affect. *J. Personality
  and Social Psychology*, 39, 1161-1178.
  <http://pdodds.w3.uvm.edu/research/papers/others/1980/russell1980a.pdf>
- Posner, J., Russell, J.A., & Peterson, B.S. (2005). The circumplex
  model of affect: An integrative approach. *Development and Psychopathology*, 17.
  <https://pmc.ncbi.nlm.nih.gov/articles/PMC2367156/>
- Ryan, R.M., Rigby, C.S. & Przybylski, A. (2006). The Motivational Pull
  of Video Games: A SDT Approach. *Motivation and Emotion*, 30, 347-363.
  <https://selfdeterminationtheory.org/SDT/documents/2006_RyanRigbyPrzybylski_MandE.pdf>
- Butler, J. & Kern, M.L. (2016). The PERMA-Profiler: A brief multidim.
  measure of flourishing. *Int. J. Wellbeing*, 6(3).
  <https://www.internationaljournalofwellbeing.org/index.php/ijow/article/view/526>
- Kashdan, T.B., Barrett, L.F., & McKnight, P.E. (2015). Unpacking
  Emotion Differentiation. *Current Directions in Psych Science*, 24.
  <https://journals.sagepub.com/doi/abs/10.1177/0963721414550708>
- Shiffman, S., Stone, A.A., & Hufford, M.R. (2008). Ecological Momentary
  Assessment. *Annual Review of Clinical Psychology*, 4.
  <https://pubmed.ncbi.nlm.nih.gov/18509902/>
- Lieberman, M.D. et al. (2007). Putting feelings into words: Affect
  labeling disrupts amygdala activity. *Psychological Science*, 18.
  <https://journals.sagepub.com/doi/10.1111/j.1467-9280.2007.01916.x>
- Torre, J.B. & Lieberman, M.D. (2018). Putting Feelings Into Words:
  Affect Labeling as Implicit Emotion Regulation. *Emotion Review*.
  <https://journals.sagepub.com/doi/full/10.1177/1754073917742706>
- Desmet, P.M.A., Vastenburg, M.H., & Romero, N. (2016). Mood
  measurement with Pick-A-Mood: Review of current methods and design
  of a pictorial self-report scale.
  <https://diopd.org/wp-content/uploads/2016/10/JDR140303-DESMET.pdf>
- PERMA-Profiler validation (Mexican sample).
  <https://link.springer.com/article/10.1007/s11482-022-10132-1>
- Russell's circumplex model (textbook overview).
  <https://psu.pb.unizin.org/psych425/chapter/circumplex-models/>

### Behavior-change / gamification (Tier 2)

- Molan et al. (2025). Scoping review of Fogg Behavior Model in health
  interventions. *BMC Public Health*.
  <https://link.springer.com/article/10.1186/s12889-025-24525-y>
- Fogg Behavior Model official page. <https://www.behaviormodel.org/>
- Diefenbach, S. & Müssig, A. (2019). Counterproductive effects of
  gamification. *Int. J. Human-Computer Studies*, 127.
  <https://www.sciencedirect.com/science/article/abs/pii/S1071581918305135>
- Roepke, A.M. et al. (2015). RCT of SuperBetter, a Smartphone-Based
  Self-Help Tool to Reduce Depressive Symptoms. *Games for Health*.
  <https://pubmed.ncbi.nlm.nih.gov/26182069/>
- SuperBetter Backed by Science page.
  <http://superbetter.com/the-science/>
- PHQ-4 validation — Kroenke, K. et al. (2009). An ultra-brief screening
  scale. *Psychosomatics*, 50.
  <https://pubmed.ncbi.nlm.nih.gov/19996233/>

### Wearable / commercial products

- WHOOP Recovery — Official.
  <https://www.whoop.com/us/en/thelocker/how-does-whoop-recovery-work-101/>
- Oura Readiness — Official.
  <https://ouraring.com/blog/readiness-score/>
- Wearable recovery-score critique — DeGruyter Brill (2025).
  <https://www.degruyterbrill.com/document/doi/10.1515/teb-2025-0001/html>
- "The weird science of recovery" — critique of composite scores.
  <https://www.twopct.com/p/the-weird-science-of-recovery>
- Whoop vs Oura — Sportsmith data comparison.
  <https://www.sportsmith.co/articles/whoop-vs-oura-ring-real-life-data-analysis-and-comparisons/>
- WellnessPulse — "Wearable Recovery Scores Explained".
  <https://wellnesspulse.com/healthtech/wearable-recovery-scores-explained/>
- "Should you trust your wearable?" — Substack analysis.
  <https://rachelepojednic.substack.com/p/should-you-trust-your-wearable-what>

### Self-care / mood-tracker apps

- Finch — App Store.
  <https://apps.apple.com/us/app/finch-self-care-pet/id1528595748>
- Finch — Google Play.
  <https://play.google.com/store/apps/details?id=com.finch.finch&hl=en_US>
- Daylio — Wikipedia / download stats.
  <https://en.wikipedia.org/wiki/Daylio>
- Daylio Similarweb stats.
  <https://www.similarweb.com/app/google-play/net.daylio/statistics/>
- How We Feel — Yale School of Medicine.
  <https://medicine.yale.edu/news-article/the-how-we-feel-app-helping-emotions-work-for-us-not-against-us/>
- How We Feel — App Store.
  <https://apps.apple.com/us/app/how-we-feel/id1562706384>
- Hopelab — Mood Meter overview.
  <https://hopelab.org/stories/mood-meter>

### GDPR / legal / privacy

- Bincoletto, G. (2022). Mental data protection and the GDPR. *Oxford
  Journal of Law and the Biosciences*.
  <https://academic.oup.com/jlb/article/9/1/lsac006/6564354>
- GDPR Art. 9 — official text.
  <https://gdpr-info.eu/art-9-gdpr/>
- Sprinto guide to GDPR Art. 9.
  <https://sprinto.com/blog/gdpr-article-9/>
- Mental Health App Data Privacy (HIPAA-GDPR hybrid).
  <https://secureprivacy.ai/blog/mental-health-app-data-privacy-hipaa-gdpr-compliance>
- European Commission — When is a DPIA required?
  <https://commission.europa.eu/law/law-topic/data-protection/rules-business-and-organisations/obligations/when-data-protection-impact-assessment-dpia-required_en>
- ICO UK — When do we need a DPIA?
  <https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/accountability-and-governance/data-protection-impact-assessments-dpias/when-do-we-need-to-do-a-dpia/>

### Minors / COPPA / age verification

- Mayer Brown — Children's Privacy Legislation Tracker 2026.
  <https://www.mayerbrown.com/en/insights/publications/2026/01/little-users-big-rules-tracking-childrens-privacy-legislation>
- FTC COPPA FAQ.
  <https://www.ftc.gov/business-guidance/resources/complying-coppa-frequently-asked-questions>
- App Store Age Verification Laws — Privacy World.
  <https://www.privacyworld.blog/2025/10/app-store-age-verification-laws-your-questions-answered/>

### Crisis / safety

- 988 Suicide & Crisis Lifeline Safety Policy (SAMHSA).
  <https://988lifeline.org/wp-content/uploads/2023/02/FINAL_988_Suicide_and_Crisis_Lifeline_Suicide_Safety_Policy_-3.pdf>
- SAMHSA 988 FAQ.
  <https://www.samhsa.gov/mental-health/988/faqs>
- SAMHSA — Recommended Standard Care for People with Suicide Risk.
  <https://theactionalliance.org/sites/default/files/action_alliance_recommended_standard_care_final.pdf>

### McMindfulness / critique

- Purser, R. — Vice interview ("Mindfulness is a Capitalist Scam").
  <https://www.vice.com/en/article/mindfulness-is-the-capitalist-spirituality-ronald-purser-interview/>
- LARB — A Panopticon for the Mind: Purser interview.
  <https://lareviewofbooks.org/blog/interviews/panopticon-mind-talking-ronald-purser-mindfulness-today/>
- New Humanist — Purser on neoliberal stress.
  <https://newhumanist.org.uk/articles/5503/privatising-the-causes-of-stress-dovetails-nicely-with-neoliberal-ideology>
- Salon — Do mindfulness apps do any good?
  <https://www.salon.com/2019/05/28/can-apps-really-make-us-feel-better/>

### CRM / lead-status conceptual

- HubSpot Knowledge Base — Lifecycle Stages.
  <https://knowledge.hubspot.com/records/use-lifecycle-stages>
- Default — HubSpot Lead Status & Lifecycle 2026.
  <https://www.default.com/post/hubspot-lead-status-lifecycle-stages>

### Emoji / pictorial scales

- Emoji Current Mood and Experience Scale (validation).
  <https://www.tandfonline.com/doi/full/10.1080/09638237.2022.2069694>
- PMC — Development & Validation of Emoji Response Scales (PRO).
  <https://pmc.ncbi.nlm.nih.gov/articles/PMC12092062/>

### Internal RPGFit artifacts grounding this report

- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/psychology-profiler.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/08-mental-stats-research.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/product-decisions-2026-04-18.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/emotional-hooks.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/lore-extracts/realms-canon.md`
- `/Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-beckend/docs/BUSINESS_LOGIC.md`

---

## 13. Changelog

- **2026-04-18 v1.** Deep research pass. 40+ URLs verified in this
  session. Grounded in `08-mental-stats-research.md` (prior research),
  `psychology-profiler.md` (founder intent), `product-decisions-2026-04-18.md`
  (D1-D5 constraints), `emotional-hooks.md` (red lines),
  `realms-canon.md` (Vector/Rupture lore), `BUSINESS_LOGIC.md` (existing
  service surface area). Assumption markers applied where web
  verification was not conclusive. Recommendation: **Option B —
  MINIMAL IN-BETA** (~3 dev-weeks), pre-registered Option C post-beta
  expansion gated on beta D7 retention data.
