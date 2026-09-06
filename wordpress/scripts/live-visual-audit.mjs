import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { pathToFileURL } from 'node:url';
import { settlePageMedia } from './client-preview-capture.mjs';

const require = createRequire(new URL('../../apps/web/package.json', import.meta.url));
const { chromium } = require('@playwright/test');

export const ROUTES = [
  { key: 'home-en', page: 'home', lang: 'en', path: '/' },
  { key: 'home-ar', page: 'home', lang: 'ar', path: '/ar/' },
  { key: 'about-en', page: 'about', lang: 'en', path: '/about/' },
  { key: 'about-ar', page: 'about', lang: 'ar', path: '/ar/about/' },
  { key: 'contact-en', page: 'contact', lang: 'en', path: '/contact/' },
  { key: 'contact-ar', page: 'contact', lang: 'ar', path: '/ar/contact/' },
  { key: 'shop-en', page: 'shop', lang: 'en', path: '/shop/' },
  { key: 'shop-ar', page: 'shop', lang: 'ar', path: '/ar/shop/' },
];

export const VIEWPORTS = [
  { width: 1920, height: 1080 },
  { width: 1440, height: 900 },
  { width: 1280, height: 800 },
  { width: 1024, height: 768 },
  { width: 768, height: 1024 },
  { width: 431, height: 932 },
  { width: 390, height: 844 },
  { width: 360, height: 800 },
];

const REVIEW_WIDTHS = new Set([1440, 1024, 390]);
const PIXEL_THRESHOLD = 24;
const CHANGED_PIXEL_LIMIT = 0.03;
const HEIGHT_LIMIT = 8;
const GEOMETRY_LIMIT = 4;
const FONT_LIMIT = 1;

function parseArgs(argv) {
  const args = {};
  for (let index = 0; index < argv.length; index += 1) {
    const token = argv[index];
    if (!token.startsWith('--')) continue;
    const key = token.slice(2);
    if (key === 'verify') {
      args.verify = true;
      continue;
    }
    const value = argv[index + 1];
    if (!value || value.startsWith('--')) throw new Error(`Missing value for --${key}`);
    args[key] = value;
    index += 1;
  }
  return args;
}

function ensureDirectory(directory) {
  fs.mkdirSync(directory, { recursive: true });
}

function viewportLabel(viewport) {
  return `${viewport.width}x${viewport.height}`;
}

function screenshotDirectory(root, route, viewport) {
  return path.join(root, 'screenshots', route.page, route.lang, viewportLabel(viewport));
}

function metricsDirectory(root, route, viewport) {
  return path.join(root, 'metrics', route.page, route.lang, viewportLabel(viewport));
}

async function waitForFonts(page) {
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
  });
}

async function collectMetrics(page) {
  return page.evaluate(() => {
    const descriptor = (element, key) => {
      const rect = element.getBoundingClientRect();
      const style = getComputedStyle(element);
      const image = element instanceof HTMLImageElement ? element : element.querySelector('img');
      return {
        key,
        tag: element.tagName.toLowerCase(),
        classes: typeof element.className === 'string' ? element.className : '',
        text: (element.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 280),
        rect: {
          x: rect.x + window.scrollX,
          y: rect.y + window.scrollY,
          width: rect.width,
          height: rect.height,
        },
        display: style.display,
        gridTemplateColumns: style.gridTemplateColumns,
        gap: style.gap,
        padding: [style.paddingTop, style.paddingRight, style.paddingBottom, style.paddingLeft],
        margin: [style.marginTop, style.marginRight, style.marginBottom, style.marginLeft],
        fontFamily: style.fontFamily,
        fontSize: style.fontSize,
        fontWeight: style.fontWeight,
        lineHeight: style.lineHeight,
        letterSpacing: style.letterSpacing,
        textAlign: style.textAlign,
        color: style.color,
        backgroundColor: style.backgroundColor,
        borderRadius: style.borderRadius,
        boxShadow: style.boxShadow,
        objectFit: image ? getComputedStyle(image).objectFit : null,
        objectPosition: image ? getComputedStyle(image).objectPosition : null,
      };
    };

    const entries = [];
    const seen = new Set();
    const add = (element, key) => {
      if (!(element instanceof Element) || seen.has(element)) return;
      seen.add(element);
      entries.push(descriptor(element, key));
    };

    const known = [
      ['announcement', '.rosa-preview-announcement'],
      ['header', '.rosa-preview-header'],
      ['home-hero', '[data-home-section="hero"]'],
      ['home-who', '[data-home-section="who"]'],
      ['home-featured', '[data-home-section="featured"]'],
      ['home-feature', '[data-home-section="feature"]'],
      ['home-latest', '[data-home-section="latest"]'],
      ['home-promos', '[data-home-section="promos"]'],
      ['home-why', '[data-home-section="why"]'],
      ['home-proof', '[data-home-section="proof"]'],
      ['home-evidence', '[data-home-section="evidence"]'],
      ['page-hero', '[data-preview-page-hero]'],
      ['about-who', '[data-preview-who-we-are]'],
      ['about-stats', '[data-preview-stats]'],
      ['about-cards', '[data-preview-about-cards]'],
      ['feature-banner', '[data-preview-feature-banner]'],
      ['why-us', '[data-preview-why-us]'],
      ['family-strip', '[data-preview-family-strip]'],
      ['contact-layout', '[data-preview-contact-layout]'],
      ['contact-map', '[data-preview-map-role]'],
      ['shop-hero', '[data-preview-shop-hero]'],
      ['shop-body', '.rosa-preview-shop'],
      ['shop-search', '.rosa-preview-shop-search'],
      ['shop-grid', '[data-preview-shop-grid]'],
      ['prefooter', '.rosa-preview-prefooter'],
      ['footer', '[data-rosa-preview-footer]'],
    ];
    for (const [key, selector] of known) {
      document.querySelectorAll(selector).forEach((element, index) => add(element, index === 0 ? key : `${key}-${index + 1}`));
    }

    const main = document.querySelector('main');
    if (main) {
      Array.from(main.children).forEach((element, index) => {
        const homeKey = element.getAttribute('data-home-section');
        const explicit = homeKey ? `main-home-${homeKey}` : `main-child-${index + 1}`;
        add(element, explicit);
      });
    }

    const visibleBrokenImages = Array.from(document.images).filter((image) => {
      const rect = image.getBoundingClientRect();
      const visible = rect.width > 0 && rect.height > 0 && getComputedStyle(image).visibility !== 'hidden' && getComputedStyle(image).display !== 'none';
      return visible && (!image.complete || image.naturalWidth === 0);
    }).map((image) => image.currentSrc || image.src);

    return {
      schemaVersion: 1,
      lang: document.documentElement.lang,
      dir: document.documentElement.dir,
      document: {
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        scrollHeight: document.documentElement.scrollHeight,
        clientHeight: document.documentElement.clientHeight,
      },
      sections: entries,
      sectionOrder: entries.filter((entry) => entry.key.startsWith('main-')).map((entry) => entry.key),
      visibleBrokenImages,
    };
  });
}

export async function captureSingle(browser, base, route, viewport, screenshotPath) {
  ensureDirectory(path.dirname(screenshotPath));
  const page = await browser.newPage({ viewport, deviceScaleFactor: 1, reducedMotion: 'reduce' });
  const consoleErrors = [];
  const pageErrors = [];
  const failedRequests = [];
  page.on('console', (message) => { if (message.type() === 'error') consoleErrors.push(message.text()); });
  page.on('pageerror', (error) => pageErrors.push(error.message));
  page.on('requestfailed', (request) => failedRequests.push({ url: request.url(), error: request.failure()?.errorText || 'request failed' }));

  try {
    const url = new URL(route.path, base).href;
    const response = await page.goto(url, { waitUntil: 'load', timeout: 60_000 });
    if (!response?.ok()) throw new Error(`${route.key} ${viewportLabel(viewport)} returned ${response?.status() ?? 'no response'} from ${url}`);
    await waitForFonts(page);
    await settlePageMedia(page, { scrollDelayMs: 10 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(150);
    const metrics = await collectMetrics(page);
    await page.screenshot({ path: screenshotPath, fullPage: true, animations: 'disabled' });
    return { url, metrics, consoleErrors, pageErrors, failedRequests };
  } finally {
    await page.close();
  }
}

function imageDataUrl(buffer) {
  return `data:image/png;base64,${buffer.toString('base64')}`;
}

export async function comparePngBuffers(browser, referenceBuffer, currentBuffer) {
  const page = await browser.newPage({ viewport: { width: 800, height: 600 }, deviceScaleFactor: 1 });
  try {
    return await page.evaluate(async ({ referenceUrl, currentUrl, threshold }) => {
      const load = (src) => new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = src;
      });
      const [reference, current] = await Promise.all([load(referenceUrl), load(currentUrl)]);
      const maxWidth = Math.max(reference.naturalWidth, current.naturalWidth);
      const maxHeight = Math.max(reference.naturalHeight, current.naturalHeight);
      const maxPixels = maxWidth * maxHeight;
      const sampleScale = Math.min(1, Math.sqrt(12_000_000 / Math.max(1, maxPixels)));
      const width = Math.max(1, Math.round(maxWidth * sampleScale));
      const height = Math.max(1, Math.round(maxHeight * sampleScale));
      const canvasA = document.createElement('canvas');
      const canvasB = document.createElement('canvas');
      canvasA.width = canvasB.width = width;
      canvasA.height = canvasB.height = height;
      const ctxA = canvasA.getContext('2d', { willReadFrequently: true });
      const ctxB = canvasB.getContext('2d', { willReadFrequently: true });
      ctxA.fillStyle = '#fff'; ctxA.fillRect(0, 0, width, height);
      ctxB.fillStyle = '#fff'; ctxB.fillRect(0, 0, width, height);
      ctxA.drawImage(reference, 0, 0, Math.round(reference.naturalWidth * sampleScale), Math.round(reference.naturalHeight * sampleScale));
      ctxB.drawImage(current, 0, 0, Math.round(current.naturalWidth * sampleScale), Math.round(current.naturalHeight * sampleScale));
      const a = ctxA.getImageData(0, 0, width, height).data;
      const b = ctxB.getImageData(0, 0, width, height).data;
      let changed = 0;
      let absolute = 0;
      for (let index = 0; index < a.length; index += 4) {
        const dr = Math.abs(a[index] - b[index]);
        const dg = Math.abs(a[index + 1] - b[index + 1]);
        const db = Math.abs(a[index + 2] - b[index + 2]);
        absolute += dr + dg + db;
        if (Math.max(dr, dg, db) > threshold) changed += 1;
      }
      const pixels = width * height;
      return {
        reference: { width: reference.naturalWidth, height: reference.naturalHeight },
        current: { width: current.naturalWidth, height: current.naturalHeight },
        sampleScale,
        samplePixels: pixels,
        changedPixels: changed,
        changedPixelRatio: pixels ? changed / pixels : 0,
        meanAbsoluteChannelDifference: pixels ? absolute / (pixels * 3) : 0,
      };
    }, {
      referenceUrl: imageDataUrl(referenceBuffer),
      currentUrl: imageDataUrl(currentBuffer),
      threshold: PIXEL_THRESHOLD,
    });
  } finally {
    await page.close();
  }
}

async function renderReviewPair(browser, referenceBuffer, currentBuffer, outputPath) {
  ensureDirectory(path.dirname(outputPath));
  const page = await browser.newPage({ viewport: { width: 1200, height: 800 }, deviceScaleFactor: 1 });
  try {
    await page.setContent(`<!doctype html><meta charset="utf-8"><style>
      html,body{margin:0;background:#222;font:16px sans-serif} .wrap{display:flex;align-items:flex-start;gap:2px}.col{background:#fff;min-width:0}.label{position:sticky;top:0;z-index:2;background:#111;color:#fff;padding:8px 12px}.col img{display:block;max-width:none}
    </style><div class="wrap"><div class="col"><div class="label">LIVE / FROZEN REFERENCE</div><img id="a"></div><div class="col"><div class="label">LOCAL / CURRENT</div><img id="b"></div></div>`);
    await page.evaluate(({ a, b }) => {
      document.querySelector('#a').src = a;
      document.querySelector('#b').src = b;
    }, { a: imageDataUrl(referenceBuffer), b: imageDataUrl(currentBuffer) });
    await page.waitForFunction(() => Array.from(document.images).every((image) => image.complete));
    await page.screenshot({ path: outputPath, fullPage: true, animations: 'disabled' });
  } finally {
    await page.close();
  }
}

function readJsonIfPresent(file) {
  if (!file || !fs.existsSync(file)) return null;
  try { return JSON.parse(fs.readFileSync(file, 'utf8')); } catch { return null; }
}

function baselineMetricsCandidates(baselineDir, route, viewport) {
  const vp = viewportLabel(viewport);
  return [
    path.join(baselineDir, 'metrics', route.page, route.lang, vp, 'live.json'),
    path.join(baselineDir, 'metrics', route.page, route.lang, vp, 'live-metrics.json'),
    path.join(baselineDir, 'screenshots', route.page, route.lang, vp, 'live-metrics.json'),
    path.join(baselineDir, 'screenshots', route.page, route.lang, vp, 'metrics.json'),
  ];
}

function findBaselineMetrics(baselineDir, route, viewport) {
  for (const candidate of baselineMetricsCandidates(baselineDir, route, viewport)) {
    const value = readJsonIfPresent(candidate);
    if (value) return { path: candidate, value };
  }
  return null;
}

function numericPx(value) {
  const match = /^(-?\d+(?:\.\d+)?)px$/.exec(String(value ?? ''));
  return match ? Number(match[1]) : null;
}

function compareMetrics(reference, current) {
  const differences = [];
  if (!reference || reference.schemaVersion !== 1 || current?.schemaVersion !== 1) return differences;
  if (reference.lang !== current.lang) differences.push(`lang changed: frozen=${reference.lang} current=${current.lang}`);
  if (reference.dir !== current.dir) differences.push(`dir changed: frozen=${reference.dir} current=${current.dir}`);
  if (JSON.stringify(reference.sectionOrder) !== JSON.stringify(current.sectionOrder)) differences.push('major-section count/order changed');
  const currentByKey = new Map(current.sections.map((entry) => [entry.key, entry]));
  for (const frozen of reference.sections) {
    const live = currentByKey.get(frozen.key);
    if (!live) {
      differences.push(`section missing: ${frozen.key}`);
      continue;
    }
    for (const property of ['x', 'y', 'width', 'height']) {
      if (Math.abs(Number(frozen.rect[property]) - Number(live.rect[property])) > GEOMETRY_LIMIT) {
        differences.push(`${frozen.key} ${property} changed by > ${GEOMETRY_LIMIT}px`);
      }
    }
    const frozenFont = numericPx(frozen.fontSize);
    const liveFont = numericPx(live.fontSize);
    if (frozenFont !== null && liveFont !== null && Math.abs(frozenFont - liveFont) > FONT_LIMIT) {
      differences.push(`${frozen.key} font size changed by > ${FONT_LIMIT}px`);
    }
  }
  return differences;
}

function baselineScreenshot(baselineDir, route, viewport) {
  return path.join(baselineDir, 'screenshots', route.page, route.lang, viewportLabel(viewport), 'live.png');
}

function routeSelection(only) {
  if (!only) return ROUTES;
  const requested = new Set(String(only).split(',').map((value) => value.trim()).filter(Boolean));
  const selected = ROUTES.filter((route) => requested.has(route.key) || requested.has(route.page));
  if (selected.length === 0) throw new Error(`--only did not match any known route: ${only}`);
  return selected;
}

export async function runAudit({ referenceBase = null, baselineDir = null, currentBase, outDir, verify = false, only = null, currentLabel = 'local' }) {
  if (!currentBase) throw new Error('A current base URL is required');
  if (!referenceBase && !baselineDir) throw new Error('Provide either a reference URL or a frozen baseline directory');
  if (referenceBase && baselineDir) throw new Error('Use either --reference or --baseline, not both');
  ensureDirectory(outDir);

  const browser = await chromium.launch({ headless: true });
  const records = [];
  const failures = [];
  const selectedRoutes = routeSelection(only);
  try {
    for (const route of selectedRoutes) {
      for (const viewport of VIEWPORTS) {
        const vp = viewportLabel(viewport);
        const shotDir = screenshotDirectory(outDir, route, viewport);
        const metricDir = metricsDirectory(outDir, route, viewport);
        ensureDirectory(shotDir);
        ensureDirectory(metricDir);
        const record = { route: route.key, path: route.path, viewport: vp, differences: [] };
        try {
          let referenceCapture = null;
          let referenceMetrics = null;
          const referenceOutput = path.join(shotDir, 'live.png');
          if (referenceBase) {
            referenceCapture = await captureSingle(browser, referenceBase, route, viewport, referenceOutput);
            referenceMetrics = referenceCapture.metrics;
            fs.writeFileSync(path.join(metricDir, 'live.json'), JSON.stringify(referenceMetrics, null, 2));
          } else {
            const frozenPath = baselineScreenshot(baselineDir, route, viewport);
            if (!fs.existsSync(frozenPath)) throw new Error(`Frozen baseline screenshot missing: ${frozenPath}`);
            fs.copyFileSync(frozenPath, referenceOutput);
            const metric = findBaselineMetrics(baselineDir, route, viewport);
            referenceMetrics = metric?.value ?? null;
            record.frozenEvidence = frozenPath;
            record.frozenMetrics = metric?.path ?? null;
          }

          const currentOutput = path.join(shotDir, `${currentLabel}.png`);
          const currentCapture = await captureSingle(browser, currentBase, route, viewport, currentOutput);
          fs.writeFileSync(path.join(metricDir, `${currentLabel}.json`), JSON.stringify(currentCapture.metrics, null, 2));

          const referenceBuffer = fs.readFileSync(referenceOutput);
          const currentBuffer = fs.readFileSync(currentOutput);
          const pixels = await comparePngBuffers(browser, referenceBuffer, currentBuffer);
          record.pixelComparison = pixels;
          record.referenceUrl = referenceCapture?.url ?? null;
          record.currentUrl = currentCapture.url;
          record.consoleErrors = currentCapture.consoleErrors;
          record.pageErrors = currentCapture.pageErrors;
          record.failedRequests = currentCapture.failedRequests;

          if (Math.abs(pixels.reference.height - pixels.current.height) > HEIGHT_LIMIT) {
            record.differences.push(`full-page height changed by > ${HEIGHT_LIMIT}px (${pixels.reference.height} -> ${pixels.current.height})`);
          }
          if (pixels.changedPixelRatio > CHANGED_PIXEL_LIMIT) {
            record.differences.push(`changed-pixel ratio ${(pixels.changedPixelRatio * 100).toFixed(2)}% exceeds ${(CHANGED_PIXEL_LIMIT * 100).toFixed(2)}%`);
          }
          record.differences.push(...compareMetrics(referenceMetrics, currentCapture.metrics));
          if (currentCapture.metrics.visibleBrokenImages.length > 0) {
            record.differences.push(`visible broken images: ${currentCapture.metrics.visibleBrokenImages.join(', ')}`);
          }
          if (currentCapture.metrics.document.scrollWidth > currentCapture.metrics.document.clientWidth + 1) {
            record.differences.push(`horizontal overflow: ${currentCapture.metrics.document.scrollWidth} > ${currentCapture.metrics.document.clientWidth}`);
          }

          if (REVIEW_WIDTHS.has(viewport.width)) {
            const pairPath = path.join(shotDir, 'side-by-side.png');
            try {
              await renderReviewPair(browser, referenceBuffer, currentBuffer, pairPath);
              record.sideBySide = pairPath;
            } catch (error) {
              record.comparisonImageError = String(error?.message || error);
            }
          }

          if (verify && record.differences.length > 0) {
            failures.push({ route: route.key, viewport: vp, reasons: record.differences, frozenEvidence: record.frozenEvidence ?? referenceOutput, currentEvidence: currentOutput });
          }
        } catch (error) {
          const reason = String(error?.message || error);
          record.captureError = reason;
          failures.push({ route: route.key, viewport: vp, reasons: [reason] });
        }
        records.push(record);
      }
    }
  } finally {
    await browser.close();
  }

  const manifest = {
    schemaVersion: 1,
    generatedAt: new Date().toISOString(),
    authority: baselineDir ? 'frozen-live-baseline' : 'live-reference',
    referenceBase: referenceBase ? String(referenceBase) : null,
    baselineDir: baselineDir ? path.resolve(baselineDir) : null,
    currentBase: String(currentBase),
    currentLabel,
    routes: selectedRoutes.map(({ key, path: routePath }) => ({ key, path: routePath })),
    viewports: VIEWPORTS,
    thresholds: { pixelChannel: PIXEL_THRESHOLD, changedPixelRatio: CHANGED_PIXEL_LIMIT, fullPageHeight: HEIGHT_LIMIT, geometry: GEOMETRY_LIMIT, font: FONT_LIMIT },
    records,
    failures,
  };
  fs.writeFileSync(path.join(outDir, 'manifest.json'), JSON.stringify(manifest, null, 2));
  return manifest;
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  const referenceBase = args.reference ? new URL(args.reference) : null;
  const baselineDir = args.baseline ? path.resolve(args.baseline) : null;
  const currentBase = new URL(args.current || 'http://localhost:8088/');
  const outDir = path.resolve(args.out || 'artifacts/live-visual-recovery/manual-audit');
  const manifest = await runAudit({ referenceBase, baselineDir, currentBase, outDir, verify: Boolean(args.verify), only: args.only || null });
  process.stdout.write(`AUDIT_COMPLETE: ${manifest.records.length} route/viewport comparisons; ${manifest.failures.length} verification failures; manifest=${path.join(outDir, 'manifest.json')}\n`);
  if (args.verify && manifest.failures.length > 0) {
    for (const failure of manifest.failures) process.stderr.write(`VISUAL_DIFF: ${failure.route} ${failure.viewport}: ${failure.reasons.join('; ')}\n`);
    process.exitCode = 1;
  }
}

const isMain = process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href;
if (isMain) {
  main().catch((error) => {
    process.stderr.write(`live-visual-audit failed: ${error.stack || error}\n`);
    process.exitCode = 1;
  });
}
