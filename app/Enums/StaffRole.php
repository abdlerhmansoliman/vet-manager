<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffRole: string
{
    case Owner = 'owner';
    case Doctor = 'doctor';
    case HeadVet = 'head_vet';
    case FieldDoctor = 'field_doctor';
    case Assistant = 'assistant';
    case VetTech = 'vet_tech';
    case Counter = 'counter';
    case Receptionist = 'receptionist';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Clinic Owner / Director',
            self::Doctor => 'Doctor / Veterinarian',
            self::HeadVet => 'Head Veterinarian',
            self::FieldDoctor => 'Field / Ambulatory Doctor',
            self::Assistant => 'Veterinary Assistant',
            self::VetTech => 'Veterinary Technician',
            self::Counter => 'Counter / Receptionist / Cashier',
            self::Receptionist => 'Receptionist',
            self::Employee => 'Clinic Employee',
        };
    }

    public function isDoctor(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet, self::FieldDoctor, self::Doctor], true);
    }

    public function isAssistant(): bool
    {
        return in_array($this, [self::Assistant, self::VetTech], true);
    }

    public function isCounter(): bool
    {
        return in_array($this, [self::Counter, self::Receptionist], true);
    }

    public function canManageStaff(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet], true);
    }

    public function canWitnessControlledSubstances(): bool
    {
        return in_array($this, [
            self::Owner,
            self::HeadVet,
            self::FieldDoctor,
            self::Doctor,
            self::VetTech,
            self::Assistant,
        ], true);
    }

    public function canAccessOwnerDashboard(): bool
    {
        return $this === self::Owner;
    }

    public function canPrescribeMedication(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet, self::FieldDoctor, self::Doctor], true);
    }

    public function canPerformSurgery(): bool
    {
        return in_array($this, [self::Owner, self::HeadVet, self::Doctor], true);
    }

    public function canManageBilling(): bool
    {
        return in_array($this, [self::Owner, self::Counter, self::Receptionist], true);
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
            'can_manage_billing' => $this->canManageBilling(),
            'is_doctor' => $this->isDoctor(),
            'is_assistant' => $this->isAssistant(),
            'is_counter' => $this->isCounter(),
        ];
    }
}
