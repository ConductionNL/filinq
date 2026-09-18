/*
 * SPDX-FileCopyrightText: 2026 Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * End-to-end regression for `documents-in-and-out-of-the-building`: the post
 * register's entry shape, the discharge that is a link rather than a flag, and
 * the plain-language counterpart a template may declare.
 *
 * WHAT IS DRIVEN HERE AND WHAT IS NOT
 * -----------------------------------
 * The open post list and the series have no surface of their own yet: the leaf
 * that would render them is blocked on the missing `leaves` webpack entry, the
 * same blocker recorded in `case-documents-and-the-flat-list`. So what this
 * file can drive is the STORE: the schemas resolve, they accept the shape the
 * services write, and they refuse to carry the two fields whose absence is the
 * point of the design.
 *
 * 🔴 THE ABSENT FIELDS ARE THE ASSERTION, NOT A DETAIL. `answered` and
 * `dischargedAt` are deliberately not on the inbound entry: a flag can be set by
 * anybody at any time without an answer existing, and the register would then
 * report a letter as dealt with because somebody ticked a box. OpenRegister
 * DROPS an undeclared property in silence, so a write that carries them comes
 * back without them, and that silence is exactly what is checked here.
 *
 * ⚠️ The `@e2e` anchors name BOTH the change's delta spec and the canonical
 * spec it is synced into at archive time. Gate 19 scans `openspec/specs/` only.
 */

import { expect, test } from '@playwright/test'
import { harvestToken, jsonHeaders, TEST_PREFIX } from './_fixtures.ts'

/** The OpenRegister objects endpoint for the filinq register. */
const OR = '/index.php/apps/openregister/api/objects/filinq'

test.describe('the post register', () => {
	// @e2e openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md#the-first-outgoing-letter-of-the-year
	// @e2e openspec/specs/document-register/spec.md#the-first-outgoing-letter-of-the-year
	test('a registration entry carries its direction, unit and moment, and is given a number', async ({
		page,
		request,
	}) => {
		const token = await harvestToken(page)

		// POSITIVE CONTROL FIRST. Without it a 404 below cannot be told apart
		// from a register that was never seeded, which is a different failure
		// with the same status code.
		const sibling = await request.get(`${OR}/uploadPolicy`, { headers: jsonHeaders(token) })
		expect(
			sibling.status(),
			`positive control — GET ${OR}/uploadPolicy must answer 200 before anything below means anything`,
		).toBe(200)

		const created = await request.post(`${OR}/documentRegistration`, {
			headers: jsonHeaders(token),
			data: {
				direction: 'outgoing',
				registeredAt: new Date().toISOString(),
				unit: `${TEST_PREFIX}-burgerzaken`,
				document: `${TEST_PREFIX}-besluit`,
			},
		})

		expect(created.status(), await created.text()).toBeLessThan(300)

		const body = await created.json()
		const entry = body.object ?? body

		expect(entry.direction).toBe('outgoing')
		expect(entry.unit).toBe(`${TEST_PREFIX}-burgerzaken`)

		// The number comes from OpenRegister's generated identifier, not from a
		// counter in filinq: two registrations in the same second cannot race
		// for one number and silently reuse it.
		expect(String(entry.registrationNumber ?? '')).toMatch(/^\d{4}-\d+$/)
	})

	// @e2e openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md#a-reply-discharges-the-request
	// @e2e openspec/specs/document-register/spec.md#a-reply-discharges-the-request
	test('the discharge is a link on the outbound entry and never a flag on the inbound one', async ({
		page,
		request,
	}) => {
		const token = await harvestToken(page)

		const inbound = await request.post(`${OR}/documentRegistration`, {
			headers: jsonHeaders(token),
			data: {
				direction: 'incoming',
				registeredAt: new Date().toISOString(),
				unit: `${TEST_PREFIX}-burgerzaken`,
				document: `${TEST_PREFIX}-aanvraag`,
				// Offered deliberately. The schema declares neither, so both
				// must come back absent: OpenRegister drops an undeclared
				// property without an error, and that silence is the assertion.
				answered: true,
				dischargedAt: new Date().toISOString(),
			},
		})

		expect(inbound.status(), await inbound.text()).toBeLessThan(300)

		const inboundBody = await inbound.json()
		const inboundEntry = inboundBody.object ?? inboundBody
		const inboundId = String(inboundBody.id ?? inboundBody.uuid ?? inboundEntry.id ?? '')

		expect(
			inboundEntry.answered,
			'an inbound entry must carry no `answered` flag: a flag can be set without an answer existing',
		).toBeUndefined()
		expect(inboundEntry.dischargedAt).toBeUndefined()

		expect(inboundId, 'the inbound entry must come back with an id to be answered by uuid').not.toBe('')

		const outbound = await request.post(`${OR}/documentRegistration`, {
			headers: jsonHeaders(token),
			data: {
				direction: 'outgoing',
				registeredAt: new Date().toISOString(),
				unit: `${TEST_PREFIX}-burgerzaken`,
				document: `${TEST_PREFIX}-besluit-antwoord`,
				answers: inboundId,
			},
		})

		expect(outbound.status(), await outbound.text()).toBeLessThan(300)

		// BARE keys beside `@self`, which is what the objects endpoint reads. A
		// `filter[answers]` wrapper is taken as the empty set there, and this
		// search would report the letter as still open with nothing in the log.
		const search = await request.get(
			`${OR}/documentRegistration?answers=${encodeURIComponent(inboundId)}`,
			{ headers: jsonHeaders(token) },
		)

		expect(search.status(), await search.text()).toBe(200)

		const found = await search.json()
		const rows = found.results ?? found
		expect(
			Array.isArray(rows) ? rows.length : 0,
			'the outbound entry naming the inbound one is what makes it discharged',
		).toBeGreaterThan(0)
	})
})

test.describe('the plain-language counterpart', () => {
	// @e2e openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md#a-besluit-leaves-with-both-renditions
	// @e2e openspec/specs/letter-correspondence-generation/spec.md#a-besluit-leaves-with-both-renditions
	test('a template can declare a counterpart, and a generation record can carry both renditions', async ({
		page,
		request,
	}) => {
		const token = await harvestToken(page)

		const template = await request.post(`${OR}/template`, {
			headers: jsonHeaders(token),
			data: {
				name: `${TEST_PREFIX}-besluit`,
				content: 'De formele tekst.',
				namespace: `${TEST_PREFIX}-besluiten`,
				plainLanguage: {
					templateId: `${TEST_PREFIX}-plain-besluit`,
					requiredStatements: ['besluit', 'bezwaartermijn'],
					source: 'template',
				},
			},
		})

		expect(template.status(), await template.text()).toBeLessThan(300)

		const templateBody = await template.json()
		const stored = templateBody.object ?? templateBody

		expect(
			stored.plainLanguage?.templateId,
			'an undeclared property is DROPPED in silence, so a missing counterpart here means the schema '
				+ 'never carried it and every generation would quietly produce the formal letter only',
		).toBe(`${TEST_PREFIX}-plain-besluit`)
		expect(stored.plainLanguage?.requiredStatements).toContain('bezwaartermijn')

		const generation = await request.post(`${OR}/generatedDocument`, {
			headers: jsonHeaders(token),
			data: {
				templateId: `${TEST_PREFIX}-besluit`,
				templateVersion: 1,
				generatedAt: new Date().toISOString(),
				format: 'pdf',
				status: 'generated',
				generatedBy: 'admin',
				filePath: `DocuDesk/${TEST_PREFIX}/besluit.pdf`,
				plainRenditionFilePath: `DocuDesk/${TEST_PREFIX}/besluit-in-gewone-taal.pdf`,
				plainRenditionExplains: `DocuDesk/${TEST_PREFIX}/besluit.pdf`,
				plainRenditionSource: 'template',
				plainRenditionGeneratedAt: new Date().toISOString(),
			},
		})

		expect(generation.status(), await generation.text()).toBeLessThan(300)

		const generationBody = await generation.json()
		const record = generationBody.object ?? generationBody

		// The plain letter names the formal document it explains. Somebody
		// receives two letters about one decision; without this they hold two
		// documents and no relation between them.
		expect(record.plainRenditionExplains).toBe(`DocuDesk/${TEST_PREFIX}/besluit.pdf`)
		expect(record.plainRenditionSource).toBe('template')
	})
})
