<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Portal\Enum\PortalType;
use App\Domain\Shared\Enum\Realm;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Sonata Admin for Portal entities. Supports curating static portals,
 * monitoring dynamic/user-created portals, and filtering by realm/type.
 *
 * @extends AbstractAdmin<\App\Domain\Portal\Entity\Portal>
 */
class PortalAdmin extends AbstractAdmin
{
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('name')
            ->add('slug')
            ->add('type', null, ['template' => null])
            ->add('realm', null, ['template' => null])
            ->add('tier')
            ->add('latitude')
            ->add('longitude')
            ->add('expiresAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
            ->add('slug')
            ->add('type')
            ->add('realm')
            ->add('tier');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Identity', ['class' => 'col-md-6'])
                ->add('name', TextType::class)
                ->add('slug', TextType::class)
                ->add('type', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn ($t) => $t->name, PortalType::cases()),
                        PortalType::cases(),
                    ),
                ])
                ->add('realm', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn ($r) => $r->name, Realm::cases()),
                        Realm::cases(),
                    ),
                ])
            ->end()
            ->with('Location', ['class' => 'col-md-6'])
                ->add('latitude', NumberType::class, ['scale' => 6])
                ->add('longitude', NumberType::class, ['scale' => 6])
                ->add('radiusM', IntegerType::class, ['label' => 'Radius (m)'])
                ->add('tier', IntegerType::class)
            ->end()
            ->with('Challenge & Reward', ['class' => 'col-md-12'])
                ->add('challengeType', TextType::class, ['required' => false])
                ->add('rewardArtifactSlug', TextType::class, ['required' => false])
                ->add('maxBattles', IntegerType::class, ['required' => false])
            ->end();
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('name')
            ->add('slug')
            ->add('type')
            ->add('realm')
            ->add('latitude')
            ->add('longitude')
            ->add('radiusM')
            ->add('tier')
            ->add('challengeType')
            ->add('challengeParams')
            ->add('rewardArtifactSlug')
            ->add('virtualReplicaOf')
            ->add('createdByUser')
            ->add('expiresAt')
            ->add('maxBattles')
            ->add('createdAt');
    }
}
