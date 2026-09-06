#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../../.." && pwd)"
COMPOSE_FILE="$ROOT_DIR/wordpress/dev/compose.yaml"
ENV_FILE="$ROOT_DIR/wordpress/dev/.env"
compose=(docker compose -f "$COMPOSE_FILE")
if [[ -f "$ENV_FILE" ]]; then compose+=(--env-file "$ENV_FILE"); fi
wp(){ "${compose[@]}" run --rm wpcli "$@"; }
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }

"${compose[@]}" up -d db wordpress >/dev/null
home_url="$(wp option get home)"
base_url="${home_url%/}"
fixture_slug='rosa-foundation-stevens-scissors-regular'

option_state_b64="$(wp eval '
$names=["rosa_business_settings","rosa_home_content","rosa_site_content","rosa_shop_content","rosa_preview_media"];
$state=[];
foreach($names as $name){$value=get_option($name,null);$state[$name]=["exists"=>$value!==null,"value"=>$value];}
echo base64_encode(wp_json_encode($state, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
')"
product_state_b64="$(wp eval '
$post=get_page_by_path("rosa-foundation-stevens-scissors-regular", OBJECT, "product");
if(!$post) WP_CLI::error("Canonical Stevens fixture product is missing");
$product=wc_get_product((int)$post->ID);
if(!$product instanceof WC_Product) WP_CLI::error("Canonical Stevens fixture is not a WooCommerce product");
$state=["id"=>$product->get_id(),"name"=>$product->get_name(),"description"=>$product->get_description()];
echo base64_encode(wp_json_encode($state, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
')"
original_product_name="$(wp eval '
$post=get_page_by_path("rosa-foundation-stevens-scissors-regular", OBJECT, "product");
$product=$post?wc_get_product((int)$post->ID):false;
echo $product instanceof WC_Product ? $product->get_name() : "";
')"
[[ -n "$original_product_name" ]] || fail 'Could not resolve canonical Stevens fixture name'

home_before="$(curl -fsSL "$base_url/")" || fail 'English Home fetch failed before ownership mutation'
home_consumes_fixture=0
if grep -Fq "$original_product_name" <<<"$home_before"; then home_consumes_fixture=1; fi

restore(){
  wp eval "
  \$state=json_decode(base64_decode('${option_state_b64}'),true);
  if(!is_array(\$state)) WP_CLI::error('Could not decode ownership option backup');
  foreach(\$state as \$name=>\$entry){
      if(!empty(\$entry['exists'])) update_option(\$name,\$entry['value']);
      else delete_option(\$name);
  }
  " >/dev/null || true
  wp eval "
  \$state=json_decode(base64_decode('${product_state_b64}'),true);
  if(!is_array(\$state) || empty(\$state['id'])) WP_CLI::error('Could not decode ownership product backup');
  \$product=wc_get_product((int)\$state['id']);
  if(!\$product instanceof WC_Product) WP_CLI::error('Could not reload ownership product backup');
  \$product->set_name((string)\$state['name']);
  \$product->set_description((string)\$state['description']);
  \$product->save();
  wc_delete_product_transients((int)\$state['id']);
  clean_post_cache((int)\$state['id']);
  " >/dev/null || true
}
trap restore EXIT

wp eval '
$business=get_option("rosa_business_settings",[]); if(!is_array($business)) $business=[];
$business["phone"]="+966 55 000 1122";
$business["email"]="ownership-test@example.invalid";
$business["address"]="Ownership Test Address";
$business["address_ar"]="عنوان اختبار الملكية";
$business["whatsapp"]="+966 55 000 3344";
update_option("rosa_business_settings",$business);

$home=get_option("rosa_home_content",[]); if(!is_array($home)) $home=[];
if(!isset($home["en"])||!is_array($home["en"])) $home["en"]=[];
$home["en"]["hero_title"]="ROLLBACK ONLY HOME TITLE";
update_option("rosa_home_content",$home);

$site=get_option("rosa_site_content",[]); if(!is_array($site)) $site=[];
if(!isset($site["en"])||!is_array($site["en"])) $site["en"]=[];
$site["en"]["cta_title"]="OWNERSHIP TEST CTA";
update_option("rosa_site_content",$site);

$shop=get_option("rosa_shop_content",[]); if(!is_array($shop)) $shop=[];
if(!isset($shop["en"])||!is_array($shop["en"])) $shop["en"]=[];
$shop["en"]["hero_title"]="LIVE SHOP TITLE";
update_option("rosa_shop_content",$shop);

$media=get_option("rosa_preview_media",[]); if(!is_array($media)) $media=[];
$media["home-hero-01"]=777;
update_option("rosa_preview_media",$media);

$post=get_page_by_path("rosa-foundation-stevens-scissors-regular", OBJECT, "product");
if(!$post) WP_CLI::error("Canonical Stevens fixture product is missing during mutation");
$product=wc_get_product((int)$post->ID);
if(!$product instanceof WC_Product) WP_CLI::error("Canonical Stevens fixture is not a WooCommerce product during mutation");
$product->set_name("OWNERSHIP TEST STEVENS");
$product->save();
wc_delete_product_transients($product->get_id());
clean_post_cache($product->get_id());
' >/dev/null

assert_public_ownership(){
  local home_html about_html contact_html ar_contact_html shop_html product_html
  home_html="$(curl -fsSL "$base_url/")" || fail 'English Home fetch failed after ownership mutation'
  about_html="$(curl -fsSL "$base_url/about/")" || fail 'English About fetch failed after ownership mutation'
  contact_html="$(curl -fsSL "$base_url/contact/")" || fail 'English Contact fetch failed after ownership mutation'
  ar_contact_html="$(curl -fsSL "$base_url/ar/contact/")" || fail 'Arabic Contact fetch failed after ownership mutation'
  shop_html="$(curl -fsSL "$base_url/shop/")" || fail 'English Shop fetch failed after ownership mutation'
  product_html="$(curl -fsSL "$base_url/product/$fixture_slug/")" || fail 'Stevens Product Detail fetch failed after ownership mutation'

  ! grep -Fq 'ROLLBACK ONLY HOME TITLE' <<<"$home_html" || fail 'legacy Homepage option still competes with Elementor public content'

  for html_name in home_html about_html contact_html; do
    local html="${!html_name}"
    grep -Fq 'OWNERSHIP TEST CTA' <<<"$html" || fail "Shared Site & CTA setting did not render on $html_name"
  done

  grep -Fq '+966 55 000 1122' <<<"$home_html" || fail 'central phone did not render in shared announcement/header'
  grep -Fq 'ownership-test@example.invalid' <<<"$home_html" || fail 'central email did not render in shared announcement/header'
  grep -Fq 'Ownership Test Address' <<<"$home_html" || fail 'central English address did not render in shared footer'

  grep -Fq '+966 55 000 1122' <<<"$contact_html" || fail 'central phone did not render on English Contact'
  grep -Fq 'ownership-test@example.invalid' <<<"$contact_html" || fail 'central email did not render on English Contact'
  grep -Fq 'Ownership Test Address' <<<"$contact_html" || fail 'central English address did not render on English Contact'
  grep -Fq 'عنوان اختبار الملكية' <<<"$ar_contact_html" || fail 'central Arabic address did not render on Arabic Contact'
  grep -Fq '+966 55 000 1122' <<<"$ar_contact_html" || fail 'central phone did not render on Arabic Contact'
  grep -Fq 'ownership-test@example.invalid' <<<"$ar_contact_html" || fail 'central email did not render on Arabic Contact'

  grep -Fq 'LIVE SHOP TITLE' <<<"$shop_html" || fail 'Shop content setting no longer renders dynamically'
  grep -Fq 'OWNERSHIP TEST STEVENS' <<<"$shop_html" || fail 'Woo product-name mutation did not render on Shop'
  grep -Fq 'OWNERSHIP TEST STEVENS' <<<"$product_html" || fail 'Woo product-name mutation did not render on Product Detail'
  grep -Fq '04-0901' <<<"$product_html" || fail 'Product Detail lost canonical Woo variation SKU 04-0901'
  grep -Fq '04-0911' <<<"$product_html" || fail 'Product Detail lost canonical Woo variation SKU 04-0911'

  if [[ "$home_consumes_fixture" -eq 1 ]]; then
    grep -Fq 'OWNERSHIP TEST STEVENS' <<<"$home_html" || fail 'Home consumed Stevens before mutation but did not update from Woo ownership'
  fi
}

assert_elementor_excludes_protected_values(){
  wp eval '
  $paths=["home","ar","about","ar/about","contact","ar/contact"];
  $needles=[
      "04-0901","04-0911","OWNERSHIP TEST STEVENS",
      "ownership-test@example.invalid","+966 55 000 1122","Ownership Test Address",
      "عنوان اختبار الملكية","+966 55 000 3344","OWNERSHIP TEST CTA"
  ];
  foreach($paths as $path){
      $page=get_page_by_path($path, OBJECT, "page");
      if(!$page) WP_CLI::error("Missing Elementor document for ownership check: {$path}");
      $raw=get_post_meta((int)$page->ID,"_elementor_data",true);
      if(!is_string($raw)) WP_CLI::error("Missing Elementor JSON for ownership check: {$path}");
      foreach($needles as $needle){
          if(strpos($raw,$needle)!==false) WP_CLI::error("Protected ownership value leaked into Elementor JSON on {$path}: {$needle}");
      }
  }
  WP_CLI::success("Protected Woo/business/shared values remain outside Elementor JSON");
  '
}

assert_public_ownership
assert_elementor_excludes_protected_values

whatsapp_value="$(wp eval '$value=get_option("rosa_business_settings",[]); echo is_array($value)?(string)($value["whatsapp"]??""):"";')"
[[ "$whatsapp_value" == '+966 55 000 3344' ]] || fail 'central WhatsApp setting did not retain the ownership mutation'

# Reference-runtime setup may export ROSA_PREVIEW_* business overrides in the
# caller shell. A routine seed in this ownership test must exercise the normal
# non-overriding path rather than accidentally replacing the disposable values.
env \
  -u ROSA_PREVIEW_PHONE \
  -u ROSA_PREVIEW_EMAIL \
  -u ROSA_PREVIEW_ADDRESS \
  -u ROSA_PREVIEW_ADDRESS_AR \
  bash "$ROOT_DIR/wordpress/scripts/client-preview-seed.sh" >/dev/null
seed_output="$(bash "$ROOT_DIR/wordpress/scripts/elementor-authoring-seed.sh")"
[[ "$(grep -c '| skipped$' <<<"$seed_output")" -eq 6 ]] || fail 'normal Elementor reseed must skip all six migrated documents during ownership test'

home_value="$(wp eval '$option=get_option("rosa_home_content",[]); echo is_array($option)?(string)($option["en"]["hero_title"]??""):"";')"
[[ "$home_value" == 'ROLLBACK ONLY HOME TITLE' ]] || fail 'routine seed overwrote preserved Homepage rollback content'
site_value="$(wp eval '$option=get_option("rosa_site_content",[]); echo is_array($option)?(string)($option["en"]["cta_title"]??""):"";')"
[[ "$site_value" == 'OWNERSHIP TEST CTA' ]] || fail 'routine seed overwrote Site & CTA content'
shop_value="$(wp eval '$option=get_option("rosa_shop_content",[]); echo is_array($option)?(string)($option["en"]["hero_title"]??""):"";')"
[[ "$shop_value" == 'LIVE SHOP TITLE' ]] || fail 'routine seed overwrote Shop content'
media_after_seed="$(wp eval '$media=get_option("rosa_preview_media",[]); echo is_array($media)?(string)($media["home-hero-01"]??""):"";')"
[[ "$media_after_seed" == '777' ]] || fail 'routine seed overwrote preserved Rosa media mapping'

assert_public_ownership
assert_elementor_excludes_protected_values

restore
trap - EXIT

restored_options_b64="$(wp eval '
$names=["rosa_business_settings","rosa_home_content","rosa_site_content","rosa_shop_content","rosa_preview_media"];
$state=[];
foreach($names as $name){$value=get_option($name,null);$state[$name]=["exists"=>$value!==null,"value"=>$value];}
echo base64_encode(wp_json_encode($state, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
')"
[[ "$restored_options_b64" == "$option_state_b64" ]] || fail 'ownership test did not restore centralized option state exactly'
restored_product_b64="$(wp eval '
$post=get_page_by_path("rosa-foundation-stevens-scissors-regular", OBJECT, "product");
if(!$post) WP_CLI::error("Canonical Stevens fixture product disappeared after restore");
$product=wc_get_product((int)$post->ID);
if(!$product instanceof WC_Product) WP_CLI::error("Canonical Stevens fixture could not reload after restore");
$state=["id"=>$product->get_id(),"name"=>$product->get_name(),"description"=>$product->get_description()];
echo base64_encode(wp_json_encode($state, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
')"
[[ "$restored_product_b64" == "$product_state_b64" ]] || fail 'ownership test did not restore Woo product state exactly'

for route in / /about/ /contact/ /ar/contact/ /shop/ "/product/$fixture_slug/"; do
  html="$(curl -fsSL "$base_url$route")"
  for marker in 'OWNERSHIP TEST CTA' 'OWNERSHIP TEST STEVENS' 'ownership-test@example.invalid' 'Ownership Test Address' 'عنوان اختبار الملكية'; do
    ! grep -Fq "$marker" <<<"$html" || fail "Ownership fixture did not restore on $route: $marker"
  done
done

printf 'PASS: WooCommerce and centralized Rosa settings own public catalogue/business/shared values without leaking into Elementor JSON\n'
