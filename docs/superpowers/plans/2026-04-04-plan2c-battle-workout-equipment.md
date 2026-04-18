# Plan 2C: Battle, Workouts, Equipment & Inventory

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the complete game loop: workout plans, battle system with real-time damage, equipment/inventory management, and mobs display. The battle screen is the centerpiece — Start Battle → pick type → pick mode → real-time mob damage from health data → results.

**Architecture:** Feature modules: `workout/`, `battle/`, `equipment/`, `mobs/`. Battle screen manages workout state and health polling. All API calls via TanStack Query hooks. Equipment/inventory as list screens with equip/unequip actions.

**Tech Stack:** Expo Router, TanStack Query, Axios, React Native Paper, Jest + RNTL

**Business Logic (from spec):**

Battle damage formula per set:
- IF reps > 0 AND weight > 0: damage = reps × weight × 0.1
- ELSE IF reps > 0: damage = reps (bodyweight)
- IF duration > 0: damage += duration × 0.5
- RETURN round(damage)

Mob selection: user level ±2, rarities by mode (custom/recommended: common/uncommon/rare, raid: rare/epic/legendary)
Raid multiplier: 1.3× (HP and XP)

Performance tiers: failed (<50%), survived (50-75%), completed (75-100%), exceeded (>100%), raid_exceeded (>100% raid)

Battle flow:
1. Start Battle → pick workout type → pick mode
2. POST /api/workout/generate → plan with exercises
3. POST /api/battle/start → session with mob
4. Active battle: log sets, track damage, health data feeds in
5. POST /api/battle/complete → results (XP, tier, loot)

Equipment slots: weapon, shield, head, body, legs, feet, hands, bracers, bracelet, ring, shirt, necklace (12)
Slot rules: 1 per slot, rings/bracelets max 2, two-handed removes shield

---

### Task 1: Workout Types and API

**Files:**
- Create: `src/features/workout/types/models.ts`
- Create: `src/features/workout/types/responses.ts`
- Create: `src/features/workout/api/workoutApi.ts`
- Test: `src/features/workout/__tests__/workoutApi.test.ts`

- [ ] **Step 1: Create workout types**

Create dirs: `mkdir -p src/features/workout/types src/features/workout/api src/features/workout/hooks src/features/workout/__tests__`

Create `src/features/workout/types/models.ts`:

```typescript
export interface Exercise {
  id: string;
  name: string;
  slug: string;
  primaryMuscle: string;
  secondaryMuscles: string[];
  equipment: string;
  difficulty: string;
  movementType: string;
  description?: string;
}

export interface WorkoutPlanExercise {
  id: string;
  exercise: Exercise;
  orderIndex: number;
  sets: number;
  repsMin: number;
  repsMax: number;
  restSeconds: number;
  notes?: string;
}

export interface WorkoutPlan {
  id: string;
  name: string;
  status: 'pending' | 'in_progress' | 'completed' | 'skipped';
  plannedAt: string | null;
  startedAt: string | null;
  completedAt: string | null;
  activityType: string | null;
  targetMuscleGroups: string[];
  exercises: WorkoutPlanExercise[];
  rewardTiers: {
    bronze?: { threshold: number; xp: number };
    silver?: { threshold: number; xp: number };
    gold?: { threshold: number; xp: number };
  } | null;
  difficultyModifier: number;
}
```

Create `src/features/workout/types/responses.ts`:

```typescript
export interface WorkoutPlanResponse {
  id: string;
  name: string;
  status: string;
  planned_at: string | null;
  started_at: string | null;
  completed_at: string | null;
  activity_type: string | null;
  target_muscle_groups: string[];
  exercises: {
    id: string;
    exercise: {
      id: string;
      name: string;
      slug: string;
      primary_muscle: string;
      secondary_muscles: string[];
      equipment: string;
      difficulty: string;
      movement_type: string;
      description?: string;
    };
    order_index: number;
    sets: number;
    reps_min: number;
    reps_max: number;
    rest_seconds: number;
    notes?: string;
  }[];
  reward_tiers: Record<string, { threshold: number; xp: number }> | null;
  difficulty_modifier: number;
}

export interface ExerciseResponse {
  id: string;
  name: string;
  slug: string;
  primary_muscle: string;
  secondary_muscles: string[];
  equipment: string;
  difficulty: string;
  movement_type: string;
  description?: string;
}
```

- [ ] **Step 2: Write the failing test**

Create `src/features/workout/__tests__/workoutApi.test.ts`:

```typescript
import { workoutApi } from '../api/workoutApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { post: jest.fn(), get: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('workoutApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('generates workout plan via POST /api/workout/generate', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { id: 'plan-1', name: 'Push Day', status: 'pending' } });
    const result = await workoutApi.generate({ activity_category: 'strength' });
    expect(apiClient.post).toHaveBeenCalledWith('/api/workout/generate', { activity_category: 'strength' });
    expect(result.id).toBe('plan-1');
  });

  it('lists plans via GET /api/workout/plans', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({ data: [{ id: 'plan-1', status: 'pending' }] });
    const result = await workoutApi.listPlans({ status: 'pending' });
    expect(apiClient.get).toHaveBeenCalledWith('/api/workout/plans', { params: { status: 'pending' } });
    expect(result).toHaveLength(1);
  });

  it('starts plan via POST /api/workout/plans/:id/start', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { id: 'plan-1', status: 'in_progress' } });
    const result = await workoutApi.startPlan('plan-1');
    expect(apiClient.post).toHaveBeenCalledWith('/api/workout/plans/plan-1/start');
    expect(result.status).toBe('in_progress');
  });

  it('completes plan via POST /api/workout/plans/:id/complete', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { id: 'plan-1', status: 'completed' } });
    const result = await workoutApi.completePlan('plan-1');
    expect(apiClient.post).toHaveBeenCalledWith('/api/workout/plans/plan-1/complete');
    expect(result.status).toBe('completed');
  });

  it('logs exercise set via POST', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { success: true } });
    await workoutApi.logSet('plan-1', 'ex-1', { setNumber: 1, reps: 10, weight: 60 });
    expect(apiClient.post).toHaveBeenCalledWith(
      '/api/workout/plans/plan-1/exercises/ex-1/log',
      { setNumber: 1, reps: 10, weight: 60 }
    );
  });

  it('fetches exercises via GET /api/exercises', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({ data: [{ id: 'ex-1', name: 'Bench Press' }] });
    const result = await workoutApi.getExercises({});
    expect(apiClient.get).toHaveBeenCalledWith('/api/exercises', { params: {} });
    expect(result[0].name).toBe('Bench Press');
  });
});
```

- [ ] **Step 3: Implement workoutApi**

Create `src/features/workout/api/workoutApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';
import type { WorkoutPlanResponse, ExerciseResponse } from '../types/responses';

export const workoutApi = {
  async generate(params: { activity_category?: string; target_date?: string }): Promise<WorkoutPlanResponse> {
    const response = await apiClient.post<WorkoutPlanResponse>('/api/workout/generate', params);
    return response.data;
  },
  async listPlans(params: { status?: string; limit?: number; offset?: number }): Promise<WorkoutPlanResponse[]> {
    const response = await apiClient.get<WorkoutPlanResponse[]>('/api/workout/plans', { params });
    return response.data;
  },
  async getPlan(id: string): Promise<WorkoutPlanResponse> {
    const response = await apiClient.get<WorkoutPlanResponse>(`/api/workout/plans/${id}`);
    return response.data;
  },
  async startPlan(id: string): Promise<WorkoutPlanResponse> {
    const response = await apiClient.post<WorkoutPlanResponse>(`/api/workout/plans/${id}/start`);
    return response.data;
  },
  async completePlan(id: string): Promise<WorkoutPlanResponse> {
    const response = await apiClient.post<WorkoutPlanResponse>(`/api/workout/plans/${id}/complete`);
    return response.data;
  },
  async skipPlan(id: string): Promise<WorkoutPlanResponse> {
    const response = await apiClient.post<WorkoutPlanResponse>(`/api/workout/plans/${id}/skip`);
    return response.data;
  },
  async logSet(planId: string, exerciseId: string, data: { setNumber: number; reps?: number; weight?: number; duration?: number; notes?: string }): Promise<void> {
    await apiClient.post(`/api/workout/plans/${planId}/exercises/${exerciseId}/log`, data);
  },
  async getExercises(params: { activityCategory?: string; muscleGroup?: string; search?: string; difficulty?: string }): Promise<ExerciseResponse[]> {
    const response = await apiClient.get<ExerciseResponse[]>('/api/exercises', { params });
    return response.data;
  },
};
```

- [ ] **Step 4: Run test — 6 tests PASS**

---

### Task 2: Battle Types and API

**Files:**
- Create: `src/features/battle/types/models.ts`
- Create: `src/features/battle/types/requests.ts`
- Create: `src/features/battle/types/responses.ts`
- Create: `src/features/battle/api/battleApi.ts`
- Test: `src/features/battle/__tests__/battleApi.test.ts`

- [ ] **Step 1: Create battle types**

Create dirs: `mkdir -p src/features/battle/types src/features/battle/api src/features/battle/hooks src/features/battle/__tests__`

Create `src/features/battle/types/models.ts`:

```typescript
export type BattleMode = 'custom' | 'recommended' | 'raid';
export type SessionStatus = 'active' | 'completed' | 'abandoned';
export type PerformanceTier = 'failed' | 'survived' | 'completed' | 'exceeded' | 'raid_exceeded';

export interface Mob {
  id: string;
  name: string;
  slug: string;
  level: number;
  hp: number;
  xpReward: number;
  rarity: string;
  description?: string;
}

export interface BattleSession {
  sessionId: string;
  mob: Mob;
  mobHp: number;
  mobXpReward: number;
  mode: BattleMode;
  status: SessionStatus;
  totalDamageDealt: number;
  startedAt: string;
}
```

Create `src/features/battle/types/requests.ts`:

```typescript
import type { BattleMode } from './models';

export interface StartBattleRequest {
  workoutPlanId: string;
  mode: BattleMode;
}

export interface CompleteBattleRequest {
  sessionId: string;
  exercises: {
    exerciseId: string;
    sets: { setNumber: number; reps?: number; weight?: number; duration?: number }[];
  }[];
  healthData?: Record<string, unknown>;
  usedSkills?: string[];
  usedConsumables?: string[];
}
```

Create `src/features/battle/types/responses.ts`:

```typescript
import type { PerformanceTier } from './models';

export interface StartBattleResponse {
  session_id: string;
  mob: {
    id: string; name: string; slug: string; level: number;
    hp: number; xp_reward: number; rarity: string; description?: string;
  };
  mob_hp: number;
  mob_xp_reward: number;
  mode: string;
  started_at: string;
}

export interface ActiveBattleResponse {
  session_id: string;
  mob: {
    id: string; name: string; slug: string; level: number;
    hp: number; xp_reward: number; rarity: string;
  };
  mob_hp: number;
  mob_xp_reward: number;
  mode: string;
  status: string;
  total_damage_dealt: number;
  started_at: string;
}

export interface CompleteBattleResponse {
  performance_tier: PerformanceTier;
  completion_percent: number;
  mobs_defeated: number;
  total_damage: number;
  xp_from_mobs: number;
  bonus_xp_percent: number;
  xp_awarded: number;
  loot_earned: boolean;
  super_loot_earned: boolean;
  level_up: boolean;
  new_level: number;
  total_xp: number;
  message: string;
}
```

- [ ] **Step 2: Write the failing test**

Create `src/features/battle/__tests__/battleApi.test.ts`:

```typescript
import { battleApi } from '../api/battleApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { post: jest.fn(), get: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('battleApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('starts battle via POST /api/battle/start', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({
      data: { session_id: 's-1', mob: { name: 'Grey Wolf' }, mob_hp: 100, mode: 'recommended' },
    });
    const result = await battleApi.start({ workoutPlanId: 'plan-1', mode: 'recommended' });
    expect(apiClient.post).toHaveBeenCalledWith('/api/battle/start', { workoutPlanId: 'plan-1', mode: 'recommended' });
    expect(result.session_id).toBe('s-1');
  });

  it('gets active battle via GET /api/battle/active', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({
      data: { session_id: 's-1', status: 'active', total_damage_dealt: 50 },
    });
    const result = await battleApi.getActive();
    expect(apiClient.get).toHaveBeenCalledWith('/api/battle/active');
    expect(result.total_damage_dealt).toBe(50);
  });

  it('completes battle via POST /api/battle/complete', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({
      data: { performance_tier: 'completed', xp_awarded: 250, mobs_defeated: 1, level_up: false },
    });
    const result = await battleApi.complete({
      sessionId: 's-1', exercises: [], healthData: {}, usedSkills: [], usedConsumables: [],
    });
    expect(apiClient.post).toHaveBeenCalledWith('/api/battle/complete', expect.objectContaining({ sessionId: 's-1' }));
    expect(result.xp_awarded).toBe(250);
  });

  it('abandons battle via POST /api/battle/abandon', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { status: 'abandoned' } });
    await battleApi.abandon();
    expect(apiClient.post).toHaveBeenCalledWith('/api/battle/abandon');
  });
});
```

- [ ] **Step 3: Implement battleApi**

Create `src/features/battle/api/battleApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';
import type { StartBattleRequest, CompleteBattleRequest } from '../types/requests';
import type { StartBattleResponse, ActiveBattleResponse, CompleteBattleResponse } from '../types/responses';

export const battleApi = {
  async start(data: StartBattleRequest): Promise<StartBattleResponse> {
    const response = await apiClient.post<StartBattleResponse>('/api/battle/start', data);
    return response.data;
  },
  async getActive(): Promise<ActiveBattleResponse> {
    const response = await apiClient.get<ActiveBattleResponse>('/api/battle/active');
    return response.data;
  },
  async complete(data: CompleteBattleRequest): Promise<CompleteBattleResponse> {
    const response = await apiClient.post<CompleteBattleResponse>('/api/battle/complete', data);
    return response.data;
  },
  async abandon(): Promise<void> {
    await apiClient.post('/api/battle/abandon');
  },
  async nextMob(): Promise<StartBattleResponse> {
    const response = await apiClient.post<StartBattleResponse>('/api/battle/next-mob');
    return response.data;
  },
};
```

- [ ] **Step 4: Run test — 4 tests PASS**

---

### Task 3: Equipment & Inventory Types and API

**Files:**
- Create: `src/features/equipment/types/models.ts`
- Create: `src/features/equipment/api/equipmentApi.ts`
- Test: `src/features/equipment/__tests__/equipmentApi.test.ts`

- [ ] **Step 1: Create types**

Create dirs: `mkdir -p src/features/equipment/types src/features/equipment/api src/features/equipment/hooks src/features/equipment/__tests__`

Create `src/features/equipment/types/models.ts`:

```typescript
export type EquipmentSlot = 'weapon' | 'shield' | 'head' | 'body' | 'legs' | 'feet' | 'hands' | 'bracers' | 'bracelet' | 'ring' | 'shirt' | 'necklace';
export type ItemType = 'equipment' | 'scroll' | 'potion';
export type ItemRarity = 'common' | 'uncommon' | 'rare' | 'epic' | 'legendary';

export interface InventoryItem {
  id: string;
  name: string;
  slug: string;
  description?: string;
  itemType: ItemType;
  rarity: ItemRarity;
  slot?: EquipmentSlot;
  quantity: number;
  equipped: boolean;
  equippedSlot?: EquipmentSlot;
  statBonuses: { statType: string; amount: number }[];
  currentDurability?: number;
  maxDurability?: number;
}

export interface EquippedItem {
  inventoryId: string;
  name: string;
  slot: EquipmentSlot;
  rarity: ItemRarity;
  statBonuses: { statType: string; amount: number }[];
}
```

- [ ] **Step 2: Write the failing test**

Create `src/features/equipment/__tests__/equipmentApi.test.ts`:

```typescript
import { equipmentApi } from '../api/equipmentApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { post: jest.fn(), get: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('equipmentApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('lists equipped items via GET /api/equipment', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({
      data: [{ inventory_id: 'inv-1', name: 'Iron Sword', slot: 'weapon' }],
    });
    const result = await equipmentApi.listEquipped();
    expect(apiClient.get).toHaveBeenCalledWith('/api/equipment');
    expect(result[0].name).toBe('Iron Sword');
  });

  it('equips item via POST /api/equipment/equip/:id', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { inventory_id: 'inv-1', slot: 'weapon' } });
    const result = await equipmentApi.equip('inv-1');
    expect(apiClient.post).toHaveBeenCalledWith('/api/equipment/equip/inv-1');
    expect(result.slot).toBe('weapon');
  });

  it('unequips item via POST /api/equipment/unequip/:id', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { success: true } });
    await equipmentApi.unequip('inv-1');
    expect(apiClient.post).toHaveBeenCalledWith('/api/equipment/unequip/inv-1');
  });
});
```

- [ ] **Step 3: Implement equipmentApi**

Create `src/features/equipment/api/equipmentApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';

export const equipmentApi = {
  async listEquipped(): Promise<any[]> {
    const response = await apiClient.get('/api/equipment');
    return response.data;
  },
  async equip(inventoryId: string): Promise<any> {
    const response = await apiClient.post(`/api/equipment/equip/${inventoryId}`);
    return response.data;
  },
  async unequip(inventoryId: string): Promise<void> {
    await apiClient.post(`/api/equipment/unequip/${inventoryId}`);
  },
};
```

- [ ] **Step 4: Run test — 3 tests PASS**

---

### Task 4: Mobs API

**Files:**
- Create: `src/features/mobs/api/mobsApi.ts`
- Test: `src/features/mobs/__tests__/mobsApi.test.ts`

- [ ] **Step 1: Create dirs and test**

Create dirs: `mkdir -p src/features/mobs/api src/features/mobs/__tests__`

Create `src/features/mobs/__tests__/mobsApi.test.ts`:

```typescript
import { mobsApi } from '../api/mobsApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { get: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('mobsApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('lists mobs via GET /api/mobs', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({
      data: [{ id: 'm-1', name: 'Grey Wolf', level: 3, rarity: 'common' }],
    });
    const result = await mobsApi.list({ level: 3 });
    expect(apiClient.get).toHaveBeenCalledWith('/api/mobs', { params: { level: 3 } });
    expect(result[0].name).toBe('Grey Wolf');
  });

  it('gets mob by slug via GET /api/mobs/:slug', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({
      data: { id: 'm-1', name: 'Grey Wolf', slug: 'grey-wolf', level: 3 },
    });
    const result = await mobsApi.getBySlug('grey-wolf');
    expect(apiClient.get).toHaveBeenCalledWith('/api/mobs/grey-wolf');
    expect(result.slug).toBe('grey-wolf');
  });
});
```

- [ ] **Step 2: Implement mobsApi**

Create `src/features/mobs/api/mobsApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';

export const mobsApi = {
  async list(params: { level?: number; level_min?: number; level_max?: number; rarity?: string; limit?: number; offset?: number }): Promise<any[]> {
    const response = await apiClient.get('/api/mobs', { params });
    return response.data;
  },
  async getBySlug(slug: string): Promise<any> {
    const response = await apiClient.get(`/api/mobs/${slug}`);
    return response.data;
  },
};
```

- [ ] **Step 3: Run test — 2 tests PASS**

---

### Task 5: Battle Screen (Start → Type → Mode → Active → Results)

**Files:**
- Create: `app/(main)/battle/index.tsx`
- Create: `app/(main)/battle/start.tsx`
- Test: `src/features/battle/__tests__/BattleScreen.test.tsx`

- [ ] **Step 1: Create battle route directory**

```bash
mkdir -p app/\(main\)/battle
```

- [ ] **Step 2: Write the failing test**

Create `src/features/battle/__tests__/BattleScreen.test.tsx`:

```typescript
import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import BattleStartScreen from '../../../../app/(main)/battle/start';
import { lightTheme } from '../../../shared/theme';

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ push: jest.fn(), back: jest.fn(), replace: jest.fn() }),
}));
jest.mock('../../workout/api/workoutApi');
jest.mock('../api/battleApi');

function renderWithProviders(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={lightTheme}>{ui}</PaperProvider>
    </QueryClientProvider>
  );
}

describe('BattleStartScreen', () => {
  it('renders workout type selection', () => {
    const { getByText } = renderWithProviders(<BattleStartScreen />);
    expect(getByText('Choose Workout Type')).toBeTruthy();
    expect(getByText('Strength')).toBeTruthy();
    expect(getByText('Cardio')).toBeTruthy();
  });

  it('renders battle mode selection after choosing type', async () => {
    const { getByText } = renderWithProviders(<BattleStartScreen />);
    fireEvent.press(getByText('Strength'));
    await waitFor(() => {
      expect(getByText('Choose Battle Mode')).toBeTruthy();
      expect(getByText('Recommended')).toBeTruthy();
      expect(getByText('Raid')).toBeTruthy();
    });
  });
});
```

- [ ] **Step 3: Implement battle start screen**

Create `app/(main)/battle/start.tsx`:

```typescript
import React, { useState } from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Text, Button, Card, Chip, Snackbar, ActivityIndicator } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { workoutApi } from '../../../src/features/workout/api/workoutApi';
import { battleApi } from '../../../src/features/battle/api/battleApi';

const WORKOUT_TYPES = [
  { key: 'strength', label: 'Strength' },
  { key: 'cardio', label: 'Cardio' },
  { key: 'crossfit', label: 'CrossFit' },
  { key: 'gymnastics', label: 'Gymnastics' },
  { key: 'martial_arts', label: 'Martial Arts' },
  { key: 'yoga', label: 'Yoga' },
];

const BATTLE_MODES = [
  { key: 'custom', label: 'Custom', description: 'Free choice of exercises' },
  { key: 'recommended', label: 'Recommended', description: 'Plan-based exercises' },
  { key: 'raid', label: 'Raid', description: '+30% difficulty, rare+ mobs, more XP' },
];

type Step = 'type' | 'mode' | 'loading';

export default function BattleStartScreen() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [step, setStep] = useState<Step>('type');
  const [workoutType, setWorkoutType] = useState('');
  const [snackbar, setSnackbar] = useState('');

  const startBattle = useMutation({
    mutationFn: async (mode: string) => {
      const plan = await workoutApi.generate({ activity_category: workoutType });
      const session = await battleApi.start({ workoutPlanId: plan.id, mode: mode as any });
      return session;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['activeBattle'] });
      router.replace('/(main)/battle');
    },
    onError: (e) => { setSnackbar(e.message || 'Failed to start battle'); },
  });

  function handleSelectType(type: string) {
    setWorkoutType(type);
    setStep('mode');
  }

  function handleSelectMode(mode: string) {
    setStep('loading');
    startBattle.mutate(mode);
  }

  if (step === 'loading' || startBattle.isPending) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" />
        <Text variant="bodyLarge" style={{ marginTop: 16 }}>Preparing battle...</Text>
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      {step === 'type' && (
        <>
          <Text variant="headlineMedium" style={styles.title}>Choose Workout Type</Text>
          <View style={styles.grid}>
            {WORKOUT_TYPES.map((t) => (
              <Card key={t.key} style={styles.typeCard} onPress={() => handleSelectType(t.key)}>
                <Card.Content style={styles.typeContent}>
                  <Text variant="titleMedium">{t.label}</Text>
                </Card.Content>
              </Card>
            ))}
          </View>
        </>
      )}

      {step === 'mode' && (
        <>
          <Text variant="headlineMedium" style={styles.title}>Choose Battle Mode</Text>
          <Chip style={styles.selectedChip}>{WORKOUT_TYPES.find((t) => t.key === workoutType)?.label}</Chip>
          <View style={styles.modes}>
            {BATTLE_MODES.map((m) => (
              <Card key={m.key} style={styles.modeCard} onPress={() => handleSelectMode(m.key)}>
                <Card.Content>
                  <Text variant="titleMedium">{m.label}</Text>
                  <Text variant="bodySmall" style={{ opacity: 0.6 }}>{m.description}</Text>
                </Card.Content>
              </Card>
            ))}
          </View>
          <Button mode="outlined" onPress={() => setStep('type')} style={styles.backBtn}>Back</Button>
        </>
      )}

      <Snackbar visible={!!snackbar} onDismiss={() => setSnackbar('')} duration={3000}>{snackbar}</Snackbar>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 24, paddingBottom: 48 },
  title: { textAlign: 'center', marginBottom: 24 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  typeCard: { width: '48%', marginBottom: 12 },
  typeContent: { alignItems: 'center', paddingVertical: 24 },
  selectedChip: { alignSelf: 'center', marginBottom: 16 },
  modes: { gap: 12 },
  modeCard: { marginBottom: 12 },
  backBtn: { marginTop: 16 },
});
```

- [ ] **Step 4: Implement active battle screen**

Create `app/(main)/battle/index.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Text, Button, Card, ProgressBar, ActivityIndicator } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { battleApi } from '../../../src/features/battle/api/battleApi';
import { useAuthStore } from '../../../src/features/auth/stores/authStore';

export default function BattleScreen() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  const { data: session, isLoading, error } = useQuery({
    queryKey: ['activeBattle'],
    queryFn: () => battleApi.getActive(),
    enabled: isAuthenticated,
    retry: false,
  });

  const completeMutation = useMutation({
    mutationFn: () => battleApi.complete({
      sessionId: session?.session_id ?? '',
      exercises: [],
    }),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['activeBattle'] });
      queryClient.invalidateQueries({ queryKey: ['user'] });
      queryClient.invalidateQueries({ queryKey: ['levelProgress'] });
    },
  });

  const abandonMutation = useMutation({
    mutationFn: () => battleApi.abandon(),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['activeBattle'] });
    },
  });

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  // No active battle — show start button
  if (error || !session) {
    return (
      <View style={styles.center}>
        <Text variant="headlineMedium" style={styles.title}>Battle Arena</Text>
        <Text variant="bodyLarge" style={{ opacity: 0.6, marginBottom: 24 }}>No active battle</Text>
        <Button mode="contained" onPress={() => router.push('/(main)/battle/start')}>
          Start Battle
        </Button>
      </View>
    );
  }

  // Active battle
  const mob = session.mob;
  const hpPercent = Math.max(0, (session.mob_hp - session.total_damage_dealt) / session.mob_hp);

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text variant="headlineMedium" style={styles.title}>{mob.name}</Text>
      <Text variant="bodyMedium" style={styles.subtitle}>
        Level {mob.level} • {mob.rarity} • Mode: {session.mode}
      </Text>

      <Card style={styles.card}>
        <Card.Content>
          <Text variant="labelMedium">Mob HP</Text>
          <ProgressBar progress={hpPercent} color={hpPercent > 0.5 ? '#4CAF50' : hpPercent > 0.25 ? '#FF9800' : '#F44336'}
            style={styles.hpBar} />
          <Text variant="bodySmall">
            {Math.max(0, session.mob_hp - session.total_damage_dealt)} / {session.mob_hp}
          </Text>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Text variant="labelMedium">Your Damage</Text>
          <Text variant="headlineMedium">{session.total_damage_dealt}</Text>
          <Text variant="bodySmall">XP Reward: {session.mob_xp_reward}</Text>
        </Card.Content>
      </Card>

      <View style={styles.actions}>
        <Button mode="contained" onPress={() => completeMutation.mutate()}
          loading={completeMutation.isPending} disabled={completeMutation.isPending}
          style={styles.actionBtn}>
          Complete Battle
        </Button>
        <Button mode="outlined" onPress={() => abandonMutation.mutate()}
          loading={abandonMutation.isPending} disabled={abandonMutation.isPending}
          style={styles.actionBtn}>
          Abandon
        </Button>
      </View>

      {completeMutation.isSuccess && completeMutation.data && (
        <Card style={styles.resultCard}>
          <Card.Content>
            <Text variant="titleMedium">Battle Results</Text>
            <Text variant="bodyMedium">Tier: {completeMutation.data.performance_tier}</Text>
            <Text variant="bodyMedium">XP Awarded: +{completeMutation.data.xp_awarded}</Text>
            <Text variant="bodyMedium">Mobs Defeated: {completeMutation.data.mobs_defeated}</Text>
            <Text variant="bodyMedium">Completion: {completeMutation.data.completion_percent.toFixed(1)}%</Text>
            {completeMutation.data.level_up && (
              <Text variant="titleMedium" style={{ color: '#FFD700', marginTop: 8 }}>
                LEVEL UP! → Level {completeMutation.data.new_level}
              </Text>
            )}
            {completeMutation.data.loot_earned && <Text variant="bodyMedium">Loot earned!</Text>}
          </Card.Content>
        </Card>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  content: { padding: 24, paddingBottom: 48 },
  title: { textAlign: 'center', marginBottom: 4 },
  subtitle: { textAlign: 'center', opacity: 0.6, marginBottom: 24 },
  card: { marginBottom: 16 },
  hpBar: { marginVertical: 8, height: 12, borderRadius: 6 },
  actions: { gap: 12, marginTop: 16 },
  actionBtn: { marginBottom: 8 },
  resultCard: { marginTop: 16 },
});
```

- [ ] **Step 5: Run test — 2 tests PASS**

---

### Task 6: Workouts Screen

**Files:**
- Create: `app/(main)/workouts/index.tsx`
- Create: `app/(main)/workouts/[id].tsx`

- [ ] **Step 1: Create workout routes**

```bash
mkdir -p app/\(main\)/workouts
```

- [ ] **Step 2: Implement workouts list**

Create `app/(main)/workouts/index.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, FlatList } from 'react-native';
import { Text, Card, Button, Chip, ActivityIndicator } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useQuery } from '@tanstack/react-query';
import { workoutApi } from '../../../src/features/workout/api/workoutApi';
import { useAuthStore } from '../../../src/features/auth/stores/authStore';

const STATUS_COLORS: Record<string, string> = {
  pending: '#2196F3', in_progress: '#FF9800', completed: '#4CAF50', skipped: '#9E9E9E',
};

export default function WorkoutsScreen() {
  const router = useRouter();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  const { data: plans, isLoading } = useQuery({
    queryKey: ['workoutPlans'],
    queryFn: () => workoutApi.listPlans({}),
    enabled: isAuthenticated,
  });

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  return (
    <View style={styles.container}>
      <Text variant="headlineMedium" style={styles.title}>Workout Plans</Text>

      <Button mode="contained" onPress={() => router.push('/(main)/battle/start')} style={styles.generateBtn}>
        Generate New Plan & Battle
      </Button>

      <FlatList
        data={plans}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <Card style={styles.planCard} onPress={() => router.push(`/(main)/workouts/${item.id}`)}>
            <Card.Content>
              <View style={styles.planHeader}>
                <Text variant="titleMedium">{item.name}</Text>
                <Chip style={{ backgroundColor: STATUS_COLORS[item.status] || '#ccc' }}>
                  {item.status}
                </Chip>
              </View>
              {item.activity_type && <Text variant="bodySmall">Type: {item.activity_type}</Text>}
              {item.exercises && <Text variant="bodySmall">{item.exercises.length} exercises</Text>}
            </Card.Content>
          </Card>
        )}
        ListEmptyComponent={<Text style={styles.empty}>No workout plans yet. Start a battle to generate one!</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  title: { textAlign: 'center', marginBottom: 16 },
  generateBtn: { marginBottom: 16 },
  planCard: { marginBottom: 12 },
  planHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
});
```

- [ ] **Step 3: Implement workout detail**

Create `app/(main)/workouts/[id].tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Text, Card, Button, ActivityIndicator, Divider } from 'react-native-paper';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { workoutApi } from '../../../src/features/workout/api/workoutApi';
import { useAuthStore } from '../../../src/features/auth/stores/authStore';

export default function WorkoutDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const queryClient = useQueryClient();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  const { data: plan, isLoading } = useQuery({
    queryKey: ['workoutPlan', id],
    queryFn: () => workoutApi.getPlan(id!),
    enabled: isAuthenticated && !!id,
  });

  const startMutation = useMutation({
    mutationFn: () => workoutApi.startPlan(id!),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['workoutPlan', id] }); },
  });

  const completeMutation = useMutation({
    mutationFn: () => workoutApi.completePlan(id!),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workoutPlan', id] });
      queryClient.invalidateQueries({ queryKey: ['workoutPlans'] });
    },
  });

  if (isLoading || !plan) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text variant="headlineMedium" style={styles.title}>{plan.name}</Text>
      <Text variant="bodyMedium" style={styles.status}>Status: {plan.status}</Text>

      {plan.status === 'pending' && (
        <Button mode="contained" onPress={() => startMutation.mutate()} loading={startMutation.isPending}
          style={styles.actionBtn}>Start Workout</Button>
      )}
      {plan.status === 'in_progress' && (
        <Button mode="contained" onPress={() => completeMutation.mutate()} loading={completeMutation.isPending}
          style={styles.actionBtn}>Complete Workout</Button>
      )}

      <Text variant="titleMedium" style={styles.sectionTitle}>Exercises ({plan.exercises?.length || 0})</Text>

      {plan.exercises?.map((ex, idx) => (
        <Card key={ex.id} style={styles.exerciseCard}>
          <Card.Content>
            <Text variant="titleSmall">{idx + 1}. {ex.exercise.name}</Text>
            <Text variant="bodySmall">
              {ex.sets} sets × {ex.reps_min}-{ex.reps_max} reps • Rest: {ex.rest_seconds}s
            </Text>
            <Text variant="bodySmall" style={{ opacity: 0.6 }}>
              {ex.exercise.primary_muscle} • {ex.exercise.equipment} • {ex.exercise.difficulty}
            </Text>
          </Card.Content>
        </Card>
      ))}

      {plan.reward_tiers && (
        <Card style={styles.rewardCard}>
          <Card.Content>
            <Text variant="titleMedium">Reward Tiers</Text>
            <Divider style={{ marginVertical: 8 }} />
            {plan.reward_tiers.bronze && <Text variant="bodySmall">Bronze: {plan.reward_tiers.bronze.xp} XP</Text>}
            {plan.reward_tiers.silver && <Text variant="bodySmall">Silver: {plan.reward_tiers.silver.xp} XP</Text>}
            {plan.reward_tiers.gold && <Text variant="bodySmall">Gold: {plan.reward_tiers.gold.xp} XP</Text>}
          </Card.Content>
        </Card>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 24, paddingBottom: 48 },
  title: { textAlign: 'center', marginBottom: 4 },
  status: { textAlign: 'center', opacity: 0.6, marginBottom: 16 },
  actionBtn: { marginBottom: 16 },
  sectionTitle: { marginBottom: 12 },
  exerciseCard: { marginBottom: 8 },
  rewardCard: { marginTop: 16 },
});
```

---

### Task 7: Equipment & Inventory Screens

**Files:**
- Create: `app/(main)/equipment.tsx`
- Create: `app/(main)/inventory.tsx`

- [ ] **Step 1: Implement equipment screen**

Create `app/(main)/equipment.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, FlatList } from 'react-native';
import { Text, Card, Button, ActivityIndicator, Chip } from 'react-native-paper';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { equipmentApi } from '../../src/features/equipment/api/equipmentApi';
import { useAuthStore } from '../../src/features/auth/stores/authStore';

const RARITY_COLORS: Record<string, string> = {
  common: '#9E9E9E', uncommon: '#4CAF50', rare: '#2196F3', epic: '#9C27B0', legendary: '#FF9800',
};

export default function EquipmentScreen() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const queryClient = useQueryClient();

  const { data: equipped, isLoading } = useQuery({
    queryKey: ['equipped'],
    queryFn: () => equipmentApi.listEquipped(),
    enabled: isAuthenticated,
  });

  const unequipMutation = useMutation({
    mutationFn: (id: string) => equipmentApi.unequip(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['equipped'] });
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  return (
    <View style={styles.container}>
      <Text variant="headlineMedium" style={styles.title}>Equipment</Text>

      <FlatList
        data={equipped}
        keyExtractor={(item) => item.inventory_id || item.id}
        renderItem={({ item }) => (
          <Card style={styles.itemCard}>
            <Card.Content>
              <View style={styles.itemHeader}>
                <Text variant="titleMedium">{item.name}</Text>
                <Chip style={{ backgroundColor: RARITY_COLORS[item.rarity] || '#ccc' }}>{item.rarity}</Chip>
              </View>
              <Text variant="bodySmall">Slot: {item.slot}</Text>
              {item.stat_bonuses?.map((b: any, i: number) => (
                <Text key={i} variant="bodySmall">+{b.amount} {b.stat_type.toUpperCase()}</Text>
              ))}
              <Button mode="outlined" onPress={() => unequipMutation.mutate(item.inventory_id || item.id)}
                style={styles.unequipBtn}>Unequip</Button>
            </Card.Content>
          </Card>
        )}
        ListEmptyComponent={<Text style={styles.empty}>No equipment equipped</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  title: { textAlign: 'center', marginBottom: 16 },
  itemCard: { marginBottom: 12 },
  itemHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  unequipBtn: { marginTop: 8 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
});
```

- [ ] **Step 2: Implement inventory screen**

Create `app/(main)/inventory.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, FlatList } from 'react-native';
import { Text, Card, Button, ActivityIndicator, Chip } from 'react-native-paper';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useUser } from '../../src/features/auth/hooks/useUser';
import { equipmentApi } from '../../src/features/equipment/api/equipmentApi';

const RARITY_COLORS: Record<string, string> = {
  common: '#9E9E9E', uncommon: '#4CAF50', rare: '#2196F3', epic: '#9C27B0', legendary: '#FF9800',
};

export default function InventoryScreen() {
  const { data: user, isLoading } = useUser();
  const queryClient = useQueryClient();

  const equipMutation = useMutation({
    mutationFn: (id: string) => equipmentApi.equip(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['equipped'] });
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  // Inventory items come from the full user response
  const items: any[] = (user as any)?.inventory ?? [];

  return (
    <View style={styles.container}>
      <Text variant="headlineMedium" style={styles.title}>Inventory</Text>

      <FlatList
        data={items}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <Card style={styles.itemCard}>
            <Card.Content>
              <View style={styles.itemHeader}>
                <Text variant="titleMedium">{item.name}</Text>
                <Chip style={{ backgroundColor: RARITY_COLORS[item.rarity] || '#ccc' }}>{item.rarity}</Chip>
              </View>
              <Text variant="bodySmall">Type: {item.item_type} • Qty: {item.quantity}</Text>
              {item.description && <Text variant="bodySmall" style={{ opacity: 0.6 }}>{item.description}</Text>}
              {!item.equipped && item.item_type === 'equipment' && (
                <Button mode="contained" onPress={() => equipMutation.mutate(item.id)}
                  loading={equipMutation.isPending} style={styles.equipBtn}>Equip</Button>
              )}
              {item.equipped && <Chip style={styles.equippedChip}>Equipped: {item.equipped_slot}</Chip>}
            </Card.Content>
          </Card>
        )}
        ListEmptyComponent={<Text style={styles.empty}>No items in inventory</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  title: { textAlign: 'center', marginBottom: 16 },
  itemCard: { marginBottom: 12 },
  itemHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  equipBtn: { marginTop: 8 },
  equippedChip: { marginTop: 8, alignSelf: 'flex-start' },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
});
```

---

### Task 8: Update Profile Navigation

**Files:**
- Modify: `app/(main)/profile.tsx` — add navigation buttons for new screens

- [ ] **Step 1: Add navigation buttons to profile**

Add to the `navButtons` section in profile.tsx (after existing Health Dashboard and XP Table buttons):

```typescript
<Button mode="contained" onPress={() => router.push('/(main)/battle')} style={styles.navBtn}>Battle Arena</Button>
<Button mode="contained" onPress={() => router.push('/(main)/workouts')} style={styles.navBtn}>Workouts</Button>
<Button mode="contained" onPress={() => router.push('/(main)/equipment')} style={styles.navBtn}>Equipment</Button>
<Button mode="contained" onPress={() => router.push('/(main)/inventory')} style={styles.navBtn}>Inventory</Button>
```

---

### Task 9: Full Test Suite Verification

- [ ] **Step 1: Run all tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest --no-cache --verbose
```

Expected: ~100+ tests all PASS.
