<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing WorkoutLog entries via the admin panel.
 *
 * Allows admins to view, create, edit, and delete workout session records.
 * Displays workout type, duration, calories, distance, and timing information.
 * Can also be used to manually create workout entries for testing.
 *
 * @extends AbstractAdmin<\App\Domain\Training\Entity\WorkoutLog>
 */
class WorkoutLogAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('workoutType')
            ->add('durationMinutes')
            ->add('caloriesBurned')
            ->add('distance')
            ->add('performedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('user.displayName')->add('workoutType');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Workout', ['class' => 'col-md-6'])
                ->add('user', null, ['required' => true])
                ->add('workoutType', TextType::class)
                ->add('durationMinutes', NumberType::class)
                ->add('caloriesBurned', NumberType::class, ['required' => false])
                ->add('distance', NumberType::class, ['required' => false])
            ->end()
            ->with('Timing', ['class' => 'col-md-6'])
                ->add('performedAt', DateTimeType::class, [
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('user.displayName', null, ['label' => 'Player'])
            ->add('workoutType')->add('durationMinutes')
            ->add('caloriesBurned')->add('distance')
            ->add('extraDetails')
            ->add('performedAt')->add('createdAt');
    }
}
