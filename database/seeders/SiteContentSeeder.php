<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['section' => 'hero', 'key' => 'title', 'value' => 'The Road to Your Success Starts Here', 'type' => 'text'],
            ['section' => 'hero', 'key' => 'subtitle', 'value' => 'Reliable vehicles for hire purchase and daily rental. Empowering Tanzanian drivers with flexible ownership solutions.', 'type' => 'textarea'],
            ['section' => 'hero', 'key' => 'cta_text', 'value' => 'Browse Vehicles', 'type' => 'text'],
            ['section' => 'hero', 'key' => 'cta_link', 'value' => '/vehicles', 'type' => 'text'],

            ['section' => 'stats', 'key' => 'vehicles_label', 'value' => 'Vehicles in Fleet', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'drivers_label', 'value' => 'Active Drivers', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'contracts_label', 'value' => 'Contracts Signed', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'years_label', 'value' => 'Years Experience', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'vehicles_count', 'value' => '50+', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'drivers_count', 'value' => '120+', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'contracts_count', 'value' => '300+', 'type' => 'text'],
            ['section' => 'stats', 'key' => 'years_count', 'value' => '8+', 'type' => 'text'],

            ['section' => 'services', 'key' => 'title', 'value' => 'Our Services', 'type' => 'text'],
            ['section' => 'services', 'key' => 'subtitle', 'value' => 'Flexible solutions tailored for Tanzanian roads and businesses', 'type' => 'textarea'],

            ['section' => 'about', 'key' => 'title', 'value' => 'About QuickWheels', 'type' => 'text'],
            ['section' => 'about', 'key' => 'mission', 'value' => 'To provide accessible and reliable vehicle financing and rental solutions that empower Tanzanian drivers and businesses to thrive.', 'type' => 'textarea'],
            ['section' => 'about', 'key' => 'vision', 'value' => 'To be Tanzania\'s leading vehicle solutions provider, setting the standard for hire purchase and rental services across East Africa.', 'type' => 'textarea'],
            ['section' => 'about', 'key' => 'story_title', 'value' => 'Our Story', 'type' => 'text'],
            ['section' => 'about', 'key' => 'story_paragraph_1', 'value' => 'Founded in Dar es Salaam, QuickWheels started with a simple mission: make vehicle ownership accessible to every hardworking Tanzanian. We saw too many talented drivers held back by the high cost of buying a vehicle outright.', 'type' => 'textarea'],
            ['section' => 'about', 'key' => 'story_paragraph_2', 'value' => 'Today, we manage a growing fleet of vehicles and have helped hundreds of drivers get behind the wheel through our flexible hire purchase and rental programs.', 'type' => 'textarea'],
            ['section' => 'about', 'key' => 'values_title', 'value' => 'Our Values', 'type' => 'text'],

            ['section' => 'contact', 'key' => 'title', 'value' => 'Get In Touch', 'type' => 'text'],
            ['section' => 'contact', 'key' => 'subtitle', 'value' => 'Have a question or ready to get started? Reach out to us.', 'type' => 'textarea'],
            ['section' => 'contact', 'key' => 'address', 'value' => '123 Samora Avenue, Dar es Salaam, Tanzania', 'type' => 'text'],
            ['section' => 'contact', 'key' => 'phone', 'value' => '+255 712 345 678', 'type' => 'text'],
            ['section' => 'contact', 'key' => 'email', 'value' => 'info@quickwheels.co.tz', 'type' => 'text'],
            ['section' => 'contact', 'key' => 'map_embed_url', 'value' => 'https://maps.google.com/maps?q=-6.7924,39.2083&z=12&output=embed', 'type' => 'text'],

            ['section' => 'footer', 'key' => 'description', 'value' => 'QuickWheels provides flexible hire purchase and rental vehicle solutions across Tanzania. Empowering drivers with the freedom of the open road.', 'type' => 'textarea'],
            ['section' => 'footer', 'key' => 'copyright', 'value' => '© 2024 QuickWheels. All rights reserved.', 'type' => 'text'],
            ['section' => 'footer', 'key' => 'facebook_url', 'value' => '#', 'type' => 'text'],
            ['section' => 'footer', 'key' => 'instagram_url', 'value' => '#', 'type' => 'text'],
            ['section' => 'footer', 'key' => 'twitter_url', 'value' => '#', 'type' => 'text'],
            ['section' => 'footer', 'key' => 'youtube_url', 'value' => '#', 'type' => 'text'],

            ['section' => 'seo', 'key' => 'site_name', 'value' => 'QuickWheels', 'type' => 'text'],
            ['section' => 'seo', 'key' => 'meta_description', 'value' => 'QuickWheels - Vehicle hire purchase and rental solutions in Tanzania', 'type' => 'text'],
        ];

        foreach ($defaults as $item) {
            SiteContent::updateOrCreate(
                ['section' => $item['section'], 'key' => $item['key']],
                $item
            );
        }
    }
}
