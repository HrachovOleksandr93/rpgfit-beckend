# Psych Profiler v2 — Implementation Spec

> **Status:** ✅ APPROVED (founder 2026-04-19). SIMPLIFIED scope.
> **Source research:** `BA/outputs/11-psych-profiler-v2-research.md`
> **Source vision:** `docs/vision/psych-profiler-v2-workout-adaptive.md`
> **Feature flag:** `feature.psych_profiler.v2.enabled` (default OFF; dev/test ON).
> **Target:** ship after beta-1 stabilises (~2-2.5 dev-weeks scope).

---

## 0. What this spec explicitly DROPS from v1 research

- ❌ **Catharsis / "побити себе" sub-flow** — no "beat yourself" intent option.
- ❌ **Three-strike rail, 4-strike auto-hide** — unnecessary without catharsis.
- ❌ **Exercise-addiction EAI monitoring** — not in scope.
- ❌ **Bad-mood intent picker (MOOD_LIFT / CATHARSIS / AUTOROUTINE)** — removed.

Reason: founder redirect 2026-04-19 — "just assess state, adapt, warn."

---

## 1. Scope (what ships)

### 1.1 Completion XP bonus

- **+10% XP** bonus applied to the next health-sync XP event after a FULL check-in answer (not skip).
- Full answer = Q1 + Q2 + Q3 + Q4 all answered (or Q1-Q3 if no workout that day).
- One-shot per day (local-TZ).
- Idempotent: even if user retakes or re-syncs, bonus applies exactly once per date.
- Stacks multiplicatively AFTER the status multiplier (CHARGED 1.15 × bonus 1.10 = 1.265 effective for that sync).
- Weekly cap: bonus only counts on max 5 days per rolling 7-day window (anti-overjustification, per research §2).
- Copy (Vector-dialect): «БРП калібровано. Vector підстроїв сьогоднішнє.» — NEVER "reward for answering".

### 1.2 Q4 — post-workout physical state

- 5-anchor text-only session-RPE scale.
- Triggered **only** after a completed workout. Merged into daily check-in UI if both happen within 2h window.
- Wording UA+EN per research §3.3:
  | Value | UA | EN |
  |-------|----|----|
  | 1 | «Легко — міг би ще» | "Easy — could keep going" |
  | 2 | «Зручно» | "Comfortable" |
  | 3 | «Нормально» | "Moderate" |
  | 4 | «Важко» | "Hard" |
  | 5 | «Ледве закінчив» | "Barely finished" |
- Q4 does NOT change `PsychStatus`. It feeds into `PsychWorkoutAdapterService`.

### 1.3 Workout-difficulty adaptation

New service `PsychWorkoutAdapterService` produces `{intensityDelta, volumeDelta, durationDelta, warningCopy?}`.

**Asymmetric rule:**
- Can LOWER load based on Weary / Dormant / Scattered status or high Q4.
- Can RAISE load ONLY when: `status = CHARGED && last Q4 ≤ 2 && no Weary/Scattered in last 3 days`.
- Otherwise baseline or reduction.

Matrix (deltas from baseline):

| Status | Q4 bucket | intensity | volume | duration | focus | warning |
|--------|-----------|-----------|--------|----------|-------|---------|
| CHARGED | 1-2 (fresh) | +10% | +10% | baseline | new-challenge | — |
| CHARGED | 3-5 | baseline | baseline | baseline | new-challenge | — |
| STEADY | any | baseline | baseline | baseline | baseline | — |
| DORMANT | any | −20% | −15% | −20% | mobility / walk | «Ти відпочиваєш. Vector рекомендує легке.» |
| WEARY | 1-3 | −25% | −25% | −20% | recovery | «Ми фіксуємо що ти стомлений. Бережи себе.» |
| WEARY | 4-5 | −35% | −35% | −30% | recovery-only | «Сильна втома. Рекомендуємо лише легке розслаблення.» |
| SCATTERED | any | −15% | −15% | −10% | focus / breath | «Сигнал рваний. Зосередься на дихальних вправах.» |

### 1.4 Warning copy at plan-start (asymmetric user choice)

When `warningCopy !== null`, before the user starts a workout, show modal:

```
Vector рекомендує полегшене тренування
«Ми фіксуємо що ти стомлений. Бережи себе.»

[Застосувати рекомендоване]  (primary, copper)
[Продовжити звичайне]        (outlined, secondary — shows toast «Слухай тіло»)
```

- Primary CTA generates adapted plan (volume/intensity deltas from matrix).
- Secondary CTA lets user proceed with original plan (respects autonomy).

### 1.5 Consecutive WEARY streak → deload suggestion

`CrisisDetectionService.getWearyStreakDays(User): int` — counts consecutive days where status was WEARY or SCATTERED.

- **Days 5-6** → home screen card with copy «Ти вже 5 днів відчуваєш себе стомлено. Поміркуй про день відпочинку.» (dismissable).
- **Day 7+** → pre-generated deload-week plan (−40% volume, −30% intensity for 7 days). Modal prompts:
  - [Застосувати deload-тиждень]
  - [Продовжити як звичайно]
- Never force. Copy in Vector-dialect.

---

## 2. Backend architecture

### 2.1 New entities / enums

- `src/Domain/PsychProfile/Entity/PhysicalStateAnswer.php`:
  - `id` uuid, `user_id` FK, `workout_session_id` nullable FK (link to `workout_sessions` when triggered post-workout), `rpeScore` int 1-5, `createdAt` datetime.
  - Indexes: `(user_id, created_at)`.
- Extend `PsychCheckIn` — add `physicalStateAnswerId` nullable FK (link when merged with daily check-in).

### 2.2 New services

- `src/Application/PsychProfile/Service/PhysicalStateService.php`
  - `record(User, ?WorkoutSession, int $rpe): PhysicalStateAnswer`.
  - `getLatestInWindow(User, int $hours = 2): ?PhysicalStateAnswer` — used by CheckInService to merge.
- `src/Application/PsychProfile/Service/PsychWorkoutAdapterService.php`
  - `adapt(User, WorkoutPlan $baseline): WorkoutPlanAdaptation` — returns `{intensityDelta, volumeDelta, durationDelta, focus, warningCopy?}`.
  - Reads `psych.adapter_matrix` from game_settings (JSON of the §1.3 table).
  - Fallback to hardcoded matrix if setting missing.
  - Applies asymmetric predicate for load-raise.
- `src/Application/PsychProfile/Service/CompletionBonusService.php`
  - `applyIfEligible(User, DateTimeImmutable $date, int $baseXp): int`
  - Returns xpAfterBonus. Writes idempotent marker row in `game_settings` scoped per-user-per-date.
  - Respects weekly 5/7 cap.

### 2.3 Modified services

- `XpAwardService::awardXpFromHealthSync` — after status multiplier, call `CompletionBonusService::applyIfEligible()` before logging. `ExperienceLog.description` shows `(psych ×1.15 ×1.10 bonus)` when both fire.
- `CheckInService` — accepts optional Q4 (rpeScore). If Q4 provided AND mood/energy/intent NOT skipped → marks check-in as "full", triggers `CompletionBonusService` marker (sets `psych_bonus_applied_{userId}_{Ymd}` game_setting for next sync).
- `WorkoutPlanGeneratorService` — on plan generation, call `PsychWorkoutAdapterService::adapt()` and apply deltas + attach `warningCopy` to response DTO.
- `CrisisDetectionService` — add `getWearyStreakDays(User): int` method.

### 2.4 New endpoints (all feature-flagged + auth required)

Added to existing `PsychProfileController`:
- `POST /api/psych/physical-state` — body `{rpeScore: 1-5, workoutSessionId?: uuid}`.
- `GET /api/psych/deload-suggestion` — returns `{showCard: bool, streakDays: int, recommendedPlan?: WorkoutPlanDTO}`.

Extended `POST /api/psych/check-in` body to accept optional `rpeScore`.

### 2.5 Game settings seeded

- `psych.completion_bonus_pct` = `10`
- `psych.completion_bonus_weekly_cap` = `5`
- `psych.adapter_matrix` = full §1.3 JSON
- `psych.deload_card_start_day` = `5`
- `psych.deload_plan_start_day` = `7`
- `psych.deload_volume_reduction_pct` = `40`
- `psych.deload_intensity_reduction_pct` = `30`

### 2.6 Migration

`migrations/Version20260419200000_AddPsychV2.php`:
- Create `physical_state_answers` table.
- Add nullable `physical_state_answer_id` to `psych_check_ins`.
- Seed all `psych.*` v2 settings.
- Widen `psych_check_ins` if needed for new merged flow.

### 2.7 Tests

- `tests/Unit/Application/PsychProfile/PhysicalStateServiceTest.php`
- `tests/Unit/Application/PsychProfile/PsychWorkoutAdapterServiceTest.php` — all 7 matrix rows + asymmetric raise predicate + fallback-when-no-q4.
- `tests/Unit/Application/PsychProfile/CompletionBonusServiceTest.php` — happy path, weekly cap, idempotent marker, skip → no bonus.
- `tests/Unit/Application/PsychProfile/CrisisDetectionServiceTest.php` — extend with `getWearyStreakDays` cases.
- `tests/Functional/PsychProfileControllerTest.php` — extend with Q4 submit, deload-suggestion endpoint, bonus applied check.

### 2.8 Test harness additions

Extend `/api/test/psych/seed` (from Phase 6) to accept optional `rpe: 1-5` and `completion_bonus_applied: bool` for scripted scenarios.

---

## 3. App architecture

### 3.1 New components

- `src/features/psych/components/PhysicalStatePicker.tsx` — 5-row text-only picker (session-RPE).
- `src/features/psych/components/AdapterWarningModal.tsx` — warning copy + 2 CTAs.
- `src/features/psych/components/DeloadSuggestionCard.tsx` — dismissable card on home.
- `src/features/psych/components/DeloadPlanModal.tsx` — day 7+ prompt with pre-gen plan.

### 3.2 Modified screens

- `app/(main)/reflect.tsx` — ReflectFlow extended: step 4 (Q4) appears if user has completed a workout today OR check-in is triggered post-workout.
- `app/(main)/workouts/[id].tsx` (or wherever plan-start lives) — before starting a workout, check `warningCopy` from adapter; if present, show `AdapterWarningModal`.
- `app/(main)/index.tsx` or home screen — mount `DeloadSuggestionCard` if `GET /api/psych/deload-suggestion` returns `showCard=true`.

### 3.3 New hooks

- `usePhysicalStateMutation` — POST /api/psych/physical-state.
- `useDeloadSuggestion` — GET, staleTime 1h.
- `useAdapterWarning` — reads from workout-plan response.

### 3.4 Explicitly NOT built

- No catharsis picker.
- No 3-strike logic.
- No exercise-addiction UI.

### 3.5 Tests

- `src/features/psych/__tests__/PhysicalStatePicker.test.tsx` — 5 rows, text-only (no emoji).
- `src/features/psych/__tests__/AdapterWarningModal.test.tsx` — renders when copy provided, 2 CTAs work.
- `src/features/psych/__tests__/DeloadSuggestionCard.test.tsx` — dismissable, appears for streak ≥5.
- `src/features/psych/__tests__/labels.test.ts` — regression: assert no emoji in any new label constant.

### 3.6 Debug drawer additions

`src/features/debug/components/tabs/PsychTab.tsx` — extend with:
- «Seed RPE=X for today» action.
- «Seed 7-day WEARY streak» action (triggers deload suggestion).

---

## 4. Implementation phases

Same feature branch `feature/psych-profiler-v2` on both repos. Sequence:

1. Backend entities + migration + service stubs.
2. Backend services fully implemented + tests.
3. Backend controllers + test harness extensions.
4. App types + API + hooks.
5. App components (Q4 picker + warning modal + deload card).
6. App screens integration (ReflectFlow + workout-start + home card).
7. Tests (full regression).
8. Merge to main.

---

## 5. Out of scope (deferred)

- Seasonal question rotation.
- Admin cohort dashboard.
- Trend UI (7/30/90 day charts — still backend-only in v2).
- Notification tone variant per status (only warnings visible).
- Per-set RPE (only session-RPE).

---

## 6. Red-team (from research §9, still applies)

1. **Overjustification boomerang** — mitigated by Vector-calibration copy + weekly cap.
2. **Detraining + CHARGED injury risk** — mitigated by asymmetric raise predicate (needs no Weary/Scattered in last 3 days).
3. **Merged check-in confusion** — mitigated by single UI flow: Q4 appears as step 4 within ReflectFlow when a workout was completed within last 2h.
4. **Feature-flag off** — whole v2 returns v1 behaviour. Controllers return `rpeScore: null`, adapter returns `{0, 0, 0, null, null}`, bonus service returns input unchanged.

---

## 7. Privacy

- Q4 (RPE) is not GDPR Art. 9 special-category — physical perceived exertion, aggregatable without stigma.
- Retention stays 180 days (shared with v1).
- Export + delete endpoints include physical_state_answers.
