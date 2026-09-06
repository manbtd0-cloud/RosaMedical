<?php
declare(strict_types=1);

$GLOBALS['rosa_test_options'] = [
    'rosa_business_settings' => [
        'phone' => '+966 50 123 4567',
        'email' => 'sales@example.test',
        'address' => 'Riyadh, Saudi Arabia',
        'address_ar' => 'طريق الملك فهد، العليا، الرياض',
        'whatsapp' => '+966 50 765 4321',
        'primary_cta_label' => 'Request a Quote',
    ],
];

function get_option(string $name, mixed $default = false): mixed
{
    return $GLOBALS['rosa_test_options'][$name] ?? $default;
}

function sanitize_text_field(string $value): string
{
    return trim(strip_tags($value));
}

function sanitize_email(string $value): string
{
    $value = trim($value);
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
}

require_once __DIR__ . '/../../wp-content/plugins/rosa-medical-core/src/Settings/BusinessSettings.php';

use RosaMedical\Core\Settings\BusinessSettings;

function expectSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

expectSame('+966 50 123 4567', BusinessSettings::get('phone'), 'known phone setting should be returned as stored');
expectSame('sales@example.test', BusinessSettings::get('email'), 'known email setting should be returned as stored');
expectSame('Riyadh, Saudi Arabia', BusinessSettings::get('address'), 'English address should be centrally available');
expectSame('طريق الملك فهد، العليا، الرياض', BusinessSettings::get('address_ar'), 'Arabic address should be centrally available');
expectSame('+966 50 765 4321', BusinessSettings::get('whatsapp'), 'WhatsApp should be centrally available');
expectSame('Request a Quote', BusinessSettings::get('primary_cta_label'), 'primary CTA label should be centrally available');
expectSame('fallback', BusinessSettings::get('unknown', 'fallback'), 'unknown key should return default');

$sanitized = BusinessSettings::sanitize([
    'phone' => ' <b>+966 50 123 4567</b> ',
    'email' => ' sales@example.test ',
    'address' => '  Sialkot <script>alert(1)</script> ',
    'address_ar' => ' طريق الملك فهد، العليا، الرياض ',
    'whatsapp' => ' <strong>+966 50 765 4321</strong> ',
    'primary_cta_label' => ' Request a Quote ',
    'not_allowed' => 'drop me',
]);

expectSame('+966 50 123 4567', $sanitized['phone'], 'phone should be sanitized');
expectSame('sales@example.test', $sanitized['email'], 'email should be sanitized');
expectSame('Sialkot alert(1)', $sanitized['address'], 'address should be sanitized');
expectSame('طريق الملك فهد، العليا، الرياض', $sanitized['address_ar'], 'Arabic address should be sanitized');
expectSame('+966 50 765 4321', $sanitized['whatsapp'], 'WhatsApp should be sanitized');
expectSame('Request a Quote', $sanitized['primary_cta_label'], 'primary CTA label should be sanitized');
expectSame(false, array_key_exists('not_allowed', $sanitized), 'unknown keys must be discarded');

fwrite(STDOUT, "PASS: BusinessSettings contract\n");
