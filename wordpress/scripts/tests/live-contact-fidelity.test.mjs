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

async function columnCount(locator) {
  const boxes = await locator.evaluateAll((elements) => elements.filter((element) => element.checkVisibility()).map((element) => {
    const rect = element.getBoundingClientRect();
    return { x: Math.round(rect.x), y: Math.round(rect.y) };
  }));
  assert.ok(boxes.length > 0, 'expected visible contact cards');
  const firstRow = Math.min(...boxes.map(({ y }) => y));
  return boxes.filter(({ y }) => Math.abs(y - firstRow) <= 2).length;
}

async function assertLiveContact(page, path, desktop) {
  assert.equal(await page.locator('[data-preview-page-hero]').count(), 1, `${path} must render one Contact hero`);
  assert.equal(await page.locator('[data-preview-contact-layout]').count(), 1, `${path} must render one Contact composition`);

  // Frozen live evidence shows one integrated two-card conversation/message composition,
  // not the historical generic three-box details block plus a separate location/map band.
  assert.equal(await page.locator('[data-preview-map-role]').count(), 0, `${path} must not render the obsolete separate map/location band`);
  assert.equal(await page.locator('.rosa-preview-contact__conversation-card').count(), 1, `${path} must render the live conversation/details card`);
  assert.equal(await page.locator('.rosa-preview-contact__message-card').count(), 1, `${path} must render the live message card`);
  assert.equal(await page.locator('.rosa-preview-contact__channel').count(), 3, `${path} must render three numbered contact channels`);
  assert.equal(
    await columnCount(page.locator('[data-preview-contact-layout] .rosa-preview-contact__card')),
    desktop ? 2 : 1,
    `${path} live Contact card layout mismatch`,
  );

  const heroBackground = await page.locator('[data-preview-page-hero]').evaluate((element) => {
    const style = getComputedStyle(element);
    return { image: style.backgroundImage, color: style.backgroundColor };
  });
  assert.notEqual(heroBackground.image, 'none', `${path} Contact hero must preserve the live layered/gradient treatment`);

  const form = page.locator('.rosa-preview-contact-form');
  assert.equal(await form.count(), 1, `${path} must render the message form presentation once`);
  assert.equal(await form.getAttribute('action'), null, `${path} must remain presentation/mailto-only`);
  assert.equal(await form.locator('a[href^="mailto:"]').count(), 1, `${path} must preserve one mailto action`);

  const documentSize = await page.evaluate(() => ({ client: document.documentElement.clientWidth, scroll: document.documentElement.scrollWidth }));
  assert.ok(documentSize.scroll <= documentSize.client + 1, `${path} overflows horizontally: ${documentSize.scroll} > ${documentSize.client}`);
}

try {
  for (const viewport of [{ width: 1440, height: 900 }, { width: 1024, height: 768 }, { width: 390, height: 844 }]) {
    const desktop = viewport.width > 768;
    for (const path of ['/contact/', '/ar/contact/']) {
      const page = await load(path, viewport);
      await assertLiveContact(page, path, desktop);
      await page.close();
    }
  }
  process.stdout.write('PASS: Contact EN/AR matches the frozen live Rosa topology at desktop/tablet/mobile\n');
} finally {
  await browser.close();
}
