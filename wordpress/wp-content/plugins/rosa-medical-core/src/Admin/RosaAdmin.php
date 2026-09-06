<?php

declare(strict_types=1);

namespace RosaMedical\Core\Admin;

use RosaMedical\Core\Settings\BusinessSettings;

final class RosaAdmin
{
    public const ROOT_SLUG = 'rosa-medical';

    public static function register(): void
    {
        add_menu_page(
            __('Rosa Medical', 'rosa-medical'),
            __('Rosa Medical', 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            self::ROOT_SLUG,
            static fn(): mixed => ElementorShortcutPage::render('home', 'Homepage'),
            'dashicons-heart',
            56
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Rosa Medical — Homepage', 'rosa-medical'),
            __('Homepage', 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            self::ROOT_SLUG,
            static fn(): mixed => ElementorShortcutPage::render('home', 'Homepage')
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Rosa Medical — About', 'rosa-medical'),
            __('About', 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            'rosa-medical-about',
            static fn(): mixed => ElementorShortcutPage::render('about', 'About')
        );

        add_submenu_page(
            self::ROOT_SLUG,
            __('Rosa Medical — Contact', 'rosa-medical'),
            __('Contact', 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            'rosa-medical-contact',
            static fn(): mixed => ElementorShortcutPage::render('contact', 'Contact')
        );

        self::addContentSubmenu('Shop', 'rosa-medical-shop', 'shop');
        self::addContentSubmenu('Site & CTA', 'rosa-medical-site', 'site');

        add_submenu_page(
            self::ROOT_SLUG,
            __('Rosa Business Settings', 'rosa-medical'),
            __('Business', 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            'rosa-business-settings',
            [BusinessSettings::class, 'renderPage']
        );
    }

    public static function enqueue(string $hook): void
    {
        if (! str_contains($hook, 'rosa-medical') && ! str_contains($hook, 'rosa-business-settings')) {
            return;
        }
        wp_enqueue_media();
        $version = defined('ROSA_MEDICAL_VERSION') ? ROSA_MEDICAL_VERSION : '0.1.0';
        wp_enqueue_style(
            'rosa-content-admin',
            plugins_url('assets/admin/rosa-content-admin.css', ROSA_MEDICAL_CORE_FILE),
            [],
            $version
        );
        wp_enqueue_script(
            'rosa-content-admin',
            plugins_url('assets/admin/rosa-content-admin.js', ROSA_MEDICAL_CORE_FILE),
            [],
            $version,
            true
        );
    }

    private static function addContentSubmenu(string $label, string $slug, string $section): void
    {
        add_submenu_page(
            self::ROOT_SLUG,
            sprintf(__('Rosa Medical — %s', 'rosa-medical'), $label),
            __($label, 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            $slug,
            static fn(): mixed => ContentPage::render($section)
        );
    }
}
