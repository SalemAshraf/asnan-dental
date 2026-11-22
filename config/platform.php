<?php

return [
    // Free trial length for NEW clinics at sign-up. Unset = no trial is granted; new accounts await manual activation.
    'trial_days' => ($v = env('PLATFORM_TRIAL_DAYS')) !== null && $v !== '' ? (int) $v : null, // null = trial not configured; owner decides
    // Sales WhatsApp number shown on the expired-trial notice, digits with country code. Empty = hidden.
    // No default: the number shown to customers is the owner's commercial choice.
    'sales_whatsapp' => preg_replace('/\D+/', '', (string) env('PLATFORM_SALES_WHATSAPP', '')) ?: null,
];
