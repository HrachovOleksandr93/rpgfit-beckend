<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Workout\Enum\WorkoutPlanStatus;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing WorkoutPlan entities via the admin panel.
 *
 * Provides a list view filtered by user and plan status. Each plan represents
 * a scheduled workout session with exercises and optional cardio targets.
 *
 * @extends AbstractAdmin<\App\Domain\Workout\Entity\WorkoutPlan>
 */
class WorkoutPlanAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('status', null, ['template' => null])
            ->add('activityType')
            ->add('plannedAt')
            ->add('createdAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.displayName')
            ->add('status')
            ->add('activityType');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('General', ['class' => 'col-md-6'])
                ->add('user', null, ['required' => true])
                ->add('name', TextType::class)
                ->add('status', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($s) => $s->name, WorkoutPlanStatus::cases()),
                        WorkoutPlanStatus::cases()
                    ),
                ])
                ->add('activityType', TextType::class, ['required' => false])
            ->end()
            ->with('Schedule', ['class' => 'col-md-6'])
                ->add('plannedAt', DateTimeType::class, [
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                ])
                ->add('startedAt', DateTimeType::class, [
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'required' => false,
                ])
                ->add('completedAt', DateTimeType::class, [
                    'widget' => 'single_text',
                    'input' => 'datetime_immutable',
                    'required' => false,
                ])
            ->end()
            ->with('Cardio Targets', ['class' => 'col-md-6'])
                ->add('targetDistance', NumberType::class, ['required' => false])
                ->add('targetDuration', NumberType::class, ['required' => false])
                ->add('targetCalories', NumberType::class, ['required' => false])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('name')
            ->add('status')
            ->add('activityType')
            ->add('targetMuscleGroups')
            ->add('plannedAt')
            ->add('startedAt')
            ->add('completedAt')
            ->add('targetDistance')
            ->add('targetDuration')
            ->add('targetCalories')
            ->add('rewardTiers')
            ->add('createdAt');
    }
}
