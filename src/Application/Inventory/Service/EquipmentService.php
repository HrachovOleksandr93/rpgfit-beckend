<?php

declare(strict_types=1);

namespace App\Application\Inventory\Service;

use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\User\Entity\User;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;

/**
 * Manages equipment slot rules: equipping, unequipping, and slot constraints.
 *
 * Application layer (Inventory bounded context). Enforces game rules for equipment slots:
 * - Default: 1 item per slot
 * - Ring: max 2 equipped simultaneously
 * - Bracelet: max 2 equipped simultaneously
 * - Weapon: one-handed occupies weapon slot; two-handed occupies weapon AND removes shield
 * - Shield: cannot coexist with a two-handed weapon
 *
 * All slot conflict resolution is automatic: conflicting items are unequipped silently.
 */
class EquipmentService
{
    /** Maximum number of rings a character can wear at once */
    private const MAX_RINGS = 2;

    /** Maximum number of bracelets a character can wear at once */
    private const MAX_BRACELETS = 2;

    public function __construct(
        private readonly UserInventoryRepository $userInventoryRepository,
    ) {
    }

    /**
     * Equip an inventory item into its designated slot.
     *
     * Enforces all slot rules: single-slot limits, ring/bracelet maximums,
     * two-handed weapon interactions with shield, etc.
     *
     * @throws \InvalidArgumentException If the item is not an equipment type
     * @throws \LogicException           If ring/bracelet limit exceeded
     */
    public function equip(User $user, UserInventory $inventoryItem): void
    {
        $catalogItem = $inventoryItem->getItemCatalog();

        // Only equipment-type items can be equipped
        if ($catalogItem->getItemType() !== ItemType::Equipment) {
            throw new \InvalidArgumentException('Only equipment items can be equipped.');
        }

        $slot = $catalogItem->getSlot();
        if ($slot === null) {
            throw new \InvalidArgumentException('This equipment item has no assigned slot.');
        }

        // Apply slot-specific rules before equipping
        $this->applySlotRules($user, $catalogItem, $slot);

        // Mark item as equipped in the designated slot
        $inventoryItem->setEquipped(true);
        $inventoryItem->setEquippedSlot($slot);

        $this->userInventoryRepository->save($inventoryItem);
    }

    /**
     * Unequip an inventory item, clearing its slot assignment.
     */
    public function unequip(User $user, UserInventory $inventoryItem): void
    {
        $inventoryItem->setEquipped(false);
        $inventoryItem->setEquippedSlot(null);

        $this->userInventoryRepository->save($inventoryItem);
    }

    /**
     * Get all currently equipped items for a user.
     *
     * @return UserInventory[]
     */
    public function getEquippedItems(User $user): array
    {
        return $this->userInventoryRepository->findEquippedByUser($user);
    }

    /**
     * Apply slot-specific rules before equipping an item.
     *
     * Handles weapon/shield two-handed conflicts, ring/bracelet limits,
     * and standard single-slot replacement.
     */
    private function applySlotRules(User $user, \App\Domain\Inventory\Entity\ItemCatalog $catalogItem, EquipmentSlot $slot): void
    {
        switch ($slot) {
            case EquipmentSlot::Weapon:
                $this->handleWeaponEquip($user, $catalogItem);
                break;

            case EquipmentSlot::Shield:
                $this->handleShieldEquip($user);
                break;

            case EquipmentSlot::Ring:
                $this->handleMultiSlotEquip($user, EquipmentSlot::Ring, self::MAX_RINGS);
                break;

            case EquipmentSlot::Bracelet:
                $this->handleMultiSlotEquip($user, EquipmentSlot::Bracelet, self::MAX_BRACELETS);
                break;

            default:
                // Standard slot: unequip any existing item in this slot
                $this->unequipSlot($user, $slot);
                break;
        }
    }

    /**
     * Handle weapon equip rules.
     *
     * Two-handed weapon: unequip current weapon(s) AND shield.
     * One-handed weapon: if current weapon is two-handed, unequip it; otherwise just replace.
     */
    private function handleWeaponEquip(User $user, \App\Domain\Inventory\Entity\ItemCatalog $catalogItem): void
    {
        if ($catalogItem->isTwoHanded()) {
            // Two-handed weapon: clear weapon slot and shield slot
            $this->unequipSlot($user, EquipmentSlot::Weapon);
            $this->unequipSlot($user, EquipmentSlot::Shield);
        } else {
            // One-handed weapon: check if current weapon is two-handed
            $currentWeapons = $this->userInventoryRepository->findEquippedBySlot($user, EquipmentSlot::Weapon);
            foreach ($currentWeapons as $weapon) {
                if ($weapon->getItemCatalog()->isTwoHanded()) {
                    // Removing two-handed weapon frees both weapon and shield slots
                    $this->unequipItem($weapon);
                } else {
                    // Replace existing one-handed weapon
                    $this->unequipItem($weapon);
                }
            }
        }
    }

    /**
     * Handle shield equip rules.
     *
     * If a two-handed weapon is currently equipped, unequip it first.
     * Otherwise just replace the existing shield.
     */
    private function handleShieldEquip(User $user): void
    {
        // Check if a two-handed weapon is equipped — must remove it
        $currentWeapons = $this->userInventoryRepository->findEquippedBySlot($user, EquipmentSlot::Weapon);
        foreach ($currentWeapons as $weapon) {
            if ($weapon->getItemCatalog()->isTwoHanded()) {
                $this->unequipItem($weapon);
            }
        }

        // Replace existing shield
        $this->unequipSlot($user, EquipmentSlot::Shield);
    }

    /**
     * Handle multi-slot equip (ring, bracelet) with a maximum count.
     *
     * If the max is reached, the oldest equipped item in that slot is removed.
     */
    private function handleMultiSlotEquip(User $user, EquipmentSlot $slot, int $maxCount): void
    {
        $currentItems = $this->userInventoryRepository->findEquippedBySlot($user, $slot);

        // If at max capacity, unequip the oldest item (first in the array)
        if (count($currentItems) >= $maxCount) {
            // Sort by obtainedAt to find the oldest
            usort($currentItems, fn(UserInventory $a, UserInventory $b) =>
                $a->getObtainedAt() <=> $b->getObtainedAt()
            );
            $this->unequipItem($currentItems[0]);
        }
    }

    /** Unequip all items in a given slot for a user. */
    private function unequipSlot(User $user, EquipmentSlot $slot): void
    {
        $items = $this->userInventoryRepository->findEquippedBySlot($user, $slot);
        foreach ($items as $item) {
            $this->unequipItem($item);
        }
    }

    /** Mark a single inventory item as unequipped. */
    private function unequipItem(UserInventory $item): void
    {
        $item->setEquipped(false);
        $item->setEquippedSlot(null);
        $this->userInventoryRepository->save($item);
    }
}
