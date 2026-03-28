# RPG Skill System Design

> Complete skill definitions, profession-to-skill mappings, and balance calculations.
>
> Stats: **STR** (Strength), **DEX** (Dexterity), **CON** (Constitution)

---

## 1. Race Passive Skills (5 skills)

Race passives are always active. No duration, no cooldown. One per race.

| # | Skill Name | Slug | Race | STR | DEX | CON | Total | Description |
|---|-----------|------|------|-----|-----|-----|-------|-------------|
| 1 | Versatile Nature | `versatile-nature` | Human | +2 | +2 | +2 | 6 | Humans adapt to any challenge. A balanced bonus to all attributes reflects their jack-of-all-trades heritage. |
| 2 | Blood of the Horde | `blood-of-the-horde` | Orc | +4 | 0 | +1 | 5 | Orcs are born with savage power coursing through their veins. Brute strength is their birthright. |
| 3 | Mountain Born | `mountain-born` | Dwarf | +2 | 0 | +3 | 5 | Dwarves are carved from the stone of the deep mountains — sturdy, unbreakable, and impossible to move. |
| 4 | Shadow Instinct | `shadow-instinct` | Dark Elf | +1 | +4 | 0 | 5 | Dark Elves move with lethal quickness honed in the lightless depths of the underworld. |
| 5 | Sylvan Grace | `sylvan-grace` | Light Elf | 0 | +3 | +2 | 5 | Light Elves carry the flowing grace of the ancient forests — nimble and enduring as the wind through the trees. |

---

## 2. Universal Active Skills (2 skills)

Available to all players regardless of race or profession.

| # | Skill Name | Slug | Type | STR | DEX | CON | Duration | Cooldown | Description |
|---|-----------|------|------|-----|-----|-----|----------|----------|-------------|
| 1 | Second Wind | `second-wind` | Active | 0 | 0 | +3 | 60 min | 60 min | A burst of renewed energy that temporarily boosts your endurance. Reliable and always ready when you need a push. |
| 2 | Battle Fury | `battle-fury` | Active | +5 | +3 | 0 | 30 min | 240 min | Channel your inner warrior for a devastating burst of power and speed. Use wisely — the fury takes time to rekindle. |

---

## 3. Shared Skill Pool — Tier 1 Profession Skills

Each Tier 1 profession gets 3 skills: 2 active + 1 passive.

### Tier 1 Passives

| # | Skill Name | Slug | STR | DEX | CON | Total | Description |
|---|-----------|------|-----|-----|-----|-------|-------------|
| P1 | Iron Skin | `iron-skin` | 0 | 0 | +2 | 2 | Hardened through endurance training. Permanently tougher than the average warrior. |
| P2 | Sharp Reflexes | `sharp-reflexes` | 0 | +2 | 0 | 2 | Finely tuned reaction speed. Your body moves before your mind catches up. |
| P3 | Raw Power | `raw-power` | +2 | 0 | 0 | 2 | Baseline strength that exceeds the untrained. Every muscle fiber is primed for force. |

### Tier 1 Actives

| # | Skill Name | Slug | STR | DEX | CON | Total | Duration | Cooldown | Description |
|---|-----------|------|-----|-----|-----|-------|----------|----------|-------------|
| A1 | Power Strike | `power-strike` | +4 | 0 | 0 | 4 | 45 min | 90 min | Focus your strength into a concentrated surge of raw force. |
| A2 | Quick Step | `quick-step` | 0 | +4 | 0 | 4 | 45 min | 90 min | Accelerate your reflexes and footwork to a blur of precise movement. |
| A3 | Endurance Boost | `endurance-boost` | 0 | 0 | +4 | 4 | 45 min | 90 min | Dig deep into your reserves for a sustained wave of stamina. |
| A4 | Focused Mind | `focused-mind` | +2 | +2 | 0 | 4 | 30 min | 120 min | Clear your thoughts and sharpen both power and precision in equal measure. |
| A5 | Steady Heart | `steady-heart` | 0 | +2 | +2 | 4 | 30 min | 120 min | Calm your breathing and synchronize your endurance with your agility. |
| A6 | Brute Force | `brute-force` | +3 | 0 | +1 | 4 | 45 min | 90 min | Overwhelm obstacles with a raw, unrefined surge of might and grit. |

---

## 4. Shared Skill Pool — Tier 2 Profession Skills

Each Tier 2 profession gets 4 skills: 3 active + 1 passive.

### Tier 2 Passives

| # | Skill Name | Slug | STR | DEX | CON | Total | Description |
|---|-----------|------|-----|-----|-----|-------|-------------|
| P4 | Hardened Body | `hardened-body` | +1 | 0 | +3 | 4 | Your body has been tempered by countless trials. Endurance runs deep in your bones. |
| P5 | Lightning Nerves | `lightning-nerves` | 0 | +3 | +1 | 4 | Your nervous system fires faster than most can comprehend. Speed is second nature. |
| P6 | Titan's Grip | `titans-grip` | +3 | 0 | +1 | 4 | Your grip strength and raw force have grown beyond ordinary limits. |
| P7 | Predator's Instinct | `predators-instinct` | +1 | +2 | +1 | 4 | A balanced sharpening of combat instincts — stronger, faster, tougher. |

### Tier 2 Actives

| # | Skill Name | Slug | STR | DEX | CON | Total | Duration | Cooldown | Description |
|---|-----------|------|-----|-----|-----|-------|----------|----------|-------------|
| A7 | Berserker Rage | `berserker-rage` | +7 | 0 | 0 | 7 | 30 min | 180 min | Unleash a primal fury that turns your muscles into instruments of destruction. |
| A8 | Shadow Step | `shadow-step` | 0 | +7 | 0 | 7 | 30 min | 180 min | Move with supernatural quickness, leaving only an afterimage in your wake. |
| A9 | Iron Will | `iron-will` | 0 | 0 | +7 | 7 | 30 min | 180 min | Steel your body against all fatigue. Endurance beyond mortal limits. |
| A10 | War Cry | `war-cry` | +4 | 0 | +3 | 7 | 45 min | 120 min | A thunderous shout that surges power and resilience through your body. |
| A11 | Wind Walk | `wind-walk` | 0 | +4 | +3 | 7 | 45 min | 120 min | Move with the lightness of air and the stamina of the eternal wind. |
| A12 | Stone Shield | `stone-shield` | +3 | 0 | +4 | 7 | 45 min | 120 min | Harden your body like living rock — unyielding force meets unbreakable defense. |
| A13 | Precision Strike | `precision-strike` | +2 | +5 | 0 | 7 | 30 min | 150 min | Channel pinpoint accuracy and explosive speed into every movement. |
| A14 | Adrenaline Rush | `adrenaline-rush` | +3 | +3 | +2 | 8 | 20 min | 240 min | A chemical surge that temporarily elevates every physical attribute at once. |

---

## 5. Shared Skill Pool — Tier 3 Profession Skills

Each Tier 3 profession gets 3 skills: 2 ultimate active + 1 passive.

### Tier 3 Passives

| # | Skill Name | Slug | STR | DEX | CON | Total | Description |
|---|-----------|------|-----|-----|-----|-------|-------------|
| P8 | Unbreakable Spirit | `unbreakable-spirit` | 0 | +2 | +5 | 7 | Your willpower and body have merged into an engine of limitless endurance. |
| P9 | Phantom Grace | `phantom-grace` | 0 | +5 | +2 | 7 | You move with the fluidity of a specter — untouchable, inexorable, perfect. |
| P10 | Titan's Legacy | `titans-legacy` | +5 | 0 | +2 | 7 | The strength of ancient titans flows through your bloodline. Raw power incarnate. |
| P11 | Perfect Balance | `perfect-balance` | +3 | +3 | +2 | 8 | Absolute equilibrium across all attributes — a rare state achieved only by true masters. |

### Tier 3 Actives (Ultimates)

| # | Skill Name | Slug | STR | DEX | CON | Total | Duration | Cooldown | Description |
|---|-----------|------|-----|-----|-----|-------|----------|----------|-------------|
| A15 | Wrath of the Titans | `wrath-of-the-titans` | +10 | 0 | +5 | 15 | 30 min | 360 min | Invoke the devastating power of the ancient titans. Your strength becomes the stuff of legend. |
| A16 | Wind God's Blessing | `wind-gods-blessing` | 0 | +10 | +5 | 15 | 30 min | 360 min | The wind god grants you supernatural speed and the endurance to sustain it. |
| A17 | Eternal Fortitude | `eternal-fortitude` | +5 | 0 | +10 | 15 | 30 min | 360 min | Your body becomes an immovable fortress, shrugging off exhaustion as if it were nothing. |
| A18 | Avatar of War | `avatar-of-war` | +7 | +7 | 0 | 14 | 20 min | 480 min | Become a living weapon — an avatar of pure martial devastation. |
| A19 | Avatar of the Storm | `avatar-of-the-storm` | 0 | +7 | +7 | 14 | 20 min | 480 min | Channel the storm's fury — lightning speed and the endurance of a hurricane. |
| A20 | Avatar of the Mountain | `avatar-of-the-mountain` | +7 | 0 | +7 | 14 | 20 min | 480 min | Become an immovable titan — the raw power and resilience of the mountain itself. |
| A21 | Transcendence | `transcendence` | +5 | +5 | +5 | 15 | 15 min | 480 min | Briefly transcend mortal limits. Every attribute surges to superhuman levels. |

---

## 6. Complete Profession-to-Skill Mapping

### Legend

- **Primary / Secondary** = the profession's primary and secondary stats
- **T1 skills** = 1 passive + 2 actives (unlocked with Tier 1 profession)
- **T2 skills** = 1 passive + 3 actives (unlocked with Tier 2 profession)
- **T3 skills** = 1 passive + 2 ultimate actives (unlocked with Tier 3 profession)
- Skill IDs reference the tables above (P1-P11 passives, A1-A21 actives)

---

### 1. Combat (STR / CON)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Fighter | Raw Power (P3) | Power Strike (A1) | Brute Force (A6) | — |
| T2 | Gladiator | Titan's Grip (P6) | Berserker Rage (A7) | War Cry (A10) | Adrenaline Rush (A14) |
| T3 | Titan Breaker | Titan's Legacy (P10) | Wrath of the Titans (A15) | Avatar of War (A18) | — |

### 2. Running (DEX / CON)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Rogue | Sharp Reflexes (P2) | Quick Step (A2) | Steady Heart (A5) | — |
| T2 | Pathfinder | Lightning Nerves (P5) | Shadow Step (A8) | Wind Walk (A11) | Adrenaline Rush (A14) |
| T3 | Wind Rider | Phantom Grace (P9) | Wind God's Blessing (A16) | Avatar of the Storm (A19) | — |

### 3. Walking (CON / DEX)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Wanderer | Iron Skin (P1) | Endurance Boost (A3) | Steady Heart (A5) | — |
| T2 | Pilgrim | Hardened Body (P4) | Iron Will (A9) | Wind Walk (A11) | Stone Shield (A12) |
| T3 | Eternal Strider | Unbreakable Spirit (P8) | Eternal Fortitude (A17) | Avatar of the Storm (A19) | — |

### 4. Cycling (CON / STR)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Rider | Iron Skin (P1) | Endurance Boost (A3) | Brute Force (A6) | — |
| T2 | Dark Rider | Hardened Body (P4) | Iron Will (A9) | War Cry (A10) | Stone Shield (A12) |
| T3 | Iron Cavalier | Titan's Legacy (P10) | Wrath of the Titans (A15) | Avatar of the Mountain (A20) | — |

### 5. Swimming (CON / STR)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Tide Warden | Iron Skin (P1) | Endurance Boost (A3) | Brute Force (A6) | — |
| T2 | Depth Walker | Hardened Body (P4) | Iron Will (A9) | Stone Shield (A12) | Adrenaline Rush (A14) |
| T3 | Abyssal Lord | Unbreakable Spirit (P8) | Eternal Fortitude (A17) | Avatar of the Mountain (A20) | — |

### 6. Strength (STR / CON)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Brawler | Raw Power (P3) | Power Strike (A1) | Brute Force (A6) | — |
| T2 | Destroyer | Titan's Grip (P6) | Berserker Rage (A7) | War Cry (A10) | Stone Shield (A12) |
| T3 | Tyrant | Titan's Legacy (P10) | Wrath of the Titans (A15) | Avatar of the Mountain (A20) | — |

### 7. Flexibility (DEX / CON)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Monk | Sharp Reflexes (P2) | Quick Step (A2) | Focused Mind (A4) | — |
| T2 | Bladedancer | Lightning Nerves (P5) | Shadow Step (A8) | Wind Walk (A11) | Precision Strike (A13) |
| T3 | Phantom Dancer | Phantom Grace (P9) | Wind God's Blessing (A16) | Transcendence (A21) | — |

### 8. Cardio (CON / DEX)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Scout | Iron Skin (P1) | Endurance Boost (A3) | Steady Heart (A5) | — |
| T2 | Storm Chaser | Hardened Body (P4) | Iron Will (A9) | Wind Walk (A11) | Adrenaline Rush (A14) |
| T3 | Tempest Warden | Unbreakable Spirit (P8) | Eternal Fortitude (A17) | Avatar of the Storm (A19) | — |

### 9. Dance (DEX / CON)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Minstrel | Sharp Reflexes (P2) | Quick Step (A2) | Steady Heart (A5) | — |
| T2 | Swordsinger | Lightning Nerves (P5) | Shadow Step (A8) | Wind Walk (A11) | Precision Strike (A13) |
| T3 | Celestial Bard | Phantom Grace (P9) | Wind God's Blessing (A16) | Transcendence (A21) | — |

### 10. Winter Sports (DEX / STR)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Frost Scout | Sharp Reflexes (P2) | Quick Step (A2) | Focused Mind (A4) | — |
| T2 | Ice Warden | Lightning Nerves (P5) | Shadow Step (A8) | Precision Strike (A13) | Adrenaline Rush (A14) |
| T3 | Boreal Sovereign | Phantom Grace (P9) | Wind God's Blessing (A16) | Avatar of War (A18) | — |

### 11. Racquet Sports (DEX / STR)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Duelist | Sharp Reflexes (P2) | Quick Step (A2) | Focused Mind (A4) | — |
| T2 | Treasure Hunter | Lightning Nerves (P5) | Shadow Step (A8) | Precision Strike (A13) | Adrenaline Rush (A14) |
| T3 | Phantom Striker | Phantom Grace (P9) | Wind God's Blessing (A16) | Avatar of War (A18) | — |

### 12. Team Sports (STR / DEX)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Squire | Raw Power (P3) | Power Strike (A1) | Focused Mind (A4) | — |
| T2 | Warlord | Predator's Instinct (P7) | Berserker Rage (A7) | War Cry (A10) | Adrenaline Rush (A14) |
| T3 | Grand Marshal | Perfect Balance (P11) | Wrath of the Titans (A15) | Avatar of War (A18) | — |

### 13. Water Sports (STR / CON)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Deckhand | Raw Power (P3) | Power Strike (A1) | Brute Force (A6) | — |
| T2 | Sea Raider | Titan's Grip (P6) | Berserker Rage (A7) | Stone Shield (A12) | Adrenaline Rush (A14) |
| T3 | Storm Sovereign | Titan's Legacy (P10) | Wrath of the Titans (A15) | Avatar of the Mountain (A20) | — |

### 14. Outdoor (DEX / STR)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Ranger | Sharp Reflexes (P2) | Quick Step (A2) | Focused Mind (A4) | — |
| T2 | Hawkeye | Lightning Nerves (P5) | Shadow Step (A8) | Precision Strike (A13) | War Cry (A10) |
| T3 | Silver Ranger | Phantom Grace (P9) | Wind God's Blessing (A16) | Avatar of War (A18) | — |

### 15. Mind & Body (CON / DEX)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Mystic | Iron Skin (P1) | Endurance Boost (A3) | Steady Heart (A5) | — |
| T2 | Prophet | Hardened Body (P4) | Iron Will (A9) | Wind Walk (A11) | Stone Shield (A12) |
| T3 | Archmage | Unbreakable Spirit (P8) | Eternal Fortitude (A17) | Transcendence (A21) | — |

### 16. Other (CON / STR)

| Tier | Profession | Passive | Active 1 | Active 2 | Active 3 |
|------|-----------|---------|----------|----------|----------|
| T1 | Adventurer | Iron Skin (P1) | Endurance Boost (A3) | Brute Force (A6) | — |
| T2 | Warsmith | Predator's Instinct (P7) | Iron Will (A9) | War Cry (A10) | Adrenaline Rush (A14) |
| T3 | Chaos Vanguard | Perfect Balance (P11) | Eternal Fortitude (A17) | Transcendence (A21) | — |

---

## 7. Complete Skill Definitions Reference

All 28 unique skills in a single flat list.

### Passives (11 skills)

| # | Slug | Name | Source | STR | DEX | CON | Total |
|---|------|------|--------|-----|-----|-----|-------|
| 1 | `versatile-nature` | Versatile Nature | Race: Human | +2 | +2 | +2 | 6 |
| 2 | `blood-of-the-horde` | Blood of the Horde | Race: Orc | +4 | 0 | +1 | 5 |
| 3 | `mountain-born` | Mountain Born | Race: Dwarf | +2 | 0 | +3 | 5 |
| 4 | `shadow-instinct` | Shadow Instinct | Race: Dark Elf | +1 | +4 | 0 | 5 |
| 5 | `sylvan-grace` | Sylvan Grace | Race: Light Elf | 0 | +3 | +2 | 5 |
| 6 | `iron-skin` | Iron Skin | Prof T1 | 0 | 0 | +2 | 2 |
| 7 | `sharp-reflexes` | Sharp Reflexes | Prof T1 | 0 | +2 | 0 | 2 |
| 8 | `raw-power` | Raw Power | Prof T1 | +2 | 0 | 0 | 2 |
| 9 | `hardened-body` | Hardened Body | Prof T2 | +1 | 0 | +3 | 4 |
| 10 | `lightning-nerves` | Lightning Nerves | Prof T2 | 0 | +3 | +1 | 4 |
| 11 | `titans-grip` | Titan's Grip | Prof T2 | +3 | 0 | +1 | 4 |
| 12 | `predators-instinct` | Predator's Instinct | Prof T2 | +1 | +2 | +1 | 4 |
| 13 | `unbreakable-spirit` | Unbreakable Spirit | Prof T3 | 0 | +2 | +5 | 7 |
| 14 | `phantom-grace` | Phantom Grace | Prof T3 | 0 | +5 | +2 | 7 |
| 15 | `titans-legacy` | Titan's Legacy | Prof T3 | +5 | 0 | +2 | 7 |
| 16 | `perfect-balance` | Perfect Balance | Prof T3 | +3 | +3 | +2 | 8 |

### Actives (21 skills)

| # | Slug | Name | Source | STR | DEX | CON | Total | Duration | Cooldown |
|---|------|------|--------|-----|-----|-----|-------|----------|----------|
| 1 | `second-wind` | Second Wind | Universal | 0 | 0 | +3 | 3 | 60 min | 60 min |
| 2 | `battle-fury` | Battle Fury | Universal | +5 | +3 | 0 | 8 | 30 min | 240 min |
| 3 | `power-strike` | Power Strike | Prof T1 | +4 | 0 | 0 | 4 | 45 min | 90 min |
| 4 | `quick-step` | Quick Step | Prof T1 | 0 | +4 | 0 | 4 | 45 min | 90 min |
| 5 | `endurance-boost` | Endurance Boost | Prof T1 | 0 | 0 | +4 | 4 | 45 min | 90 min |
| 6 | `focused-mind` | Focused Mind | Prof T1 | +2 | +2 | 0 | 4 | 30 min | 120 min |
| 7 | `steady-heart` | Steady Heart | Prof T1 | 0 | +2 | +2 | 4 | 30 min | 120 min |
| 8 | `brute-force` | Brute Force | Prof T1 | +3 | 0 | +1 | 4 | 45 min | 90 min |
| 9 | `berserker-rage` | Berserker Rage | Prof T2 | +7 | 0 | 0 | 7 | 30 min | 180 min |
| 10 | `shadow-step` | Shadow Step | Prof T2 | 0 | +7 | 0 | 7 | 30 min | 180 min |
| 11 | `iron-will` | Iron Will | Prof T2 | 0 | 0 | +7 | 7 | 30 min | 180 min |
| 12 | `war-cry` | War Cry | Prof T2 | +4 | 0 | +3 | 7 | 45 min | 120 min |
| 13 | `wind-walk` | Wind Walk | Prof T2 | 0 | +4 | +3 | 7 | 45 min | 120 min |
| 14 | `stone-shield` | Stone Shield | Prof T2 | +3 | 0 | +4 | 7 | 45 min | 120 min |
| 15 | `precision-strike` | Precision Strike | Prof T2 | +2 | +5 | 0 | 7 | 30 min | 150 min |
| 16 | `adrenaline-rush` | Adrenaline Rush | Prof T2 | +3 | +3 | +2 | 8 | 20 min | 240 min |
| 17 | `wrath-of-the-titans` | Wrath of the Titans | Prof T3 | +10 | 0 | +5 | 15 | 30 min | 360 min |
| 18 | `wind-gods-blessing` | Wind God's Blessing | Prof T3 | 0 | +10 | +5 | 15 | 30 min | 360 min |
| 19 | `eternal-fortitude` | Eternal Fortitude | Prof T3 | +5 | 0 | +10 | 15 | 30 min | 360 min |
| 20 | `avatar-of-war` | Avatar of War | Prof T3 | +7 | +7 | 0 | 14 | 20 min | 480 min |
| 21 | `avatar-of-the-storm` | Avatar of the Storm | Prof T3 | 0 | +7 | +7 | 14 | 20 min | 480 min |
| 22 | `avatar-of-the-mountain` | Avatar of the Mountain | Prof T3 | +7 | 0 | +7 | 14 | 20 min | 480 min |
| 23 | `transcendence` | Transcendence | Prof T3 | +5 | +5 | +5 | 15 | 15 min | 480 min |

**Total unique skills: 16 passives + 23 actives = 39 skills**

---

## 8. Profession Skill Assignment Summary Table

Quick-reference table showing skill IDs for all 48 professions.

| # | Category | Tier | Profession | Passive | Actives |
|---|----------|------|-----------|---------|---------|
| 1 | Combat | T1 | Fighter | P3 | A1, A6 |
| 2 | Combat | T2 | Gladiator | P6 | A7, A10, A14 |
| 3 | Combat | T3 | Titan Breaker | P10 | A15, A18 |
| 4 | Running | T1 | Rogue | P2 | A2, A5 |
| 5 | Running | T2 | Pathfinder | P5 | A8, A11, A14 |
| 6 | Running | T3 | Wind Rider | P9 | A16, A19 |
| 7 | Walking | T1 | Wanderer | P1 | A3, A5 |
| 8 | Walking | T2 | Pilgrim | P4 | A9, A11, A12 |
| 9 | Walking | T3 | Eternal Strider | P8 | A17, A19 |
| 10 | Cycling | T1 | Rider | P1 | A3, A6 |
| 11 | Cycling | T2 | Dark Rider | P4 | A9, A10, A12 |
| 12 | Cycling | T3 | Iron Cavalier | P10 | A15, A20 |
| 13 | Swimming | T1 | Tide Warden | P1 | A3, A6 |
| 14 | Swimming | T2 | Depth Walker | P4 | A9, A12, A14 |
| 15 | Swimming | T3 | Abyssal Lord | P8 | A17, A20 |
| 16 | Strength | T1 | Brawler | P3 | A1, A6 |
| 17 | Strength | T2 | Destroyer | P6 | A7, A10, A12 |
| 18 | Strength | T3 | Tyrant | P10 | A15, A20 |
| 19 | Flexibility | T1 | Monk | P2 | A2, A4 |
| 20 | Flexibility | T2 | Bladedancer | P5 | A8, A11, A13 |
| 21 | Flexibility | T3 | Phantom Dancer | P9 | A16, A21 |
| 22 | Cardio | T1 | Scout | P1 | A3, A5 |
| 23 | Cardio | T2 | Storm Chaser | P4 | A9, A11, A14 |
| 24 | Cardio | T3 | Tempest Warden | P8 | A17, A19 |
| 25 | Dance | T1 | Minstrel | P2 | A2, A5 |
| 26 | Dance | T2 | Swordsinger | P5 | A8, A11, A13 |
| 27 | Dance | T3 | Celestial Bard | P9 | A16, A21 |
| 28 | Winter Sports | T1 | Frost Scout | P2 | A2, A4 |
| 29 | Winter Sports | T2 | Ice Warden | P5 | A8, A13, A14 |
| 30 | Winter Sports | T3 | Boreal Sovereign | P9 | A16, A18 |
| 31 | Racquet Sports | T1 | Duelist | P2 | A2, A4 |
| 32 | Racquet Sports | T2 | Treasure Hunter | P5 | A8, A13, A14 |
| 33 | Racquet Sports | T3 | Phantom Striker | P9 | A16, A18 |
| 34 | Team Sports | T1 | Squire | P3 | A1, A4 |
| 35 | Team Sports | T2 | Warlord | P7 | A7, A10, A14 |
| 36 | Team Sports | T3 | Grand Marshal | P11 | A15, A18 |
| 37 | Water Sports | T1 | Deckhand | P3 | A1, A6 |
| 38 | Water Sports | T2 | Sea Raider | P6 | A7, A12, A14 |
| 39 | Water Sports | T3 | Storm Sovereign | P10 | A15, A20 |
| 40 | Outdoor | T1 | Ranger | P2 | A2, A4 |
| 41 | Outdoor | T2 | Hawkeye | P5 | A8, A13, A10 |
| 42 | Outdoor | T3 | Silver Ranger | P9 | A16, A18 |
| 43 | Mind & Body | T1 | Mystic | P1 | A3, A5 |
| 44 | Mind & Body | T2 | Prophet | P4 | A9, A11, A12 |
| 45 | Mind & Body | T3 | Archmage | P8 | A17, A21 |
| 46 | Other | T1 | Adventurer | P1 | A3, A6 |
| 47 | Other | T2 | Warsmith | P7 | A9, A10, A14 |
| 48 | Other | T3 | Chaos Vanguard | P11 | A17, A21 |

---

## 9. Balance Analysis

### Stat Budget by Progression Stage

Example build: **Orc + Combat (Fighter -> Gladiator -> Titan Breaker)**

#### Permanent Bonuses (Passives, always active)

| Source | STR | DEX | CON | Total |
|--------|-----|-----|-----|-------|
| Race: Blood of the Horde | +4 | 0 | +1 | 5 |
| T1: Raw Power | +2 | 0 | 0 | 2 |
| T2: Titan's Grip | +3 | 0 | +1 | 4 |
| T3: Titan's Legacy | +5 | 0 | +2 | 7 |
| **Total Permanent** | **+14** | **0** | **+4** | **18** |

#### Theoretical Maximum (all actives + universals simultaneously)

| Source | STR | DEX | CON | Total |
|--------|-----|-----|-----|-------|
| All passives (above) | +14 | 0 | +4 | 18 |
| Universal: Second Wind | 0 | 0 | +3 | 3 |
| Universal: Battle Fury | +5 | +3 | 0 | 8 |
| T1: Power Strike | +4 | 0 | 0 | 4 |
| T1: Brute Force | +3 | 0 | +1 | 4 |
| T2: Berserker Rage | +7 | 0 | 0 | 7 |
| T2: War Cry | +4 | 0 | +3 | 7 |
| T2: Adrenaline Rush | +3 | +3 | +2 | 8 |
| T3: Wrath of the Titans | +10 | 0 | +5 | 15 |
| T3: Avatar of War | +7 | +7 | 0 | 14 |
| **Theoretical Max** | **+57** | **+13** | **+18** | **88** |

> This is impossible in practice due to overlapping cooldowns and short durations.

#### Realistic Combat Scenario (passives + 1-2 best actives)

| Source | STR | DEX | CON | Total |
|--------|-----|-----|-----|-------|
| All passives | +14 | 0 | +4 | 18 |
| Best active: Wrath of the Titans | +10 | 0 | +5 | 15 |
| **Realistic Peak** | **+24** | **0** | **+9** | **33** |

#### Casual Play (passives + Second Wind)

| Source | STR | DEX | CON | Total |
|--------|-----|-----|-----|-------|
| All passives | +14 | 0 | +4 | 18 |
| Second Wind | 0 | 0 | +3 | 3 |
| **Casual Total** | **+14** | **0** | **+7** | **21** |

---

### Balance Against Base Stats

| Level | Base Stats (approx) | Passive Bonus | Passive as % of Base | Realistic Peak Bonus | Peak as % of Base |
|-------|--------------------:|:--------------|:---------------------|:---------------------|:------------------|
| 1 | ~30 | +18 | 60% | +33 | 110% |
| 10 | ~40 | +18 | 45% | +33 | 83% |
| 30 | ~55 | +18 | 33% | +33 | 60% |
| 50 | ~70 | +18 | 26% | +33 | 47% |
| 70 | ~90 | +18 | 20% | +33 | 37% |

**Conclusion:** Skills are dominant early game (encouraging progression), meaningful mid-game, and still relevant but not overwhelming at max level. This is intentional — at level 70, base stats matter most but skills give a meaningful edge.

---

### Cross-Build Comparison (all at max tier)

| Build | Permanent STR | Permanent DEX | Permanent CON | Total Passive |
|-------|:-------------|:-------------|:-------------|:-------------|
| Orc + Strength (Tyrant) | +14 | 0 | +4 | 18 |
| Dark Elf + Running (Wind Rider) | +1 | +14 | +4 | 19 |
| Dwarf + Walking (Eternal Strider) | +2 | +2 | +13 | 17 |
| Human + Team Sports (Grand Marshal) | +8 | +8 | +6 | 22 |
| Light Elf + Dance (Celestial Bard) | 0 | +13 | +6 | 19 |
| Orc + Flexibility (Phantom Dancer) | +4 | +10 | +3 | 17 |

**Range of total passive bonuses: 17-22 points.** This is a tight band, confirming balance. Human + hybrid professions trend slightly higher in total due to the balanced distribution nature of both, but specialist builds get higher peaks in single stats.

---

### Skill Count Verification

| Tier | Skills per Profession | Breakdown | Total slots across 48 professions |
|------|----------------------|-----------|-----------------------------------|
| T1 | 3 | 1 passive + 2 active | 16 x 3 = 48 |
| T2 | 4 | 1 passive + 3 active | 16 x 4 = 64 |
| T3 | 3 | 1 passive + 2 active | 16 x 3 = 48 |
| Universal | 2 | 2 active | 2 |
| Race | 1 | 1 passive | 5 |
| **Totals** | | | 167 assignments |

**Unique skills: 39** (16 passives + 23 actives), reused across 167 assignment slots.

**Per-player skill slots at max progression:**
- 1 race passive
- 2 universal actives
- 3 T1 skills (1 passive + 2 active)
- 4 T2 skills (1 passive + 3 active)
- 3 T3 skills (1 passive + 2 active)
- **Total: 13 skills per player** (4 passives always active + 9 actives to manage)

---

## 10. Design Notes

### Shared Skill Groupings

Professions naturally cluster by their stat emphasis:

**STR-primary group** (Combat, Strength, Team Sports, Water Sports):
- T1: Raw Power + Power Strike + Brute Force or Focused Mind
- T2: Titan's Grip or Predator's Instinct + Berserker Rage + War Cry
- T3: Titan's Legacy or Perfect Balance + Wrath of the Titans + Avatar of War/Mountain

**DEX-primary group** (Running, Flexibility, Dance, Winter Sports, Racquet Sports, Outdoor):
- T1: Sharp Reflexes + Quick Step + Steady Heart or Focused Mind
- T2: Lightning Nerves + Shadow Step + Wind Walk or Precision Strike
- T3: Phantom Grace + Wind God's Blessing + Avatar of War/Storm or Transcendence

**CON-primary group** (Walking, Cycling, Swimming, Cardio, Mind & Body, Other):
- T1: Iron Skin + Endurance Boost + Steady Heart or Brute Force
- T2: Hardened Body or Predator's Instinct + Iron Will + Stone Shield or Wind Walk
- T3: Unbreakable Spirit or Perfect Balance + Eternal Fortitude + Avatar of Storm/Mountain or Transcendence

### Differentiation Within Groups

Even professions sharing the same stat emphasis are differentiated by:
1. **Which actives** they receive (e.g., Combat gets War Cry; Strength gets Stone Shield at T2)
2. **Which T3 ultimate pair** they receive (Avatar of War vs Avatar of the Mountain)
3. **Their lore and profession identity** (same skill, different narrative context)

### Cooldown Philosophy

| Tier | Typical Cooldown | Rationale |
|------|-----------------|-----------|
| Universal (Second Wind) | 60 min | Always available, low-impact |
| Universal (Battle Fury) | 240 min | Powerful, use once per long session |
| T1 actives | 90-120 min | Bread-and-butter, usable 1-2x per session |
| T2 actives | 120-240 min | Significant buffs, plan your usage |
| T3 ultimates | 360-480 min | Once per day power spikes |
