# Battle Session Flow Design

## Overview

The core gameplay loop: User selects activity → gets workout plan → chooses battle mode → fights a mob through exercise → submits results → earns XP and loot.

## Flow

```
1. User selects activity type (running, strength, yoga...)
2. App requests plan from server → POST /api/workout/generate
3. Server returns plan based on history + when last trained
4. User chooses battle mode:
   - Custom Hunting: pick own exercises, regular mob
   - Recommended Hunting: follow plan, regular mob
   - Raid: follow plan (harder), rare mob with +30% HP/XP
5. Server creates WorkoutSession (active, mode flag)
6. App requests mob → GET /api/battle/mob
   - Custom/Recommended: mob near user level
   - Raid: rarer mob, +30% HP, +30% XP
7. If recommended/raid with exercises: warm-up prompt
   - "I already warmed up" → skip
   - "Start warm-up" → 10min timer → done
8. Battle begins! Motivational button: "Begin Battle!" / "For Glory!" / "To Arms!"
9. Exercise execution:
   - Recommended/Raid: show plan exercises in order, one at a time
     - Input: weight, reps → "Finish Set" / "Finish Exercise"
     - After plan done → switch to custom mode (add more if wanted)
   - Custom: show exercise list grouped by muscle, with search
     - User picks exercise → input weight, reps → log sets
   - Raid: same as recommended but label "push harder to close the raid"
10. "End Battle" button always visible at bottom
11. On end: submit all logged exercises + health API data to server
12. Server calculates XP, mob damage, rewards
```

## Backend API

### POST /api/battle/start
Start a battle session after plan generation.
```json
Request: {
  "workoutPlanId": "uuid",
  "mode": "recommended" | "custom" | "raid"
}
Response 200: {
  "sessionId": "uuid",
  "mode": "recommended",
  "plan": { ...full plan with exercises... },
  "mob": { ...mob data with adjusted HP/XP for raid... },
  "startedAt": "ISO8601"
}
```

### GET /api/battle/mob?mode=recommended|raid
Get a mob for the battle based on user level.
- Custom/Recommended: random mob near user level (±2), common-rare rarity
- Raid: random mob near user level, rare-epic rarity, HP×1.3, XP×1.3
```json
Response 200: {
  "id": "uuid",
  "name": "Fierce Wolf",
  "level": 12,
  "hp": 850,        // base or +30% for raid
  "xpReward": 65,   // base or +30% for raid
  "rarity": "rare",
  "image": "/uploads/mobs/..."
}
```

### POST /api/battle/complete
Submit battle results.
```json
Request: {
  "sessionId": "uuid",
  "exercises": [
    {
      "exerciseSlug": "barbell-bench-press",
      "sets": [
        {"setNumber": 1, "reps": 10, "weight": 80.0},
        {"setNumber": 2, "reps": 8, "weight": 85.0}
      ]
    }
  ],
  "healthData": {
    "duration": 3600,      // seconds
    "calories": 350.0,
    "distance": null,      // meters, for cardio
    "averageHeartRate": 145
  }
}
Response 200: {
  "xpAwarded": 235,
  "mobDefeated": true,
  "damageDealt": 850,
  "rewardTier": "silver",
  "levelUp": false,
  "newLevel": 12,
  "totalXp": 15420
}
```

### New Entity: WorkoutSession

Tracks an active battle session.
- UUID id
- user (ManyToOne User)
- workoutPlan (ManyToOne WorkoutPlan)
- mob (ManyToOne Mob, nullable)
- mode: enum (custom, recommended, raid)
- mobHp (int) — actual HP for this session (may be +30% for raid)
- mobXpReward (int) — actual XP reward (may be +30%)
- startedAt (DateTimeImmutable)
- completedAt (DateTimeImmutable, nullable)
- totalDamageDealt (int, default 0)
- xpAwarded (int, default 0)
- status: enum (active, completed, abandoned)
- Table: workout_sessions
