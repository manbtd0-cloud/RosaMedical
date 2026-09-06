#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PLUGIN="$ROOT/wp-content/plugins/rosa-medical-core"
CAPABILITIES="$PLUGIN/src/Admin/Capabilities.php"
ADMIN="$PLUGIN/src/Admin/RosaAdmin.php"
CONTENT_PAGE="$PLUGIN/src/Admin/ContentPage.php"
SHORTCUT="$PLUGIN/src/Admin/ElementorShortcutPage.php"
BUSINESS="$PLUGIN/src/Settings/BusinessSettings.php"
PLUGIN_CLASS="$PLUGIN/src/Plugin.php"
MEDIA_FIELD="$PLUGIN/src/Admin/MediaField.php"
BOOTSTRAP="$PLUGIN/rosa-medical-core.php"
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }

[[ -f "$ADMIN" ]] || fail 'RosaAdmin.php missing'
[[ -f "$CONTENT_PAGE" ]] || fail 'ContentPage.php missing for Shop/Site and rollback storage'
[[ -f "$SHORTCUT" ]] || fail 'ElementorShortcutPage.php missing'
[[ -f "$CAPABILITIES" ]] || fail 'Capabilities.php missing for Rosa content-manager boundary'
[[ -f "$PLUGIN/assets/admin/rosa-content-admin.js" ]] || fail 'admin JS missing'
[[ -f "$PLUGIN/assets/admin/rosa-content-admin.css" ]] || fail 'admin CSS missing'

grep -Fq "'rosa_manage_content'" "$CAPABILITIES" || fail 'Rosa content capability missing'
grep -Fq "'rosa_content_manager'" "$CAPABILITIES" || fail 'Rosa content-manager role missing'
grep -Fq "'editor'" "$CAPABILITIES" || fail 'Rosa content-manager must inherit safe page/media capabilities from Editor'
grep -Fq "'shop_manager'" "$CAPABILITIES" || fail 'Rosa content-manager must inherit catalogue capabilities from Woo Shop Manager'
for forbidden in install_plugins edit_plugins update_plugins switch_themes edit_themes; do
  grep -Fq "'$forbidden'" "$CAPABILITIES" || fail "Capabilities must explicitly guard forbidden capability: $forbidden"
done

grep -Fq 'Capabilities.php' "$BOOTSTRAP" || fail 'Capabilities bootstrap missing'
grep -Fq 'Capabilities::class' "$PLUGIN_CLASS" || fail 'Capabilities lifecycle is not registered'
grep -Fq 'option_page_capability_' "$PLUGIN_CLASS" || fail 'Settings option-page capability filters missing'
for group in rosa_business rosa_media rosa_content_site rosa_content_shop; do
  grep -Fq "'$group'" "$PLUGIN_CLASS" || fail "settings capability group missing: $group"
done

grep -Fq 'add_menu_page' "$ADMIN" || fail 'top-level menu missing'
for label in Homepage About Contact Shop 'Site & CTA' Business; do grep -Fq "$label" "$ADMIN" || fail "submenu missing: $label"; done
! grep -Fq 'manage_options' "$ADMIN" || fail 'Rosa content menu is still administrator-only'
grep -Fq 'Capabilities::MANAGE_CONTENT' "$ADMIN" || fail 'Rosa menu does not use custom content capability'

for mapping in "ElementorShortcutPage::render('home', 'Homepage')" "ElementorShortcutPage::render('about', 'About')" "ElementorShortcutPage::render('contact', 'Contact')"; do
  grep -Fq "$mapping" "$ADMIN" || fail "missing Elementor shortcut mapping: $mapping"
done
! grep -Fq "ContentPage::render('home')" "$ADMIN" || fail 'Homepage must not retain competing Rosa content form'
! grep -Fq "ContentPage::render('about')" "$ADMIN" || fail 'About must not retain competing Rosa content form'
! grep -Fq "ContentPage::render('contact')" "$ADMIN" || fail 'Contact must not retain competing Rosa content form'
grep -Fq "self::addContentSubmenu('Shop', 'rosa-medical-shop', 'shop')" "$ADMIN" || fail 'Shop content submenu mapping must remain'
grep -Fq "self::addContentSubmenu('Site & CTA', 'rosa-medical-site', 'site')" "$ADMIN" || fail 'Site & CTA content submenu mapping must remain'
grep -Fq 'ContentPage::render($section)' "$ADMIN" || fail 'shared Shop/Site content-form callback must remain'

grep -Fq 'Capabilities::MANAGE_CONTENT' "$CONTENT_PAGE" || fail 'ContentPage capability boundary missing'
! grep -Fq "current_user_can('manage_options')" "$CONTENT_PAGE" || fail 'ContentPage remains administrator-only'
grep -Fq 'Capabilities::MANAGE_CONTENT' "$BUSINESS" || fail 'Business settings capability boundary missing'
! grep -Fq "current_user_can('manage_options')" "$BUSINESS" || fail 'Business settings render remains administrator-only'
grep -Fq 'Capabilities::MANAGE_CONTENT' "$SHORTCUT" || fail 'Elementor shortcut capability boundary missing'
grep -Fq "current_user_can('edit_post', \$pageId)" "$SHORTCUT" || fail 'Elementor shortcut must also require edit_post for the target page'
! grep -Fq "current_user_can('manage_options')" "$SHORTCUT" || fail 'Elementor shortcut remains administrator-only'

grep -Fq 'ContentSettings.php' "$BOOTSTRAP" || fail 'structured page settings must remain loaded for migration/rollback'
grep -Fq 'get_page_by_path' "$SHORTCUT" || fail 'Elementor shortcut must resolve actual page IDs by path'
grep -Fq 'get_edit_url' "$SHORTCUT" || fail 'Elementor shortcut must derive Elementor editor URL from document'
grep -Fq 'get_edit_post_link' "$SHORTCUT" || fail 'Elementor-unavailable fallback Edit Page link missing'
grep -Fq 'wp_enqueue_media' "$ADMIN" || fail 'media library not enqueued for remaining Rosa media forms'
grep -Fq "'prefooter-person-01' => 'Shared pre-footer image'" "$MEDIA_FIELD" || fail 'shared pre-footer media control missing'
if grep -Fq "'about_international' =>" "$MEDIA_FIELD"; then
  fail 'About page must not expose legacy about_international as a dead media control'
fi

echo 'PASS: Rosa admin, content-manager capability and Elementor shortcut contract'
