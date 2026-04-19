# Plan 1: React Native Foundation + Auth

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Set up the Expo React Native project with shared infrastructure (Axios, auth interceptors, Zustand store, theme, config) and implement login + registration screens with full test coverage.

**Architecture:** Expo Router file-based navigation with route groups: `(auth)` for public screens, `(onboarding)` placeholder, `(main)` placeholder. Axios with JWT interceptors for API layer. Zustand for auth state (token + user). TanStack Query for server state. React Native Paper MD3 theme.

**Tech Stack:** Expo SDK 52+, Expo Router, TypeScript strict, Axios, Zustand, TanStack Query v5, React Native Paper, expo-secure-store, Jest, React Native Testing Library

**Spec reference:** `docs/superpowers/specs/2026-04-04-react-native-rewrite-design.md`

---

### Task 1: Initialize Expo Project

**Files:**
- Create: `rpgfit-mobile/` (entire project scaffold)
- Create: `rpgfit-mobile/tsconfig.json`
- Create: `rpgfit-mobile/app.json`

- [ ] **Step 1: Create Expo project with TypeScript template**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit
npx create-expo-app@latest rpgfit-mobile --template blank-typescript
```

Expected: Project created in `rpgfit-mobile/` with TypeScript config.

- [ ] **Step 2: Install core dependencies**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx expo install expo-router expo-secure-store expo-linking expo-constants expo-status-bar react-native-safe-area-context react-native-screens react-native-paper react-native-vector-icons @react-native-async-storage/async-storage
npm install axios zustand @tanstack/react-query
npm install --save-dev jest @testing-library/react-native @testing-library/jest-native @types/react @types/jest
```

- [ ] **Step 3: Configure app.json for Expo Router**

Update `rpgfit-mobile/app.json`:

```json
{
  "expo": {
    "name": "RPGFit",
    "slug": "rpgfit",
    "version": "1.0.0",
    "orientation": "portrait",
    "scheme": "rpgfit",
    "platforms": ["ios", "android"],
    "plugins": ["expo-router", "expo-secure-store"],
    "experiments": {
      "typedRoutes": true
    }
  }
}
```

- [ ] **Step 4: Configure tsconfig.json for strict mode and path aliases**

Update `rpgfit-mobile/tsconfig.json`:

```json
{
  "extends": "expo/tsconfig.base",
  "compilerOptions": {
    "strict": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"],
      "@features/*": ["src/features/*"],
      "@shared/*": ["src/shared/*"]
    }
  },
  "include": ["**/*.ts", "**/*.tsx", "src/**/*.ts", "src/**/*.tsx", "app/**/*.ts", "app/**/*.tsx"]
}
```

- [ ] **Step 5: Create source directory structure**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
mkdir -p src/shared/api
mkdir -p src/shared/config
mkdir -p src/shared/theme
mkdir -p src/shared/types
mkdir -p src/shared/hooks
mkdir -p src/shared/components
mkdir -p src/shared/utils
mkdir -p src/features/auth/api
mkdir -p src/features/auth/hooks
mkdir -p src/features/auth/types
mkdir -p src/features/auth/stores
mkdir -p src/features/auth/__tests__
mkdir -p app/\(auth\)
mkdir -p app/\(onboarding\)
mkdir -p app/\(main\)
```

- [ ] **Step 6: Verify project compiles**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx expo export --platform web --output-dir /tmp/rpgfit-check 2>&1 | head -20
```

Expected: No TypeScript errors.

- [ ] **Step 7: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git init
git add -A
git commit -m "feat: initialize Expo project with TypeScript and core dependencies"
```

---

### Task 2: Environment Configuration

**Files:**
- Create: `src/shared/config/environment.ts`
- Test: `src/shared/config/__tests__/environment.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/shared/config/__tests__/environment.test.ts`:

```typescript
import { getConfig, Environment } from '../environment';

describe('getConfig', () => {
  it('returns development config with correct base URL', () => {
    const config = getConfig(Environment.Development);
    expect(config.apiBaseUrl).toBe('https://rpgfit.local:8443');
    expect(config.apiTimeout).toBe(30000);
    expect(config.enableLogging).toBe(true);
  });

  it('returns staging config with correct base URL', () => {
    const config = getConfig(Environment.Staging);
    expect(config.apiBaseUrl).toBe('https://staging-api.rpgfit.com');
    expect(config.apiTimeout).toBe(15000);
    expect(config.enableLogging).toBe(true);
  });

  it('returns production config with correct base URL', () => {
    const config = getConfig(Environment.Production);
    expect(config.apiBaseUrl).toBe('https://api.rpgfit.com');
    expect(config.apiTimeout).toBe(10000);
    expect(config.enableLogging).toBe(false);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/config/__tests__/environment.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Write minimal implementation**

Create `src/shared/config/environment.ts`:

```typescript
export enum Environment {
  Development = 'development',
  Staging = 'staging',
  Production = 'production',
}

export interface AppConfig {
  apiBaseUrl: string;
  apiTimeout: number;
  enableLogging: boolean;
}

const configs: Record<Environment, AppConfig> = {
  [Environment.Development]: {
    apiBaseUrl: 'https://rpgfit.local:8443',
    apiTimeout: 30000,
    enableLogging: true,
  },
  [Environment.Staging]: {
    apiBaseUrl: 'https://staging-api.rpgfit.com',
    apiTimeout: 15000,
    enableLogging: true,
  },
  [Environment.Production]: {
    apiBaseUrl: 'https://api.rpgfit.com',
    apiTimeout: 10000,
    enableLogging: false,
  },
};

export const CURRENT_ENV = Environment.Development;

export function getConfig(env: Environment = CURRENT_ENV): AppConfig {
  return configs[env];
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/config/__tests__/environment.test.ts --no-cache
```

Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/shared/config/
git commit -m "feat: add environment configuration with dev/staging/prod configs"
```

---

### Task 3: Shared TypeScript Types and Enums

**Files:**
- Create: `src/shared/types/enums.ts`
- Create: `src/shared/types/user.ts`
- Test: `src/shared/types/__tests__/enums.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/shared/types/__tests__/enums.test.ts`:

```typescript
import {
  CharacterRace,
  WorkoutType,
  ActivityLevel,
  DesiredGoal,
  Gender,
  TrainingFrequency,
  Lifestyle,
} from '../enums';

describe('Enums', () => {
  describe('CharacterRace', () => {
    it('has 5 values matching backend', () => {
      expect(Object.values(CharacterRace)).toHaveLength(5);
      expect(CharacterRace.Human).toBe('human');
      expect(CharacterRace.Orc).toBe('orc');
      expect(CharacterRace.Dwarf).toBe('dwarf');
      expect(CharacterRace.DarkElf).toBe('dark_elf');
      expect(CharacterRace.LightElf).toBe('light_elf');
    });
  });

  describe('WorkoutType', () => {
    it('has 6 values matching backend', () => {
      expect(Object.values(WorkoutType)).toHaveLength(6);
      expect(WorkoutType.Strength).toBe('strength');
      expect(WorkoutType.Cardio).toBe('cardio');
      expect(WorkoutType.Crossfit).toBe('crossfit');
      expect(WorkoutType.Gymnastics).toBe('gymnastics');
      expect(WorkoutType.MartialArts).toBe('martial_arts');
      expect(WorkoutType.Yoga).toBe('yoga');
    });
  });

  describe('ActivityLevel', () => {
    it('has 5 values matching backend', () => {
      expect(Object.values(ActivityLevel)).toHaveLength(5);
      expect(ActivityLevel.Sedentary).toBe('sedentary');
      expect(ActivityLevel.Light).toBe('light');
      expect(ActivityLevel.Moderate).toBe('moderate');
      expect(ActivityLevel.Active).toBe('active');
      expect(ActivityLevel.VeryActive).toBe('very_active');
    });
  });

  describe('DesiredGoal', () => {
    it('has 3 values matching backend', () => {
      expect(Object.values(DesiredGoal)).toHaveLength(3);
      expect(DesiredGoal.LoseWeight).toBe('lose_weight');
      expect(DesiredGoal.GainMass).toBe('gain_mass');
      expect(DesiredGoal.Maintain).toBe('maintain');
    });
  });

  describe('Gender', () => {
    it('has 2 values matching backend', () => {
      expect(Object.values(Gender)).toHaveLength(2);
      expect(Gender.Male).toBe('male');
      expect(Gender.Female).toBe('female');
    });
  });

  describe('TrainingFrequency', () => {
    it('has 4 values matching backend', () => {
      expect(Object.values(TrainingFrequency)).toHaveLength(4);
      expect(TrainingFrequency.None).toBe('none');
      expect(TrainingFrequency.Light).toBe('light');
      expect(TrainingFrequency.Moderate).toBe('moderate');
      expect(TrainingFrequency.Heavy).toBe('heavy');
    });
  });

  describe('Lifestyle', () => {
    it('has 4 values matching backend', () => {
      expect(Object.values(Lifestyle)).toHaveLength(4);
      expect(Lifestyle.Sedentary).toBe('sedentary');
      expect(Lifestyle.Moderate).toBe('moderate');
      expect(Lifestyle.Active).toBe('active');
      expect(Lifestyle.VeryActive).toBe('very_active');
    });
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/types/__tests__/enums.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Write enums implementation**

Create `src/shared/types/enums.ts`:

```typescript
export enum CharacterRace {
  Human = 'human',
  Orc = 'orc',
  Dwarf = 'dwarf',
  DarkElf = 'dark_elf',
  LightElf = 'light_elf',
}

export enum WorkoutType {
  Strength = 'strength',
  Cardio = 'cardio',
  Crossfit = 'crossfit',
  Gymnastics = 'gymnastics',
  MartialArts = 'martial_arts',
  Yoga = 'yoga',
}

export enum ActivityLevel {
  Sedentary = 'sedentary',
  Light = 'light',
  Moderate = 'moderate',
  Active = 'active',
  VeryActive = 'very_active',
}

export enum DesiredGoal {
  LoseWeight = 'lose_weight',
  GainMass = 'gain_mass',
  Maintain = 'maintain',
}

export enum Gender {
  Male = 'male',
  Female = 'female',
}

export enum TrainingFrequency {
  None = 'none',
  Light = 'light',
  Moderate = 'moderate',
  Heavy = 'heavy',
}

export enum Lifestyle {
  Sedentary = 'sedentary',
  Moderate = 'moderate',
  Active = 'active',
  VeryActive = 'very_active',
}

export const ENUM_DISPLAY_NAMES: Record<string, string> = {
  // CharacterRace
  human: 'Human',
  orc: 'Orc',
  dwarf: 'Dwarf',
  dark_elf: 'Dark Elf',
  light_elf: 'Light Elf',
  // WorkoutType
  strength: 'Strength',
  cardio: 'Cardio',
  crossfit: 'CrossFit',
  gymnastics: 'Gymnastics',
  martial_arts: 'Martial Arts',
  yoga: 'Yoga',
  // ActivityLevel
  sedentary: 'Sedentary',
  light: 'Light',
  moderate: 'Moderate',
  active: 'Active',
  very_active: 'Very Active',
  // DesiredGoal
  lose_weight: 'Lose Weight',
  gain_mass: 'Gain Mass',
  maintain: 'Maintain',
  // Gender
  male: 'Male',
  female: 'Female',
  // TrainingFrequency
  none: 'None',
  heavy: 'Heavy',
  // Lifestyle (shares sedentary, moderate, active, very_active with above)
};
```

- [ ] **Step 4: Write User type**

Create `src/shared/types/user.ts`:

```typescript
import {
  CharacterRace,
  WorkoutType,
  ActivityLevel,
  DesiredGoal,
  Gender,
  TrainingFrequency,
  Lifestyle,
} from './enums';

export interface CharacterStats {
  strength: number;
  dexterity: number;
  constitution: number;
  level: number;
  totalXp: number;
}

export interface User {
  id: string;
  login: string;
  displayName: string;
  height: number;
  weight: number;
  workoutType: WorkoutType | null;
  activityLevel: ActivityLevel | null;
  desiredGoal: DesiredGoal | null;
  characterRace: CharacterRace | null;
  gender: Gender | null;
  trainingFrequency: TrainingFrequency | null;
  lifestyle: Lifestyle | null;
  preferredWorkouts: string[];
  onboardingCompleted: boolean;
  characterStats: CharacterStats | null;
}

export interface LevelProgress {
  currentLevel: number;
  totalXp: number;
  xpInCurrentBracket: number;
  xpToNextLevel: number;
  progressPercent: number;
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/types/__tests__/enums.test.ts --no-cache
```

Expected: 7 tests PASS.

- [ ] **Step 6: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/shared/types/
git commit -m "feat: add shared TypeScript types and enums matching backend"
```

---

### Task 4: Axios Client with Auth Interceptors

**Files:**
- Create: `src/shared/api/client.ts`
- Create: `src/shared/api/interceptors.ts`
- Test: `src/shared/api/__tests__/client.test.ts`
- Test: `src/shared/api/__tests__/interceptors.test.ts`

- [ ] **Step 1: Write the failing test for interceptors**

Create `src/shared/api/__tests__/interceptors.test.ts`:

```typescript
import axios, { AxiosHeaders, InternalAxiosRequestConfig } from 'axios';
import { attachToken, handleUnauthorized } from '../interceptors';

// Mock expo-secure-store
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

import * as SecureStore from 'expo-secure-store';

describe('attachToken interceptor', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('attaches Bearer token when token exists', async () => {
    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue('test-jwt-token');

    const config: InternalAxiosRequestConfig = {
      headers: new AxiosHeaders(),
    } as InternalAxiosRequestConfig;

    const result = await attachToken(config);
    expect(result.headers.Authorization).toBe('Bearer test-jwt-token');
  });

  it('does not attach token when token is null', async () => {
    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue(null);

    const config: InternalAxiosRequestConfig = {
      headers: new AxiosHeaders(),
    } as InternalAxiosRequestConfig;

    const result = await attachToken(config);
    expect(result.headers.Authorization).toBeUndefined();
  });

  it('does not attach token when token is empty string', async () => {
    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue('');

    const config: InternalAxiosRequestConfig = {
      headers: new AxiosHeaders(),
    } as InternalAxiosRequestConfig;

    const result = await attachToken(config);
    expect(result.headers.Authorization).toBeUndefined();
  });
});

describe('handleUnauthorized interceptor', () => {
  it('clears tokens on 401 response', async () => {
    const error = {
      response: { status: 401 },
      isAxiosError: true,
    };

    await expect(handleUnauthorized(error)).rejects.toEqual(error);
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('auth_token');
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('refresh_token');
  });

  it('does not clear tokens on non-401 errors', async () => {
    const error = {
      response: { status: 500 },
      isAxiosError: true,
    };

    await expect(handleUnauthorized(error)).rejects.toEqual(error);
    expect(SecureStore.deleteItemAsync).not.toHaveBeenCalled();
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/api/__tests__/interceptors.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Implement interceptors**

Create `src/shared/api/interceptors.ts`:

```typescript
import { InternalAxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'auth_token';
const REFRESH_TOKEN_KEY = 'refresh_token';

export async function attachToken(
  config: InternalAxiosRequestConfig
): Promise<InternalAxiosRequestConfig> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  if (token && token.length > 0) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}

export async function handleUnauthorized(error: unknown): Promise<never> {
  const axiosError = error as { response?: { status: number } };
  if (axiosError.response?.status === 401) {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
  }
  throw error;
}
```

- [ ] **Step 4: Run interceptor tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/api/__tests__/interceptors.test.ts --no-cache
```

Expected: 5 tests PASS.

- [ ] **Step 5: Write the failing test for API client**

Create `src/shared/api/__tests__/client.test.ts`:

```typescript
import { apiClient } from '../client';

// Mock expo-secure-store
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

describe('apiClient', () => {
  it('is an axios instance with correct base URL', () => {
    // Development config: https://rpgfit.local:8443
    expect(apiClient.defaults.baseURL).toBe('https://rpgfit.local:8443');
  });

  it('has correct timeout from config', () => {
    expect(apiClient.defaults.timeout).toBe(30000);
  });

  it('has JSON content type header', () => {
    expect(apiClient.defaults.headers['Content-Type']).toBe('application/json');
  });
});
```

- [ ] **Step 6: Implement API client**

Create `src/shared/api/client.ts`:

```typescript
import axios from 'axios';
import { getConfig } from '../config/environment';
import { attachToken, handleUnauthorized } from './interceptors';

const config = getConfig();

export const apiClient = axios.create({
  baseURL: config.apiBaseUrl,
  timeout: config.apiTimeout,
  headers: {
    'Content-Type': 'application/json',
  },
});

apiClient.interceptors.request.use(attachToken);
apiClient.interceptors.response.use((response) => response, handleUnauthorized);
```

- [ ] **Step 7: Run all API tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/api/__tests__/ --no-cache
```

Expected: 8 tests PASS.

- [ ] **Step 8: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/shared/api/
git commit -m "feat: add Axios client with JWT interceptors and 401 handling"
```

---

### Task 5: Auth Zustand Store

**Files:**
- Create: `src/features/auth/stores/authStore.ts`
- Test: `src/features/auth/__tests__/authStore.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/features/auth/__tests__/authStore.test.ts`:

```typescript
import { useAuthStore } from '../stores/authStore';

// Mock expo-secure-store
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

import * as SecureStore from 'expo-secure-store';

describe('authStore', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({
      token: null,
      user: null,
      isAuthenticated: false,
      isLoading: true,
    });
  });

  it('starts with no token and loading state', () => {
    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);
    expect(state.isLoading).toBe(true);
  });

  it('setToken saves token to secure store and updates state', async () => {
    await useAuthStore.getState().setToken('new-jwt-token');

    expect(SecureStore.setItemAsync).toHaveBeenCalledWith('auth_token', 'new-jwt-token');
    const state = useAuthStore.getState();
    expect(state.token).toBe('new-jwt-token');
    expect(state.isAuthenticated).toBe(true);
    expect(state.isLoading).toBe(false);
  });

  it('logout clears token from secure store and resets state', async () => {
    // Set initial authenticated state
    useAuthStore.setState({ token: 'existing-token', isAuthenticated: true, isLoading: false });

    await useAuthStore.getState().logout();

    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('auth_token');
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('refresh_token');
    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it('initialize loads token from secure store', async () => {
    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue('stored-token');

    await useAuthStore.getState().initialize();

    const state = useAuthStore.getState();
    expect(state.token).toBe('stored-token');
    expect(state.isAuthenticated).toBe(true);
    expect(state.isLoading).toBe(false);
  });

  it('initialize with no stored token sets unauthenticated', async () => {
    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue(null);

    await useAuthStore.getState().initialize();

    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.isAuthenticated).toBe(false);
    expect(state.isLoading).toBe(false);
  });

  it('setUser updates user in state', () => {
    const mockUser = {
      id: 'uuid-1',
      login: 'test@test.com',
      displayName: 'TestUser',
      height: 180,
      weight: 80,
      workoutType: null,
      activityLevel: null,
      desiredGoal: null,
      characterRace: null,
      gender: null,
      trainingFrequency: null,
      lifestyle: null,
      preferredWorkouts: [],
      onboardingCompleted: false,
      characterStats: null,
    };

    useAuthStore.getState().setUser(mockUser);
    expect(useAuthStore.getState().user).toEqual(mockUser);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/authStore.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Write minimal implementation**

Create `src/features/auth/stores/authStore.ts`:

```typescript
import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';
import { User } from '@/shared/types/user';

const TOKEN_KEY = 'auth_token';
const REFRESH_TOKEN_KEY = 'refresh_token';

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  setToken: (token: string) => Promise<void>;
  setUser: (user: User) => void;
  logout: () => Promise<void>;
  initialize: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: null,
  user: null,
  isAuthenticated: false,
  isLoading: true,

  setToken: async (token: string) => {
    await SecureStore.setItemAsync(TOKEN_KEY, token);
    set({ token, isAuthenticated: true, isLoading: false });
  },

  setUser: (user: User) => {
    set({ user });
  },

  logout: async () => {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
    set({ token: null, user: null, isAuthenticated: false });
  },

  initialize: async () => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (token && token.length > 0) {
      set({ token, isAuthenticated: true, isLoading: false });
    } else {
      set({ token: null, isAuthenticated: false, isLoading: false });
    }
  },
}));
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/authStore.test.ts --no-cache
```

Expected: 6 tests PASS.

- [ ] **Step 5: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/features/auth/stores/ src/features/auth/__tests__/authStore.test.ts
git commit -m "feat: add Zustand auth store with secure token persistence"
```

---

### Task 6: Auth API Functions

**Files:**
- Create: `src/features/auth/types/requests.ts`
- Create: `src/features/auth/types/responses.ts`
- Create: `src/features/auth/api/authApi.ts`
- Test: `src/features/auth/__tests__/authApi.test.ts`

- [ ] **Step 1: Create request/response types**

Create `src/features/auth/types/requests.ts`:

```typescript
import {
  CharacterRace,
  WorkoutType,
  ActivityLevel,
  DesiredGoal,
} from '@/shared/types/enums';

export interface LoginRequest {
  login: string;
  password: string;
}

export interface RegistrationRequest {
  login: string;
  password: string;
  display_name: string;
  height: number;
  weight: number;
  workout_type: WorkoutType;
  activity_level: ActivityLevel;
  desired_goal: DesiredGoal;
  character_race: CharacterRace;
}
```

Create `src/features/auth/types/responses.ts`:

```typescript
export interface LoginResponse {
  token: string;
}

export interface RegistrationResponse {
  id: string;
  login: string;
  display_name: string;
}

export interface UserResponse {
  id: string;
  login: string;
  display_name: string;
  height: number;
  weight: number;
  workout_type: string | null;
  activity_level: string | null;
  desired_goal: string | null;
  character_race: string | null;
  gender: string | null;
  training_frequency: string | null;
  lifestyle: string | null;
  preferred_workouts: string[];
  onboarding_completed: boolean;
  character_stats: {
    strength: number;
    dexterity: number;
    constitution: number;
    level: number;
    total_xp: number;
  } | null;
}
```

- [ ] **Step 2: Write the failing test for authApi**

Create `src/features/auth/__tests__/authApi.test.ts`:

```typescript
import { authApi } from '../api/authApi';
import { apiClient } from '@/shared/api/client';
import { WorkoutType, ActivityLevel, DesiredGoal, CharacterRace } from '@/shared/types/enums';

// Mock the apiClient
jest.mock('@/shared/api/client', () => ({
  apiClient: {
    post: jest.fn(),
    get: jest.fn(),
  },
}));

// Mock expo-secure-store (needed by client import chain)
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

describe('authApi', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('login', () => {
    it('sends POST to /api/login with credentials', async () => {
      (apiClient.post as jest.Mock).mockResolvedValue({
        data: { token: 'jwt-token' },
      });

      const result = await authApi.login({ login: 'user@test.com', password: 'password123' });

      expect(apiClient.post).toHaveBeenCalledWith('/api/login', {
        login: 'user@test.com',
        password: 'password123',
      });
      expect(result.token).toBe('jwt-token');
    });
  });

  describe('register', () => {
    it('sends POST to /api/v1/auth/register with full user data', async () => {
      (apiClient.post as jest.Mock).mockResolvedValue({
        data: { id: 'uuid-1', login: 'user@test.com', display_name: 'TestUser' },
      });

      const request = {
        login: 'user@test.com',
        password: 'password123',
        display_name: 'TestUser',
        height: 180,
        weight: 80,
        workout_type: WorkoutType.Cardio,
        activity_level: ActivityLevel.Moderate,
        desired_goal: DesiredGoal.Maintain,
        character_race: CharacterRace.Human,
      };

      const result = await authApi.register(request);

      expect(apiClient.post).toHaveBeenCalledWith('/api/v1/auth/register', request);
      expect(result.id).toBe('uuid-1');
    });
  });

  describe('getProfile', () => {
    it('sends GET to /api/profile', async () => {
      (apiClient.get as jest.Mock).mockResolvedValue({
        data: { id: 'uuid-1', login: 'user@test.com' },
      });

      const result = await authApi.getProfile();

      expect(apiClient.get).toHaveBeenCalledWith('/api/profile');
      expect(result.id).toBe('uuid-1');
    });
  });

  describe('getFullUser', () => {
    it('sends GET to /api/user', async () => {
      (apiClient.get as jest.Mock).mockResolvedValue({
        data: {
          id: 'uuid-1',
          login: 'user@test.com',
          display_name: 'TestUser',
          onboarding_completed: true,
          character_stats: { strength: 10, dexterity: 10, constitution: 10, level: 1, total_xp: 0 },
        },
      });

      const result = await authApi.getFullUser();

      expect(apiClient.get).toHaveBeenCalledWith('/api/user');
      expect(result.onboarding_completed).toBe(true);
    });
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/authApi.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 4: Implement authApi**

Create `src/features/auth/api/authApi.ts`:

```typescript
import { apiClient } from '@/shared/api/client';
import { LoginRequest, RegistrationRequest } from '../types/requests';
import { LoginResponse, RegistrationResponse, UserResponse } from '../types/responses';

export const authApi = {
  async login(data: LoginRequest): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/api/login', data);
    return response.data;
  },

  async register(data: RegistrationRequest): Promise<RegistrationResponse> {
    const response = await apiClient.post<RegistrationResponse>('/api/v1/auth/register', data);
    return response.data;
  },

  async getProfile(): Promise<UserResponse> {
    const response = await apiClient.get<UserResponse>('/api/profile');
    return response.data;
  },

  async getFullUser(): Promise<UserResponse> {
    const response = await apiClient.get<UserResponse>('/api/user');
    return response.data;
  },
};
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/authApi.test.ts --no-cache
```

Expected: 4 tests PASS.

- [ ] **Step 6: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/features/auth/types/ src/features/auth/api/
git commit -m "feat: add auth API functions with request/response types"
```

---

### Task 7: Auth TanStack Query Hooks

**Files:**
- Create: `src/features/auth/hooks/useLogin.ts`
- Create: `src/features/auth/hooks/useRegister.ts`
- Create: `src/features/auth/hooks/useUser.ts`
- Test: `src/features/auth/__tests__/useLogin.test.ts`
- Test: `src/features/auth/__tests__/useRegister.test.ts`

- [ ] **Step 1: Write the failing test for useLogin**

Create `src/features/auth/__tests__/useLogin.test.ts`:

```typescript
import { renderHook, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useLogin } from '../hooks/useLogin';
import { authApi } from '../api/authApi';
import { useAuthStore } from '../stores/authStore';

jest.mock('../api/authApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(QueryClientProvider, { client: queryClient }, children);
}

describe('useLogin', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ token: null, user: null, isAuthenticated: false, isLoading: false });
  });

  it('calls authApi.login and stores token on success', async () => {
    (authApi.login as jest.Mock).mockResolvedValue({ token: 'new-jwt-token' });

    const { result } = renderHook(() => useLogin(), { wrapper: createWrapper() });

    result.current.mutate({ login: 'user@test.com', password: 'password123' });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(authApi.login).toHaveBeenCalledWith({ login: 'user@test.com', password: 'password123' });
  });

  it('sets error state on API failure', async () => {
    (authApi.login as jest.Mock).mockRejectedValue(new Error('Invalid credentials'));

    const { result } = renderHook(() => useLogin(), { wrapper: createWrapper() });

    result.current.mutate({ login: 'user@test.com', password: 'wrong' });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error?.message).toBe('Invalid credentials');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/useLogin.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Implement useLogin hook**

Create `src/features/auth/hooks/useLogin.ts`:

```typescript
import { useMutation } from '@tanstack/react-query';
import { authApi } from '../api/authApi';
import { useAuthStore } from '../stores/authStore';
import { LoginRequest } from '../types/requests';

export function useLogin() {
  const setToken = useAuthStore((state) => state.setToken);

  return useMutation({
    mutationFn: (data: LoginRequest) => authApi.login(data),
    onSuccess: async (response) => {
      await setToken(response.token);
    },
  });
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/useLogin.test.ts --no-cache
```

Expected: 2 tests PASS.

- [ ] **Step 5: Write the failing test for useRegister**

Create `src/features/auth/__tests__/useRegister.test.ts`:

```typescript
import { renderHook, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useRegister } from '../hooks/useRegister';
import { authApi } from '../api/authApi';
import { WorkoutType, ActivityLevel, DesiredGoal, CharacterRace } from '@/shared/types/enums';

jest.mock('../api/authApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(QueryClientProvider, { client: queryClient }, children);
}

describe('useRegister', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('calls authApi.register with full registration data', async () => {
    (authApi.register as jest.Mock).mockResolvedValue({
      id: 'uuid-1',
      login: 'user@test.com',
      display_name: 'TestUser',
    });

    const { result } = renderHook(() => useRegister(), { wrapper: createWrapper() });

    const request = {
      login: 'user@test.com',
      password: 'password123',
      display_name: 'TestUser',
      height: 180,
      weight: 80,
      workout_type: WorkoutType.Cardio,
      activity_level: ActivityLevel.Moderate,
      desired_goal: DesiredGoal.Maintain,
      character_race: CharacterRace.Human,
    };

    result.current.mutate(request);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(authApi.register).toHaveBeenCalledWith(request);
  });

  it('sets error state on registration failure', async () => {
    (authApi.register as jest.Mock).mockRejectedValue(new Error('Email already exists'));

    const { result } = renderHook(() => useRegister(), { wrapper: createWrapper() });

    result.current.mutate({
      login: 'existing@test.com',
      password: 'password123',
      display_name: 'TestUser',
      height: 180,
      weight: 80,
      workout_type: WorkoutType.Cardio,
      activity_level: ActivityLevel.Moderate,
      desired_goal: DesiredGoal.Maintain,
      character_race: CharacterRace.Human,
    });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error?.message).toBe('Email already exists');
  });
});
```

- [ ] **Step 6: Implement useRegister hook**

Create `src/features/auth/hooks/useRegister.ts`:

```typescript
import { useMutation } from '@tanstack/react-query';
import { authApi } from '../api/authApi';
import { RegistrationRequest } from '../types/requests';

export function useRegister() {
  return useMutation({
    mutationFn: (data: RegistrationRequest) => authApi.register(data),
  });
}
```

- [ ] **Step 7: Implement useUser hook**

Create `src/features/auth/hooks/useUser.ts`:

```typescript
import { useQuery } from '@tanstack/react-query';
import { authApi } from '../api/authApi';
import { useAuthStore } from '../stores/authStore';
import { User } from '@/shared/types/user';
import { UserResponse } from '../types/responses';
import {
  WorkoutType,
  ActivityLevel,
  DesiredGoal,
  CharacterRace,
  Gender,
  TrainingFrequency,
  Lifestyle,
} from '@/shared/types/enums';

function mapUserResponse(data: UserResponse): User {
  return {
    id: data.id,
    login: data.login,
    displayName: data.display_name,
    height: data.height,
    weight: data.weight,
    workoutType: (data.workout_type as WorkoutType) ?? null,
    activityLevel: (data.activity_level as ActivityLevel) ?? null,
    desiredGoal: (data.desired_goal as DesiredGoal) ?? null,
    characterRace: (data.character_race as CharacterRace) ?? null,
    gender: (data.gender as Gender) ?? null,
    trainingFrequency: (data.training_frequency as TrainingFrequency) ?? null,
    lifestyle: (data.lifestyle as Lifestyle) ?? null,
    preferredWorkouts: data.preferred_workouts ?? [],
    onboardingCompleted: data.onboarding_completed,
    characterStats: data.character_stats
      ? {
          strength: data.character_stats.strength,
          dexterity: data.character_stats.dexterity,
          constitution: data.character_stats.constitution,
          level: data.character_stats.level,
          totalXp: data.character_stats.total_xp,
        }
      : null,
  };
}

export function useUser() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const setUser = useAuthStore((state) => state.setUser);

  return useQuery({
    queryKey: ['user'],
    queryFn: async () => {
      const data = await authApi.getFullUser();
      const user = mapUserResponse(data);
      setUser(user);
      return user;
    },
    enabled: isAuthenticated,
  });
}
```

- [ ] **Step 8: Run all auth tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/ --no-cache
```

Expected: All tests PASS.

- [ ] **Step 9: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/features/auth/hooks/ src/features/auth/__tests__/useLogin.test.ts src/features/auth/__tests__/useRegister.test.ts
git commit -m "feat: add auth TanStack Query hooks (useLogin, useRegister, useUser)"
```

---

### Task 8: Validation Utilities

**Files:**
- Create: `src/shared/utils/validation.ts`
- Test: `src/shared/utils/__tests__/validation.test.ts`

- [ ] **Step 1: Write the failing test**

Create `src/shared/utils/__tests__/validation.test.ts`:

```typescript
import {
  validateEmail,
  validatePassword,
  validateDisplayName,
  validatePositiveNumber,
  validateOnboardingDisplayName,
} from '../validation';

describe('validateEmail', () => {
  it('returns null for valid email', () => {
    expect(validateEmail('user@test.com')).toBeNull();
    expect(validateEmail('a@b.c')).toBeNull();
  });

  it('returns error for empty email', () => {
    expect(validateEmail('')).toBe('Email is required');
  });

  it('returns error for invalid email', () => {
    expect(validateEmail('not-an-email')).toBe('Invalid email format');
    expect(validateEmail('user@')).toBe('Invalid email format');
    expect(validateEmail('@test.com')).toBe('Invalid email format');
  });
});

describe('validatePassword', () => {
  it('returns null for valid password (8+ chars)', () => {
    expect(validatePassword('12345678')).toBeNull();
    expect(validatePassword('longpassword123')).toBeNull();
  });

  it('returns error for empty password', () => {
    expect(validatePassword('')).toBe('Password is required');
  });

  it('returns error for short password', () => {
    expect(validatePassword('1234567')).toBe('Password must be at least 8 characters');
  });
});

describe('validateDisplayName', () => {
  it('returns null for valid name (3+ chars)', () => {
    expect(validateDisplayName('Bob')).toBeNull();
    expect(validateDisplayName('LongName123')).toBeNull();
  });

  it('returns error for empty name', () => {
    expect(validateDisplayName('')).toBe('Display name is required');
  });

  it('returns error for short name', () => {
    expect(validateDisplayName('AB')).toBe('Display name must be at least 3 characters');
  });
});

describe('validateOnboardingDisplayName', () => {
  it('returns null for valid Latin name', () => {
    expect(validateOnboardingDisplayName('TestUser')).toBeNull();
    expect(validateOnboardingDisplayName('user-name_01')).toBeNull();
    expect(validateOnboardingDisplayName('My Name')).toBeNull();
  });

  it('returns error for non-Latin characters', () => {
    expect(validateOnboardingDisplayName('Тест')).toBe('Only Latin letters, numbers, spaces, hyphens, and underscores');
  });

  it('returns error for short name', () => {
    expect(validateOnboardingDisplayName('AB')).toBe('Display name must be at least 3 characters');
  });

  it('returns error for name over 30 chars', () => {
    expect(validateOnboardingDisplayName('A'.repeat(31))).toBe('Display name must be at most 30 characters');
  });
});

describe('validatePositiveNumber', () => {
  it('returns null for positive numbers', () => {
    expect(validatePositiveNumber(1, 'Height')).toBeNull();
    expect(validatePositiveNumber(180.5, 'Height')).toBeNull();
  });

  it('returns error for zero', () => {
    expect(validatePositiveNumber(0, 'Height')).toBe('Height must be greater than 0');
  });

  it('returns error for negative', () => {
    expect(validatePositiveNumber(-5, 'Weight')).toBe('Weight must be greater than 0');
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/utils/__tests__/validation.test.ts --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Write minimal implementation**

Create `src/shared/utils/validation.ts`:

```typescript
const EMAIL_REGEX = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
const LATIN_ONLY_REGEX = /^[a-zA-Z0-9_\- ]+$/;

export function validateEmail(email: string): string | null {
  if (!email.trim()) return 'Email is required';
  if (!EMAIL_REGEX.test(email)) return 'Invalid email format';
  return null;
}

export function validatePassword(password: string): string | null {
  if (!password) return 'Password is required';
  if (password.length < 8) return 'Password must be at least 8 characters';
  return null;
}

export function validateDisplayName(name: string): string | null {
  if (!name.trim()) return 'Display name is required';
  if (name.trim().length < 3) return 'Display name must be at least 3 characters';
  return null;
}

export function validateOnboardingDisplayName(name: string): string | null {
  if (!name.trim()) return 'Display name is required';
  if (name.trim().length < 3) return 'Display name must be at least 3 characters';
  if (name.trim().length > 30) return 'Display name must be at most 30 characters';
  if (!LATIN_ONLY_REGEX.test(name.trim())) return 'Only Latin letters, numbers, spaces, hyphens, and underscores';
  return null;
}

export function validatePositiveNumber(value: number, fieldName: string): string | null {
  if (value <= 0) return `${fieldName} must be greater than 0`;
  return null;
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/shared/utils/__tests__/validation.test.ts --no-cache
```

Expected: 13 tests PASS.

- [ ] **Step 5: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/shared/utils/
git commit -m "feat: add validation utilities matching Flutter/backend rules"
```

---

### Task 9: React Native Paper Theme

**Files:**
- Create: `src/shared/theme/theme.ts`
- Create: `src/shared/theme/index.ts`

- [ ] **Step 1: Create theme matching Flutter deepPurple Material 3**

Create `src/shared/theme/theme.ts`:

```typescript
import { MD3LightTheme, MD3DarkTheme } from 'react-native-paper';

export const lightTheme = {
  ...MD3LightTheme,
  colors: {
    ...MD3LightTheme.colors,
    primary: '#673AB7',
    primaryContainer: '#E1BEE7',
    secondary: '#9C27B0',
    secondaryContainer: '#F3E5F5',
    background: '#FFFBFE',
    surface: '#FFFBFE',
    error: '#B3261E',
  },
};

export const darkTheme = {
  ...MD3DarkTheme,
  colors: {
    ...MD3DarkTheme.colors,
    primary: '#D1C4E9',
    primaryContainer: '#4A148C',
    secondary: '#CE93D8',
    secondaryContainer: '#4A148C',
    background: '#1C1B1F',
    surface: '#1C1B1F',
    error: '#F2B8B5',
  },
};
```

Create `src/shared/theme/index.ts`:

```typescript
export { lightTheme, darkTheme } from './theme';
```

- [ ] **Step 2: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add src/shared/theme/
git commit -m "feat: add React Native Paper MD3 theme (deepPurple, matching Flutter)"
```

---

### Task 10: Root Layout with Providers

**Files:**
- Create: `app/_layout.tsx`
- Create: `app/index.tsx`

- [ ] **Step 1: Create root layout with all providers**

Create `app/_layout.tsx`:

```typescript
import React, { useEffect } from 'react';
import { Slot } from 'expo-router';
import { PaperProvider } from 'react-native-paper';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { lightTheme } from '@/shared/theme';
import { useAuthStore } from '@/features/auth/stores/authStore';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000,
      retry: 2,
    },
  },
});

export default function RootLayout() {
  const initialize = useAuthStore((state) => state.initialize);

  useEffect(() => {
    initialize();
  }, [initialize]);

  return (
    <SafeAreaProvider>
      <QueryClientProvider client={queryClient}>
        <PaperProvider theme={lightTheme}>
          <Slot />
        </PaperProvider>
      </QueryClientProvider>
    </SafeAreaProvider>
  );
}
```

- [ ] **Step 2: Create index entry point with auth redirect**

Create `app/index.tsx`:

```typescript
import { Redirect } from 'expo-router';
import { ActivityIndicator, View } from 'react-native';
import { useAuthStore } from '@/features/auth/stores/authStore';

export default function Index() {
  const isLoading = useAuthStore((state) => state.isLoading);
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (isAuthenticated) {
    return <Redirect href="/(main)/profile" />;
  }

  return <Redirect href="/(auth)/login" />;
}
```

- [ ] **Step 3: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add app/_layout.tsx app/index.tsx
git commit -m "feat: add root layout with providers and auth-based routing"
```

---

### Task 11: Auth Layout and Login Screen

**Files:**
- Create: `app/(auth)/_layout.tsx`
- Create: `app/(auth)/login.tsx`
- Test: `src/features/auth/__tests__/LoginScreen.test.tsx`

- [ ] **Step 1: Create auth group layout**

Create `app/(auth)/_layout.tsx`:

```typescript
import { Stack } from 'expo-router';

export default function AuthLayout() {
  return (
    <Stack
      screenOptions={{
        headerShown: false,
      }}
    />
  );
}
```

- [ ] **Step 2: Write the failing test for LoginScreen**

Create `src/features/auth/__tests__/LoginScreen.test.tsx`:

```typescript
import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import LoginScreen from '../../../../app/(auth)/login';
import { authApi } from '../api/authApi';
import { lightTheme } from '@/shared/theme';

jest.mock('../api/authApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ replace: jest.fn() }),
  Link: ({ children }: { children: React.ReactNode }) => children,
}));

function renderWithProviders(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={lightTheme}>
        {ui}
      </PaperProvider>
    </QueryClientProvider>
  );
}

describe('LoginScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders email and password fields', () => {
    const { getByLabelText } = renderWithProviders(<LoginScreen />);
    expect(getByLabelText('Email')).toBeTruthy();
    expect(getByLabelText('Password')).toBeTruthy();
  });

  it('renders login button', () => {
    const { getByText } = renderWithProviders(<LoginScreen />);
    expect(getByText('Login')).toBeTruthy();
  });

  it('shows validation error when email is empty', async () => {
    const { getByText, getByLabelText } = renderWithProviders(<LoginScreen />);

    fireEvent.changeText(getByLabelText('Password'), 'password123');
    fireEvent.press(getByText('Login'));

    await waitFor(() => {
      expect(getByText('Email is required')).toBeTruthy();
    });
  });

  it('shows validation error when password is empty', async () => {
    const { getByText, getByLabelText } = renderWithProviders(<LoginScreen />);

    fireEvent.changeText(getByLabelText('Email'), 'user@test.com');
    fireEvent.press(getByText('Login'));

    await waitFor(() => {
      expect(getByText('Password is required')).toBeTruthy();
    });
  });

  it('calls login API with valid credentials', async () => {
    (authApi.login as jest.Mock).mockResolvedValue({ token: 'jwt-token' });

    const { getByText, getByLabelText } = renderWithProviders(<LoginScreen />);

    fireEvent.changeText(getByLabelText('Email'), 'user@test.com');
    fireEvent.changeText(getByLabelText('Password'), 'password123');
    fireEvent.press(getByText('Login'));

    await waitFor(() => {
      expect(authApi.login).toHaveBeenCalledWith({
        login: 'user@test.com',
        password: 'password123',
      });
    });
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/LoginScreen.test.tsx --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 4: Implement Login screen**

Create `app/(auth)/login.tsx`:

```typescript
import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { TextInput, Button, Text, Snackbar } from 'react-native-paper';
import { useRouter, Link } from 'expo-router';
import { useLogin } from '@/features/auth/hooks/useLogin';
import { validateEmail } from '@/shared/utils/validation';

export default function LoginScreen() {
  const router = useRouter();
  const loginMutation = useLogin();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errors, setErrors] = useState<{ email?: string; password?: string }>({});
  const [snackbar, setSnackbar] = useState('');

  function validate(): boolean {
    const newErrors: { email?: string; password?: string } = {};

    const emailError = validateEmail(email);
    if (emailError) newErrors.email = emailError;

    if (!password) newErrors.password = 'Password is required';

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }

  function handleLogin() {
    if (!validate()) return;

    loginMutation.mutate(
      { login: email.trim(), password },
      {
        onSuccess: () => {
          router.replace('/(main)/profile');
        },
        onError: (error) => {
          setSnackbar(error.message || 'Login failed');
        },
      }
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.content}>
        <Text variant="headlineLarge" style={styles.title}>
          RPGFit
        </Text>
        <Text variant="titleMedium" style={styles.subtitle}>
          Login to your account
        </Text>

        <TextInput
          label="Email"
          accessibilityLabel="Email"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          error={!!errors.email}
          disabled={loginMutation.isPending}
          style={styles.input}
        />
        {errors.email && <Text style={styles.error}>{errors.email}</Text>}

        <TextInput
          label="Password"
          accessibilityLabel="Password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          error={!!errors.password}
          disabled={loginMutation.isPending}
          style={styles.input}
        />
        {errors.password && <Text style={styles.error}>{errors.password}</Text>}

        <Button
          mode="contained"
          onPress={handleLogin}
          loading={loginMutation.isPending}
          disabled={loginMutation.isPending}
          style={styles.button}
        >
          Login
        </Button>

        <Link href="/(auth)/registration" asChild>
          <Button mode="text" style={styles.link}>
            Don't have an account? Register
          </Button>
        </Link>
      </ScrollView>

      <Snackbar
        visible={!!snackbar}
        onDismiss={() => setSnackbar('')}
        duration={3000}
      >
        {snackbar}
      </Snackbar>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flexGrow: 1, justifyContent: 'center', padding: 24 },
  title: { textAlign: 'center', marginBottom: 8 },
  subtitle: { textAlign: 'center', marginBottom: 32, opacity: 0.7 },
  input: { marginBottom: 4 },
  error: { color: '#B3261E', fontSize: 12, marginBottom: 8, marginLeft: 12 },
  button: { marginTop: 16 },
  link: { marginTop: 8 },
});
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/LoginScreen.test.tsx --no-cache
```

Expected: 5 tests PASS.

- [ ] **Step 6: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add app/\(auth\)/ src/features/auth/__tests__/LoginScreen.test.tsx
git commit -m "feat: add login screen with validation and API integration"
```

---

### Task 12: Registration Screen

**Files:**
- Create: `app/(auth)/registration.tsx`
- Test: `src/features/auth/__tests__/RegistrationScreen.test.tsx`

- [ ] **Step 1: Write the failing test**

Create `src/features/auth/__tests__/RegistrationScreen.test.tsx`:

```typescript
import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { PaperProvider } from 'react-native-paper';
import RegistrationScreen from '../../../../app/(auth)/registration';
import { authApi } from '../api/authApi';
import { lightTheme } from '@/shared/theme';

jest.mock('../api/authApi');
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));
jest.mock('expo-router', () => ({
  useRouter: () => ({ replace: jest.fn() }),
  Link: ({ children }: { children: React.ReactNode }) => children,
}));

function renderWithProviders(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <PaperProvider theme={lightTheme}>
        {ui}
      </PaperProvider>
    </QueryClientProvider>
  );
}

describe('RegistrationScreen', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders all required fields', () => {
    const { getByLabelText } = renderWithProviders(<RegistrationScreen />);

    expect(getByLabelText('Email')).toBeTruthy();
    expect(getByLabelText('Password')).toBeTruthy();
    expect(getByLabelText('Display Name')).toBeTruthy();
    expect(getByLabelText('Height (cm)')).toBeTruthy();
    expect(getByLabelText('Weight (kg)')).toBeTruthy();
  });

  it('renders register button', () => {
    const { getByText } = renderWithProviders(<RegistrationScreen />);
    expect(getByText('Register')).toBeTruthy();
  });

  it('shows validation error for short password', async () => {
    const { getByText, getByLabelText } = renderWithProviders(<RegistrationScreen />);

    fireEvent.changeText(getByLabelText('Email'), 'user@test.com');
    fireEvent.changeText(getByLabelText('Password'), 'short');
    fireEvent.changeText(getByLabelText('Display Name'), 'TestUser');
    fireEvent.changeText(getByLabelText('Height (cm)'), '180');
    fireEvent.changeText(getByLabelText('Weight (kg)'), '80');
    fireEvent.press(getByText('Register'));

    await waitFor(() => {
      expect(getByText('Password must be at least 8 characters')).toBeTruthy();
    });
  });

  it('calls register API with valid data', async () => {
    (authApi.register as jest.Mock).mockResolvedValue({
      id: 'uuid-1',
      login: 'user@test.com',
      display_name: 'TestUser',
    });

    const { getByText, getByLabelText } = renderWithProviders(<RegistrationScreen />);

    fireEvent.changeText(getByLabelText('Email'), 'user@test.com');
    fireEvent.changeText(getByLabelText('Password'), 'password123');
    fireEvent.changeText(getByLabelText('Display Name'), 'TestUser');
    fireEvent.changeText(getByLabelText('Height (cm)'), '180');
    fireEvent.changeText(getByLabelText('Weight (kg)'), '80');
    fireEvent.press(getByText('Register'));

    await waitFor(() => {
      expect(authApi.register).toHaveBeenCalled();
    });
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/RegistrationScreen.test.tsx --no-cache
```

Expected: FAIL — module not found.

- [ ] **Step 3: Implement Registration screen**

Create `app/(auth)/registration.tsx`:

```typescript
import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { TextInput, Button, Text, Snackbar, SegmentedButtons } from 'react-native-paper';
import { useRouter, Link } from 'expo-router';
import { useRegister } from '@/features/auth/hooks/useRegister';
import {
  validateEmail,
  validatePassword,
  validateDisplayName,
  validatePositiveNumber,
} from '@/shared/utils/validation';
import {
  CharacterRace,
  WorkoutType,
  ActivityLevel,
  DesiredGoal,
  ENUM_DISPLAY_NAMES,
} from '@/shared/types/enums';
import { RegistrationRequest } from '@/features/auth/types/requests';

export default function RegistrationScreen() {
  const router = useRouter();
  const registerMutation = useRegister();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [displayName, setDisplayName] = useState('');
  const [height, setHeight] = useState('');
  const [weight, setWeight] = useState('');
  const [workoutType, setWorkoutType] = useState<WorkoutType>(WorkoutType.Cardio);
  const [activityLevel, setActivityLevel] = useState<ActivityLevel>(ActivityLevel.Moderate);
  const [desiredGoal, setDesiredGoal] = useState<DesiredGoal>(DesiredGoal.Maintain);
  const [characterRace, setCharacterRace] = useState<CharacterRace>(CharacterRace.Human);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [snackbar, setSnackbar] = useState('');

  function validate(): boolean {
    const newErrors: Record<string, string> = {};

    const emailErr = validateEmail(email);
    if (emailErr) newErrors.email = emailErr;

    const passErr = validatePassword(password);
    if (passErr) newErrors.password = passErr;

    const nameErr = validateDisplayName(displayName);
    if (nameErr) newErrors.displayName = nameErr;

    const heightNum = parseFloat(height);
    if (isNaN(heightNum)) {
      newErrors.height = 'Height is required';
    } else {
      const hErr = validatePositiveNumber(heightNum, 'Height');
      if (hErr) newErrors.height = hErr;
    }

    const weightNum = parseFloat(weight);
    if (isNaN(weightNum)) {
      newErrors.weight = 'Weight is required';
    } else {
      const wErr = validatePositiveNumber(weightNum, 'Weight');
      if (wErr) newErrors.weight = wErr;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }

  function handleRegister() {
    if (!validate()) return;

    const request: RegistrationRequest = {
      login: email.trim(),
      password,
      display_name: displayName.trim(),
      height: parseFloat(height),
      weight: parseFloat(weight),
      workout_type: workoutType,
      activity_level: activityLevel,
      desired_goal: desiredGoal,
      character_race: characterRace,
    };

    registerMutation.mutate(request, {
      onSuccess: () => {
        router.replace('/(auth)/login');
      },
      onError: (error) => {
        setSnackbar(error.message || 'Registration failed');
      },
    });
  }

  function renderEnumPicker<T extends string>(
    label: string,
    enumObj: Record<string, T>,
    value: T,
    onChange: (v: T) => void
  ) {
    const buttons = Object.values(enumObj).map((v) => ({
      value: v,
      label: ENUM_DISPLAY_NAMES[v] || v,
    }));
    return (
      <View style={styles.enumSection}>
        <Text variant="labelLarge" style={styles.enumLabel}>{label}</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
          <SegmentedButtons
            value={value}
            onValueChange={(v) => onChange(v as T)}
            buttons={buttons}
          />
        </ScrollView>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.content}>
        <Text variant="headlineLarge" style={styles.title}>
          Create Account
        </Text>

        <TextInput
          label="Email"
          accessibilityLabel="Email"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          error={!!errors.email}
          disabled={registerMutation.isPending}
          style={styles.input}
        />
        {errors.email && <Text style={styles.error}>{errors.email}</Text>}

        <TextInput
          label="Password"
          accessibilityLabel="Password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          error={!!errors.password}
          disabled={registerMutation.isPending}
          style={styles.input}
        />
        {errors.password && <Text style={styles.error}>{errors.password}</Text>}

        <TextInput
          label="Display Name"
          accessibilityLabel="Display Name"
          value={displayName}
          onChangeText={setDisplayName}
          error={!!errors.displayName}
          disabled={registerMutation.isPending}
          style={styles.input}
        />
        {errors.displayName && <Text style={styles.error}>{errors.displayName}</Text>}

        <TextInput
          label="Height (cm)"
          accessibilityLabel="Height (cm)"
          value={height}
          onChangeText={setHeight}
          keyboardType="numeric"
          error={!!errors.height}
          disabled={registerMutation.isPending}
          style={styles.input}
        />
        {errors.height && <Text style={styles.error}>{errors.height}</Text>}

        <TextInput
          label="Weight (kg)"
          accessibilityLabel="Weight (kg)"
          value={weight}
          onChangeText={setWeight}
          keyboardType="numeric"
          error={!!errors.weight}
          disabled={registerMutation.isPending}
          style={styles.input}
        />
        {errors.weight && <Text style={styles.error}>{errors.weight}</Text>}

        {renderEnumPicker('Workout Type', WorkoutType, workoutType, setWorkoutType)}
        {renderEnumPicker('Activity Level', ActivityLevel, activityLevel, setActivityLevel)}
        {renderEnumPicker('Desired Goal', DesiredGoal, desiredGoal, setDesiredGoal)}
        {renderEnumPicker('Character Race', CharacterRace, characterRace, setCharacterRace)}

        <Button
          mode="contained"
          onPress={handleRegister}
          loading={registerMutation.isPending}
          disabled={registerMutation.isPending}
          style={styles.button}
        >
          Register
        </Button>

        <Link href="/(auth)/login" asChild>
          <Button mode="text" style={styles.link}>
            Already have an account? Login
          </Button>
        </Link>
      </ScrollView>

      <Snackbar
        visible={!!snackbar}
        onDismiss={() => setSnackbar('')}
        duration={3000}
      >
        {snackbar}
      </Snackbar>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flexGrow: 1, padding: 24, paddingBottom: 48 },
  title: { textAlign: 'center', marginBottom: 24 },
  input: { marginBottom: 4 },
  error: { color: '#B3261E', fontSize: 12, marginBottom: 8, marginLeft: 12 },
  button: { marginTop: 16 },
  link: { marginTop: 8 },
  enumSection: { marginBottom: 16 },
  enumLabel: { marginBottom: 8 },
});
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest src/features/auth/__tests__/RegistrationScreen.test.tsx --no-cache
```

Expected: 4 tests PASS.

- [ ] **Step 5: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add app/\(auth\)/registration.tsx src/features/auth/__tests__/RegistrationScreen.test.tsx
git commit -m "feat: add registration screen with full validation and enum pickers"
```

---

### Task 13: Main and Onboarding Layout Placeholders

**Files:**
- Create: `app/(main)/_layout.tsx`
- Create: `app/(main)/profile.tsx` (placeholder)
- Create: `app/(onboarding)/_layout.tsx`
- Create: `app/(onboarding)/index.tsx` (placeholder)

- [ ] **Step 1: Create main layout with auth guard**

Create `app/(main)/_layout.tsx`:

```typescript
import { Redirect, Stack } from 'expo-router';
import { ActivityIndicator, View } from 'react-native';
import { useAuthStore } from '@/features/auth/stores/authStore';
import { useUser } from '@/features/auth/hooks/useUser';

export default function MainLayout() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const isLoading = useAuthStore((state) => state.isLoading);
  const { data: user, isLoading: userLoading } = useUser();

  if (isLoading || userLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  if (user && !user.onboardingCompleted) {
    return <Redirect href="/(onboarding)" />;
  }

  return (
    <Stack
      screenOptions={{
        headerShown: true,
      }}
    />
  );
}
```

- [ ] **Step 2: Create profile placeholder**

Create `app/(main)/profile.tsx`:

```typescript
import { View, StyleSheet } from 'react-native';
import { Text } from 'react-native-paper';

export default function ProfileScreen() {
  return (
    <View style={styles.container}>
      <Text variant="headlineMedium">Profile</Text>
      <Text variant="bodyLarge">Coming in Plan 2</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
```

- [ ] **Step 3: Create onboarding layout**

Create `app/(onboarding)/_layout.tsx`:

```typescript
import { Redirect, Stack } from 'expo-router';
import { useAuthStore } from '@/features/auth/stores/authStore';

export default function OnboardingLayout() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  return (
    <Stack
      screenOptions={{
        headerShown: false,
      }}
    />
  );
}
```

- [ ] **Step 4: Create onboarding placeholder**

Create `app/(onboarding)/index.tsx`:

```typescript
import { View, StyleSheet } from 'react-native';
import { Text } from 'react-native-paper';

export default function OnboardingScreen() {
  return (
    <View style={styles.container}>
      <Text variant="headlineMedium">Onboarding</Text>
      <Text variant="bodyLarge">Coming in Plan 2</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', alignItems: 'center' },
});
```

- [ ] **Step 5: Commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add app/\(main\)/ app/\(onboarding\)/
git commit -m "feat: add main/onboarding layouts with auth guards and placeholders"
```

---

### Task 14: Run Full Test Suite and Verify

- [ ] **Step 1: Run all tests**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx jest --no-cache --verbose
```

Expected: All tests PASS (environment: 3, enums: 7, interceptors: 5, client: 3, authStore: 6, authApi: 4, useLogin: 2, useRegister: 2, validation: 13, LoginScreen: 5, RegistrationScreen: 4 = ~54 tests total).

- [ ] **Step 2: Verify TypeScript compiles**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
npx tsc --noEmit
```

Expected: No errors.

- [ ] **Step 3: Final commit**

```bash
cd /Users/oleksandr/PhpstormProjects/rpgfit/rpgfit-mobile
git add -A
git commit -m "chore: verify full test suite passes and TypeScript compiles"
```
