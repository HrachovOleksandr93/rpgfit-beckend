# Plan 2B: Health Data Sync

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement health data types, API layer, sync service abstraction, health dashboard screen with permissions flow and daily summary display.

**Architecture:** `src/features/health/` with platform-agnostic API layer and service abstraction. Actual HealthKit/Health Connect integration will be stubbed with a platform service interface — native modules added when building dev builds. Dashboard shows sync status and daily summary.

**Tech Stack:** Expo Router, TanStack Query, Axios, React Native Paper, Jest + RNTL

**Business Logic (from spec):**

XP conversion rates (all rounded down):
- Steps: value / 1000 * rate_steps
- Active energy: value / 100 * rate_active_energy
- Workout: value / 10 * rate_workout
- Distance: value / 1000 * rate_distance (metres)
- Sleep: min(value, max_hours * 60) / 60 * rate_sleep
- Flights: value * rate_flights
- Non-XP types (heart_rate, weight, height, body_fat, blood_oxygen, water): 0
- Daily XP cap: 3000

Health data sync pipeline:
1. Check last sync time from AsyncStorage (key: health_last_sync_time)
2. If never synced, default window = last 7 days
3. Fetch data from device (HealthKit/Health Connect)
4. Upload via POST /api/health/sync
5. Backend deduplicates via (user_id, external_uuid)
6. Backend returns: accepted count, skipped count, XP awarded
7. Update last sync timestamp
8. Fetch today's summary from GET /api/health/summary?date=YYYY-MM-DD

Platform sync strategy:
- iOS: HKObserverQuery + enableBackgroundDeliveryForType (native, push-model)
- Android: Polling — 10s during active workout, 5min idle
- Both: Manual "Sync Now" button, sync on app open

---

### Task 1: Health Types and Enums

**Files:**
- Create: `src/features/health/types/enums.ts`
- Create: `src/features/health/types/models.ts`
- Create: `src/features/health/types/requests.ts`
- Create: `src/features/health/types/responses.ts`
- Test: `src/features/health/__tests__/healthEnums.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/features/health/__tests__/healthEnums.test.ts`:

```typescript
import { HealthDataType, HEALTH_DATA_TYPE_DISPLAY } from '../types/enums';

describe('HealthDataType', () => {
  it('has 15 types matching backend', () => {
    expect(Object.values(HealthDataType)).toHaveLength(15);
  });

  it('has correct API values', () => {
    expect(HealthDataType.Steps).toBe('steps');
    expect(HealthDataType.HeartRate).toBe('heart_rate');
    expect(HealthDataType.ActiveEnergyBurned).toBe('active_energy_burned');
    expect(HealthDataType.DistanceDelta).toBe('distance_delta');
    expect(HealthDataType.SleepAsleep).toBe('sleep_asleep');
    expect(HealthDataType.Workout).toBe('workout');
  });

  it('has display names for all types', () => {
    Object.values(HealthDataType).forEach((type) => {
      expect(HEALTH_DATA_TYPE_DISPLAY[type]).toBeDefined();
    });
  });
});
```

- [ ] **Step 2: Create health enums**

Create `src/features/health/types/enums.ts`:

```typescript
export enum HealthDataType {
  Steps = 'steps',
  HeartRate = 'heart_rate',
  ActiveEnergyBurned = 'active_energy_burned',
  DistanceDelta = 'distance_delta',
  Weight = 'weight',
  Height = 'height',
  BodyFatPercentage = 'body_fat_percentage',
  SleepAsleep = 'sleep_asleep',
  SleepDeep = 'sleep_deep',
  SleepLight = 'sleep_light',
  SleepRem = 'sleep_rem',
  Workout = 'workout',
  FlightsClimbed = 'flights_climbed',
  BloodOxygen = 'blood_oxygen',
  WaterConsumption = 'water_consumption',
}

export const HEALTH_DATA_TYPE_DISPLAY: Record<HealthDataType, string> = {
  [HealthDataType.Steps]: 'Steps',
  [HealthDataType.HeartRate]: 'Heart Rate',
  [HealthDataType.ActiveEnergyBurned]: 'Active Energy',
  [HealthDataType.DistanceDelta]: 'Distance',
  [HealthDataType.Weight]: 'Weight',
  [HealthDataType.Height]: 'Height',
  [HealthDataType.BodyFatPercentage]: 'Body Fat %',
  [HealthDataType.SleepAsleep]: 'Sleep (Asleep)',
  [HealthDataType.SleepDeep]: 'Sleep (Deep)',
  [HealthDataType.SleepLight]: 'Sleep (Light)',
  [HealthDataType.SleepRem]: 'Sleep (REM)',
  [HealthDataType.Workout]: 'Workout',
  [HealthDataType.FlightsClimbed]: 'Flights Climbed',
  [HealthDataType.BloodOxygen]: 'Blood Oxygen',
  [HealthDataType.WaterConsumption]: 'Water',
};

export const HEALTH_DATA_TYPE_UNIT: Record<HealthDataType, string> = {
  [HealthDataType.Steps]: 'COUNT',
  [HealthDataType.HeartRate]: 'BPM',
  [HealthDataType.ActiveEnergyBurned]: 'KCAL',
  [HealthDataType.DistanceDelta]: 'METER',
  [HealthDataType.Weight]: 'KG',
  [HealthDataType.Height]: 'METER',
  [HealthDataType.BodyFatPercentage]: 'PERCENT',
  [HealthDataType.SleepAsleep]: 'MINUTE',
  [HealthDataType.SleepDeep]: 'MINUTE',
  [HealthDataType.SleepLight]: 'MINUTE',
  [HealthDataType.SleepRem]: 'MINUTE',
  [HealthDataType.Workout]: 'MINUTE',
  [HealthDataType.FlightsClimbed]: 'COUNT',
  [HealthDataType.BloodOxygen]: 'PERCENT',
  [HealthDataType.WaterConsumption]: 'LITER',
};
```

- [ ] **Step 3: Create models, requests, responses**

Create `src/features/health/types/models.ts`:

```typescript
export interface HealthDataPoint {
  externalUuid?: string;
  type: string;
  value: number;
  unit: string;
  dateFrom: string;
  dateTo: string;
  sourceApp?: string;
  recordingMethod: 'automatic' | 'manual';
}

export interface HealthSummary {
  steps: number;
  activeEnergy: number;
  distance: number;
  sleepMinutes: number;
  averageHeartRate: number;
  workoutMinutes: number;
  date: string;
}
```

Create `src/features/health/types/requests.ts`:

```typescript
import type { HealthDataPoint } from './models';

export interface HealthSyncRequest {
  platform: 'ios' | 'android';
  data_points: {
    external_uuid?: string;
    type: string;
    value: number;
    unit: string;
    date_from: string;
    date_to: string;
    source_app?: string;
    recording_method: string;
  }[];
}
```

Create `src/features/health/types/responses.ts`:

```typescript
export interface HealthSyncResponse {
  accepted: number;
  skipped: number;
  xp_awarded: number;
}

export interface HealthSummaryResponse {
  steps: number;
  active_energy: number;
  distance: number;
  sleep_minutes: number;
  average_heart_rate: number;
  workout_minutes: number;
  date: string;
}

export interface HealthSyncStatusResponse {
  last_sync: string | null;
}
```

- [ ] **Step 4: Run test**

Expected: 3 tests PASS.

---

### Task 2: Health API

**Files:**
- Create: `src/features/health/api/healthApi.ts`
- Test: `src/features/health/__tests__/healthApi.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/features/health/__tests__/healthApi.test.ts`:

```typescript
import { healthApi } from '../api/healthApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { post: jest.fn(), get: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('healthApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('syncs health data via POST /api/health/sync', async () => {
    (apiClient.post as jest.Mock).mockResolvedValue({ data: { accepted: 5, skipped: 1, xp_awarded: 120 } });
    const request = { platform: 'ios' as const, data_points: [] };
    const result = await healthApi.sync(request);
    expect(apiClient.post).toHaveBeenCalledWith('/api/health/sync', request);
    expect(result.accepted).toBe(5);
    expect(result.xp_awarded).toBe(120);
  });

  it('fetches daily summary via GET /api/health/summary', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({
      data: { steps: 8000, active_energy: 350, distance: 5000, sleep_minutes: 420,
        average_heart_rate: 72, workout_minutes: 45, date: '2026-04-04' },
    });
    const result = await healthApi.getSummary('2026-04-04');
    expect(apiClient.get).toHaveBeenCalledWith('/api/health/summary', { params: { date: '2026-04-04' } });
    expect(result.steps).toBe(8000);
  });

  it('fetches sync status via GET /api/health/sync-status', async () => {
    (apiClient.get as jest.Mock).mockResolvedValue({
      data: { last_sync: '2026-04-04T10:30:00.000Z' },
    });
    const result = await healthApi.getSyncStatus();
    expect(apiClient.get).toHaveBeenCalledWith('/api/health/sync-status');
    expect(result.last_sync).toBe('2026-04-04T10:30:00.000Z');
  });
});
```

- [ ] **Step 2: Implement healthApi**

Create `src/features/health/api/healthApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';
import type { HealthSyncRequest } from '../types/requests';
import type { HealthSyncResponse, HealthSummaryResponse, HealthSyncStatusResponse } from '../types/responses';

export const healthApi = {
  async sync(data: HealthSyncRequest): Promise<HealthSyncResponse> {
    const response = await apiClient.post<HealthSyncResponse>('/api/health/sync', data);
    return response.data;
  },
  async getSummary(date: string): Promise<HealthSummaryResponse> {
    const response = await apiClient.get<HealthSummaryResponse>('/api/health/summary', { params: { date } });
    return response.data;
  },
  async getSyncStatus(): Promise<HealthSyncStatusResponse> {
    const response = await apiClient.get<HealthSyncStatusResponse>('/api/health/sync-status');
    return response.data;
  },
};
```

- [ ] **Step 3: Run test**

Expected: 3 tests PASS.

---

### Task 3: Health Hooks

**Files:**
- Create: `src/features/health/hooks/useHealthSync.ts`
- Create: `src/features/health/hooks/useHealthSummary.ts`
- Test: `src/features/health/__tests__/useHealthSummary.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/features/health/__tests__/useHealthSummary.test.ts`:

```typescript
import { renderHook, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useHealthSummary } from '../hooks/useHealthSummary';
import { healthApi } from '../api/healthApi';
import { useAuthStore } from '../../auth/stores/authStore';

jest.mock('../api/healthApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

function createWrapper() {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(QueryClientProvider, { client: queryClient }, children);
}

describe('useHealthSummary', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ isAuthenticated: true, isLoading: false, token: 'test', user: null });
  });

  it('fetches health summary for today', async () => {
    (healthApi.getSummary as jest.Mock).mockResolvedValue({
      steps: 8000, active_energy: 350, distance: 5000,
      sleep_minutes: 420, average_heart_rate: 72, workout_minutes: 45, date: '2026-04-04',
    });
    const { result } = renderHook(() => useHealthSummary('2026-04-04'), { wrapper: createWrapper() });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.steps).toBe(8000);
  });
});
```

- [ ] **Step 2: Implement hooks**

Create `src/features/health/hooks/useHealthSummary.ts`:

```typescript
import { useQuery } from '@tanstack/react-query';
import { healthApi } from '../api/healthApi';
import { useAuthStore } from '../../auth/stores/authStore';

export function useHealthSummary(date: string) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  return useQuery({
    queryKey: ['healthSummary', date],
    queryFn: () => healthApi.getSummary(date),
    enabled: isAuthenticated && !!date,
  });
}
```

Create `src/features/health/hooks/useHealthSync.ts`:

```typescript
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { healthApi } from '../api/healthApi';
import type { HealthSyncRequest } from '../types/requests';

export function useHealthSync() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: HealthSyncRequest) => healthApi.sync(data),
    onSuccess: () => {
      const today = new Date().toISOString().split('T')[0];
      queryClient.invalidateQueries({ queryKey: ['healthSummary', today] });
      queryClient.invalidateQueries({ queryKey: ['levelProgress'] });
    },
  });
}
```

- [ ] **Step 3: Run test**

Expected: 1 test PASS.

---

### Task 4: Health Dashboard Screen

**Files:**
- Create: `app/(main)/health.tsx`
- Test: `src/features/health/__tests__/HealthScreen.test.tsx`

- [ ] **Step 1: Write the failing test**

Create `src/features/health/__tests__/HealthScreen.test.tsx`:

```typescript
import React from 'react';
import { render, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import HealthScreen from '../../../../app/(main)/health';
import { healthApi } from '../api/healthApi';
import { useAuthStore } from '../../auth/stores/authStore';
import { lightTheme } from '../../../shared/theme';

jest.mock('../api/healthApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ back: jest.fn(), push: jest.fn() }),
}));

function renderWithProviders(ui: React.ReactElement) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  useAuthStore.setState({ isAuthenticated: true, isLoading: false, token: 'test', user: null });
  return render(
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={lightTheme}>{ui}</PaperProvider>
    </QueryClientProvider>
  );
}

describe('HealthScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (healthApi.getSummary as jest.Mock).mockResolvedValue({
      steps: 8500, active_energy: 420, distance: 6200,
      sleep_minutes: 450, average_heart_rate: 68, workout_minutes: 55, date: '2026-04-04',
    });
    (healthApi.getSyncStatus as jest.Mock).mockResolvedValue({ last_sync: '2026-04-04T08:00:00Z' });
  });

  it('displays Health Dashboard title', async () => {
    const { getByText } = renderWithProviders(<HealthScreen />);
    await waitFor(() => { expect(getByText('Health Dashboard')).toBeTruthy(); });
  });

  it('displays step count', async () => {
    const { getByText } = renderWithProviders(<HealthScreen />);
    await waitFor(() => { expect(getByText(/8,500/)).toBeTruthy(); });
  });

  it('displays sync button', async () => {
    const { getByText } = renderWithProviders(<HealthScreen />);
    await waitFor(() => { expect(getByText('Sync Now')).toBeTruthy(); });
  });
});
```

- [ ] **Step 2: Implement Health Dashboard**

Create `app/(main)/health.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Text, Card, Button, ActivityIndicator } from 'react-native-paper';
import { useHealthSummary } from '../../src/features/health/hooks/useHealthSummary';
import { useHealthSync } from '../../src/features/health/hooks/useHealthSync';

function formatDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function formatMinutes(minutes: number): string {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function formatNumber(n: number): string {
  return n.toLocaleString();
}

export default function HealthScreen() {
  const today = formatDate(new Date());
  const { data: summary, isLoading } = useHealthSummary(today);
  const syncMutation = useHealthSync();

  function handleSync() {
    syncMutation.mutate({ platform: 'ios', data_points: [] });
  }

  if (isLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text variant="headlineMedium" style={styles.title}>Health Dashboard</Text>
      <Text variant="bodyMedium" style={styles.date}>{today}</Text>

      {summary && (
        <View style={styles.grid}>
          <SummaryCard title="Steps" value={formatNumber(summary.steps)} icon="walk" />
          <SummaryCard title="Active Energy" value={`${formatNumber(summary.active_energy)} kcal`} icon="fire" />
          <SummaryCard title="Distance" value={`${(summary.distance / 1000).toFixed(2)} km`} icon="map" />
          <SummaryCard title="Sleep" value={formatMinutes(summary.sleep_minutes)} icon="sleep" />
          <SummaryCard title="Avg Heart Rate" value={`${summary.average_heart_rate} bpm`} icon="heart" />
          <SummaryCard title="Workouts" value={formatMinutes(summary.workout_minutes)} icon="dumbbell" />
        </View>
      )}

      <Button mode="contained" onPress={handleSync} loading={syncMutation.isPending}
        disabled={syncMutation.isPending} style={styles.syncBtn}>
        Sync Now
      </Button>

      {syncMutation.isSuccess && syncMutation.data && (
        <Card style={styles.resultCard}>
          <Card.Content>
            <Text variant="bodyMedium">Synced: {syncMutation.data.accepted} new, {syncMutation.data.skipped} duplicate</Text>
            <Text variant="bodyMedium">XP earned: +{syncMutation.data.xp_awarded}</Text>
          </Card.Content>
        </Card>
      )}
    </ScrollView>
  );
}

function SummaryCard({ title, value, icon }: { title: string; value: string; icon: string }) {
  return (
    <Card style={summaryStyles.card}>
      <Card.Content>
        <Text variant="labelMedium" style={summaryStyles.label}>{title}</Text>
        <Text variant="headlineSmall">{value}</Text>
      </Card.Content>
    </Card>
  );
}

const summaryStyles = StyleSheet.create({
  card: { width: '48%', marginBottom: 12 },
  label: { opacity: 0.6, marginBottom: 4 },
});

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 16, paddingBottom: 48 },
  title: { textAlign: 'center', marginBottom: 4 },
  date: { textAlign: 'center', opacity: 0.6, marginBottom: 16 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  syncBtn: { marginTop: 16, marginBottom: 16 },
  resultCard: { marginBottom: 16 },
});
```

- [ ] **Step 3: Run test**

Expected: 3 tests PASS.

---

### Task 5: Full Test Suite Verification

- [ ] **Step 1: Run all tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest --no-cache --verbose
```

Expected: ~83+ tests all PASS.
