<?php
/**
 * Rosa Medical child-theme setup.
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/inc/client-preview.php';
require_once __DIR__ . '/inc/client-preview-navigation.php';

function rosa_is_latest_home_page(?int $postId = null): bool
{
    $postId = $postId ?? (int) get_queried_object_id();
    if ($postId <= 0 || get_post_type($postId) !== 'page') {
        return false;
    }

    if ((string) get_page_template_slug($postId) !== 'page-templates/rosa-elementor-authoring.php') {
        return false;
    }

    if ((string) get_post_meta($postId, '_rosa_elementor_home_parity_version', true) !== '1') {
        return false;
    }

    $frontId = (int) get_option('page_on_front', 0);
    $pageUri = function_exists('get_page_uri') ? trim((string) get_page_uri($postId), '/') : '';

    return $postId === $frontId || ($pageUri === 'ar' && rosa_preview_locale($postId) === 'ar');
}

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('custom-logo');

    register_nav_menus([
        'primary' => __('Primary Navigation', 'rosa-medical'),
    ]);
});

add_action('wp_enqueue_scripts', static function (): void {
    $theme = wp_get_theme();
    $version = (string) $theme->get('Version');
    $pageTemplate = is_page() ? (string) get_page_template_slug() : '';

    wp_enqueue_style(
        'rosa-medical-tokens',
        get_stylesheet_directory_uri() . '/assets/css/tokens.css',
        [],
        $version
    );

    wp_enqueue_style(
        'rosa-medical-base',
        get_stylesheet_directory_uri() . '/assets/css/base.css',
        ['rosa-medical-tokens'],
        $version
    );

    $previewTemplates = [
        'page-templates/client-preview-home.php',
        'page-templates/client-preview-about.php',
        'page-templates/client-preview-contact.php',
        'page-templates/client-preview-shop.php',
        'page-templates/rosa-elementor-authoring.php',
    ];
    $isPreviewPage = is_page() && (in_array($pageTemplate, $previewTemplates, true) || rosa_preview_locale() === 'ar');
    $isPreviewCatalogue = function_exists('is_shop') && (
        is_shop()
        || is_product_category()
        || is_product_tag()
        || (function_exists('is_product') && is_product())
    );
    if ($isPreviewPage || $isPreviewCatalogue) {
        wp_enqueue_style('rosa-client-preview', get_stylesheet_directory_uri() . '/assets/css/client-preview.css', ['rosa-medical-base'], $version);
        wp_enqueue_style('rosa-live-visual-recovery', get_stylesheet_directory_uri() . '/assets/css/live-visual-recovery.css', ['rosa-client-preview'], $version);

        $isShopSurface = $pageTemplate === 'page-templates/client-preview-shop.php'
            || (function_exists('is_shop') && (is_shop() || is_product_category() || is_product_tag()));
        if ($isShopSurface) {
            wp_enqueue_style(
                'rosa-shop-live-visual-recovery',
                get_stylesheet_directory_uri() . '/assets/css/shop-live-visual-recovery.css',
                ['rosa-live-visual-recovery'],
                $version
            );
        }

        $media = get_option(ROSA_PREVIEW_MEDIA_OPTION, []);
        $editableMediaKeys = [
            'home-hero-01', 'home-who-01', 'home-feature-01',
            'home-promo-01', 'home-promo-02', 'home-promo-03', 'home-promo-04',
            'home-why-01', 'home-evidence-01', 'prefooter-person-01',
        ];
        $hasEditableMedia = is_array($media) && array_reduce(
            $editableMediaKeys,
            static fn(bool $found, string $key): bool => $found || (isset($media[$key]) && (int) $media[$key] > 0),
            false
        );
        if ($hasEditableMedia) {
            wp_enqueue_style('rosa-client-preview-media', get_stylesheet_directory_uri() . '/assets/css/client-preview-media.css', ['rosa-client-preview'], $version);
        }

        if ($pageTemplate === 'page-templates/rosa-elementor-authoring.php') {
            wp_enqueue_style(
                'rosa-elementor-authoring',
                get_stylesheet_directory_uri() . '/assets/css/elementor-authoring.css',
                ['rosa-client-preview'],
                $version
            );
        }

        if (rosa_preview_locale() === 'ar' && file_exists(get_stylesheet_directory() . '/assets/css/client-preview-rtl.css')) {
            wp_enqueue_style('rosa-client-preview-rtl', get_stylesheet_directory_uri() . '/assets/css/client-preview-rtl.css', ['rosa-client-preview'], $version);
        }
        wp_enqueue_script('rosa-client-preview', get_stylesheet_directory_uri() . '/assets/js/client-preview.js', [], $version, true);
    }
});

function rosa_theme_business_value(string $key, string $default = ''): string
{
    if (function_exists('rosa_business_value')) {
        return rosa_business_value($key, $default);
    }

    return $default;
}
