<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing ExperienceLog entries via the admin panel.
 *
 * Allows admins to view, create, edit, and delete XP gain events. Useful for manually
 * awarding bonus XP, viewing a user's XP history, or debugging progression issues.
 * Each entry shows the player, amount, source, description, and timestamp.
 *
 * @extends AbstractAdmin<\App\Domain\Character\Entity\ExperienceLog>
 */
class ExperienceLogAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('user.displayName', null, ['label' => 'Player'])
            ->add('amount')
            ->add('source')
            ->add('description')
            ->add('earnedAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('user.displayName')->add('source');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('user', null, ['required' => true])
            ->add('amount', IntegerType::class)
            ->add('source', TextType::class)
            ->add('description', TextType::class, ['required' => false])
            ->add('earnedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('user.displayName', null, ['label' => 'Player'])
            ->add('amount')->add('source')->add('description')->add('earnedAt');
    }
}
