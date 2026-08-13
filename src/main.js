/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

/* eslint-disable camelcase, no-undef */
// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ before
// any CSS or asset/resource URL or lazy chunk URL is evaluated. See
// setPublicPath.js — docudesk lives under apps-extra, not the baked-in /apps/.
import './setPublicPath.js'
import { createApp, h } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import {
	translate as t,
	translatePlural as n,
	loadTranslations,
} from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	CnPageRenderer,
	defaultPageTypes,
	registerBuiltinDashboardWidgets,
	registerIcons,
	registerTranslations,
} from '@conduction/nextcloud-vue'
import pinia from './pinia.js'
import App from './App.vue'
import bundledManifest from './manifest.json'
import menuLayout from './menu-layout.json'
import registry from './registry.js'
import appIcons from './icons.js'
import { initializeStores } from './store/store.js'

// Library CSS — must be explicit import (webpack tree-shakes side-effect imports from aliased packages)
import '@conduction/nextcloud-vue/css/index.css'
// gridstack is an nc-vue peerDependency that the library deliberately does NOT
// bundle, stylesheet included. CnDashboardPage → CnDashboardGrid drives it, and
// without this stylesheet v12 sizes items with an undefined
// `--gs-column-width`, so every dashboard item renders 0 px wide with NO error
// and nothing in the console. nc-vue's own CSS carries the `.grid-stack`
// overrides but none of the sizing primitives.
import 'gridstack/dist/gridstack.min.css'
import './assets/fonts.css'
import './assets/app.css'

// Register library-side icon set + lib translations once at bootstrap.
registerIcons(appIcons)

// nc-vue marks itself `sideEffects: ["**/*.css", ...]`, so webpack is free to
// drop the bare imports that register the built-in `stat` / `object-table`
// dashboard widgets. When that happens the widgets render "Widget not
// available" with no error (larpingapp lost 5 of 7). Registering explicitly at
// bootstrap makes it a real call webpack cannot tree-shake.
registerBuiltinDashboardWidgets()

try {
	registerTranslations()
} catch (e) {
	// Non-fatal — lib translations fall back to English source.
	// eslint-disable-next-line no-console
	console.warn(
		'[docudesk] registerTranslations failed; falling back to English',
		e,
	)
}

// Fire-and-forget translation load. Some Nextcloud installs only allow the
// JS/CSS allowlist through Apache; /custom_apps/<app>/l10n/<locale>.json
// may 404. Strings fall back to English source on miss; boot must not
// depend on this resolving.
function tryLoadTranslations() {
	try {
		const result = loadTranslations('docudesk', () => {})
		if (result && typeof result.then === 'function') {
			result.then(
				() => {},
				() => {},
			)
		}
	} catch {
		// no-op
	}
}

// Shallow-clone CnPageRenderer: the lib's barrel exports are non-extensible
// (webpack ESM module records) and frozen in some bundle shapes. Vue 3 no
// longer attaches a `_Ctor` cache the way Vue 2's `Vue.extend()` did, but the
// router and the renderer still write bookkeeping onto component options, so
// handing them an extensible copy keeps this independent of bundle shape.
const RoutePageRenderer = { ...CnPageRenderer }

/**
 * Merge incoming menu items onto a target list by `id`. A top-level or child
 * entry already present is extended (only filling undefined scalar keys, then
 * recursing into children); a new entry is appended. Used by the relocation
 * pass to re-home leaves/groups into a target group without duplicating ids.
 *
 * @param {Array<object>} target The accumulated menu (mutated in place).
 * @param {Array<object>} incoming Menu items to merge in.
 * @return {void}
 */
function mergeMenuItems(target, incoming) {
	incoming.forEach((item) => {
		const existing = target.find((t) => t.id === item.id)
		if (!existing) {
			target.push({
				...item,
				children: Array.isArray(item.children)
					? [...item.children]
					: item.children,
			})
			return
		}
		for (const key of [
			'label',
			'icon',
			'route',
			'order',
			'section',
			'featureFlag',
			'permission',
			'visibleIf',
			'href',
			'action',
		]) {
			if (existing[key] === undefined && item[key] !== undefined) {
				existing[key] = item[key]
			}
		}
		if (Array.isArray(item.children) && item.children.length > 0) {
			if (!Array.isArray(existing.children)) {
				existing.children = []
			}
			mergeMenuItems(existing.children, item.children)
		}
	})
}

/**
 * Re-home merged menu entries onto the canonical navigation layout declared
 * by `src/menu-layout.json#relocations` (`{ sourceId: targetGroupId }`).
 *
 *  - A relocated GROUP dissolves: its children merge (by id) into the
 *    target group and the now-empty shell is dropped.
 *  - A relocated LEAF (top-level or child of any group) moves under the
 *    target group.
 *  - A child group relocated onto its own parent flattens into it.
 *  - Unknown source ids are inert; a missing target group keeps the entry
 *    at the top level so nothing silently disappears.
 *
 * Runs in passes until stable (children freed by a dissolved group can
 * themselves be relocated on the next pass).
 *
 * @param {Array<object>} menu The merged menu (mutated in place).
 * @param {Record<string, string>|undefined} relocations Source-id → target-group-id map.
 * @return {Array<object>} The menu with relocations applied.
 */
function applyMenuRelocations(menu, relocations) {
	if (!relocations || typeof relocations !== 'object') return menu
	for (let pass = 0; pass < 5; pass++) {
		const moves = []
		for (let i = menu.length - 1; i >= 0; i--) {
			const node = menu[i]
			const target = relocations[node.id]
			if (target && target !== node.id) {
				menu.splice(i, 1)
				moves.push({ node, target })
				continue
			}
			if (!Array.isArray(node.children)) continue
			for (let j = node.children.length - 1; j >= 0; j--) {
				const child = node.children[j]
				const childTarget = relocations[child.id]
				if (!childTarget) continue
				if (childTarget === node.id && !Array.isArray(child.children))
					continue
				node.children.splice(j, 1)
				moves.push({ node: child, target: childTarget })
			}
		}
		if (moves.length === 0) break
		moves.forEach(({ node, target }) => {
			const group = menu.find((m) => m.id === target)
			if (!group) {
				menu.push(node)
				return
			}
			if (!Array.isArray(group.children)) group.children = []
			if (Array.isArray(node.children)) {
				mergeMenuItems(group.children, node.children)
			} else {
				mergeMenuItems(group.children, [node])
			}
		})
	}
	return menu.filter(
		(m) =>
			m.type === 'caption'
			|| m.route
			|| m.href
			|| m.action
			|| (Array.isArray(m.children) && m.children.length > 0),
	)
}

/**
 * Assign top-level menu leaves to a navigation section declared by
 * `src/menu-layout.json#sections` (`{ menuEntryId: sectionName }`). Only
 * top-level entries are sectioned; unknown ids are inert.
 *
 * @param {Array<object>} menu The merged menu (mutated in place).
 * @param {Record<string, string>|undefined} sections Menu-entry id → section name.
 * @return {Array<object>} The menu with sections applied.
 */
function applyMenuSections(menu, sections) {
	if (!sections || typeof sections !== 'object') return menu
	menu.forEach((node) => {
		const section = sections[node.id]
		if (section) node.section = section
	})
	return menu
}

/**
 * Remove individual menu entries by id after relocation — used to retire
 * duplicate navigation entries whose PAGE must stay routable (deep links
 * and e2e specs hit the route directly). Declared in
 * `src/menu-layout.json#removals`. Only leaf entries are removed; group ids
 * are ignored so a removal can never silently hide a whole cluster.
 *
 * @param {Array<object>} menu The merged menu (mutated in place).
 * @param {Array<string>|undefined} removals Menu-entry ids to drop.
 * @return {Array<object>} The menu without the removed entries.
 */
function applyMenuRemovals(menu, removals) {
	if (!Array.isArray(removals) || removals.length === 0) return menu
	const drop = new Set(removals)
	const isLeaf = (n) => !Array.isArray(n.children) || n.children.length === 0
	menu.forEach((node) => {
		if (Array.isArray(node.children)) {
			node.children = node.children.filter(
				(c) => !(drop.has(c.id) && isLeaf(c)),
			)
		}
	})
	return menu.filter((node) => !(drop.has(node.id) && isLeaf(node)))
}

/**
 * Promote the menu entries listed in `src/menu-layout.json#settingsSection`
 * into Nextcloud's settings foldout — the NcAppNavigationSettings gear at the
 * bottom-left of the navigation, OUTSIDE the scrollable list. CnAppNav renders
 * every TOP-LEVEL item carrying `section: "settings"` as a flat entry inside
 * that foldout (with an auto-prepended "Personal settings"). This lifts each
 * listed id out of wherever it currently sits, tags it `section: "settings"`,
 * flattens it (the foldout has no nested groups), and appends it to the top
 * level. Empty non-clickable groups left behind are dropped; a clickable group
 * (one with route/href/action) is kept.
 *
 * @param {Array<object>} menu        The merged + relocated + pruned menu.
 * @param {Array<string>|undefined} settingsIds Entry ids to move to the foldout.
 * @return {Array<object>} The menu with the settings entries lifted out.
 */
function applySettingsSection(menu, settingsIds) {
	if (!Array.isArray(settingsIds) || settingsIds.length === 0) return menu
	const want = new Set(settingsIds)
	const isClickable = (n) =>
		n.route !== undefined || n.href !== undefined || n.action !== undefined
	const lifted = []
	const strip = (nodes) =>
		nodes.reduce((acc, n) => {
			if (want.has(n.id)) {
				const { children, ...leaf } = n
				lifted.push({ ...leaf, section: 'settings' })
				return acc
			}
			if (Array.isArray(n.children)) {
				const children = strip(n.children)
				if (
					children.length === 0
					&& n.children.length > 0
					&& !isClickable(n)
				)
					return acc
				acc.push({ ...n, children })
				return acc
			}
			acc.push(n)
			return acc
		}, [])
	const remaining = strip(menu)
	return [...remaining, ...lifted]
}

/**
 * Apply the canonical navigation layout (src/menu-layout.json) to the bundled
 * manifest's menu and return a shallow-cloned manifest carrying the rewritten
 * menu. DocuDesk ships a monolithic manifest (no manifest.d fragments), so the
 * four canonical passes run directly on a deep copy of `manifest.menu`:
 * relocations → sections → removals → settingsSection. The manifest stays the
 * source of WHAT exists (ADR-037); menu-layout.json is the single place deciding
 * WHERE entries live, including which config/admin entries fold into the
 * Nextcloud settings gear (applySettingsSection). Currently every map in
 * menu-layout.json is empty (DocuDesk's in-app menu is wholly operational and
 * its admin surface lives in NC's settings framework), so this is an identity
 * transform today — the wiring exists for fleet parity so any future in-menu
 * config entry folds in by listing its id in menu-layout.json alone.
 *
 * @param {object} manifest The bundled base manifest (with `menu[]`).
 * @return {object} A manifest clone whose `menu` reflects the canonical layout.
 */
function applyMenuLayout(manifest) {
	let menu = JSON.parse(JSON.stringify(manifest.menu || []))
	menu = applyMenuRelocations(menu, menuLayout.relocations)
	menu = applyMenuSections(menu, menuLayout.sections)
	menu = applyMenuRemovals(menu, menuLayout.removals)
	menu = applySettingsSection(menu, menuLayout.settingsSection)
	return { ...manifest, menu }
}

const manifest = applyMenuLayout(bundledManifest)

/**
 * Build the vue-router config from the manifest. Each manifest page
 * becomes one route; the route's `name` IS `page.id` (per the lib's
 * manifest contract). Routes whose path declares a `:` parameter pass
 * `props: true` so the renderer receives params as props.
 *
 * @param {object} manifest The bundled manifest (with `pages[]`).
 * @return {Array<object>} vue-router 4 routes config.
 */
function routesFromManifest(manifest) {
	const routes = manifest.pages.map((page) => ({
		name: page.id,
		path: page.route,
		component: RoutePageRenderer,
		props: page.route.includes(':'),
	}))
	// Catch-all redirect to dashboard. vue-router 4 REMOVED the bare `'*'`
	// path — it matches nothing and throws no error, so the shell renders
	// with an empty <main> on any unknown route. The named-param form below
	// is the v4 replacement.
	routes.push({ path: '/:pathMatch(.*)*', redirect: '/' })
	return routes
}

const router = createRouter({
	history: createWebHistory(generateUrl('/apps/docudesk')),
	routes: routesFromManifest(manifest),
})

tryLoadTranslations()

// Pass shallow copies of the registry maps to CnAppRoot. The lib exports
// `defaultPageTypes` (and the app's component maps) as frozen module
// objects in some bundle shapes — Vue 2's `Vue.extend()` mutates component
// definitions to attach an internal `_Ctor` cache, which throws against a
// frozen source map. Cloning here yields extensible objects.
//
// `customComponents` is derived from the v2 registry's `kind:"page"`
// entries because CnPageRenderer's `type:"custom"` dispatch path still
// resolves through `customComponents` (see ADR-036 transition notes).
// Future modal / widget entries in `registry.js` are consumed directly
// from the `registry` prop by CnAppRoot.
const pageTypesProp = { ...defaultPageTypes }
const registryProp = { ...registry }
const customComponentsProp = Object.fromEntries(
	Object.entries(registryProp)
		.filter(([, entry]) => entry && entry.kind === 'page')
		.map(([key, entry]) => [key, entry.component]),
)

// Phase 1 of the store migration (openspec/changes/docudesk-store-migration):
// pre-register the seven OR-backed object types against the lib's
// useObjectStore so any consumer (manifest pages, sub-resource plugins,
// future OR-backed code paths) binds to a known-shape store. Fire-and-
// forget — registration is synchronous after the settings fetch resolves,
// and the mount stays unblocked even if the boot path fails.
// Called BEFORE mount per REQ-DSM-6 (spec: openspec/specs/docudesk-store-migration/spec.md).
try {
	const result = initializeStores()
	if (result && typeof result.then === 'function') {
		result.then(
			() => {},
			(e) => {
				// eslint-disable-next-line no-console
				console.warn('[docudesk] initializeStores failed', e)
			},
		)
	}
} catch (e) {
	// eslint-disable-next-line no-console
	console.warn('[docudesk] initializeStores threw synchronously', e)
}

// Create and mount the app immediately so the App renders.
//
// ⚠️ Mount target is `#docudesk-app`, NOT `#content`. Vue 2's `$mount()`
// REPLACED the matched element, so mounting on templates/index.php's
// `<div id="content">` quietly replaced Nextcloud's own `#content` wrapper
// from layout.user.php and the duplicate id never showed. Vue 3's `mount()`
// renders INSIDE the match, so the app would end up nested in core's wrapper
// — and with two `#content` elements it is undefined which one is matched.
// A dedicated host id removes the ambiguity entirely.
const app = createApp({
	render: () =>
		h(App, {
			manifest,
			customComponents: customComponentsProp,
			pageTypes: pageTypesProp,
			registry: registryProp,
		}),
})

app.mixin({ methods: { t, n } })
app.use(pinia)
app.use(router)
app.mount('#docudesk-app')
