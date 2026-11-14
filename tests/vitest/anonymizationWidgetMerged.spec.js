/**
 * The one anonymisation upload widget: the file types it accepts, the
 * upload dialog every upload goes through, and where the user lands after
 * an upload, inside the app and on the Nextcloud dashboard.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/one-anonymisation-upload-widget/specs/anonymization/spec.md
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const calls = []
const errors = []

const stores = vi.hoisted(() => ({
	anonymizationStore: {},
	fileViewerStore: {},
	myDocumentsStore: {},
}))

vi.mock('../../src/store/store.js', () => stores)
vi.mock('@nextcloud/dialogs', () => ({ showError: (msg) => errors.push(msg) }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))

/**
 * A file as the browser hands it over.
 *
 * @param {string} name File name.
 * @param {string} type MIME type, empty when the browser gives none.
 * @return {object} The file.
 */
function file(name, type = '') {
	return { name, type }
}

/**
 * Fill the store fakes: every call is recorded, uploads add queue entries.
 */
function resetStores() {
	calls.length = 0
	errors.length = 0
	const queue = []
	Object.assign(stores.anonymizationStore, {
		files: queue,
		addFiles: async (files) => {
			calls.push(['addFiles', files.map((f) => f.name)])
			for (const f of files) {
				queue.push({
					fileId: 'id-' + f.name,
					name: f.name,
					filePath: '/u/files/' + f.name,
				})
			}
		},
		addFilesAsDossier: async (files, name) => {
			calls.push(['addFilesAsDossier', files.map((f) => f.name), name])
			for (const f of files) {
				queue.push({
					fileId: 'id-' + f.name,
					name: f.name,
					filePath: '/u/files/' + name + '/' + f.name,
				})
			}
		},
		bindDossier: async (name) => calls.push(['bindDossier', name]),
	})
	Object.assign(stores.fileViewerStore, {
		open: (entry, options) => calls.push(['open', entry, options]),
	})
	Object.assign(stores.myDocumentsStore, {
		fetchDocuments: async (path) => calls.push(['fetchDocuments', path]),
		fetchRecentAnonymized: async (limit) => {
			calls.push(['fetchRecentAnonymized', limit])
			return []
		},
	})
}

/**
 * A widget instance, inside the app (with a router) or on the Nextcloud
 * dashboard (without one).
 *
 * @param {boolean} inApp Whether the app's router is there.
 * @return {Promise<object>} The instance.
 */
async function widget(inApp, props = {}) {
	const options = await loadSfcOptions(
		join(
			import.meta.dirname,
			'../../src/views/anonymization/AnonymizationWidget.vue',
		),
	)
	const vm = createInstance(options, { route: { name: 'Dashboard' }, props })
	if (inApp) {
		// vue-router's push answers a promise, which the widget catches.
		vm.$router = {
			push: (to) => Promise.resolve(vm.$pushed.push(to)),
		}
	} else {
		delete vm.$router
	}
	return vm
}

beforeEach(() => {
	resetStores()
	globalThis.window = {
		location: { assign: (url) => calls.push(['assign', url]) },
	}
})

describe('the one anonymisation widget', () => {
	it('accepts docx, odt, pdf, txt and eml by type or extension, and skips the rest', async () => {
		const vm = await widget(false)
		const accepted = vm.filterAllowed([
			file('a.docx'),
			file('b.odt'),
			file('c.pdf', 'application/pdf'),
			file('d.txt'),
			file('e.eml'),
			file('f.xlsx'),
		])
		expect(accepted.map((f) => f.name)).toEqual([
			'a.docx',
			'b.odt',
			'c.pdf',
			'd.txt',
			'e.eml',
		])
		expect(errors).toEqual([
			'Only Word (.docx), ODT, PDF and TXT files are supported. Skipped: f.xlsx',
		])
	})

	it('opens the upload dialog for one file and uploads nothing yet', async () => {
		const vm = await widget(false)
		vm.dispatchFiles([file('brief.pdf')])
		expect(vm.showDossierDialog).toBe(true)
		expect(vm.isSingleFile).toBe(true)
		expect(vm.singleFileName).toBe('brief.pdf')
		expect(calls).toEqual([])
	})

	it('asks for a dossier name for several files and uploads them as that dossier', async () => {
		const vm = await widget(true)
		vm.dispatchFiles([file('a.pdf'), file('b.pdf'), file('c.odt')])
		expect(vm.isSingleFile).toBe(false)
		vm.dossierName = ' Bezwaar 12 '
		await vm.confirmDossier()
		expect(calls).toEqual([
			['addFilesAsDossier', ['a.pdf', 'b.pdf', 'c.odt'], 'Bezwaar 12'],
			['bindDossier', 'Bezwaar 12'],
			['fetchDocuments', '/DocuDesk/Bezwaar 12'],
			[
				'open',
				{
					fileId: 'id-a.pdf',
					fileName: 'a.pdf',
					mimeType: '',
					path: '/u/files/Bezwaar 12/a.pdf',
				},
				{ grondslagen: true },
			],
		])
		expect(vm.$pushed).toEqual([{ name: 'MyDocuments' }])
		expect(vm.showDossierDialog).toBe(false)
	})

	it('inside the app opens the first file on My documents', async () => {
		const vm = await widget(true)
		expect(vm.inApp).toBe(true)
		vm.dispatchFiles([file('brief.pdf', 'application/pdf')])
		vm.grondslagen = false
		await vm.confirmDossier()
		expect(calls).toEqual([
			['addFiles', ['brief.pdf']],
			[
				'open',
				{
					fileId: 'id-brief.pdf',
					fileName: 'brief.pdf',
					mimeType: 'application/pdf',
					path: '/u/files/brief.pdf',
				},
				{ grondslagen: false },
			],
		])
		expect(vm.$pushed).toEqual([{ name: 'MyDocuments' }])
		expect(vm.showDossierDialog).toBe(false)
		expect(vm.dossierError).toBe('')
	})

	it('on the Nextcloud dashboard sends the browser to My documents', async () => {
		const vm = await widget(false)
		expect(vm.inApp).toBe(false)
		vm.dispatchFiles([file('melding.eml')])
		await vm.confirmDossier()
		expect(calls.map((c) => c[0])).toEqual(['addFiles', 'open', 'assign'])
		expect(calls[2]).toEqual(['assign', '/index.php/apps/filinq/my-documents'])
	})

	it('keeps the dialog open with the entry error when an upload fails', async () => {
		stores.anonymizationStore.addFiles = async (files) => {
			calls.push(['addFiles', files.map((f) => f.name)])
			stores.anonymizationStore.files.push({
				name: files[0].name,
				status: 'error',
				error: 'Bestand is te groot',
			})
		}
		const vm = await widget(false)
		vm.dispatchFiles([file('groot.pdf', 'application/pdf')])
		await vm.confirmDossier()
		expect(calls).toEqual([['addFiles', ['groot.pdf']]])
		expect(vm.showDossierDialog).toBe(true)
		expect(vm.dossierError).toBe('Bestand is te groot')
		expect(vm.dossierSubmitting).toBe(false)
	})

	it('loads the recent documents only where they are asked for', async () => {
		const tile = await widget(false)
		tile.$options.mounted.call(tile)
		expect(calls).toEqual([])
		expect(tile.showRecent).toBe(false)

		const page = await widget(true, { showRecent: true })
		page.$options.mounted.call(page)
		expect(calls).toEqual([['fetchRecentAnonymized', 4]])
	})

	it('links to Filinq outside the app', async () => {
		const vm = await widget(false)
		expect(vm.appUrl).toBe('/index.php/apps/filinq')
		expect(vm.inApp).toBe(false)
	})
})

describe('the upload dialog confirm, edge paths', () => {
	it('keeps going when the dossier cannot be bound or opened', async () => {
		const logged = []
		const original = console.error
		console.error = (...args) => logged.push(args[0])
		stores.anonymizationStore.bindDossier = async (name) => {
			calls.push(['bindDossier', name])
			throw new Error('no OpenRegister')
		}
		stores.myDocumentsStore.fetchDocuments = async (path) => {
			calls.push(['fetchDocuments', path])
			throw new Error('no folder')
		}
		const vm = await widget(true)
		vm.dispatchFiles([file('a.pdf'), file('b.pdf')])
		vm.dossierName = 'Zaak'
		await vm.confirmDossier()
		console.error = original
		expect(calls.map((c) => c[0])).toEqual(['addFilesAsDossier', 'bindDossier', 'fetchDocuments', 'open'])
		expect(logged).toEqual(['Failed to bind dossier to OpenRegister:', 'Failed to open dossier folder:'])
		expect(vm.showDossierDialog).toBe(false)
		expect(vm.dossierError).toBe('')
	})

	it('shows a generic error for a failed entry without a message', async () => {
		stores.anonymizationStore.addFiles = async () => {
			stores.anonymizationStore.files.push({ name: 'x.pdf', status: 'error' })
		}
		const vm = await widget(false)
		vm.dispatchFiles([file('x.pdf')])
		await vm.confirmDossier()
		expect(vm.dossierError).toBe('Failed to upload')
		expect(vm.showDossierDialog).toBe(true)
	})

	it('only looks at the entries this upload added', async () => {
		stores.anonymizationStore.files.push({ name: 'old.pdf', status: 'error', error: 'old' })
		const vm = await widget(false)
		vm.dispatchFiles([file('new.pdf')])
		await vm.confirmDossier()
		expect(vm.dossierError).toBe('')
		expect(calls.map((c) => c[0])).toEqual(['addFiles', 'open', 'assign'])
	})

	it('closes without opening a viewer when the first entry has no file id', async () => {
		stores.anonymizationStore.addFiles = async (files) => {
			calls.push(['addFiles', files.map((f) => f.name)])
			stores.anonymizationStore.files.push({ name: files[0].name })
		}
		const vm = await widget(true)
		vm.dispatchFiles([file('n.pdf')])
		await vm.confirmDossier()
		expect(calls).toEqual([['addFiles', ['n.pdf']]])
		expect(vm.$pushed).toEqual([])
		expect(vm.showDossierDialog).toBe(false)
	})

	it.each([
		['the server message', Object.assign(new Error('x'), { response: { data: { error: 'Quota' } } }), 'Quota'],
		['the error message', new Error('offline'), 'offline'],
		['a generic message', {}, 'Failed to upload'],
	])('a throwing upload shows %s and keeps the dialog', async (_, thrown, shown) => {
		stores.anonymizationStore.addFiles = async () => {
			throw thrown
		}
		const vm = await widget(false)
		vm.dispatchFiles([file('t.pdf')])
		await vm.confirmDossier()
		expect(vm.dossierError).toBe(shown)
		expect(vm.dossierSubmitting).toBe(false)
		expect(vm.showDossierDialog).toBe(true)
	})
})
