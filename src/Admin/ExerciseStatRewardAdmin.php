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

/**
 * Sonata Admin for managing ExerciseStatReward configuration via the admin panel.
 *
 * Allows game designers to configure how many RPG stat points (STR/DEX/CON) each
 * exercise type awards. This is the key balancing tool for the RPG progression system.
 * Example: "Running" -> DEX +3, "Bench Press" -> STR +5, "Swimming" -> CON +4.
 *
 * @extends AbstractAdmin<\App\Domain\Training\Entity\ExerciseStatReward>
 */
class ExerciseStatRewardAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('exerciseType.name', null, ['label' => 'Exercise'])
            ->add('exerciseType.category.name', null, ['label' => 'Category'])
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
            ->add('statType')->add('points');
    }
}
