<?php

declare(strict_types=1);

namespace RosaMedical\Core\Admin;

final class ElementorShortcutPage
{
    public static function render(string $path, string $label): void
    {
        if (! current_user_can(Capabilities::MANAGE_CONTENT)) {
            return;
        }

        $page = get_page_by_path($path, OBJECT, 'page');
        if (! $page) {
            self::notice(
                sprintf(__('The %s page could not be found.', 'rosa-medical'), $label),
                admin_url('edit.php?post_type=page')
            );
            return;
        }

        $pageId = (int) $page->ID;
        if (! current_user_can('edit_post', $pageId)) {
            self::notice(__('You do not have permission to edit this page.', 'rosa-medical'), admin_url());
            return;
        }

        $document = false;
        if (class_exists('\\Elementor\\Plugin')
            && isset(\Elementor\Plugin::$instance)
            && is_object(\Elementor\Plugin::$instance)
            && isset(\Elementor\Plugin::$instance->documents)) {
            $document = \Elementor\Plugin::$instance->documents->get($pageId);
        }

        $url = $document ? (string) $document->get_edit_url() : (string) get_edit_post_link($pageId, '');
        if ($url === '') {
            self::notice(
                sprintf(__('The %s page exists, but no editor URL is available.', 'rosa-medical'), $label),
                admin_url('edit.php?post_type=page')
            );
            return;
        }

        if ($document && ! headers_sent() && wp_safe_redirect($url)) {
            exit;
        }

        $message = $document
            ? sprintf(__('Open %s in Elementor.', 'rosa-medical'), $label)
            : sprintf(__('Elementor is unavailable. Open %s in the standard WordPress editor.', 'rosa-medical'), $label);
        self::notice($message, $url, $document ? __('Edit with Elementor', 'rosa-medical') : __('Edit Page', 'rosa-medical'));
    }

    private static function notice(string $message, string $url, string $button = ''): void
    {
        $button = $button !== '' ? $button : __('View Pages', 'rosa-medical');
        ?>
        <div class="wrap rosa-content-admin">
            <h1><?php echo esc_html__('Rosa Medical', 'rosa-medical'); ?></h1>
            <div class="notice notice-warning inline"><p><?php echo esc_html($message); ?></p></div>
            <p><a class="button button-primary" href="<?php echo esc_url($url); ?>"><?php echo esc_html($button); ?></a></p>
        </div>
        <?php
    }
}
