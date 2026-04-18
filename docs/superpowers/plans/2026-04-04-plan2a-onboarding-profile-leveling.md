# Plan 2A: Onboarding, Profile & Leveling Screens

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the 9-step onboarding wizard, full profile screen with character stats, and leveling progress screen — all wired to backend API endpoints.

**Architecture:** Feature modules under `src/features/onboarding/` and `src/features/leveling/`. Profile reuses existing `auth` feature (useUser hook). Onboarding wizard uses React state + horizontal FlatList for step navigation. Leveling fetches from `GET /api/levels/table` and `GET /api/levels/progress`.

**Tech Stack:** Expo Router, TanStack Query, React Native Paper, Axios, Jest + RNTL

**Existing code reference:**
- Auth store/hooks: `src/features/auth/stores/authStore.ts`, `src/features/auth/hooks/useUser.ts`
- Shared types: `src/shared/types/enums.ts`, `src/shared/types/user.ts`
- API client: `src/shared/api/client.ts`
- Validation: `src/shared/utils/validation.ts`
- Jest mocks: `jest.mock.react-native.js`, `jest.mock.react-native-paper.js`, `jest.mock.safe-area-context.js`
- All imports from `app/` use relative paths (`../../src/...`), all imports within `src/` use relative paths (`../../../shared/...`)

---

### Task 1: Onboarding API and Types

**Files:**
- Create: `src/features/onboarding/types/requests.ts`
- Create: `src/features/onboarding/api/onboardingApi.ts`
- Test: `src/features/onboarding/__tests__/onboardingApi.test.ts`

- [ ] **Step 1: Create onboarding request type**

Create `src/features/onboarding/types/requests.ts`:

```typescript
export interface OnboardingRequest {
  display_name: string;
  height: number;
  weight: number;
  gender: string;
  character_race: string;
  training_frequency: string;
  workout_type: string;
  lifestyle: string;
  preferred_workouts: string[];
}
```

- [ ] **Step 2: Write the failing test**

Create `src/features/onboarding/__tests__/onboardingApi.test.ts`:

```typescript
import { onboardingApi } from '../api/onboardingApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { post: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('onboardingApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('sends POST to /api/onboarding with all fields', async () => {
    const responseData = {
      id: 'uuid-1', login: 'user@test.com', display_name: 'TestUser',
      onboarding_completed: true,
      character_stats: { strength: 10, dexterity: 12, constitution: 8, level: 1, total_xp: 0 },
    };
    (apiClient.post as jest.Mock).mockResolvedValue({ data: responseData });

    const request = {
      display_name: 'TestUser', height: 180, weight: 80, gender: 'male',
      character_race: 'human', training_frequency: 'moderate',
      workout_type: 'cardio', lifestyle: 'active',
      preferred_workouts: ['running', 'cycling'],
    };
    const result = await onboardingApi.complete(request);

    expect(apiClient.post).toHaveBeenCalledWith('/api/onboarding', request);
    expect(result.onboarding_completed).toBe(true);
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/onboarding/__tests__/onboardingApi.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 4: Implement onboardingApi**

Create `src/features/onboarding/api/onboardingApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';
import type { OnboardingRequest } from '../types/requests';
import type { UserResponse } from '../../auth/types/responses';

export const onboardingApi = {
  async complete(data: OnboardingRequest): Promise<UserResponse> {
    const response = await apiClient.post<UserResponse>('/api/onboarding', data);
    return response.data;
  },
};
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/onboarding/__tests__/onboardingApi.test.ts --no-cache
```

Expected: 1 test PASS.

---

### Task 2: Onboarding TanStack Query Hook

**Files:**
- Create: `src/features/onboarding/hooks/useOnboarding.ts`
- Test: `src/features/onboarding/__tests__/useOnboarding.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/features/onboarding/__tests__/useOnboarding.test.ts`:

```typescript
import { renderHook, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useOnboarding } from '../hooks/useOnboarding';
import { onboardingApi } from '../api/onboardingApi';

jest.mock('../api/onboardingApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(QueryClientProvider, { client: queryClient }, children);
}

describe('useOnboarding', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('calls onboardingApi.complete and returns user on success', async () => {
    const responseData = {
      id: 'uuid-1', login: 'user@test.com', display_name: 'TestUser',
      height: 180, weight: 80, workout_type: 'cardio', activity_level: 'moderate',
      desired_goal: 'maintain', character_race: 'human', gender: 'male',
      training_frequency: 'moderate', lifestyle: 'active',
      preferred_workouts: ['running'], onboarding_completed: true,
      character_stats: { strength: 10, dexterity: 12, constitution: 8, level: 1, total_xp: 0 },
    };
    (onboardingApi.complete as jest.Mock).mockResolvedValue(responseData);

    const { result } = renderHook(() => useOnboarding(), { wrapper: createWrapper() });

    result.current.mutate({
      display_name: 'TestUser', height: 180, weight: 80, gender: 'male',
      character_race: 'human', training_frequency: 'moderate',
      workout_type: 'cardio', lifestyle: 'active',
      preferred_workouts: ['running'],
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(onboardingApi.complete).toHaveBeenCalled();
  });

  it('sets error state on failure', async () => {
    (onboardingApi.complete as jest.Mock).mockRejectedValue(new Error('Already onboarded'));
    const { result } = renderHook(() => useOnboarding(), { wrapper: createWrapper() });

    result.current.mutate({
      display_name: 'TestUser', height: 180, weight: 80, gender: 'male',
      character_race: 'human', training_frequency: 'moderate',
      workout_type: 'cardio', lifestyle: 'active',
      preferred_workouts: ['running'],
    });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error?.message).toBe('Already onboarded');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/onboarding/__tests__/useOnboarding.test.ts --no-cache
```

- [ ] **Step 3: Implement useOnboarding hook**

Create `src/features/onboarding/hooks/useOnboarding.ts`:

```typescript
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { onboardingApi } from '../api/onboardingApi';
import type { OnboardingRequest } from '../types/requests';

export function useOnboarding() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: OnboardingRequest) => onboardingApi.complete(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/onboarding/__tests__/useOnboarding.test.ts --no-cache
```

Expected: 2 tests PASS.

---

### Task 3: Onboarding Wizard Screen

**Files:**
- Modify: `app/(onboarding)/index.tsx` (replace placeholder)
- Test: `src/features/onboarding/__tests__/OnboardingScreen.test.tsx`

- [ ] **Step 1: Write the failing test**

Create `src/features/onboarding/__tests__/OnboardingScreen.test.tsx`:

```typescript
import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import OnboardingScreen from '../../../../app/(onboarding)/index';
import { onboardingApi } from '../api/onboardingApi';
import { lightTheme } from '../../../shared/theme';

jest.mock('../api/onboardingApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ replace: jest.fn() }),
}));

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

describe('OnboardingScreen', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('renders step 1 with display name field', () => {
    const { getByLabelText, getByText } = renderWithProviders(<OnboardingScreen />);
    expect(getByText('Step 1 of 9')).toBeTruthy();
    expect(getByLabelText('Display Name')).toBeTruthy();
  });

  it('shows Next button on step 1', () => {
    const { getByText } = renderWithProviders(<OnboardingScreen />);
    expect(getByText('Next')).toBeTruthy();
  });

  it('validates display name before advancing', async () => {
    const { getByText } = renderWithProviders(<OnboardingScreen />);
    fireEvent.press(getByText('Next'));
    await waitFor(() => {
      expect(getByText('Display name must be at least 3 characters')).toBeTruthy();
    });
  });

  it('advances to step 2 with valid name', async () => {
    const { getByText, getByLabelText } = renderWithProviders(<OnboardingScreen />);
    fireEvent.changeText(getByLabelText('Display Name'), 'TestUser');
    fireEvent.press(getByText('Next'));
    await waitFor(() => {
      expect(getByText('Step 2 of 9')).toBeTruthy();
      expect(getByLabelText('Height (cm)')).toBeTruthy();
    });
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/onboarding/__tests__/OnboardingScreen.test.tsx --no-cache
```

- [ ] **Step 3: Implement onboarding wizard**

Replace `app/(onboarding)/index.tsx` with the full 9-step wizard:

```typescript
import React, { useState } from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { TextInput, Button, Text, ProgressBar, Chip, Snackbar } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useOnboarding } from '../../src/features/onboarding/hooks/useOnboarding';
import {
  validateOnboardingDisplayName, validatePositiveNumber,
} from '../../src/shared/utils/validation';
import {
  Gender, CharacterRace, TrainingFrequency, WorkoutType, Lifestyle,
  ENUM_DISPLAY_NAMES,
} from '../../src/shared/types/enums';

const TOTAL_STEPS = 9;

const PREFERRED_WORKOUT_OPTIONS = [
  'running', 'powerlifting', 'crossfit', 'yoga', 'swimming',
  'cycling', 'martial_arts', 'gymnastics', 'hiking', 'dancing', 'other',
];

const WORKOUT_LABELS: Record<string, string> = {
  running: 'Running', powerlifting: 'Powerlifting', crossfit: 'CrossFit',
  yoga: 'Yoga', swimming: 'Swimming', cycling: 'Cycling',
  martial_arts: 'Martial Arts', gymnastics: 'Gymnastics',
  hiking: 'Hiking', dancing: 'Dancing', other: 'Other',
};

export default function OnboardingScreen() {
  const router = useRouter();
  const onboardingMutation = useOnboarding();

  const [step, setStep] = useState(1);
  const [displayName, setDisplayName] = useState('');
  const [height, setHeight] = useState('');
  const [weight, setWeight] = useState('');
  const [gender, setGender] = useState<Gender>(Gender.Male);
  const [characterRace, setCharacterRace] = useState<CharacterRace>(CharacterRace.Human);
  const [trainingFrequency, setTrainingFrequency] = useState<TrainingFrequency>(TrainingFrequency.None);
  const [workoutType, setWorkoutType] = useState<WorkoutType>(WorkoutType.Strength);
  const [lifestyle, setLifestyle] = useState<Lifestyle>(Lifestyle.Sedentary);
  const [preferredWorkouts, setPreferredWorkouts] = useState<string[]>([]);
  const [error, setError] = useState('');
  const [snackbar, setSnackbar] = useState('');

  function validateStep(): boolean {
    setError('');
    switch (step) {
      case 1: {
        const err = validateOnboardingDisplayName(displayName);
        if (err) { setError(err); return false; }
        return true;
      }
      case 2: {
        const h = parseFloat(height);
        const w = parseFloat(weight);
        if (isNaN(h) || h <= 0) { setError('Height must be greater than 0'); return false; }
        if (isNaN(w) || w <= 0) { setError('Weight must be greater than 0'); return false; }
        return true;
      }
      case 8: {
        if (preferredWorkouts.length === 0) { setError('Select at least one workout'); return false; }
        return true;
      }
      default:
        return true;
    }
  }

  function handleNext() {
    if (!validateStep()) return;
    if (step < TOTAL_STEPS) {
      setStep(step + 1);
    }
  }

  function handleBack() {
    if (step > 1) { setStep(step - 1); setError(''); }
  }

  function handleSubmit() {
    onboardingMutation.mutate({
      display_name: displayName.trim(), height: parseFloat(height), weight: parseFloat(weight),
      gender, character_race: characterRace, training_frequency: trainingFrequency,
      workout_type: workoutType, lifestyle, preferred_workouts: preferredWorkouts,
    }, {
      onSuccess: () => { router.replace('/(main)/profile'); },
      onError: (e) => { setSnackbar(e.message || 'Onboarding failed'); },
    });
  }

  function toggleWorkout(w: string) {
    setPreferredWorkouts((prev) =>
      prev.includes(w) ? prev.filter((x) => x !== w) : [...prev, w]
    );
  }

  function renderEnumOptions<T extends string>(enumObj: Record<string, T>, value: T, onChange: (v: T) => void) {
    return (
      <View style={styles.chipRow}>
        {Object.values(enumObj).map((v) => (
          <Chip key={v} selected={v === value} onPress={() => onChange(v)}
            style={styles.chip}>{ENUM_DISPLAY_NAMES[v] || v}</Chip>
        ))}
      </View>
    );
  }

  function renderStep() {
    switch (step) {
      case 1:
        return (<>
          <Text variant="titleLarge">What's your display name?</Text>
          <TextInput label="Display Name" accessibilityLabel="Display Name" value={displayName}
            onChangeText={setDisplayName} style={styles.input} />
        </>);
      case 2:
        return (<>
          <Text variant="titleLarge">Your measurements</Text>
          <TextInput label="Height (cm)" accessibilityLabel="Height (cm)" value={height}
            onChangeText={setHeight} keyboardType="numeric" style={styles.input} />
          <TextInput label="Weight (kg)" accessibilityLabel="Weight (kg)" value={weight}
            onChangeText={setWeight} keyboardType="numeric" style={styles.input} />
        </>);
      case 3:
        return (<>
          <Text variant="titleLarge">Gender</Text>
          {renderEnumOptions(Gender, gender, setGender)}
        </>);
      case 4:
        return (<>
          <Text variant="titleLarge">Character Race</Text>
          {renderEnumOptions(CharacterRace, characterRace, setCharacterRace)}
        </>);
      case 5:
        return (<>
          <Text variant="titleLarge">Training Frequency</Text>
          {renderEnumOptions(TrainingFrequency, trainingFrequency, setTrainingFrequency)}
        </>);
      case 6:
        return (<>
          <Text variant="titleLarge">Preferred Training Style</Text>
          {renderEnumOptions(WorkoutType, workoutType, setWorkoutType)}
        </>);
      case 7:
        return (<>
          <Text variant="titleLarge">Lifestyle</Text>
          {renderEnumOptions(Lifestyle, lifestyle, setLifestyle)}
        </>);
      case 8:
        return (<>
          <Text variant="titleLarge">Preferred Workouts</Text>
          <Text variant="bodyMedium" style={{ marginBottom: 12, opacity: 0.7 }}>Select all that apply</Text>
          <View style={styles.chipRow}>
            {PREFERRED_WORKOUT_OPTIONS.map((w) => (
              <Chip key={w} selected={preferredWorkouts.includes(w)} onPress={() => toggleWorkout(w)}
                style={styles.chip}>{WORKOUT_LABELS[w]}</Chip>
            ))}
          </View>
        </>);
      case 9:
        return (<>
          <Text variant="titleLarge">Summary</Text>
          <Text variant="bodyLarge">Name: {displayName}</Text>
          <Text variant="bodyLarge">Height: {height} cm</Text>
          <Text variant="bodyLarge">Weight: {weight} kg</Text>
          <Text variant="bodyLarge">Gender: {ENUM_DISPLAY_NAMES[gender]}</Text>
          <Text variant="bodyLarge">Race: {ENUM_DISPLAY_NAMES[characterRace]}</Text>
          <Text variant="bodyLarge">Training: {ENUM_DISPLAY_NAMES[trainingFrequency]}</Text>
          <Text variant="bodyLarge">Style: {ENUM_DISPLAY_NAMES[workoutType]}</Text>
          <Text variant="bodyLarge">Lifestyle: {ENUM_DISPLAY_NAMES[lifestyle]}</Text>
          <Text variant="bodyLarge">Workouts: {preferredWorkouts.map((w) => WORKOUT_LABELS[w]).join(', ')}</Text>
        </>);
    }
  }

  return (
    <View style={styles.container}>
      <ProgressBar progress={step / TOTAL_STEPS} style={styles.progress} />
      <Text style={styles.stepLabel}>Step {step} of {TOTAL_STEPS}</Text>
      <ScrollView contentContainerStyle={styles.content}>
        {renderStep()}
        {error ? <Text style={styles.error}>{error}</Text> : null}
      </ScrollView>
      <View style={styles.buttons}>
        {step > 1 && <Button mode="outlined" onPress={handleBack} style={styles.backBtn}>Back</Button>}
        {step < TOTAL_STEPS ? (
          <Button mode="contained" onPress={handleNext} style={styles.nextBtn}>Next</Button>
        ) : (
          <Button mode="contained" onPress={handleSubmit}
            loading={onboardingMutation.isPending} disabled={onboardingMutation.isPending}
            style={styles.nextBtn}>Submit</Button>
        )}
      </View>
      <Snackbar visible={!!snackbar} onDismiss={() => setSnackbar('')} duration={3000}>{snackbar}</Snackbar>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 24 },
  progress: { marginBottom: 8 },
  stepLabel: { textAlign: 'center', marginBottom: 16, opacity: 0.6 },
  content: { flexGrow: 1 },
  input: { marginBottom: 12 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 12 },
  chip: { marginBottom: 4 },
  error: { color: '#B3261E', fontSize: 12, marginTop: 8 },
  buttons: { flexDirection: 'row', justifyContent: 'space-between', paddingTop: 16 },
  backBtn: { flex: 1, marginRight: 8 },
  nextBtn: { flex: 1, marginLeft: 8 },
});
```

- [ ] **Step 4: Add Chip and ProgressBar to react-native-paper mock if missing**

Check `jest.mock.react-native-paper.js` and add `Chip` and `ProgressBar` mocks if not present:

```javascript
// Add to jest.mock.react-native-paper.js exports:
Chip: ({ children, onPress, selected, style }) => {
  const React = require('react');
  return React.createElement('TouchableOpacity', { onPress, style, accessibilityState: { selected } }, 
    React.createElement('Text', null, children));
},
ProgressBar: ({ progress, style }) => {
  const React = require('react');
  return React.createElement('View', { style, accessibilityRole: 'progressbar', accessibilityValue: { now: progress } });
},
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/onboarding/__tests__/OnboardingScreen.test.tsx --no-cache
```

Expected: 4 tests PASS.

---

### Task 4: Leveling API and Types

**Files:**
- Create: `src/features/leveling/types/responses.ts`
- Create: `src/features/leveling/api/levelingApi.ts`
- Test: `src/features/leveling/__tests__/levelingApi.test.ts`

- [ ] **Step 1: Create leveling response types**

Create `src/features/leveling/types/responses.ts`:

```typescript
export interface LevelTableEntry {
  level: number;
  xp_required: number;
  total_xp_required: number;
}

export interface LevelProgressResponse {
  current_level: number;
  total_xp: number;
  xp_in_current_bracket: number;
  xp_to_next_level: number;
  progress_percent: number;
}
```

- [ ] **Step 2: Write the failing test**

Create `src/features/leveling/__tests__/levelingApi.test.ts`:

```typescript
import { levelingApi } from '../api/levelingApi';
import { apiClient } from '../../../shared/api/client';

jest.mock('../../../shared/api/client', () => ({
  apiClient: { get: jest.fn() },
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

describe('levelingApi', () => {
  beforeEach(() => { jest.clearAllMocks(); });

  it('fetches level table from GET /api/levels/table', async () => {
    const table = [
      { level: 1, xp_required: 32, total_xp_required: 32 },
      { level: 2, xp_required: 73, total_xp_required: 105 },
    ];
    (apiClient.get as jest.Mock).mockResolvedValue({ data: table });
    const result = await levelingApi.getTable();
    expect(apiClient.get).toHaveBeenCalledWith('/api/levels/table');
    expect(result).toHaveLength(2);
    expect(result[0].level).toBe(1);
  });

  it('fetches level progress from GET /api/levels/progress', async () => {
    const progress = {
      current_level: 5, total_xp: 500, xp_in_current_bracket: 120,
      xp_to_next_level: 180, progress_percent: 66.7,
    };
    (apiClient.get as jest.Mock).mockResolvedValue({ data: progress });
    const result = await levelingApi.getProgress();
    expect(apiClient.get).toHaveBeenCalledWith('/api/levels/progress');
    expect(result.current_level).toBe(5);
    expect(result.progress_percent).toBe(66.7);
  });
});
```

- [ ] **Step 3: Implement levelingApi**

Create `src/features/leveling/api/levelingApi.ts`:

```typescript
import { apiClient } from '../../../shared/api/client';
import type { LevelTableEntry, LevelProgressResponse } from '../types/responses';

export const levelingApi = {
  async getTable(): Promise<LevelTableEntry[]> {
    const response = await apiClient.get<LevelTableEntry[]>('/api/levels/table');
    return response.data;
  },
  async getProgress(): Promise<LevelProgressResponse> {
    const response = await apiClient.get<LevelProgressResponse>('/api/levels/progress');
    return response.data;
  },
};
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/leveling/__tests__/levelingApi.test.ts --no-cache
```

Expected: 2 tests PASS.

---

### Task 5: Leveling Hooks

**Files:**
- Create: `src/features/leveling/hooks/useLevelTable.ts`
- Create: `src/features/leveling/hooks/useLevelProgress.ts`
- Test: `src/features/leveling/__tests__/useLevelProgress.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/features/leveling/__tests__/useLevelProgress.test.ts`:

```typescript
import { renderHook, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useLevelProgress } from '../hooks/useLevelProgress';
import { levelingApi } from '../api/levelingApi';
import { useAuthStore } from '../../auth/stores/authStore';

jest.mock('../api/levelingApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(QueryClientProvider, { client: queryClient }, children);
}

describe('useLevelProgress', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ isAuthenticated: true, isLoading: false, token: 'test', user: null });
  });

  it('fetches level progress when authenticated', async () => {
    (levelingApi.getProgress as jest.Mock).mockResolvedValue({
      current_level: 5, total_xp: 500, xp_in_current_bracket: 120,
      xp_to_next_level: 180, progress_percent: 66.7,
    });

    const { result } = renderHook(() => useLevelProgress(), { wrapper: createWrapper() });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.current_level).toBe(5);
  });
});
```

- [ ] **Step 2: Implement hooks**

Create `src/features/leveling/hooks/useLevelProgress.ts`:

```typescript
import { useQuery } from '@tanstack/react-query';
import { levelingApi } from '../api/levelingApi';
import { useAuthStore } from '../../auth/stores/authStore';

export function useLevelProgress() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  return useQuery({
    queryKey: ['levelProgress'],
    queryFn: () => levelingApi.getProgress(),
    enabled: isAuthenticated,
  });
}
```

Create `src/features/leveling/hooks/useLevelTable.ts`:

```typescript
import { useQuery } from '@tanstack/react-query';
import { levelingApi } from '../api/levelingApi';

export function useLevelTable() {
  return useQuery({
    queryKey: ['levelTable'],
    queryFn: () => levelingApi.getTable(),
    staleTime: 24 * 60 * 60 * 1000, // cache for 24h — table rarely changes
  });
}
```

- [ ] **Step 3: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/leveling/__tests__/useLevelProgress.test.ts --no-cache
```

Expected: 1 test PASS.

---

### Task 6: Profile Screen (replace placeholder)

**Files:**
- Modify: `app/(main)/profile.tsx` (replace placeholder)
- Test: `src/features/auth/__tests__/ProfileScreen.test.tsx`

- [ ] **Step 1: Write the failing test**

Create `src/features/auth/__tests__/ProfileScreen.test.tsx`:

```typescript
import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import ProfileScreen from '../../../../app/(main)/profile';
import { authApi } from '../api/authApi';
import { levelingApi } from '../../leveling/api/levelingApi';
import { useAuthStore } from '../stores/authStore';
import { lightTheme } from '../../../shared/theme';

jest.mock('../api/authApi');
jest.mock('../../leveling/api/levelingApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ replace: jest.fn(), push: jest.fn() }),
}));

function renderWithProviders(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  useAuthStore.setState({ isAuthenticated: true, isLoading: false, token: 'test', user: null });
  return render(
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={lightTheme}>{ui}</PaperProvider>
    </QueryClientProvider>
  );
}

describe('ProfileScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (authApi.getFullUser as jest.Mock).mockResolvedValue({
      id: 'uuid-1', login: 'user@test.com', display_name: 'TestUser',
      height: 180, weight: 80, workout_type: 'cardio', activity_level: 'moderate',
      desired_goal: 'maintain', character_race: 'human', gender: 'male',
      training_frequency: 'moderate', lifestyle: 'active',
      preferred_workouts: ['running'], onboarding_completed: true,
      character_stats: { strength: 10, dexterity: 12, constitution: 8, level: 5, total_xp: 500 },
    });
    (levelingApi.getProgress as jest.Mock).mockResolvedValue({
      current_level: 5, total_xp: 500, xp_in_current_bracket: 120,
      xp_to_next_level: 180, progress_percent: 66.7,
    });
  });

  it('displays user profile data', async () => {
    const { getByText } = renderWithProviders(<ProfileScreen />);
    await waitFor(() => {
      expect(getByText('TestUser')).toBeTruthy();
    });
  });

  it('displays character stats', async () => {
    const { getByText } = renderWithProviders(<ProfileScreen />);
    await waitFor(() => {
      expect(getByText(/STR/)).toBeTruthy();
      expect(getByText(/DEX/)).toBeTruthy();
      expect(getByText(/CON/)).toBeTruthy();
    });
  });

  it('displays level info', async () => {
    const { getByText } = renderWithProviders(<ProfileScreen />);
    await waitFor(() => {
      expect(getByText(/Level 5/)).toBeTruthy();
    });
  });

  it('has logout button', async () => {
    const { getByText } = renderWithProviders(<ProfileScreen />);
    await waitFor(() => {
      expect(getByText('Logout')).toBeTruthy();
    });
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/ProfileScreen.test.tsx --no-cache
```

- [ ] **Step 3: Implement profile screen**

Replace `app/(main)/profile.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import { Text, Button, Card, ProgressBar, Divider, ActivityIndicator } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useUser } from '../../src/features/auth/hooks/useUser';
import { useLevelProgress } from '../../src/features/leveling/hooks/useLevelProgress';
import { useAuthStore } from '../../src/features/auth/stores/authStore';
import { ENUM_DISPLAY_NAMES } from '../../src/shared/types/enums';

export default function ProfileScreen() {
  const router = useRouter();
  const { data: user, isLoading: userLoading } = useUser();
  const { data: levelProgress } = useLevelProgress();
  const logout = useAuthStore((state) => state.logout);

  async function handleLogout() {
    await logout();
    router.replace('/(auth)/login');
  }

  if (userLoading || !user) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  const stats = user.characterStats;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text variant="headlineMedium" style={styles.name}>{user.displayName}</Text>
      <Text variant="bodyMedium" style={styles.email}>{user.login}</Text>

      {levelProgress && (
        <Card style={styles.card}>
          <Card.Content>
            <Text variant="titleMedium">Level {levelProgress.current_level}</Text>
            <ProgressBar progress={levelProgress.progress_percent / 100} style={styles.xpBar} />
            <Text variant="bodySmall">
              {levelProgress.xp_in_current_bracket} / {levelProgress.xp_to_next_level} XP to next level
            </Text>
            <Text variant="bodySmall">Total XP: {levelProgress.total_xp}</Text>
          </Card.Content>
        </Card>
      )}

      {stats && (
        <Card style={styles.card}>
          <Card.Content>
            <Text variant="titleMedium">Character Stats</Text>
            <View style={styles.statsRow}>
              <View style={styles.stat}>
                <Text variant="headlineSmall">{stats.strength}</Text>
                <Text variant="labelMedium">STR</Text>
              </View>
              <View style={styles.stat}>
                <Text variant="headlineSmall">{stats.dexterity}</Text>
                <Text variant="labelMedium">DEX</Text>
              </View>
              <View style={styles.stat}>
                <Text variant="headlineSmall">{stats.constitution}</Text>
                <Text variant="labelMedium">CON</Text>
              </View>
            </View>
          </Card.Content>
        </Card>
      )}

      <Card style={styles.card}>
        <Card.Content>
          <Text variant="titleMedium">Profile</Text>
          <Divider style={styles.divider} />
          <ProfileRow label="Height" value={`${user.height} cm`} />
          <ProfileRow label="Weight" value={`${user.weight} kg`} />
          {user.gender && <ProfileRow label="Gender" value={ENUM_DISPLAY_NAMES[user.gender]} />}
          {user.workoutType && <ProfileRow label="Workout Type" value={ENUM_DISPLAY_NAMES[user.workoutType]} />}
          {user.activityLevel && <ProfileRow label="Activity Level" value={ENUM_DISPLAY_NAMES[user.activityLevel]} />}
          {user.desiredGoal && <ProfileRow label="Goal" value={ENUM_DISPLAY_NAMES[user.desiredGoal]} />}
          {user.characterRace && <ProfileRow label="Race" value={ENUM_DISPLAY_NAMES[user.characterRace]} />}
          {user.trainingFrequency && <ProfileRow label="Training Freq" value={ENUM_DISPLAY_NAMES[user.trainingFrequency]} />}
          {user.lifestyle && <ProfileRow label="Lifestyle" value={ENUM_DISPLAY_NAMES[user.lifestyle]} />}
        </Card.Content>
      </Card>

      <View style={styles.navButtons}>
        <Button mode="contained" onPress={() => router.push('/(main)/health')} style={styles.navBtn}>Health Dashboard</Button>
        <Button mode="contained" onPress={() => router.push('/(main)/levels')} style={styles.navBtn}>XP Table</Button>
      </View>

      <Button mode="outlined" onPress={handleLogout} style={styles.logoutBtn}>Logout</Button>
    </ScrollView>
  );
}

function ProfileRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={profileRowStyles.row}>
      <Text variant="bodyMedium" style={profileRowStyles.label}>{label}</Text>
      <Text variant="bodyMedium">{value}</Text>
    </View>
  );
}

const profileRowStyles = StyleSheet.create({
  row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 },
  label: { opacity: 0.6 },
});

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 24, paddingBottom: 48 },
  name: { textAlign: 'center', marginBottom: 4 },
  email: { textAlign: 'center', opacity: 0.6, marginBottom: 24 },
  card: { marginBottom: 16 },
  xpBar: { marginVertical: 8 },
  statsRow: { flexDirection: 'row', justifyContent: 'space-around', marginTop: 12 },
  stat: { alignItems: 'center' },
  divider: { marginVertical: 8 },
  navButtons: { gap: 8, marginBottom: 16 },
  navBtn: { marginBottom: 8 },
  logoutBtn: { marginTop: 8 },
});
```

- [ ] **Step 4: Add Card, Divider, ActivityIndicator mocks to jest.mock.react-native-paper.js if missing**

Add to the mock file if not already present:

```javascript
Card: Object.assign(
  ({ children, style }) => React.createElement('View', { style }, children),
  { Content: ({ children }) => React.createElement('View', null, children) }
),
Divider: ({ style }) => React.createElement('View', { style }),
ActivityIndicator: ({ size }) => React.createElement('View', { accessibilityRole: 'progressbar' }),
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/ProfileScreen.test.tsx --no-cache
```

Expected: 4 tests PASS.

---

### Task 7: Levels Screen

**Files:**
- Create: `app/(main)/levels.tsx`
- Test: `src/features/leveling/__tests__/LevelsScreen.test.tsx`

- [ ] **Step 1: Write the failing test**

Create `src/features/leveling/__tests__/LevelsScreen.test.tsx`:

```typescript
import React from 'react';
import { render, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import LevelsScreen from '../../../../app/(main)/levels';
import { levelingApi } from '../api/levelingApi';
import { useAuthStore } from '../../auth/stores/authStore';
import { lightTheme } from '../../../shared/theme';

jest.mock('../api/levelingApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ back: jest.fn() }),
}));

function renderWithProviders(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  useAuthStore.setState({ isAuthenticated: true, isLoading: false, token: 'test', user: null });
  return render(
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={lightTheme}>{ui}</PaperProvider>
    </QueryClientProvider>
  );
}

describe('LevelsScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (levelingApi.getTable as jest.Mock).mockResolvedValue([
      { level: 1, xp_required: 32, total_xp_required: 32 },
      { level: 2, xp_required: 73, total_xp_required: 105 },
      { level: 3, xp_required: 122, total_xp_required: 227 },
    ]);
    (levelingApi.getProgress as jest.Mock).mockResolvedValue({
      current_level: 2, total_xp: 80, xp_in_current_bracket: 48,
      xp_to_next_level: 73, progress_percent: 65.8,
    });
  });

  it('displays level table header', async () => {
    const { getByText } = renderWithProviders(<LevelsScreen />);
    await waitFor(() => {
      expect(getByText('XP Table')).toBeTruthy();
    });
  });

  it('displays current level progress', async () => {
    const { getByText } = renderWithProviders(<LevelsScreen />);
    await waitFor(() => {
      expect(getByText(/Level 2/)).toBeTruthy();
    });
  });
});
```

- [ ] **Step 2: Implement levels screen**

Create `app/(main)/levels.tsx`:

```typescript
import React from 'react';
import { View, StyleSheet, FlatList } from 'react-native';
import { Text, Card, ProgressBar, ActivityIndicator } from 'react-native-paper';
import { useLevelTable } from '../../src/features/leveling/hooks/useLevelTable';
import { useLevelProgress } from '../../src/features/leveling/hooks/useLevelProgress';

export default function LevelsScreen() {
  const { data: table, isLoading: tableLoading } = useLevelTable();
  const { data: progress } = useLevelProgress();

  if (tableLoading) {
    return <View style={styles.center}><ActivityIndicator size="large" /></View>;
  }

  return (
    <View style={styles.container}>
      <Text variant="headlineMedium" style={styles.title}>XP Table</Text>

      {progress && (
        <Card style={styles.progressCard}>
          <Card.Content>
            <Text variant="titleMedium">Current: Level {progress.current_level}</Text>
            <ProgressBar progress={progress.progress_percent / 100} style={styles.bar} />
            <Text variant="bodySmall">
              {progress.xp_in_current_bracket} / {progress.xp_to_next_level} XP ({progress.progress_percent.toFixed(1)}%)
            </Text>
            <Text variant="bodySmall">Total XP: {progress.total_xp}</Text>
          </Card.Content>
        </Card>
      )}

      <FlatList
        data={table}
        keyExtractor={(item) => String(item.level)}
        renderItem={({ item }) => {
          const isCurrent = progress && item.level === progress.current_level;
          return (
            <View style={[styles.row, isCurrent && styles.currentRow]}>
              <Text variant="bodyMedium" style={styles.levelCol}>Lv {item.level}</Text>
              <Text variant="bodyMedium" style={styles.xpCol}>{item.xp_required} XP</Text>
              <Text variant="bodySmall" style={styles.totalCol}>Total: {item.total_xp_required}</Text>
            </View>
          );
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 16 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  title: { textAlign: 'center', marginBottom: 16 },
  progressCard: { marginBottom: 16 },
  bar: { marginVertical: 8 },
  row: { flexDirection: 'row', paddingVertical: 8, paddingHorizontal: 12, borderBottomWidth: 1, borderBottomColor: '#eee' },
  currentRow: { backgroundColor: '#E8DEF8' },
  levelCol: { flex: 1 },
  xpCol: { flex: 1, textAlign: 'center' },
  totalCol: { flex: 1, textAlign: 'right', opacity: 0.6 },
});
```

- [ ] **Step 3: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/leveling/__tests__/LevelsScreen.test.tsx --no-cache
```

Expected: 2 tests PASS.

---

### Task 8: Run Full Test Suite

- [ ] **Step 1: Run all tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest --no-cache --verbose
```

Expected: All tests PASS (previous 57 + new ~16 = ~73 total).

- [ ] **Step 2: Verify no TypeScript errors**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx tsc --noEmit 2>&1 | head -20
```
