<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffRole: string
{
    case Owner = 'owner';
    case HeadVet = 'head_vet';
    case FieldDoctor = 'field_doctor';
    case VetTech = 'vet_tech';
    case Receptionist = 'receptionist';

    public function canManageStaff(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet], true);
    }

    public function canWitnessControlledSubstances(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet, self::FieldDoctor, self::VetTech], true);
    }

    public function canAccessOwnerDashboard(): bool
    {
        return $this === self::Owner;
    }

    public function canPrescribeMedication(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet, self::FieldDoctor], true);
    }

    public function canPerformSurgery(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet], true);
    }

    /**
     * Get the capability matrix for client-side adaptive UI routing.
     *
     * @return array<string, bool>
     */
    public function capabilities(): array
    {
        return [
            'can_manage_staff' => $this->canManageStaff(),
            'can_witness_controlled_substances' => $this->canWitnessControlledSubstances(),
            'can_access_owner_dashboard' => $this->canAccessOwnerDashboard(),
            'can_prescribe_medication' => $this->canPrescribeMedication(),
            'can_perform_surgery' => $this->canPerformSurgery(),
        ];
    }
}
