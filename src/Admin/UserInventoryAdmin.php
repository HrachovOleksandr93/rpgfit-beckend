<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * Sonata Admin for viewing and managing UserInventory entities via the admin panel.
 *
 * Allows admins to view user item ownership, filter by equipped status, and see
 * soft-deleted items. Supports editing quantity, equipped status, and durability.
 *
 * @extends AbstractAdmin<\App\Domain\Inventory\Entity\UserInventory>
 */
class UserInventoryAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user', null, ['label' => 'User'])
            ->add('itemCatalog.name', null, ['label' => 'Item'])
            ->add('quantity')
            ->add('equipped')
            ->add('currentDurability')
            ->add('obtainedAt')
            ->add('expiresAt')
            ->add('deletedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('user')->add('equipped');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, ['required' => true])
            ->add('itemCatalog', null, ['required' => true])
            ->add('quantity', IntegerType::class)
            ->add('equipped', CheckboxType::class, ['required' => false])
            ->add('currentDurability', IntegerType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('user')->add('itemCatalog.name', null, ['label' => 'Item'])
            ->add('quantity')->add('equipped')->add('currentDurability')
            ->add('obtainedAt')->add('expiresAt')->add('deletedAt');
    }
}
