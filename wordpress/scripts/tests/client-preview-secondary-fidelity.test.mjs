import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { settlePageMedia } from '../client-preview-capture.mjs';

const require = createRequire(new URL('../../../apps/web/package.json', import.meta.url));
const { chromium } = require('@playwright/test');

const referenceBase = new URL(process.argv[2] || 'http://localhost:8090/');
const currentBase = new URL(process.argv[3] || 'http://localhost:8088/');
const viewports = [
  { width: 1440, height: 900 },
  { width: 1280, height: 800 },
  { width: 1024, height: 768 },
  { width: 768, height: 1024 },
  { width: 431, height: 932 },
  { width: 390, height: 844 },
  { width: 360, height: 800 },
];

const sharedSelectors = [
  '.rosa-preview-announcement',
  '.rosa-preview-header',
  '.rosa-preview-prefooter',
  '.rosa-preview-footer',
];

const routes = [
  {
    key: 'about-en', path: '/about/', selectors: [
      '[data-preview-page-hero]',
      '[data-preview-who-we-are]',
      '[data-preview-stats]',
      '[data-preview-about-cards]',
      '[data-preview-feature-banner]',
      '[data-preview-why-us]',
      '[data-preview-family-strip]',
    ],
  },
  {
    key: 'about-ar', path: '/ar/about/', selectors: [
      '[data-preview-page-hero]',
      '[data-preview-who-we-are]',
      '[data-preview-stats]',
      '[data-preview-about-cards]',
      '[data-preview-feature-banner]',
      '[data-preview-why-us]',
      '[data-preview-family-strip]',
    ],
  },
  {
    key: 'contact-en', path: '/contact/', selectors: [
      '[data-preview-page-hero]',
      '[data-preview-contact-layout]',
      '[data-preview-map-role]',
    ],
  },
  {
    key: 'contact-ar', path: '/ar/contact/', selectors: [
      '[data-preview-page-hero]',
      '[data-preview-contact-layout]',
      '[data-preview-map-role]',
    ],
  },
  {
    key: 'shop-en', path: '/shop/', selectors: [
      '[data-preview-shop-hero]',
      '.rosa-preview-shop',
      '.rosa-preview-shop-search',
      '[data-preview-shop-grid]',
    ],
  },
  {
    key: 'shop-ar', path: '/ar/shop/', selectors: [
      '[data-preview-shop-hero]',
      '.rosa-preview-shop',
      '.rosa-preview-shop-search',
      '[data-preview-shop-grid]',
    ],
  },
];

const tolerance = {
  position: 2,
  size: 2,
  font: 0.6,
  spacing: 1,
};

function normalizeText(value) {
  return value.replace(/\s+/g, ' ').trim();
}

function px(value) {
  const match = /^(-?\d+(?:\.\d+)?)px$/.exec(value);
  return match ? Number(match[1]) : null;
}

function diffNumber(actual, expected, max, label, failures) {
  if (Math.abs(actual - expected) > max) {
    failures.push(`${label}: reference=${expected.toFixed(2)} current=${actual.toFixed(2)} tolerance=${max}`);
  }
}

function compareCssNumber(actual, expected, max, label, failures) {
  const actualPx = px(actual);
  const expectedPx = px(expected);
  if (actualPx !== null && expectedPx !== null) {
    diffNumber(actualPx, expectedPx, max, label, failures);
    return;
  }
  if (actual !== expected) failures.push(`${label}: reference=${expected} current=${actual}`);
}

function compareGrid(actual, expected, label, failures) {
  if (actual === expected) return;
  const actualParts = actual.split(/\s+/).filter(Boolean);
  const expectedParts = expected.split(/\s+/).filter(Boolean);
  if (actualParts.length !== expectedParts.length) {
    failures.push(`${label}: reference=${expected} current=${actual}`);
    return;
  }
  const numeric = actualParts.every((part) => px(part) !== null) && expectedParts.every((part) => px(part) !== null);
  if (!numeric) {
    failures.push(`${label}: reference=${expected} current=${actual}`);
    return;
  }
  actualParts.forEach((part, index) => diffNumber(px(part), px(expectedParts[index]), tolerance.size, `${label}[${index}]`, failures));
}

async function snapshot(page, selector, routeKey, viewportLabel) {
  const locator = page.locator(selector).first();
  assert.equal(await page.locator(selector).count(), 1, `${routeKey} ${viewportLabel}: ${selector} must render exactly once`);
  return locator.evaluate((element) => {
    const rect = element.getBoundingClientRect();
    const style = getComputedStyle(element);
    return {
      rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
      display: style.display,
      gridTemplateColumns: style.gridTemplateColumns,
      padding: [style.paddingTop, style.paddingRight, style.paddingBottom, style.paddingLeft],
      margin: [style.marginTop, style.marginRight, style.marginBottom, style.marginLeft],
      fontSize: style.fontSize,
      lineHeight: style.lineHeight,
      backgroundColor: style.backgroundColor,
      borderRadius: style.borderRadius,
      text: element.innerText,
    };
  });
}

async function load(browser, base, route, viewport) {
  const page = await browser.newPage({ viewport, reducedMotion: 'reduce' });
  const response = await page.goto(new URL(route.path, base).href, { waitUntil: 'load', timeout: 60_000 });
  assert.ok(response?.ok(), `${route.key}: ${base.href} returned ${response?.status() ?? 'no response'}`);
  await settlePageMedia(page, { scrollDelayMs: 10 });
  await page.evaluate(() => window.scrollTo(0, 0));
  return page;
}

function compareSnapshot(current, reference, label, geometryFailures, textDifferences) {
  diffNumber(current.rect.x, reference.rect.x, tolerance.position, `${label} x`, geometryFailures);
  diffNumber(current.rect.y, reference.rect.y, tolerance.position, `${label} y`, geometryFailures);
  diffNumber(current.rect.width, reference.rect.width, tolerance.size, `${label} width`, geometryFailures);
  diffNumber(current.rect.height, reference.rect.height, tolerance.size, `${label} height`, geometryFailures);

  if (current.display !== reference.display) geometryFailures.push(`${label} display: reference=${reference.display} current=${current.display}`);
  compareGrid(current.gridTemplateColumns, reference.gridTemplateColumns, `${label} gridTemplateColumns`, geometryFailures);
  current.padding.forEach((value, index) => compareCssNumber(value, reference.padding[index], tolerance.spacing, `${label} padding[${index}]`, geometryFailures));
  current.margin.forEach((value, index) => compareCssNumber(value, reference.margin[index], tolerance.spacing, `${label} margin[${index}]`, geometryFailures));
  compareCssNumber(current.fontSize, reference.fontSize, tolerance.font, `${label} fontSize`, geometryFailures);
  compareCssNumber(current.lineHeight, reference.lineHeight, tolerance.font, `${label} lineHeight`, geometryFailures);
  if (current.backgroundColor !== reference.backgroundColor) geometryFailures.push(`${label} backgroundColor: reference=${reference.backgroundColor} current=${current.backgroundColor}`);
  compareCssNumber(current.borderRadius, reference.borderRadius, tolerance.spacing, `${label} borderRadius`, geometryFailures);

  const currentText = normalizeText(current.text);
  const referenceText = normalizeText(reference.text);
  if (currentText !== referenceText) {
    textDifferences.push(`${label}: reference text=${JSON.stringify(referenceText)} current text=${JSON.stringify(currentText)}`);
  }
}

const browser = await chromium.launch({ headless: true });
const geometryFailures = [];
const textDifferences = [];

try {
  for (const route of routes) {
    for (const viewport of viewports) {
      const viewportLabel = `${viewport.width}x${viewport.height}`;
      const referencePage = await load(browser, referenceBase, route, viewport);
      const currentPage = await load(browser, currentBase, route, viewport);

      const referenceDocument = await referencePage.evaluate(() => ({
        lang: document.documentElement.lang,
        dir: document.documentElement.dir,
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
      }));
      const currentDocument = await currentPage.evaluate(() => ({
        lang: document.documentElement.lang,
        dir: document.documentElement.dir,
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
      }));
      assert.equal(currentDocument.lang, referenceDocument.lang, `${route.key} ${viewportLabel}: lang differs from finished target`);
      assert.equal(currentDocument.dir, referenceDocument.dir, `${route.key} ${viewportLabel}: dir differs from finished target`);
      assert.ok(referenceDocument.scrollWidth <= referenceDocument.clientWidth + 1, `${route.key} ${viewportLabel}: reference overflows horizontally`);
      assert.ok(currentDocument.scrollWidth <= currentDocument.clientWidth + 1, `${route.key} ${viewportLabel}: current overflows horizontally`);

      for (const selector of [...sharedSelectors, ...route.selectors]) {
        const reference = await snapshot(referencePage, selector, route.key, viewportLabel);
        const current = await snapshot(currentPage, selector, route.key, viewportLabel);
        compareSnapshot(current, reference, `${route.key} ${viewportLabel} ${selector}`, geometryFailures, textDifferences);
      }

      await referencePage.close();
      await currentPage.close();
    }
  }

  for (const difference of textDifferences) process.stderr.write(`TEXT_DIFF: ${difference}\n`);
  if (geometryFailures.length > 0) {
    const preview = geometryFailures.slice(0, 80).map((failure) => ` - ${failure}`).join('\n');
    const suffix = geometryFailures.length > 80 ? `\n - ... ${geometryFailures.length - 80} additional geometry/style differences` : '';
    assert.fail(`Secondary-page finished-target parity failed with ${geometryFailures.length} geometry/style differences:\n${preview}${suffix}`);
  }

  process.stdout.write(`PASS: About/Contact/Shop EN/AR match the finished-target geometry/style contracts (${textDifferences.length} text/content differences classified separately)\n`);
} finally {
  await browser.close();
}
