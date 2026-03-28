<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
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
 * Sonata Admin for managing ItemCatalog entities via the admin panel.
 *
 * Allows game designers to create and manage item definitions in the game catalog.
 * Supports filtering by item type and rarity. Equipment items have slot and durability
 * fields; consumables have duration. Stat bonuses are managed via ItemStatBonusAdmin.
 *
 * @extends AbstractAdmin<\App\Domain\Inventory\Entity\ItemCatalog>
 */
class ItemCatalogAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('itemType', null, ['template' => null])
            ->add('rarity', null, ['template' => null])
            ->add('slot', null, ['template' => null])
            ->add('stackable')
            ->add('twoHanded')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Basic Info', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('description', TextareaType::class, ['required' => false])
                ->add('icon', TextType::class, ['required' => false])
            ->end()
            ->with('Type & Rarity', ['class' => 'col-md-6'])
                ->add('itemType', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($t) => $t->name, ItemType::cases()),
                        ItemType::cases()
                    ),
                ])
                ->add('rarity', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($r) => $r->name, ItemRarity::cases()),
                        ItemRarity::cases()
                    ),
                ])
            ->end()
            ->with('Equipment Properties', ['class' => 'col-md-6'])
                ->add('slot', ChoiceType::class, [
                    'required' => false,
                    'choices' => array_combine(
                        array_map(fn($s) => $s->name, EquipmentSlot::cases()),
                        EquipmentSlot::cases()
                    ),
                ])
                ->add('durability', IntegerType::class, ['required' => false])
                ->add('twoHanded', CheckboxType::class, ['required' => false, 'help' => 'Only relevant for weapons'])
            ->end()
            ->with('Media', ['class' => 'col-md-6'])
                ->add('image', null, ['required' => false])
            ->end()
            ->with('Consumable Properties', ['class' => 'col-md-6'])
                ->add('duration', IntegerType::class, ['required' => false, 'help' => 'Duration in minutes'])
                ->add('stackable', CheckboxType::class, ['required' => false])
                ->add('maxStack', IntegerType::class)
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('name')->add('slug')->add('description')
            ->add('itemType')->add('rarity')->add('icon')
            ->add('slot')->add('durability')->add('duration')
            ->add('stackable')->add('maxStack')->add('twoHanded')->add('image');
    }
}
