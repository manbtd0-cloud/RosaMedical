import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { settlePageMedia } from '../client-preview-capture.mjs';

const require = createRequire(new URL('../../../apps/web/package.json', import.meta.url));
const { chromium } = require('@playwright/test');
const baseUrl = new URL(process.argv[2] || 'http://localhost:8088/');
const browser = await chromium.launch({ headless: true });

async function load(path, viewport) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: 1, reducedMotion: 'reduce' });
  const response = await page.goto(new URL(path, baseUrl).href, { waitUntil: 'load', timeout: 60_000 });
  assert.ok(response?.ok(), `${path} returned ${response?.status() ?? 'no response'}`);
  await page.evaluate(async () => { if (document.fonts?.ready) await document.fonts.ready; });
  await settlePageMedia(page, { scrollDelayMs: 10 });
  await page.evaluate(() => window.scrollTo(0, 0));
  return page;
}

async function assertNoHorizontalOverflow(page, path) {
  const size = await page.evaluate(() => ({
    client: document.documentElement.clientWidth,
    scroll: document.documentElement.scrollWidth,
  }));
  assert.ok(size.scroll <= size.client + 1, `${path} overflows horizontally: ${size.scroll} > ${size.client}`);
}

async function assertLiveShop(page, path, locale) {
  assert.equal(await page.locator('[data-preview-shop-hero]').count(), 1, `${path} must render one Shop hero`);
  assert.equal(await page.locator('[data-preview-shop-grid]').count(), 1, `${path} must render one catalogue grid`);

  const heroTitle = (await page.locator('[data-preview-shop-hero] h1').textContent())?.trim() || '';
  if (locale === 'en') {
    assert.equal(heroTitle, 'Find Product', `${path} must use the frozen live Find Product hero`);
  } else {
    assert.notEqual(heroTitle, 'المنتجات', `${path} must not use the obsolete generic Shop hero title`);
  }

  // Frozen live Shop shows a populated catalogue, not the old one-product/empty preview.
  const visibleCards = await page.locator('[data-preview-shop-grid] .rosa-preview-product').evaluateAll((elements) =>
    elements.filter((element) => element.checkVisibility()).length,
  );
  assert.ok(visibleCards >= 5, `${path} must expose the populated multi-family catalogue; found ${visibleCards} visible cards`);

  // Frozen live Shop continues beyond the product grid with the procurement workflow,
  // support content, family/category navigation, then the shared quotation CTA.
  assert.equal(await page.locator('[data-preview-shop-workflow]').count(), 1, `${path} must render the live procurement workflow section`);
  assert.equal(await page.locator('[data-preview-shop-support]').count(), 1, `${path} must render the live procurement support section`);
  assert.equal(await page.locator('[data-preview-shop-families]').count(), 1, `${path} must render the live family navigation section`);
  assert.equal(await page.locator('.rosa-preview-prefooter').count(), 1, `${path} must preserve the shared quotation CTA`);

  if (locale === 'en') {
    const workflowText = (await page.locator('[data-preview-shop-workflow]').textContent()) || '';
    assert.match(workflowText, /Turn an instrument need into a clear procurement request\./, `${path} must preserve the frozen live workflow heading`);
  }

  await assertNoHorizontalOverflow(page, path);
}

try {
  for (const viewport of [
    { width: 1440, height: 900 },
    { width: 1024, height: 768 },
    { width: 390, height: 844 },
  ]) {
    for (const [path, locale] of [['/shop/', 'en'], ['/ar/shop/', 'ar']]) {
      const page = await load(path, viewport);
      await assertLiveShop(page, path, locale);
      await page.close();
    }
  }
  process.stdout.write('PASS: Shop EN/AR matches the frozen live Rosa topology at desktop/tablet/mobile\n');
} finally {
  await browser.close();
}
