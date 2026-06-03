/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

import Vue from 'vue'
import VueRouter from 'vue-router'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n, loadTranslations } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	CnPageRenderer,
	defaultPageTypes,
	registerIcons,
	registerTranslations,
} from '@conduction/nextcloud-vue'
import pinia from './pinia.js'
import App from './App.vue'
import bundledManifest from './manifest.json'
import registry from './registry.js'
import { initializeStores } from './store/store.js'

// Library CSS — must be explicit import (webpack tree-shakes side-effect imports from aliased packages)
import '@conduction/nextcloud-vue/css/index.css'
import './assets/app.css'

Vue.mixin({ methods: { t, n } })
Vue.use(PiniaVuePlugin)
Vue.use(VueRouter)

// Register library-side icon set + lib translations once at bootstrap.
registerIcons()
try {
	registerTranslations()
} catch (e) {
	// Non-fatal — lib translations fall back to English source.
	// eslint-disable-next-line no-console
	console.warn('[docudesk] registerTranslations failed; falling back to English', e)
}

// Fire-and-forget translation load. Some Nextcloud installs only allow the
// JS/CSS allowlist through Apache; /custom_apps/<app>/l10n/<locale>.json
// may 404. Strings fall back to English source on miss; boot must not
// depend on this resolving.
function tryLoadTranslations() {
	try {
		const result = loadTranslations('docudesk', () => {})
		if (result && typeof result.then === 'function') {
			result.then(() => {}, () => {})
		}
	} catch {
		// no-op
	}
}

// Shallow-clone CnPageRenderer because the lib's barrel exports are
// non-extensible (webpack ESM module records). Vue 2's `Vue.extend()`
// adds an internal `_Ctor` cache to the component definition; mutating
// a non-extensible export throws "Cannot add property _Ctor, object is
// not extensible". Cloning gives Vue Router an extensible
// component-options object without altering the lib's internals.
const RoutePageRenderer = { ...CnPageRenderer }

/**
 * Build the vue-router config from the manifest. Each manifest page
 * becomes one route; the route's `name` IS `page.id` (per the lib's
 * manifest contract). Routes whose path declares a `:` parameter pass
 * `props: true` so the renderer receives params as props.
 *
 * @param {object} manifest The bundled manifest (with `pages[]`).
 * @return {Array<object>} vue-router 3 routes config.
 */
function routesFromManifest(manifest) {
	const routes = manifest.pages.map((page) => ({
		name: page.id,
		path: page.route,
		component: RoutePageRenderer,
		props: page.route.includes(':'),
	}))
	// Catch-all redirect to dashboard.
	routes.push({ path: '*', redirect: '/' })
	return routes
}

const router = new VueRouter({
	mode: 'history',
	base: generateUrl('/apps/docudesk'),
	routes: routesFromManifest(bundledManifest),
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
		result.then(() => {}, (e) => {
			// eslint-disable-next-line no-console
			console.warn('[docudesk] initializeStores failed', e)
		})
	}
} catch (e) {
	// eslint-disable-next-line no-console
	console.warn('[docudesk] initializeStores threw synchronously', e)
}

// Create and mount Vue instance immediately so the App renders.
new Vue({
	pinia,
	router,
	render: (h) => h(App, {
		props: {
			manifest: bundledManifest,
			customComponents: customComponentsProp,
			pageTypes: pageTypesProp,
			registry: registryProp,
		},
	}),
}).$mount('#content')
