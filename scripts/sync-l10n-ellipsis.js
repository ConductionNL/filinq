#!/usr/bin/env node
/**
 * Mirrors the @nextcloud/l10n-enforce-ellipsis lint fix into the l10n
 * translation catalogs. ESLint only touches source files; this rewrites
 * `...` (exactly three ASCII dots, not adjacent to another dot) to `…`
 * in l10n/*.json — keys and values both, since runtime lookups match
 * the exact source string.
 *
 * Implemented as a textual substitution to preserve all unrelated
 * formatting (whitespace, key order, even duplicate keys) so the diff
 * is strictly the ellipsis change.
 *
 * Runs as part of `npm run lint-fix`.
 */

const fs = require('fs')
const path = require('path')

const ELLIPSIS_RE = /(?<!\.)\.{3}(?!\.)/g

function processFile(file) {
	const original = fs.readFileSync(file, 'utf8')
	const rewritten = original.replace(ELLIPSIS_RE, '…')
	if (rewritten === original) return false
	fs.writeFileSync(file, rewritten)
	return true
}

const l10nDir = path.join(__dirname, '..', 'l10n')
if (!fs.existsSync(l10nDir)) {
	process.exit(0)
}

let changed = 0
for (const entry of fs.readdirSync(l10nDir)) {
	if (!entry.endsWith('.json')) continue
	const file = path.join(l10nDir, entry)
	if (processFile(file)) {
		changed++
		console.log(`sync-l10n-ellipsis: rewrote ${path.relative(process.cwd(), file)}`)
	}
}

if (changed === 0) {
	console.log('sync-l10n-ellipsis: no changes')
}
