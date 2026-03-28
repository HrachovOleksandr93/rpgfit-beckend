<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing ActivityType entities via the admin panel.
 *
 * Allows admins to view and edit the 99 activity types mapped from Flutter
 * health package enums, with platform support and native type identifiers.
 *
 * @extends AbstractAdmin<\App\Domain\Activity\Entity\ActivityType>
 */
class ActivityTypeAdmin extends AbstractAdmin
{
    /** Configure the columns displayed in the activity type list view. */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('flutterEnum')
            ->add('platformSupport')
            ->add('category')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    /** Configure the filters available in the activity type list view. */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('slug')
            ->add('platformSupport')
            ->add('category');
    }

    /** Configure the form fields for creating and editing activity types. */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Basic Info', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('flutterEnum', TextType::class)
            ->end()
            ->with('Platform', ['class' => 'col-md-6'])
                ->add('platformSupport', ChoiceType::class, [
                    'choices' => [
                        'Universal' => 'universal',
                        'iOS Only' => 'ios_only',
                        'Android Only' => 'android_only',
                    ],
                ])
                ->add('iosNative', TextType::class, ['required' => false])
                ->add('androidNative', TextType::class, ['required' => false])
                ->add('fallbackSlug', TextType::class, ['required' => false])
                ->add('category', null, ['required' => true])
            ->end();
    }

    /** Configure the fields displayed in the activity type detail (show) view. */
    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('flutterEnum')
            ->add('iosNative')
            ->add('androidNative')
            ->add('platformSupport')
            ->add('fallbackSlug')
            ->add('category');
    }
}
