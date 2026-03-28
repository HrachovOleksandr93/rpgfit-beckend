<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Battle\Enum\SessionStatus;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Sonata Admin for managing WorkoutSession entities via the admin panel.
 *
 * Allows administrators to view and filter battle sessions by user, mode,
 * and status. Sessions are read-only in practice (created by the battle API),
 * but form fields are provided for emergency manual adjustments.
 *
 * @extends AbstractAdmin<\App\Domain\Battle\Entity\WorkoutSession>
 */
class WorkoutSessionAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the session list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user', null, ['label' => 'User'])
            ->add('mode', null, ['label' => 'Battle Mode'])
            ->add('status', null, ['label' => 'Status'])
            ->add('mob', null, ['label' => 'Mob'])
            ->add('mobHp', null, ['label' => 'Mob HP'])
            ->add('totalDamageDealt', null, ['label' => 'Damage Dealt'])
            ->add('xpAwarded', null, ['label' => 'XP Awarded'])
            ->add('startedAt')
            ->add('completedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the session list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user')
            ->add('mode')
            ->add('status');
    }

    /** Configure the form fields for editing sessions. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Session Info', ['class' => 'col-md-6'])
                ->add('user', null, ['disabled' => true])
                ->add('workoutPlan', null, ['disabled' => true])
                ->add('mob', null, ['disabled' => true])
                ->add('mode', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($m) => $m->name, BattleMode::cases()),
                        BattleMode::cases()
                    ),
                    'disabled' => true,
                ])
                ->add('status', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($s) => $s->name, SessionStatus::cases()),
                        SessionStatus::cases()
                    ),
                ])
            ->end()
            ->with('Stats', ['class' => 'col-md-6'])
                ->add('mobHp')
                ->add('mobXpReward')
                ->add('totalDamageDealt')
                ->add('xpAwarded')
            ->end();
    }

    /** Configure the fields displayed in the session detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user')
            ->add('workoutPlan')
            ->add('mob')
            ->add('mode')
            ->add('status')
            ->add('mobHp')
            ->add('mobXpReward')
            ->add('totalDamageDealt')
            ->add('xpAwarded')
            ->add('startedAt')
            ->add('completedAt')
            ->add('healthData');
    }
}
