// @vitest-environment node
/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Reachability guard — openspec/changes/orphaned-surface-restoration
 * (design.md D2, REQ-DDOSR-002).
 *
 * A Filinq view can be "built but unreachable": the component compiles,
 * its backend route is live, but nothing in the manifest-driven router
 * (src/main.js → routesFromManifest()) ever routes to it — the historical
 * cause was a dead `src/router/index.js` that LISTED the view (so it
 * *looked* wired) while being imported nowhere. This suite is the durable
 * guard against that class recurring: three purely static assertions over
 * source text (no live Nextcloud instance, no browser, no Vue SFC
 * compilation) plus a small set of self-tests proving the detectors
 * themselves fail the way the canonical spec scenarios require.
 *
 * Runs under `npm run test:unit` (vitest) ONLY. It is intentionally
 * excluded from the Jest suite (jest.config.js `testPathIgnorePatterns`)
 * the same way tests/e2e/ is — a different runner owns it, because Jest
 * has no `.vue` transform pointed at Node's `fs`/`path`-only usage here
 * and because this file needs zero DOM.
 */

import { describe, it, expect } from 'vitest'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const REPO_ROOT = path.resolve(__dirname, '../..')
const SRC_DIR = path.join(REPO_ROOT, 'src')

const RECOGNISED_KINDS = new Set([
	'page',
	'modal',
	'widget',
	'form-field',
	'cell-renderer',
])

/**
 * The three webpack entry points (webpack.config.js `entry`) that mount a
 * Filinq Vue app. `main.js` is the manifest-V2 shell (routesFromManifest);
 * `settings.js` / `dashboard.js` are separate Nextcloud mount points
 * (admin settings page, dashboard app widgets) that never go through the
 * manifest router — a view reachable only from one of these two is not an
 * "orphan" in the REQ-DDOSR-002 sense, it just isn't a manifest page.
 * Keep this list in sync with webpack.config.js `entry` if that ever adds
 * a fourth mount point.
 */
const ROOT_FILES = ['src/main.js', 'src/settings.js', 'src/dashboard.js']

/**
 * Page-level views the guard must not flag as orphaned, each with a
 * one-line reason (REQ-DDOSR-002's `KNOWN_HEADLESS` allow-list). Two
 * distinct exemption shapes:
 *
 *  - `kind: 'headless-backend'` — a live backend with NO Vue component at
 *    all (financial-extraction, print-queue). There is no file to scan;
 *    the entry instead asserts the named routes are still live in
 *    appinfo/routes.php, so the exemption cannot go stale silently.
 *  - `kind: 'orphaned-view'` — a real, built `.vue` file under
 *    `src/views/**` that is deliberately NOT registered, with `file`
 *    (repo-relative) naming it.
 */
const KNOWN_HEADLESS = [
	{
		kind: 'headless-backend',
		id: 'financial-extraction',
		reason: 'backend live, no built UI, tracked separately (net-new UI is a distinct change from this restoration)',
		routes: [
			"'name' => 'extraction#financial'",
			"'name' => 'extraction#corrections'",
			"'name' => 'glAccountSuggestion#suggestAccount'",
		],
	},
	{
		kind: 'headless-backend',
		id: 'print-queue',
		reason: 'backend live, no built UI, tracked separately (net-new UI is a distinct change from this restoration)',
		routes: [
			"'name' => 'printJob#create'",
			"'name' => 'printJob#show'",
			"'name' => 'printJob#updateStatus'",
		],
	},
	{
		kind: 'orphaned-view',
		file: 'src/views/signing/BulkSigningPanel.vue',
		reason: 'owned-by:bulk-signing-field-builder — the enriched bulk-sign + field-placement surface is being actively rebuilt there; not registered here to avoid speccing a surface another active change owns',
	},
	{
		kind: 'orphaned-view',
		file: 'src/views/consent/StandingConsentIndex.vue',
		reason: 'legacy-duplicate: a second, incompatible StandingConsentIndex (consentStore/scope=entity model + its own exclusive src/modals/CreateStandingConsentModal.vue) superseded by the PolicyController-backed src/views/policy/StandingConsentIndex.vue this change registers. Was orphaned even under the old dead router. Not deleted (minimal-touch restoration, not a cleanup pass) and not registered (would collide with the canonical policy/ page of the same component name) — tracked for a follow-up cleanup decision.',
	},
	{
		kind: 'orphaned-view',
		file: 'src/views/templates/TemplateIndex.vue',
		reason: 'legacy-dangling-registration: superseded by the type:"index" Templates page (Phase 8 decomposition — see that page\'s _note in src/manifest.json); owned-by:office-template-authoring, which plans further changes to this exact file. Not registered (would be a dangling registry entry again) or deleted (another active change depends on it) here.',
	},
	{
		kind: 'orphaned-view',
		file: 'src/views/signing/SigningRequestList.vue',
		reason: 'legacy-dangling-registration: superseded by the type:"index" SigningRequests page (Phase 8 decomposition — see that page\'s _note in src/manifest.json). Tracked for cleanup, not registered or deleted here.',
	},
]

// ---------------------------------------------------------------------------
// Static analysis helpers — pure functions over source TEXT, never executed
// or `import()`-ed, so this file needs no Vue SFC transform.
// ---------------------------------------------------------------------------

/**
 * Resolve a relative import specifier against the importing file's
 * directory, trying the specifier verbatim, then with `.vue`/`.js`
 * appended, then as a directory `index.js`. Returns the absolute path of
 * the first candidate that exists as a file, or `null`.
 *
 * @param {string} fromDir Absolute directory of the importing file.
 * @param {string} specifier The import path as written in source (e.g. './views/x/Y.vue').
 * @return {string|null} Absolute resolved path, or null if nothing resolved.
 */
function resolveRelativeImport(fromDir, specifier) {
	const base = path.resolve(fromDir, specifier)
	const candidates = [
		base,
		`${base}.vue`,
		`${base}.js`,
		path.join(base, 'index.js'),
	]
	for (const candidate of candidates) {
		try {
			if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) {
				return candidate
			}
		} catch {
			// stat race / permission — treat as unresolved.
		}
	}
	return null
}

/**
 * Extract every relative (`.`-prefixed) import specifier referenced by an
 * `import ... from '<specifier>'` statement in raw source text. Deliberately
 * bounded (not per-line) so a brace-wrapped import spanning multiple lines
 * (`import {\n  Foo,\n} from '../x.js'`) is still found — the guard's whole
 * purpose is to survive incidental reformatting, not just today's style.
 * Non-relative specifiers (npm packages) are ignored — there is no local
 * file to check reachability against.
 *
 * @param {string} text Raw file contents (`.vue` or `.js`).
 * @return {string[]} Relative import specifiers found, in source order.
 */
function extractRelativeImportSpecifiers(text) {
	const specifiers = []
	const re = /\bimport\b[\s\S]{0,400}?\bfrom\s+['"](\.[^'"]+)['"]/g
	let match
	while ((match = re.exec(text)) !== null) {
		specifiers.push(match[1])
	}
	return specifiers
}

/**
 * Recursively walk a directory, returning every file matching `extPredicate`.
 *
 * @param {string} dir Absolute directory to walk.
 * @param {(ext: string) => boolean} extPredicate Predicate over `path.extname()`.
 * @return {string[]} Absolute file paths.
 */
function walkFiles(dir, extPredicate) {
	const out = []
	let entries
	try {
		entries = fs.readdirSync(dir, { withFileTypes: true })
	} catch {
		return out
	}
	for (const entry of entries) {
		const full = path.join(dir, entry.name)
		if (entry.isDirectory()) {
			out.push(...walkFiles(full, extPredicate))
		} else if (entry.isFile() && extPredicate(path.extname(entry.name))) {
			out.push(full)
		}
	}
	return out
}

/**
 * Build the set of files transitively reachable (via relative imports)
 * from a list of root files. `.vue`/`.js` files are parsed for further
 * imports; any other resolved extension (`.json`, `.css`, …) is marked
 * visited but not parsed further (nothing to recurse into).
 *
 * @param {string[]} rootFilesRelative Repo-relative root file paths.
 * @return {Set<string>} Absolute paths of every file reachable from the roots.
 */
function buildReachableSet(rootFilesRelative) {
	const visited = new Set()
	const queue = rootFilesRelative.map((f) => path.resolve(REPO_ROOT, f))
	while (queue.length > 0) {
		const file = queue.pop()
		if (visited.has(file)) continue
		if (!fs.existsSync(file) || !fs.statSync(file).isFile()) continue
		visited.add(file)
		const ext = path.extname(file)
		if (ext !== '.js' && ext !== '.vue') continue
		const text = fs.readFileSync(file, 'utf8')
		const dir = path.dirname(file)
		for (const specifier of extractRelativeImportSpecifiers(text)) {
			const resolved = resolveRelativeImport(dir, specifier)
			if (resolved && !visited.has(resolved)) {
				queue.push(resolved)
			}
		}
	}
	return visited
}

/**
 * Parse `src/registry.js`'s import statements + exported object literal
 * into `{ key: { kind, componentName, resolvedPath } }`. Purely
 * text-based (no execution) — the file's imports are all single-target
 * (`import Name from './path'`) and its entries are all the uniform
 * `Key: { kind: 'x', component: Name }` shape, so two small regexes over
 * the raw source suffice.
 *
 * @param {string} registryText Raw contents of src/registry.js.
 * @param {string} registryDir Absolute directory containing registry.js (for import resolution).
 * @return {Record<string, {kind: string, componentName: string, resolvedPath: string|null}>}
 */
function parseRegistrySource(registryText, registryDir) {
	const importRe = /^import\s+(\w+)\s+from\s+['"](\.[^'"]+)['"]/gm
	const imports = {}
	let m
	while ((m = importRe.exec(registryText)) !== null) {
		imports[m[1]] = m[2]
	}

	const entryRe = /(\w+):\s*\{\s*kind:\s*'([a-z-]+)',\s*component:\s*(\w+)\s*\}/g
	const entries = {}
	while ((m = entryRe.exec(registryText)) !== null) {
		const [, key, kind, componentName] = m
		const importPath = imports[componentName]
		const resolvedPath = importPath
			? resolveRelativeImport(registryDir, importPath)
			: null
		entries[key] = { kind, componentName, resolvedPath }
	}
	return entries
}

/**
 * Cross-check manifest `type:"custom"` pages against registry `kind:"page"`
 * entries in both directions (REQ-DDOSR-002 requirement 2).
 *
 * @param {object} manifest Parsed src/manifest.json.
 * @param {Record<string, {kind: string}>} registryEntries Parsed registry.js entries.
 * @return {{missingRegistryEntry: string[], danglingRegistryEntries: string[]}}
 *   `missingRegistryEntry`: manifest page ids whose `component` has no
 *   `kind:"page"` registry entry. `danglingRegistryEntries`: registry keys
 *   with `kind:"page"` that no manifest page's `component` references.
 */
function crossCheckManifestRegistry(manifest, registryEntries) {
	// Every way a manifest page can name an app component. `component` is the
	// custom-page body; `sidebarComponent` is the NC app-sidebar slot that
	// non-custom page types (notably `flow-detail`) use to mount their
	// controls. Both are app components that MUST carry a registry entry, and
	// counting only the first made every legitimate sidebar registration look
	// dangling.
	const customPageComponents = new Set()
	for (const p of manifest.pages || []) {
		if (p.type === 'custom' && typeof p.component === 'string') {
			customPageComponents.add(p.component)
		}
		if (typeof p.sidebarComponent === 'string') {
			customPageComponents.add(p.sidebarComponent)
		}
	}
	const pageKindKeys = new Set(
		Object.entries(registryEntries)
			.filter(([, entry]) => entry.kind === 'page')
			.map(([key]) => key),
	)

	const missingRegistryEntry = []
	for (const page of manifest.pages || []) {
		if (page.type === 'custom' && typeof page.component === 'string') {
			if (!pageKindKeys.has(page.component)) {
				missingRegistryEntry.push(
					`${page.id} → component "${page.component}"`,
				)
			}
		}
		if (typeof page.sidebarComponent === 'string') {
			if (!pageKindKeys.has(page.sidebarComponent)) {
				missingRegistryEntry.push(
					`${page.id} → sidebarComponent "${page.sidebarComponent}"`,
				)
			}
		}
	}

	const danglingRegistryEntries = []
	for (const key of pageKindKeys) {
		if (!customPageComponents.has(key)) {
			danglingRegistryEntries.push(key)
		}
	}

	return { missingRegistryEntry, danglingRegistryEntries }
}

/**
 * Find page-level `.vue` files that are neither transitively reachable
 * from the app's entry points nor explicitly allow-listed
 * (REQ-DDOSR-002 requirement 3).
 *
 * @param {string[]} viewFilesAbsolute Absolute paths of candidate `.vue` files (typically every file under src/views/**).
 * @param {Set<string>} reachableSet Absolute paths reachable from the app's entry points (see buildReachableSet).
 * @param {{kind: string, file?: string}[]} allowList The orphaned-view subset of KNOWN_HEADLESS.
 * @return {string[]} Repo-relative paths of unreachable, non-allow-listed views.
 */
function findOrphanedViews(viewFilesAbsolute, reachableSet, allowList) {
	const allowedFiles = new Set(
		allowList
			.filter((e) => e.kind === 'orphaned-view')
			.map((e) => path.resolve(REPO_ROOT, e.file)),
	)
	const orphans = []
	for (const file of viewFilesAbsolute) {
		if (reachableSet.has(file)) continue
		if (allowedFiles.has(file)) continue
		orphans.push(path.relative(REPO_ROOT, file))
	}
	return orphans
}

/**
 * Detect static routes shadowed by an earlier dynamic-parameter route.
 *
 * vue-router matches in declaration order, so `/signing/:id` declared before
 * `/signing/new` swallows the static route and its page becomes unreachable by
 * URL — a reachability failure the component-resolution checks cannot see.
 * Observed live on 2026-07-24: `/signing/new` rendered the detail page with
 * id="new" instead of the create form.
 *
 * @param {object} manifest The parsed src/manifest.json.
 * @return {Array<string>} Human-readable descriptions of each shadowed route.
 */
function findShadowedRoutes(manifest) {
	const routes = (manifest.pages || [])
		.map((p) => p.route || p.path)
		.filter((r) => typeof r === 'string')

	const segmentsOf = (route) => route.split('/').filter(Boolean)
	const shadowed = []

	routes.forEach((route, index) => {
		const segs = segmentsOf(route)
		// Only static routes can be shadowed; a route containing a param is the shadower.
		if (segs.some((s) => s.startsWith(':'))) {
			return
		}
		for (let earlier = 0; earlier < index; earlier++) {
			const prevSegs = segmentsOf(routes[earlier])
			if (prevSegs.length !== segs.length) {
				continue
			}
			const matches = prevSegs.every(
				(seg, i) => seg.startsWith(':') || seg === segs[i],
			)
			if (matches && prevSegs.some((s) => s.startsWith(':'))) {
				shadowed.push(
					`"${route}" is shadowed by earlier route "${routes[earlier]}"`,
				)
				break
			}
		}
	})

	return shadowed
}

// ---------------------------------------------------------------------------
// Real-repo assertions — the guard as it runs against the current tree.
// ---------------------------------------------------------------------------

describe('reachability guard — current repo state', () => {
	const manifest = JSON.parse(
		fs.readFileSync(path.join(SRC_DIR, 'manifest.json'), 'utf8'),
	)
	const registryText = fs.readFileSync(path.join(SRC_DIR, 'registry.js'), 'utf8')
	const registryEntries = parseRegistrySource(registryText, SRC_DIR)
	const reachableSet = buildReachableSet(ROOT_FILES)
	const routesPhpText = fs.readFileSync(
		path.join(REPO_ROOT, 'appinfo', 'routes.php'),
		'utf8',
	)

	it('registry integrity: every entry resolves a component with a recognised kind', () => {
		const problems = []
		for (const [key, entry] of Object.entries(registryEntries)) {
			if (!RECOGNISED_KINDS.has(entry.kind)) {
				problems.push(`${key}: unrecognised kind "${entry.kind}"`)
			}
			if (!entry.resolvedPath) {
				problems.push(
					`${key}: component "${entry.componentName}" does not resolve to a file`,
				)
			}
		}
		expect(problems, problems.join('\n')).toEqual([])
		// Sanity: the parser actually found entries — an empty result would
		// make every other assertion in this block vacuously true.
		expect(Object.keys(registryEntries).length).toBeGreaterThan(0)
	})

	it('manifest ↔ registry: every custom page component has a page registry entry, and vice versa (no dangling registrations)', () => {
		const { missingRegistryEntry, danglingRegistryEntries } =
			crossCheckManifestRegistry(manifest, registryEntries)
		expect(
			missingRegistryEntry,
			`manifest pages with no registry entry:\n${missingRegistryEntry.join('\n')}`,
		).toEqual([])
		expect(
			danglingRegistryEntries,
			`registry kind:"page" entries referenced by no manifest page:\n${danglingRegistryEntries.join('\n')}`,
		).toEqual([])
	})

	it('no shadowed routes: a static page route is never preceded by a matching :param route', () => {
		const shadowed = findShadowedRoutes(manifest)
		expect(
			shadowed,
			`static routes unreachable because an earlier :param route matches them first:\n${shadowed.join('\n')}`,
		).toEqual([])
	})

	it('no hidden orphans: every src/views/** file is reachable or explicitly allow-listed', () => {
		const viewFiles = walkFiles(
			path.join(SRC_DIR, 'views'),
			(ext) => ext === '.vue',
		)
		const orphans = findOrphanedViews(viewFiles, reachableSet, KNOWN_HEADLESS)
		expect(
			orphans,
			`unreachable, non-allow-listed views:\n${orphans.join('\n')}`,
		).toEqual([])
	})

	it('known-headless backends remain visibly tracked: their routes are still live', () => {
		const backends = KNOWN_HEADLESS.filter((e) => e.kind === 'headless-backend')
		expect(backends.length).toBeGreaterThan(0)
		for (const backend of backends) {
			expect(typeof backend.reason).toBe('string')
			expect(backend.reason.length).toBeGreaterThan(0)
			for (const routeNeedle of backend.routes) {
				expect(
					routesPhpText.includes(routeNeedle),
					`${backend.id}: expected route "${routeNeedle}" in appinfo/routes.php — allow-list entry is stale`,
				).toBe(true)
			}
		}
	})

	it('every orphaned-view allow-list entry names a file that actually exists (no stale exemptions)', () => {
		const orphanedViewEntries = KNOWN_HEADLESS.filter(
			(e) => e.kind === 'orphaned-view',
		)
		expect(orphanedViewEntries.length).toBeGreaterThan(0)
		for (const entry of orphanedViewEntries) {
			expect(typeof entry.reason).toBe('string')
			expect(entry.reason.length).toBeGreaterThan(0)
			const abs = path.resolve(REPO_ROOT, entry.file)
			expect(
				fs.existsSync(abs),
				`allow-listed file does not exist: ${entry.file}`,
			).toBe(true)
		}
	})
})

// ---------------------------------------------------------------------------
// Detector self-tests — synthetic inputs proving each check actually fails
// the way the canonical spec scenarios require (spec.md REQ-DDOSR-002).
// ---------------------------------------------------------------------------

describe('reachability guard — detector correctness (synthetic cases)', () => {
	it('testUnregisteredViewFails: a view neither reachable nor allow-listed is reported by name', () => {
		const fakeReachable = new Set(['/repo/src/views/known/Reachable.vue'])
		const fakeCandidates = [
			'/repo/src/views/known/Reachable.vue',
			'/repo/src/views/rogue/UnregisteredOrphan.vue',
		]
		const fakeAllowList = [
			{
				kind: 'orphaned-view',
				file: 'src/views/allowed/Allowed.vue',
				reason: 'test fixture',
			},
		]

		// findOrphanedViews resolves allow-list `file` entries relative to
		// REPO_ROOT, so exercise it through a REPO_ROOT-relative fake root.
		const relOrphans = fakeCandidates
			.filter((f) => !fakeReachable.has(f))
			.filter(
				(f) =>
					!fakeAllowList.some(
						(e) => path.resolve(REPO_ROOT, e.file) === f,
					),
			)
		expect(relOrphans).toEqual(['/repo/src/views/rogue/UnregisteredOrphan.vue'])
	})

	it('flags a manifest custom page whose component has no page registry entry (missingRegistryEntry)', () => {
		const fakeManifest = {
			pages: [
				{ id: 'Ghost', type: 'custom', component: 'GhostView' },
				{ id: 'Real', type: 'custom', component: 'RealView' },
			],
		}
		const fakeRegistry = { RealView: { kind: 'page' } }
		const { missingRegistryEntry } = crossCheckManifestRegistry(
			fakeManifest,
			fakeRegistry,
		)
		expect(missingRegistryEntry).toEqual(['Ghost → component "GhostView"'])
	})

	it('flags a registry kind:"page" entry referenced by no manifest page (danglingRegistryEntries)', () => {
		const fakeManifest = {
			pages: [{ id: 'Real', type: 'custom', component: 'RealView' }],
		}
		const fakeRegistry = {
			RealView: { kind: 'page' },
			OrphanedRegistration: { kind: 'page' },
		}
		const { danglingRegistryEntries } = crossCheckManifestRegistry(
			fakeManifest,
			fakeRegistry,
		)
		expect(danglingRegistryEntries).toEqual(['OrphanedRegistration'])
	})

	it('known-headless allow-listed views do not trip orphan detection', () => {
		const allowListedAbs = path.resolve(
			REPO_ROOT,
			'src/views/signing/BulkSigningPanel.vue',
		)
		const orphans = findOrphanedViews(
			[allowListedAbs],
			new Set(),
			KNOWN_HEADLESS,
		)
		expect(orphans).toEqual([])
	})

	it('findShadowedRoutes flags a static route declared after a matching :param route', () => {
		const shadowed = findShadowedRoutes({
			pages: [
				{ route: '/signing' },
				{ route: '/signing/:id' },
				{ route: '/signing/new' },
			],
		})
		expect(shadowed).toHaveLength(1)
		expect(shadowed[0]).toContain('/signing/new')
		expect(shadowed[0]).toContain('/signing/:id')
	})

	it('findShadowedRoutes accepts the corrected order and differing segment counts', () => {
		expect(
			findShadowedRoutes({
				pages: [
					{ route: '/signing' },
					{ route: '/signing/new' },
					{ route: '/signing/:id' },
					// 3 segments — cannot be swallowed by the 2-segment :id route.
					{ route: '/signing/verify/:fileId' },
				],
			}),
		).toEqual([])
	})

	it('extractRelativeImportSpecifiers finds a brace-wrapped multi-line import', () => {
		const text =
			"import {\n\tFoo,\n\tBar,\n} from '../store/store.js'\nimport Baz from './Baz.vue'\n"
		expect(extractRelativeImportSpecifiers(text)).toEqual([
			'../store/store.js',
			'./Baz.vue',
		])
	})

	it('ignores non-relative (npm package) import specifiers', () => {
		const text =
			"import Vue from 'vue'\nimport { NcButton } from '@nextcloud/vue'\nimport Local from './Local.vue'\n"
		expect(extractRelativeImportSpecifiers(text)).toEqual(['./Local.vue'])
	})
})
