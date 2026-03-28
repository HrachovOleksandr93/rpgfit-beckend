<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Inventory\Enum\ItemRarity;
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
 * Sonata Admin for managing Mob entities via the admin panel.
 *
 * Allows game designers to create, view, filter, and edit mob definitions.
 * Supports filtering by level range, rarity, and name search.
 *
 * @extends AbstractAdmin<\App\Domain\Mob\Entity\Mob>
 */
class MobAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the mob list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('level')
            ->add('hp')
            ->add('xpReward')
            ->add('rarity', null, ['template' => null])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the mob list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('level')
            ->add('rarity');
    }

    /** Configure the form fields for creating and editing mobs. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Basic Info', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('description', TextareaType::class, ['required' => false])
            ->end()
            ->with('Stats', ['class' => 'col-md-6'])
                ->add('level', IntegerType::class)
                ->add('hp', IntegerType::class, ['label' => 'Hit Points'])
                ->add('xpReward', IntegerType::class, ['label' => 'XP Reward'])
                ->add('rarity', ChoiceType::class, [
                    'required' => false,
                    'choices' => array_combine(
                        array_map(fn($r) => $r->name, ItemRarity::cases()),
                        ItemRarity::cases()
                    ),
                ])
            ->end()
            ->with('Media', ['class' => 'col-md-6'])
                ->add('image', null, ['required' => false])
            ->end();
    }

    /** Configure the fields displayed in the mob detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('level')
            ->add('hp')
            ->add('xpReward')
            ->add('rarity')
            ->add('description')
            ->add('image')
            ->add('createdAt');
    }
}
