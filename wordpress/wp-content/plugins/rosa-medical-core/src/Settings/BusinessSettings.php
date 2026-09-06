<?php

declare(strict_types=1);

namespace RosaMedical\Core\Settings;

use RosaMedical\Core\Admin\Capabilities;

final class BusinessSettings
{
    public const OPTION_NAME = 'rosa_business_settings';

    /** @var list<string> */
    private const KEYS = [
        'phone',
        'email',
        'address',
        'address_ar',
        'whatsapp',
        'primary_cta_label',
    ];

    public static function get(string $key, string $default = ''): string
    {
        if (! in_array($key, self::KEYS, true)) {
            return $default;
        }

        $settings = get_option(self::OPTION_NAME, []);
        if (! is_array($settings) || ! array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key];
        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param mixed $input
     * @return array<string, string>
     */
    public static function sanitize(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $sanitized = [];
        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $input) || ! is_scalar($input[$key])) {
                continue;
            }

            $value = (string) $input[$key];
            $sanitized[$key] = $key === 'email'
                ? sanitize_email($value)
                : sanitize_text_field($value);
        }

        return $sanitized;
    }

    public static function register(): void
    {
        register_setting(
            'rosa_business',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => [],
            ]
        );

        add_settings_section(
            'rosa_business_main',
            __('Business information', 'rosa-medical'),
            '__return_false',
            'rosa-business-settings'
        );

        $labels = [
            'phone' => __('Phone', 'rosa-medical'),
            'email' => __('Email', 'rosa-medical'),
            'address' => __('Address', 'rosa-medical'),
            'address_ar' => __('Arabic address', 'rosa-medical'),
            'whatsapp' => __('WhatsApp', 'rosa-medical'),
            'primary_cta_label' => __('Primary CTA label', 'rosa-medical'),
        ];

        foreach ($labels as $key => $label) {
            add_settings_field(
                'rosa_business_' . $key,
                $label,
                [self::class, 'renderField'],
                'rosa-business-settings',
                'rosa_business_main',
                ['key' => $key]
            );
        }
    }

    public static function registerPage(): void
    {
        add_options_page(
            __('Rosa Business Settings', 'rosa-medical'),
            __('Rosa Business', 'rosa-medical'),
            Capabilities::MANAGE_CONTENT,
            'rosa-business-settings',
            [self::class, 'renderPage']
        );
    }

    /** @param array{key?: string} $args */
    public static function renderField(array $args): void
    {
        $key = isset($args['key']) ? (string) $args['key'] : '';
        if (! in_array($key, self::KEYS, true)) {
            return;
        }

        printf(
            '<input class="regular-text" type="text" name="%1$s[%2$s]" value="%3$s">',
            esc_attr(self::OPTION_NAME),
            esc_attr($key),
            esc_attr(self::get($key))
        );
    }

    public static function renderPage(): void
    {
        if (! current_user_can(Capabilities::MANAGE_CONTENT)) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Rosa Business Settings', 'rosa-medical'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('rosa_business');
                do_settings_sections('rosa-business-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
