<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hospital Identity & Localization Settings (Kenyan Default)
    |--------------------------------------------------------------------------
    | Centralized configuration values for MediCare Hospital Management System.
    | Deploying hospital administrators can customize these via .env variables
    | or administrative settings.
    */
    'name' => env('HOSPITAL_NAME', 'MediCare Hospital'),
    'tagline' => env('HOSPITAL_TAGLINE', 'Healthcare Management System'),
    'city' => env('HOSPITAL_CITY', 'Nairobi'),
    'country' => env('HOSPITAL_COUNTRY', 'Kenya'),
    'address' => env('HOSPITAL_ADDRESS', 'Upper Hill Medical District, Nairobi, Kenya'),
    'phone' => env('HOSPITAL_PHONE', '+254 20 790 0000'),
    'emergency_phone' => env('HOSPITAL_EMERGENCY_PHONE', '+254 700 911 911'),
    'mobile_phone' => env('HOSPITAL_MOBILE_PHONE', '+254 711 000 000'),
    'email' => env('HOSPITAL_EMAIL', 'info@medicare.co.ke'),
    'emergency_email' => env('HOSPITAL_EMERGENCY_EMAIL', 'emergency@medicare.co.ke'),
    'hours' => env('HOSPITAL_HOURS', '24/7 Emergency & Inpatient Care'),
    'opd_hours' => env('HOSPITAL_OPD_HOURS', 'Mon - Sat: 8:00 AM - 6:00 PM'),
    'default_country_code' => env('HOSPITAL_DEFAULT_COUNTRY_CODE', '+254'),
    'currency' => env('HOSPITAL_CURRENCY', 'KES'),
    'currency_symbol' => env('HOSPITAL_CURRENCY_SYMBOL', 'KSh'),

    /*
    |--------------------------------------------------------------------------
    | Demonstration Mode
    |--------------------------------------------------------------------------
    | When true, the public website displays clear, professional notices
    | clarifying that provider profiles, schedules, and clinical offerings
    | are demonstration templates for software evaluation until verified by
    | the deploying hospital administration.
    */
    'is_demo' => env('HOSPITAL_DEMO_MODE', true),
    'accreditation_notice' => env('HOSPITAL_ACCREDITATION_NOTICE', 'Sample Installation — Demonstrating Hospital Management System Capabilities'),
];
