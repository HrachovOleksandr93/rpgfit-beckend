# RPG Fitness App - Leveling System Research

## 1. Core Formula

### Fibonacci-Based Level Progression

```
fib_index(level) = 1.0 + (level - 1) * 15 / 99
fib_interpolated(x) = fib[floor(x)] * (1 - frac(x)) + fib[ceil(x)] * frac(x)
xp_to_next_level(N) = max(100, round(928 * fib_interpolated(fib_index(N))))
total_xp(N) = sum(xp_to_next_level(1..N))
```

**Parameters:**
- Fibonacci sequence: `fib[1]=1, fib[2]=1, fib[n]=fib[n-1]+fib[n-2]`
- Rate multiplier: **928**
- Fibonacci index range: levels 1-100 map to fib indices **1.0 to 16.0** (interpolated)
- Minimum XP per level: **100**
- `fib[16] = 987` (the maximum Fibonacci number used)

**Why this works:** Standard Fibonacci grows at ratio phi (1.618x per index). By mapping 100 levels to only 16 Fibonacci indices, we get meaningful exponential growth without making high levels astronomically unreachable. The interpolation between Fibonacci numbers ensures smooth progression rather than sudden jumps.

---

## 2. Complete Level Table (Levels 1-100)

| Level | Fib Index | XP to Next Level | Total XP Required | Tier |
|------:|----------:|------------------:|------------------:|------|
| 1 | 1.00 | 928 | 928 | Novice |
| 2 | 1.15 | 928 | 1,856 | Novice |
| 3 | 1.30 | 928 | 2,784 | Novice |
| 4 | 1.45 | 928 | 3,712 | Novice |
| 5 | 1.61 | 928 | 4,640 | Novice |
| 6 | 1.76 | 928 | 5,568 | Novice |
| 7 | 1.91 | 928 | 6,496 | Novice |
| 8 | 2.06 | 984 | 7,480 | Novice |
| 9 | 2.21 | 1,125 | 8,605 | Novice |
| 10 | 2.36 | 1,265 | 9,870 | Novice |
| 11 | 2.52 | 1,406 | 11,276 | Apprentice |
| 12 | 2.67 | 1,547 | 12,823 | Apprentice |
| 13 | 2.82 | 1,687 | 14,510 | Apprentice |
| 14 | 2.97 | 1,828 | 16,338 | Apprentice |
| 15 | 3.12 | 1,968 | 18,306 | Apprentice |
| 16 | 3.27 | 2,109 | 20,415 | Apprentice |
| 17 | 3.42 | 2,250 | 22,665 | Apprentice |
| 18 | 3.58 | 2,390 | 25,055 | Apprentice |
| 19 | 3.73 | 2,531 | 27,586 | Apprentice |
| 20 | 3.88 | 2,672 | 30,258 | Apprentice |
| 21 | 4.03 | 2,840 | 33,098 | Journeyman |
| 22 | 4.18 | 3,121 | 36,219 | Journeyman |
| 23 | 4.33 | 3,403 | 39,622 | Journeyman |
| 24 | 4.48 | 3,684 | 43,306 | Journeyman |
| 25 | 4.64 | 3,965 | 47,271 | Journeyman |
| 26 | 4.79 | 4,246 | 51,517 | Journeyman |
| 27 | 4.94 | 4,528 | 56,045 | Journeyman |
| 28 | 5.09 | 4,893 | 60,938 | Journeyman |
| 29 | 5.24 | 5,315 | 66,253 | Journeyman |
| 30 | 5.39 | 5,737 | 71,990 | Journeyman |
| 31 | 5.55 | 6,159 | 78,149 | Veteran |
| 32 | 5.70 | 6,580 | 84,729 | Veteran |
| 33 | 5.85 | 7,002 | 91,731 | Veteran |
| 34 | 6.00 | 7,424 | 99,155 | Veteran |
| 35 | 6.15 | 8,127 | 107,282 | Veteran |
| 36 | 6.30 | 8,830 | 116,112 | Veteran |
| 37 | 6.45 | 9,533 | 125,645 | Veteran |
| 38 | 6.61 | 10,236 | 135,881 | Veteran |
| 39 | 6.76 | 10,939 | 146,820 | Veteran |
| 40 | 6.91 | 11,642 | 158,462 | Veteran |
| 41 | 7.06 | 12,514 | 170,976 | Expert |
| 42 | 7.21 | 13,639 | 184,615 | Expert |
| 43 | 7.36 | 14,764 | 199,379 | Expert |
| 44 | 7.52 | 15,888 | 215,267 | Expert |
| 45 | 7.67 | 17,013 | 232,280 | Expert |
| 46 | 7.82 | 18,138 | 250,418 | Expert |
| 47 | 7.97 | 19,263 | 269,681 | Expert |
| 48 | 8.12 | 20,950 | 290,631 | Expert |
| 49 | 8.27 | 22,778 | 313,409 | Expert |
| 50 | 8.42 | 24,606 | 338,015 | Expert |
| 51 | 8.58 | 26,434 | 364,449 | Master |
| 52 | 8.73 | 28,262 | 392,711 | Master |
| 53 | 8.88 | 30,090 | 422,801 | Master |
| 54 | 9.03 | 32,143 | 454,944 | Master |
| 55 | 9.18 | 35,095 | 490,039 | Master |
| 56 | 9.33 | 38,048 | 528,087 | Master |
| 57 | 9.48 | 41,001 | 569,088 | Master |
| 58 | 9.64 | 43,953 | 613,041 | Master |
| 59 | 9.79 | 46,906 | 659,947 | Master |
| 60 | 9.94 | 49,859 | 709,806 | Master |
| 61 | 10.09 | 53,908 | 763,714 | Champion |
| 62 | 10.24 | 58,689 | 822,403 | Champion |
| 63 | 10.39 | 63,470 | 885,873 | Champion |
| 64 | 10.55 | 68,250 | 954,123 | Champion |
| 65 | 10.70 | 73,031 | 1,027,154 | Champion |
| 66 | 10.85 | 77,811 | 1,104,965 | Champion |
| 67 | 11.00 | 82,592 | 1,187,557 | Champion |
| 68 | 11.15 | 90,325 | 1,277,882 | Champion |
| 69 | 11.30 | 98,059 | 1,375,941 | Champion |
| 70 | 11.45 | 105,792 | 1,481,733 | Champion |
| 71 | 11.61 | 113,525 | 1,595,258 | Legend |
| 72 | 11.76 | 121,259 | 1,716,517 | Legend |
| 73 | 11.91 | 128,992 | 1,845,509 | Legend |
| 74 | 12.06 | 138,638 | 1,984,147 | Legend |
| 75 | 12.21 | 151,152 | 2,135,299 | Legend |
| 76 | 12.36 | 163,665 | 2,298,964 | Legend |
| 77 | 12.52 | 176,179 | 2,475,143 | Legend |
| 78 | 12.67 | 188,693 | 2,663,836 | Legend |
| 79 | 12.82 | 201,207 | 2,865,043 | Legend |
| 80 | 12.97 | 213,721 | 3,078,764 | Legend |
| 81 | 13.12 | 232,422 | 3,311,186 | Mythic |
| 82 | 13.27 | 252,669 | 3,563,855 | Mythic |
| 83 | 13.42 | 272,916 | 3,836,771 | Mythic |
| 84 | 13.58 | 293,164 | 4,129,935 | Mythic |
| 85 | 13.73 | 313,411 | 4,443,346 | Mythic |
| 86 | 13.88 | 333,658 | 4,777,004 | Mythic |
| 87 | 14.03 | 356,408 | 5,133,412 | Mythic |
| 88 | 14.18 | 389,169 | 5,522,581 | Mythic |
| 89 | 14.33 | 421,931 | 5,944,512 | Mythic |
| 90 | 14.48 | 454,692 | 6,399,204 | Mythic |
| 91 | 14.64 | 487,453 | 6,886,657 | Transcendent |
| 92 | 14.79 | 520,214 | 7,406,871 | Transcendent |
| 93 | 14.94 | 552,976 | 7,959,847 | Transcendent |
| 94 | 15.09 | 597,885 | 8,557,732 | Transcendent |
| 95 | 15.24 | 650,894 | 9,208,626 | Transcendent |
| 96 | 15.39 | 703,902 | 9,912,528 | Transcendent |
| 97 | 15.55 | 756,911 | 10,669,439 | Transcendent |
| 98 | 15.70 | 809,919 | 11,479,358 | Transcendent |
| 99 | 15.85 | 862,928 | 12,342,286 | Transcendent |
| 100 | 16.00 | 915,936 | 13,258,222 | Transcendent |

### Tier System (every 10 levels)

| Tier | Levels | Name |
|------|--------|------|
| 1 | 1-10 | Novice |
| 2 | 11-20 | Apprentice |
| 3 | 21-30 | Journeyman |
| 4 | 31-40 | Veteran |
| 5 | 41-50 | Expert |
| 6 | 51-60 | Master |
| 7 | 61-70 | Champion |
| 8 | 71-80 | Legend |
| 9 | 81-90 | Mythic |
| 10 | 91-100 | Transcendent |

---

## 3. XP Rates Per Health Activity

| Activity | Rate | Unit | Rationale |
|----------|-----:|------|-----------|
| Steps | **10** | XP per 1,000 steps | 8,000 steps = 80 XP. Average person walks 6-10K/day |
| Active Energy | **25** | XP per 100 kcal | 350 kcal workout = 87.5 XP. Rewards intensity |
| Workout Duration | **15** | XP per 10 minutes | 45 min session = 67.5 XP. Rewards consistency |
| Distance | **10** | XP per km | 5 km run = 50 XP. Rewards cardio |
| Sleep | **10** | XP per hour (max 9h) | 8h sleep = 80 XP. Recovery matters in RPG |
| Flights Climbed | **5** | XP per flight | 5 flights = 25 XP. Small but consistent bonus |

### XP Formula from Health Data

```
daily_xp = (steps / 1000) * 10
         + (active_energy_kcal / 100) * 25
         + (workout_min / 10) * 15
         + distance_km * 10
         + min(sleep_hours, 9) * 10
         + flights * 5
```

### HealthDataType Enum Mapping

| HealthDataType | XP Calculation | Unit in DB |
|----------------|---------------|------------|
| `STEPS` | `value / 1000 * steps_per_1000` | count |
| `ACTIVE_ENERGY_BURNED` | `value / 100 * active_energy_per_100kcal` | kcal |
| `WORKOUT` | `value / 10 * workout_per_10min` | minutes |
| `DISTANCE_DELTA` | `(value / 1000) * distance_per_km` | meters (convert to km) |
| `SLEEP_ASLEEP` | `value / 60 * sleep_per_hour` | minutes (convert to hours) |
| `SLEEP_DEEP` | `value / 60 * sleep_per_hour` | minutes (convert to hours) |
| `SLEEP_LIGHT` | `value / 60 * sleep_per_hour` | minutes (convert to hours) |
| `SLEEP_REM` | `value / 60 * sleep_per_hour` | minutes (convert to hours) |
| `FLIGHTS_CLIMBED` | `value * flights_per_flight` | count |
| `HEART_RATE` | No direct XP (intensity multiplier) | bpm |
| `WEIGHT` | No direct XP (body tracking) | kg |
| `HEIGHT` | No direct XP (body tracking) | cm |
| `BODY_FAT_PERCENTAGE` | No direct XP (body tracking) | % |
| `BLOOD_OXYGEN` | No direct XP (health monitoring) | % |
| `WATER` | No direct XP (hydration, future bonus) | ml |

**Sleep XP note:** Sum all sleep types (ASLEEP + DEEP + LIGHT + REM) and cap total at 9 hours before calculating XP.

---

## 4. Daily XP Estimates

### Sedentary Office Worker (~165 XP/day)

| Source | Amount | XP |
|--------|-------:|---:|
| Steps | 4,000 | 40 |
| Active Energy | 100 kcal | 25 |
| Workout | 0 min | 0 |
| Distance | 2.5 km | 25 |
| Sleep | 6.5 hours | 65 |
| Flights | 2 | 10 |
| **Total** | | **165** |

### Casual Gym-Goer, 3x/week (~312 XP/day averaged)

| Source | Amount | XP |
|--------|-------:|---:|
| Steps | 8,000 | 80 |
| Active Energy | 250 kcal | 62.5 |
| Workout | 20 min (avg) | 30 |
| Distance | 4 km | 40 |
| Sleep | 7.5 hours | 75 |
| Flights | 5 | 25 |
| **Total** | | **312.5** |

### Regular Athlete, 5x/week (~520 XP/day averaged)

| Source | Amount | XP |
|--------|-------:|---:|
| Steps | 12,000 | 120 |
| Active Energy | 500 kcal | 125 |
| Workout | 50 min (avg) | 75 |
| Distance | 8 km | 80 |
| Sleep | 8 hours | 80 |
| Flights | 8 | 40 |
| **Total** | | **520** |

### Professional Athlete, daily (~905 XP/day)

| Source | Amount | XP |
|--------|-------:|---:|
| Steps | 18,000 | 180 |
| Active Energy | 1,000 kcal | 250 |
| Workout | 120 min | 180 |
| Distance | 15 km | 150 |
| Sleep | 8.5 hours | 85 |
| Flights | 12 | 60 |
| **Total** | | **905** |

---

## 5. Time to Reach Milestone Levels

### Fixed Daily XP (no progression)

| Level | Total XP | Sedentary (165/d) | Casual (312/d) | Athlete (520/d) | Pro (905/d) |
|------:|---------:|-------------------:|---------------:|----------------:|------------:|
| 10 | 9,870 | 2.0 months | 1.1 months | 19 days | 11 days |
| 20 | 30,258 | 6.1 months | 3.2 months | 1.9 months | 1.1 months |
| 30 | 71,990 | 1.2 years | 7.7 months | 4.6 months | 2.7 months |
| 50 | 338,015 | 5.6 years | 3.0 years | 1.8 years | 1.0 years |
| 70 | 1,481,733 | 24.6 years | 13.0 years | 7.8 years | 4.5 years |
| 90 | 6,399,204 | 106.3 years | 56.1 years | 33.7 years | 19.4 years |
| 100 | 13,258,222 | 220.1 years | 116.2 years | 69.9 years | 40.1 years |

### Realistic Progressive Timeline (user improves over time)

This models someone who starts as a beginner and progressively trains harder:

| Phase | Levels | Daily XP |
|-------|--------|----------|
| Starting out | 1-20 | ~170 XP/day |
| Getting into routine | 21-40 | ~350 XP/day |
| Serious athlete | 41-60 | ~600 XP/day |
| Dedicated training | 61-80 | ~1,000 XP/day |
| Elite performance | 81-100 | ~1,400 XP/day |

| Level | XP to Next | Total XP | Time to Reach |
|------:|-----------:|---------:|--------------:|
| 10 | 1,265 | 9,870 | 1.9 months |
| 20 | 2,672 | 30,258 | 5.9 months |
| 30 | 5,737 | 71,990 | 9.9 months |
| 40 | 11,642 | 158,462 | 1.5 years |
| 50 | 24,606 | 338,015 | 2.3 years |
| 60 | 49,859 | 709,806 | 4.0 years |
| 70 | 105,792 | 1,481,733 | 6.1 years |
| 80 | 213,721 | 3,078,764 | 10.5 years |
| 90 | 454,692 | 6,399,204 | 17.0 years |
| 100 | 915,936 | 13,258,222 | 30.4 years |

**Key milestones achieved:**
- Level ~30 = regular gym-goer (10 months of consistent training)
- Level ~50 = serious athlete (2.3 years)
- Level ~70 = professional athlete level (6.1 years)
- Level ~90 = Olympic champion level (17 years)
- Level 100 = absolute peak (30.4 years -- nearly unreachable)

---

## 6. Daily XP Caps (Anti-Cheat / Balance)

| Cap | Value | Rationale |
|-----|------:|-----------|
| Max steps/day | 25,000 | Ultra-marathoners might do 50K+, cap prevents abuse |
| Max active energy/day | 3,000 kcal | Even Ironman doesn't exceed this meaningfully |
| Max workout time/day | 240 min (4h) | Professional athletes rarely train more |
| Max distance/day | 100 km | Ultra-marathon territory |
| Max sleep for XP | 9 hours | Beyond 9h is not more beneficial |
| Max flights/day | 50 | Extreme stair climbing |
| **Hard daily XP cap** | **3,000** | Absolute maximum XP per day regardless of activity |

---

## 7. Streak Bonus Multipliers

| Streak | Multiplier | Effect |
|--------|----------:|--------|
| 3 consecutive days | 1.10x | +10% XP (encourages forming habit) |
| 7 consecutive days | 1.25x | +25% XP (weekly consistency) |
| 30 consecutive days | 1.50x | +50% XP (monthly dedication) |

Additional bonuses:
- **Weekend Warrior:** 1.15x for Saturday/Sunday workouts
- **Early Bird:** 1.10x for workouts completed before 7 AM
- **Night Owl:** 1.10x for workouts completed after 9 PM

---

## 8. Settings Table Design

### Doctrine Entity: `GameSetting`

```php
#[ORM\Entity(repositoryClass: GameSettingRepository::class)]
#[ORM\Table(name: 'game_settings')]
class GameSetting
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: 'string', length: 255)]
    private string $value;

    #[ORM\Column(type: 'string', length: 20)]
    private string $valueType = 'string'; // string, integer, float, boolean, json

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category = 'general'; // xp_rates, xp_caps, leveling, bonuses

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;
}
```

### SQL Schema

```sql
CREATE TABLE game_settings (
    id          UUID PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    value       VARCHAR(255) NOT NULL,
    value_type  VARCHAR(20)  NOT NULL DEFAULT 'string',
    description VARCHAR(500) NULL,
    category    VARCHAR(50)  NOT NULL DEFAULT 'general',
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by  VARCHAR(180) NULL
);

CREATE INDEX idx_game_settings_category ON game_settings(category);
```

### Initial Settings Data

#### Category: `xp_rates`
| Key | Value | Type | Description |
|-----|------:|------|-------------|
| `xp.rate.steps_per_1000` | 10 | integer | XP per 1,000 steps |
| `xp.rate.active_energy_per_100kcal` | 25 | integer | XP per 100 kcal burned |
| `xp.rate.workout_per_10min` | 15 | integer | XP per 10 min workout |
| `xp.rate.distance_per_km` | 10 | integer | XP per km distance |
| `xp.rate.sleep_per_hour` | 10 | integer | XP per hour sleep |
| `xp.rate.flights_per_flight` | 5 | integer | XP per flight climbed |

#### Category: `xp_caps`
| Key | Value | Type | Description |
|-----|------:|------|-------------|
| `xp.cap.steps_daily_max` | 25000 | integer | Max steps for XP/day |
| `xp.cap.active_energy_daily_max_kcal` | 3000 | integer | Max kcal for XP/day |
| `xp.cap.workout_daily_max_min` | 240 | integer | Max workout min for XP/day |
| `xp.cap.distance_daily_max_km` | 100 | integer | Max km for XP/day |
| `xp.cap.sleep_max_hours` | 9 | integer | Max sleep hours for XP |
| `xp.cap.flights_daily_max` | 50 | integer | Max flights for XP/day |
| `xp.cap.total_daily_max` | 3000 | integer | Hard daily XP cap |

#### Category: `leveling`
| Key | Value | Type | Description |
|-----|------:|------|-------------|
| `level.fibonacci_max_index` | 16 | integer | Max Fibonacci index (level 100 maps here) |
| `level.rate_multiplier` | 928 | integer | Fibonacci multiplier for XP |
| `level.max_level` | 100 | integer | Maximum level |
| `level.min_xp_per_level` | 100 | integer | Minimum XP per level |

#### Category: `bonuses`
| Key | Value | Type | Description |
|-----|------:|------|-------------|
| `xp.bonus.streak_3day` | 1.1 | float | 3-day streak multiplier |
| `xp.bonus.streak_7day` | 1.25 | float | 7-day streak multiplier |
| `xp.bonus.streak_30day` | 1.5 | float | 30-day streak multiplier |
| `xp.bonus.weekend_warrior` | 1.15 | float | Weekend workout bonus |
| `xp.bonus.early_bird` | 1.1 | float | Before 7 AM bonus |
| `xp.bonus.night_owl` | 1.1 | float | After 9 PM bonus |

---

## 9. Implementation Notes

### Level Calculation Service (pseudo-code)

```php
class LevelCalculationService
{
    private array $fibCache = [];

    public function calculateLevel(int $totalXp): int
    {
        $cumulativeXp = 0;
        for ($level = 1; $level <= $this->getMaxLevel(); $level++) {
            $cumulativeXp += $this->xpForNextLevel($level);
            if ($totalXp < $cumulativeXp) {
                return $level - 1;
            }
        }
        return $this->getMaxLevel();
    }

    public function xpForNextLevel(int $level): int
    {
        $fibIndex = 1.0 + ($level - 1) * ($this->getFibMaxIndex() - 1) / 99.0;
        $fibValue = $this->interpolateFib($fibIndex);
        return max($this->getMinXpPerLevel(), (int) round($this->getRateMultiplier() * $fibValue));
    }

    private function interpolateFib(float $x): float
    {
        $lo = max(1, (int) floor($x));
        $hi = $lo + 1;
        $frac = $x - $lo;
        return $this->fib($lo) * (1 - $frac) + $this->fib($hi) * $frac;
    }

    private function fib(int $n): int
    {
        if (isset($this->fibCache[$n])) return $this->fibCache[$n];
        if ($n <= 2) return $this->fibCache[$n] = 1;
        return $this->fibCache[$n] = $this->fib($n - 1) + $this->fib($n - 2);
    }
}
```

### XP Calculation from Health Data (pseudo-code)

```php
class XpCalculationService
{
    public function calculateDailyXp(array $healthSummary): int
    {
        $xp = 0;
        $xp += min($healthSummary['steps'], $this->getCap('steps_daily_max')) / 1000 * $this->getRate('steps_per_1000');
        $xp += min($healthSummary['active_energy_kcal'], $this->getCap('active_energy_daily_max_kcal')) / 100 * $this->getRate('active_energy_per_100kcal');
        $xp += min($healthSummary['workout_min'], $this->getCap('workout_daily_max_min')) / 10 * $this->getRate('workout_per_10min');
        $xp += min($healthSummary['distance_km'], $this->getCap('distance_daily_max_km')) * $this->getRate('distance_per_km');
        $xp += min($healthSummary['sleep_hours'], $this->getCap('sleep_max_hours')) * $this->getRate('sleep_per_hour');
        $xp += min($healthSummary['flights'], $this->getCap('flights_daily_max')) * $this->getRate('flights_per_flight');

        // Apply streak bonus
        $xp *= $this->getStreakMultiplier($healthSummary['streak_days']);

        // Apply daily hard cap
        return min((int) round($xp), $this->getCap('total_daily_max'));
    }
}
```
