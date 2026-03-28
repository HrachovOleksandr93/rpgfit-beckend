<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Sonata Admin for viewing and managing UserSkill entities via the admin panel.
 *
 * Allows admins to view which skills users have unlocked and when. Supports
 * assigning new skills to users and filtering by user.
 *
 * @extends AbstractAdmin<\App\Domain\Skill\Entity\UserSkill>
 */
class UserSkillAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user', null, ['label' => 'User'])
            ->add('skill.name', null, ['label' => 'Skill'])
            ->add('unlockedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('user')->add('skill');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, ['required' => true])
            ->add('skill', null, ['required' => true]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('user')->add('skill.name', null, ['label' => 'Skill'])
            ->add('unlockedAt');
    }
}
