<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Return Policy Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your store's return policies and rules
    |
    */

    // Return deadline in days from sale date
    'return_deadline_days' => env('RETURN_DEADLINE_DAYS', 30),

    // Require manager approval for returns
    'require_approval' => env('RETURN_REQUIRE_APPROVAL', true),

    // Require quality inspection
    'require_inspection' => env('RETURN_REQUIRE_INSPECTION', true),

    // Auto-approve returns under this amount
    'auto_approve_threshold' => env('RETURN_AUTO_APPROVE_THRESHOLD', 0),

    // Restocking fee percentage (0-100)
    'restocking_fee_percent' => env('RETURN_RESTOCKING_FEE', 0),

    // Allow partial returns
    'allow_partial_returns' => env('RETURN_ALLOW_PARTIAL', true),

    // Notification settings
    'notifications' => [
        'email_on_create' => true,
        'email_on_approve' => true,
        'email_on_reject' => true,
    ],

    // Status workflow
    'workflow' => [
        'pending' => [
            'label' => 'Pending Approval',
            'color' => 'warning',
            'icon' => 'clock',
            'next_states' => ['approved', 'rejected'],
        ],
        'approved' => [
            'label' => 'Approved',
            'color' => 'info',
            'icon' => 'check-circle',
            'next_states' => ['completed'],
        ],
        'rejected' => [
            'label' => 'Rejected',
            'color' => 'danger',
            'icon' => 'times-circle',
            'next_states' => [],
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => 'success',
            'icon' => 'check-double',
            'next_states' => [],
        ],
    ],

    // Quality status options
    'quality_statuses' => [
        'pending_inspection' => [
            'label' => 'Pending Inspection',
            'color' => 'secondary',
        ],
        'good' => [
            'label' => 'Good Condition',
            'color' => 'success',
        ],
        'damaged' => [
            'label' => 'Damaged',
            'color' => 'warning',
        ],
        'defective' => [
            'label' => 'Defective',
            'color' => 'danger',
        ],
    ],
];
