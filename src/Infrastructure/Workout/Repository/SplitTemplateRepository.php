<?php

declare(strict_types=1);

namespace App\Infrastructure\Workout\Repository;

use App\Domain\Workout\Entity\SplitTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for SplitTemplate entities.
 *
 * Infrastructure layer (Workout bounded context). Provides data access for
 * training split templates used to generate weekly workout schedules.
 *
 * @extends ServiceEntityRepository<SplitTemplate>
 */
class SplitTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SplitTemplate::class);
    }

    public function save(SplitTemplate $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find templates matching the given number of training days per week.
     *
     * @return SplitTemplate[]
     */
    public function findByDaysPerWeek(int $daysPerWeek): array
    {
        return $this->findBy(['daysPerWeek' => $daysPerWeek]);
    }

    public function findBySlug(string $slug): ?SplitTemplate
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
