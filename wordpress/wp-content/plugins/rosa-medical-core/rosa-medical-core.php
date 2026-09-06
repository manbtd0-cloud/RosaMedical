<?php
/**
 * Plugin Name: Rosa Medical Core
 * Description: Rosa Medical catalogue and business-logic foundation.
 * Version: 0.1.0
 * Text Domain: rosa-medical
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('ROSA_MEDICAL_CORE_FILE', __FILE__);
define('ROSA_MEDICAL_VERSION', '0.1.0');

require_once __DIR__ . '/src/Settings/BusinessSettings.php';
require_once __DIR__ . '/src/Settings/ContentSchema.php';
require_once __DIR__ . '/src/Settings/ContentSettings.php';
require_once __DIR__ . '/src/Settings/MediaSettings.php';
require_once __DIR__ . '/src/Admin/Capabilities.php';
require_once __DIR__ . '/src/Admin/ContentPage.php';
require_once __DIR__ . '/src/Admin/MediaField.php';
require_once __DIR__ . '/src/Admin/ElementorShortcutPage.php';
require_once __DIR__ . '/src/Admin/RosaAdmin.php';
require_once __DIR__ . '/src/Elementor/WidgetRegistry.php';
require_once __DIR__ . '/src/Elementor/ElementorIntegration.php';
require_once __DIR__ . '/src/Elementor/ElementorSeedData.php';
require_once __DIR__ . '/src/Elementor/ElementorPageSeeder.php';
require_once __DIR__ . '/src/Plugin.php';

use RosaMedical\Core\Plugin;
use RosaMedical\Core\Settings\BusinessSettings;
use RosaMedical\Core\Settings\ContentSettings;

function rosa_business_value(string $key, string $default = ''): string
{
    return BusinessSettings::get($key, $default);
}

function rosa_content_value(string $section, string $key, string $locale = 'en', string $fallback = ''): string
{
    return ContentSettings::get($section, $key, $locale, $fallback);
}

Plugin::register();
