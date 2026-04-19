<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\UserRole;
use App\Domain\User\Enum\WorkoutType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Sonata Admin for managing User entities via the admin panel.
 *
 * Allows admins to view, edit, and delete users. Displays account info (login, displayName),
 * physical data (height, weight), and RPG profile (workout type, activity level, desired goal).
 * Password cannot be changed via admin (only via API).
 *
 * Phase 5 additions:
 *  - `roles` multi-select (ROLE_USER is preselected and cannot be toggled off).
 *  - Only ROLE_SUPERADMIN can edit another user's roles.
 *  - Editing one's own roles is never allowed (prevents lock-out / self-escalation).
 *
 * @extends AbstractAdmin<User>
 */
class UserAdmin extends AbstractAdmin
{
    public function __construct(private readonly Security $security)
    {
        parent::__construct();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('login')
            ->add('displayName')
            ->add('roles', null, ['template' => null])
            ->add('workoutType', null, ['template' => null])
            ->add('activityLevel', null, ['template' => null])
            ->add('createdAt')
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => ['show' => [], 'edit' => [], 'delete' => []],
            ]);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter->add('login')->add('displayName');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Account', ['class' => 'col-md-6'])
                ->add('login', EmailType::class)
                ->add('displayName', TextType::class)
            ->end()
            ->with('Body', ['class' => 'col-md-6'])
                ->add('height', NumberType::class)
                ->add('weight', NumberType::class)
            ->end()
            ->with('RPG Profile', ['class' => 'col-md-6'])
                ->add('workoutType', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($t) => $t->name, WorkoutType::cases()),
                        WorkoutType::cases()
                    ),
                ])
                ->add('activityLevel', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($l) => $l->name, ActivityLevel::cases()),
                        ActivityLevel::cases()
                    ),
                ])
                ->add('desiredGoal', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(fn($g) => $g->name, DesiredGoal::cases()),
                        DesiredGoal::cases()
                    ),
                ])
            ->end();

        // Roles are only editable by super-admins — and never on one's own account.
        if ($this->canEditRolesForSubject()) {
            $roleChoices = [];
            foreach (UserRole::cases() as $role) {
                $roleChoices[$role->label()] = $role->value;
            }

            $form
                ->with('Security', ['class' => 'col-md-6'])
                    ->add('roles', ChoiceType::class, [
                        'choices' => $roleChoices,
                        'multiple' => true,
                        'expanded' => true,
                        'required' => false,
                        'preferred_choices' => [UserRole::USER->value],
                        'help' => 'ROLE_USER is always enabled and cannot be removed.',
                        // ROLE_USER is disabled in the rendered form via the
                        // PRE_SET_DATA listener below; the submit handler also
                        // guarantees it stays in the stored list.
                        'choice_attr' => static function (string $choice): array {
                            return $choice === UserRole::USER->value
                                ? ['disabled' => 'disabled', 'checked' => 'checked']
                                : [];
                        },
                    ])
                ->end();

            /** @var \Symfony\Component\Form\FormBuilderInterface $builder */
            $builder = $form->getFormBuilder();
            $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
                $data = $event->getData();
                if (!is_array($data)) {
                    return;
                }

                $roles = $data['roles'] ?? [];
                if (!is_array($roles)) {
                    $roles = [];
                }
                if (!in_array(UserRole::USER->value, $roles, true)) {
                    $roles[] = UserRole::USER->value;
                }
                $data['roles'] = array_values(array_unique($roles));
                $event->setData($data);
            });
        }
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')->add('login')->add('displayName')
            ->add('roles')
            ->add('height')->add('weight')
            ->add('workoutType')
            ->add('activityLevel')->add('desiredGoal')
            ->add('createdAt')->add('updatedAt');
    }

    /**
     * Validation hook: block self-role-edit and require SUPERADMIN for any role
     * mutation. Runs whether or not the form was rendered, so a tampered POST
     * cannot slip through.
     *
     * @param User $object
     */
    protected function prePersist(object $object): void
    {
        $this->assertRoleMutationAllowed($object);
    }

    /**
     * @param User $object
     */
    protected function preUpdate(object $object): void
    {
        $this->assertRoleMutationAllowed($object);
    }

    private function assertRoleMutationAllowed(User $object): void
    {
        // New user or no role field submitted — nothing to validate.
        $originalRoles = null;
        try {
            if ($this->hasSubject() && $this->getSubject() instanceof User) {
                $originalRoles = $this->getSubject()->getRoles();
            }
        } catch (\Throwable) {
            $originalRoles = null;
        }

        $newRoles = $object->getRoles();
        if ($originalRoles !== null) {
            sort($originalRoles);
            sort($newRoles);
            if ($originalRoles === $newRoles) {
                return;
            }
        }

        if (!$this->security->isGranted(UserRole::SUPERADMIN->value)) {
            throw new \RuntimeException('Only ROLE_SUPERADMIN can modify user roles.');
        }

        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User
            && $currentUser->getId()->toRfc4122() === $object->getId()->toRfc4122()) {
            throw new \RuntimeException('You cannot modify your own roles.');
        }
    }

    /**
     * The `roles` form field is only rendered when:
     *   - the viewer is ROLE_SUPERADMIN; and
     *   - the subject is either new or not the viewer themselves.
     */
    private function canEditRolesForSubject(): bool
    {
        if (!$this->security->isGranted(UserRole::SUPERADMIN->value)) {
            return false;
        }

        try {
            if (!$this->hasSubject()) {
                return true;
            }
            $subject = $this->getSubject();
        } catch (\Throwable) {
            return true;
        }

        if (!$subject instanceof User) {
            return true;
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return true;
        }

        return $currentUser->getId()->toRfc4122() !== $subject->getId()->toRfc4122();
    }
}
