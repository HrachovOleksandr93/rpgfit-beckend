# Psych Profiler — Beta Implementation Spec (2026-04-18)

> **Status:** ✅ APPROVED — ship as beta per founder direction.
> **Mode:** Option B (MINIMAL IN-BETA) from `BA/outputs/10-psychology-research.md §10`.
> **Visual constraint (founder):** **text labels only — NO emojis anywhere.**
> **Tone:** Vector-dialect for status copy.
> **Feature flag:** `feature.psych_profiler.enabled` default `false`, enable in dev/test.

---

## 1. Decisions (locks the 10 open questions from research §11)

1. **Crisis trigger:** 5 of 7 rolling days Weary/Scattered — **log only** in beta (no auto-user nudge). Admin sees it in audit logs. Revisit post-beta.
2. **Tone:** Vector-dialect status copy (e.g., "Signal's clean. Hold line."). Matches lore-canon.
3. **A/B control cohort:** NO — keep the feature behind the flag; users who toggle ON get full experience, OFF stays current baseline.
4. **Age gate:** all users 13+ (match existing gate). No minor-specific block in beta.
5. **Therapist links:** include UA (7333 Lifeline) + US (988) links in Settings under "Mental health resources", visible only when feature is enabled.
6. **Q-rotation:** NO — single fixed 3-question set in beta.
7. **Status persistence:** **24h, until user's local midnight.** Timezone resolved from user's profile TZ.
8. **Skip behavior:** inherit previous day's status with 5% decay toward Steady per consecutive skip; after 7 skips → reset to Steady.
9. **Public profile:** status is **private by default**, no leaderboards, no friend-visible feed.
10. **Post-beta Option C:** gate on retention-lift evidence (D7 +5% or D30 +3% attributable to feature). Trend UI, seasonal Q-rotation, admin dashboards deferred.

---

## 2. Text-only question copy (no emojis)

### Q1 — Mood

**Prompt (UA):** «Як ти себе почуваєш прямо зараз?»
**Prompt (EN):** "How are you feeling right now?"

**5 options (radio list, no icons):**

| Value | UA | EN | Russell quadrant |
|-------|----|----|------------------|
| DRAINED | «Виснажений» | "Drained" | LL |
| AT_EASE | «Спокійний» | "At ease" | HL |
| NEUTRAL | «Рівно» | "Neutral" | center |
| ENERGIZED | «Живий» | "Energized" | HH |
| ON_EDGE | «Нап'ятий» | "On edge" | LH |

### Q2 — Energy

**Prompt (UA):** «Скільки в тобі енергії зараз?»
**Prompt (EN):** "How much energy do you have right now?"

**5 options (1-5 scale, text labels):**

| Value | UA | EN |
|-------|----|----|
| 1 | «Майже нуль» | "Almost empty" |
| 2 | «Нижче норми» | "Low" |
| 3 | «Робоча» | "Okay" |
| 4 | «Висока» | "High" |
| 5 | «Переповнена» | "Buzzing" |

### Q3 — Intent

**Prompt (UA):** «Що для тебе сьогодні?»
**Prompt (EN):** "What do you want from today?"

**3 options:**

| Value | UA | EN |
|-------|----|----|
| REST | «Відпочити» | "Rest" |
| MAINTAIN | «Утримати ритм» | "Keep rhythm" |
| PUSH | «Натиснути» | "Push" |

**Required footer copy under Q3:** «Всі три — валідні перемоги сьогодні.» / "All three are valid wins today."

---

## 3. Status assignment rules (deterministic, config-driven)

Rules live in `game_settings` key `psych.status_rules` (JSON array) so research iterations tune without redeploy.

**Order — first match wins:**

```
1. mood == ON_EDGE                                    → SCATTERED
2. mood == DRAINED                                    → WEARY   (any energy, any intent)
3. (mood == AT_EASE || mood == NEUTRAL) && intent==REST → DORMANT
4. mood == ENERGIZED && intent==PUSH && energy >= 4   → CHARGED
5. *                                                  → STEADY   (default)
```

### 5 statuses + Vector-dialect badge copy

| Status | UA label | Badge copy (Vector dialect, EN) | Badge copy UA |
|--------|---------|---------------------------------|---------------|
| CHARGED | «Зарядженний» | "Spark is on. Burn it." | «Іскра є. Спали її.» |
| STEADY | «У ритмі» | "Signal's clean. Hold line." | «Сигнал чистий. Тримай лінію.» |
| DORMANT | «У спокої» | "The core is resting. Don't force it." | «Ядро спить. Не тисни.» |
| WEARY | «Стомлений» | "Battery's low. Walk, don't run." | «Батарея на нулі. Іди, не біжи.» |
| SCATTERED | «Розсіяний» | "Pulse is jagged. One breath at a time." | «Пульс рваний. Один подих за раз.» |

---

## 4. In-game effects (conservative, ±15% cap)

| Status | XP multiplier | Per-activity rule |
|--------|---------------|-------------------|
| CHARGED | ×1.15 | Only for "new challenge" activity (first-time mob / first-time portal). Baseline elsewhere. |
| STEADY | ×1.00 | No modifier — baseline. |
| DORMANT | ×1.20 | Only for rest-category activities (stretch, walk under 30 min, meditation). Baseline on workouts. |
| WEARY | ×1.00 | No XP penalty. Notification tone softer ("recovery is progress"). |
| SCATTERED | ×1.00 | No XP penalty. Show meditation/breathing micro-action on home screen. |

**Red lines:**
- **NO damage-taken multiplier.** Status never makes battles harder (punishes honesty).
- **NO XP / streak for the check-in itself** (SDT — don't reward self-report).
- All modifiers applied at battle/workout resolution in the existing `BattleResultCalculator` / XP pipeline via a new `PsychStatusModifierService`.

---

## 5. Backend architecture (Symfony 7, DDD)

### 5.1 Domain — `src/Domain/PsychProfile/`

**Enums:**
- `Enum/MoodQuadrant.php` — DRAINED, AT_EASE, NEUTRAL, ENERGIZED, ON_EDGE
- `Enum/UserIntent.php` — REST, MAINTAIN, PUSH
- `Enum/PsychStatus.php` — CHARGED, STEADY, DORMANT, WEARY, SCATTERED

**Entities:**
- `Entity/PsychCheckIn.php` — id (uuid), userId (FK), moodQuadrant (enum nullable), energyLevel (int 1-5 nullable), intent (UserIntent nullable), assignedStatus (PsychStatus), skipped (bool), checkedInOn (date YYYY-MM-DD — local to user TZ), createdAt.
- `Entity/PsychUserProfile.php` — id, userId (unique FK), currentStatus (PsychStatus), statusValidUntil (datetime), consecutiveSkips (int), featureOptedIn (bool, default false), lastCheckInAt (nullable datetime). Trends (nullable JSON): `dominantStatus7d`, `dominantStatus30d`, `statusDistribution90d`.

### 5.2 Application — `src/Application/PsychProfile/`

**Services:**
- `Service/StatusAssignmentService.php` — `assign(MoodQuadrant, int $energy, UserIntent): PsychStatus` reads rules from `GameSettingRepository` (key `psych.status_rules`).
- `Service/CheckInService.php` — `checkIn(User, ?MoodQuadrant, ?int, ?UserIntent, bool $skipped): PsychCheckIn`. If skipped, inherits previous with 5% decay (decay logic: after each skip add 0.05 chance of forcing Steady; after 7 skips → force Steady always).
- `Service/TodayService.php` — `getToday(User): TodayPayload` — is due? returns questions + last status.
- `Service/PsychStatusModifierService.php` — used by `BattleResultCalculator` / XP pipeline; returns multiplier per activity context.
- `Service/ProfileTrendService.php` — computes rolling dominant-status 7d/30d/90d from `psych_check_in` table.
- `Service/CrisisDetectionService.php` — returns `true` if last 7 days include ≥5 WEARY or SCATTERED statuses. Logged via `LoggerInterface::warning('psych.crisis-pattern')`. No user-facing notification in beta.

**DTOs** (`src/Application/PsychProfile/DTO/`):
- `CheckInRequest.php` — `{mood?: string, energy?: int, intent?: string, skipped: bool}`
- `CheckInResponse.php` — `{id, assignedStatus, statusValidUntil, badgeCopyUa, badgeCopyEn}`
- `TodayResponse.php` — `{isDue, lastStatus, nextCheckInAt, prompts: {q1, q2, q3}}`
- `TrendResponse.php` — `{window, points: [{date, status, count}]}`

### 5.3 Infrastructure — `src/Infrastructure/PsychProfile/Repository/`

- `PsychCheckInRepository.php` — standard repo + `findLastByUser(User)`, `findInRange(User, start, end)`, `deleteOlderThan(DateTime)`.
- `PsychUserProfileRepository.php` — standard + `findOrCreateForUser(User)`.

### 5.4 Controller — `src/Controller/PsychProfileController.php`

Routes (all require `ROLE_USER` auth + feature flag check via middleware):
- `GET /api/psych/today` → `TodayResponse`
- `POST /api/psych/check-in` — body `CheckInRequest` → `CheckInResponse` (201)
- `GET /api/psych/trend?window=7|30|90` → `TrendResponse`
- `POST /api/psych/opt-in` → flips `PsychUserProfile.featureOptedIn=true`
- `POST /api/psych/opt-out` → flips OFF + triggers soft-delete of historical check-ins after `?erase=1`
- `GET /api/psych/export` → full user psych history (GDPR Art. 20)
- `DELETE /api/psych/history` — GDPR Art. 17 hard erase

**Feature flag:**
- Env var `PSYCH_PROFILER_ENABLED` (default `false`). When false → endpoints 404.
- Per-user toggle `PsychUserProfile.featureOptedIn` — when false → `GET /today` returns `{isDue: false, reason: 'not_opted_in'}`.

### 5.5 Migration

`migrations/Version20260418200500_AddPsychProfile.php`:
- Create `psych_check_ins` + `psych_user_profiles` tables (UUID PKs, FK to users, appropriate indexes on `(user_id, checked_in_on)`, `(user_id)` unique for profiles).
- Seed `game_settings`:
  - `psych.status_rules` — JSON array of the §3 rules
  - `psych.xp_multipliers` — JSON map status→multiplier
  - `psych.retention_days` = "180"
  - `psych.crisis_threshold_days` = "5" (out of 7-day window)

### 5.6 Privacy — GDPR integration

- `PsychCheckIn.createdAt` used for retention. Command `app:psych-purge` deletes rows older than `psych.retention_days` (180 by default). Schedule daily cron.
- `/api/psych/export` returns JSON dump of all user's check-ins + profile.
- `/api/psych/history` DELETE purges user's check-ins + resets profile.
- Symfony Monolog `psych` channel for all writes (audit).

### 5.7 Tests

- `tests/Unit/Application/PsychProfile/StatusAssignmentServiceTest.php` — covers all 5 rules + edge cases (75 combinations anchor-tested). Rules loaded from fixture, not hardcoded.
- `tests/Unit/Application/PsychProfile/CheckInServiceTest.php` — happy path, skip inheritance, 7-skip reset to Steady.
- `tests/Unit/Application/PsychProfile/PsychStatusModifierServiceTest.php` — XP multipliers per status.
- `tests/Unit/Application/PsychProfile/CrisisDetectionServiceTest.php` — 5/7 detection.
- `tests/Functional/PsychProfileControllerTest.php` — check-in flow, opt-in gate, feature-flag 404, export, delete.

### 5.8 Test harness integration (Phase 6)

Add one more test endpoint (follow §11 decisions from test-harness spec):

- `POST /api/test/psych/seed` (tester+) — body `{days: int, status: 'WEARY'|'SCATTERED'|…}` seeds N days of the given status. Used by Playwright to trigger crisis-detection log without waiting real days.

---

## 6. App architecture (React Native, 07 Vector Field)

### 6.1 Feature — `src/features/psych/`

**Types** (`types/`):
- `models.ts` — `PsychStatus`, `MoodQuadrant`, `UserIntent` string unions + labels.
- `requests.ts` — `CheckInRequest`.
- `responses.ts` — mirror backend DTOs (snake_case → camelCase mapper).

**API** (`api/psychApi.ts`):
- `getToday()`, `submitCheckIn(req)`, `skipToday()`, `getTrend(window)`, `optIn()`, `optOut()`, `exportHistory()`, `deleteHistory()`.

**Hooks:**
- `useToday()` — TanStack Query, staleTime 5 min.
- `useCheckInMutation()` — invalidates `useToday` + user queries + XP.
- `useTrend(window)`.
- `useOptIn()` / `useOptOut()`.

**Store:**
- `store/psychStore.ts` — zustand: `sessionDismissed: boolean` (ephemeral; user dismissed today's check-in prompt but may return later), `selectedMood`, `selectedEnergy`, `selectedIntent`.

### 6.2 Components — `src/features/psych/components/` (TEXT-ONLY)

- `MoodPicker.tsx` — 5 Paper `List.Item` rows, selectable. **No icons, no emoji.** Labels from `types/models.ts`.
- `EnergyPicker.tsx` — 5 radio-style chips `1 / 2 / 3 / 4 / 5` with text labels below. **No battery icons.**
- `IntentPicker.tsx` — 3 large Paper `Button` rows (Rest / Keep rhythm / Push). **No emoji.**
- `ReflectFlow.tsx` — orchestrates 3-step flow, progress indicator "1 of 3" / "2 of 3" / "3 of 3", "Skip" link in the header.
- `PsychStatusBadge.tsx` — small tag with status text + Vector-dialect badge copy. Used in profile header. Subtle copper border when CHARGED.
- `OptInCard.tsx` — shown in Settings + as a one-time prompt on first launch after feature rolls out. Explains value + privacy + links to detailed policy.

**Styling:**
- All components use 07 Vector Field tokens (`colors`, `typography`, `spacing`).
- Crosshair motif on the question card hero.
- Big tap targets (48×48 minimum, WCAG 2.5.5).
- Text minimum 13px per design-principles-2026-04-18.md mass-audience rules.

### 6.3 Screens

- `app/(main)/reflect.tsx` — full-screen ReflectFlow, modal-style.
- `app/(main)/reflect-summary.tsx` — shown after submit: assigned status, badge copy, "What changes today" (copy per status).
- `app/(main)/settings/psych.tsx` — opt-in toggle, export, delete, therapist links.

### 6.4 First-daily-login interceptor

In `app/_layout.tsx`:
- After auth gate, add a `useEffect` that calls `useToday()`.
- If `isDue && featureOptedIn && !sessionDismissed` → router.push('/(main)/reflect').
- User can skip → sets `sessionDismissed=true` for this session.
- **Never** renders the interceptor if feature flag off (server returns `{isDue:false}`).

### 6.5 Profile integration

- `app/(main)/profile.tsx` — under avatar, show `<PsychStatusBadge />` if user has a current status + feature opted in.
- Subtle, not prominent — «частина UI, а не центр».

### 6.6 Tests (Jest + RNTL)

- `src/features/psych/__tests__/StatusAssignmentRules.test.ts` — mirror backend rules on client side (we reuse status enum; rules are backend-authoritative — this test just validates label mapping).
- `src/features/psych/__tests__/MoodPicker.test.tsx`, `EnergyPicker.test.tsx`, `IntentPicker.test.tsx` — text rendering, no emoji regression.
- `src/features/psych/__tests__/ReflectFlow.test.tsx` — 3-step flow, skip handling.
- `src/features/psych/__tests__/useToday.test.ts` — caching + feature-flag-off handling.

### 6.7 Debug drawer integration

Add a new tab `DebugDrawer/tabs/PsychTab.tsx` (admin+ via Phase 7 pattern):
- Trigger a synthetic check-in (seed via new `/api/test/psych/seed`).
- Force a status for testing XP multipliers.
- Clear user's psych history.

---

## 7. Implementation phases

Do in this order on the SAME feature branches (`feature/foundation-portals-mobs-races` + `feature/foundation-07-theme-map`):

1. **Backend domain + repository + migration** — ship first; unlocks everything.
2. **Backend services + controller + tests** — green before moving on.
3. **Backend test-harness endpoint** — `/api/test/psych/seed`.
4. **App types + API + hooks** — parallel after #1 lands.
5. **App components (TEXT-ONLY pickers)** — uses 07 theme.
6. **App screens + interceptor + profile badge** — integration.
7. **Debug drawer Psych tab**.
8. **Jest tests**.

Target: total ~2500 LOC (backend ~1500, app ~1000). Per research §10, ~3 dev-weeks human; agents can compress.

---

## 8. Out of scope (explicitly deferred to post-beta)

- Seasonal question rotation.
- Admin dashboard for cohort status distribution.
- User-facing trend UI (7/30/90 day charts).
- Notification tone variation per status (only 1 soft Weary-variant in beta).
- Damage-taken multiplier.
- Social share of status.
- Minor-specific age gate.
- PERMA-5 monthly pulse.
- AI-assisted action suggestions.

---

## 9. Sources

- `/Users/oleksandr/PhpstormProjects/rpgfit/BA/outputs/10-psychology-research.md` — authoritative design source
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/psychology-profiler.md` — original vision doc
- `/Users/oleksandr/PhpstormProjects/rpgfit/docs/vision/product-decisions-2026-04-18.md`
