import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { runAudit } from './live-visual-audit.mjs';

function parseArgs(argv) {
  const args = {};
  for (let index = 0; index < argv.length; index += 1) {
    const token = argv[index];
    if (!token.startsWith('--')) continue;
    const key = token.slice(2);
    const value = argv[index + 1];
    if (!value || value.startsWith('--')) throw new Error(`Missing value for --${key}`);
    args[key] = value;
    index += 1;
  }
  return args;
}

export async function runSyncCheck({ baselineDir, liveBase, outDir, only = null }) {
  if (!baselineDir) throw new Error('A frozen baseline directory is required');
  const live = new URL(liveBase || 'https://rosamedical.org/');
  const manifest = await runAudit({
    baselineDir: path.resolve(baselineDir),
    currentBase: live,
    outDir: path.resolve(outDir),
    verify: true,
    only,
    currentLabel: 'fresh-live',
  });

  return manifest;
}

async function main() {
  const args = parseArgs(process.argv.slice(2));
  const baselineDir = args.baseline ? path.resolve(args.baseline) : null;
  if (!baselineDir) throw new Error('Usage: node wordpress/scripts/live-site-sync-check.mjs --baseline <frozen-audit-dir> [--live https://rosamedical.org/] --out <dir>');
  const liveBase = args.live || 'https://rosamedical.org/';
  const outDir = path.resolve(args.out || 'artifacts/live-visual-recovery/live-sync');
  const manifest = await runSyncCheck({ baselineDir, liveBase, outDir, only: args.only || null });

  if (manifest.failures.length > 0) {
    for (const failure of manifest.failures) {
      const reason = failure.reasons.join('; ');
      const baselineError = reason.includes('Frozen baseline screenshot missing');
      const prefix = baselineError ? 'BASELINE_ERROR' : 'TARGET_DRIFT';
      process.stderr.write(`${prefix}: ${failure.route} ${failure.viewport}: ${reason}`);
      if (failure.frozenEvidence) process.stderr.write(` | frozen=${failure.frozenEvidence}`);
      if (failure.currentEvidence) process.stderr.write(` | fresh=${failure.currentEvidence}`);
      process.stderr.write('\n');
    }
    process.exitCode = 1;
    return;
  }

  process.stdout.write(`PASS: current rosamedical.org remains consistent with frozen live baseline (${manifest.records.length} route/viewport checks)\n`);
}

const isMain = process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href;
if (isMain) {
  main().catch((error) => {
    process.stderr.write(`live-site-sync-check failed: ${error.stack || error}\n`);
    process.exitCode = 1;
  });
}
