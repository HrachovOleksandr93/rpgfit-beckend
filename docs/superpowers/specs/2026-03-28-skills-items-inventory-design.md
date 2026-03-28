# Skills, Items & Inventory System Design

## Overview

Add RPG skill system, item catalog (equipment + consumables), and user inventory to the backend. This enables character progression beyond base stats — skills provide passive bonuses, equipment provides stat boosts while equipped, and consumables (scrolls/potions) provide temporary time-limited buffs.

## New Entities

### 1. Skill (admin-managed catalog)

RPG skills that a character can learn/unlock. Each skill provides stat bonuses.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| name | string(100) | Display name (e.g., "Heavy Lifting", "Sprint Mastery") |
| slug | string(100), unique | Code-friendly identifier |
| description | text, nullable | What the skill does |
| icon | string(255), nullable | Path/URL to skill icon |
| requiredLevel | int, default 1 | Minimum character level to unlock |

Relationship: 1:N → SkillStatBonus

### 2. SkillStatBonus (admin-managed, stat rewards per skill)

Defines how many stat points a skill gives when active.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| skill | Skill (ManyToOne) | FK |
| statType | StatType enum (str/con/dex) | Which stat |
| points | int | Bonus points |

Same pattern as ExerciseStatReward but for skills.

### 3. ItemCatalog (admin-managed, base item definitions)

Master catalog of all items in the game. Single table with `itemType` discriminator.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| name | string(100) | Display name |
| slug | string(100), unique | Code identifier |
| description | text, nullable | Item description |
| itemType | Enum(equipment, scroll, potion) | Discriminator |
| rarity | Enum(common, uncommon, rare, epic, legendary) | Item rarity |
| icon | string(255), nullable | Path/URL to icon |
| slot | Enum(head, body, legs, feet, hands, weapon, shield, accessory), nullable | Equipment slot (null for consumables) |
| durability | int, nullable | Max durability for equipment (null for consumables) |
| duration | int, nullable | Effect duration in minutes for consumables (60=1h, 1440=1d, null for equipment) |
| stackable | bool, default false | Whether multiple can stack in inventory |
| maxStack | int, default 1 | Max stack size if stackable |

Relationship: 1:N → ItemStatBonus

### 4. ItemStatBonus (admin-managed, stat bonuses per item)

Defines stat bonuses an item provides when equipped/active.

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| item | ItemCatalog (ManyToOne) | FK |
| statType | StatType enum (str/con/dex) | Which stat |
| points | int | Bonus points |

### 5. ItemRarity Enum

String-backed:
- common
- uncommon
- rare
- epic
- legendary

### 6. ItemType Enum

String-backed:
- equipment
- scroll
- potion

### 7. EquipmentSlot Enum

String-backed:
- head
- body
- legs
- feet
- hands
- weapon
- shield
- accessory

### 8. UserInventory (user's bag — items they own)

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| user | User (ManyToOne) | FK |
| item | ItemCatalog (ManyToOne) | FK |
| quantity | int, default 1 | Stack count |
| equipped | bool, default false | Is equipment currently worn |
| currentDurability | int, nullable | Current durability (for equipment) |
| obtainedAt | DateTimeImmutable | When item was obtained |
| expiresAt | DateTimeImmutable, nullable | When consumable effect expires (null = permanent/equipment) |
| deletedAt | DateTimeImmutable, nullable | Soft delete timestamp |

Indexes:
- (user_id, item_id) — for lookups
- (user_id, equipped) — for finding equipped items
- (user_id, deleted_at) — for filtering active items

### 9. UserSkill (user's learned skills)

| Field | Type | Notes |
|-------|------|-------|
| id | UUID | PK |
| user | User (ManyToOne) | FK |
| skill | Skill (ManyToOne) | FK |
| unlockedAt | DateTimeImmutable | When skill was learned |

Unique constraint: (user_id, skill_id)

## Relationships Diagram

```
Skill 1──N SkillStatBonus
ItemCatalog 1──N ItemStatBonus
User 1──N UserInventory N──1 ItemCatalog
User 1──N UserSkill N──1 Skill
```

## Sonata Admin

Full CRUD for:
- **SkillAdmin** — manage skills with inline stat bonuses
- **SkillStatBonusAdmin** — manage skill stat configs
- **ItemCatalogAdmin** — manage items with type/rarity/slot filters, inline stat bonuses
- **ItemStatBonusAdmin** — manage item stat configs
- **UserInventoryAdmin** — view/manage user items, filter by user/equipped/deleted
- **UserSkillAdmin** — view/manage user skills

## Scope

**In scope:**
- All entities, enums, relationships, migrations
- Repositories with soft-delete filtering for UserInventory
- Sonata Admin CRUD for all entities
- Unit tests for all entities

**Out of scope (future):**
- Equip/unequip API endpoints
- Consumable activation logic
- Item drop/reward logic
- Skill unlock conditions
- Stat calculation including item/skill bonuses
