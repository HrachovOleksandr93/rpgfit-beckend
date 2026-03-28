# Workout Plan Generation System - Research Data

---

## 1. Exercise Database

### Legend
- **Type**: C = Compound, I = Isolation
- **Priority**: 1 = heavy compound (do first), 2 = secondary compound, 3 = accessory compound, 4 = primary isolation, 5 = finishing isolation
- **Base**: fundamental movement pattern (squat, hinge, press, pull)
- **Difficulty**: B = Beginner, I = Intermediate, A = Advanced

---

### 1.1 Chest

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Barbell Bench Press | chest | triceps, front_delts | barbell | B | C | 1 | yes |
| 2 | Incline Barbell Bench Press | chest (upper) | triceps, front_delts | barbell | I | C | 1 | yes |
| 3 | Dumbbell Bench Press | chest | triceps, front_delts | dumbbell | B | C | 2 | no |
| 4 | Incline Dumbbell Bench Press | chest (upper) | triceps, front_delts | dumbbell | B | C | 2 | no |
| 5 | Decline Barbell Bench Press | chest (lower) | triceps, front_delts | barbell | I | C | 2 | no |
| 6 | Dumbbell Flye | chest | front_delts | dumbbell | B | I | 4 | no |
| 7 | Incline Dumbbell Flye | chest (upper) | front_delts | dumbbell | B | I | 4 | no |
| 8 | Cable Crossover | chest | front_delts | cable | I | I | 4 | no |
| 9 | Machine Chest Press | chest | triceps, front_delts | machine | B | C | 3 | no |
| 10 | Pec Deck / Machine Flye | chest | front_delts | machine | B | I | 5 | no |
| 11 | Push-Up | chest | triceps, front_delts, core | bodyweight | B | C | 3 | no |
| 12 | Dips (Chest Emphasis) | chest (lower) | triceps, front_delts | bodyweight | I | C | 2 | no |
| 13 | Low Cable Flye (Low-to-High) | chest (upper) | front_delts | cable | I | I | 4 | no |
| 14 | Landmine Press | chest (upper) | triceps, front_delts | barbell | I | C | 3 | no |
| 15 | Svend Press | chest (inner) | front_delts | dumbbell | B | I | 5 | no |

---

### 1.2 Back

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Barbell Deadlift | back (lower), back (upper) | glutes, hamstrings, core, traps | barbell | I | C | 1 | yes |
| 2 | Barbell Bent-Over Row | back (upper) | biceps, rear_delts, core | barbell | I | C | 1 | yes |
| 3 | Pull-Up | back (lats) | biceps, rear_delts, core | bodyweight | I | C | 1 | yes |
| 4 | Lat Pulldown | back (lats) | biceps, rear_delts | cable | B | C | 2 | no |
| 5 | Seated Cable Row | back (mid) | biceps, rear_delts | cable | B | C | 2 | no |
| 6 | Dumbbell Single-Arm Row | back (lats) | biceps, rear_delts | dumbbell | B | C | 2 | no |
| 7 | T-Bar Row | back (mid) | biceps, rear_delts, core | barbell | I | C | 2 | no |
| 8 | Chin-Up | back (lats) | biceps | bodyweight | I | C | 2 | no |
| 9 | Face Pull | back (upper), rear_delts | traps | cable | B | I | 4 | no |
| 10 | Chest-Supported Row (Machine) | back (mid) | biceps, rear_delts | machine | B | C | 3 | no |
| 11 | Pendlay Row | back (upper) | biceps, rear_delts, core | barbell | A | C | 2 | no |
| 12 | Straight-Arm Pulldown | back (lats) | triceps (long head) | cable | I | I | 4 | no |
| 13 | Meadows Row | back (lats) | biceps, rear_delts | barbell | A | C | 3 | no |
| 14 | Rack Pull | back (upper), traps | glutes, hamstrings | barbell | I | C | 2 | no |
| 15 | Inverted Row | back (mid) | biceps, rear_delts, core | bodyweight | B | C | 3 | no |

---

### 1.3 Shoulders

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Barbell Overhead Press (Standing) | shoulders (front, medial) | triceps, core, traps | barbell | I | C | 1 | yes |
| 2 | Dumbbell Shoulder Press (Seated) | shoulders (front, medial) | triceps, traps | dumbbell | B | C | 1 | no |
| 3 | Arnold Press | shoulders (front, medial) | triceps | dumbbell | I | C | 2 | no |
| 4 | Dumbbell Lateral Raise | shoulders (medial) | traps | dumbbell | B | I | 4 | no |
| 5 | Cable Lateral Raise | shoulders (medial) | traps | cable | B | I | 4 | no |
| 6 | Dumbbell Front Raise | shoulders (front) | chest (upper) | dumbbell | B | I | 5 | no |
| 7 | Barbell Upright Row | shoulders (medial), traps | biceps | barbell | I | C | 3 | no |
| 8 | Dumbbell Rear Delt Flye (Bent-Over) | shoulders (rear) | traps, rhomboids | dumbbell | B | I | 4 | no |
| 9 | Cable Reverse Flye | shoulders (rear) | traps, rhomboids | cable | B | I | 4 | no |
| 10 | Machine Shoulder Press | shoulders (front, medial) | triceps | machine | B | C | 2 | no |
| 11 | Machine Lateral Raise | shoulders (medial) | traps | machine | B | I | 5 | no |
| 12 | Push Press | shoulders (front, medial) | triceps, core, quads | barbell | A | C | 1 | no |
| 13 | Plate Front Raise | shoulders (front) | chest (upper) | dumbbell | B | I | 5 | no |
| 14 | Cable Face Pull | shoulders (rear), traps | biceps, rhomboids | cable | B | C | 3 | no |
| 15 | Handstand Push-Up | shoulders (front, medial) | triceps, core | bodyweight | A | C | 2 | no |

---

### 1.4 Biceps

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Barbell Curl | biceps | forearms | barbell | B | I | 4 | no |
| 2 | Dumbbell Curl (Standing) | biceps | forearms | dumbbell | B | I | 4 | no |
| 3 | EZ-Bar Curl | biceps | forearms | barbell | B | I | 4 | no |
| 4 | Hammer Curl | biceps (brachialis) | forearms (brachioradialis) | dumbbell | B | I | 4 | no |
| 5 | Incline Dumbbell Curl | biceps (long head) | forearms | dumbbell | I | I | 4 | no |
| 6 | Preacher Curl | biceps (short head) | forearms | barbell | I | I | 4 | no |
| 7 | Concentration Curl | biceps (peak) | forearms | dumbbell | B | I | 5 | no |
| 8 | Cable Curl | biceps | forearms | cable | B | I | 4 | no |
| 9 | Spider Curl | biceps (short head) | forearms | dumbbell | I | I | 5 | no |
| 10 | Reverse Curl | biceps (brachialis) | forearms | barbell | I | I | 5 | no |
| 11 | Cable Hammer Curl (Rope) | biceps (brachialis) | forearms | cable | B | I | 4 | no |
| 12 | Machine Curl | biceps | forearms | machine | B | I | 5 | no |
| 13 | Zottman Curl | biceps, forearms | brachialis | dumbbell | I | I | 5 | no |
| 14 | Drag Curl | biceps (long head) | forearms | barbell | I | I | 5 | no |
| 15 | Resistance Band Curl | biceps | forearms | resistance_band | B | I | 5 | no |

---

### 1.5 Triceps

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Close-Grip Bench Press | triceps | chest, front_delts | barbell | I | C | 2 | no |
| 2 | Dips (Tricep Emphasis) | triceps | chest, front_delts | bodyweight | I | C | 2 | no |
| 3 | Overhead Tricep Extension (Dumbbell) | triceps (long head) | - | dumbbell | B | I | 4 | no |
| 4 | Skull Crushers (EZ-Bar) | triceps | - | barbell | I | I | 4 | no |
| 5 | Cable Pushdown (Bar) | triceps (lateral head) | - | cable | B | I | 4 | no |
| 6 | Cable Pushdown (Rope) | triceps | - | cable | B | I | 4 | no |
| 7 | Overhead Cable Extension (Rope) | triceps (long head) | - | cable | B | I | 4 | no |
| 8 | Diamond Push-Up | triceps | chest, front_delts | bodyweight | I | C | 3 | no |
| 9 | Tricep Kickback | triceps | - | dumbbell | B | I | 5 | no |
| 10 | Single-Arm Cable Pushdown | triceps | - | cable | B | I | 5 | no |
| 11 | Machine Tricep Extension | triceps | - | machine | B | I | 5 | no |
| 12 | JM Press | triceps | chest | barbell | A | C | 3 | no |
| 13 | Bench Dips | triceps | chest, front_delts | bodyweight | B | C | 3 | no |
| 14 | Resistance Band Pushdown | triceps | - | resistance_band | B | I | 5 | no |
| 15 | French Press (Barbell) | triceps (long head) | - | barbell | I | I | 4 | no |

---

### 1.6 Quads

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Barbell Back Squat | quads | glutes, hamstrings, core | barbell | I | C | 1 | yes |
| 2 | Barbell Front Squat | quads | glutes, core | barbell | A | C | 1 | yes |
| 3 | Leg Press | quads | glutes, hamstrings | machine | B | C | 2 | no |
| 4 | Bulgarian Split Squat | quads | glutes, hamstrings, core | dumbbell | I | C | 2 | no |
| 5 | Walking Lunge | quads | glutes, hamstrings, core | dumbbell | B | C | 3 | no |
| 6 | Goblet Squat | quads | glutes, core | dumbbell | B | C | 2 | no |
| 7 | Hack Squat (Machine) | quads | glutes | machine | I | C | 2 | no |
| 8 | Leg Extension | quads | - | machine | B | I | 4 | no |
| 9 | Step-Up | quads | glutes, hamstrings | dumbbell | B | C | 3 | no |
| 10 | Sissy Squat | quads | core | bodyweight | A | I | 5 | no |
| 11 | Smith Machine Squat | quads | glutes, hamstrings | machine | B | C | 2 | no |
| 12 | Pistol Squat | quads | glutes, hamstrings, core | bodyweight | A | C | 3 | no |
| 13 | Barbell Lunge | quads | glutes, hamstrings, core | barbell | I | C | 3 | no |
| 14 | Wall Sit | quads | core | bodyweight | B | I | 5 | no |
| 15 | Kettlebell Swing (Quad-dominant) | quads | glutes, hamstrings, core | kettlebell | I | C | 3 | no |

---

### 1.7 Hamstrings

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Romanian Deadlift (Barbell) | hamstrings | glutes, lower_back | barbell | I | C | 1 | yes |
| 2 | Stiff-Leg Deadlift | hamstrings | glutes, lower_back | barbell | I | C | 1 | no |
| 3 | Lying Leg Curl | hamstrings | calves | machine | B | I | 4 | no |
| 4 | Seated Leg Curl | hamstrings | calves | machine | B | I | 4 | no |
| 5 | Dumbbell Romanian Deadlift | hamstrings | glutes, lower_back | dumbbell | B | C | 2 | no |
| 6 | Good Morning | hamstrings | glutes, lower_back, core | barbell | A | C | 2 | no |
| 7 | Nordic Hamstring Curl | hamstrings | calves | bodyweight | A | I | 3 | no |
| 8 | Glute-Ham Raise | hamstrings | glutes, calves | bodyweight | A | C | 2 | no |
| 9 | Single-Leg Romanian Deadlift | hamstrings | glutes, core | dumbbell | I | C | 3 | no |
| 10 | Cable Pull-Through | hamstrings | glutes | cable | B | C | 3 | no |
| 11 | Kettlebell Swing | hamstrings | glutes, core, shoulders | kettlebell | I | C | 2 | no |
| 12 | Swiss Ball Hamstring Curl | hamstrings | glutes, core | bodyweight | I | I | 5 | no |
| 13 | Barbell Hip Hinge | hamstrings | glutes, lower_back | barbell | B | C | 3 | no |
| 14 | Resistance Band Leg Curl | hamstrings | calves | resistance_band | B | I | 5 | no |
| 15 | Deficit Romanian Deadlift | hamstrings | glutes, lower_back | barbell | A | C | 2 | no |

---

### 1.8 Glutes

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Barbell Hip Thrust | glutes | hamstrings, core | barbell | I | C | 1 | yes |
| 2 | Barbell Back Squat (Wide Stance) | glutes | quads, hamstrings, core | barbell | I | C | 1 | no |
| 3 | Sumo Deadlift | glutes | hamstrings, quads, core, back | barbell | A | C | 1 | no |
| 4 | Glute Bridge | glutes | hamstrings | bodyweight | B | I | 4 | no |
| 5 | Single-Leg Glute Bridge | glutes | hamstrings, core | bodyweight | B | I | 4 | no |
| 6 | Cable Kickback | glutes | hamstrings | cable | B | I | 4 | no |
| 7 | Cable Pull-Through | glutes | hamstrings | cable | B | C | 3 | no |
| 8 | Dumbbell Hip Thrust | glutes | hamstrings | dumbbell | B | C | 2 | no |
| 9 | Bulgarian Split Squat (Glute Focus) | glutes | quads, hamstrings, core | dumbbell | I | C | 2 | no |
| 10 | Reverse Lunge | glutes | quads, hamstrings | dumbbell | B | C | 3 | no |
| 11 | Banded Clamshell | glutes (medius) | - | resistance_band | B | I | 5 | no |
| 12 | Banded Lateral Walk | glutes (medius) | - | resistance_band | B | I | 5 | no |
| 13 | Machine Hip Abduction | glutes (medius) | - | machine | B | I | 5 | no |
| 14 | Frog Pump | glutes | hamstrings | bodyweight | B | I | 5 | no |
| 15 | Kettlebell Sumo Squat | glutes | quads, hamstrings | kettlebell | B | C | 3 | no |

---

### 1.9 Calves

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Standing Calf Raise (Machine) | calves (gastrocnemius) | - | machine | B | I | 4 | no |
| 2 | Seated Calf Raise | calves (soleus) | - | machine | B | I | 4 | no |
| 3 | Single-Leg Standing Calf Raise | calves (gastrocnemius) | core | dumbbell | B | I | 4 | no |
| 4 | Leg Press Calf Raise | calves (gastrocnemius) | - | machine | B | I | 4 | no |
| 5 | Barbell Standing Calf Raise | calves (gastrocnemius) | core | barbell | I | I | 4 | no |
| 6 | Donkey Calf Raise | calves (gastrocnemius) | - | machine | I | I | 4 | no |
| 7 | Smith Machine Calf Raise | calves (gastrocnemius) | - | machine | B | I | 4 | no |
| 8 | Bodyweight Calf Raise | calves | - | bodyweight | B | I | 5 | no |
| 9 | Jump Rope | calves | quads, core | bodyweight | B | C | 5 | no |
| 10 | Farmer's Walk (on toes) | calves | forearms, traps, core | dumbbell | I | C | 5 | no |
| 11 | Tibialis Raise | calves (tibialis anterior) | - | bodyweight | B | I | 5 | no |
| 12 | Resistance Band Calf Raise | calves | - | resistance_band | B | I | 5 | no |

---

### 1.10 Core

| # | Exercise | Primary | Secondary | Equipment | Difficulty | Type | Priority | Base |
|---|----------|---------|-----------|-----------|------------|------|----------|------|
| 1 | Plank | core (rectus abdominis, transverse) | shoulders | bodyweight | B | I | 4 | no |
| 2 | Hanging Leg Raise | core (lower abs) | hip_flexors | bodyweight | I | I | 4 | no |
| 3 | Cable Crunch | core (rectus abdominis) | - | cable | B | I | 4 | no |
| 4 | Ab Wheel Rollout | core (rectus abdominis, transverse) | shoulders, lats | bodyweight | A | C | 3 | no |
| 5 | Russian Twist | core (obliques) | hip_flexors | dumbbell | B | I | 5 | no |
| 6 | Bicycle Crunch | core (obliques, rectus) | hip_flexors | bodyweight | B | I | 5 | no |
| 7 | Dead Bug | core (transverse abdominis) | hip_flexors | bodyweight | B | I | 5 | no |
| 8 | Pallof Press | core (obliques, anti-rotation) | shoulders | cable | I | I | 4 | no |
| 9 | Side Plank | core (obliques) | shoulders, glutes | bodyweight | B | I | 4 | no |
| 10 | Mountain Climber | core | hip_flexors, shoulders | bodyweight | B | C | 5 | no |
| 11 | Leg Raise (Lying) | core (lower abs) | hip_flexors | bodyweight | B | I | 5 | no |
| 12 | Crunch | core (rectus abdominis) | - | bodyweight | B | I | 5 | no |
| 13 | Dragon Flag | core | hip_flexors, lats | bodyweight | A | I | 3 | no |
| 14 | Woodchop (Cable) | core (obliques) | shoulders | cable | I | C | 4 | no |
| 15 | Farmer's Walk | core (transverse, obliques) | traps, forearms | dumbbell | B | C | 3 | no |

---

## 2. Split Workout Templates

### 2.1 Two Days Per Week -- Full Body A / Full Body B

**Day 1 - Full Body A (Compound Focus):**
- Quads (squat pattern)
- Chest (horizontal press)
- Back (horizontal pull)
- Hamstrings/Glutes (hinge pattern)
- Shoulders (vertical press)
- Core

**Day 2 - Full Body B (Variation Focus):**
- Quads (lunge/split stance)
- Back (vertical pull)
- Chest (incline press)
- Glutes/Hamstrings (hip thrust/RDL)
- Shoulders (lateral raises)
- Biceps + Triceps (superset)
- Core

**Exercise count per day:** 6-8 exercises, 2-3 sets each

---

### 2.2 Three Days Per Week -- Push / Pull / Legs

**Day 1 - Push:**
- Chest: 3-4 exercises
- Shoulders: 2-3 exercises
- Triceps: 2 exercises

**Day 2 - Pull:**
- Back: 3-4 exercises
- Biceps: 2 exercises
- Rear Delts: 1 exercise

**Day 3 - Legs:**
- Quads: 2-3 exercises
- Hamstrings: 2 exercises
- Glutes: 1-2 exercises
- Calves: 1-2 exercises
- Core: 2 exercises

#### Alternative: Full Body A / B / C

**Day A:** Squat, Bench Press, Barbell Row, Lateral Raise, Curl, Plank
**Day B:** Deadlift, Overhead Press, Lat Pulldown, Leg Curl, Tricep Extension, Hanging Leg Raise
**Day C:** Front Squat, Incline DB Press, Seated Row, Hip Thrust, Face Pull, Ab Wheel

---

### 2.3 Four Days Per Week -- Upper / Lower Split

**Day 1 - Upper A (Strength Focus):**
- Chest: Barbell Bench Press, Incline DB Press
- Back: Barbell Row, Lat Pulldown
- Shoulders: Overhead Press, Lateral Raise
- Biceps: Barbell Curl
- Triceps: Cable Pushdown

**Day 2 - Lower A (Quad Focus):**
- Quads: Barbell Squat, Leg Press, Leg Extension
- Hamstrings: Romanian Deadlift, Lying Leg Curl
- Glutes: Hip Thrust
- Calves: Standing Calf Raise
- Core: Hanging Leg Raise, Plank

**Day 3 - Upper B (Hypertrophy Focus):**
- Chest: Dumbbell Bench Press, Cable Crossover
- Back: Pull-Up, Seated Cable Row, Face Pull
- Shoulders: Arnold Press, Rear Delt Flye
- Biceps: Hammer Curl, Incline Curl
- Triceps: Overhead Tricep Extension

**Day 4 - Lower B (Posterior Chain Focus):**
- Hamstrings: Stiff-Leg Deadlift, Seated Leg Curl
- Glutes: Hip Thrust, Cable Kickback
- Quads: Bulgarian Split Squat, Goblet Squat
- Calves: Seated Calf Raise
- Core: Cable Crunch, Pallof Press

---

### 2.4 Five Days Per Week -- Push / Pull / Legs / Upper / Lower

**Day 1 - Push:**
- Chest: 3 exercises
- Shoulders: 2 exercises
- Triceps: 2 exercises

**Day 2 - Pull:**
- Back: 3-4 exercises
- Biceps: 2 exercises
- Rear Delts: 1 exercise

**Day 3 - Legs:**
- Quads: 2-3 exercises
- Hamstrings: 2 exercises
- Glutes: 1-2 exercises
- Calves: 2 exercises
- Core: 2 exercises

**Day 4 - Upper (Hypertrophy):**
- Chest: 2 exercises
- Back: 2 exercises
- Shoulders: 2 exercises
- Biceps: 1 exercise
- Triceps: 1 exercise

**Day 5 - Lower (Hypertrophy):**
- Quads: 2 exercises
- Hamstrings: 2 exercises
- Glutes: 2 exercises
- Calves: 1 exercise
- Core: 2 exercises

---

### 2.5 Six Days Per Week -- Push / Pull / Legs x 2

**Day 1 - Push A (Heavy):**
- Barbell Bench Press: 4x5
- Overhead Press: 3x6
- Incline Dumbbell Press: 3x8
- Lateral Raise: 3x12
- Cable Pushdown: 3x10
- Overhead Tricep Extension: 3x12

**Day 2 - Pull A (Heavy):**
- Barbell Deadlift: 3x5
- Barbell Row: 4x6
- Lat Pulldown: 3x8
- Face Pull: 3x15
- Barbell Curl: 3x8
- Hammer Curl: 3x10

**Day 3 - Legs A (Heavy):**
- Barbell Squat: 4x5
- Romanian Deadlift: 3x8
- Leg Press: 3x10
- Lying Leg Curl: 3x10
- Standing Calf Raise: 4x12
- Hanging Leg Raise: 3x12

**Day 4 - Push B (Volume):**
- Dumbbell Bench Press: 4x10
- Arnold Press: 3x10
- Cable Crossover: 3x12
- Lateral Raise: 4x15
- Dips: 3x12
- Cable Pushdown (Rope): 3x15

**Day 5 - Pull B (Volume):**
- Pull-Up: 4x8-12
- Seated Cable Row: 3x10
- Dumbbell Row: 3x12
- Rear Delt Flye: 3x15
- Incline Curl: 3x12
- Cable Curl: 3x15

**Day 6 - Legs B (Volume):**
- Front Squat: 3x8
- Hip Thrust: 4x10
- Bulgarian Split Squat: 3x10/leg
- Seated Leg Curl: 3x12
- Leg Extension: 3x12
- Seated Calf Raise: 4x15
- Cable Crunch: 3x15

---

### 2.6 Muscle Group to Day Mapping (Summary)

| Days/Week | Split Type | Day 1 | Day 2 | Day 3 | Day 4 | Day 5 | Day 6 |
|-----------|-----------|-------|-------|-------|-------|-------|-------|
| 2 | Full Body | All | All | - | - | - | - |
| 3 | PPL | chest, shoulders, triceps | back, biceps, rear_delts | quads, hamstrings, glutes, calves, core | - | - | - |
| 3 | Full Body | All | All | All | - | - | - |
| 4 | Upper/Lower | chest, back, shoulders, biceps, triceps | quads, hamstrings, glutes, calves, core | chest, back, shoulders, biceps, triceps | quads, hamstrings, glutes, calves, core | - | - |
| 5 | PPLUL | chest, shoulders, triceps | back, biceps | quads, hamstrings, glutes, calves, core | chest, back, shoulders, biceps, triceps | quads, hamstrings, glutes, calves, core | - |
| 6 | PPL x2 | chest, shoulders, triceps | back, biceps | quads, hamstrings, glutes, calves, core | chest, shoulders, triceps | back, biceps | quads, hamstrings, glutes, calves, core |

---

## 3. Synergy Pairs (Muscle Group Combos)

### 3.1 Push Synergy
- **Chest + Triceps + Front Delts**
- Reasoning: All pressing movements (bench press, overhead press) recruit triceps and anterior deltoids as synergists.
- When you bench press, triceps are already fatigued; isolation work afterward requires less volume.

### 3.2 Pull Synergy
- **Back + Biceps + Rear Delts**
- Reasoning: All pulling movements (rows, pulldowns) recruit biceps and posterior deltoids as synergists.
- Biceps are pre-fatigued from compound pulls; 2-3 sets of curls is sufficient.

### 3.3 Legs Synergy
- **Quads + Hamstrings + Glutes + Calves**
- These are trained together because:
  - Squats hit quads, glutes, and hamstrings simultaneously.
  - Hip hinges (RDL/deadlift) hit hamstrings and glutes.
  - Calves are only on the legs and pair naturally.

### 3.4 Upper Body Synergy (for Upper/Lower splits)
- **Chest + Back + Shoulders + Biceps + Triceps**
- Agonist/antagonist supersets are efficient: bench press + row, overhead press + pulldown.
- Saves time; opposing muscles recover while the other works.

### 3.5 Anti-Synergy (avoid pairing)
- **Chest day + Shoulders day on consecutive days**: Front delts are hammered in both; need 48h rest.
- **Back day + Biceps day on consecutive days**: Biceps won't recover.
- **Heavy Deadlift day + Heavy Squat day on consecutive days**: Lower back and CNS fatigue.

### 3.6 Synergy Pair Matrix

| Muscle Group | Strong Synergy | Moderate Synergy | Weak/No Synergy |
|-------------|---------------|-----------------|----------------|
| Chest | Triceps, Front Delts | Core | Biceps, Back, Legs |
| Back | Biceps, Rear Delts | Core, Forearms | Chest, Triceps |
| Shoulders | Triceps (pressing), Traps | Core | Biceps (except upright row) |
| Quads | Glutes, Core | Hamstrings, Calves | Upper body |
| Hamstrings | Glutes, Lower Back | Calves | Upper body |
| Glutes | Hamstrings, Quads, Core | Lower Back | Upper body |
| Core | - | Everything (stabilizer) | - |

---

## 4. Cardio / Running Plan Generation

### 4.1 Beginner Running Plan Progression

| Week | Run/Walk Ratio | Total Duration | Frequency |
|------|---------------|----------------|-----------|
| 1-2 | Run 1 min / Walk 2 min | 20-25 min | 3x/week |
| 3-4 | Run 2 min / Walk 1 min | 25-30 min | 3x/week |
| 5-6 | Run 3 min / Walk 1 min | 25-30 min | 3x/week |
| 7-8 | Run 5 min / Walk 1 min | 30 min | 3-4x/week |
| 9-10 | Continuous 15-20 min | 30 min | 3-4x/week |
| 11-12 | Continuous 20-30 min | 30-35 min | 3-4x/week |

### 4.2 Calculating Suggested Distance

**Based on recent history (last 2-4 weeks):**

```
average_recent_distance = avg(last_4_sessions_distance)
suggested_distance = average_recent_distance * 1.05 to 1.10

// Weekly volume rule:
total_weekly_distance_next_week <= total_weekly_distance_this_week * 1.10
```

**Distance suggestions by goal:**
- Easy run: 60-70% of longest recent run
- Standard run: 80-90% of longest recent run
- Long run: 100-110% of longest recent run (once per week max)

### 4.3 Progressive Overload Rules for Running

1. **10% Rule**: Never increase weekly total distance by more than 10% from one week to the next.
2. **Step-back weeks**: Every 3-4 weeks, reduce volume by 20-30% for recovery.
3. **One variable at a time**: Increase either distance OR intensity (pace), not both simultaneously.
4. **80/20 Rule**: 80% of runs at easy/conversational pace, 20% at moderate-to-hard effort.
5. **Long run cap**: Single longest run should not exceed 30% of total weekly distance.

### 4.4 Rest Day Recommendations

| Weekly Runs | Rest Days | Pattern |
|------------|-----------|---------|
| 2 | 5 | Run-Rest-Rest-Run-Rest-Rest-Rest |
| 3 | 4 | Run-Rest-Run-Rest-Run-Rest-Rest |
| 4 | 3 | Run-Rest-Run-Run-Rest-Run-Rest |
| 5 | 2 | Run-Run-Rest-Run-Run-Rest-Run |
| 6 | 1 | Run-Run-Run-Rest-Run-Run-Run |

**Key rules:**
- Never run hard sessions on consecutive days.
- At least 1 full rest day per week.
- After a long run, the next day should be rest or very easy.

### 4.5 Reward Thresholds (for gamification)

| Threshold | Calculation | XP Multiplier |
|-----------|------------|---------------|
| Minimum (bronze) | 70% of suggested distance | 0.5x |
| Target (silver) | 100% of suggested distance | 1.0x |
| Stretch (gold) | 120% of suggested distance | 1.5x |
| Ultra (platinum) | 150% of suggested distance (rare) | 2.0x |

**Pace-based bonuses:**
- Completing at a pace faster than average recent pace: +25% bonus XP
- Negative split (second half faster than first): +15% bonus XP
- Consistency bonus: 3+ runs in a week: +20% weekly bonus

---

## 5. Yoga / Flexibility Plan Patterns

### 5.1 Common Yoga Poses (20 poses)

| # | Pose Name | Sanskrit | Category | Difficulty | Duration (breaths) | Focus |
|---|-----------|----------|----------|------------|-------------------|-------|
| 1 | Mountain Pose | Tadasana | Standing | B | 5-10 | alignment, grounding |
| 2 | Forward Fold | Uttanasana | Standing | B | 5-8 | hamstrings, spine |
| 3 | Downward Dog | Adho Mukha Svanasana | Standing/Inversion | B | 5-10 | full body stretch |
| 4 | Warrior I | Virabhadrasana I | Standing | B | 5-8 | quads, hip flexors |
| 5 | Warrior II | Virabhadrasana II | Standing | B | 5-8 | hips, legs, core |
| 6 | Triangle Pose | Trikonasana | Standing | I | 5-8 | hips, hamstrings, obliques |
| 7 | Chair Pose | Utkatasana | Standing | B | 5-8 | quads, glutes, core |
| 8 | Tree Pose | Vrksasana | Balance | B | 5-10 | balance, ankles, core |
| 9 | Eagle Pose | Garudasana | Balance | I | 5-8 | shoulders, hips, balance |
| 10 | Half Moon | Ardha Chandrasana | Balance | A | 5-8 | balance, hamstrings, core |
| 11 | Plank Pose | Phalakasana | Core | B | 5-10 | core, shoulders, arms |
| 12 | Boat Pose | Navasana | Core | I | 5-8 | core, hip flexors |
| 13 | Cobra Pose | Bhujangasana | Backbend | B | 5-8 | spine extension, chest |
| 14 | Bridge Pose | Setu Bandhasana | Backbend | B | 5-10 | glutes, spine, chest |
| 15 | Pigeon Pose | Eka Pada Rajakapotasana | Hip Opener | I | 8-15 | hip flexors, glutes |
| 16 | Seated Forward Fold | Paschimottanasana | Floor/Stretch | B | 8-10 | hamstrings, spine |
| 17 | Supine Twist | Supta Matsyendrasana | Floor/Stretch | B | 8-10 | spine rotation, chest |
| 18 | Cat-Cow | Marjaryasana-Bitilasana | Warm-up | B | 10 cycles | spine mobility |
| 19 | Child's Pose | Balasana | Restorative | B | 10-15 | back, hips, rest |
| 20 | Corpse Pose | Savasana | Restorative | B | 2-5 min | full relaxation |

### 5.2 Yoga Sequence Structure

**Standard class structure (45-60 min):**

```
Phase 1: WARM-UP (5-10 min)
  - Child's Pose (centering)
  - Cat-Cow (spine mobilization)
  - Downward Dog (full body activation)

Phase 2: SUN SALUTATION (5-10 min)
  - Mountain Pose → Forward Fold → Half Lift → Plank → Cobra → Downward Dog
  - Repeat 3-5 rounds

Phase 3: STANDING POSES (10-15 min)
  - Warrior I → Warrior II → Triangle
  - Chair Pose
  - Forward Fold transitions

Phase 4: BALANCE POSES (5-10 min)
  - Tree Pose
  - Eagle Pose
  - Half Moon (advanced)

Phase 5: FLOOR WORK (10-15 min)
  - Boat Pose (core)
  - Bridge Pose (backbend)
  - Pigeon Pose (hip opener)
  - Seated Forward Fold

Phase 6: COOL-DOWN & RESTORATION (5-10 min)
  - Supine Twist
  - Happy Baby (optional)
  - Child's Pose
  - Corpse Pose (final relaxation)
```

### 5.3 Difficulty-Based Sequencing

| Level | Duration | Poses | Notes |
|-------|----------|-------|-------|
| Beginner | 20-30 min | 8-10 poses | No balance, no inversions, longer holds |
| Intermediate | 30-45 min | 12-15 poses | Include balance, mild backbends |
| Advanced | 45-60 min | 15-20 poses | Arm balances, deep backbends, inversions |

---

## 6. Sets / Reps / Rest Recommendations

### 6.1 By Training Goal

| Goal | Sets | Reps | Rest | Intensity (% 1RM) |
|------|------|------|------|-------------------|
| Strength | 4-6 | 1-5 | 3-5 min | 85-100% |
| Hypertrophy | 3-4 | 6-12 | 60-120s | 65-85% |
| Muscular Endurance | 2-3 | 12-20 | 30-60s | 50-65% |
| Power | 3-5 | 1-5 | 3-5 min | 70-90% (explosive) |

### 6.2 By Exercise Type

| Exercise Type | Sets | Reps | Rest | Examples |
|--------------|------|------|------|----------|
| Heavy Compound (Priority 1) | 4-5 | 4-6 | 2-3 min | Squat, Bench, Deadlift, OHP |
| Secondary Compound (Priority 2) | 3-4 | 6-10 | 90-120s | Incline Press, Rows, Front Squat |
| Accessory Compound (Priority 3) | 3 | 8-12 | 60-90s | Lunges, Dips, Pull-Ups |
| Primary Isolation (Priority 4) | 3 | 10-15 | 60-90s | Lateral Raise, Curls, Leg Curl |
| Finishing Isolation (Priority 5) | 2-3 | 12-20 | 45-60s | Pec Deck, Cable Flye, Calf Raise |
| Bodyweight | 3 | 8-15 (or to failure -2) | 60s | Push-ups, Pull-ups, Planks |

### 6.3 Volume Recommendations Per Muscle Group Per Week

| Muscle Group | Minimum (sets/week) | Optimal (sets/week) | Maximum (sets/week) |
|-------------|--------------------|--------------------|---------------------|
| Chest | 10 | 12-16 | 20 |
| Back | 10 | 14-18 | 22 |
| Shoulders (side/rear) | 8 | 12-16 | 20 |
| Biceps | 6 | 10-14 | 18 |
| Triceps | 6 | 10-14 | 18 |
| Quads | 8 | 12-16 | 20 |
| Hamstrings | 6 | 10-14 | 18 |
| Glutes | 6 | 10-14 | 20 |
| Calves | 6 | 10-14 | 18 |
| Core (direct) | 4 | 6-10 | 14 |

*Note: Compound movements count toward synergist muscles. For example, 4 sets of bench press count as 4 sets of chest AND ~2 effective sets of triceps.*

### 6.4 Tempo Recommendations

| Goal | Eccentric | Pause | Concentric | Example |
|------|-----------|-------|------------|---------|
| Strength | 2s | 1s | Explosive | 2-1-X-0 |
| Hypertrophy | 3s | 1s | 2s | 3-1-2-0 |
| Endurance | 2s | 0s | 2s | 2-0-2-0 |
| Control/Rehab | 4s | 2s | 3s | 4-2-3-0 |

### 6.5 Workout Duration Guidelines

| Split Type | Target Duration | Exercise Count |
|-----------|----------------|---------------|
| Full Body | 45-60 min | 6-8 exercises |
| Upper Body | 45-60 min | 7-9 exercises |
| Lower Body | 40-55 min | 6-8 exercises |
| Push | 40-55 min | 6-8 exercises |
| Pull | 40-55 min | 6-8 exercises |
| Legs | 45-60 min | 7-9 exercises |
| Arms (bro split) | 30-45 min | 6-8 exercises |

### 6.6 Warm-Up Protocol

```
1. General warm-up: 5 min light cardio (bike, rowing, walking)
2. Dynamic stretches: 5 min (arm circles, leg swings, hip circles)
3. Specific warm-up sets for first exercise:
   - Set 1: empty bar / very light × 10-15 reps
   - Set 2: 50% working weight × 8 reps
   - Set 3: 70% working weight × 5 reps
   - Set 4: 85% working weight × 2-3 reps
4. Begin working sets
```

---

## 7. Quick Reference: Exercise Order Within a Workout

### General Rules:
1. **Compound before isolation** (always)
2. **Free weights before machines** (generally)
3. **Large muscle groups before small** (chest/back before biceps/triceps)
4. **Bilateral before unilateral** (squat before lunge)
5. **Higher priority number = later in workout**

### Standard ordering within a Push day:
1. Barbell Bench Press (Priority 1 - heavy compound)
2. Incline Dumbbell Press (Priority 2 - secondary compound)
3. Overhead Press or Arnold Press (Priority 2)
4. Lateral Raise (Priority 4 - isolation)
5. Cable Crossover or Flye (Priority 4 - isolation)
6. Cable Pushdown (Priority 4 - isolation)
7. Overhead Tricep Extension (Priority 5 - finishing)

### Standard ordering within a Pull day:
1. Barbell Deadlift or Row (Priority 1 - heavy compound)
2. Pull-Up or Lat Pulldown (Priority 1-2)
3. Seated Cable Row or T-Bar Row (Priority 2)
4. Face Pull (Priority 4)
5. Barbell Curl (Priority 4)
6. Hammer Curl (Priority 4-5)

### Standard ordering within a Leg day:
1. Barbell Squat (Priority 1 - heavy compound)
2. Romanian Deadlift (Priority 1 - heavy compound)
3. Leg Press or Bulgarian Split Squat (Priority 2)
4. Hip Thrust (Priority 2)
5. Leg Curl (Priority 4)
6. Leg Extension (Priority 4)
7. Calf Raise (Priority 4)
8. Core work (Priority 4-5)
