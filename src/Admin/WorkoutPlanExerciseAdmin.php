<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing WorkoutPlanExercise entities via the admin panel.
 *
 * Manages individual exercises within a workout plan, including their order,
 * sets/reps programming, and optional coaching notes.
 *
 * @extends AbstractAdmin<\App\Domain\Workout\Entity\WorkoutPlanExercise>
 */
class WorkoutPlanExerciseAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('workoutPlan', null, ['label' => 'Plan'])
            ->add('exercise.name', null, ['label' => 'Exercise'])
            ->add('orderIndex')
            ->add('sets')
            ->add('repsMin')
            ->add('repsMax')
            ->add('restSeconds')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('workoutPlan')
            ->add('exercise');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('workoutPlan', null, ['required' => true])
            ->add('exercise', null, ['required' => true])
            ->add('orderIndex', IntegerType::class)
            ->add('sets', IntegerType::class)
            ->add('repsMin', IntegerType::class)
            ->add('repsMax', IntegerType::class)
            ->add('restSeconds', IntegerType::class)
            ->add('notes', TextType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('workoutPlan')
            ->add('exercise.name', null, ['label' => 'Exercise'])
            ->add('orderIndex')
            ->add('sets')
            ->add('repsMin')
            ->add('repsMax')
            ->add('restSeconds')
            ->add('notes');
    }
}
