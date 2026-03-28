<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for managing Skill entities via the admin panel.
 *
 * Allows game designers to create and manage RPG skills. Each skill has a name,
 * slug, description, icon, and required level. Stat bonuses are managed separately
 * via SkillStatBonusAdmin.
 *
 * @extends AbstractAdmin<\App\Domain\Skill\Entity\Skill>
 */
class SkillAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('requiredLevel')
            ->add('icon')
            ->add('description')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('name')->add('slug')->add('requiredLevel');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class)
            ->add('slug', TextType::class)
            ->add('description', TextareaType::class, ['required' => false])
            ->add('icon', TextType::class, ['required' => false])
            ->add('image', null, ['required' => false])
            ->add('requiredLevel', IntegerType::class);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('name')->add('slug')
            ->add('description')->add('icon')->add('image')->add('requiredLevel');
    }
}
