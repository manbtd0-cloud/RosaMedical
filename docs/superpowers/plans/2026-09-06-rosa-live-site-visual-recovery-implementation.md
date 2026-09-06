# Rosa Medical Live-Site Visual Recovery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Preserve the already-proven Elementor/WooCommerce/settings/permissions architecture while reconstructing the public Rosa Medical WordPress site so localhost demonstrably matches the frozen approved `rosamedical.org` live baseline across EN/AR desktop, tablet and mobile.

**Architecture:** The frozen 2026-09-06 browser audit of `https://rosamedical.org/` is the primary visual authority. Elementor remains the authoring surface for Home/About/Contact EN+AR, WooCommerce remains catalogue truth, centralized Rosa settings remain shared-business truth, and the child theme/plugin render the approved live topology. Historical `d0726eed...` contracts are demoted to implementation evidence only and may not override live evidence.

**Tech Stack:** WordPress/PHP 8+, Hello Elementor, Elementor Free, WooCommerce, Docker Compose, Bash/WP-CLI, vanilla CSS/JS, Playwright via `apps/web`, ImageMagick/Pillow-compatible image tooling where available.

**Spec:** `docs/superpowers/specs/2026-09-06-rosa-live-site-visual-recovery-design.md`

## Global Constraints

- Work only on `wordpress/client-content-controls`; do not merge or delete it during this plan.
- Plan baseline commit: `5b3306d3494f9a0d2f9829ff794b5874880baee1`.
- **Primary visual authority:** frozen browser evidence from the approved 2026-09-06 `rosamedical.org` audit.
- **Periodic live sync rule:** re-capture and compare the current `https://rosamedical.org/` at the start, after Home, after About, after Contact, after Shop, after Product Detail, and before final acceptance. A current-live change is reported as target drift; it must never silently replace the frozen baseline.
- If fresh production differs materially from the frozen baseline, stop the affected page, classify the change, and ask for explicit target-refresh approval before adopting it.
- Historical branch `wordpress/client-preview-medicashop-recreation` / `d0726eed34b4fc14267570853ade8b74df49ae9e` is implementation evidence only.
- `apps/web/**` is not visual authority.
- Do not redesign Rosa Medical.
- Preserve Elementor Free authoring for EN/AR Home, About and Contact.
- Preserve WooCommerce ownership of products, families/categories, media, SKUs, configurations, descriptions and publish state.
- Preserve centralized Rosa ownership of phone, email, EN/AR address, WhatsApp and shared Site/CTA values.
- Protected Woo/shared values must remain outside all six Elementor JSON documents.
- Keep `rosa_content_manager` / `rosa_manage_content` permission boundary intact.
- No Elementor Pro or proprietary MedicaShop runtime dependency.
- Contact stays presentation/mailto-oriented; do not add a server-side submission backend.
- Routine seeds must not overwrite edited Elementor documents; `--force` is not routine.
- No Hostinger/production mutation is authorized.
- Required viewports: `1920x1080`, `1440x900`, `1280x800`, `1024x768`, `768x1024`, `431x932`, `390x844`, `360x800`.
- A page is not frozen until full-page screenshots have been manually reviewed at `1440x900`, `1024x768`, and `390x844`.
- No CRITICAL/HIGH visual difference may remain. MEDIUM differences require repair, proof of nondeterminism, or explicit user acceptance.

---

## File Map

### New/rewritten visual-authority tooling

- Create: `wordpress/scripts/live-visual-audit.mjs` — deterministic live/local route+viewport capture, measurements, screenshots, overlays/diffs metadata.
- Create: `wordpress/scripts/live-site-sync-check.mjs` — compare current live against the frozen baseline and report target drift without changing the target.
- Create: `wordpress/scripts/tests/live-visual-authority-contract.test.sh` — source contract for corrected authority and sync checkpoints.
- Modify: `wordpress/scripts/tests/client-preview-runtime-tooling.test.sh` — remove old `ROSA_REFERENCE_URL`/pinned-target strict requirement; require corrected live-baseline mode.
- Modify: `wordpress/scripts/client-preview-runtime-verify.sh` — optional strict frozen-baseline verification only when explicitly configured.
- Demote/rewrite as needed: `wordpress/scripts/tests/client-preview-secondary-fidelity.test.mjs`.
- Demote/rewrite as needed: `wordpress/scripts/tests/medicashop-elementor-home-fidelity.test.mjs`.
- Modify: `wordpress/scripts/client-preview-capture.mjs` only if shared settling/measurement helpers are needed.

### Marketing-page rendering

- `wordpress/wp-content/plugins/rosa-medical-core/src/Elementor/Widgets/HomeWidgets.php`
- `wordpress/wp-content/plugins/rosa-medical-core/src/Elementor/Widgets/AboutWidgets.php`
- `wordpress/wp-content/plugins/rosa-medical-core/src/Elementor/Widgets/ContactWidgets.php`
- `wordpress/wp-content/plugins/rosa-medical-core/src/Elementor/ElementorSeedData.php`
- `wordpress/wp-content/plugins/rosa-medical-core/src/Elementor/ElementorPageSeeder.php`
- `wordpress/wp-content/themes/rosa-medical-child/template-parts/client-preview/*.php`
- `wordpress/wp-content/themes/rosa-medical-child/assets/css/client-preview.css`
- `wordpress/wp-content/themes/rosa-medical-child/assets/css/client-preview-rtl.css`
- `wordpress/wp-content/themes/rosa-medical-child/assets/css/elementor-authoring.css`

### Shop/Product/shared shell

- `wordpress/wp-content/themes/rosa-medical-child/woocommerce/archive-product.php`
- `wordpress/wp-content/themes/rosa-medical-child/page-templates/client-preview-shop.php`
- `wordpress/wp-content/themes/rosa-medical-child/template-parts/client-preview/product-card.php`
- `wordpress/wp-content/plugins/rosa-medical-core/templates/product-detail-prototype.php`
- `wordpress/wp-content/themes/rosa-medical-child/header.php`
- `wordpress/wp-content/themes/rosa-medical-child/footer.php`
- `wordpress/wp-content/themes/rosa-medical-child/template-parts/client-preview/cta-banner.php`
- `wordpress/wp-content/themes/rosa-medical-child/assets/js/client-preview.js`

### Architecture regressions that must stay green

- `wordpress/scripts/tests/elementor-authoring-mutation.test.sh`
- `wordpress/scripts/tests/client-preview-content-zero-drift.test.sh`
- `wordpress/scripts/tests/client-preview-content-mutation.test.sh`
- `wordpress/scripts/tests/business-settings.test.php`
- `wordpress/scripts/tests/client-preview-admin-contract.test.sh`
- `wordpress/scripts/tests/client-preview-content-manager-runtime.test.sh`
- `wordpress/scripts/tests/elementor-authoring-editor-links.test.sh`
- `wordpress/scripts/foundation-product-verify.sh`
- `wordpress/scripts/tests/client-preview-accessibility.test.mjs`

---

### Task 0: Freeze Current State and Import the Live Audit as Evidence

**Files:**
- Read only: repo state and `artifacts/live-vs-local-audit-2026-09-06/` if present.
- Create compact metadata only: `docs/superpowers/reports/2026-09-06-live-visual-baseline-manifest.md`.

**Interfaces:**
- Consumes: current checkout and the CLI-produced live/local audit.
- Produces: immutable baseline identity used by all later tasks.

- [ ] **Step 1: Record state**

```bash
mkdir -p artifacts/live-visual-recovery/preflight
{
  printf 'HEAD='; git rev-parse HEAD
  printf 'BRANCH='; git branch --show-current
  printf '\nSTATUS\n'; git status --short
  printf '\nREMOTE\n'; git ls-remote origin refs/heads/wordpress/client-content-controls
} | tee artifacts/live-visual-recovery/preflight/repository-state.txt
```

Expected: no reset/stash/rebase.

- [ ] **Step 2: Verify the audit exists**

```bash
test -f artifacts/live-vs-local-audit-2026-09-06/manifest.json
test -f artifacts/live-vs-local-audit-2026-09-06/report/LIVE-VS-LOCAL-VISUAL-AUDIT.md
find artifacts/live-vs-local-audit-2026-09-06/screenshots -name live.png | wc -l
find artifacts/live-vs-local-audit-2026-09-06/screenshots -name local.png | wc -l
```

If the audit directory is missing, restore/extract the previously generated ZIP to that exact ignored artifact path. Do not regenerate a different target silently.

- [ ] **Step 3: Write compact committed baseline metadata**

The report must record: audit timestamp, live base URL, branch/commit audited, browser, exact viewport list, route list, SHA-256 of `manifest.json`, and the absolute/relative ignored artifact location. Do not commit PNGs.

- [ ] **Step 4: Commit metadata**

```bash
git add docs/superpowers/reports/2026-09-06-live-visual-baseline-manifest.md
git commit -m "docs(wordpress): freeze live visual baseline identity"
```

---

### Task 1: Replace the Wrong Pinned-Target Strict Contract with Live-Baseline Authority

**Files:**
- Create: `wordpress/scripts/tests/live-visual-authority-contract.test.sh`
- Modify: `wordpress/scripts/tests/client-preview-runtime-tooling.test.sh`
- Later modify after RED: `wordpress/scripts/client-preview-runtime-verify.sh`

**Interfaces:**
- Produces strict environment variables:
  - `ROSA_LIVE_BASELINE_DIR` — frozen audit directory.
  - `ROSA_LIVE_REFERENCE_URL` — optional fresh-live drift check, normally `https://rosamedical.org/`.

- [ ] **Step 1: Write the RED contract**

Assert all of the following:

```bash
grep -Fq 'ROSA_LIVE_BASELINE_DIR' "$RUNTIME" || fail 'runtime verifier lacks frozen live-baseline mode'
! grep -Fq 'ROSA_REFERENCE_URL' "$RUNTIME" || fail 'runtime verifier still exposes obsolete pinned-target strict mode'
grep -Fq 'live-visual-audit.mjs' "$RUNTIME" || fail 'strict runtime verifier does not consume live visual baseline'
grep -Fq 'live-site-sync-check.mjs' "$RUNTIME" || fail 'runtime verifier lacks optional fresh-live drift check'
```

Also remove source assertions that treat `medicashop-elementor-*` or `client-preview-secondary-fidelity.test.mjs` as final visual authority.

- [ ] **Step 2: Observe RED**

```bash
bash wordpress/scripts/tests/live-visual-authority-contract.test.sh
bash wordpress/scripts/tests/client-preview-runtime-tooling.test.sh
```

Expected: fail because corrected live-baseline tooling does not yet exist.

- [ ] **Step 3: Do not change page code**

This task changes only verification authority.

---

### Task 2: Build Deterministic Live/Local Capture and Periodic Live-Sync Tooling

**Files:**
- Create: `wordpress/scripts/live-visual-audit.mjs`
- Create: `wordpress/scripts/live-site-sync-check.mjs`
- Modify: `wordpress/scripts/client-preview-capture.mjs` only for reusable settling helpers.
- Modify: `wordpress/scripts/client-preview-runtime-verify.sh`
- Test: `wordpress/scripts/tests/live-visual-authority-contract.test.sh`

**Interfaces:**

`live-visual-audit.mjs`:

```text
node wordpress/scripts/live-visual-audit.mjs \
  --reference https://rosamedical.org/ \
  --current http://localhost:8088/ \
  --out artifacts/live-visual-recovery/<checkpoint>
```

`live-site-sync-check.mjs`:

```text
node wordpress/scripts/live-site-sync-check.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --live https://rosamedical.org/ \
  --out artifacts/live-visual-recovery/<checkpoint>-live-sync
```

- [ ] **Step 1: Implement exact route matrix**

```js
const routes = [
  ['home-en', '/'], ['home-ar', '/ar/'],
  ['about-en', '/about/'], ['about-ar', '/ar/about/'],
  ['contact-en', '/contact/'], ['contact-ar', '/ar/contact/'],
  ['shop-en', '/shop/'], ['shop-ar', '/ar/shop/'],
];
```

Add Product Detail only when a live equivalent URL is discovered and recorded; never invent one.

- [ ] **Step 2: Implement exact viewport matrix**

```js
const viewports = [
  { width: 1920, height: 1080 },
  { width: 1440, height: 900 },
  { width: 1280, height: 800 },
  { width: 1024, height: 768 },
  { width: 768, height: 1024 },
  { width: 431, height: 932 },
  { width: 390, height: 844 },
  { width: 360, height: 800 },
];
```

- [ ] **Step 3: Settle pages deterministically**

For every capture: `deviceScaleFactor:1`, `reducedMotion:'reduce'`, wait for `load`, `document.fonts.ready`, run existing `settlePageMedia`, scroll full page once, return to top, wait 150ms, collect console/page/network failures, capture full-page PNG.

- [ ] **Step 4: Capture geometry/style evidence**

For major `[data-*]` sections plus header/prefooter/footer, record `x/y/width/height`, display, grid columns, gap, physical padding/margins, font family/size/weight/line-height, color/background, radius, shadow, `objectFit`, `objectPosition`, document `scrollWidth/clientWidth/scrollHeight`.

- [ ] **Step 5: Generate side-by-side/overlay/diff metadata**

If image libraries are available, produce PNGs. If not, still produce live/local full screenshots and a JSON manifest that records source paths and dimensions; never skip the screenshots.

- [ ] **Step 6: Implement fresh-live drift detection**

`live-site-sync-check.mjs` compares fresh production against the frozen live screenshots/metrics. Flag a route/viewport as `TARGET_DRIFT` if any of these occur after normalizing known dynamic year text:

```text
major-section count/order changes
full-page height changes by > 8px
key section x/y/width/height changes by > 4px
font size changes by > 1px
changed-pixel ratio > 3% above an anti-aliasing threshold
```

A drift result must exit non-zero and print: route, viewport, reason, frozen evidence path, fresh evidence path. It must never overwrite baseline files.

- [ ] **Step 7: Wire strict verifier**

In `client-preview-runtime-verify.sh`:

```bash
if [[ -n "${ROSA_LIVE_BASELINE_DIR:-}" ]]; then
  run node wordpress/scripts/live-visual-audit.mjs \
    --baseline "$ROSA_LIVE_BASELINE_DIR" \
    --current "$home_url" \
    --out "${ROSA_LIVE_AUDIT_OUT:-artifacts/live-visual-recovery/strict-runtime}"
fi
if [[ -n "${ROSA_LIVE_REFERENCE_URL:-}" && -n "${ROSA_LIVE_BASELINE_DIR:-}" ]]; then
  run node wordpress/scripts/live-site-sync-check.mjs \
    --baseline "$ROSA_LIVE_BASELINE_DIR" \
    --live "$ROSA_LIVE_REFERENCE_URL" \
    --out "${ROSA_LIVE_SYNC_OUT:-artifacts/live-visual-recovery/strict-live-sync}"
fi
```

Normal verifier stays offline-friendly.

- [ ] **Step 8: Verify GREEN**

```bash
bash wordpress/scripts/tests/live-visual-authority-contract.test.sh
bash wordpress/scripts/tests/client-preview-runtime-tooling.test.sh
node --check wordpress/scripts/live-visual-audit.mjs
node --check wordpress/scripts/live-site-sync-check.mjs
```

- [ ] **Step 9: Commit**

```bash
git add wordpress/scripts
git commit -m "test(wordpress): make live Rosa baseline visual authority"
```

---

### Task 3: Establish the Initial Recovery Scorecard

**Files:**
- Create: `docs/superpowers/reports/2026-09-06-live-visual-recovery-scorecard.md`
- No production code changes.

- [ ] **Step 1: Run baseline-vs-local capture**

```bash
node wordpress/scripts/live-visual-audit.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --current http://localhost:8088/ \
  --out artifacts/live-visual-recovery/00-initial
```

- [ ] **Step 2: Run the first periodic live sync**

```bash
node wordpress/scripts/live-site-sync-check.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --live https://rosamedical.org/ \
  --out artifacts/live-visual-recovery/00-initial-live-sync
```

If `TARGET_DRIFT` appears, stop the affected route and report it before page implementation.

- [ ] **Step 3: Manually inspect 1440/1024/390 pairs**

Classify Home/About/Contact/Shop EN+AR as `MATCH`, `CLOSE`, `NOTICEABLE DRIFT`, `MAJOR DRIFT`, or `NOT COMPARABLE`; record CRITICAL/HIGH/MEDIUM root causes.

- [ ] **Step 4: Commit scorecard**

```bash
git add docs/superpowers/reports/2026-09-06-live-visual-recovery-scorecard.md
git commit -m "docs(wordpress): record live visual recovery scorecard"
```

---

### Task 4: Recover Home EN/AR Against the Frozen Live Baseline

**Files:**
- Test/update: `wordpress/scripts/tests/medicashop-elementor-home-fidelity.test.mjs` — rename assertions/messages to live authority or replace with `live-home-fidelity.test.mjs`.
- Modify only as evidence requires: `HomeWidgets.php`, `ElementorSeedData.php`, Home template parts, `client-preview.css`, `client-preview-rtl.css`.

- [ ] **Step 1: Inventory frozen live Home topology**

Write the exact live section order and required media from the audit into the focused test. Do not preserve the historical nine-section list if frozen live evidence differs.

- [ ] **Step 2: Observe RED on localhost**

```bash
node wordpress/scripts/tests/live-home-fidelity.test.mjs http://localhost:8088/
```

Failure must identify the first real live mismatch: missing/extra section, geometry, media, or responsive state.

- [ ] **Step 3: Repair one root cause at a time**

Prefer widget -> existing/new focused template part -> CSS. Keep Woo product sections dynamic. If topology migration is needed, use `ElementorPageSeeder` safe-state logic; edited documents must not be force-overwritten.

- [ ] **Step 4: Rerun architecture regressions**

```bash
bash wordpress/scripts/tests/elementor-authoring-mutation.test.sh
bash wordpress/scripts/tests/client-preview-content-zero-drift.test.sh
bash wordpress/scripts/tests/client-preview-content-mutation.test.sh
```

- [ ] **Step 5: Capture and manually review**

```bash
node wordpress/scripts/live-visual-audit.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --current http://localhost:8088/ \
  --routes home-en,home-ar \
  --out artifacts/live-visual-recovery/10-home
```

Review 1440, 1024, 390. No CRITICAL/HIGH differences remain.

- [ ] **Step 6: Periodic live sync after Home**

```bash
node wordpress/scripts/live-site-sync-check.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --live https://rosamedical.org/ \
  --routes home-en,home-ar \
  --out artifacts/live-visual-recovery/10-home-live-sync
```

- [ ] **Step 7: Commit Home recovery**

Commit only Home-focused production/tests with a message such as `fix(wordpress): restore live Rosa Home fidelity`.

---

### Task 5: Recover About EN/AR

**Files:**
- Rewrite focused About assertions in `elementor-authoring-about-contact.test.mjs` or split to `live-about-fidelity.test.mjs`.
- Modify: `AboutWidgets.php`, `ElementorSeedData.php`, About template parts, CSS/RTL only as measured.

- [ ] **Step 1: Replace historical About topology assumptions with frozen live topology**
- [ ] **Step 2: Observe focused RED**
- [ ] **Step 3: Repair markup/widget/media/CSS by root cause**
- [ ] **Step 4: Run six-page edit-persistence and zero-drift gates**
- [ ] **Step 5: Capture and manually review About EN/AR at 1440/1024/390**
- [ ] **Step 6: Periodic live sync after About using `live-site-sync-check.mjs --routes about-en,about-ar`**
- [ ] **Step 7: Commit `fix(wordpress): restore live Rosa About fidelity`**

Acceptance: no CRITICAL/HIGH differences; Elementor edits remain independent EN/AR and routine seeds skip edited documents.

---

### Task 6: Recover Contact EN/AR

**Files:**
- Test: split/create `wordpress/scripts/tests/live-contact-fidelity.test.mjs`.
- Modify: `ContactWidgets.php`, `contact-layout.php`, page-hero/contact-related partials, CSS/RTL.
- Remove `contact-map.php` from public topology only if frozen live evidence proves that the separate map block is absent; keep centralized address ownership.

- [ ] **Step 1: Encode the live two-card/contact composition in a failing test**

Require the live hero treatment, intro/contact-information hierarchy, numbered contact details, message-form presentation, correct card count/order, and no extra local-only major map block when absent on frozen live.

- [ ] **Step 2: Observe RED**

```bash
node wordpress/scripts/tests/live-contact-fidelity.test.mjs http://localhost:8088/
```

- [ ] **Step 3: Reconstruct live Contact without adding a backend**

Form controls remain presentation-only; action must be absent or mailto-driven as approved. Phone/email/address remain dynamic centralized values.

- [ ] **Step 4: Run ownership regression**

```bash
bash wordpress/scripts/tests/client-preview-content-mutation.test.sh
php wordpress/scripts/tests/business-settings.test.php
```

- [ ] **Step 5: Capture/manual review Contact EN/AR 1440/1024/390**
- [ ] **Step 6: Periodic live sync after Contact**
- [ ] **Step 7: Commit `fix(wordpress): restore live Rosa Contact fidelity`**

---

### Task 7: Recover Shop EN/AR and Correct Fixture Population

**Files:**
- Test: create `wordpress/scripts/tests/live-shop-fidelity.test.mjs`.
- Modify: `woocommerce/archive-product.php`, `page-templates/client-preview-shop.php`, `product-card.php`, relevant shared shop partials, CSS.
- Modify seed/fixtures only if evidence shows local data population—not renderer logic—is the reason only one product is visible.

- [ ] **Step 1: Encode live Shop topology**

Require: live Find Product/search hero composition, sorting/control placement, catalogue grid density, workflow section, procurement-support section, family strip, shared CTA/footer relationship.

- [ ] **Step 2: Diagnose one-product local state before coding**

Use WP-CLI:

```bash
wp post list --post_type=product --post_status=publish --fields=ID,post_title,post_name --format=table
wp term list product_cat --fields=term_id,name,slug,count --format=table
```

Classify as fixture population vs query/rendering. Do not hard-code fake cards.

- [ ] **Step 3: Observe RED**
- [ ] **Step 4: Implement live archive composition with Woo-driven data**
- [ ] **Step 5: Verify search and sort controls function without ecommerce checkout**
- [ ] **Step 6: Run Woo/settings ownership test and product fixture verifier**

```bash
bash wordpress/scripts/tests/client-preview-content-mutation.test.sh
bash wordpress/scripts/foundation-product-verify.sh
```

- [ ] **Step 7: Capture/manual review Shop EN/AR 1440/1024/390**
- [ ] **Step 8: Periodic live sync after Shop**
- [ ] **Step 9: Commit `fix(wordpress): restore live Rosa Shop fidelity`**

---

### Task 8: Recover Product Detail and Supported Family/Category Surfaces

**Files:**
- Test: modify `product-detail-structure.test.sh`; create `live-product-detail-fidelity.test.mjs`.
- Modify: `product-detail-prototype.php`, product/family template parts, CSS.
- Category surface: use actual discovered Woo route; do not invent one.

- [ ] **Step 1: Discover live and local representative URLs**

Local canonical product remains `rosa-foundation-stevens-scissors-regular`. Discover live equivalent through actual live links. If no live equivalent exists, record `NOT COMPARABLE` and use only the frozen live product evidence already captured; do not fabricate production data.

- [ ] **Step 2: Encode required live Product Detail composition**

Require one coherent body and one shell; breadcrumb/category context, gallery/thumbs, title/reference/price-request block, numbered support panel, description/configuration region, related products, CTA/footer.

- [ ] **Step 3: Add explicit duplicate-shell guard**

```js
assert.equal(await page.locator('[data-rosa-preview-footer]').count(), 1);
assert.equal(await page.locator('main').count(), 1);
assert.equal(await page.locator('.rosa-product-detail').count(), 1);
```

- [ ] **Step 4: Observe RED and repair dynamic Woo renderer**

Keep product name, description, SKUs `04-0901`/`04-0911`, directions/configurations and media Woo-driven.

- [ ] **Step 5: Run product/ownership regressions**
- [ ] **Step 6: Capture/manual review Product Detail 1440/1024/390**
- [ ] **Step 7: Periodic live sync after Product Detail**
- [ ] **Step 8: Commit `fix(wordpress): restore live Rosa product fidelity`**

---

### Task 9: Shared Shell and Interaction Sweep

**Files:**
- Test: extend `client-preview-accessibility.test.mjs` or create `live-shared-shell-fidelity.test.mjs`.
- Modify only globally: `header.php`, `footer.php`, `cta-banner.php`, `client-preview.css`, `client-preview-rtl.css`, `client-preview.js`.

- [ ] **Step 1: Compare announcement/header/prefooter/footer across all routes**
- [ ] **Step 2: Write focused RED for any shared mismatch**
- [ ] **Step 3: Repair globally, never page-by-page**
- [ ] **Step 4: Verify desktop nav, mobile menu open/close/Escape/focus trap, language switch, inquiry links**
- [ ] **Step 5: Run accessibility test on EN/AR representative routes**
- [ ] **Step 6: Capture all routes at 1440/1024/390 and verify shell remains consistent**
- [ ] **Step 7: Commit `fix(wordpress): align shared shell with live Rosa`**

---

### Task 10: RTL and Full Responsive Matrix Sweep

**Files:**
- Test: create/extend live RTL/responsive tests.
- Modify: `client-preview-rtl.css` and page-specific CSS only where direct measured evidence requires.

- [ ] **Step 1: Run every required route at all eight viewports**
- [ ] **Step 2: Assert no horizontal overflow**
- [ ] **Step 3: Verify Arabic logical spacing/order/alignment; LTR isolate phone/email/SKU**
- [ ] **Step 4: Verify mobile/tablet topology rather than merely `direction:rtl`**
- [ ] **Step 5: Capture full matrix and resolve all CRITICAL/HIGH/MEDIUM differences**
- [ ] **Step 6: Commit `fix(wordpress): complete live responsive and RTL parity`**

---

### Task 11: Final Full Live-vs-Local Audit and Periodic Production Sync

**Files:**
- Update: recovery scorecard/report.
- No production code unless the audit reopens a concrete RED defect.

- [ ] **Step 1: Run full frozen-baseline audit**

```bash
node wordpress/scripts/live-visual-audit.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --current http://localhost:8088/ \
  --out artifacts/live-visual-recovery/90-final
```

- [ ] **Step 2: Run mandatory fresh-live sync**

```bash
node wordpress/scripts/live-site-sync-check.mjs \
  --baseline artifacts/live-vs-local-audit-2026-09-06 \
  --live https://rosamedical.org/ \
  --out artifacts/live-visual-recovery/90-final-live-sync
```

If production drift exists, do not silently recapture/replace the baseline. Report exact routes/viewports and wait for target-refresh approval.

- [ ] **Step 3: Human review every page at 1440/1024/390**

Final verdict must be `VISUALLY EQUIVALENT` or `MINOR DIFFERENCES ONLY`; otherwise reopen the relevant page task.

- [ ] **Step 4: Run architecture regressions**

```bash
bash wordpress/scripts/tests/elementor-authoring-mutation.test.sh
bash wordpress/scripts/tests/client-preview-content-zero-drift.test.sh
bash wordpress/scripts/tests/client-preview-content-mutation.test.sh
php wordpress/scripts/tests/business-settings.test.php
bash wordpress/scripts/tests/client-preview-admin-contract.test.sh
bash wordpress/scripts/tests/client-preview-content-manager-runtime.test.sh
bash wordpress/scripts/tests/elementor-authoring-editor-links.test.sh
bash wordpress/scripts/foundation-product-verify.sh
```

- [ ] **Step 5: Run normal master verifier**

```bash
bash wordpress/scripts/client-preview-runtime-verify.sh
```

- [ ] **Step 6: Run strict frozen-live verifier**

```bash
ROSA_LIVE_BASELINE_DIR="$PWD/artifacts/live-vs-local-audit-2026-09-06" \
ROSA_LIVE_REFERENCE_URL="https://rosamedical.org/" \
bash wordpress/scripts/client-preview-runtime-verify.sh
```

---

### Task 12: Proven-Dead Historical Parity Cleanup and Final Runbook

**Files:**
- Remove only files proven unused and superseded by corrected live authority.
- Modify final WordPress runbook/documentation.

- [ ] **Step 1: Search all references before deletion**

```bash
git grep -nE 'latest-rosa-home|d0726eed|ROSA_REFERENCE_URL|client-preview-secondary-fidelity|medicashop-elementor-home-fidelity'
```

Classify each occurrence as runtime, active test, historical documentation, or negative guard.

- [ ] **Step 2: Delete only active runtime/test artifacts whose sole purpose is the wrong visual authority**

Historical docs may remain clearly marked superseded. Negative guards may remain.

- [ ] **Step 3: Document final editing ownership**

```text
Pages -> Home/About/Contact -> Elementor
Rosa Medical -> Site & CTA / Business
WooCommerce -> Products / Categories / Attributes / Variations
Theme/plugin -> protected layout, shell, responsive, RTL, Woo templates
```

- [ ] **Step 4: Run full normal + strict verification again**
- [ ] **Step 5: Record final git state**

```bash
git status --short
git log --oneline -15
```

- [ ] **Step 6: STOP before production**

No Hostinger/database/filesystem/live WordPress deployment occurs without separate explicit approval.

---

## Periodic Live-Sync Checkpoints — Non-Negotiable

The user explicitly requires repeated production comparison during implementation. Run `live-site-sync-check.mjs` at these checkpoints:

1. before any page recovery;
2. immediately after Home freeze;
3. immediately after About freeze;
4. immediately after Contact freeze;
5. immediately after Shop freeze;
6. immediately after Product Detail freeze;
7. immediately before final acceptance.

If the fresh live site changed from the frozen baseline:

```text
DO NOT copy the new production appearance automatically.
DO NOT update frozen screenshots automatically.
DO NOT weaken tests to make both targets pass.

Instead:
1. save fresh-live evidence;
2. classify TARGET_DRIFT;
3. identify affected route/viewports;
4. report the difference;
5. wait for explicit target-refresh approval.
```

This keeps the project synchronized with reality while preventing a moving production website from silently redefining the implementation target mid-task.

## Completion Gate

Local visual recovery is complete only when all of the following are true:

- Home EN/AR accepted against frozen live evidence.
- About EN/AR accepted against frozen live evidence.
- Contact EN/AR accepted against frozen live evidence.
- Shop EN/AR accepted against frozen live evidence.
- Representative Product Detail/category surfaces accepted where comparable.
- Shared shell and interactions accepted.
- All eight responsive viewports and RTL sweep accepted.
- No CRITICAL/HIGH differences remain and no unresolved MEDIUM difference remains.
- Elementor edit persistence remains green.
- Woo/settings ownership remains green.
- Rosa Content Manager permission boundary remains green.
- Final fresh-live sync reports no unapproved production target drift.
- Normal and strict master verifiers pass.
- Production deployment has not been attempted.
