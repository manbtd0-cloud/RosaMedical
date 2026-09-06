<?php

declare(strict_types=1);

namespace RosaMedical\Core\Admin;

use RosaMedical\Core\Settings\ContentSchema;
use RosaMedical\Core\Settings\ContentSettings;

final class ContentPage
{
    public static function render(string $section): void
    {
        if (! current_user_can(Capabilities::MANAGE_CONTENT)) {
            return;
        }

        $definition = ContentSchema::section($section);
        if ($definition === []) {
            return;
        }

        $optionName = (string) $definition['option'];
        $stored = get_option($optionName, []);
        $stored = is_array($stored) ? $stored : [];
        $title = (string) $definition['title'];
        $descriptions = [
            'home' => __('Edit Rosa homepage text and approved media. Layout, spacing and product grids are managed by the Rosa theme.', 'rosa-medical'),
            'about' => __('Edit Rosa About page content. Section structure and responsive layout remain managed by the Rosa theme.', 'rosa-medical'),
            'contact' => __('Edit Contact page labels and copy. Phone, email and addresses are managed under Business.', 'rosa-medical'),
            'shop' => __('Edit Shop interface copy here. Add or edit actual products under WooCommerce → Products.', 'rosa-medical'),
            'site' => __('Edit shared header, navigation, footer and quotation CTA labels used across the Rosa website.', 'rosa-medical'),
        ];
        ?>
        <div class="wrap rosa-content-admin">
            <h1><?php echo esc_html(sprintf(__('Rosa Medical — %s', 'rosa-medical'), $title)); ?></h1>
            <?php if (isset($descriptions[$section])) : ?><p class="description"><?php echo esc_html($descriptions[$section]); ?></p><?php endif; ?>
            <div class="rosa-content-admin__actions">
                <?php foreach (self::previewLinks($section) as $label => $url) : ?>
                    <a class="button" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </div>
            <nav class="nav-tab-wrapper" data-rosa-language-tabs>
                <button type="button" class="nav-tab nav-tab-active" data-lang="en"><?php echo esc_html__('English', 'rosa-medical'); ?></button>
                <button type="button" class="nav-tab" data-lang="ar">العربية</button>
            </nav>
            <form method="post" action="options.php">
                <?php settings_fields('rosa_content_' . $section); ?>
                <?php foreach (['en' => 'English', 'ar' => 'العربية'] as $locale => $localeLabel) : ?>
                    <section class="rosa-content-admin__language" data-lang-panel="<?php echo esc_attr($locale); ?>"<?php echo $locale === 'ar' ? ' dir="rtl"' : ''; ?>>
                        <h2><?php echo esc_html($localeLabel); ?></h2>
                        <?php foreach ($definition['groups'] as $groupTitle => $keys) : ?>
                            <div class="rosa-content-admin__group">
                                <h3><?php echo esc_html((string) $groupTitle); ?></h3>
                                <table class="form-table" role="presentation"><tbody>
                                <?php foreach ($keys as $key) :
                                    $field = $definition['fields'][$key] ?? null;
                                    if (! is_array($field)) {
                                        continue;
                                    }
                                    $value = isset($stored[$locale]) && is_array($stored[$locale]) && array_key_exists($key, $stored[$locale])
                                        ? (string) $stored[$locale][$key]
                                        : ContentSettings::get($section, $key, $locale);
                                    $label = self::labelFromKey($key);
                                    ?>
                                    <tr>
                                        <th scope="row"><label for="rosa-<?php echo esc_attr($section . '-' . $locale . '-' . $key); ?>"><?php echo esc_html($label); ?></label></th>
                                        <td>
                                            <?php if (($field['type'] ?? 'text') === 'textarea') : ?>
                                                <textarea class="large-text" rows="4" id="rosa-<?php echo esc_attr($section . '-' . $locale . '-' . $key); ?>" name="<?php echo esc_attr($optionName); ?>[<?php echo esc_attr($locale); ?>][<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($value); ?></textarea>
                                            <?php else : ?>
                                                <input class="regular-text" type="text" id="rosa-<?php echo esc_attr($section . '-' . $locale . '-' . $key); ?>" name="<?php echo esc_attr($optionName); ?>[<?php echo esc_attr($locale); ?>][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
                <?php submit_button(__('Save content', 'rosa-medical')); ?>
            </form>
            <?php if (MediaField::fieldsForSection($section) !== []) : ?>
                <form method="post" action="options.php" class="rosa-content-admin__media-form">
                    <?php settings_fields('rosa_media'); ?>
                    <?php MediaField::renderSection($section); ?>
                    <?php submit_button(__('Save media', 'rosa-medical')); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /** @return array<string,string> */
    private static function previewLinks(string $section): array
    {
        $paths = [
            'home' => ['View English' => '/', 'View العربية' => '/ar/'],
            'about' => ['View English' => '/about/', 'View العربية' => '/ar/about/'],
            'contact' => ['View English' => '/contact/', 'View العربية' => '/ar/contact/'],
            'shop' => ['View English' => '/shop/', 'View العربية' => '/ar/shop/'],
            'site' => ['View homepage' => '/', 'View العربية' => '/ar/'],
        ];
        $result = [];
        foreach ($paths[$section] ?? [] as $label => $path) {
            $result[$label] = home_url($path);
        }
        return $result;
    }

    private static function labelFromKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', preg_replace('/^(stat|benefit|promo|why|proof|evidence|card)_(\d+)_?/', '$1 $2 ', $key) ?? $key));
    }
}
