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
 * Sonata Admin for managing ItemStatBonus entities via the admin panel.
 *
 * Allows game designers to configure how many RPG stat points (STR/DEX/CON) each
 * catalog item provides as a bonus. Follows the same pattern as ExerciseStatRewardAdmin.
 *
 * @extends AbstractAdmin<\App\Domain\Inventory\Entity\ItemStatBonus>
 */
class ItemStatBonusAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('itemCatalog.name', null, ['label' => 'Item'])
            ->add('statType', null, ['template' => null])
            ->add('points')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('itemCatalog');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('itemCatalog', null, ['required' => true])
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
            ->add('itemCatalog.name', null, ['label' => 'Item'])
            ->add('statType')->add('points');
    }
}
