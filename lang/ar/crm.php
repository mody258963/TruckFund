<?php

return [
    'users' => [
        'title' => 'المستخدمون',
        'create' => 'إنشاء مستخدم',
        'manager' => 'يرفع تقاريره إلى',
        'cannot_edit' => 'لا يمكنك تعديل هذا المستخدم.',
        'invalid_role' => 'لا يمكنك إنشاء هذا الدور.',
        'manager_admin_only' => 'المدير العام فقط يمكنه إنشاء مديرين.',
        'reports_to_required' => 'اختر المدير المباشر.',
        'invalid_manager' => 'اختيار مدير غير صالح.',
    ],
    'freelancers' => [
        'title' => 'المراجع (المستقلون)',
        'create' => 'إضافة مرجع',
        'national_id' => 'الرقم القومي',
        'locked' => 'المدير فقط يمكنه تعديل المراجع المقفلة.',
        'locked_hint' => 'مقفل بعد الإنشاء',
    ],
    'transfer' => [
        'title' => 'طلب نقل',
        'request' => 'طلب نقل',
        'to_user' => 'نقل إلى',
        'reason' => 'السبب',
        'pending' => 'بانتظار الموافقة',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'sales_only' => 'المبيعات فقط يمكنها طلب النقل.',
        'not_assigned' => 'هذا العميل غير مُسند إليك.',
        'invalid_target' => 'هدف النقل غير صالح.',
        'pending_exists' => 'يوجد طلب نقل معلق بالفعل.',
        'not_pending' => 'هذا الطلب لم يعد معلقاً.',
        'cannot_review' => 'لا يمكنك مراجعة هذا الطلب.',
        'approved_log' => 'تمت الموافقة على النقل — إسناد إلى :to',
    ],
    'leads' => [
        'source' => 'المصدر',
        'created_by' => 'أنشأه',
        'reference' => 'المرجع',
        'cannot_assign' => 'لا يمكنك الإسناد لهذا المستخدم.',
    ],
];
