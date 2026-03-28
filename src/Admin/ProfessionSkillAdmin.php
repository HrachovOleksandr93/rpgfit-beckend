<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Sonata Admin for managing ProfessionSkill junction entities via the admin panel.
 *
 * Allows game designers to view and manage which skills are assigned to which
 * professions. Each row links one profession to one skill.
 *
 * @extends AbstractAdmin<\App\Domain\Activity\Entity\ProfessionSkill>
 */
class ProfessionSkillAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the profession-skill list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('profession', null, ['label' => 'Profession'])
            ->add('skill', null, ['label' => 'Skill'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the profession-skill list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('profession')
            ->add('skill');
    }

    /** Configure the form fields for creating and editing profession-skill links. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('profession', null, ['required' => true])
            ->add('skill', null, ['required' => true]);
    }

    /** Configure the fields displayed in the profession-skill detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('profession')
            ->add('skill');
    }
}
