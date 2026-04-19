# RPGFit — Flow Summary

## Core Game Loop

```
Пользователь тренируется → Health Data собирается → Отправляется на сервер →
→ Конвертируется в XP → Уровень растёт → Характеристики улучшаются →
→ Сильнее урон в бою → Побеждает мобов → Получает лут → Экипирует предметы → Ещё сильнее
```

---

## 1. Регистрация и Онбординг

**Регистрация** (`POST /api/registration`):
- Юзер вводит: email, пароль, имя, рост, вес, тип тренировки, уровень активности, цель, расу персонажа
- Сервер создаёт аккаунт, `onboardingCompleted = true`

**OAuth** (`POST /api/auth/oauth`):
- Вход через Google/Apple/Facebook
- Если новый юзер → `onboardingCompleted = false` → перенаправляем на онбординг

**Онбординг** (`POST /api/onboarding`) — 9 шагов:
1. Имя → 2. Рост/Вес → 3. Пол → 4. Раса → 5. Частота тренировок → 6. Тип тренировок → 7. Образ жизни → 8. Предпочитаемые виды → 9. Подтверждение
- Сервер рассчитывает начальные STR/DEX/CON (всего 30 очков) на основе выбранных параметров

---

## 2. Профиль и Прогрессия

**Профиль** (`GET /api/user`):
- Все данные юзера + характеристики (STR, DEX, CON) + уровень + инвентарь + скиллы

**Левелинг** (`GET /api/levels/progress`):
- 100 уровней, 10 тиров (Novice → Transcendent)
- XP зарабатывается через: health data sync + завершение баттлов

---

## 3. Health Data Sync

**Как данные попадают на сервер:**

**iOS:**
- HealthKit автоматически уведомляет приложение о новых данных (push-модель)
- Приложение читает данные и отправляет на сервер

**Android:**
- Во время тренировки: опрос Health Connect каждые 10 секунд
- В покое: опрос каждые 5 минут
- При открытии приложения: немедленная синхронизация

**Синхронизация** (`POST /api/health/sync`):
- Приложение отправляет массив данных (шаги, пульс, калории, дистанция, сон и т.д.)
- Сервер дедуплицирует по `externalUuid`
- Конвертирует данные в XP (дневной лимит: 3000)
- Возвращает: сколько принято, сколько дубликатов, сколько XP начислено

**Дашборд** (`GET /api/health/summary?date=`):
- Шаги, калории, дистанция, сон, средний пульс, время тренировок за день

---

## 4. Battle Flow (главный игровой цикл)

### Начало боя:
1. Юзер нажимает **"Start Battle"**
2. Выбирает **тип тренировки**: Strength / Cardio / CrossFit / Gymnastics / Martial Arts / Yoga
3. Выбирает **режим боя**:
   - **Custom** — свободный выбор упражнений
   - **Recommended** — сервер генерирует план
   - **Raid** — +30% сложности, редкие мобы, больше XP
4. Сервер генерирует план тренировки (`POST /api/workout/generate`)
5. Сервер подбирает моба по уровню юзера ±2 (`POST /api/battle/start`)

### Активный бой:
- Экран показывает: моба, его HP, текущий урон, таймер
- iOS: HKWorkoutBuilder записывает данные автоматически
- Android: polling Health Connect каждые 10 секунд
- Урон обновляется в реальном времени на основе данных упражнений
- Юзер может залогировать сеты вручную (`POST /api/workout/plans/{id}/exercises/{id}/log`)

### Завершение боя:
- `POST /api/battle/complete` → сервер считает:
  - Общий урон (из сетов + health data)
  - Процент выполнения
  - Уровень результата: Failed / Survived / Completed / Exceeded / Raid Exceeded
  - Начисляет XP, определяет лут
  - Если XP хватило — level up

### Результаты:
- Tier (уровень выполнения)
- XP заработан
- Мобы побеждены
- Лут (если получен)
- Level Up! (если сработал)

---

## 5. Equipment & Inventory

**Инвентарь**: предметы получаются как лут из баттлов
- Типы: equipment (экипировка), scroll (разблокировка скиллов), potion (временный бафф)
- Редкость: common → uncommon → rare → epic → legendary

**Экипировка** (`POST /api/equipment/equip/{id}`):
- 12 слотов: weapon, shield, head, body, legs, feet, hands, bracers, bracelet, ring, shirt, necklace
- Экипированные предметы дают бонусы к STR/DEX/CON
- Бонусы влияют на урон в бою

---

## 6. Profession System

- 16 категорий активности (Combat, Running, Cycling, Swimming, Strength, и т.д.)
- Каждая категория = 3 тира профессий (как в Lineage 2)
- Пример: Combat → Fighter → Gladiator → Titan Breaker
- Профессия даёт скиллы (пассивные и активные)
- Скиллы дают бонусы к характеристикам и используются в бою

---

## Технический стек

**Mobile (React Native):**
- Expo SDK 52+ (dev builds)
- Expo Router (file-based navigation)
- TanStack Query v5 (server state)
- Zustand (auth state)
- React Native Paper (Material Design 3)
- Axios + JWT interceptors
- 106 тестов (Jest + RNTL)

**Backend (Symfony):**
- PHP 8.3, Symfony 7.x, Doctrine ORM
- MySQL 8.0, Redis 7
- JWT Authentication (lexik/jwt-auth)
- DDD architecture
- 70 тестов (PHPUnit)

**Экраны:**
`/login` → `/registration` → `/onboarding` → `/profile` → `/battle` → `/battle/start` → `/health` → `/workouts` → `/workouts/[id]` → `/equipment` → `/inventory` → `/levels`
