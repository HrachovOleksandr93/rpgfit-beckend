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
 * Sonata Admin for managing WorkoutCategory entities via the admin panel.
 *
 * Allows admins to create and manage workout categories (e.g. "Cardio", "Strength").
 * These categories group exercise types and are part of the training configuration
 * hierarchy: WorkoutCategory -> ExerciseType -> ExerciseStatReward.
 *
 * @extends AbstractAdmin<\App\Domain\Training\Entity\WorkoutCategory>
 */
class WorkoutCategoryAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('description')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextareaType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->add('id')->add('name')->add('slug')->add('description');
    }
}
