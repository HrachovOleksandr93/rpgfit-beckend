<?php

declare(strict_types=1);

namespace App\Infrastructure\Health\Repository;

use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HealthDataPoint>
 */
class HealthDataPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthDataPoint::class);
    }

    public function findByUserAndExternalUuid(User $user, string $externalUuid): ?HealthDataPoint
    {
        return $this->findOneBy([
            'user' => $user,
            'externalUuid' => $externalUuid,
        ]);
    }

    /**
     * @return HealthDataPoint[]
     */
    public function findByUserDateAndType(
        User $user,
        HealthDataType $type,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        return $this->createQueryBuilder('h')
            ->where('h.user = :user')
            ->andWhere('h.dataType = :type')
            ->andWhere('h.dateFrom >= :from')
            ->andWhere('h.dateFrom < :to')
            ->setParameter('user', $user)
            ->setParameter('type', $type->value)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('h.dateFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns aggregated health data for a given date.
     *
     * @return array<string, mixed>
     */
    public function getAggregatedByDate(User $user, \DateTimeImmutable $date): array
    {
        $dayStart = $date->setTime(0, 0, 0);
        $dayEnd = $date->setTime(23, 59, 59);

        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT data_type AS type, SUM(value) AS total, AVG(value) AS average, COUNT(*) AS count
                FROM health_data_points
                WHERE user_id = :userId
                  AND date_from >= :dayStart
                  AND date_from <= :dayEnd
                GROUP BY data_type';

        $result = $conn->executeQuery($sql, [
            'userId' => $user->getId()->toBinary(),
            'dayStart' => $dayStart->format('Y-m-d H:i:s'),
            'dayEnd' => $dayEnd->format('Y-m-d H:i:s'),
        ]);

        return $result->fetchAllAssociative();
    }

    public function save(HealthDataPoint $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @param HealthDataPoint[] $entities
     */
    public function batchSave(array $entities): void
    {
        $em = $this->getEntityManager();

        foreach ($entities as $i => $entity) {
            $em->persist($entity);

            // Flush every 50 entities for performance
            if (($i + 1) % 50 === 0) {
                $em->flush();
            }
        }

        $em->flush();
    }
}
