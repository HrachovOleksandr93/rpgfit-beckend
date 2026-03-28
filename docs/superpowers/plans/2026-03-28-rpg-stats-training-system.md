# RPG Character Stats & Training System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add RPG character statistics, experience logging, workout logging, exercise config tables, and Sonata Admin CRUD for all entities.

**Architecture:** DDD layers following existing patterns — Domain (entities/enums), Application (DTOs/services), Infrastructure (repositories), Controller. New `Character` and `Training` bounded contexts alongside existing `User` and `Health`. Sonata Admin 5.x provides admin interface.

**Tech Stack:** Symfony 7.2, Doctrine ORM 3.6, Sonata Admin 5.x, PHP 8.4, MySQL 8.0, PHPUnit 13

---

### Task 1: StatType Enum

**Files:**
- Create: `src/Domain/Character/Enum/StatType.php`

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/StatTypeEnumTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use PHPUnit\Framework\TestCase;

class StatTypeEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $this->assertCount(3, StatType::cases());
    }

    public function testStringBackedValues(): void
    {
        $this->assertSame('str', StatType::Strength->value);
        $this->assertSame('con', StatType::Constitution->value);
        $this->assertSame('dex', StatType::Dexterity->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(StatType::Strength, StatType::from('str'));
        $this->assertSame(StatType::Constitution, StatType::from('con'));
        $this->assertSame(StatType::Dexterity, StatType::from('dex'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/StatTypeEnumTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the enum**

Create `src/Domain/Character/Enum/StatType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Character\Enum;

enum StatType: string
{
    case Strength = 'str';
    case Constitution = 'con';
    case Dexterity = 'dex';
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/StatTypeEnumTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add src/Domain/Character/Enum/StatType.php tests/Unit/StatTypeEnumTest.php
git commit -m "feat: add StatType enum (str, con, dex)"
```

---

### Task 2: CharacterStats Entity

**Files:**
- Create: `src/Domain/Character/Entity/CharacterStats.php`
- Create: `src/Infrastructure/Character/Repository/CharacterStatsRepository.php`
- Modify: `config/packages/doctrine.yaml` — add DomainCharacter mapping
- Modify: `config/services.yaml` — exclude new Entity/Enum dirs

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/CharacterStatsEntityTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CharacterStatsEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $stats = new CharacterStats();
        $this->assertInstanceOf(Uuid::class, $stats->getId());
    }

    public function testDefaultStatsAreZero(): void
    {
        $stats = new CharacterStats();
        $this->assertSame(0, $stats->getStrength());
        $this->assertSame(0, $stats->getDexterity());
        $this->assertSame(0, $stats->getConstitution());
    }

    public function testSettersAndGetters(): void
    {
        $stats = new CharacterStats();
        $user = new User();

        $stats->setUser($user);
        $stats->setStrength(10);
        $stats->setDexterity(15);
        $stats->setConstitution(8);

        $this->assertSame($user, $stats->getUser());
        $this->assertSame(10, $stats->getStrength());
        $this->assertSame(15, $stats->getDexterity());
        $this->assertSame(8, $stats->getConstitution());
    }

    public function testSetterChaining(): void
    {
        $stats = new CharacterStats();
        $result = $stats->setStrength(5)->setDexterity(3)->setConstitution(7);
        $this->assertSame($stats, $result);
    }

    public function testUpdatedAtIsSet(): void
    {
        $stats = new CharacterStats();
        $this->assertInstanceOf(\DateTimeImmutable::class, $stats->getUpdatedAt());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/CharacterStatsEntityTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the entity**

Create `src/Domain/Character/Entity/CharacterStats.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Character\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CharacterStatsRepository::class)]
#[ORM\Table(name: 'character_stats')]
#[ORM\HasLifecycleCallbacks]
class CharacterStats
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private User $user;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $strength = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $dexterity = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $constitution = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): self
    {
        $this->strength = $strength;
        return $this;
    }

    public function getDexterity(): int
    {
        return $this->dexterity;
    }

    public function setDexterity(int $dexterity): self
    {
        $this->dexterity = $dexterity;
        return $this;
    }

    public function getConstitution(): int
    {
        return $this->constitution;
    }

    public function setConstitution(int $constitution): self
    {
        $this->constitution = $constitution;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

- [ ] **Step 4: Create the repository**

Create `src/Infrastructure/Character/Repository/CharacterStatsRepository.php`:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Character\Repository;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CharacterStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CharacterStats::class);
    }

    public function save(CharacterStats $stats, bool $flush = true): void
    {
        $this->getEntityManager()->persist($stats);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): ?CharacterStats
    {
        return $this->findOneBy(['user' => $user]);
    }
}
```

- [ ] **Step 5: Update doctrine.yaml — add DomainCharacter mapping**

Add to `config/packages/doctrine.yaml` under `orm.mappings`:
```yaml
            DomainCharacter:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Domain/Character/Entity'
                prefix: 'App\Domain\Character\Entity'
                alias: DomainCharacter
```

- [ ] **Step 6: Update services.yaml — exclude new Entity/Enum dirs**

Add to `config/services.yaml` exclude list:
```yaml
            - '../src/Domain/Character/Entity/'
            - '../src/Domain/Character/Enum/'
```

- [ ] **Step 7: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/CharacterStatsEntityTest.php`
Expected: PASS (5 tests)

- [ ] **Step 8: Commit**

```bash
git add src/Domain/Character/ src/Infrastructure/Character/ tests/Unit/CharacterStatsEntityTest.php config/packages/doctrine.yaml config/services.yaml
git commit -m "feat: add CharacterStats entity (1:1 User, str/dex/con)"
```

---

### Task 3: ExperienceLog Entity

**Files:**
- Create: `src/Domain/Character/Entity/ExperienceLog.php`
- Create: `src/Infrastructure/Character/Repository/ExperienceLogRepository.php`

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/ExperienceLogEntityTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExperienceLogEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $log = new ExperienceLog();
        $this->assertInstanceOf(Uuid::class, $log->getId());
    }

    public function testSettersAndGetters(): void
    {
        $log = new ExperienceLog();
        $user = new User();

        $log->setUser($user);
        $log->setAmount(100);
        $log->setSource('workout');
        $log->setDescription('Completed bench press session');
        $log->setEarnedAt(new \DateTimeImmutable('2026-03-28'));

        $this->assertSame($user, $log->getUser());
        $this->assertSame(100, $log->getAmount());
        $this->assertSame('workout', $log->getSource());
        $this->assertSame('Completed bench press session', $log->getDescription());
        $this->assertSame('2026-03-28', $log->getEarnedAt()->format('Y-m-d'));
    }

    public function testDescriptionIsNullable(): void
    {
        $log = new ExperienceLog();
        $log->setDescription(null);
        $this->assertNull($log->getDescription());
    }

    public function testEarnedAtDefaultsToNow(): void
    {
        $log = new ExperienceLog();
        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getEarnedAt());
    }

    public function testSetterChaining(): void
    {
        $log = new ExperienceLog();
        $result = $log->setAmount(50)->setSource('achievement')->setDescription('First workout');
        $this->assertSame($log, $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/ExperienceLogEntityTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the entity**

Create `src/Domain/Character/Entity/ExperienceLog.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Character\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExperienceLogRepository::class)]
#[ORM\Table(name: 'experience_logs')]
#[ORM\Index(name: 'idx_exp_user_earned', columns: ['user_id', 'earned_at'])]
class ExperienceLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 50)]
    private string $source;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $earnedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->earnedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEarnedAt(): \DateTimeImmutable
    {
        return $this->earnedAt;
    }

    public function setEarnedAt(\DateTimeImmutable $earnedAt): self
    {
        $this->earnedAt = $earnedAt;
        return $this;
    }
}
```

- [ ] **Step 4: Create the repository**

Create `src/Infrastructure/Character/Repository/ExperienceLogRepository.php`:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Character\Repository;

use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExperienceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExperienceLog::class);
    }

    public function save(ExperienceLog $log, bool $flush = true): void
    {
        $this->getEntityManager()->persist($log);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['earnedAt' => 'DESC']);
    }

    public function getTotalXpByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/ExperienceLogEntityTest.php`
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add src/Domain/Character/Entity/ExperienceLog.php src/Infrastructure/Character/Repository/ExperienceLogRepository.php tests/Unit/ExperienceLogEntityTest.php
git commit -m "feat: add ExperienceLog entity for XP tracking"
```

---

### Task 4: WorkoutLog Entity

**Files:**
- Create: `src/Domain/Training/Entity/WorkoutLog.php`
- Create: `src/Infrastructure/Training/Repository/WorkoutLogRepository.php`
- Modify: `config/packages/doctrine.yaml` — add DomainTraining mapping
- Modify: `config/services.yaml` — exclude Training Entity dir

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/WorkoutLogEntityTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class WorkoutLogEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $log = new WorkoutLog();
        $this->assertInstanceOf(Uuid::class, $log->getId());
    }

    public function testSettersAndGetters(): void
    {
        $log = new WorkoutLog();
        $user = new User();
        $performedAt = new \DateTimeImmutable('2026-03-28T07:00:00');

        $log->setUser($user);
        $log->setWorkoutType('running');
        $log->setDurationMinutes(45.0);
        $log->setCaloriesBurned(312.5);
        $log->setDistance(5200.0);
        $log->setPerformedAt($performedAt);

        $this->assertSame($user, $log->getUser());
        $this->assertSame('running', $log->getWorkoutType());
        $this->assertSame(45.0, $log->getDurationMinutes());
        $this->assertSame(312.5, $log->getCaloriesBurned());
        $this->assertSame(5200.0, $log->getDistance());
        $this->assertSame($performedAt, $log->getPerformedAt());
    }

    public function testNullableFields(): void
    {
        $log = new WorkoutLog();
        $this->assertNull($log->getCaloriesBurned());
        $this->assertNull($log->getDistance());
        $this->assertNull($log->getHealthDataPoint());
        $this->assertNull($log->getExtraDetails());
    }

    public function testExtraDetailsJson(): void
    {
        $log = new WorkoutLog();
        $details = [
            'exercises' => [
                ['name' => 'Bench Press', 'sets' => 3, 'reps' => 10, 'weight_kg' => 100],
                ['name' => 'Squat', 'sets' => 4, 'reps' => 8, 'weight_kg' => 120],
            ],
        ];
        $log->setExtraDetails($details);
        $this->assertSame($details, $log->getExtraDetails());
    }

    public function testHealthDataPointRelation(): void
    {
        $log = new WorkoutLog();
        $healthPoint = new HealthDataPoint();
        $log->setHealthDataPoint($healthPoint);
        $this->assertSame($healthPoint, $log->getHealthDataPoint());
    }

    public function testCreatedAtIsSet(): void
    {
        $log = new WorkoutLog();
        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
    }

    public function testSetterChaining(): void
    {
        $log = new WorkoutLog();
        $result = $log->setWorkoutType('yoga')
            ->setDurationMinutes(60.0)
            ->setCaloriesBurned(200.0);
        $this->assertSame($log, $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/WorkoutLogEntityTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the entity**

Create `src/Domain/Training/Entity/WorkoutLog.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\User\Entity\User;
use App\Infrastructure\Training\Repository\WorkoutLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutLogRepository::class)]
#[ORM\Table(name: 'workout_logs')]
#[ORM\Index(name: 'idx_wl_user_performed', columns: ['user_id', 'performed_at'])]
class WorkoutLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 100)]
    private string $workoutType;

    #[ORM\Column(type: 'float')]
    private float $durationMinutes;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $caloriesBurned = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $distance = null;

    #[ORM\ManyToOne(targetEntity: HealthDataPoint::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?HealthDataPoint $healthDataPoint = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extraDetails = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $performedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getWorkoutType(): string
    {
        return $this->workoutType;
    }

    public function setWorkoutType(string $workoutType): self
    {
        $this->workoutType = $workoutType;
        return $this;
    }

    public function getDurationMinutes(): float
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(float $durationMinutes): self
    {
        $this->durationMinutes = $durationMinutes;
        return $this;
    }

    public function getCaloriesBurned(): ?float
    {
        return $this->caloriesBurned;
    }

    public function setCaloriesBurned(?float $caloriesBurned): self
    {
        $this->caloriesBurned = $caloriesBurned;
        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;
        return $this;
    }

    public function getHealthDataPoint(): ?HealthDataPoint
    {
        return $this->healthDataPoint;
    }

    public function setHealthDataPoint(?HealthDataPoint $healthDataPoint): self
    {
        $this->healthDataPoint = $healthDataPoint;
        return $this;
    }

    public function getExtraDetails(): ?array
    {
        return $this->extraDetails;
    }

    public function setExtraDetails(?array $extraDetails): self
    {
        $this->extraDetails = $extraDetails;
        return $this;
    }

    public function getPerformedAt(): \DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function setPerformedAt(\DateTimeImmutable $performedAt): self
    {
        $this->performedAt = $performedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

- [ ] **Step 4: Create the repository**

Create `src/Infrastructure/Training/Repository/WorkoutLogRepository.php`:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkoutLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutLog::class);
    }

    public function save(WorkoutLog $log, bool $flush = true): void
    {
        $this->getEntityManager()->persist($log);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['performedAt' => 'DESC']);
    }
}
```

- [ ] **Step 5: Update doctrine.yaml — add DomainTraining mapping**

Add to `config/packages/doctrine.yaml` under `orm.mappings`:
```yaml
            DomainTraining:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Domain/Training/Entity'
                prefix: 'App\Domain\Training\Entity'
                alias: DomainTraining
```

- [ ] **Step 6: Update services.yaml — exclude Training Entity dir**

Add to `config/services.yaml` exclude list:
```yaml
            - '../src/Domain/Training/Entity/'
```

- [ ] **Step 7: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/WorkoutLogEntityTest.php`
Expected: PASS (7 tests)

- [ ] **Step 8: Commit**

```bash
git add src/Domain/Training/ src/Infrastructure/Training/ tests/Unit/WorkoutLogEntityTest.php config/packages/doctrine.yaml config/services.yaml
git commit -m "feat: add WorkoutLog entity with HealthDataPoint relation"
```

---

### Task 5: WorkoutCategory Entity

**Files:**
- Create: `src/Domain/Training/Entity/WorkoutCategory.php`
- Create: `src/Infrastructure/Training/Repository/WorkoutCategoryRepository.php`

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/WorkoutCategoryEntityTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Training\Entity\WorkoutCategory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class WorkoutCategoryEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $cat = new WorkoutCategory();
        $this->assertInstanceOf(Uuid::class, $cat->getId());
    }

    public function testSettersAndGetters(): void
    {
        $cat = new WorkoutCategory();
        $cat->setName('Strength Training');
        $cat->setSlug('strength-training');
        $cat->setDescription('All strength-based exercises');

        $this->assertSame('Strength Training', $cat->getName());
        $this->assertSame('strength-training', $cat->getSlug());
        $this->assertSame('All strength-based exercises', $cat->getDescription());
    }

    public function testDescriptionIsNullable(): void
    {
        $cat = new WorkoutCategory();
        $this->assertNull($cat->getDescription());
    }

    public function testExerciseTypesCollectionIsEmpty(): void
    {
        $cat = new WorkoutCategory();
        $this->assertCount(0, $cat->getExerciseTypes());
    }

    public function testSetterChaining(): void
    {
        $cat = new WorkoutCategory();
        $result = $cat->setName('Cardio')->setSlug('cardio');
        $this->assertSame($cat, $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/WorkoutCategoryEntityTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the entity**

Create `src/Domain/Training/Entity/WorkoutCategory.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Infrastructure\Training\Repository\WorkoutCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutCategoryRepository::class)]
#[ORM\Table(name: 'workout_categories')]
class WorkoutCategory
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, ExerciseType> */
    #[ORM\OneToMany(targetEntity: ExerciseType::class, mappedBy: 'workoutCategory', cascade: ['persist', 'remove'])]
    private Collection $exerciseTypes;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->exerciseTypes = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** @return Collection<int, ExerciseType> */
    public function getExerciseTypes(): Collection
    {
        return $this->exerciseTypes;
    }

    public function addExerciseType(ExerciseType $exerciseType): self
    {
        if (!$this->exerciseTypes->contains($exerciseType)) {
            $this->exerciseTypes->add($exerciseType);
            $exerciseType->setWorkoutCategory($this);
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
```

- [ ] **Step 4: Create the repository**

Create `src/Infrastructure/Training/Repository/WorkoutCategoryRepository.php`:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\WorkoutCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkoutCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutCategory::class);
    }

    public function save(WorkoutCategory $category, bool $flush = true): void
    {
        $this->getEntityManager()->persist($category);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/WorkoutCategoryEntityTest.php`
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add src/Domain/Training/Entity/WorkoutCategory.php src/Infrastructure/Training/Repository/WorkoutCategoryRepository.php tests/Unit/WorkoutCategoryEntityTest.php
git commit -m "feat: add WorkoutCategory entity"
```

---

### Task 6: ExerciseType Entity

**Files:**
- Create: `src/Domain/Training/Entity/ExerciseType.php`
- Create: `src/Infrastructure/Training/Repository/ExerciseTypeRepository.php`

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/ExerciseTypeEntityTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Training\Entity\ExerciseType;
use App\Domain\Training\Entity\WorkoutCategory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExerciseTypeEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $ex = new ExerciseType();
        $this->assertInstanceOf(Uuid::class, $ex->getId());
    }

    public function testSettersAndGetters(): void
    {
        $ex = new ExerciseType();
        $cat = new WorkoutCategory();
        $cat->setName('Strength');
        $cat->setSlug('strength');

        $ex->setWorkoutCategory($cat);
        $ex->setName('Bench Press');
        $ex->setSlug('bench-press');
        $ex->setDescription('Barbell bench press');

        $this->assertSame($cat, $ex->getWorkoutCategory());
        $this->assertSame('Bench Press', $ex->getName());
        $this->assertSame('bench-press', $ex->getSlug());
        $this->assertSame('Barbell bench press', $ex->getDescription());
    }

    public function testStatRewardsCollectionIsEmpty(): void
    {
        $ex = new ExerciseType();
        $this->assertCount(0, $ex->getStatRewards());
    }

    public function testSetterChaining(): void
    {
        $ex = new ExerciseType();
        $result = $ex->setName('Running')->setSlug('running');
        $this->assertSame($ex, $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/ExerciseTypeEntityTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the entity**

Create `src/Domain/Training/Entity/ExerciseType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Infrastructure\Training\Repository\ExerciseTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExerciseTypeRepository::class)]
#[ORM\Table(name: 'exercise_types')]
class ExerciseType
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: WorkoutCategory::class, inversedBy: 'exerciseTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkoutCategory $workoutCategory;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, ExerciseStatReward> */
    #[ORM\OneToMany(targetEntity: ExerciseStatReward::class, mappedBy: 'exerciseType', cascade: ['persist', 'remove'])]
    private Collection $statRewards;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->statRewards = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getWorkoutCategory(): WorkoutCategory
    {
        return $this->workoutCategory;
    }

    public function setWorkoutCategory(WorkoutCategory $workoutCategory): self
    {
        $this->workoutCategory = $workoutCategory;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** @return Collection<int, ExerciseStatReward> */
    public function getStatRewards(): Collection
    {
        return $this->statRewards;
    }

    public function addStatReward(ExerciseStatReward $reward): self
    {
        if (!$this->statRewards->contains($reward)) {
            $this->statRewards->add($reward);
            $reward->setExerciseType($this);
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
```

- [ ] **Step 4: Create the repository**

Create `src/Infrastructure/Training/Repository/ExerciseTypeRepository.php`:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\ExerciseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExerciseTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseType::class);
    }

    public function save(ExerciseType $type, bool $flush = true): void
    {
        $this->getEntityManager()->persist($type);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/ExerciseTypeEntityTest.php`
Expected: PASS (4 tests)

- [ ] **Step 6: Commit**

```bash
git add src/Domain/Training/Entity/ExerciseType.php src/Infrastructure/Training/Repository/ExerciseTypeRepository.php tests/Unit/ExerciseTypeEntityTest.php
git commit -m "feat: add ExerciseType entity (N:1 WorkoutCategory)"
```

---

### Task 7: ExerciseStatReward Entity

**Files:**
- Create: `src/Domain/Training/Entity/ExerciseStatReward.php`
- Create: `src/Infrastructure/Training/Repository/ExerciseStatRewardRepository.php`

- [ ] **Step 1: Write the unit test**

Create `tests/Unit/ExerciseStatRewardEntityTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Training\Entity\ExerciseStatReward;
use App\Domain\Training\Entity\ExerciseType;
use App\Domain\Training\Entity\WorkoutCategory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExerciseStatRewardEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $reward = new ExerciseStatReward();
        $this->assertInstanceOf(Uuid::class, $reward->getId());
    }

    public function testSettersAndGetters(): void
    {
        $reward = new ExerciseStatReward();
        $cat = new WorkoutCategory();
        $cat->setName('Strength')->setSlug('strength');
        $ex = new ExerciseType();
        $ex->setWorkoutCategory($cat)->setName('Bench Press')->setSlug('bench-press');

        $reward->setExerciseType($ex);
        $reward->setStatType(StatType::Strength);
        $reward->setPoints(5);

        $this->assertSame($ex, $reward->getExerciseType());
        $this->assertSame(StatType::Strength, $reward->getStatType());
        $this->assertSame(5, $reward->getPoints());
    }

    public function testAllStatTypes(): void
    {
        $reward = new ExerciseStatReward();

        $reward->setStatType(StatType::Strength);
        $this->assertSame('str', $reward->getStatType()->value);

        $reward->setStatType(StatType::Constitution);
        $this->assertSame('con', $reward->getStatType()->value);

        $reward->setStatType(StatType::Dexterity);
        $this->assertSame('dex', $reward->getStatType()->value);
    }

    public function testSetterChaining(): void
    {
        $reward = new ExerciseStatReward();
        $result = $reward->setStatType(StatType::Dexterity)->setPoints(3);
        $this->assertSame($reward, $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker-compose exec php php bin/phpunit tests/Unit/ExerciseStatRewardEntityTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create the entity**

Create `src/Domain/Training/Entity/ExerciseStatReward.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Domain\Character\Enum\StatType;
use App\Infrastructure\Training\Repository\ExerciseStatRewardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ExerciseStatRewardRepository::class)]
#[ORM\Table(name: 'exercise_stat_rewards')]
#[ORM\Index(name: 'idx_esr_exercise', columns: ['exercise_type_id'])]
class ExerciseStatReward
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ExerciseType::class, inversedBy: 'statRewards')]
    #[ORM\JoinColumn(nullable: false)]
    private ExerciseType $exerciseType;

    #[ORM\Column(type: 'string', length: 10, enumType: StatType::class)]
    private StatType $statType;

    #[ORM\Column(type: 'integer')]
    private int $points;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getExerciseType(): ExerciseType
    {
        return $this->exerciseType;
    }

    public function setExerciseType(ExerciseType $exerciseType): self
    {
        $this->exerciseType = $exerciseType;
        return $this;
    }

    public function getStatType(): StatType
    {
        return $this->statType;
    }

    public function setStatType(StatType $statType): self
    {
        $this->statType = $statType;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }
}
```

- [ ] **Step 4: Create the repository**

Create `src/Infrastructure/Training/Repository/ExerciseStatRewardRepository.php`:
```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\ExerciseStatReward;
use App\Domain\Training\Entity\ExerciseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExerciseStatRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseStatReward::class);
    }

    public function save(ExerciseStatReward $reward, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reward);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByExerciseType(ExerciseType $exerciseType): array
    {
        return $this->findBy(['exerciseType' => $exerciseType]);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker-compose exec php php bin/phpunit tests/Unit/ExerciseStatRewardEntityTest.php`
Expected: PASS (4 tests)

- [ ] **Step 6: Commit**

```bash
git add src/Domain/Training/Entity/ExerciseStatReward.php src/Infrastructure/Training/Repository/ExerciseStatRewardRepository.php tests/Unit/ExerciseStatRewardEntityTest.php
git commit -m "feat: add ExerciseStatReward entity (N:1 ExerciseType, StatType enum)"
```

---

### Task 8: Database Migration

**Files:**
- Create: `migrations/VersionXXXX.php` (auto-generated)

- [ ] **Step 1: Generate migration**

Run: `docker-compose exec php php bin/console doctrine:migrations:diff`
Expected: "Generated new migration class" with tables: character_stats, experience_logs, workout_logs, workout_categories, exercise_types, exercise_stat_rewards

- [ ] **Step 2: Review generated migration**

Run: `docker-compose exec php php bin/console doctrine:migrations:status`
Verify one new migration is pending.

- [ ] **Step 3: Apply migration**

Run: `docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction`
Expected: Migration applied successfully

- [ ] **Step 4: Run ALL existing tests to verify nothing broke**

Run: `docker-compose exec php php bin/phpunit`
Expected: All tests pass (existing 69 + new entity tests)

- [ ] **Step 5: Commit**

```bash
git add migrations/
git commit -m "feat: add migration for RPG stats and training tables"
```

---

### Task 9: Install Sonata Admin

**Files:**
- Modify: `composer.json`
- Create: `config/packages/sonata_admin.yaml`
- Create: `config/routes/sonata_admin.yaml`

- [ ] **Step 1: Install Sonata Admin via Docker**

Run:
```bash
docker-compose exec php composer require sonata-project/admin-bundle sonata-project/doctrine-orm-admin-bundle
```

- [ ] **Step 2: Install Twig and assets (required by Sonata)**

Run:
```bash
docker-compose exec php composer require symfony/twig-bundle symfony/asset symfony/webpack-encore-bundle twig/extra-bundle
```

If webpack-encore-bundle causes issues, skip it — Sonata works with Twig alone.

- [ ] **Step 3: Configure Sonata Admin**

Create `config/packages/sonata_admin.yaml`:
```yaml
sonata_admin:
    title: 'RPGFit Admin'
    title_logo: ~
    dashboard:
        groups:
            users:
                label: Users
                icon: '<i class="fa fa-users"></i>'
                items:
                    - admin.user
                    - admin.character_stats
            training:
                label: Training Config
                icon: '<i class="fa fa-dumbbell"></i>'
                items:
                    - admin.workout_category
                    - admin.exercise_type
                    - admin.exercise_stat_reward
            logs:
                label: Logs
                icon: '<i class="fa fa-list"></i>'
                items:
                    - admin.experience_log
                    - admin.workout_log
```

- [ ] **Step 4: Configure Sonata Admin routes**

Create `config/routes/sonata_admin.yaml`:
```yaml
sonata_admin:
    resource: '@SonataAdminBundle/Resources/config/routing/sonata_admin.xml'
    prefix: /admin

_sonata_admin:
    resource: .
    type: sonata_admin
    prefix: /admin
```

- [ ] **Step 5: Update security.yaml — allow admin access**

Add to `config/packages/security.yaml` firewalls (before `api`):
```yaml
        admin:
            pattern: ^/admin
            security: false
```

Note: This is dev-only. Production should use proper admin auth. For now we keep it open for setup.

- [ ] **Step 6: Clear cache and verify**

Run:
```bash
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console sonata:admin:list
```
Expected: No errors. Admin list should be empty (no admins registered yet).

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock config/packages/sonata_admin.yaml config/routes/sonata_admin.yaml config/packages/security.yaml symfony.lock
git commit -m "feat: install and configure Sonata Admin"
```

---

### Task 10: Sonata Admin Classes — User & CharacterStats

**Files:**
- Create: `src/Admin/UserAdmin.php`
- Create: `src/Admin/CharacterStatsAdmin.php`

- [ ] **Step 1: Create UserAdmin**

Create `src/Admin/UserAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/** @extends AbstractAdmin<\App\Domain\User\Entity\User> */
class UserAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('login')
            ->add('displayName')
            ->add('characterRace', null, ['template' => null])
            ->add('workoutType', null, ['template' => null])
            ->add('activityLevel', null, ['template' => null])
            ->add('createdAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('login')
            ->add('displayName');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Account', ['class' => 'col-md-6'])
                ->add('login', EmailType::class)
                ->add('displayName', TextType::class)
            ->end()
            ->with('Body', ['class' => 'col-md-6'])
                ->add('height', NumberType::class)
                ->add('weight', NumberType::class)
            ->end()
            ->with('RPG Profile', ['class' => 'col-md-6'])
                ->add('characterRace', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($r) => $r->name, CharacterRace::cases()),
                        CharacterRace::cases()
                    ),
                ])
                ->add('workoutType', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($t) => $t->name, WorkoutType::cases()),
                        WorkoutType::cases()
                    ),
                ])
                ->add('activityLevel', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($l) => $l->name, ActivityLevel::cases()),
                        ActivityLevel::cases()
                    ),
                ])
                ->add('desiredGoal', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($g) => $g->name, DesiredGoal::cases()),
                        DesiredGoal::cases()
                    ),
                ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('login')
            ->add('displayName')
            ->add('height')
            ->add('weight')
            ->add('characterRace')
            ->add('workoutType')
            ->add('activityLevel')
            ->add('desiredGoal')
            ->add('createdAt')
            ->add('updatedAt');
    }
}
```

- [ ] **Step 2: Create CharacterStatsAdmin**

Create `src/Admin/CharacterStatsAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/** @extends AbstractAdmin<\App\Domain\Character\Entity\CharacterStats> */
class CharacterStatsAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('strength')
            ->add('dexterity')
            ->add('constitution')
            ->add('updatedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('user.displayName');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, ['disabled' => !$this->isCurrentRoute('create')])
            ->add('strength', IntegerType::class)
            ->add('dexterity', IntegerType::class)
            ->add('constitution', IntegerType::class);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('strength')
            ->add('dexterity')
            ->add('constitution')
            ->add('updatedAt');
    }
}
```

- [ ] **Step 3: Register admin services**

Add to `config/services.yaml`:
```yaml
    admin.user:
        class: App\Admin\UserAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\User\Entity\User, manager_type: orm, label: Users, group: users }

    admin.character_stats:
        class: App\Admin\CharacterStatsAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\Character\Entity\CharacterStats, manager_type: orm, label: Character Stats, group: users }
```

- [ ] **Step 4: Clear cache and verify**

Run:
```bash
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console sonata:admin:list
```
Expected: Shows `admin.user` and `admin.character_stats`

- [ ] **Step 5: Commit**

```bash
git add src/Admin/UserAdmin.php src/Admin/CharacterStatsAdmin.php config/services.yaml
git commit -m "feat: add Sonata Admin for User and CharacterStats"
```

---

### Task 11: Sonata Admin Classes — Training Config (WorkoutCategory, ExerciseType, ExerciseStatReward)

**Files:**
- Create: `src/Admin/WorkoutCategoryAdmin.php`
- Create: `src/Admin/ExerciseTypeAdmin.php`
- Create: `src/Admin/ExerciseStatRewardAdmin.php`

- [ ] **Step 1: Create WorkoutCategoryAdmin**

Create `src/Admin/WorkoutCategoryAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/** @extends AbstractAdmin<\App\Domain\Training\Entity\WorkoutCategory> */
class WorkoutCategoryAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('description')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextareaType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->add('id')->add('name')->add('slug')->add('description');
    }
}
```

- [ ] **Step 2: Create ExerciseTypeAdmin**

Create `src/Admin/ExerciseTypeAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/** @extends AbstractAdmin<\App\Domain\Training\Entity\ExerciseType> */
class ExerciseTypeAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('workoutCategory.name', null, ['label' => 'Category'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug')->add('workoutCategory');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('workoutCategory', null, ['required' => true])
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextareaType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('workoutCategory.name', null, ['label' => 'Category'])
            ->add('name')
            ->add('slug')
            ->add('description');
    }
}
```

- [ ] **Step 3: Create ExerciseStatRewardAdmin**

Create `src/Admin/ExerciseStatRewardAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Character\Enum\StatType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/** @extends AbstractAdmin<\App\Domain\Training\Entity\ExerciseStatReward> */
class ExerciseStatRewardAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('exerciseType.name', null, ['label' => 'Exercise'])
            ->add('exerciseType.workoutCategory.name', null, ['label' => 'Category'])
            ->add('statType', null, ['template' => null])
            ->add('points')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('exerciseType');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('exerciseType', null, ['required' => true])
            ->add('statType', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($s) => $s->name, StatType::cases()),
                    StatType::cases()
                ),
            ])
            ->add('points', IntegerType::class);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('exerciseType.name', null, ['label' => 'Exercise'])
            ->add('statType')
            ->add('points');
    }
}
```

- [ ] **Step 4: Register admin services**

Add to `config/services.yaml`:
```yaml
    admin.workout_category:
        class: App\Admin\WorkoutCategoryAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\Training\Entity\WorkoutCategory, manager_type: orm, label: Workout Categories, group: training }

    admin.exercise_type:
        class: App\Admin\ExerciseTypeAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\Training\Entity\ExerciseType, manager_type: orm, label: Exercise Types, group: training }

    admin.exercise_stat_reward:
        class: App\Admin\ExerciseStatRewardAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\Training\Entity\ExerciseStatReward, manager_type: orm, label: Stat Rewards, group: training }
```

- [ ] **Step 5: Clear cache and verify**

Run:
```bash
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console sonata:admin:list
```
Expected: Shows all 5 admin services registered (admin.user, admin.character_stats, admin.workout_category, admin.exercise_type, admin.exercise_stat_reward)

- [ ] **Step 6: Commit**

```bash
git add src/Admin/WorkoutCategoryAdmin.php src/Admin/ExerciseTypeAdmin.php src/Admin/ExerciseStatRewardAdmin.php config/services.yaml
git commit -m "feat: add Sonata Admin for WorkoutCategory, ExerciseType, ExerciseStatReward"
```

---

### Task 12: Sonata Admin Classes — Logs (ExperienceLog, WorkoutLog)

**Files:**
- Create: `src/Admin/ExperienceLogAdmin.php`
- Create: `src/Admin/WorkoutLogAdmin.php`

- [ ] **Step 1: Create ExperienceLogAdmin**

Create `src/Admin/ExperienceLogAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/** @extends AbstractAdmin<\App\Domain\Character\Entity\ExperienceLog> */
class ExperienceLogAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('amount')
            ->add('source')
            ->add('description')
            ->add('earnedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.displayName')
            ->add('source');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, ['required' => true])
            ->add('amount', IntegerType::class)
            ->add('source', TextType::class)
            ->add('description', TextType::class, ['required' => false])
            ->add('earnedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('amount')
            ->add('source')
            ->add('description')
            ->add('earnedAt');
    }
}
```

- [ ] **Step 2: Create WorkoutLogAdmin**

Create `src/Admin/WorkoutLogAdmin.php`:
```php
<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/** @extends AbstractAdmin<\App\Domain\Training\Entity\WorkoutLog> */
class WorkoutLogAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('workoutType')
            ->add('durationMinutes')
            ->add('caloriesBurned')
            ->add('distance')
            ->add('performedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.displayName')
            ->add('workoutType');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Workout', ['class' => 'col-md-6'])
                ->add('user', null, ['required' => true])
                ->add('workoutType', TextType::class)
                ->add('durationMinutes', NumberType::class)
                ->add('caloriesBurned', NumberType::class, ['required' => false])
                ->add('distance', NumberType::class, ['required' => false])
            ->end()
            ->with('Timing', ['class' => 'col-md-6'])
                ->add('performedAt', DateTimeType::class, [
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('workoutType')
            ->add('durationMinutes')
            ->add('caloriesBurned')
            ->add('distance')
            ->add('healthDataPoint.id', null, ['label' => 'Health Data Point ID'])
            ->add('extraDetails')
            ->add('performedAt')
            ->add('createdAt');
    }
}
```

- [ ] **Step 3: Register admin services**

Add to `config/services.yaml`:
```yaml
    admin.experience_log:
        class: App\Admin\ExperienceLogAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\Character\Entity\ExperienceLog, manager_type: orm, label: Experience Logs, group: logs }

    admin.workout_log:
        class: App\Admin\WorkoutLogAdmin
        tags:
            - { name: sonata.admin, model_class: App\Domain\Training\Entity\WorkoutLog, manager_type: orm, label: Workout Logs, group: logs }
```

- [ ] **Step 4: Clear cache and verify all 7 admins**

Run:
```bash
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console sonata:admin:list
```
Expected: Shows all 7 admin services

- [ ] **Step 5: Commit**

```bash
git add src/Admin/ExperienceLogAdmin.php src/Admin/WorkoutLogAdmin.php config/services.yaml
git commit -m "feat: add Sonata Admin for ExperienceLog and WorkoutLog"
```

---

### Task 13: Run Full Test Suite and Final Verification

**Files:** None (verification only)

- [ ] **Step 1: Run all unit tests**

Run: `docker-compose exec php php bin/phpunit tests/Unit/`
Expected: All unit tests pass (existing + new entity tests)

- [ ] **Step 2: Run all functional tests**

Run: `docker-compose exec php php bin/phpunit tests/Functional/`
Expected: All functional tests pass

- [ ] **Step 3: Run full test suite**

Run: `docker-compose exec php php bin/phpunit`
Expected: All tests pass with 0 failures

- [ ] **Step 4: Verify Sonata Admin dashboard loads**

Run: `docker-compose exec php php bin/console router:match /admin/dashboard`
Expected: Route matches sonata_admin_dashboard

- [ ] **Step 5: Verify all admin services are registered**

Run: `docker-compose exec php php bin/console sonata:admin:list`
Expected: 7 admin classes listed

- [ ] **Step 6: Final commit**

```bash
git add -A
git commit -m "feat: complete RPG stats and training system with Sonata Admin"
```
