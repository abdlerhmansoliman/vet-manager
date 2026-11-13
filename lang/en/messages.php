<?php

declare(strict_types=1);

return [
    'auth' => [
        'login_successful' => 'Authentication successful.',
        'logged_out' => 'Successfully logged out.',
        'invalid_credentials' => 'Invalid email or password.',
        'pin_verified' => 'Staff witness PIN verified.',
        'pin_invalid' => 'Invalid witness PIN. Please try again.',
        'pin_locked' => 'Security lockout: Too many failed witness PIN attempts. Please wait :minutes minutes.',
        'rate_limited' => 'Too many requests. Please slow down and try again later.',
        'unauthenticated' => 'Your session has expired. Please log in again to continue.',
        'unauthorized' => 'You do not have permission to perform this clinical action.',
    ],

    'cases' => [
        'created' => 'Clinical case opened successfully.',
        'updated' => 'Clinical case updated successfully.',
        'closed' => 'Clinical case discharged and closed.',
        'priority' => [
            'routine' => 'Routine',
            'urgent' => 'Urgent',
            'critical' => 'Critical / Emergency',
        ],
        'status' => [
            'open' => 'Open',
            'active' => 'Active',
            'closed' => 'Closed',
        ],
    ],

    'visits' => [
        'created' => 'Clinical visit started successfully.',
        'updated' => 'Clinical visit updated successfully.',
        'signed' => 'Clinical encounter formally signed and locked.',
        'locked_error' => 'This clinical encounter is signed and legally locked. Modifications are strictly forbidden.',
        'types' => [
            'consultation' => 'Consultation',
            'follow_up' => 'Follow-up',
            'emergency' => 'Emergency',
            'routine' => 'Routine Check',
            'surgery_recheck' => 'Surgery Recheck',
        ],
    ],

    'sync' => [
        'accepted' => 'Sync mutations accepted for processing.',
        'applied' => 'Mutation applied successfully.',
        'rejected' => 'Mutation rejected by server rules.',
        'ignored' => 'Mutation ignored under Last-Write-Wins rule.',
        'idempotent_replay' => 'Idempotent replay: mutation already applied.',
    ],

    'inventory' => [
        'deducted' => 'Medication dose deducted from batch inventory.',
        'received' => 'Stock inventory received successfully.',
        'insufficient_stock' => 'Insufficient inventory in available batches.',
        'controlled_witness_required' => 'Controlled substance dispensing requires a verified supervisor witness PIN.',
    ],

    'media' => [
        'upload_confirmed' => 'Diagnostic media upload confirmed and indexed.',
        'presigned_url_created' => 'Presigned S3 upload URL generated.',
        'file_missing_in_s3' => 'Uploaded media file was not found in S3 storage.',
    ],
];
