<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * Sonata Admin for managing CharacterStats entities via the admin panel.
 *
 * Allows admins to view and manually adjust RPG character stats (STR/DEX/CON) per user.
 * Useful for testing, debugging, or manual corrections. In production, stats will be
 * updated automatically by game logic when workouts are completed.
 *
 * @extends AbstractAdmin<\App\Domain\Character\Entity\CharacterStats>
 */
class CharacterStatsAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('strength')->add('dexterity')->add('constitution')
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
            ->add('id')->add('user.displayName', null, ['label' => 'Player'])
            ->add('strength')->add('dexterity')->add('constitution')
            ->add('updatedAt');
    }
}
