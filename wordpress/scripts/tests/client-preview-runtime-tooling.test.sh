#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
RUNTIME="$ROOT/wordpress/scripts/client-preview-runtime-verify.sh"
CAPTURE="$ROOT/wordpress/scripts/client-preview-responsive-capture.sh"
VIDEO="$ROOT/wordpress/scripts/client-preview-video-capture.sh"
CAPTURE_HELPER="$ROOT/wordpress/scripts/client-preview-capture.mjs"
VIDEO_REVIEW="$ROOT/wordpress/scripts/client-preview-video-review.mjs"
PARITY_CAPTURE="$ROOT/wordpress/scripts/medicashop-elementor-parity-capture.mjs"
SECONDARY_FIDELITY="$ROOT/wordpress/scripts/tests/client-preview-secondary-fidelity.test.mjs"
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }
[[ -f "$RUNTIME" ]] || fail 'client preview runtime verifier missing'
[[ -f "$CAPTURE" ]] || fail 'client preview responsive capture missing'
[[ -f "$VIDEO" ]] || fail 'client preview video capture missing'
[[ -f "$CAPTURE_HELPER" ]] || fail 'client preview media-settling helper missing'
[[ -f "$VIDEO_REVIEW" ]] || fail 'client preview video review helper missing'
[[ -f "$PARITY_CAPTURE" ]] || fail 'finished-template parity capture missing'
[[ -f "$SECONDARY_FIDELITY" ]] || fail 'secondary-page finished-target fidelity test missing'
grep -Fq 'client-preview-seed.sh' "$RUNTIME" || fail 'runtime verifier does not seed client preview'
grep -Fq 'medicashop-elementor-reference-contract.test.sh' "$RUNTIME" || fail 'runtime verifier omits finished-template source authority contract'
grep -Fq 'medicashop-elementor-home-contract.test.php' "$RUNTIME" || fail 'runtime verifier omits finished-template Elementor topology contract'
grep -Fq 'medicashop-elementor-home-fidelity.test.mjs' "$RUNTIME" || fail 'runtime verifier omits finished-template browser geometry acceptance'
! grep -Fq 'latest-rosa-home-parity.test.mjs' "$RUNTIME" || fail 'runtime verifier still uses superseded latest-custom Home parity test'
! grep -Fq 'latest-rosa-home-reference-contract.test.sh' "$RUNTIME" || fail 'runtime verifier still uses superseded latest-custom source contract'
grep -Fq '390,844' "$CAPTURE" || fail '390x844 capture missing'
grep -Fq '2560,1440' "$CAPTURE" || fail '2560x1440 capture missing'
grep -Fq '/ar/' "$CAPTURE" || fail 'Arabic capture routes missing'
grep -Fq 'recordVideo' "$VIDEO" || fail 'Playwright video recording missing'
grep -Fq 'client-preview-artifacts' "$CAPTURE" || fail 'ignored artifact directory missing from capture tooling'
grep -Fq 'settlePageMedia' "$CAPTURE_HELPER" || fail 'capture helper does not settle below-fold media'
grep -Fq 'client-preview-capture.test.mjs' "$RUNTIME" || fail 'runtime verification omits capture behavior regression'
grep -Fq 'client-preview-accessibility.test.mjs' "$RUNTIME" || fail 'runtime verification omits browser accessibility acceptance'
grep -Fq 'client-preview-video-review.mjs' "$VIDEO" || fail 'video capture does not inspect the generated recording'
grep -Fq 'https://rosamedical.org/' "$PARITY_CAPTURE" || fail 'finished-template capture does not default to deployed visual reference'
grep -Fq 'artifacts/medicashop-elementor-parity' "$PARITY_CAPTURE" || fail 'finished-template capture output directory changed'
for route in '/about/' '/contact/' '/shop/' '/ar/about/' '/ar/contact/' '/ar/shop/'; do
  grep -Fq "$route" "$PARITY_CAPTURE" || fail "finished-template parity capture omits $route"
done
grep -Fq 'referenceBase' "$SECONDARY_FIDELITY" || fail 'secondary fidelity test does not accept a reference runtime'
grep -Fq 'currentBase' "$SECONDARY_FIDELITY" || fail 'secondary fidelity test does not accept a current runtime'
grep -Fq 'position: 2' "$SECONDARY_FIDELITY" || fail 'secondary fidelity position tolerance changed'
grep -Fq 'size: 2' "$SECONDARY_FIDELITY" || fail 'secondary fidelity size tolerance changed'
grep -Fq 'font: 0.6' "$SECONDARY_FIDELITY" || fail 'secondary fidelity font tolerance changed'
grep -Fq 'spacing: 1' "$SECONDARY_FIDELITY" || fail 'secondary fidelity spacing tolerance changed'
grep -Fq 'TEXT_DIFF:' "$SECONDARY_FIDELITY" || fail 'secondary fidelity test does not classify text/content differences separately'
! grep -Fq 'admin-post.php' "$SECONDARY_FIDELITY" || fail 'secondary fidelity test reintroduced a Contact submission backend requirement'
! grep -Eq 'printf .*\| grep -' "$RUNTIME" || fail 'runtime verifier uses grep -q pipelines that are unsafe under pipefail; use here-strings'
python3 - "$VIDEO" <<'PY'
import sys
text = open(sys.argv[1], encoding='utf-8').read()
shop = text.index("await visit(process.env.ROSA_VIDEO_SHOP);")
switch = text.index("await page.locator('.rosa-preview-header__actions .rosa-preview-language').click();")
try:
    home_again = text.index("await visit(process.env.ROSA_VIDEO_HOME);", shop + 1)
except ValueError:
    raise SystemExit('FAIL: video must return to English Home before demonstrating Home→Arabic language switching')
if not (shop < home_again < switch):
    raise SystemExit('FAIL: Home return must occur after Shop and before the Arabic language switch')
PY

# Verify assert_single_main correctly counts <main> tags in strict mode
env -i PATH="$PATH" bash -euo pipefail -c '
fail(){ printf "mock fail: %s\n" "$1" >&2; exit 1; }
'"$(sed -n '/^assert_single_main(){/,/^}/p' "$RUNTIME")"'
assert_single_main "Valid single main" "<!DOCTYPE html><html><body><main id=\"main\" class=\"rosa-site-main\"><p>content</p></main></body></html>"
' || fail 'assert_single_main failed to recognize single <main> tag'

printf 'PASS: client preview runtime tooling contract\n'