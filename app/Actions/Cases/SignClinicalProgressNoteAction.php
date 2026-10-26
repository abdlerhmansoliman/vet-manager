<?php

declare(strict_types=1);

namespace App\Actions\Cases;

use App\Models\ClinicalProgressNote;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SignClinicalProgressNoteAction
{
    /**
     * Digitally sign and lock a clinical progress note for legal/medical audit compliance.
     *
     * @throws ValidationException
     */
    public function execute(ClinicalProgressNote $note, User $actor): ClinicalProgressNote
    {
        if ($note->is_signed) {
            throw ValidationException::withMessages([
                'progress_note' => 'This clinical progress note has already been signed and legally finalized.',
            ]);
        }

        $note->update([
            'is_signed' => true,
            'signed_at' => now(),
        ]);

        return $note->refresh()->load(['veterinarian', 'images']);
    }
}
