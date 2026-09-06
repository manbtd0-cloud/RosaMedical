#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
COMPOSE_FILE="$ROOT/wordpress/dev/compose.yaml"
ENV_FILE="$ROOT/wordpress/dev/.env"
compose=(docker compose -f "$COMPOSE_FILE")
if [[ -f "$ENV_FILE" ]]; then compose+=(--env-file "$ENV_FILE"); fi
wp(){ "${compose[@]}" run --rm wpcli "$@"; }
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }

bash "$ROOT/wordpress/scripts/foundation-bootstrap.sh" >/dev/null

wp eval '
$role=get_role("rosa_content_manager");
if(!$role) WP_CLI::error("Rosa Content Manager role missing");
foreach(["rosa_manage_content","edit_pages","upload_files","edit_products"] as $cap){
    if(empty($role->capabilities[$cap])) WP_CLI::error("Missing capability: {$cap}");
}
foreach(["manage_options","install_plugins","edit_plugins","update_plugins","switch_themes","edit_themes","edit_theme_options","unfiltered_html"] as $cap){
    if(!empty($role->capabilities[$cap])) WP_CLI::error("Forbidden capability: {$cap}");
}
foreach(["rosa_business","rosa_media","rosa_content_site","rosa_content_shop"] as $group){
    $resolved=apply_filters("option_page_capability_{$group}","manage_options");
    if($resolved!=="rosa_manage_content") WP_CLI::error("Wrong settings capability for {$group}: {$resolved}");
}
WP_CLI::success("Rosa content-manager role and settings capability filters verified");
'

username="rosa_capability_test_$(date +%s)_$RANDOM"
email="${username}@example.invalid"
user_id="$(wp user create "$username" "$email" --role=rosa_content_manager --porcelain)"
[[ "$user_id" =~ ^[0-9]+$ ]] || fail 'Could not create disposable Rosa Content Manager user'
cleanup(){ wp user delete "$user_id" --yes >/dev/null 2>&1 || true; }
trap cleanup EXIT

wp --user="$user_id" eval '
foreach(["rosa_manage_content","edit_pages","upload_files","edit_products"] as $cap){
    if(!current_user_can($cap)) WP_CLI::error("Disposable content manager cannot: {$cap}");
}
$home=get_page_by_path("home",OBJECT,"page");
if(!$home) WP_CLI::error("Home page missing for edit_post capability check");
if(!current_user_can("edit_post",(int)$home->ID)) WP_CLI::error("Disposable content manager cannot edit the Home Elementor page");
foreach(["manage_options","install_plugins","edit_plugins","update_plugins","switch_themes","edit_themes","edit_theme_options","unfiltered_html"] as $cap){
    if(current_user_can($cap)) WP_CLI::error("Disposable content manager unexpectedly can: {$cap}");
}
WP_CLI::success("Disposable Rosa Content Manager effective permissions verified");
'

cleanup
trap - EXIT
printf 'PASS: Rosa content-manager runtime permission boundary\n'
