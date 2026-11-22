<?php

return [
    // Free trial given to every NEW clinic at sign-up. Clinics that already exist are never given one.
    'trial_days' => (int) env('PLATFORM_TRIAL_DAYS', 14),
    // Sales WhatsApp number shown on the expired-trial notice, digits with country code. Empty = hidden.
    // No default: the number shown to customers is the owner's commercial choice.
    'sales_whatsapp' => preg_replace('/\D+/', '', (string) env('PLATFORM_SALES_WHATSAPP', '')) ?: null,
];
