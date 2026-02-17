<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Holiday Wishes
            [
                'name' => 'New Year Greeting',
                'content' => 'Happy New Year {customer_name}! Thank you for being our valued customer. Wishing you prosperity and joy in {year}. - {business_name}',
                'category' => 'holiday',
                'language' => 'en',
                'description' => 'New Year greeting message',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'year', 'business_name'],
            ],
            [
                'name' => 'Christmas Greeting',
                'content' => 'Merry Christmas {customer_name}! May this festive season bring you happiness and joy. Thank you for your continued support. - {business_name}',
                'category' => 'holiday',
                'language' => 'en',
                'description' => 'Christmas greeting message',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Eid Mubarak',
                'content' => 'Eid Mubarak {customer_name}! Wishing you and your family a blessed Eid filled with peace and happiness. - {business_name}',
                'category' => 'holiday',
                'language' => 'en',
                'description' => 'Eid greeting message',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Independence Day',
                'content' => 'Happy Independence Day {customer_name}! Celebrating freedom and unity. Thank you for being part of our community. - {business_name}',
                'category' => 'holiday',
                'language' => 'en',
                'description' => 'Independence Day greeting',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],

            // Promotions
            [
                'name' => 'Discount Offer',
                'content' => 'Special offer for {customer_name}! Get {discount}% off on all items. Valid until {expiry_date}. Visit us today! - {business_name}',
                'category' => 'promotion',
                'language' => 'en',
                'description' => 'Discount promotion message',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'discount', 'expiry_date', 'business_name'],
            ],
            [
                'name' => 'Happy Hour',
                'content' => 'Happy Hour Alert {customer_name}! Enjoy special prices from {start_time} to {end_time}. Don\'t miss out! - {business_name}',
                'category' => 'promotion',
                'language' => 'en',
                'description' => 'Happy hour promotion',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'start_time', 'end_time', 'business_name'],
            ],
            [
                'name' => 'New Product Launch',
                'content' => 'Exciting news {customer_name}! We\'ve launched new products. Come and try them today! - {business_name}',
                'category' => 'promotion',
                'language' => 'en',
                'description' => 'New product announcement',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],

            // Updates
            [
                'name' => 'Business Hours Change',
                'content' => 'Important Update {customer_name}: Our business hours have changed. New hours: {hours}. Thank you for your understanding. - {business_name}',
                'category' => 'update',
                'language' => 'en',
                'description' => 'Business hours update',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'hours', 'business_name'],
            ],
            [
                'name' => 'New Location',
                'content' => 'Great news {customer_name}! We\'ve opened a new location at {address}. Visit us soon! - {business_name}',
                'category' => 'update',
                'language' => 'en',
                'description' => 'New location announcement',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'address', 'business_name'],
            ],
            [
                'name' => 'Menu Update',
                'content' => 'Menu Update {customer_name}: We\'ve added exciting new items to our menu. Come and try them! - {business_name}',
                'category' => 'update',
                'language' => 'en',
                'description' => 'Menu update announcement',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],

            // Engagement
            [
                'name' => 'Thank You Message',
                'content' => 'Thank you {customer_name} for your {total_orders} orders with us! We truly appreciate your loyalty. - {business_name}',
                'category' => 'engagement',
                'language' => 'en',
                'description' => 'Thank you message for loyal customers',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'total_orders', 'business_name'],
            ],
            [
                'name' => 'Birthday Wish',
                'content' => 'Happy Birthday {customer_name}! Wishing you a wonderful day filled with joy. Come celebrate with us and enjoy a special treat! - {business_name}',
                'category' => 'engagement',
                'language' => 'en',
                'description' => 'Birthday greeting',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Feedback Request',
                'content' => 'Hi {customer_name}, we value your opinion! Please share your feedback about your recent visit. Your input helps us serve you better. - {business_name}',
                'category' => 'engagement',
                'language' => 'en',
                'description' => 'Feedback request message',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],

            // Swahili Templates
            [
                'name' => 'Karibu Mwaka Mpya',
                'content' => 'Heri ya Mwaka Mpya {customer_name}! Asante kwa kuwa mteja wetu. Tunakutakia mafanikio na furaha katika mwaka mpya. - {business_name}',
                'category' => 'holiday',
                'language' => 'sw',
                'description' => 'Ujumbe wa mwaka mpya',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Pongezi za Krismasi',
                'content' => 'Krismasi Njema {customer_name}! Tunakutakia furaha na amani katika siku hizi za sikukuu. Asante kwa msaada wako. - {business_name}',
                'category' => 'holiday',
                'language' => 'sw',
                'description' => 'Ujumbe wa Krismasi',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Eid Mubarak (Swahili)',
                'content' => 'Eid Mubarak {customer_name}! Tunakutakia na familia yako Eid yenye amani na furaha. - {business_name}',
                'category' => 'holiday',
                'language' => 'sw',
                'description' => 'Ujumbe wa Eid',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Pongezi za Siku ya Uhuru',
                'content' => 'Heri ya Siku ya Uhuru {customer_name}! Tunaadhimisha uhuru na umoja. Asante kwa kuwa sehemu ya jamii yetu. - {business_name}',
                'category' => 'holiday',
                'language' => 'sw',
                'description' => 'Ujumbe wa siku ya uhuru',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'business_name'],
            ],
            [
                'name' => 'Ofa ya Punguzo',
                'content' => 'Ofa maalum kwa {customer_name}! Pata punguzo la {discount}% kwenye bidhaa zote. Inaisha {expiry_date}. Tembelea leo! - {business_name}',
                'category' => 'promotion',
                'language' => 'sw',
                'description' => 'Ujumbe wa ofa ya punguzo',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'discount', 'expiry_date', 'business_name'],
            ],
            [
                'name' => 'Asante',
                'content' => 'Asante {customer_name} kwa oda zako {total_orders} nasi! Tunashukuru sana kwa uaminifu wako. - {business_name}',
                'category' => 'engagement',
                'language' => 'sw',
                'description' => 'Ujumbe wa shukrani',
                'is_system_template' => true,
                'placeholders' => ['customer_name', 'total_orders', 'business_name'],
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                [
                    'name' => $template['name'],
                    'is_system_template' => true,
                ],
                $template
            );
        }
    }
}
