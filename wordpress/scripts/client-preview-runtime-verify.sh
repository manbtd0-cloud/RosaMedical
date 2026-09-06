#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/wordpress/dev/compose.yaml"
ENV_FILE="$ROOT_DIR/wordpress/dev/.env"
THEME="$ROOT_DIR/wordpress/wp-content/themes/rosa-medical-child"
compose=(docker compose -f "$COMPOSE_FILE")
if [[ -f "$ENV_FILE" ]]; then compose+=(--env-file "$ENV_FILE"); fi
wp(){ "${compose[@]}" run --rm wpcli "$@"; }
fail(){ printf 'Client preview runtime verification failed: %s\n' "$1" >&2; exit 1; }
run(){ printf '==> %s\n' "$*"; "$@"; }

cd "$ROOT_DIR"

run php wordpress/scripts/tests/elementor-authoring-integration.test.php
run php wordpress/scripts/tests/elementor-authoring-seed-contract.test.php
run php wordpress/scripts/tests/elementor-authoring-root-class-migration.test.php
run php wordpress/scripts/tests/elementor-home-parity-migration.test.php
run php wordpress/scripts/tests/medicashop-elementor-home-contract.test.php

source_tests=(
  wordpress/scripts/tests/medicashop-elementor-reference-contract.test.sh
  wordpress/scripts/tests/foundation-preflight.test.sh
  wordpress/scripts/tests/foundation-contract.test.sh
  wordpress/scripts/tests/foundation-theme-contract.test.sh
  wordpress/scripts/tests/foundation-verify-contract.test.sh
  wordpress/scripts/tests/product-template-hook.test.sh
  wordpress/scripts/tests/product-detail-structure.test.sh
  wordpress/scripts/tests/client-preview-reference-boundary.test.sh
  wordpress/scripts/tests/client-preview-seed-contract.test.sh
  wordpress/scripts/tests/client-preview-shell-contract.test.sh
  wordpress/scripts/tests/client-preview-home-contract.test.sh
  wordpress/scripts/tests/client-preview-about-contract.test.sh
  wordpress/scripts/tests/client-preview-contact-contract.test.sh
  wordpress/scripts/tests/client-preview-shop-contract.test.sh
  wordpress/scripts/tests/client-preview-rtl-contract.test.sh
  wordpress/scripts/tests/client-preview-runtime-tooling.test.sh
  wordpress/scripts/tests/client-preview-admin-contract.test.sh
  wordpress/scripts/tests/elementor-authoring-theme-contract.test.sh
  wordpress/scripts/tests/hostinger-migration-tooling.test.sh
)
for test_file in "${source_tests[@]}"; do run bash "$test_file"; done
run php wordpress/scripts/tests/business-settings.test.php
run php wordpress/scripts/tests/content-settings.test.php
run php wordpress/scripts/tests/media-settings.test.php
run php wordpress/scripts/tests/client-preview-content.test.php

while IFS= read -r -d '' file; do php -l "$file" >/dev/null || fail "PHP syntax error: $file"; done < <(find wordpress/wp-content -type f -name '*.php' -print0)
while IFS= read -r -d '' file; do bash -n "$file" || fail "shell syntax error: $file"; done < <(find wordpress/scripts -type f -name '*.sh' -print0)
run node --check "$THEME/assets/js/client-preview.js"
run node --check "$ROOT_DIR/wordpress/wp-content/plugins/rosa-medical-core/assets/admin/rosa-content-admin.js"
run node --check wordpress/scripts/tests/medicashop-elementor-home-fidelity.test.mjs
run node --check wordpress/scripts/medicashop-elementor-parity-capture.mjs
run node --check wordpress/scripts/tests/elementor-authoring-about-contact.test.mjs
run node --check wordpress/scripts/live-visual-audit.mjs
run node --check wordpress/scripts/live-site-sync-check.mjs
run node wordpress/scripts/tests/client-preview-capture.test.mjs

run bash "$SCRIPT_DIR/foundation-bootstrap.sh"
run bash "$SCRIPT_DIR/client-preview-seed.sh"
run bash "$SCRIPT_DIR/foundation-seed.sh"
run bash "$SCRIPT_DIR/foundation-product-verify.sh"
run bash "$SCRIPT_DIR/elementor-authoring-seed.sh"
run bash wordpress/scripts/tests/elementor-authoring-runtime.test.sh
run bash wordpress/scripts/tests/elementor-authoring-editor-links.test.sh

page_url(){
  local path="$1"
  wp eval "\$page=get_page_by_path('${path}', OBJECT, 'page'); if(!\$page){WP_CLI::error('Missing preview page: ${path}');} echo get_permalink((int)\$page->ID);"
}

home_url="$(wp option get home)"
about_url="$(page_url 'about')"
contact_url="$(page_url 'contact')"
shop_id="$(wp option get woocommerce_shop_page_id)"
[[ "$shop_id" =~ ^[0-9]+$ && "$shop_id" -gt 0 ]] || fail 'WooCommerce Shop page ID is missing'
shop_url="$(wp post url "$shop_id")"
ar_home_url="$(page_url 'ar')"
ar_about_url="$(page_url 'ar/about')"
ar_contact_url="$(page_url 'ar/contact')"
ar_shop_url="$(page_url 'ar/shop')"
product_id="$(wp post list --post_type=product --name='rosa-foundation-stevens-scissors-regular' --post_status=publish --field=ID --format=ids | awk '{print $1}')"
[[ -n "$product_id" ]] || fail 'canonical Stevens product is missing'
product_url="$(wp post url "$product_id")"

phone="$(wp eval '$settings=get_option("rosa_business_settings",[]); echo is_array($settings)?trim((string)($settings["phone"]??"")):"";')"
email="$(wp eval '$settings=get_option("rosa_business_settings",[]); echo is_array($settings)?trim((string)($settings["email"]??"")):"";')"
address="$(wp eval '$settings=get_option("rosa_business_settings",[]); echo is_array($settings)?trim((string)($settings["address"]??"")):"";')"
address_ar="$(wp eval '$settings=get_option("rosa_business_settings",[]); echo is_array($settings)?trim((string)($settings["address_ar"]??"")):"";')"
[[ -n "$phone" ]] || fail 'verified Rosa phone is empty'
[[ -n "$email" ]] || fail 'verified Rosa email is empty'
[[ -n "$address" ]] || fail 'verified Rosa address is empty'
[[ -n "$address_ar" ]] || fail 'verified Rosa Arabic address is empty'

visible_text(){
  python3 -c 'import sys
from html.parser import HTMLParser
class P(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True); self.skip=0; self.parts=[]
    def handle_starttag(self, tag, attrs):
        if tag.lower() in {"script","style","noscript","template"}: self.skip += 1
    def handle_endtag(self, tag):
        if tag.lower() in {"script","style","noscript","template"} and self.skip: self.skip -= 1
    def handle_data(self, data):
        if not self.skip and data.strip(): self.parts.append(data.strip())
p=P(); p.feed(sys.stdin.read()); print(" ".join(" ".join(p.parts).split()))'
}

assert_single_main(){
  local label="$1" html="$2" count
  count="$(printf '%s' "$html" | python3 -c 'import re,sys; print(len(re.findall(r"<main(?:\s|>)", sys.stdin.read(), flags=re.I)))')"
  [[ "$count" == '1' ]] || fail "$label expected exactly one <main>, found $count"
}

assert_preview_page(){
  local label="$1" url="$2" locale="$3" html text
  html="$(curl -fsSL "$url")" || fail "$label did not return HTTP success: $url"
  assert_single_main "$label" "$html"
  [[ "$html" == *'data-rosa-preview-shell'* ]] || fail "$label preview shell marker missing"
  if [[ "$locale" == 'ar' ]]; then
    [[ "$html" == *'<html lang="ar" dir="rtl">'* ]] || fail "$label Arabic lang/dir output missing"
  else
    [[ "$html" == *'<html lang="en-US" dir="ltr">'* ]] || fail "$label English lang/dir output missing"
  fi
  [[ "$html" != *'preview.themeforest.net'* && "$html" != *'fullkit.moxcreative.com'* ]] || fail "$label references ThemeForest/demo URLs"
  text="$(printf '%s' "$html" | visible_text)"
  if grep -Eqi 'Medicashop|MoxCreative|Add to cart|Checkout|My Account|ratings?|wishlist|shipping|returns?|secure payment|newsletter' <<<"$text"; then
    fail "$label contains unsupported retail/demo language"
  fi
  if grep -Eqi 'Coming Soon' <<<"$text"; then
    fail "$label is intercepted by WooCommerce Coming Soon"
  fi
}

assert_preview_page 'English Home' "$home_url" en
assert_preview_page 'English About' "$about_url" en
assert_preview_page 'English Contact' "$contact_url" en
assert_preview_page 'English Shop' "$shop_url" en
assert_preview_page 'Arabic Home' "$ar_home_url" ar
assert_preview_page 'Arabic About' "$ar_about_url" ar
assert_preview_page 'Arabic Contact' "$ar_contact_url" ar
assert_preview_page 'Arabic Shop' "$ar_shop_url" ar

contact_html="$(curl -fsSL "$contact_url")"
home_html="$(curl -fsSL "$home_url")"
ar_contact_html="$(curl -fsSL "$ar_contact_url")"
ar_shop_html="$(curl -fsSL "$ar_shop_url")"
contact_text="$(printf '%s' "$contact_html" | visible_text)"
home_text="$(printf '%s' "$home_html" | visible_text)"
ar_contact_text="$(printf '%s' "$ar_contact_html" | visible_text)"
ar_shop_text="$(printf '%s' "$ar_shop_html" | visible_text)"
for value in "$phone" "$email" "$address"; do
  grep -Fq -- "$value" <<<"$contact_text" || fail "English Contact does not render verified business value: $value"
  grep -Fq -- "$value" <<<"$home_text" || fail "Home/footer does not render verified business value: $value"
done
grep -Fq -- "$address_ar" <<<"$ar_contact_text" || fail 'Arabic Contact does not render the centralized Arabic address'
grep -Fq -- 'البحث في المنتجات' <<<"$ar_shop_text" || fail 'Arabic Shop search is missing'

product_html="$(curl -fsSL "$product_url")" || fail "Stevens Product Detail did not return HTTP success: $product_url"
assert_single_main 'Stevens Product Detail' "$product_html"
product_text="$(printf '%s' "$product_html" | visible_text)"
for value in 'Stevens Scissors' '04-0901' '04-0911' 'Straight' 'Curved'; do
  grep -Fq -- "$value" <<<"$product_text" || fail "Stevens Product Detail missing: $value"
done

run bash wordpress/scripts/tests/client-preview-content-zero-drift.test.sh
run bash wordpress/scripts/tests/client-preview-content-mutation.test.sh
run bash wordpress/scripts/tests/elementor-authoring-mutation.test.sh
run node wordpress/scripts/tests/client-preview-accessibility.test.mjs "$home_url"
run node wordpress/scripts/tests/medicashop-elementor-home-fidelity.test.mjs "$home_url"
run node wordpress/scripts/tests/elementor-authoring-about-contact.test.mjs "$home_url"

# Strict visual acceptance is opt-in because it consumes the frozen live-site audit.
# Normal/offline verification remains deterministic and does not require internet access.
if [[ -n "${ROSA_LIVE_BASELINE_DIR:-}" ]]; then
  [[ -d "$ROSA_LIVE_BASELINE_DIR" ]] || fail "ROSA_LIVE_BASELINE_DIR is not a directory: $ROSA_LIVE_BASELINE_DIR"
  strict_out="${ROSA_LIVE_AUDIT_OUT:-$ROOT_DIR/artifacts/live-visual-recovery/strict-runtime}"
  run node wordpress/scripts/live-visual-audit.mjs \
    --baseline "$ROSA_LIVE_BASELINE_DIR" \
    --current "$home_url" \
    --out "$strict_out" \
    --verify

  # Periodic production sync is separately opt-in. It detects target drift and never
  # mutates/replaces the frozen baseline.
  if [[ -n "${ROSA_LIVE_REFERENCE_URL:-}" ]]; then
    sync_out="${ROSA_LIVE_SYNC_OUT:-$ROOT_DIR/artifacts/live-visual-recovery/strict-runtime-live-sync}"
    run node wordpress/scripts/live-site-sync-check.mjs \
      --baseline "$ROSA_LIVE_BASELINE_DIR" \
      --live "$ROSA_LIVE_REFERENCE_URL" \
      --out "$sync_out"
  fi
fi

printf 'PASS: Rosa WordPress runtime passes bilingual authoring, catalogue, ownership, accessibility and optional frozen-live visual acceptance\n'
