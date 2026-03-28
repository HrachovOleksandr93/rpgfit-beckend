<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing User entities via the admin panel.
 *
 * Allows admins to view, edit, and delete users. Displays account info (login, displayName),
 * physical data (height, weight), and RPG profile (character race, workout type, activity level,
 * desired goal). Password cannot be changed via admin (only via API).
 *
 * @extends AbstractAdmin<\App\Domain\User\Entity\User>
 */
class UserAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('login')
            ->add('displayName')
            ->add('characterRace', null, ['template' => null])
            ->add('workoutType', null, ['template' => null])
            ->add('activityLevel', null, ['template' => null])
            ->add('createdAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('login')->add('displayName');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Account', ['class' => 'col-md-6'])
                ->add('login', EmailType::class)
                ->add('displayName', TextType::class)
            ->end()
            ->with('Body', ['class' => 'col-md-6'])
                ->add('height', NumberType::class)
                ->add('weight', NumberType::class)
            ->end()
            ->with('RPG Profile', ['class' => 'col-md-6'])
                ->add('characterRace', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($r) => $r->name, CharacterRace::cases()),
                        CharacterRace::cases()
                    ),
                ])
                ->add('workoutType', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($t) => $t->name, WorkoutType::cases()),
                        WorkoutType::cases()
                    ),
                ])
                ->add('activityLevel', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($l) => $l->name, ActivityLevel::cases()),
                        ActivityLevel::cases()
                    ),
                ])
                ->add('desiredGoal', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($g) => $g->name, DesiredGoal::cases()),
                        DesiredGoal::cases()
                    ),
                ])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('login')->add('displayName')
            ->add('height')->add('weight')
            ->add('characterRace')->add('workoutType')
            ->add('activityLevel')->add('desiredGoal')
            ->add('createdAt')->add('updatedAt');
    }
}
