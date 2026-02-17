<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Company Information
        SystemSetting::set('company_name', 'EmCa Technologies', 'text', 'company', 'Company name');
        SystemSetting::set('company_email', 'emca@emca.tech', 'text', 'company', 'Company email address');
        SystemSetting::set('company_phone', '+255 749 719 998', 'text', 'company', 'Company phone number');
        SystemSetting::set('company_address', 'Ben Bella Street, Moshi', 'text', 'company', 'Company physical address');
        SystemSetting::set('company_website', 'www.emca.tech', 'text', 'company', 'Company website URL');
        SystemSetting::set('company_tin', '181-103-264', 'text', 'company', 'Company TIN number');

        // Payment Settings
        SystemSetting::set('bank_name', 'CRDB Bank', 'text', 'payment', 'Bank name for manual payments');
        SystemSetting::set('bank_account_number', '329876567', 'text', 'payment', 'Bank account number for manual payments');
        SystemSetting::set('payment_instructions', 'Please make payment to the above account number and upload proof of payment for verification.', 'text', 'payment', 'Payment instructions for customers');

        // SMS Settings
        SystemSetting::set('sms_username', 'emcatechn', 'text', 'sms', 'SMS gateway username');
        SystemSetting::set('sms_password', 'Emca@#12', 'text', 'sms', 'SMS gateway password');
        SystemSetting::set('sms_sender_id', 'MauzoLink', 'text', 'sms', 'SMS sender ID');

        // General Settings
        SystemSetting::set('currency', 'TSh', 'text', 'general', 'System currency');
        SystemSetting::set('timezone', 'Africa/Dar_es_Salaam', 'text', 'general', 'System timezone');
        SystemSetting::set('date_format', 'Y-m-d', 'text', 'general', 'Date format');
        SystemSetting::set('registration_enabled', true, 'boolean', 'general', 'Enable/disable user registration');
        SystemSetting::set('maintenance_mode', false, 'boolean', 'general', 'Enable/disable maintenance mode');

        $this->command->info('System settings seeded successfully!');
    }
}
