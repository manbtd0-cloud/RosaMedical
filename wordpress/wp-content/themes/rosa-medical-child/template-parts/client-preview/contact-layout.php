<?php
if (! defined('ABSPATH')) { exit; }
$sectionArgs = isset($args) && is_array($args) ? $args : [];
$locale = (string) ($sectionArgs['locale'] ?? rosa_preview_locale());
$c = static fn(string $key, string $en, string $ar): string => rosa_preview_section_value($sectionArgs, 'contact', $key, $locale, $locale === 'ar' ? $ar : $en);
$address = rosa_preview_business_value('address', $locale);
$phone = rosa_theme_business_value('phone');
$email = rosa_theme_business_value('email');
?>
<section class="rosa-preview-contact" data-preview-contact-layout id="inquiry">
    <div class="rosa-preview-rail">
        <div class="rosa-preview-contact__cards">
            <article class="rosa-preview-contact__card rosa-preview-contact__conversation-card">
                <p class="rosa-preview-eyebrow"><?php echo esc_html($c('conversation_eyebrow', 'Contact Rosa', 'تواصل مع روزا')); ?></p>
                <h2><?php echo esc_html($c('conversation_title', 'Let’s start talking about your requirements.', 'لنبدأ الحديث عن متطلباتك.')); ?></h2>
                <p class="rosa-preview-contact__intro"><?php echo esc_html($c('conversation_body', 'Share the instrument family, catalogue reference or configuration you need and our team will help you prepare the next step.', 'شارك فئة الأداة أو مرجع الكتالوج أو التكوين المطلوب وسيساعدك فريقنا في الخطوة التالية.')); ?></p>

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
                <h2><?php echo esc_html($c('message_title', 'Send us a message', 'أرسل لنا رسالة')); ?></h2>
                <form class="rosa-preview-contact-form" data-preview-contact-form>
                    <label><?php echo esc_html($c('field_name', 'Name', 'الاسم')); ?><input type="text" name="name" autocomplete="name"></label>
                    <label><?php echo esc_html($c('field_phone', 'Phone', 'الهاتف')); ?><input type="tel" name="phone" autocomplete="tel" dir="ltr"></label>
                    <label><?php echo esc_html($c('field_subject', 'Subject', 'الموضوع')); ?><input type="text" name="subject"></label>
                    <label><?php echo esc_html($c('field_message', 'Message', 'الرسالة')); ?><textarea name="message" rows="6"></textarea></label>
                    <?php if ($email !== '') : ?>
                        <a class="rosa-preview-button rosa-preview-button--accent" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($c('send_email', 'Send by email', 'إرسال عبر البريد الإلكتروني')); ?></a>
                    <?php endif; ?>
                </form>
            </article>
        </div>
    </div>
</section>
