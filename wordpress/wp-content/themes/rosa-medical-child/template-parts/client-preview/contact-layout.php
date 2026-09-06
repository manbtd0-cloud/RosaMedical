<?php
if (! defined('ABSPATH')) { exit; }
$sectionArgs = isset($args) && is_array($args) ? $args : [];
$locale = (string) ($sectionArgs['locale'] ?? rosa_preview_locale());
$content = isset($sectionArgs['content']) && is_array($sectionArgs['content']) ? $sectionArgs['content'] : [];
$c = static function (string $key, string $en, string $ar) use ($content, $locale): string {
    if (array_key_exists($key, $content) && is_scalar($content[$key])) {
        $value = trim((string) $content[$key]);
        if ($value !== '') {
            return $value;
        }
    }
    return rosa_preview_content('contact', $key, $locale, $locale === 'ar' ? $ar : $en);
};
$address = rosa_preview_business_value('address', $locale);
$phone = rosa_theme_business_value('phone');
$email = rosa_theme_business_value('email');

$conversationEyebrow = $c('conversation_eyebrow', 'GET IN TOUCH', 'تواصل معنا');
$conversationTitle = $c('conversation_title', 'Let’s start talking about your requirements.', 'لنبدأ الحديث عن احتياجك.');
$formTitle = $c('form_title', 'Send us a message', 'أرسل لنا رسالة');
$sendLabel = $c('send_email', 'Send Message', 'إرسال الرسالة');

// Existing Elementor documents may still contain the historical defaults.
// Preserve real client edits, but translate only those untouched old defaults
// to the approved live Contact wording.
if ($formTitle === 'Share your requirements' || $formTitle === 'شارك متطلباتك') {
    $formTitle = $locale === 'ar' ? 'أرسل لنا رسالة' : 'Send us a message';
}
if ($sendLabel === 'Send by email' || $sendLabel === 'إرسال عبر البريد الإلكتروني') {
    $sendLabel = $locale === 'ar' ? 'إرسال الرسالة' : 'Send Message';
}

$nameLabel = $c('field_name', 'Name', 'الاسم');
$phoneLabel = $c('field_phone', 'Phone', 'الهاتف');
$subjectLabel = $c('field_subject', 'Subject', 'الموضوع');
$emailFieldLabel = $c('field_email', 'Email', 'البريد الإلكتروني');
$messageLabel = $c('field_message', 'Message', 'الرسالة');
?>
<section class="rosa-preview-contact" data-preview-contact-layout id="inquiry">
    <div class="rosa-preview-rail">
        <div class="rosa-preview-contact__cards">
            <article class="rosa-preview-contact__card rosa-preview-contact__conversation-card">
                <p class="rosa-preview-eyebrow"><?php echo esc_html($conversationEyebrow); ?></p>
                <h2><?php echo esc_html($conversationTitle); ?></h2>

                <div class="rosa-preview-contact__channels">
                    <article class="rosa-preview-contact__channel" data-preview-contact-location>
                        <span class="rosa-preview-contact__channel-number">01</span>
                        <div>
                            <span class="rosa-preview-contact__channel-label"><?php echo esc_html($c('location_label', 'Location', 'الموقع')); ?></span>
                            <strong><?php echo esc_html($address); ?></strong>
                        </div>
                    </article>
                    <article class="rosa-preview-contact__channel" data-preview-contact-phone>
                        <span class="rosa-preview-contact__channel-number">02</span>
                        <div>
                            <span class="rosa-preview-contact__channel-label"><?php echo esc_html($c('phone_label', 'Call us', 'اتصل بنا')); ?></span>
                            <?php if ($phone !== '') : ?>
                                <a href="tel:<?php echo esc_attr((string) preg_replace('/[^0-9+]/', '', $phone)); ?>"><bdi dir="ltr"><?php echo esc_html($phone); ?></bdi></a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <article class="rosa-preview-contact__channel" data-preview-contact-email>
                        <span class="rosa-preview-contact__channel-number">03</span>
                        <div>
                            <span class="rosa-preview-contact__channel-label"><?php echo esc_html($c('email_label', 'Email us', 'البريد الإلكتروني')); ?></span>
                            <?php if ($email !== '') : ?>
                                <a href="mailto:<?php echo esc_attr($email); ?>"><bdi dir="ltr"><?php echo esc_html($email); ?></bdi></a>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            </article>

            <article class="rosa-preview-contact__card rosa-preview-contact__message-card">
                <h2><?php echo esc_html($formTitle); ?></h2>
                <form class="rosa-preview-contact-form" data-preview-contact-form>
                    <label><?php echo esc_html($nameLabel); ?><input type="text" name="name" autocomplete="name" placeholder="<?php echo esc_attr($nameLabel); ?>"></label>
                    <label><?php echo esc_html($phoneLabel); ?><input type="tel" name="phone" autocomplete="tel" dir="ltr" placeholder="<?php echo esc_attr($phoneLabel); ?>"></label>
                    <label><?php echo esc_html($subjectLabel); ?><input type="text" name="subject" placeholder="<?php echo esc_attr($subjectLabel); ?>"></label>
                    <label><?php echo esc_html($emailFieldLabel); ?><input type="email" name="email" autocomplete="email" dir="ltr" placeholder="<?php echo esc_attr($emailFieldLabel); ?>"></label>
                    <label><?php echo esc_html($messageLabel); ?><textarea name="message" rows="5" placeholder="<?php echo esc_attr($messageLabel); ?>"></textarea></label>
                    <?php if ($email !== '') : ?>
                        <a class="rosa-preview-button rosa-preview-button--accent" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($sendLabel); ?></a>
                    <?php endif; ?>
                </form>
            </article>
        </div>
    </div>
</section>
