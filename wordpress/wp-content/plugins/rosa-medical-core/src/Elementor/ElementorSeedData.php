<?php

declare(strict_types=1);

namespace RosaMedical\Core\Elementor;

use RosaMedical\Core\Settings\ContentSettings;
use RosaMedical\Core\Settings\MediaSettings;

final class ElementorSeedData
{
    public static function deterministicId(string $key): string
    {
        return substr(md5('rosa:' . $key), 0, 8);
    }

    /** @return list<array<string,mixed>> */
    public static function build(string $pageType, string $locale): array
    {
        $locale = $locale === 'ar' ? 'ar' : 'en';
        $specs = self::specs($pageType);
        if ($specs === []) {
            return [];
        }

        $widgets = [];
        foreach ($specs as $index => $spec) {
            $settings = [];
            foreach ($spec['content'] as $key) {
                $settings[$key] = ContentSettings::get($spec['section'], $key, $locale);
            }
            foreach ($spec['media'] as $control => $mediaKey) {
                $id = MediaSettings::id($mediaKey);
                $settings[$control] = $id > 0 ? ['id' => $id] : [];
            }
            $instanceKey = sprintf('%s-%s-%02d-%s', $pageType, $locale, $index + 1, $spec['widget']);
            $widgets[] = [
                'id' => self::deterministicId($instanceKey),
                'elType' => 'widget',
                'widgetType' => $spec['widget'],
                'isInner' => false,
                'settings' => $settings,
                'elements' => [],
            ];
        }

        return [[
            'id' => self::deterministicId($pageType . '-' . $locale . '-root'),
            'elType' => 'container',
            'isInner' => false,
            'settings' => [
                'css_classes' => 'rosa-elementor-root',
                'content_width' => 'full',
                'gap' => ['unit' => 'px', 'size' => 0, 'sizes' => []],
                'padding' => [
                    'unit' => 'px',
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'isLinked' => true,
                ],
            ],
            'elements' => $widgets,
        ]];
    }

    /** @return list<array{widget:string,section:string,content:list<string>,media:array<string,string>}> */
    private static function specs(string $pageType): array
    {
        if ($pageType === 'home') {
            return [
                self::spec('rosa-home-hero', 'home', ['hero_eyebrow', 'hero_title', 'hero_body', 'hero_button'], ['image' => 'home-hero-01']),
                self::spec('rosa-home-who', 'home', ['who_eyebrow', 'who_title', 'who_body', 'who_button', 'stat_1_value', 'stat_1_label', 'stat_2_value', 'stat_2_label', 'stat_3_value', 'stat_3_label'], ['image' => 'home-who-01']),
                self::spec('rosa-home-featured', 'home', ['featured_title', 'benefit_1_title', 'benefit_1_body', 'benefit_2_title', 'benefit_2_body', 'benefit_3_title', 'benefit_3_body']),
                self::spec('rosa-home-feature-banner', 'home', ['feature_eyebrow', 'feature_title', 'feature_body', 'feature_button'], ['image' => 'home-feature-01']),
                self::spec('rosa-home-latest', 'home', ['latest_title']),
                self::spec('rosa-home-promotions', 'home', ['promo_1_title', 'promo_1_body', 'promo_2_title', 'promo_2_body', 'promo_3_title', 'promo_3_body', 'promo_4_title', 'promo_4_body'], [
                    'image_1' => 'home-promo-01',
                    'image_2' => 'home-promo-02',
                    'image_3' => 'home-promo-03',
                    'image_4' => 'home-promo-04',
                ]),
                self::spec('rosa-home-why', 'home', ['why_eyebrow', 'why_title', 'why_1_title', 'why_1_body', 'why_2_title', 'why_2_body', 'why_3_title', 'why_3_body'], ['image' => 'home-why-01']),
                self::spec('rosa-home-proof', 'home', ['proof_1', 'proof_2', 'proof_3', 'proof_4', 'proof_5', 'proof_6']),
                self::spec('rosa-home-evidence', 'home', ['evidence_eyebrow', 'evidence_title', 'evidence_body', 'evidence_1_title', 'evidence_1_body', 'evidence_2_title', 'evidence_2_body', 'evidence_3_title', 'evidence_3_body'], ['image' => 'home-evidence-01']),
            ];
        }

        if ($pageType === 'about') {
            return [
                self::spec('rosa-page-hero-about', 'about', ['page_eyebrow', 'page_title', 'page_body']),
                self::spec('rosa-about-who', 'about', ['who_eyebrow', 'who_title', 'who_body'], ['image' => 'about_procurement']),
                self::spec('rosa-about-stats', 'about', ['stat_1_value', 'stat_1_label', 'stat_2_value', 'stat_2_label', 'stat_3_value', 'stat_3_label']),
                self::spec('rosa-about-cards', 'about', ['card_1_title', 'card_1_body', 'card_1_cta', 'card_2_title', 'card_2_body', 'card_2_cta', 'card_3_title', 'card_3_body', 'card_3_cta']),
                self::spec('rosa-about-feature', 'about', ['feature_eyebrow', 'feature_title', 'feature_body'], ['image' => 'about_hospitals']),
                self::spec('rosa-about-why', 'about', ['why_title', 'why_1_title', 'why_1_body', 'why_2_title', 'why_2_body', 'why_3_title', 'why_3_body']),
                self::spec('rosa-about-proof', 'about', ['proof_1', 'proof_2', 'proof_3']),
            ];
        }

        if ($pageType === 'contact') {
            return [
                self::spec('rosa-page-hero-contact', 'contact', ['page_eyebrow', 'page_title', 'page_body']),
                self::spec('rosa-contact-layout', 'contact', ['location_label', 'phone_label', 'email_label', 'field_name', 'field_phone', 'field_subject', 'field_message', 'send_email']),
            ];
        }

        return [];
    }

    /** @param list<string> $content @param array<string,string> $media */
    private static function spec(string $widget, string $section, array $content, array $media = []): array
    {
        return [
            'widget' => $widget,
            'section' => $section,
            'content' => $content,
            'media' => $media,
        ];
    }
}
