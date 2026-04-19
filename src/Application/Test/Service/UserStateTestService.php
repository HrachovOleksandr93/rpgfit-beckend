<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\SessionStatus;
use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\User\Entity\LinkedAccount;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Dumps a user's full state and resets it to a clean slate.
 *
 * Soft vs hard reset semantics follow the spec §3.9:
 *   - soft: inventory + XP + level + active battles + test health points
 *   - hard: soft + onboarding reset + OAuth linked-account purge
 *
 * The user row itself is never deleted — GDPR-style deletes stay out of
 * the harness.
 */
final class UserStateTestService
{
    public function __construct(
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly UserInventoryRepository $userInventoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function dumpState(User $user): array
    {
        $stats = $this->characterStatsRepository->findByUser($user);

        $experienceLogs = $this->experienceLogRepository->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.earnedAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $inventory = $this->userInventoryRepository->findActiveByUser($user);

        $healthPoints = $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(HealthDataPoint::class, 'h')
            ->where('h.user = :user')
            ->setParameter('user', $user)
            ->orderBy('h.dateFrom', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $activeSession = $this->entityManager->getRepository(WorkoutSession::class)
            ->findOneBy(['user' => $user, 'status' => SessionStatus::Active]);

        return [
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'displayName' => $user->getDisplayName(),
                'onboardingCompleted' => $user->isOnboardingCompleted(),
            ],
            'stats' => $stats !== null ? [
                'str' => $stats->getStrength(),
                'dex' => $stats->getDexterity(),
                'con' => $stats->getConstitution(),
                'level' => $stats->getLevel(),
                'totalXp' => $stats->getTotalXp(),
            ] : null,
            'inventoryCount' => count($inventory),
            'inventory' => array_map(static fn (UserInventory $i): array => [
                'id' => $i->getId()->toRfc4122(),
                'slug' => $i->getItemCatalog()->getSlug(),
                'name' => $i->getItemCatalog()->getName(),
                'quantity' => $i->getQuantity(),
                'equipped' => $i->isEquipped(),
                'equippedSlot' => $i->getEquippedSlot()?->value,
            ], $inventory),
            'experienceLogs' => array_map(static fn (ExperienceLog $e): array => [
                'id' => $e->getId()->toRfc4122(),
                'amount' => $e->getAmount(),
                'source' => $e->getSource(),
                'earnedAt' => $e->getEarnedAt()->format(\DateTimeInterface::ATOM),
            ], $experienceLogs),
            'healthDataPoints' => array_map(static fn (HealthDataPoint $p): array => [
                'id' => $p->getId()->toRfc4122(),
                'type' => $p->getDataType()->value,
                'value' => $p->getValue(),
                'unit' => $p->getUnit(),
                'sourceApp' => $p->getSourceApp(),
                'dateFrom' => $p->getDateFrom()->format(\DateTimeInterface::ATOM),
                'dateTo' => $p->getDateTo()->format(\DateTimeInterface::ATOM),
            ], $healthPoints),
            'activeBattle' => $activeSession !== null ? [
                'id' => $activeSession->getId()->toRfc4122(),
                'status' => $activeSession->getStatus()->value,
                'mode' => $activeSession->getMode()->value,
            ] : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function reset(User $user, bool $hard): array
    {
        $deleted = [
            'inventory' => 0,
            'experienceLogs' => 0,
            'healthPoints' => 0,
            'sessions' => 0,
            'linkedAccounts' => 0,
        ];

        // Inventory — always hard-delete here (test reset, not a GDPR delete).
        foreach ($this->userInventoryRepository->findActiveByUser($user) as $item) {
            $this->entityManager->remove($item);
            $deleted['inventory']++;
        }

        // Experience logs
        $logs = $this->experienceLogRepository->findBy(['user' => $user]);
        foreach ($logs as $log) {
            $this->entityManager->remove($log);
            $deleted['experienceLogs']++;
        }

        // Test-origin health data — never touches real HealthKit data.
        $healthPoints = $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(HealthDataPoint::class, 'h')
            ->where('h.user = :user')
            ->andWhere('h.sourceApp = :source')
            ->setParameter('user', $user)
            ->setParameter('source', HealthTestService::TEST_SOURCE_APP)
            ->getQuery()
            ->getResult();
        foreach ($healthPoints as $point) {
            $this->entityManager->remove($point);
            $deleted['healthPoints']++;
        }

        // Active / completed sessions belonging to the target user. Sessions
        // can cascade plenty of data, so we rely on Doctrine's normal remove().
        $sessions = $this->entityManager->getRepository(WorkoutSession::class)
            ->findBy(['user' => $user]);
        foreach ($sessions as $session) {
            $this->entityManager->remove($session);
            $deleted['sessions']++;
        }

        // Character stats: reset in place (keeps the 1:1 FK intact).
        $stats = $this->characterStatsRepository->findByUser($user);
        if ($stats !== null) {
            $stats->setStrength(0);
            $stats->setDexterity(0);
            $stats->setConstitution(0);
            $stats->setLevel(1);
            $stats->setTotalXp(0);
            $this->entityManager->persist($stats);
        }

        if ($hard) {
            $user->setOnboardingCompleted(false);
            $this->entityManager->persist($user);

            $linked = $this->entityManager->getRepository(LinkedAccount::class)
                ->findBy(['user' => $user]);
            foreach ($linked as $link) {
                $this->entityManager->remove($link);
                $deleted['linkedAccounts']++;
            }
        }

        $this->entityManager->flush();

        return $deleted;
    }
}
