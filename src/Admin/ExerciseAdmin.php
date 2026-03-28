<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Workout\Enum\Equipment;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\ExerciseMovementType;
use App\Domain\Workout\Enum\MuscleGroup;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing Exercise entities via the admin panel.
 *
 * Provides list view with muscle group, equipment, and difficulty filters.
 * Form includes all exercise fields including default programming parameters.
 *
 * @extends AbstractAdmin<\App\Domain\Workout\Entity\Exercise>
 */
class ExerciseAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('primaryMuscle', null, ['template' => null])
            ->add('equipment', null, ['template' => null])
            ->add('difficulty', null, ['template' => null])
            ->add('movementType', null, ['template' => null])
            ->add('priority')
            ->add('isBaseExercise')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('primaryMuscle')
            ->add('equipment')
            ->add('difficulty');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('General', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('description', TextareaType::class, ['required' => false])
                ->add('image', null, ['required' => false])
            ->end()
            ->with('Classification', ['class' => 'col-md-6'])
                ->add('primaryMuscle', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($m) => $m->name, MuscleGroup::cases()),
                        MuscleGroup::cases()
                    ),
                ])
                ->add('equipment', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($e) => $e->name, Equipment::cases()),
                        Equipment::cases()
                    ),
                ])
                ->add('difficulty', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($d) => $d->name, ExerciseDifficulty::cases()),
                        ExerciseDifficulty::cases()
                    ),
                ])
                ->add('movementType', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($t) => $t->name, ExerciseMovementType::cases()),
                        ExerciseMovementType::cases()
                    ),
                ])
                ->add('priority', IntegerType::class)
                ->add('isBaseExercise', CheckboxType::class, ['required' => false])
            ->end()
            ->with('Default Programming', ['class' => 'col-md-6'])
                ->add('defaultSets', IntegerType::class)
                ->add('defaultRepsMin', IntegerType::class)
                ->add('defaultRepsMax', IntegerType::class)
                ->add('defaultRestSeconds', IntegerType::class)
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('primaryMuscle')
            ->add('secondaryMuscles')
            ->add('equipment')
            ->add('difficulty')
            ->add('movementType')
            ->add('priority')
            ->add('isBaseExercise')
            ->add('description')
            ->add('defaultSets')
            ->add('defaultRepsMin')
            ->add('defaultRepsMax')
            ->add('defaultRestSeconds');
    }
}
