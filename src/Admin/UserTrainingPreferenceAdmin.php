<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Sonata Admin for managing UserTrainingPreference entities via the admin panel.
 *
 * Allows admins to view and edit training preferences collected during onboarding.
 * Each record is 1:1 with a User entity.
 *
 * @extends AbstractAdmin<\App\Domain\User\Entity\UserTrainingPreference>
 */
class UserTrainingPreferenceAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('trainingFrequency', null, ['template' => null])
            ->add('lifestyle', null, ['template' => null])
            ->add('primaryTrainingStyle', null, ['template' => null])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('user.displayName');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, ['disabled' => !$this->isCurrentRoute('create')])
            ->add('trainingFrequency', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($f) => $f->name, TrainingFrequency::cases()),
                    TrainingFrequency::cases()
                ),
                'required' => false,
            ])
            ->add('lifestyle', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($l) => $l->name, Lifestyle::cases()),
                    Lifestyle::cases()
                ),
                'required' => false,
            ])
            ->add('primaryTrainingStyle', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($t) => $t->name, WorkoutType::cases()),
                    WorkoutType::cases()
                ),
                'required' => false,
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('trainingFrequency')
            ->add('lifestyle')
            ->add('primaryTrainingStyle')
            ->add('preferredWorkouts');
    }
}
