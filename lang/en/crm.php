<?php

return [
    'users' => [
        'title' => 'Users',
        'create' => 'Create user',
        'manager' => 'Reports to',
        'cannot_edit' => 'You cannot edit this user.',
        'invalid_role' => 'You cannot create this role.',
        'manager_admin_only' => 'Only Admin can create Managers.',
        'reports_to_required' => 'Select a manager for this user.',
        'invalid_manager' => 'Invalid manager selection.',
    ],
    'freelancers' => [
        'title' => 'References (Freelancers)',
        'create' => 'Add reference',
        'national_id' => 'National ID',
        'locked' => 'Only administrators can edit locked references.',
        'locked_hint' => 'Locked after creation',
    ],
    'transfer' => [
        'title' => 'Transfer request',
        'request' => 'Request transfer',
        'to_user' => 'Transfer to',
        'reason' => 'Reason',
        'pending' => 'Pending approval',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'sales_only' => 'Only sales can request transfers.',
        'not_assigned' => 'This lead is not assigned to you.',
        'invalid_target' => 'Invalid transfer target.',
        'pending_exists' => 'A pending transfer already exists.',
        'not_pending' => 'This request is no longer pending.',
        'cannot_review' => 'You cannot review this transfer.',
        'approved_log' => 'Transfer approved — assigned to :to',
    ],
    'leads' => [
        'source' => 'Source',
        'created_by' => 'Created by',
        'reference' => 'Reference',
        'cannot_assign' => 'You cannot assign to this user.',
    ],
];
