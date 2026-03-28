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

/**
 * Sonata Admin for managing ActivityCategory entities via the admin panel.
 *
 * Allows admins to view and edit the 16 RPG activity categories that group
 * related activity types and professions (e.g. Combat, Running, Swimming).
 *
 * @extends AbstractAdmin<\App\Domain\Activity\Entity\ActivityCategory>
 */
class ActivityCategoryAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the category list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the category list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug');
    }

    /** Configure the form fields for creating and editing categories. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Basic Info', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('description', TextareaType::class, ['required' => false])
            ->end();
    }

    /** Configure the fields displayed in the category detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('description');
    }
}
