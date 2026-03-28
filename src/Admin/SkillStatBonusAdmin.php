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

/**
 * Sonata Admin for managing SkillStatBonus entities via the admin panel.
 *
 * Allows game designers to configure how many RPG stat points (STR/DEX/CON) each
 * skill provides as a passive bonus. This is the key balancing tool for the skill system.
 *
 * @extends AbstractAdmin<\App\Domain\Skill\Entity\SkillStatBonus>
 */
class SkillStatBonusAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->add('skill.name', null, ['label' => 'Skill'])
            ->add('statType', null, ['template' => null])
            ->add('points')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('skill');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('skill', null, ['required' => true])
            ->add('statType', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($s) => $s->name, StatType::cases()),
                    StatType::cases()
                ),
            ])
            ->add('points', IntegerType::class);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('skill.name', null, ['label' => 'Skill'])
            ->add('statType')->add('points');
    }
}
