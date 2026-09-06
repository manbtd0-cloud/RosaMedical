<?php

declare(strict_types=1);

namespace RosaMedical\Core\Elementor;

final class ElementorRenderCache
{
    public const OPTION = 'rosa_elementor_render_cache_version';
    public const VERSION = '2026-09-06-live-visual-recovery-1';

    public static function ensureFresh(): void
    {
        if (! function_exists('get_option') || ! function_exists('update_option')) {
            return;
        }

        if ((string) get_option(self::OPTION, '') === self::VERSION) {
            return;
        }

        if (! function_exists('get_posts')) {
            return;
        }

        $postIds = get_posts([
            'post_type' => 'page',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_key' => '_wp_page_template',
            'meta_value' => ElementorPageSeeder::TEMPLATE,
        ]);

        foreach ((array) $postIds as $postId) {
            $postId = (int) $postId;
            if ($postId <= 0) {
                continue;
            }

            if (function_exists('delete_post_meta')) {
                delete_post_meta($postId, '_elementor_element_cache');
                delete_post_meta($postId, '_elementor_css');
            }
            if (function_exists('clean_post_cache')) {
                clean_post_cache($postId);
            }
        }

        update_option(self::OPTION, self::VERSION, false);
    }
}
