<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing GameSetting entities via the admin panel.
 *
 * Allows designers and admins to tune all game parameters (XP rates, caps,
 * leveling curve, streak bonuses) without deploying code changes. Settings
 * are grouped by category for easy navigation.
 *
 * @extends AbstractAdmin<\App\Domain\Config\Entity\GameSetting>
 */
class GameSettingAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('category')
            ->addIdentifier('key')
            ->add('value', null, ['editable' => true])
            ->add('description')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('category')
            ->add('key');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('category', TextType::class)
            ->add('key', TextType::class)
            ->add('value', TextType::class)
            ->add('description', TextType::class, ['required' => false]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('category')
            ->add('key')
            ->add('value')
            ->add('description');
    }
}
