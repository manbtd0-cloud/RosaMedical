#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
RUNTIME="$ROOT/wordpress/scripts/client-preview-runtime-verify.sh"
CAPTURE="$ROOT/wordpress/scripts/client-preview-responsive-capture.sh"
VIDEO="$ROOT/wordpress/scripts/client-preview-video-capture.sh"
CAPTURE_HELPER="$ROOT/wordpress/scripts/client-preview-capture.mjs"
VIDEO_REVIEW="$ROOT/wordpress/scripts/client-preview-video-review.mjs"
PARITY_CAPTURE="$ROOT/wordpress/scripts/medicashop-elementor-parity-capture.mjs"
LIVE_AUDIT="$ROOT/wordpress/scripts/live-visual-audit.mjs"
LIVE_SYNC="$ROOT/wordpress/scripts/live-site-sync-check.mjs"
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }
[[ -f "$RUNTIME" ]] || fail 'client preview runtime verifier missing'
[[ -f "$CAPTURE" ]] || fail 'client preview responsive capture missing'
[[ -f "$VIDEO" ]] || fail 'client preview video capture missing'
[[ -f "$CAPTURE_HELPER" ]] || fail 'client preview media-settling helper missing'
[[ -f "$VIDEO_REVIEW" ]] || fail 'client preview video review helper missing'
[[ -f "$PARITY_CAPTURE" ]] || fail 'historical parity capture missing'
[[ -f "$LIVE_AUDIT" ]] || fail 'live visual audit tool missing'
[[ -f "$LIVE_SYNC" ]] || fail 'periodic live-site sync checker missing'

grep -Fq 'client-preview-seed.sh' "$RUNTIME" || fail 'client preview runtime verifier does not seed client preview'
grep -Fq 'ROSA_LIVE_BASELINE_DIR' "$RUNTIME" || fail 'runtime verifier does not expose optional frozen live-baseline mode'
grep -Fq 'ROSA_LIVE_REFERENCE_URL' "$RUNTIME" || fail 'runtime verifier does not expose optional fresh-live drift check'
grep -Fq 'live-visual-audit.mjs' "$RUNTIME" || fail 'runtime verifier omits frozen live-baseline visual acceptance'
grep -Fq 'live-site-sync-check.mjs' "$RUNTIME" || fail 'runtime verifier omits periodic live-site drift detection'
! grep -Fq 'ROSA_REFERENCE_URL' "$RUNTIME" || fail 'runtime verifier still uses obsolete pinned-target strict mode'
! grep -Fq 'client-preview-secondary-fidelity.test.mjs' "$RUNTIME" || fail 'runtime verifier still treats historical secondary comparator as final visual authority'
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

grep -Fq 'https://rosamedical.org/' "$PARITY_CAPTURE" || fail 'historical parity capture no longer points at deployed Rosa evidence'
grep -Fq 'settlePageMedia' "$PARITY_CAPTURE" || fail 'historical parity capture does not settle below-fold media'
grep -Fq 'settlePageMedia' "$LIVE_AUDIT" || fail 'live visual audit does not settle below-fold media'
grep -Fq 'TARGET_DRIFT' "$LIVE_SYNC" || fail 'live-site sync checker does not classify target drift'
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
