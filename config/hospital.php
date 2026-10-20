<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hospital Identity & Contact Settings
    |--------------------------------------------------------------------------
    | Reusable configuration values for the hospital institution.
    | Deploying hospitals can customize these via .env variables or direct configuration.
    */
    'name' => env('HOSPITAL_NAME', 'MediCare Hospital'),
    'tagline' => env('HOSPITAL_TAGLINE', 'Healthcare Management System'),
    'address' => env('HOSPITAL_ADDRESS', 'Hospital Campus, Healthcare District'),
    'phone' => env('HOSPITAL_PHONE', '+1 (555) 010-0200'),
    'emergency_phone' => env('HOSPITAL_EMERGENCY_PHONE', '+1 (555) 010-0911'),
    'email' => env('HOSPITAL_EMAIL', 'info@example.com'),
    'hours' => env('HOSPITAL_HOURS', '24/7 Emergency & Inpatient Care'),
    'opd_hours' => env('HOSPITAL_OPD_HOURS', 'Mon - Sat: 8:00 AM - 6:00 PM'),

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
