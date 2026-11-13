/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

import {
	buildManifest,
	CnPageRenderer,
	defaultPageTypes,
	registerBuiltinDashboardWidgets,
	registerIcons,
	registerTranslations,
} from '@conduction/nextcloud-vue'
import {
	loadTranslations,
	translatePlural as n,
	translate as t,
} from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { createApp, h } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import appIcons from './icons.js'
import { registerDocumentIntakeLeaf } from './integrations/registerDocumentIntakeLeaf.js'
import { registerDocumentsLeaf } from './integrations/registerDocumentsLeaf.js'
import { registerDownloadAllFilesLeaf } from './integrations/registerDownloadAllFilesLeaf.js'
import { registerMergeToPdfLeaf } from './integrations/registerMergeToPdfLeaf.js'
import { registerSigningFolderLeaf } from './integrations/registerSigningFolderLeaf.js'
import bundledManifest from './manifest.json'
import fragments from './manifestFragments.js'
import menuLayout from './menu-layout.json'
import pinia from './pinia.js'
import registry from './registry.js'
import { initializeStores } from './store/store.js'

// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ before
// any CSS or asset/resource URL or lazy chunk URL is evaluated. See
// setPublicPath.js — filinq lives under apps-extra, not the baked-in /apps/.
import './setPublicPath.js'
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
	console.warn('[filinq] registerTranslations failed; falling back to English', e)
}

// Fire-and-forget translation load. Some Nextcloud installs only allow the
// JS/CSS allowlist through Apache; /custom_apps/<app>/l10n/<locale>.json
// may 404. Strings fall back to English source on miss; boot must not
// depend on this resolving.
/**
 *
 */
function tryLoadTranslations() {
	try {
		const result = loadTranslations('filinq', () => {})
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

// The effective manifest: the bundled base, its manifest.d fragments (ADR-037)
// and the canonical navigation layout (src/menu-layout.json), assembled by the
// shared pipeline (ADR-044) rather than a local copy of it. The manifest stays
// the source of WHAT exists; menu-layout.json decides WHERE entries live.
const manifest = buildManifest(bundledManifest, fragments, menuLayout)

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

/**
 * The router base for THIS page load.
 *
 * ⚠️ `generateUrl('/apps/filinq')` alone is not enough. Nextcloud serves the
 * app under BOTH `/apps/filinq/...` and `/index.php/apps/filinq/...`, but
 * `generateUrl()` returns only the form the instance is configured for. A
 * visitor arriving on the other form — a bookmark, an emailed deep link, an
 * integration that hardcodes `/index.php` — has a pathname the router cannot
 * strip its base from. No route matches, the catch-all takes over, and they
 * land on the dashboard with no error at all: the deep link is silently
 * swallowed.
 *
 * Measured on a live instance for learniq, across all 282 of its routes:
 * `/apps/learniq/courses` resolved to Courses, `/index.php/apps/learniq/courses`
 * resolved to the dashboard. Every route behaved the same way, so this is not
 * one broken page but every deep link in that URL form.
 *
 * Deriving the base from the pathname makes both forms resolve, because the
 * base then always matches the URL the visitor actually arrived on.
 *
 * @return {string} The base path vue-router should strip from the URL.
 */
function routerBase() {
	const match = window.location.pathname.match(/^(.*\/apps\/filinq)(?:\/|$)/)
	return match ? match[1] : generateUrl('/apps/filinq')
}

const router = createRouter({
	history: createWebHistory(routerBase()),
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

// Phase 1 of the store migration (openspec/changes/filinq-store-migration):
// pre-register the seven OR-backed object types against the lib's
// useObjectStore so any consumer (manifest pages, sub-resource plugins,
// future OR-backed code paths) binds to a known-shape store. Fire-and-
// forget — registration is synchronous after the settings fetch resolves,
// and the mount stays unblocked even if the boot path fails.
// Called BEFORE mount per REQ-DSM-6 (spec: openspec/specs/filinq-store-migration/spec.md).
try {
	const result = initializeStores()
	if (result && typeof result.then === 'function') {
		result.then(
			() => {},
			(e) => {
				// eslint-disable-next-line no-console
				console.warn('[filinq] initializeStores failed', e)
			},
		)
	}
} catch (e) {
	// eslint-disable-next-line no-console
	console.warn('[filinq] initializeStores threw synchronously', e)
}

// Create and mount the app immediately so the App renders.
//
// ⚠️ Mount target is `#filinq-app`, NOT `#content`. Vue 2's `$mount()`
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
app.mount('#filinq-app')

// Register Filinq's own OpenRegister leaves on Filinq's pages.
//
// On OTHER apps' pages the same registration arrives through the separate
// `filinq-leaves` bundle, which OpenRegister enqueues. Here it rides along with
// the SPA, because this bundle is already on the page and a second script would
// register the id twice.
registerDocumentsLeaf()
registerMergeToPdfLeaf()
registerDownloadAllFilesLeaf()
registerSigningFolderLeaf()
registerDocumentIntakeLeaf()
