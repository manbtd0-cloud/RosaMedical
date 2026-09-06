import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { settlePageMedia } from './client-preview-capture.mjs';

const require = createRequire(new URL('../../apps/web/package.json', import.meta.url));
const { chromium } = require('@playwright/test');

const referenceBase = new URL(process.argv[2] || 'https://rosamedical.org/');
const elementorBase = new URL(process.argv[3] || 'http://localhost:8088/');
const outputDir = path.resolve(process.argv[4] || 'artifacts/medicashop-elementor-parity');
const viewports = [[1440, 900], [1280, 800], [1024, 768], [768, 1024], [431, 932], [390, 844], [360, 800]];

const routes = [
  { key: 'home-en', locale: 'en', path: '/', sections: ['[data-home-section]'] },
  { key: 'home-ar', locale: 'ar', path: '/ar/', sections: ['[data-home-section]'] },
  {
    key: 'about-en', locale: 'en', path: '/about/', sections: [
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
    key: 'about-ar', locale: 'ar', path: '/ar/about/', sections: [
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
    key: 'contact-en', locale: 'en', path: '/contact/', sections: [
      '[data-preview-page-hero]',
      '[data-preview-contact-layout]',
      '[data-preview-map-role]',
    ],
  },
  {
    key: 'contact-ar', locale: 'ar', path: '/ar/contact/', sections: [
      '[data-preview-page-hero]',
      '[data-preview-contact-layout]',
      '[data-preview-map-role]',
    ],
  },
  {
    key: 'shop-en', locale: 'en', path: '/shop/', sections: [
      '[data-preview-shop-hero]',
      '.rosa-preview-shop',
    ],
  },
  {
    key: 'shop-ar', locale: 'ar', path: '/ar/shop/', sections: [
      '[data-preview-shop-hero]',
      '.rosa-preview-shop',
    ],
  },
];

const sharedSections = [
  '.rosa-preview-announcement',
  '.rosa-preview-header',
  '.rosa-preview-prefooter',
  '.rosa-preview-footer',
];

await fs.mkdir(path.join(outputDir, 'reference'), { recursive: true });
await fs.mkdir(path.join(outputDir, 'local'), { recursive: true });
const browser = await chromium.launch({ headless: true });

function slugSelector(selector, index) {
  return `${String(index + 1).padStart(2, '0')}-${selector.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase() || 'section'}`;
}

async function inspectSelector(page, selector) {
  const nodes = page.locator(selector);
  const count = await nodes.count();
  if (count === 0) return { selector, count: 0, items: [] };
  const items = await nodes.evaluateAll((elements) => elements.map((element) => {
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
    };
  }));
  return { selector, count, items };
}

async function capture(base, sourceName, route, width, height) {
  const context = await browser.newContext({
    viewport: { width, height },
    reducedMotion: 'reduce',
    deviceScaleFactor: 1,
  });
  const page = await context.newPage();
  const url = new URL(route.path, base);
  await page.goto(url.href, { waitUntil: 'load', timeout: 60_000 });
  await settlePageMedia(page, { scrollDelayMs: 10 });
  await page.evaluate(() => window.scrollTo(0, 0));

  const directory = sourceName === 'reference' ? 'reference' : 'local';
  const stem = `${route.key}-${width}x${height}`;
  await page.screenshot({
    path: path.join(outputDir, directory, `${stem}-full.png`),
    fullPage: true,
    animations: 'disabled',
  });

  const selectors = [...sharedSections, ...route.sections];
  for (let index = 0; index < selectors.length; index += 1) {
    const selector = selectors[index];
    const locator = page.locator(selector).first();
    if (await locator.count()) {
      await locator.screenshot({
        path: path.join(outputDir, directory, `${stem}-${slugSelector(selector, index)}.png`),
        animations: 'disabled',
      });
    }
  }

  const sectionMetrics = [];
  for (const selector of selectors) sectionMetrics.push(await inspectSelector(page, selector));
  const metrics = await page.evaluate(() => ({
    width: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
    height: document.documentElement.scrollHeight,
    lang: document.documentElement.lang,
    dir: document.documentElement.dir,
  }));

  await fs.writeFile(
    path.join(outputDir, directory, `${stem}-metrics.json`),
    `${JSON.stringify({ url: url.href, route: route.key, ...metrics, sections: sectionMetrics }, null, 2)}\n`,
    'utf8',
  );

  await context.close();
}

try {
  for (const route of routes) {
    for (const [width, height] of viewports) {
      await capture(referenceBase, 'reference', route, width, height);
      await capture(elementorBase, 'local', route, width, height);
      process.stdout.write(`CAPTURED: ${route.key} ${width}x${height}\n`);
    }
  }
  process.stdout.write(`PASS: finished-template reference/Elementor captures written to ${outputDir}\n`);
} finally {
  await browser.close();
}
