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
 * Sonata Admin for managing ExerciseType entities via the admin panel.
 *
 * Allows admins to create and manage specific exercises within categories
 * (e.g. "Running" in Cardio, "Bench Press" in Strength). Each exercise type
 * can have stat rewards configured via ExerciseStatRewardAdmin.
 *
 * @extends AbstractAdmin<\App\Domain\Training\Entity\ExerciseType>
 */
class ExerciseTypeAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('category.name', null, ['label' => 'Category'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug')->add('category');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('category', null, ['required' => true])
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextareaType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('category.name', null, ['label' => 'Category'])
            ->add('name')->add('slug')->add('description');
    }
}
