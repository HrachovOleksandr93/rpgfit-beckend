<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\User\Enum\OAuthProvider;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Sonata Admin for managing LinkedAccount entities via the admin panel.
 *
 * Allows admins to view and filter OAuth linked accounts by user and provider.
 * Displays the provider, email, and linked date for each account.
 *
 * @extends AbstractAdmin<\App\Domain\User\Entity\LinkedAccount>
 */
class LinkedAccountAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user')
            ->add('provider', null, ['template' => null])
            ->add('email')
            ->add('providerUserId')
            ->add('linkedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user')
            ->add('provider')
            ->add('email');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user')
            ->add('provider', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($p) => $p->name, OAuthProvider::cases()),
                    OAuthProvider::cases()
                ),
            ])
            ->add('providerUserId')
            ->add('email');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('user')
            ->add('provider')
            ->add('providerUserId')
            ->add('email')
            ->add('linkedAt');
    }
}
