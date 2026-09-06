#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
RUNTIME="$ROOT/wordpress/scripts/client-preview-runtime-verify.sh"
AUDIT="$ROOT/wordpress/scripts/live-visual-audit.mjs"
SYNC="$ROOT/wordpress/scripts/live-site-sync-check.mjs"
fail(){ printf 'FAIL: %s\n' "$1" >&2; exit 1; }

[[ -f "$RUNTIME" ]] || fail 'client preview runtime verifier missing'
[[ -f "$AUDIT" ]] || fail 'live visual audit tool missing'
[[ -f "$SYNC" ]] || fail 'live-site sync checker missing'

grep -Fq 'ROSA_LIVE_BASELINE_DIR' "$RUNTIME" || fail 'runtime verifier lacks frozen live-baseline mode'
grep -Fq 'live-visual-audit.mjs' "$RUNTIME" || fail 'strict runtime verifier does not consume the live visual baseline'
grep -Fq 'live-site-sync-check.mjs' "$RUNTIME" || fail 'runtime verifier lacks optional fresh-live drift detection'
grep -Fq 'ROSA_LIVE_REFERENCE_URL' "$RUNTIME" || fail 'runtime verifier lacks explicit fresh-live reference URL support'
! grep -Fq 'ROSA_REFERENCE_URL' "$RUNTIME" || fail 'runtime verifier still exposes obsolete pinned-target strict mode'

grep -Fq 'https://rosamedical.org/' "$SYNC" || fail 'live-site sync checker does not default to rosamedical.org'
grep -Fq 'TARGET_DRIFT' "$SYNC" || fail 'live-site sync checker does not classify production target drift'
grep -Fq 'baseline' "$SYNC" || fail 'live-site sync checker does not consume an immutable baseline'
grep -Fq 'settlePageMedia' "$AUDIT" || fail 'live visual audit does not settle lazy media'
grep -Fq '1920' "$AUDIT" || fail 'live visual audit omits 1920x1080 coverage'
grep -Fq '360' "$AUDIT" || fail 'live visual audit omits 360x800 coverage'

printf 'PASS: live Rosa frozen-baseline authority and periodic production-sync contract\n'
