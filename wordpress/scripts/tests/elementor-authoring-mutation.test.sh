#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
COMPOSE_FILE="$ROOT/wordpress/dev/compose.yaml"
ENV_FILE="$ROOT/wordpress/dev/.env"
BASE_URL="${ROSA_BASE_URL:-http://localhost:8088}"
compose=(docker compose -f "$COMPOSE_FILE")
if [[ -f "$ENV_FILE" ]]; then compose+=(--env-file "$ENV_FILE"); fi
wp(){ "${compose[@]}" run --rm wpcli "$@"; }
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }

admin_id="$(wp user list --role=administrator --field=ID | head -n1 | tr -d '\r')"
[[ "$admin_id" =~ ^[0-9]+$ ]] || fail 'No WordPress administrator available for Elementor mutation test'
wp_admin(){ wp --user="$admin_id" "$@"; }

ids="$(wp_admin eval '
$paths = ["home", "ar", "about", "ar/about", "contact", "ar/contact"];
$ids = [];
foreach ($paths as $path) {
    $page = get_page_by_path($path, OBJECT, "page");
    if (! $page) WP_CLI::error("Missing Elementor authoring page: {$path}");
    $ids[] = (int) $page->ID;
}
echo implode("|", $ids);
')"
IFS='|' read -r home_en_id home_ar_id about_en_id about_ar_id contact_en_id contact_ar_id <<<"$ids"
for id in "$home_en_id" "$home_ar_id" "$about_en_id" "$about_ar_id" "$contact_en_id" "$contact_ar_id"; do
  [[ "$id" =~ ^[0-9]+$ ]] || fail 'Could not resolve all six Elementor page IDs'
done
all_ids=("$home_en_id" "$home_ar_id" "$about_en_id" "$about_ar_id" "$contact_en_id" "$contact_ar_id")

id_csv="$(IFS=,; echo "${all_ids[*]}")"
wp_admin eval "
foreach ([${id_csv}] as \$id) {
    \$document = \\Elementor\\Plugin::\$instance->documents->get((int) \$id, false);
    if (! \$document) WP_CLI::error('Missing Elementor document for post ' . \$id);
    \$raw = get_post_meta((int) \$id, '_elementor_data', true);
    if (! is_string(\$raw) || \$raw === '') WP_CLI::error('Missing raw Elementor JSON for post ' . \$id);
    update_option('rosa_elementor_mutation_raw_backup_' . \$id, \$raw, false);
}
"

restore(){
  wp_admin eval "
  foreach ([${id_csv}] as \$id) {
      \$backup = get_option('rosa_elementor_mutation_raw_backup_' . \$id, null);
      if (! is_string(\$backup) || \$backup === '') continue;
      update_post_meta((int) \$id, '_elementor_data', wp_slash(\$backup));
      clean_post_cache((int) \$id);
      \$restored = get_post_meta((int) \$id, '_elementor_data', true);
      if (! is_string(\$restored) || ! hash_equals(hash('sha256', \$backup), hash('sha256', \$restored))) {
          WP_CLI::error('Exact Elementor JSON restore mismatch for post ' . \$id);
      }
      delete_option('rosa_elementor_mutation_raw_backup_' . \$id);
  }
  " >/dev/null
  printf 'RESTORED: exact Elementor mutation JSON fixtures\n'
}
trap restore EXIT

media_id="$(wp_admin eval "
function rosa_mutate_widget(array &\$elements, string \$widget, array \$changes): bool {
    foreach (\$elements as &\$element) {
        if (! is_array(\$element)) continue;
        if ((string) (\$element['widgetType'] ?? '') === \$widget) {
            if (! isset(\$element['settings']) || ! is_array(\$element['settings'])) \$element['settings'] = [];
            foreach (\$changes as \$key => \$value) \$element['settings'][\$key] = \$value;
            return true;
        }
        if (isset(\$element['elements']) && is_array(\$element['elements']) && rosa_mutate_widget(\$element['elements'], \$widget, \$changes)) return true;
    }
    return false;
}
function rosa_mutate_document(int \$id, string \$widget, array \$changes): void {
    \$document = \\Elementor\\Plugin::\$instance->documents->get(\$id, false);
    if (! \$document) WP_CLI::error('Missing Elementor document for post ' . \$id);
    \$elements = \$document->get_elements_data();
    if (! rosa_mutate_widget(\$elements, \$widget, \$changes)) WP_CLI::error('Widget ' . \$widget . ' not found in post ' . \$id);
    if (! \$document->save(['elements' => \$elements])) WP_CLI::error('Could not save Elementor mutation for post ' . \$id);
}

\$home = \\Elementor\\Plugin::\$instance->documents->get(${home_en_id}, false);
\$homeElements = \$home->get_elements_data();
\$currentMedia = 0;
foreach (\$homeElements as \$root) {
    foreach ((array) (\$root['elements'] ?? []) as \$widget) {
        if ((string) (\$widget['widgetType'] ?? '') === 'rosa-home-hero') \$currentMedia = (int) (\$widget['settings']['image']['id'] ?? 0);
    }
}
\$attachments = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 100, 'fields' => 'ids']);
\$media = 0;
foreach (\$attachments as \$candidate) {
    if ((int) \$candidate > 0 && (int) \$candidate !== \$currentMedia && str_starts_with((string) get_post_mime_type((int) \$candidate), 'image/')) {
        \$media = (int) \$candidate;
        break;
    }
}
if (\$media <= 0) WP_CLI::error('Need a second Media Library image for media mutation test');

rosa_mutate_document(${home_en_id}, 'rosa-home-hero', ['hero_title' => 'TEST ELEMENTOR HOME EN', 'image' => ['id' => \$media]]);
rosa_mutate_document(${home_ar_id}, 'rosa-home-hero', ['hero_title' => 'اختبار الصفحة الرئيسية']);
rosa_mutate_document(${about_en_id}, 'rosa-page-hero-about', ['page_title' => 'TEST ELEMENTOR ABOUT EN']);
rosa_mutate_document(${about_ar_id}, 'rosa-page-hero-about', ['page_title' => 'اختبار من نحن']);
rosa_mutate_document(${contact_en_id}, 'rosa-page-hero-contact', ['page_title' => 'TEST ELEMENTOR CONTACT EN']);
rosa_mutate_document(${contact_ar_id}, 'rosa-page-hero-contact', ['page_title' => 'اختبار اتصل بنا']);
echo \$media;
")"
[[ "$media_id" =~ ^[0-9]+$ ]] || fail 'Media mutation did not return an attachment ID'
media_url="$(wp eval "echo (string) wp_get_attachment_url(${media_id});")"
[[ -n "$media_url" ]] || fail 'Could not resolve selected attachment URL'

assert_markers(){
  local home_en home_ar about_en about_ar contact_en contact_ar
  home_en="$(curl -fsSL "$BASE_URL/")"
  home_ar="$(curl -fsSL "$BASE_URL/ar/")"
  about_en="$(curl -fsSL "$BASE_URL/about/")"
  about_ar="$(curl -fsSL "$BASE_URL/ar/about/")"
  contact_en="$(curl -fsSL "$BASE_URL/contact/")"
  contact_ar="$(curl -fsSL "$BASE_URL/ar/contact/")"

  grep -Fq 'TEST ELEMENTOR HOME EN' <<<"$home_en" || fail 'English Home Elementor edit did not render'
  grep -Fq 'اختبار الصفحة الرئيسية' <<<"$home_ar" || fail 'Arabic Home Elementor edit did not render'
  grep -Fq 'TEST ELEMENTOR ABOUT EN' <<<"$about_en" || fail 'English About Elementor edit did not render'
  grep -Fq 'اختبار من نحن' <<<"$about_ar" || fail 'Arabic About Elementor edit did not render'
  grep -Fq 'TEST ELEMENTOR CONTACT EN' <<<"$contact_en" || fail 'English Contact Elementor edit did not render'
  grep -Fq 'اختبار اتصل بنا' <<<"$contact_ar" || fail 'Arabic Contact Elementor edit did not render'

  ! grep -Fq 'اختبار الصفحة الرئيسية' <<<"$home_en" || fail 'Arabic Home edit leaked into English Home'
  ! grep -Fq 'TEST ELEMENTOR HOME EN' <<<"$home_ar" || fail 'English Home edit leaked into Arabic Home'
  ! grep -Fq 'اختبار من نحن' <<<"$about_en" || fail 'Arabic About edit leaked into English About'
  ! grep -Fq 'TEST ELEMENTOR ABOUT EN' <<<"$about_ar" || fail 'English About edit leaked into Arabic About'
  ! grep -Fq 'اختبار اتصل بنا' <<<"$contact_en" || fail 'Arabic Contact edit leaked into English Contact'
  ! grep -Fq 'TEST ELEMENTOR CONTACT EN' <<<"$contact_ar" || fail 'English Contact edit leaked into Arabic Contact'

  grep -Fq 'lang="ar" dir="rtl"' <<<"$home_ar" || fail 'Arabic Home lost RTL document attributes'
  grep -Fq 'lang="ar" dir="rtl"' <<<"$about_ar" || fail 'Arabic About lost RTL document attributes'
  grep -Fq 'lang="ar" dir="rtl"' <<<"$contact_ar" || fail 'Arabic Contact lost RTL document attributes'
  grep -Fq "$media_url" <<<"$home_en" || fail 'Elementor Home media edit did not render selected attachment URL'
}

assert_markers

home_html="$(curl -fsSL "$BASE_URL/")"
for section in hero who featured feature latest promos why proof evidence; do
  grep -Fq "data-home-section=\"${section}\"" <<<"$home_html" || fail "Home section disappeared after Elementor edit: ${section}"
done

bash "$ROOT/wordpress/scripts/client-preview-seed.sh" >/dev/null
for id in "${all_ids[@]}"; do
  template="$(wp post meta get "$id" _wp_page_template)"
  [[ "$template" == 'page-templates/rosa-elementor-authoring.php' ]] || fail "routine client-preview seed reverted Elementor authoring template for post $id"
done
for id in "$home_en_id" "$home_ar_id"; do
  parity="$(wp post meta get "$id" _rosa_elementor_home_parity_version)"
  [[ "$parity" == '2' ]] || fail 'routine client-preview seed lost finished-template Home parity metadata'
done
seed_output="$(bash "$ROOT/wordpress/scripts/elementor-authoring-seed.sh")"
[[ "$(grep -c '| skipped$' <<<"$seed_output")" -eq 6 ]] || fail 'normal Elementor reseed must skip all six previously migrated documents'

assert_markers

restore
trap - EXIT
for route in / /ar/ /about/ /ar/about/ /contact/ /ar/contact/; do
  html="$(curl -fsSL "$BASE_URL$route")"
  for marker in \
    'TEST ELEMENTOR HOME EN' \
    'اختبار الصفحة الرئيسية' \
    'TEST ELEMENTOR ABOUT EN' \
    'اختبار من نحن' \
    'TEST ELEMENTOR CONTACT EN' \
    'اختبار اتصل بنا'; do
    ! grep -Fq "$marker" <<<"$html" || fail "Mutation fixture did not restore on $route: $marker"
  done
done

printf 'PASS: all six Elementor Home/About/Contact EN/AR edits and Home media persist through routine seeds, then exact JSON fixtures restore\n'
