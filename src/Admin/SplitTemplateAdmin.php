<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Workout\Enum\SplitType;
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
 * Sonata Admin for managing SplitTemplate entities via the admin panel.
 *
 * Allows admins to create, edit, and view training split templates that define
 * how muscle groups are distributed across weekly training days.
 *
 * @extends AbstractAdmin<\App\Domain\Workout\Entity\SplitTemplate>
 */
class SplitTemplateAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('splitType', null, ['template' => null])
            ->add('daysPerWeek')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('daysPerWeek');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('splitType', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($t) => $t->name, SplitType::cases()),
                    SplitType::cases()
                ),
            ])
            ->add('daysPerWeek', IntegerType::class)
            ->add('description', TextareaType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('splitType')
            ->add('daysPerWeek')
            ->add('dayConfigs')
            ->add('description');
    }
}
