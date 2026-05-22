<?php

return [
    'user_role' => [
        'Admin' => 'Administrator',
        'SalesAgent' => 'Sales Agent',
        'FinanceOfficer' => 'Finance Officer',
        'MerchantAgent' => 'Merchant Agent',
    ],
    'lead_status' => [
        'New' => 'New',
        'Pending' => 'Pending',
        'NotReachable' => 'Not Reachable',
        'Duplicate' => 'Duplicate',
        'ResolvedProfileCreated' => 'Profile Created',
        'ResolvedNotInterested' => 'Not Interested',
        'ResolvedNotQualified' => 'Not Qualified',
        'ResolvedProductSold' => 'Product Sold',
        'ResolvedProductListed' => 'Product Listed',
        'CashNoLoan' => 'Cash — No Loan',
    ],
    'lead_value' => ['Low' => 'Low', 'Mid' => 'Mid', 'High' => 'High'],
    'application_status' => [
        'Draft' => 'Draft',
        'Submitted' => 'Submitted',
        'UnderReview' => 'Under Review',
        'Accepted' => 'Accepted',
        'Rejected' => 'Rejected',
        'Cancelled' => 'Cancelled',
        'BookingConfirmed' => 'Booking Confirmed',
        'DocsUploaded' => 'Documents Uploaded',
        'Completed' => 'Completed',
    ],
    'doc_type' => [
        'NationalId' => 'National ID',
        'IncomeProof' => 'Income Proof',
        'CommercialReg' => 'Commercial Registration',
        'LandContract' => 'Land Contract',
        'AcceptancePaper' => 'Acceptance Paper',
        'Other' => 'Other',
    ],
    'comm_type' => ['Note' => 'Note', 'Call' => 'Call', 'Email' => 'Email', 'Sms' => 'SMS'],
    'gender' => ['Male' => 'Male', 'Female' => 'Female'],
];
