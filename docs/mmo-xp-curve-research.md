# MMO XP Curve Research for RPGFit

## 1. Lineage 2 Classic Experience Curve

**Source:** [ExpZone L2 Experience Table](https://expzone.net/l2/?a=experience), [L2Wiki](https://l2wiki.com/classic/Character_Level)

### Key Data Points (XP to next level)

| Level | XP to Next | Growth vs L1 | Level-to-Level Ratio |
|------:|-----------:|-------------:|---------------------:|
| 1 | 68 | 1x | - |
| 5 | 3,154 | 46x | ~1.84x per level |
| 10 | 22,973 | 338x | ~1.36x per level |
| 15 | 77,537 | 1,140x | ~1.23x per level |
| 20 | 187,921 | 2,764x | ~1.17x per level |
| 30 | 675,450 | 9,933x | ~1.08x per level |
| 40 | 1,714,158 | 25,208x | ~1.06x per level |
| 50 | 5,370,971 | 78,985x | **2.13x jump** (tier wall) |
| 60 | 19,798,547 | 291,155x | **2.01x jump** (tier wall) |
| 70 | 44,573,456 | 655,492x | 1.29x per level |
| 75 | 127,050,464 | 1,868,389x | **2.85x jump** |
| 80 | 2,100,000,000 | 30,882,353x | **16.5x jump** (extreme) |
| 85 (max) | 5,555,555,555 | 81,699,346x | 2.65x per level |

**Total XP to max (85):** 19,827,440,000

### Curve Characteristics
- **Type:** Hybrid polynomial (L^3) + exponential with step jumps
- **Early game (1-49):** Smooth polynomial ~L^3 growth, level-to-level ratio gradually decreases from 4.35x to 1.04x
- **Mid game (50-69):** Step jumps at class advancement tiers (1.6x-2.0x sudden increase)
- **End game (70-85):** Super-exponential, with extreme walls at 75 and 79 (16.5x jump)
- **Feel:** Smooth and rewarding early, noticeable "walls" at class advancement levels, extreme grind after 70
- **Grindy from:** Level 50+ (notorious for months of grinding per level at 70+)

---

## 2. World of Warcraft Classic Experience Curve

**Source:** [Wowpedia - Experience to level](https://wowpedia.fandom.com/wiki/Experience_to_level), [WoWWiki - Formulas:XP To Level](https://wowwiki-archive.fandom.com/wiki/Formulas:XP_To_Level), [Warcraft Wiki](https://warcraft.wiki.gg/wiki/Experience_point)

### Formula

```
XP_to_level(L) = ((8 * L) + diff(L)) * MXP(L)

where:
  MXP(L) = 45 + 5 * L                  (base XP from same-level mob)
  diff(L) = difficulty_factor(L)         (increases at higher levels)
```

This is fundamentally a **quadratic** formula: roughly proportional to L^2, with an additional difficulty factor that steepens it at higher levels.

### Key Data Points (XP to next level)

| Level | XP to Next | Cumulative | Growth Ratio |
|------:|-----------:|-----------:|-------------:|
| 1 | 400 | 400 | - |
| 5 | 2,800 | 7,600 | ~1.33x |
| 10 | 7,600 | 35,200 | ~1.17x |
| 15 | 14,400 | 92,800 | ~1.12x |
| 20 | 22,800 | 189,500 | ~1.09x |
| 25 | 33,900 | 335,500 | ~1.08x |
| 30 | 48,000 | 545,700 | ~1.07x |
| 35 | 66,500 | 839,400 | ~1.07x |
| 40 | 89,300 | 1,238,700 | ~1.06x |
| 45 | 116,300 | 1,764,200 | ~1.05x |
| 50 | 148,300 | 2,439,700 | ~1.05x |
| 55 | 185,300 | 3,290,200 | ~1.04x |
| 59 (last) | 218,900 | ~4,085,000 | ~1.04x |

**Total XP 1-60:** 4,084,700 (canonical)

### Curve Characteristics
- **Type:** Quadratic with difficulty factor (polynomial degree ~2)
- **Average level-to-level multiplier:** ~1.05x-1.12x (very smooth)
- **Multiplier range:** 2.25x (L1-2) down to 1.04x (L58-59) -- converges
- **Feel:** Incredibly smooth, no sudden jumps, gradually slowing
- **Grindy from:** Level 40+ (mid-40s is the infamous "wall"), level 50-60 feels very slow
- **Halfway point:** Level 48 by XP (not 30!) -- players are shocked to learn this
- **Design intent:** Keep all content relevant, avoid extreme late-game abandonment

---

## 3. Growth Rate Comparison

| System | Avg Per-Level Multiplier | Curve Type | End/Start Ratio |
|--------|------------------------:|------------|----------------:|
| **WoW Classic** | ~1.05x | Quadratic (L^2) | 547x (L1 to L59) |
| **Lineage 2** | ~1.12x (smooth) + jumps | Polynomial (L^3) + exponential | 81,699,346x |
| **RuneScape** | ~1.10x | Exponential with linear | ~13,000,000x |
| **Fibonacci** | 1.618x (golden ratio) | Pure exponential | 3.54 x 10^20 (!!!) |
| **Diablo 3** | Varies (piecewise) | Piecewise quadratic | ~4,000x |

### Key Insight

Pure Fibonacci (1.618x per level) is **astronomically too steep** for any real application:
- Level 30 Fibonacci = 832,040
- Level 50 Fibonacci = 12,586,269,025
- Level 100 Fibonacci = 354,224,848,179,261,931,520

This is why no major MMO uses pure Fibonacci. WoW and L2 both use **polynomial bases** (L^2 or L^3) with **multiplier adjustments**, not exponential growth.

---

## 4. What Makes MMO Curves Feel Good

Based on analysis of WoW, L2, RuneScape, and game design literature ([Davide Aversa](https://www.davideaversa.it/blog/gamedesign-math-rpg-level-based-progression/), [GameDev.net](https://www.gamedev.net/forums/topic/476272-exp-and-leveling-equations/)):

1. **Early game is fast but not instant** -- Players need to feel progress within the first session, but "ding" shouldn't happen every 30 seconds. WoW L1-2 takes ~20 minutes, L2 L1-2 takes ~5 minutes.

2. **Mid-game is the sweet spot** -- The 20-50 range should feel like steady, predictable progress. Players develop routines here. WoW does this brilliantly with near-constant time-per-level from 20-40.

3. **Tier walls create drama** -- L2's class advancement levels (20, 40, 52, 61, 76) have sudden XP jumps that make reaching them feel like a real achievement. This is the "boss fight" of leveling.

4. **End-game is aspirational** -- The last 10-20% of levels should be genuinely hard. In WoW, levels 50-60 take as long as 1-40 combined. In L2, level 79-80 is a wall that takes months.

5. **The curve should decelerate its acceleration** -- Early levels the multiplier is high (2-4x), mid-levels it's moderate (1.05-1.15x), late levels the per-level multiplier stabilizes but tier jumps compensate.

---

## 5. Proposed MMO-Inspired RPGFit Curve (Alternative)

### Formula

```
xp_to_next(L) = floor((2 * L^2 + 30 * L + 270) * tier_factor(L))
```

**Base polynomial:** `2L^2 + 30L + 270` (WoW-inspired quadratic)

**Tier multipliers** (L2-inspired class advancement walls):

| Tier | Levels | Factor | Name |
|------|--------|-------:|------|
| 1 | 1-14 | x1.0 | Novice |
| 2 | 15-29 | x2.0 | Apprentice |
| 3 | 30-49 | x4.5 | Warrior |
| 4 | 50-69 | x10.0 | Champion |
| 5 | 70-84 | x20.0 | Hero |
| 6 | 85-94 | x40.0 | Legend |
| 7 | 95-99 | x80.0 | Mythic |

### Complete XP Table (100 Levels)

| Level | XP to Next | Cumulative XP | Tier |
|------:|-----------:|--------------:|------|
| 1 | 302 | 302 | Novice |
| 2 | 338 | 640 | Novice |
| 3 | 378 | 1,018 | Novice |
| 4 | 422 | 1,440 | Novice |
| 5 | 470 | 1,910 | Novice |
| 6 | 522 | 2,432 | Novice |
| 7 | 578 | 3,010 | Novice |
| 8 | 638 | 3,648 | Novice |
| 9 | 702 | 4,350 | Novice |
| 10 | 770 | 5,120 | Novice |
| 11 | 842 | 5,962 | Novice |
| 12 | 918 | 6,880 | Novice |
| 13 | 998 | 7,878 | Novice |
| 14 | 1,082 | 8,960 | Novice |
| 15 | 2,340 | 11,300 | Apprentice |
| 16 | 2,524 | 13,824 | Apprentice |
| 17 | 2,716 | 16,540 | Apprentice |
| 18 | 2,916 | 19,456 | Apprentice |
| 19 | 3,124 | 22,580 | Apprentice |
| 20 | 3,340 | 25,920 | Apprentice |
| 21 | 3,564 | 29,484 | Apprentice |
| 22 | 3,796 | 33,280 | Apprentice |
| 23 | 4,036 | 37,316 | Apprentice |
| 24 | 4,284 | 41,600 | Apprentice |
| 25 | 4,540 | 46,140 | Apprentice |
| 26 | 4,804 | 50,944 | Apprentice |
| 27 | 5,076 | 56,020 | Apprentice |
| 28 | 5,356 | 61,376 | Apprentice |
| 29 | 5,644 | 67,020 | Apprentice |
| 30 | 13,365 | 80,385 | Warrior |
| 31 | 14,049 | 94,434 | Warrior |
| 32 | 14,751 | 109,185 | Warrior |
| 33 | 15,471 | 124,656 | Warrior |
| 34 | 16,209 | 140,865 | Warrior |
| 35 | 16,965 | 157,830 | Warrior |
| 36 | 17,739 | 175,569 | Warrior |
| 37 | 18,531 | 194,100 | Warrior |
| 38 | 19,341 | 213,441 | Warrior |
| 39 | 20,169 | 233,610 | Warrior |
| 40 | 21,015 | 254,625 | Warrior |
| 41 | 21,879 | 276,504 | Warrior |
| 42 | 22,761 | 299,265 | Warrior |
| 43 | 23,661 | 322,926 | Warrior |
| 44 | 24,579 | 347,505 | Warrior |
| 45 | 25,515 | 373,020 | Warrior |
| 46 | 26,469 | 399,489 | Warrior |
| 47 | 27,441 | 426,930 | Warrior |
| 48 | 28,431 | 455,361 | Warrior |
| 49 | 29,439 | 484,800 | Warrior |
| 50 | 67,700 | 552,500 | Champion |
| 51 | 70,020 | 622,520 | Champion |
| 52 | 72,380 | 694,900 | Champion |
| 53 | 74,780 | 769,680 | Champion |
| 54 | 77,220 | 846,900 | Champion |
| 55 | 79,700 | 926,600 | Champion |
| 56 | 82,220 | 1,008,820 | Champion |
| 57 | 84,780 | 1,093,600 | Champion |
| 58 | 87,380 | 1,180,980 | Champion |
| 59 | 90,020 | 1,271,000 | Champion |
| 60 | 92,700 | 1,363,700 | Champion |
| 61 | 95,420 | 1,459,120 | Champion |
| 62 | 98,180 | 1,557,300 | Champion |
| 63 | 100,980 | 1,658,280 | Champion |
| 64 | 103,820 | 1,762,100 | Champion |
| 65 | 106,700 | 1,868,800 | Champion |
| 66 | 109,620 | 1,978,420 | Champion |
| 67 | 112,580 | 2,091,000 | Champion |
| 68 | 115,580 | 2,206,580 | Champion |
| 69 | 118,620 | 2,325,200 | Champion |
| 70 | 243,400 | 2,568,600 | Hero |
| 71 | 249,640 | 2,818,240 | Hero |
| 72 | 255,960 | 3,074,200 | Hero |
| 73 | 262,360 | 3,336,560 | Hero |
| 74 | 268,840 | 3,605,400 | Hero |
| 75 | 275,400 | 3,880,800 | Hero |
| 76 | 282,040 | 4,162,840 | Hero |
| 77 | 288,760 | 4,451,600 | Hero |
| 78 | 295,560 | 4,747,160 | Hero |
| 79 | 302,440 | 5,049,600 | Hero |
| 80 | 309,400 | 5,359,000 | Hero |
| 81 | 316,440 | 5,675,440 | Hero |
| 82 | 323,560 | 5,999,000 | Hero |
| 83 | 330,760 | 6,329,760 | Hero |
| 84 | 338,040 | 6,667,800 | Hero |
| 85 | 690,800 | 7,358,600 | Legend |
| 86 | 705,680 | 8,064,280 | Legend |
| 87 | 720,720 | 8,785,000 | Legend |
| 88 | 735,920 | 9,520,920 | Legend |
| 89 | 751,280 | 10,272,200 | Legend |
| 90 | 766,800 | 11,039,000 | Legend |
| 91 | 782,480 | 11,821,480 | Legend |
| 92 | 798,320 | 12,619,800 | Legend |
| 93 | 814,320 | 13,434,120 | Legend |
| 94 | 830,480 | 14,264,600 | Legend |
| 95 | 1,693,600 | 15,958,200 | Mythic |
| 96 | 1,726,560 | 17,684,760 | Mythic |
| 97 | 1,759,840 | 19,444,600 | Mythic |
| 98 | 1,793,440 | 21,238,040 | Mythic |
| 99 | 1,827,360 | 23,065,400 | Mythic |
| 100 | 1,861,600 | 24,927,000 | Mythic |

### Timeline (with progressive daily XP as user gets fitter)

| Level | Cumulative XP | Time to Reach | Fitness Profile |
|------:|--------------:|--------------:|-----------------|
| 10 | 5,120 | ~17 days | Casual walker (300 XP/day) |
| 20 | 25,920 | ~2 months | Regular activity (400 XP/day) |
| 30 | 80,385 | ~5 months | Gym routine forming (500 XP/day) |
| 40 | 254,625 | ~14 months | Consistent gym-goer (600 XP/day) |
| 50 | 552,500 | ~2.2 years | Serious athlete (700 XP/day) |
| 60 | 1,363,700 | ~5.3 years | Serious athlete (700 XP/day) |
| 70 | 2,568,600 | ~10 years | Dedicated trainer (700 XP/day) |
| 80 | 5,359,000 | ~18 years | Dedicated + elite (800 XP/day) |
| 90 | 11,039,000 | ~38 years | Elite performer (800 XP/day) |
| 95 | 15,958,200 | ~49 years | Professional athlete (900 XP/day) |
| 100 | 24,927,000 | ~76 years | Lifetime maximum (900 XP/day) |

### Tier Transition Jumps (the "wall" moments)

These are the dramatic moments where XP requirement suddenly doubles, creating L2-style "class advancement" drama:

| Transition | Before | After | Jump |
|------------|-------:|------:|-----:|
| L14 -> L15 (Novice -> Apprentice) | 1,082 | 2,340 | **2.16x** |
| L29 -> L30 (Apprentice -> Warrior) | 5,644 | 13,365 | **2.37x** |
| L49 -> L50 (Warrior -> Champion) | 29,439 | 67,700 | **2.30x** |
| L69 -> L70 (Champion -> Hero) | 118,620 | 243,400 | **2.05x** |
| L84 -> L85 (Hero -> Legend) | 338,040 | 690,800 | **2.04x** |
| L94 -> L95 (Legend -> Mythic) | 830,480 | 1,693,600 | **2.04x** |

### Statistics

- **Total XP to Level 100:** 24,927,000
- **XP range:** 302 (L1) to 1,827,360 (L99)
- **Overall multiplier L1 to L99:** 6,051x
- **Average per-level growth:** ~1.093x (closest to Lineage 2)

---

## 6. Comparison: Existing Fibonacci Curve vs MMO-Inspired Curve

The existing RPGFit Fibonacci curve (from `leveling-system-research.md`) compared with the MMO-inspired alternative:

| Level | Fibonacci XP/Lvl | MMO XP/Lvl | Fibonacci Cumul | MMO Cumul | Fib Time (progressive) | MMO Time (progressive) |
|------:|------------------:|-----------:|----------------:|----------:|-----------------------:|-----------------------:|
| 10 | 1,265 | 770 | 9,870 | 5,120 | 58 days | 30 days |
| 20 | 2,672 | 3,340 | 30,258 | 25,920 | 5.9 months | 5.1 months |
| 30 | 5,737 | 13,365 | 71,990 | 80,385 | 9.9 months | 10.3 months |
| 50 | 24,606 | 67,700 | 338,015 | 552,500 | 2.3 years | 3.6 years |
| 70 | 105,792 | 243,400 | 1,481,733 | 2,568,600 | 6.1 years | 10.6 years |
| 90 | 454,692 | 766,800 | 6,399,204 | 11,039,000 | 17.0 years | 29.3 years |
| 100 | 915,936 | 1,861,600 | 13,258,222 | 24,927,000 | 30.4 years | 56.5 years |

### Key Differences

| Aspect | Fibonacci Curve | MMO Curve |
|--------|-----------------|-----------|
| **Early game (1-14)** | Flat (928 XP for 7 levels) | Smooth ramp (302-1082) |
| **First level** | 928 XP (~3 days casual) | 302 XP (~1 day casual) |
| **Mid-game feel** | Smooth, no surprises | Tier walls at 15, 30, 50 create drama |
| **L100 reachability** | ~30 years (progressive) | ~56 years (progressive) |
| **Tier transitions** | Gradual | Sharp 2x jumps (MMO "boss fight" feel) |
| **Formula complexity** | Fibonacci interpolation | Simple quadratic + lookup table |
| **Number aesthetics** | Irregular (928, 1265, 1968...) | Clean base, irregular after tier mult |

### Recommendation

The **existing Fibonacci curve is well-calibrated** for its target timelines. The MMO-inspired curve offers:
- **Pros:** Sharper tier walls (more dramatic "leveling up" moments), faster first levels (better onboarding), simpler formula, closer to proven MMO psychology
- **Cons:** Roughly 2x slower at high levels, L100 may be TOO unreachable (76 years at max effort), needs tier multiplier tuning if targets change

**If the goal is "1 day for first level" and "dramatic tier transitions"** -- use the MMO curve.
**If the goal is "smooth Fibonacci elegance" and "L100 in ~30 years for a progressive athlete"** -- keep the existing curve.

A **hybrid approach** is also possible: use the MMO quadratic base with Fibonacci-derived tier multipliers, or adjust the MMO tier factors to match the Fibonacci timeline targets.

---

## 7. Sources

- [Lineage 2 Library - EXP Table/Calculator](https://www.lineage2library.com/exp-tablecalculator)
- [ExpZone - L2 Experience Table](https://expzone.net/l2/?a=experience)
- [Wowpedia - Experience to level](https://wowpedia.fandom.com/wiki/Experience_to_level)
- [WoWWiki - Formulas:XP To Level](https://wowwiki-archive.fandom.com/wiki/Formulas:XP_To_Level)
- [Warcraft Wiki - Experience point](https://warcraft.wiki.gg/wiki/Experience_point)
- [Davide Aversa - GameDesign Math: RPG Level-based Progression](https://www.davideaversa.it/blog/gamedesign-math-rpg-level-based-progression/)
- [Pav Creations - Level systems and character growth](https://pavcreations.com/level-systems-and-character-growth-in-rpg-games/)
- [GameDev.net - EXP and leveling equations](https://www.gamedev.net/forums/topic/476272-exp-and-leveling-equations/)
- [Gamasutra - Quantitative design: How to define XP thresholds](https://www.gamedeveloper.com/design/quantitative-design---how-to-define-xp-thresholds-)
