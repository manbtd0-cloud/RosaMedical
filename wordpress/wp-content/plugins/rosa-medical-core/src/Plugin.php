<?php

declare(strict_types=1);

namespace RosaMedical\Core;

use RosaMedical\Core\Admin\Capabilities;
use RosaMedical\Core\Admin\RosaAdmin;
use RosaMedical\Core\Elementor\ElementorIntegration;
use RosaMedical\Core\Elementor\ElementorRenderCache;
use RosaMedical\Core\Settings\BusinessSettings;
use RosaMedical\Core\Settings\ContentSettings;
use RosaMedical\Core\Settings\MediaSettings;

final class Plugin
{
    public const VERSION = '0.1.0';

    public static function register(): void
    {
        add_action('init', static function (): void {
            load_plugin_textdomain(
                'rosa-medical',
                false,
                dirname(plugin_basename(ROSA_MEDICAL_CORE_FILE)) . '/languages'
            );
        });
        add_action('init', [Capabilities::class, 'ensure'], 20);
        add_action('init', [ElementorRenderCache::class, 'ensureFresh'], 30);

        foreach (['rosa_business', 'rosa_media', 'rosa_content_site', 'rosa_content_shop'] as $group) {
            add_filter('option_page_capability_' . $group, [Capabilities::class, 'settingsCapability']);
        }

        add_action('admin_init', [BusinessSettings::class, 'register']);
        add_action('admin_init', [ContentSettings::class, 'register']);
        add_action('admin_init', [MediaSettings::class, 'register']);
        add_action('admin_menu', [RosaAdmin::class, 'register']);
        add_action('admin_enqueue_scripts', [RosaAdmin::class, 'enqueue']);
        ElementorIntegration::register();

        // Elementor Free registers template_include at priority 11. Run later so
        // Rosa's shared Product Detail prototype remains authoritative only for
        // WooCommerce product requests while leaving all other templates alone.
        add_filter('template_include', [self::class, 'productTemplate'], 100);
    }

    public static function productTemplate(string $template): string
    {
        if (! function_exists('is_product') || ! is_product()) {
            return $template;
        }

        $rosa_template = dirname(ROSA_MEDICAL_CORE_FILE) . '/templates/product-detail-prototype.php';

        return is_readable($rosa_template) ? $rosa_template : $template;
    }
}
