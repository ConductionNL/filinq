/**
 * The small entry bundles: the leaves bundle other apps load, the compare
 * bundle, the webpack runtime bootstrap, the admin settings mount and the
 * dashboard widgets. Each is a module with side effects, so every test
 * imports a fresh copy against recording stubs.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/filinq-store-migration/spec.md
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const rec = vi.hoisted(() => ({ calls: [], apps: [], translations: null }))

/**
 * @param {string} name the recorded call
 * @return {Function} a function that records its name and arguments
 */
function recorder(name) {
	return (...args) => { rec.calls.push([name, ...args]) }
}

vi.mock('@nextcloud/l10n', () => ({
	loadTranslations: (...args) => { rec.calls.push(['loadTranslations', ...args]); return rec.translations() },
	translate: (app, text) => text,
	translatePlural: (app, one) => one,
}))
vi.mock('@nextcloud/router', () => ({
	generateFilePath: (app, type, file) => `/apps-extra/${app}/${file}`,
	generateUrl: (url) => '/index.php' + url,
}))
vi.mock('vue', () => ({
	h: (component, props) => ({ rendered: component, props }),
	createApp: (root, props) => {
		const app = { root, props, mixins: [], plugins: [], mountedOn: null, unmounted: false }
		app.mixin = (m) => { app.mixins.push(m); return app }
		app.use = (p) => { app.plugins.push(p); return app }
		app.mount = (el) => { app.mountedOn = el; return app }
		app.unmount = () => { app.unmounted = true }
		rec.apps.push(app)
		return app
	},
}))
vi.mock('@nextcloud/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }))
vi.mock('../../src/pinia.js', () => ({ default: { id: 'pinia' } }))
vi.mock('../../src/assets/fonts.css', () => ({}))
vi.mock('../../src/views/settings/Settings.vue', () => ({ default: { name: 'AdminSettings' } }))
vi.mock('../../src/views/anonymization/AnonymizationWidget.vue', () => ({ default: { name: 'AnonymizationWidget' } }))
vi.mock('../../src/views/widgets/FileEntitiesDashboardWidget.vue', () => ({ default: { name: 'FileEntitiesDashboardWidget' } }))
vi.mock('../../src/components/compare/DocumentCompare.vue', () => ({ default: { name: 'DocumentCompare' } }))
vi.mock('../../src/integrations/registerDocumentsLeaf.js', () => ({ registerDocumentsLeaf: recorder('documents') }))
vi.mock('../../src/integrations/registerMergeToPdfLeaf.js', () => ({ registerMergeToPdfLeaf: recorder('mergeToPdf') }))
vi.mock('../../src/integrations/registerDownloadAllFilesLeaf.js', () => ({ registerDownloadAllFilesLeaf: recorder('downloadAllFiles') }))
vi.mock('../../src/integrations/registerSigningFolderLeaf.js', () => ({ registerSigningFolderLeaf: recorder('signingFolder') }))
vi.mock('../../src/integrations/registerDocumentIntakeLeaf.js', () => ({ registerDocumentIntakeLeaf: recorder('documentIntake') }))

let dashboardRegistrations

beforeEach(() => {
	vi.resetModules()
	rec.calls.length = 0
	rec.apps.length = 0
	rec.translations = () => Promise.resolve()
	dashboardRegistrations = {}
	vi.stubGlobal('OC', { requestToken: 'token-123' })
	vi.stubGlobal('OCA', { Dashboard: { register: (id, cb) => { dashboardRegistrations[id] = cb } } })
	vi.stubGlobal('window', {})
	globalThis.__webpack_nonce__ = undefined
	globalThis.__webpack_public_path__ = undefined
})

afterEach(() => {
	vi.unstubAllGlobals()
	delete globalThis.__webpack_nonce__
	delete globalThis.__webpack_public_path__
})

describe('the leaves bundle', () => {
	it('registers every leaf before it asks for the translations', async () => {
		await import('../../src/leaves.js')
		expect(rec.calls.map((c) => c[0])).toEqual(['documents', 'mergeToPdf', 'downloadAllFiles', 'signingFolder', 'documentIntake', 'loadTranslations'])
		expect(rec.calls[5][1]).toBe('filinq')
	})

	it('swallows a missing catalogue instead of leaving an unhandled rejection', async () => {
		let caught = null
		const rejected = Promise.reject(new Error('404'))
		const original = rejected.catch.bind(rejected)
		rejected.catch = (handler) => { caught = handler; return original(handler) }
		rec.translations = () => rejected
		await import('../../src/leaves.js')
		expect(typeof caught).toBe('function')
		expect(caught(new Error('404'))).toBeUndefined()
	})
})

describe('the webpack runtime bootstrap', () => {
	it('sets the CSP nonce from the request token and the public path to the app js folder', async () => {
		await import('../../src/setPublicPath.js')
		expect(globalThis.__webpack_nonce__).toBe(btoa('token-123'))
		expect(globalThis.__webpack_public_path__).toBe('/apps-extra/filinq/js/')
	})
})

describe('the compare bundle', () => {
	it('exposes mountCompare on OCA.Filinq, keeping what is already there', async () => {
		window.OCA = { Filinq: { other: 1 } }
		await import('../../src/compare.js')
		expect(window.OCA.Filinq.other).toBe(1)
		expect(typeof window.OCA.Filinq.mountCompare).toBe('function')
		expect(rec.calls.find((c) => c[0] === 'loadTranslations')).toEqual(['loadTranslations', 'filinq'])
		expect(globalThis.__webpack_public_path__).toBe('/apps-extra/filinq/js/')
	})

	it('mounts the compare view with the given files into the element, and unmounts it once', async () => {
		await import('../../src/compare.js')
		const el = { nodeType: 1 }
		const original = { path: '/a.pdf' }
		const delivered = { url: '/b.pdf' }
		const handle = window.OCA.Filinq.mountCompare(el, { original, delivered })
		expect(rec.apps).toHaveLength(1)
		expect(rec.apps[0].root).toEqual({ name: 'DocumentCompare' })
		expect(rec.apps[0].props).toEqual({ original, delivered, labels: {} })
		expect(rec.apps[0].mountedOn).toBe(el)
		handle.unmount()
		expect(rec.apps[0].unmounted).toBe(true)
	})

	it('refuses an element that is not one, before mounting anything', async () => {
		await import('../../src/compare.js')
		expect(() => window.OCA.Filinq.mountCompare(null, { original: { path: 'a' }, delivered: { path: 'b' } })).toThrow(TypeError)
		expect(rec.apps).toHaveLength(0)
	})

	it('swallows a missing catalogue', async () => {
		rec.translations = () => Promise.reject(new Error('404'))
		await expect(import('../../src/compare.js')).resolves.toBeDefined()
		await new Promise((resolve) => setTimeout(resolve, 0))
	})
})

describe('the admin settings mount', () => {
	it('renders the settings view with t, n and pinia into #admin-settings', async () => {
		await import('../../src/settings.js')
		expect(rec.apps).toHaveLength(1)
		const app = rec.apps[0]
		expect(app.root.render()).toEqual({ rendered: { name: 'AdminSettings' }, props: undefined })
		expect(Object.keys(app.mixins[0].methods)).toEqual(['t', 'n'])
		expect(app.plugins).toEqual([{ id: 'pinia' }])
		expect(app.mountedOn).toBe('#admin-settings')
		expect(globalThis.__webpack_public_path__).toBe('/apps-extra/filinq/js/')
	})
})

describe('the dashboard widgets', () => {
	it('registers both widgets under their frozen ids', async () => {
		await import('../../src/dashboard.js')
		expect(Object.keys(dashboardRegistrations)).toEqual(['docudesk-anonymization', 'docudesk-file-entities'])
		expect(rec.apps).toHaveLength(0)
	})

	it('mounts each widget into the element it is given, with its title, t, n and pinia', async () => {
		await import('../../src/dashboard.js')
		const first = { id: 'el-1' }
		const second = { id: 'el-2' }
		dashboardRegistrations['docudesk-anonymization'](first, { widget: { title: 'Anonymisation' } })
		dashboardRegistrations['docudesk-file-entities'](second, { widget: { title: 'Entities' } })
		expect(rec.apps.map((a) => [a.root.render(), a.mountedOn])).toEqual([
			[{ rendered: { name: 'AnonymizationWidget' }, props: { title: 'Anonymisation' } }, first],
			[{ rendered: { name: 'FileEntitiesDashboardWidget' }, props: { title: 'Entities' } }, second],
		])
		expect(rec.apps.every((a) => a.plugins[0].id === 'pinia' && Object.keys(a.mixins[0].methods).join() === 't,n')).toBe(true)
	})
})
