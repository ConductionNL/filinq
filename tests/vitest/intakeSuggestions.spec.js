/**
 * The assign dialog shows the registry and party suggestions of an arriving
 * document, and the clerk accepts, corrects or dismisses each one through the
 * server (intake-base-registry-enrichment REQ-IBR-004, REQ-IDW-03).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 */

import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

const axiosMock = { post: vi.fn(), get: vi.fn() }
vi.mock('@nextcloud/axios', () => ({ default: axiosMock }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
const helpers = await import('../../src/services/intakeSuggestions.js')
const IntakeSuggestionCard = await loadSfcOptions(new URL('../../src/components/intake/IntakeSuggestionCard.vue', import.meta.url).pathname)
const IntakeAssignDialog = await loadSfcOptions(new URL('../../src/dialogs/IntakeAssignDialog.vue', import.meta.url).pathname)
const CARD_SOURCE = readFileSync('src/components/intake/IntakeSuggestionCard.vue', 'utf8')
const DIALOG_SOURCE = readFileSync('src/dialogs/IntakeAssignDialog.vue', 'utf8')

const DOCUMENT = {
	uuid: 'doc-1',
	subject: 'Aanvraag dakkapel',
	sender: 'info@voorbeeld.nl',
	registrySuggestion: [
		{ source: 'kvk', identifier: '12345678', fields: { name: 'Voorbeeld B.V.', address: 'Markt 1, 1234 AB Zuiddrecht', kvkNumber: '12345678', extra: 'x' } },
		{ source: 'kvk', identifier: '87654321', fields: { name: 'Al gedaan B.V.' }, written: true },
		{ source: 'brp', identifier: '***789', fields: { name: 'J. Jansen' }, dismissed: true },
	],
	partySuggestion: [
		{ party: { name: 'Jan Jansen', email: 'jan@example.nl' }, confidence: 0.5 },
		{ party: {}, confidence: 0 },
	],
	registryLookupNote: 'BRP lookup is switched off',
}

/**
 * A plain instance of the assign dialog over a document.
 *
 * @param {object} document The intake document.
 * @return {object} The instance.
 */
function dialog(document = DOCUMENT) {
	const self = { document, saving: false, error: '', $emit: vi.fn() }
	for (const [name, method] of Object.entries(IntakeAssignDialog.methods)) {
		self[name] = method.bind(self)
	}
	Object.assign(self, IntakeAssignDialog.data.call(self))
	for (const [name, getter] of Object.entries(IntakeAssignDialog.computed)) {
		Object.defineProperty(self, name, { get: getter.bind(self) })
	}
	return self
}

beforeEach(() => {
	axiosMock.post.mockReset()
})

describe('which suggestions are open', () => {
	it('keeps the undecided registry suggestions with their position and the correctable fields', () => {
		expect(helpers.openRegistrySuggestions(DOCUMENT)).toEqual([
			{ index: 0, source: 'kvk', identifier: '12345678', fields: { name: 'Voorbeeld B.V.', address: 'Markt 1, 1234 AB Zuiddrecht', kvkNumber: '12345678' } },
		])
		expect(helpers.openRegistrySuggestions(null)).toEqual([])
	})

	it('keeps the party suggestions that name something', () => {
		expect(helpers.openPartySuggestions(DOCUMENT)).toEqual([
			{ index: 0, party: { name: 'Jan Jansen', email: 'jan@example.nl' }, confidence: 0.5 },
		])
	})
})

describe('what is sent', () => {
	it('accepts a registry suggestion with only the correctable fields, and dismisses one with none', async () => {
		axiosMock.post.mockResolvedValue({ data: { uuid: 'doc-1' } })
		await helpers.decideRegistrySuggestion('doc-1', 0, 'accepted', { name: ' Voorbeeld B.V. ', address: 'Markt 3', kvkNumber: '12345678', written: true })
		await helpers.decideRegistrySuggestion('doc-1', 2, 'dismissed', { name: 'x' })
		expect(axiosMock.post.mock.calls).toEqual([
			['/index.php/apps/filinq/api/intake/documents/doc-1/registry-decisions', { index: 0, decision: 'accepted', fields: { name: 'Voorbeeld B.V.', address: 'Markt 3', kvkNumber: '12345678' } }],
			['/index.php/apps/filinq/api/intake/documents/doc-1/registry-decisions', { index: 2, decision: 'dismissed' }],
		])
		expect(readFileSync('appinfo/routes.php', 'utf8')).toContain("['name' => 'intake#decideRegistry', 'url' => 'api/intake/documents/{uuid}/registry-decisions', 'verb' => 'POST']")
	})

	it('confirms, corrects or dismisses a party suggestion through decidePartySuggestion', async () => {
		axiosMock.post.mockResolvedValue({ data: {} })
		const suggestion = helpers.openPartySuggestions(DOCUMENT)[0]
		await helpers.decidePartyChoice(DOCUMENT, suggestion, 'confirm')
		await helpers.decidePartyChoice(DOCUMENT, suggestion, 'confirm', { name: 'Jan Janssen', email: 'jan@example.nl' })
		await helpers.decidePartyChoice(DOCUMENT, suggestion, 'dismiss')
		const bodies = axiosMock.post.mock.calls.map((call) => [call[0], call[1].decision, call[1].accepted])
		expect(bodies).toEqual([
			['/index.php/apps/filinq/api/intake/party-decisions', 'accepted', { name: 'Jan Jansen', email: 'jan@example.nl' }],
			['/index.php/apps/filinq/api/intake/party-decisions', 'edited', { name: 'Jan Janssen', email: 'jan@example.nl' }],
			['/index.php/apps/filinq/api/intake/party-decisions', 'rejected', {}],
		])
		expect(axiosMock.post.mock.calls[0][1]).toMatchObject({ sender: 'info@voorbeeld.nl', intakeDocument: 'doc-1', suggested: { name: 'Jan Jansen', email: 'jan@example.nl' } })
	})
})

describe('IntakeSuggestionCard', () => {
	const props = { title: 'KvK: 12345678', fields: { name: 'Voorbeeld B.V.', kvkNumber: '12345678' }, editableKeys: ['name', 'address', 'kvkNumber'], labels: { name: 'Name' }, acceptLabel: 'Accept' }

	it('offers accept, correct and dismiss while open, and reads as decided after', () => {
		expect(CARD_SOURCE).toContain('data-testid="intake-suggestion-accept"')
		expect(CARD_SOURCE).toContain('data-testid="intake-suggestion-correct"')
		expect(CARD_SOURCE).toContain('data-testid="intake-suggestion-dismiss"')
		expect(CARD_SOURCE).toMatch(/v-if="state === 'accepted'"[\s\S]*v-else-if="state === 'dismissed'"[\s\S]*<template v-else>/)
	})

	it('shows only the fields the suggestion carries', () => {
		const self = { ...props }
		Object.defineProperty(self, 'shownKeys', { get: IntakeSuggestionCard.computed.shownKeys.bind(self) })
		expect(self.shownKeys).toEqual(['name', 'kvkNumber'])
	})

	it('accepts the correction the clerk typed', () => {
		const self = { ...props, $emit: vi.fn() }
		for (const [name, method] of Object.entries(IntakeSuggestionCard.methods)) {
			self[name] = method.bind(self)
		}
		Object.assign(self, IntakeSuggestionCard.data.call(self))
		self.accept()
		self.startCorrecting()
		self.setDraft('address', 'Markt 3, 1234 AB Zuiddrecht')
		self.accept()
		expect(self.$emit.mock.calls).toEqual([
			['accept', { name: 'Voorbeeld B.V.', kvkNumber: '12345678' }],
			['accept', { name: 'Voorbeeld B.V.', kvkNumber: '12345678', address: 'Markt 3, 1234 AB Zuiddrecht' }],
		])
	})
})

describe('the assign dialog', () => {
	it('shows both lists above the record pickers, through the card', () => {
		expect(DIALOG_SOURCE).toMatch(/data-testid="intake-registry-suggestions"[\s\S]*data-testid="intake-party-suggestions"[\s\S]*<CnRegisterSchemaSelect/)
		expect(DIALOG_SOURCE).toContain('@accept="decideRegistry(suggestion, \'accepted\', $event)"')
		expect(DIALOG_SOURCE).toContain('@dismiss="decideParty(suggestion, \'dismiss\')"')
	})

	it('lists the open suggestions and the lookup note', () => {
		const self = dialog()
		expect(self.registrySuggestions.map((s) => s.index)).toEqual([0])
		expect(self.partySuggestions.map((s) => s.index)).toEqual([0])
		expect(self.registryNote).toBe('BRP lookup is switched off')
		expect(self.registryTitle(self.registrySuggestions[0])).toBe('KvK: 12345678')
	})

	it('marks an accepted registry suggestion and a dismissed party suggestion', async () => {
		axiosMock.post.mockResolvedValue({ data: {} })
		const self = dialog()
		await self.decideRegistry(self.registrySuggestions[0], 'accepted', { name: 'Voorbeeld B.V.' })
		await self.decideParty(self.partySuggestions[0], 'dismiss')
		expect(self.decisions).toEqual({ 'registry-0': 'accepted', 'party-0': 'dismissed' })
		expect(self.suggestionError).toBe('')
	})

	it('shows the refusal and leaves the suggestion open', async () => {
		axiosMock.post.mockRejectedValue({ response: { data: { error: 'This registry suggestion was already decided.' } } })
		const self = dialog()
		await self.decideRegistry(self.registrySuggestions[0], 'dismissed')
		expect(self.decisions).toEqual({})
		expect(self.suggestionError).toBe('This registry suggestion was already decided.')
		expect(self.deciding).toBe('')
	})
})
