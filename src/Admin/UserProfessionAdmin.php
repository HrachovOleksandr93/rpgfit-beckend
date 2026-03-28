<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Sonata Admin for managing UserProfession entities via the admin panel.
 *
 * Allows admins to view and manage which professions users have unlocked
 * and which are currently active in each activity category.
 *
 * @extends AbstractAdmin<\App\Domain\Activity\Entity\UserProfession>
 */
class UserProfessionAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the user profession list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user')
            ->add('profession')
            ->add('active')
            ->add('unlockedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the user profession list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user')
            ->add('profession')
            ->add('active');
    }

    /** Configure the form fields for creating and editing user professions. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Assignment', ['class' => 'col-md-6'])
                ->add('user', null, ['required' => true])
                ->add('profession', null, ['required' => true])
                ->add('active', CheckboxType::class, ['required' => false])
            ->end();
    }

    /** Configure the fields displayed in the user profession detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user')
            ->add('profession')
            ->add('active')
            ->add('unlockedAt');
    }
}
