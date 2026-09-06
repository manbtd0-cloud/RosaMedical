<?php
if (! defined('ABSPATH')) { exit; }
$sectionArgs = isset($args) && is_array($args) ? $args : [];
$locale = (string) ($sectionArgs['locale'] ?? rosa_preview_locale());
$section = (string) ($sectionArgs['section'] ?? 'about');
$isContact = $section === 'contact';
$eyebrowFallback = 'ROSA';
$titleFallback = $isContact ? ($locale === 'ar' ? 'اتصل بنا' : 'Contact us') : ($locale === 'ar' ? 'من نحن' : 'About us');
$bodyFallback = $isContact
    ? ($locale === 'ar' ? 'تواصل معنا وأخبرنا كيف يمكننا مساعدتك.' : 'Get in touch and let us know how we can help.')
    : ($locale === 'ar' ? 'تعرف على نهج روزا في دعم اكتشاف الأدوات الطبية والتوريد.' : 'Learn about Rosa’s approach to medical-instrument discovery and procurement support.');
$classes = 'rosa-preview-page-hero' . ($isContact ? ' rosa-preview-page-hero--contact' : '');
?>
<section class="<?php echo esc_attr($classes); ?>" data-preview-page-hero><div class="rosa-preview-rail"><p class="rosa-preview-eyebrow"><?php echo esc_html(rosa_preview_section_value($sectionArgs, $section, 'page_eyebrow', $locale, $eyebrowFallback)); ?></p><h1><?php echo esc_html(rosa_preview_section_value($sectionArgs, $section, 'page_title', $locale, $titleFallback)); ?></h1><p><?php echo esc_html(rosa_preview_section_value($sectionArgs, $section, 'page_body', $locale, $bodyFallback)); ?></p></div></section>
