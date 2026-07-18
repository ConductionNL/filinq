#!/usr/bin/env node
/* eslint-disable jsdoc/require-param */
/* eslint-disable n/no-process-exit */
/* eslint-disable no-console */
/* eslint-disable n/shebang */
/**
 * l10n .js → .json synchroniser.
 *
 * The frontend l10n/*.js files (maintained by l10n-ai.js / clean-l10n.js) are
 * the source of truth. The backend l10n/*.json files (read by Nextcloud's PHP
 * IL10N) are DERIVED from them. Because the rest of the l10n tooling only
 * touches the .js files, the .json twins silently drift behind — leaving the
 * backend to fall back to English for every string added since the last manual
 * .json edit. This script regenerates each l10n/<lang>.json from its
 * l10n/<lang>.js sibling so the twins stay in lockstep.
 *
 * Run it after any change to l10n/*.js (and wire it into the l10n workflow so
 * the drift cannot recur).
 *
 * Usage:
 *   node scripts/sync-l10n-json.js           # dry-run: report which .json files are stale
 *   node scripts/sync-l10n-json.js --apply   # regenerate the .json twins from the .js files
 */

const fs = require('fs')
const path = require('path')

const {
	loadJsTranslations,
	serializeJson,
	listJsLocaleFiles,
	localeNameOf,
} = require('./lib/l10n.js')

const ROOT = path.resolve(__dirname, '..')
const L10N_DIR = path.join(ROOT, 'l10n')

// ---------- CLI ----------

const args = new Set(process.argv.slice(2))
if (args.has('--help') || args.has('-h')) {
	console.log(fs.readFileSync(__filename, 'utf8').split('\n').slice(6, 24).join('\n'))
	process.exit(0)
}
const apply = args.has('--apply')

// ---------- Main ----------

function main() {
	if (!fs.existsSync(L10N_DIR)) {
		console.error(`l10n directory not found: ${L10N_DIR}`)
		process.exit(1)
	}

	const jsFiles = listJsLocaleFiles(L10N_DIR)
	if (jsFiles.length === 0) {
		console.error(`No l10n/*.js files found in ${L10N_DIR}`)
		process.exit(1)
	}

	let staleCount = 0
	for (const jsFile of jsFiles) {
		const locale = localeNameOf(jsFile)
		const jsonFile = path.join(L10N_DIR, `${locale}.json`)

		const { translations, pluralForm } = loadJsTranslations(jsFile)
		const nextJson = serializeJson({ translations, pluralForm })
		const currentJson = fs.existsSync(jsonFile) ? fs.readFileSync(jsonFile, 'utf8') : null

		if (currentJson === nextJson) {
			console.log(`  ✓ ${locale}.json is in sync (${Object.keys(translations).length} keys)`)
			continue
		}

		staleCount++
		const keyDelta = currentJson === null
			? 'missing'
			: `${Object.keys(translations).length} keys in .js`
		if (apply) {
			fs.writeFileSync(jsonFile, nextJson, 'utf8')
			console.log(`  ↻ regenerated ${locale}.json (${keyDelta})`)
		} else {
			console.log(`  ✗ ${locale}.json is stale (${keyDelta}) — run with --apply to regenerate`)
		}
	}

	if (!apply && staleCount > 0) {
		console.log(`\n${staleCount} .json file(s) out of sync. Re-run with --apply to regenerate.`)
		process.exit(1)
	}
	console.log(apply ? '\nDone.' : '\nAll .json files are in sync.')
}

main()
