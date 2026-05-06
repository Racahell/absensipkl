<?php

namespace App\Providers;

use App\Support\SettingStore;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.buttons');
        Paginator::defaultSimpleView('vendor.pagination.simple-buttons');

        View::composer('*', function ($view): void {
            $footerLinks = array_values(array_filter([
                [
                    'label' => SettingStore::get('footer_link_1_label', 'Privacy'),
                    'url' => SettingStore::get('footer_link_1_url', '#'),
                ],
                [
                    'label' => SettingStore::get('footer_link_2_label', 'Terms'),
                    'url' => SettingStore::get('footer_link_2_url', '#'),
                ],
                [
                    'label' => SettingStore::get('footer_link_3_label', 'Support'),
                    'url' => SettingStore::get('footer_link_3_url', '#'),
                ],
            ], fn (array $item) => filled($item['label']) && filled($item['url'])));

            $view->with('appProfile', [
                'name' => SettingStore::get('app_name', config('app.name', 'Absensi PKL')),
                'tagline' => SettingStore::get('app_tagline', 'Absensi & Monitoring PKL'),
                'logo' => SettingStore::get('app_logo', 'image/download.png'),
                'favicon' => SettingStore::get('app_favicon', SettingStore::get('app_logo', 'image/download.png')),
                'address' => SettingStore::get('school_address', '-'),
                'manager' => SettingStore::get('school_manager', '-'),
                'contact' => SettingStore::get('school_contact', '-'),
                'timezone' => SettingStore::get('attendance_timezone', 'Asia/Jakarta'),
                'footer_text' => SettingStore::get('footer_text', 'Absensi PKL'),
                'footer_links' => $footerLinks,
                'theme_primary' => SettingStore::get('theme_primary', '#f97316'),
                'theme_sidebar' => SettingStore::get('theme_sidebar', '#ffffff'),
                'theme_button' => SettingStore::get('theme_button', '#f97316'),
                'theme_background' => SettingStore::get('theme_background', '#ffffff'),
                'theme_card' => SettingStore::get('theme_card', '#ffffff'),
            ]);
        });
    }
}
