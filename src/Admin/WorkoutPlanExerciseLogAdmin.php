<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing WorkoutPlanExerciseLog entries via the admin panel.
 *
 * Allows admins to view and edit individual set performance logs,
 * including actual reps, weight, duration, and completion timestamps.
 *
 * @extends AbstractAdmin<\App\Domain\Workout\Entity\WorkoutPlanExerciseLog>
 */
class WorkoutPlanExerciseLogAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('planExercise', null, ['label' => 'Plan Exercise'])
            ->add('setNumber')
            ->add('reps')
            ->add('weight')
            ->add('duration')
            ->add('completedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('planExercise');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('planExercise', null, ['required' => true])
            ->add('setNumber', IntegerType::class)
            ->add('reps', IntegerType::class, ['required' => false])
            ->add('weight', NumberType::class, ['required' => false])
            ->add('duration', IntegerType::class, ['required' => false])
            ->add('notes', TextType::class, ['required' => false])
            ->add('completedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('planExercise')
            ->add('setNumber')
            ->add('reps')
            ->add('weight')
            ->add('duration')
            ->add('notes')
            ->add('completedAt');
    }
}
