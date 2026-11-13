/**
 * The SPA entry: the bundled manifest's menu reshaped by menu-layout.json
 * (relocations, sections, removals, the settings foldout), a route per
 * manifest page under the app's base path, the app mounted with the page
 * components of the registry, the stores initialised and the leaves
 * registered, with every failure at boot logged instead of thrown.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/filinq-adopt-buildmanifest-pipeline/specs/menu-architecture/spec.md
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const s = vi.hoisted(() => ({}))

/**
 * @param {string} name the recorded call
 * @return {Function} a function that records its name and arguments
 */
function recorder(name) {
	return (...args) => { s.calls.push([name, ...args]) }
}

vi.mock('@conduction/nextcloud-vue', () => ({
	CnPageRenderer: { name: 'CnPageRenderer' },
	get defaultPageTypes() { return { index: 'IndexPage' } },
	registerBuiltinDashboardWidgets: () => s.calls.push(['registerBuiltinDashboardWidgets']),
	registerIcons: (icons) => s.calls.push(['registerIcons', icons]),
	registerTranslations: () => s.registerTranslations(),
}))
vi.mock('@conduction/nextcloud-vue/css/index.css', () => ({}))
vi.mock('gridstack/dist/gridstack.min.css', () => ({}))
vi.mock('../../src/assets/fonts.css', () => ({}))
vi.mock('../../src/assets/app.css', () => ({}))
vi.mock('../../src/setPublicPath.js', () => ({}))
vi.mock('@nextcloud/l10n', () => ({
	loadTranslations: (...args) => s.loadTranslations(...args),
	translate: (app, text) => text,
	translatePlural: (app, one) => one,
}))
vi.mock('vue', () => ({
	h: (component, props) => ({ component, props }),
	createApp: (root) => {
		s.app = { root, mixins: [], plugins: [], mountedOn: null }
		s.app.mixin = (m) => { s.app.mixins.push(m); return s.app }
		s.app.use = (p) => { s.app.plugins.push(p); return s.app }
		s.app.mount = (el) => { s.app.mountedOn = el; s.calls.push(['mount', el]); return s.app }
		return s.app
	},
}))
vi.mock('vue-router', () => ({
	createWebHistory: (base) => ({ base }),
	createRouter: (options) => { s.routerOptions = options; return { id: 'router' } },
}))
vi.mock('../../src/App.vue', () => ({ default: { name: 'App' } }))
vi.mock('../../src/icons.js', () => ({ default: { Folder: 'FolderIcon' } }))
vi.mock('../../src/pinia.js', () => ({ default: { id: 'pinia' } }))
vi.mock('../../src/manifest.json', () => ({ get default() { return s.manifest } }))
vi.mock('../../src/menu-layout.json', () => ({ get default() { return s.layout } }))
vi.mock('../../src/registry.js', () => ({
	default: {
		DashboardIndex: { kind: 'page', component: 'DashboardComponent' },
		DossierFormModal: { kind: 'modal', component: 'ModalComponent' },
		Broken: null,
	},
}))
vi.mock('../../src/services/menuRelocations.js', () => ({
	applyMenuRelocations: (menu, relocations) => { s.calls.push(['relocate', relocations]); return menu },
}))
vi.mock('../../src/store/store.js', () => ({ initializeStores: () => s.initializeStores() }))
vi.mock('../../src/integrations/registerDocumentsLeaf.js', () => ({ registerDocumentsLeaf: recorder('documents') }))
vi.mock('../../src/integrations/registerMergeToPdfLeaf.js', () => ({ registerMergeToPdfLeaf: recorder('mergeToPdf') }))
vi.mock('../../src/integrations/registerDownloadAllFilesLeaf.js', () => ({ registerDownloadAllFilesLeaf: recorder('downloadAllFiles') }))
vi.mock('../../src/integrations/registerSigningFolderLeaf.js', () => ({ registerSigningFolderLeaf: recorder('signingFolder') }))
vi.mock('../../src/integrations/registerDocumentIntakeLeaf.js', () => ({ registerDocumentIntakeLeaf: recorder('documentIntake') }))

let warn

beforeEach(() => {
	vi.resetModules()
	s.calls = []
	s.app = null
	s.routerOptions = null
	s.layout = { relocations: {}, sections: {}, removals: [], settingsSection: [] }
	s.manifest = {
		version: '1',
		menu: [{ id: 'dashboard', route: 'Dashboard' }],
		pages: [
			{ id: 'Dashboard', route: '/' },
			{ id: 'DossierDetail', route: '/dossiers/:id' },
		],
	}
	s.registerTranslations = () => s.calls.push(['registerTranslations'])
	s.loadTranslations = (...args) => { s.calls.push(['loadTranslations', ...args]); return Promise.resolve() }
	s.initializeStores = () => { s.calls.push(['initializeStores']); return Promise.resolve() }
	warn = vi.spyOn(console, 'warn').mockImplementation(() => {})
	vi.stubGlobal('window', { location: { pathname: '/index.php/apps/filinq/dossiers/7' } })
})

afterEach(() => {
	warn.mockRestore()
	vi.unstubAllGlobals()
})

/**
 * @return {Promise<object>} the props App is rendered with
 */
async function boot() {
	await import('../../src/main.js')
	await new Promise((resolve) => setTimeout(resolve, 0))
	return s.app.root.render().props
}

describe('booting the SPA', () => {
	it('registers icons, widgets and translations, then mounts and registers the leaves in that order', async () => {
		await boot()
		expect(s.calls.map((c) => c[0])).toEqual([
			'registerIcons', 'registerBuiltinDashboardWidgets', 'registerTranslations',
			'relocate', 'loadTranslations', 'initializeStores', 'mount',
			'documents', 'mergeToPdf', 'downloadAllFiles', 'signingFolder', 'documentIntake',
		])
		expect(s.calls[0][1]).toEqual({ Folder: 'FolderIcon' })
		expect(s.calls.find((c) => c[0] === 'loadTranslations')[1]).toBe('filinq')
		expect(s.app.mountedOn).toBe('#filinq-app')
		expect(s.app.plugins).toEqual([{ id: 'pinia' }, { id: 'router' }])
		expect(Object.keys(s.app.mixins[0].methods)).toEqual(['t', 'n'])
		expect(warn).not.toHaveBeenCalled()
	})

	it('renders App with the manifest, the page components of the registry, the page types and the registry', async () => {
		const props = await boot()
		expect(s.app.root.render().component).toEqual({ name: 'App' })
		expect(props.customComponents).toEqual({ DashboardIndex: 'DashboardComponent' })
		expect(props.pageTypes).toEqual({ index: 'IndexPage' })
		expect(Object.keys(props.registry)).toEqual(['DashboardIndex', 'DossierFormModal', 'Broken'])
		expect(props.manifest).toEqual(s.manifest)
		expect(props.manifest.menu).not.toBe(s.manifest.menu)
	})

	it('routes every page through the page renderer, passes params as props, and sends the rest home', async () => {
		await boot()
		expect(s.routerOptions.history).toEqual({ base: '/index.php/apps/filinq' })
		expect(s.routerOptions.routes).toEqual([
			{ name: 'Dashboard', path: '/', component: { name: 'CnPageRenderer' }, props: false },
			{ name: 'DossierDetail', path: '/dossiers/:id', component: { name: 'CnPageRenderer' }, props: true },
			{ path: '/:pathMatch(.*)*', redirect: '/' },
		])
	})

	it('falls back to the generated app url as the base outside the app path', async () => {
		window.location.pathname = '/somewhere/else'
		await boot()
		expect(s.routerOptions.history).toEqual({ base: '/index.php/apps/filinq' })
		window.location.pathname = '/apps-extra/apps/filinq'
		vi.resetModules()
		await boot()
		expect(s.routerOptions.history).toEqual({ base: '/apps-extra/apps/filinq' })
	})

	it('logs each boot failure and still mounts', async () => {
		s.registerTranslations = () => { throw new Error('no catalogue') }
		s.loadTranslations = () => { throw new Error('sync l10n failure') }
		s.initializeStores = () => Promise.reject(new Error('settings offline'))
		await boot()
		expect(warn.mock.calls.map((c) => [c[0], c[1].message])).toEqual([
			['[filinq] registerTranslations failed; falling back to English', 'no catalogue'],
			['[filinq] initializeStores failed', 'settings offline'],
		])
		expect(s.app.mountedOn).toBe('#filinq-app')
	})

	it('logs a store setup that throws synchronously, and ignores a rejected translation load', async () => {
		s.loadTranslations = () => Promise.reject(new Error('404'))
		s.initializeStores = () => { throw new Error('no pinia') }
		await boot()
		expect(warn.mock.calls.map((c) => [c[0], c[1].message])).toEqual([['[filinq] initializeStores threw synchronously', 'no pinia']])
		expect(s.app.mountedOn).toBe('#filinq-app')
	})

	it('accepts a store setup and a translation load that return nothing', async () => {
		s.loadTranslations = () => undefined
		s.initializeStores = () => undefined
		await boot()
		expect(warn).not.toHaveBeenCalled()
		expect(s.app.mountedOn).toBe('#filinq-app')
	})
})

describe('the menu layout', () => {
	it('hands the relocations over, then puts top-level entries in their section', async () => {
		s.manifest.menu = [{ id: 'docs' }, { id: 'roadmap' }, { id: 'dashboard' }, { id: 'group', children: [{ id: 'docs' }] }]
		s.layout = { relocations: { a: 'b' }, sections: { docs: 'footer', roadmap: 'footer', unknown: 'footer' }, removals: [], settingsSection: [] }
		const { manifest } = await boot()
		expect(s.calls.find((c) => c[0] === 'relocate')[1]).toEqual({ a: 'b' })
		expect(manifest.menu).toEqual([
			{ id: 'docs', section: 'footer' },
			{ id: 'roadmap', section: 'footer' },
			{ id: 'dashboard' },
			{ id: 'group', children: [{ id: 'docs' }] },
		])
		expect(s.manifest.menu[0]).toEqual({ id: 'docs' })
	})

	it('removes leaf entries by id at either level, but never a group with children', async () => {
		s.manifest.menu = [
			{ id: 'old-list' },
			{ id: 'group', children: [{ id: 'keep' }, { id: 'old-child' }, { id: 'nested', children: [{ id: 'x' }] }] },
			{ id: 'empty-group', children: [] },
		]
		s.layout = { sections: {}, removals: ['old-list', 'old-child', 'nested', 'group', 'empty-group'], settingsSection: [] }
		const { manifest } = await boot()
		expect(manifest.menu).toEqual([
			{ id: 'group', children: [{ id: 'keep' }, { id: 'nested', children: [{ id: 'x' }] }] },
		])
	})

	it('lifts the listed entries into the settings section at the end, dropping groups they empty unless clickable', async () => {
		s.manifest.menu = [
			{ id: 'dashboard', route: 'Dashboard' },
			{ id: 'admin', children: [{ id: 'config', route: 'Config', children: [{ id: 'deep' }] }] },
			{ id: 'tools', route: 'Tools', children: [{ id: 'integrations', href: '/x' }] },
			{ id: 'reports', children: [{ id: 'monthly', route: 'Monthly' }, { id: 'yearly', route: 'Yearly' }] },
			{ id: 'audit', route: 'Audit' },
		]
		s.layout = { sections: null, removals: null, settingsSection: ['config', 'integrations', 'yearly', 'audit'] }
		const { manifest } = await boot()
		expect(manifest.menu).toEqual([
			{ id: 'dashboard', route: 'Dashboard' },
			{ id: 'tools', route: 'Tools', children: [] },
			{ id: 'reports', children: [{ id: 'monthly', route: 'Monthly' }] },
			{ id: 'config', route: 'Config', section: 'settings' },
			{ id: 'integrations', href: '/x', section: 'settings' },
			{ id: 'yearly', route: 'Yearly', section: 'settings' },
			{ id: 'audit', route: 'Audit', section: 'settings' },
		])
	})

	it('keeps an emptied group that has an action of its own', async () => {
		s.manifest.menu = [{ id: 'more', action: 'open', children: [{ id: 'gear' }] }]
		s.layout = { settingsSection: ['gear'] }
		const { manifest } = await boot()
		expect(manifest.menu).toEqual([{ id: 'more', action: 'open', children: [] }, { id: 'gear', section: 'settings' }])
	})

	it('treats a manifest without a menu as an empty one', async () => {
		delete s.manifest.menu
		const { manifest } = await boot()
		expect(manifest.menu).toEqual([])
	})
})
