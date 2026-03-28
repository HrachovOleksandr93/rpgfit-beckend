<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Character\Enum\StatType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing Profession entities via the admin panel.
 *
 * Allows game designers to view and edit the 48 RPG professions
 * (3 tiers per 16 categories) with their stat assignments and descriptions.
 *
 * @extends AbstractAdmin<\App\Domain\Activity\Entity\Profession>
 */
class ProfessionAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the profession list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('tier')
            ->add('category')
            ->add('primaryStat', null, ['template' => null])
            ->add('secondaryStat', null, ['template' => null])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the profession list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('tier')
            ->add('category');
    }

    /** Configure the form fields for creating and editing professions. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Basic Info', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('tier', IntegerType::class)
                ->add('description', TextareaType::class, ['required' => false])
            ->end()
            ->with('Stats', ['class' => 'col-md-6'])
                ->add('primaryStat', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($s) => $s->name, StatType::cases()),
                        StatType::cases()
                    ),
                ])
                ->add('secondaryStat', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($s) => $s->name, StatType::cases()),
                        StatType::cases()
                    ),
                ])
                ->add('category', null, ['required' => true])
            ->end()
            ->with('Media', ['class' => 'col-md-6'])
                ->add('image', null, ['required' => false])
            ->end();
    }

    /** Configure the fields displayed in the profession detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('tier')
            ->add('description')
            ->add('primaryStat')
            ->add('secondaryStat')
            ->add('category')
            ->add('image');
    }
}
