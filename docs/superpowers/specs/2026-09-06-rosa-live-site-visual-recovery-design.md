# Rosa Medical Live-Site Visual Recovery Design

**Date:** 2026-09-06  
**Status:** Corrective architecture approved in chat; written-spec review pending  
**Working branch:** `wordpress/client-content-controls`  
**Branch baseline before this spec:** `dc8e056b9bfdb495fcd9661ccd6aa493e0bd0e5a`  
**Supersedes for visual authority:** `docs/superpowers/specs/2026-09-05-rosa-wordpress-exact-elementor-parity-master-design.md`  
**Preserves from prior work:** Elementor/WooCommerce/settings/permissions/edit-preservation architecture already verified locally.

## 1. Why this corrective design exists

The prior conversion process treated the pinned repository implementation `wordpress/client-preview-medicashop-recreation` at `d0726eed34b4fc14267570853ade8b74df49ae9e` as the primary visual authority and used `https://rosamedical.org/` only as secondary confirmation.

A direct live-versus-local screenshot audit on 2026-09-06 disproved the assumption that matching that pinned implementation was sufficient to claim parity with the live Rosa Medical website.

The audit showed major page-body differences, including:

- Shop: live composition contains a materially different hero/search/catalogue/workflow/support structure than the current local Shop;
- Contact: live composition contains a materially different hero, contact-card/form layout and visual treatment than the current local Contact;
- Product Detail: live composition contains a materially different product-detail hierarchy and the local render demonstrates duplicated/misordered content around the shared shell;
- shared shell elements are substantially closer than page bodies but still require direct live review rather than assumption;
- existing selector/geometry contracts can pass while the human-visible page composition is materially wrong.

Therefore the project is not in final parity/cleanup. The visual acceptance model must be corrected before further implementation.

## 2. Objective

Preserve the now-proven WordPress authoring and data-ownership architecture while reconstructing every public page that differs so the local WordPress site demonstrably matches the actual Rosa Medical live design.

The target is not a redesign and not an interpretation of MedicaShop.

The target is the Rosa Medical design actually rendered by the approved live website.

The completed local system must satisfy both:

1. **Live visual fidelity:** at matched route, locale, content state and viewport, localhost must visually match the frozen approved `rosamedical.org` baseline closely enough to survive direct side-by-side human review; and
2. **Authoring/data correctness:** Elementor, WooCommerce and centralized Rosa settings must continue to own the content domains already proven by the existing mutation and permission tests.

## 3. Corrected source-of-truth hierarchy

When visual evidence conflicts, use this order:

1. **Frozen browser captures and browser measurements of the approved `https://rosamedical.org/` baseline from the live-site audit.**
2. A fresh browser capture of `https://rosamedical.org/` only when the frozen baseline is incomplete, corrupt, or the user explicitly states that production changed and the baseline must be refreshed.
3. Current live DOM/computed-style inspection used to explain the frozen visual target.
4. Current `wordpress/client-content-controls` implementation as an implementation substrate and authoring/data architecture.
5. Historical Rosa repository branches and the old pinned `d0726eed...` implementation as implementation clues only.
6. Original MedicaShop reference as historical design-intent evidence only when the approved live Rosa baseline does not answer a question.
7. `apps/web/**` is not visual authority.

The historical pinned branch is explicitly demoted. It must never override direct live Rosa evidence.

## 4. Baseline immutability and reproducibility

The live website is a deployed system and can change. A moving website cannot be the sole reproducible regression fixture.

Therefore the implementation phase must first turn the 2026-09-06 live audit into a **frozen visual baseline**:

- retain live screenshots for every supported route/viewport;
- retain local screenshots from the same audit for before/after comparison;
- retain side-by-side, overlay and diff images;
- retain route/viewport metadata and browser geometry JSON;
- retain audit manifest and report;
- record the audit date/time and browser conditions;
- do not silently refresh the frozen target after implementation starts.

If production later changes intentionally, target refresh is a separate explicit decision.

## 5. Architecture that remains valid and must be preserved

The visual-authority failure does **not** invalidate the following architecture, which has already been locally proven and should be retained unless direct evidence requires a narrow change.

### 5.1 Elementor Free owns

Elementor remains the approved authoring surface for body content of:

- EN Home;
- AR Home;
- EN About;
- AR About;
- EN Contact;
- AR Contact.

Existing client edits must continue to survive routine seed operations.

The six documents must remain independently editable and language-specific.

### 5.2 WooCommerce owns

WooCommerce remains the sole truth for:

- products;
- categories/families;
- product names;
- descriptions;
- product media;
- SKUs/catalogue codes;
- attributes;
- configurations/variations;
- publish state;
- future pricing data.

Shop and Product Detail must render from Woo data. Product records must not be serialized into Elementor page JSON.

### 5.3 Central Rosa settings own

Centralized settings remain authoritative for shared business/site values including:

- phone;
- email;
- English address;
- Arabic address;
- WhatsApp where used;
- shared CTA/site values.

The already-proven ownership rule remains: protected shared/Woo values stay outside the six Elementor JSON documents.

### 5.4 Rosa theme/code owns

Theme/plugin code continues to own:

- global header/announcement/navigation shell;
- footer and shared shell structure;
- responsive behavior;
- RTL foundations;
- reusable live-matching section markup/classes;
- Woo archive/product presentation;
- global interaction behavior;
- live-fidelity CSS/JS required to reproduce the approved public design.

### 5.5 Permissions boundary remains

The verified `rosa_content_manager` role and `rosa_manage_content` capability remain part of the target architecture.

Visual recovery must not reintroduce administrator-only content editing or grant plugin/theme/system administration capabilities to the content-manager role.

## 6. What is reopened

All visual-completion claims for the following are reopened until direct live-baseline acceptance is green:

- Home EN/AR;
- About EN/AR;
- Contact EN/AR;
- Shop EN/AR;
- representative Product Detail;
- supported public product family/category surfaces;
- shared announcement/header/navigation/mobile drawer;
- shared pre-footer CTA;
- footer;
- responsive behavior at every required viewport;
- Arabic RTL behavior.

A previous automated PASS against the old pinned target does not freeze any of these surfaces.

## 7. Required route matrix

The recovery must cover at minimum:

### Marketing

- `/`
- `/ar/`
- `/about/`
- `/ar/about/`
- `/contact/`
- `/ar/contact/`

### Catalogue

- `/shop/`
- `/ar/shop/` when supported by the approved live routing model;
- the representative Stevens Product Detail route used by the local fixture and its live equivalent when one exists;
- any supported public category/family route needed to reproduce current live catalogue navigation.

If a live route does not exist, do not fabricate one merely to satisfy an old contract.

## 8. Required viewport matrix

Use exactly:

- 1920×1080;
- 1440×900;
- 1280×800;
- 1024×768;
- 768×1024;
- 431×932;
- 390×844;
- 360×800.

Existing wider 2560 coverage may remain as supplemental evidence but is not a substitute for these sizes.

## 9. Page-by-page recovery method

Every page is recovered independently using the same evidence loop.

### Step A — freeze the live page

For the target route and all required viewports:

- capture the live page under deterministic browser conditions;
- record full-page dimensions;
- record section order and bounding boxes;
- record computed typography, spacing, grid, color, radius and media-fit properties for meaningful elements;
- record console/network/media failures separately;
- inspect screenshots manually.

### Step B — capture current local

Capture localhost under the same conditions and produce:

- local screenshot;
- side-by-side;
- 50% overlay;
- deterministic diff;
- geometry/style metrics.

### Step C — classify root causes

Classify every meaningful mismatch as one or more of:

- wrong page topology;
- missing section;
- duplicate section;
- wrong content source;
- wrong media asset;
- wrong media crop/aspect ratio;
- wrong Woo query/data population;
- wrong widget output;
- wrong PHP partial;
- wrong Elementor wrapper/layout behavior;
- wrong shared shell placement;
- CSS geometry/typography/color drift;
- JS/interaction drift;
- RTL-specific drift;
- fixture/data-state mismatch;
- environment/loading mismatch.

Do not add CSS until the root cause is known.

### Step D — define a failing live contract

Before production code changes, add or update the smallest meaningful contract that fails because of the observed live mismatch.

Tests may assert:

- live section topology;
- required page-body markers;
- dynamic ownership paths;
- section counts;
- key browser geometry;
- viewport transformations;
- absence of duplicate shell/body structures;
- media identity/crop rules;
- live screenshot/diff thresholds where deterministic enough.

A test that only proves a selector exists is insufficient when the visual defect is composition-level.

### Step E — implement minimally

Repair the page using the existing ownership architecture. Do not rewrite unrelated pages or move data to the wrong owner merely because that would make visual reproduction easier.

### Step F — accept visually

A page can be frozen only when:

- its focused structural/runtime tests are green;
- ownership/edit-preservation regressions remain green;
- required screenshots are regenerated;
- no major/high live-versus-local differences remain;
- representative 1440×900, 1024×768 and 390×844 comparisons have been manually reviewed;
- Arabic/RTL acceptance is green where applicable.

## 10. Recovery sequence

Use this order:

1. establish and commit trustworthy live-baseline tooling/contracts;
2. Home EN/AR;
3. About EN/AR;
4. Contact EN/AR;
5. Shop EN/AR;
6. Product Detail and required family/category surfaces;
7. shared-shell cross-page sweep;
8. Arabic/RTL cross-page sweep;
9. full responsive matrix;
10. final live-versus-local audit;
11. only then final verifier integration and dead-code cleanup.

Why this order:

- Home and About validate the Elementor reconstruction path;
- Contact validates Elementor plus centralized business data and form presentation;
- Shop validates Woo archive rendering and live catalogue population;
- Product Detail validates dynamic Woo detail rendering and shell placement;
- shell/RTL sweeps happen after page bodies stop moving so shared corrections are not repeatedly invalidated.

## 11. Home recovery requirements

Do not assume the old nine-widget Home topology remains visually authoritative.

Inventory the frozen live Home first.

Retain Elementor authoring, but change widget/partial topology where necessary to reproduce the live Home exactly while preserving client-editable approved fields.

For product-driven sections, Woo remains dynamic.

Acceptance must include:

- live section order;
- hero composition and media;
- all major product/promotional/workflow/support sections visible on live;
- exact shared CTA relationship;
- responsive transformations;
- Arabic ordering/alignment;
- no duplicate legacy Home markup.

## 12. About recovery requirements

Inventory the live About page from the frozen audit before relying on the prior seven-widget structure.

Retain Elementor editing but reconstruct any mismatched section topology, media treatment, spacing, typography or card composition to the live target.

Do not preserve an old section merely because a prior contract expects it if the live page no longer contains it.

## 13. Contact recovery requirements

The direct audit demonstrates that the current local Contact composition is not the approved live composition.

The recovery must reproduce the frozen live Contact design, including the observed:

- live hero treatment;
- live two-column/card composition;
- live introductory/contact-information hierarchy;
- numbered contact-detail presentation where present;
- live message-form presentation;
- live spacing/shadow/radius treatment;
- live relationship to the shared CTA/footer.

Centralized phone/email/address ownership must remain intact.

The design remains presentation/mailto-oriented unless separate authorization explicitly adds a server-side submission workflow. Visual recovery must not quietly introduce a new backend.

## 14. Shop recovery requirements

The direct audit demonstrates that current localhost Shop is a major mismatch.

The frozen live Shop—not the old pinned branch—is authoritative for:

- hero/search composition;
- search placement;
- catalogue sorting/control placement;
- product/family-card grid density;
- number and ordering of visible cards for matched Woo fixture data;
- workflow/explanation section;
- procurement-support section;
- family navigation/strip;
- shared CTA/footer relationship;
- responsive stacking.

WooCommerce remains the data source.

If localhost displays only one product while the frozen live target displays a richer catalogue, first determine whether the difference is fixture/data population or renderer/query logic. Do not hard-code fake product cards to imitate the screenshot.

## 15. Product Detail recovery requirements

The direct audit demonstrates that current localhost Product Detail is not acceptable.

The frozen live Product Detail is authoritative for:

- breadcrumb/category context;
- gallery/media composition;
- product title/reference/price-request presentation;
- support/check/quotation panel;
- configuration presentation;
- description/tab region;
- related products/families;
- shared CTA/footer placement;
- responsive transformations.

Product name, description, media, SKUs, configurations and related catalogue data must remain Woo-driven.

The local page must contain exactly one coherent shared shell and one coherent product body. Duplicate/misordered footer/body output is a release blocker.

## 16. Shared shell recovery requirements

The screenshots suggest the shared shell is closer than page bodies, but it is not exempt from direct comparison.

Verify and, where necessary, repair once globally:

- announcement bar height/copy/alignment;
- logo sizing and placement;
- navigation spacing;
- language/action controls;
- mobile menu/drawer;
- content rail width;
- shared CTA geometry/media;
- footer columns, typography, dividers and bottom row;
- Arabic shell direction/order.

Do not patch the shell separately per page.

## 17. Visual acceptance standard

A page is not accepted because a structural or geometry test passes.

Final page acceptance requires all of:

### Structural

- correct live section order;
- no missing major live sections;
- no local-only major sections unless intentionally approved;
- no duplicated body/shell structures;
- correct dynamic data ownership.

### Browser geometry/style

Measure meaningful elements for:

- x/y;
- width/height;
- margin/padding/gap;
- display/grid columns;
- font family/size/weight/line height;
- alignment;
- background/color;
- border/radius/shadow;
- media object-fit/object-position;
- document width/height/overflow.

### Media

- correct asset or approved equivalent;
- correct aspect ratio/crop;
- no broken lazy-loaded images;
- no placeholder substitution where live contains approved media.

### Responsive/RTL

- desktop/tablet/mobile topology matches live;
- no unintended horizontal overflow;
- mobile navigation behavior matches;
- Arabic logical spacing/order/alignment matches;
- phone/email/SKU identifiers maintain correct LTR isolation.

### Human visual review

At minimum manually review side-by-side/overlay/diff at:

- 1440×900;
- 1024×768;
- 390×844.

If a human reviewer can immediately identify a different page composition, automated green status is invalid and the page remains open.

## 18. Difference classification

Use these severities consistently:

- **CRITICAL:** different/broken page composition or duplicate shell/body output;
- **HIGH:** missing/wrong major section, major media, responsive state or shared-shell component;
- **MEDIUM:** clearly visible spacing/typography/card/grid/crop drift;
- **LOW:** small cosmetic deviation unlikely to change design perception;
- **CONTENT:** text/data mismatch with equivalent layout;
- **ENVIRONMENT:** load/font/network/runtime issue not caused by implementation.

No CRITICAL or HIGH difference may remain at page freeze.

MEDIUM differences require explicit disposition: fix, prove nondeterministic rendering noise, or obtain user acceptance.

## 19. Screenshot/diff metrics are supporting evidence

Pixel metrics may include:

- full-page height delta;
- changed-pixel percentage above a sensible threshold;
- mean absolute pixel difference;
- SSIM when useful.

These metrics never substitute for visual review because fonts, anti-aliasing and dynamic text can generate benign pixel differences while major topology errors can be missed by narrow selector tests.

## 20. Authoring and ownership regression gates

Every visual recovery phase must preserve the already-green architecture.

At appropriate checkpoints rerun:

- six-page Elementor mutation/edit persistence;
- zero-drift default-content regression;
- Woo/settings ownership mutation test;
- BusinessSettings contract;
- Rosa content-manager runtime permission boundary;
- Elementor editor-link contract;
- product data/SKU fixture verification;
- accessibility/browser interaction acceptance.

If visual repair breaks ownership/editability, the repair is invalid even if screenshots improve.

## 21. Seeder safety

Routine seed scripts remain non-destructive to migrated/client-edited Elementor documents.

Do not use `--force` as a normal visual-recovery mechanism.

Do not regenerate pages in a way that silently erases client edits merely to simplify parity work.

When page topology must change, use explicit migration logic that distinguishes generated untouched documents from client-edited documents and reports unsafe migration states rather than overwriting them.

## 22. Existing tests that must be demoted or rewritten

The old tests that encode the historical pinned visual target are no longer final visual authority.

They may be retained temporarily as historical regression evidence only when they do not block live fidelity.

In particular, the pending branch commit `dc8e056b9bfdb495fcd9661ccd6aa493e0bd0e5a` added a RED tooling requirement for a strict verifier against the old finished-target reference. **Do not implement that old requirement as written.**

The recovery plan must instead rewrite strict visual verification around the frozen approved live-site baseline.

Any test that conflicts with frozen live evidence must be updated or replaced through TDD. Do not weaken unrelated ownership/accessibility/security contracts.

## 23. Audit artifacts and repository policy

The CLI audit package and screenshots are evidence, not application source.

Preferred policy:

- keep large image artifacts under ignored artifact paths;
- commit compact manifests/metrics/contracts only when useful for reproducibility;
- do not bloat Git history with every PNG unless the repository already has an approved lightweight baseline strategy;
- never include secrets, database dumps or browser caches;
- record stable relative artifact paths in reports/runbooks.

The implementation plan must decide the exact lightweight committed baseline format before page repair starts.

## 24. Interaction acceptance

Preserve the approved live interactions without inventing behavior.

At minimum verify:

- desktop primary navigation;
- mobile menu open/close;
- language switch;
- contact/inquiry controls;
- product/family links;
- Shop search/sort behavior present on live;
- Product Detail actions/anchors present on live;
- reduced-motion-safe behavior where motion exists.

Do not submit real production contact forms or perform destructive catalogue actions during visual testing.

## 25. Completion definition

The visual recovery is complete only when:

1. every required route has a frozen live baseline;
2. Home EN/AR passes live visual acceptance;
3. About EN/AR passes live visual acceptance;
4. Contact EN/AR passes live visual acceptance;
5. Shop EN/AR passes live visual acceptance;
6. representative Product Detail/family surfaces pass live visual acceptance;
7. shared shell passes cross-page review;
8. RTL/responsive sweeps pass;
9. authoring/Woo/settings/permission/accessibility regressions remain green;
10. a fresh full live-versus-local audit reports no CRITICAL or HIGH differences and no unresolved MEDIUM differences;
11. the master verifier uses the corrected live-baseline authority;
12. only then are obsolete historical parity helpers eligible for proven-dead cleanup.

## 26. Deployment boundary

This corrective implementation remains local/repository work.

No Hostinger, production database, production filesystem, DNS, production WooCommerce data, live Elementor documents or other production state may be modified without separate explicit deployment approval after local acceptance.

## 27. Immediate next step after written-spec approval

After the user reviews this written spec, create a detailed implementation plan that begins with **baseline/tooling correction**, not page CSS.

The first plan tasks must:

1. neutralize/supersede the pending old-reference strict-verifier RED contract without implementing the wrong authority;
2. define the frozen live-baseline artifact/manifest format;
3. create deterministic route/viewport live-vs-local capture and measurement tooling;
4. add a contract proving the master verifier can consume the corrected live baseline only in explicit strict mode;
5. establish the initial full route scorecard;
6. then begin Home recovery using TDD and visual review.

No page implementation should begin before this baseline is trustworthy.
