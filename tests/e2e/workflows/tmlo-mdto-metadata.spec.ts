/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * tmlo-mdto-metadata, task 4.2: the MDTO panel, proposal confirmation, the
 * blocked transfer with its missing-field banner, the sidecar export and the
 * manual-delivery degradation.
 *
 * WHERE THE PANEL IS. `MdtoPanel` sits in the file viewer's sidebar for a
 * document (record id from the document's anonymisation link) and on the
 * dossier page for a dossier. The document panel is reached the only way a
 * user reaches it: open the file from My Documents (`/DocuDesk`).
 *
 * WHAT IT SEEDS ITSELF. The blocked-transfer and manual-delivery tests create
 * a dossier through `POST api/dossiers`; a fresh dossier has no supplement and
 * no retention block, so its verdict is incomplete with `aggregatieniveau`
 * among the missing fields. The dossier object is deleted through
 * OpenRegister afterwards (its home folder stays: filinq has no dossier delete
 * route). Note the spec's GIVEN is a `bewaren` record; a fresh dossier has no
 * appraisal at all, which the gate reports as missing too, so the assertion is
 * on `aggregatieniveau` being listed, which holds either way.
 *
 * WHAT IT READS FROM THE INSTANCE. A record with a populated `retention`
 * block, a consent in `objection_received`, and a complete record with a
 * confirmed supplement are made by the retention engine, the consent flow and
 * an archivist, not by a browser. The live pass prepares them and points:
 *   - `FILINQ_E2E_MDTO_RECORD_ID` / `FILINQ_E2E_MDTO_FILE_NAME`: a document
 *     record whose retention block is filled, and its file name in /DocuDesk;
 *   - `FILINQ_E2E_MDTO_OBJECTION_RECORD_ID` / `FILINQ_E2E_MDTO_OBJECTION_FILE_NAME`:
 *     a document with a consent record in `objection_received`, and its file;
 *   - `FILINQ_E2E_MDTO_COMPLETE_RECORD_ID`: a complete document record with a
 *     confirmed supplement and a file.
 * Without them those tests skip and say why. The objection test accepts the
 * proposal through the panel and then puts the supplement back as it found
 * it (PUT of the original, or DELETE of the one the accept created).
 *
 * Excluded in the spec (`@e2e exclude`) and not here: no app-side writes to
 * `retention`/`tmlo` and no transport code (architecture tests), the register
 * import and the betrokkene data shape (PHPUnit), dekking in tijd derivation
 * and gate-implies-generator (MdtoMappingServiceTest), the dossier sidecar
 * aggregation (fixture tests), and the configured-transport transfer (needs a
 * live e-depot).
 *
 * Locale: the assertions accept the English and the Dutch strings.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#core-fields-render-from-retention-read-only
// @e2e openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#objection-state-proposes-an-avg-use-restriction
// @e2e openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#incomplete-record-cannot-be-transferred
// @e2e openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#document-sidecar-carries-core-supplement-and-bestand
// @e2e openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#missing-transport-degrades-to-manual-export

import type { APIRequestContext, Locator, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go } from '../spec-coverage/_helpers.ts'

/** filinq's MDTO routes. */
const mdtoApi = (id: string): string => `/index.php/apps/filinq/api/mdto/${encodeURIComponent(id)}`

/** The dossier route the seed uses. */
const DOSSIER_API = '/index.php/apps/filinq/api/dossiers'

/** OpenRegister's object and schema APIs. */
const OBJECTS = '/index.php/apps/openregister/api/objects/filinq'
const SCHEMAS_API = '/index.php/apps/openregister/api/schemas'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** Fixtures the live pass prepares (see the header). */
const RECORD_ID = process.env.FILINQ_E2E_MDTO_RECORD_ID ?? ''
const FILE_NAME = process.env.FILINQ_E2E_MDTO_FILE_NAME ?? ''
const OBJECTION_RECORD_ID = process.env.FILINQ_E2E_MDTO_OBJECTION_RECORD_ID ?? ''
const OBJECTION_FILE_NAME = process.env.FILINQ_E2E_MDTO_OBJECTION_FILE_NAME ?? ''
const COMPLETE_RECORD_ID = process.env.FILINQ_E2E_MDTO_COMPLETE_RECORD_ID ?? ''

/** The core archival attributes that live in `retention` and nowhere else. */
const CORE_FIELDS = ['archiefnominatie', 'bewaartermijn', 'archiefactiedatum', 'archiefstatus', 'classificatie']
const RETENTION_OWNED = ['waardering', 'bewaartermijn', 'archiefactiedatum', 'archiefstatus', 'classificatie']

/** What `GET api/mdto/{id}` answers. */
interface MdtoStatus {
	complete: boolean
	missing: string[]
	transportConfigured: boolean
	mayTransfer: boolean
	retention: Record<string, unknown>
	supplement: Record<string, any>
	proposals: Record<string, any>
}

/**
 * Read a record's MDTO status.
 *
 * @param request The authenticated request context.
 * @param id The record uuid.
 * @return The status.
 */
async function mdtoStatus(request: APIRequestContext, id: string): Promise<MdtoStatus> {
	const res = await request.get(mdtoApi(id), { headers: OCS })
	expect(res.ok(), `GET api/mdto/${id} -> ${res.status()}`).toBeTruthy()

	return (await res.json()) as MdtoStatus
}

/**
 * Download a record's MDTO sidecar.
 *
 * @param request The authenticated request context.
 * @param id The record uuid.
 * @param format `xml` or `json`.
 * @return The sidecar bytes as text.
 */
async function exportSidecar(request: APIRequestContext, id: string, format: 'xml' | 'json' = 'xml'): Promise<string> {
	const res = await request.get(`${mdtoApi(id)}/export?format=${format}`, { headers: OCS })
	expect(res.status(), `export ${format} -> ${res.status()}`).toBe(200)
	expect(res.headers()['content-type']).toContain(format === 'json' ? 'application/json' : 'application/xml')

	return await res.text()
}

/**
 * Whether an XML document has an element of this local name, in any prefix.
 *
 * @param xml The XML.
 * @param name The local element name.
 * @return How many times it occurs.
 */
function elementCount(xml: string, name: string): number {
	return (xml.match(new RegExp(`<(?:[\\w-]+:)?${name}[\\s/>]`, 'g')) ?? []).length
}

/**
 * The id of a stored supplement, whichever key the reader returns it under.
 *
 * @param supplement The supplement.
 * @return Its id, or an empty string when there is none.
 */
function supplementId(supplement: Record<string, any> | null | undefined): string {
	return String(supplement?.id ?? supplement?.uuid ?? supplement?.['@self']?.id ?? '')
}

/**
 * Create a dossier through filinq's own route, which also makes its folder.
 *
 * @param request The authenticated request context.
 * @return The dossier id.
 */
async function createDossier(request: APIRequestContext): Promise<string> {
	const res = await request.post(DOSSIER_API, {
		headers: OCS,
		data: { name: `MDTO e2e ${Date.now()}` },
	})
	expect(res.status(), `POST ${DOSSIER_API}`).toBe(201)
	const body = await res.json()
	const id = String(body.id ?? body.uuid ?? body['@self']?.id ?? '')
	expect(id).not.toBe('')

	return id
}

/**
 * Open a document from My Documents and wait for its MDTO panel.
 *
 * @param page The page.
 * @param fileName The file name in /DocuDesk.
 * @return The panel.
 */
async function openDocumentPanel(page: Page, fileName: string): Promise<Locator> {
	await go(page, 'my-documents')
	// The list strips the extension for display.
	const stem = fileName.replace(/\.[^.]+$/, '')
	const row = page.locator('tr').filter({ hasText: stem }).first()
	await expect(row, `${fileName} is listed in My Documents`).toBeVisible({ timeout: 30_000 })
	await row.click()
	const panel = page.getByTestId('mdto-panel')
	await expect(panel).toBeVisible({ timeout: 30_000 })
	await expect(panel.getByTestId('mdto-core')).toBeVisible({ timeout: 30_000 })

	return panel
}

test.describe('TMLO/MDTO metadata', () => {
	test.setTimeout(180_000)

	test('core fields render from retention, read-only, and no supplement property duplicates them', async ({ page, request }) => {
		test.skip(RECORD_ID === '' || FILE_NAME === '', 'FILINQ_E2E_MDTO_RECORD_ID/FILE_NAME is not set: no document with a filled retention block')

		const status = await mdtoStatus(request, RECORD_ID)
		const filled = CORE_FIELDS.filter((field) => String(status.retention?.[field] ?? '') !== '')
		expect(filled, 'the fixture record carries a retention block').toEqual(expect.arrayContaining(['archiefnominatie', 'classificatie']))

		const panel = await openDocumentPanel(page, FILE_NAME)
		const core = panel.getByTestId('mdto-core')
		for (const field of filled) {
			await expect(core).toContainText(String(status.retention[field]))
		}
		// Read-only: the core block offers nothing to type into or press.
		await expect(core.locator('input, textarea, select, button')).toHaveCount(0)

		// The supplement schema holds none of the retention-owned attributes.
		const res = await request.get(`${SCHEMAS_API}?slug=mdtoSupplement`, { headers: OCS })
		expect(res.ok()).toBeTruthy()
		const schema = ((await res.json())?.results ?? [])[0]
		expect(schema, 'the mdtoSupplement schema is imported').toBeTruthy()
		const properties = Object.keys(schema.properties ?? {})
		expect(properties).toContain('aggregatieniveau')
		for (const owned of RETENTION_OWNED) {
			expect(properties, `${owned} belongs to retention, not the supplement`).not.toContain(owned)
		}
	})

	test('an objection proposes an AVG use restriction that the export leaves out until it is accepted', async ({ page, request }) => {
		test.skip(
			OBJECTION_RECORD_ID === '' || OBJECTION_FILE_NAME === '',
			'FILINQ_E2E_MDTO_OBJECTION_RECORD_ID/FILE_NAME is not set: no document with a consent in objection_received',
		)

		const before = await mdtoStatus(request, OBJECTION_RECORD_ID)
		const proposed = (before.proposals?.beperkingGebruik?.value ?? []) as Array<Record<string, any>>
		const avg = proposed.find((entry) => entry.type === 'avg-persoonsgegevens')
		expect(avg, 'the objection proposes an avg-persoonsgegevens restriction').toBeTruthy()
		expect(String(avg!.grondslag ?? '')).not.toBe('')

		const confirmedBefore = ((before.supplement?.beperkingGebruik ?? []) as Array<Record<string, any>>)
			.filter((entry) => entry.proposed !== true)
		test.skip(
			confirmedBefore.some((entry) => entry.type === 'avg-persoonsgegevens'),
			'the fixture already holds a confirmed avg-persoonsgegevens restriction, so the export cannot show the difference',
		)

		// Not confirmed yet: not in the sidecar.
		expect(await exportSidecar(request, OBJECTION_RECORD_ID)).not.toContain('avg-persoonsgegevens')

		const originalId = supplementId(before.supplement)
		try {
			const panel = await openDocumentPanel(page, OBJECTION_FILE_NAME)
			const chip = panel.getByTestId('mdto-restriction-proposal').filter({ hasText: 'avg-persoonsgegevens' }).first()
			await expect(chip).toBeVisible()
			await expect(chip).toContainText(String(avg!.grondslag))

			await chip.getByTestId('mdto-restriction-accept').click()
			await expect(
				panel.getByTestId('mdto-restriction-proposal').filter({ hasText: 'avg-persoonsgegevens' }),
			).toHaveCount(0, { timeout: 15_000 })
			await expect(panel.getByTestId('mdto-restrictions')).toContainText('avg-persoonsgegevens')

			// Confirmed: now in the sidecar, with its grondslag.
			const xml = await exportSidecar(request, OBJECTION_RECORD_ID)
			expect(xml).toContain('avg-persoonsgegevens')
			expect(xml).toContain(String(avg!.grondslag))
		} finally {
			const after = await mdtoStatus(request, OBJECTION_RECORD_ID)
			const afterId = supplementId(after.supplement)
			if (originalId !== '') {
				const { id: _id, uuid: _uuid, '@self': _self, ...body } = before.supplement
				await request.put(`${OBJECTS}/mdtoSupplement/${originalId}`, { headers: OCS, data: body })
			} else if (afterId !== '') {
				await request.delete(`${OBJECTS}/mdtoSupplement/${afterId}`, { headers: OCS })
			}
		}
	})

	test('an incomplete record cannot be transferred and the banner lists aggregatieniveau', async ({ page, request }) => {
		const dossierId = await createDossier(request)
		try {
			const status = await mdtoStatus(request, dossierId)
			expect(status.complete).toBe(false)
			expect(status.missing).toContain('aggregatieniveau')

			// The transfer action stops with the same list, whatever transport exists.
			const transfer = await request.post(`${mdtoApi(dossierId)}/transfer`, { headers: OCS })
			expect(transfer.status()).toBe(422)
			const blocked = await transfer.json()
			expect(blocked.status).toBe('blocked')
			expect(blocked.missing).toContain('aggregatieniveau')

			await go(page, `dossiers/${dossierId}`)
			const panel = page.getByTestId('mdto-panel')
			await expect(panel).toBeVisible({ timeout: 30_000 })
			const banner = panel.getByTestId('mdto-incomplete')
			await expect(banner).toBeVisible()
			await expect(banner).toContainText(/This record cannot be transferred yet|kan nog niet worden overgebracht/)
			await expect(banner.locator('code', { hasText: /^aggregatieniveau$/ })).toBeVisible()
			for (const field of status.missing) {
				await expect(banner).toContainText(field)
			}

			// Where a transport exists the button is there and refuses to start.
			if (status.transportConfigured) {
				await expect(panel.getByTestId('mdto-transfer')).toBeDisabled()
			} else {
				await expect(panel.getByTestId('mdto-transfer')).toHaveCount(0)
			}
		} finally {
			await request.delete(`${OBJECTS}/dossier/${dossierId}`, { headers: OCS })
		}
	})

	test('a complete document exports one informatieobject with core, supplement and bestand, byte-identical twice', async ({ request }) => {
		test.skip(COMPLETE_RECORD_ID === '', 'FILINQ_E2E_MDTO_COMPLETE_RECORD_ID is not set: no complete record with a confirmed supplement')

		const status = await mdtoStatus(request, COMPLETE_RECORD_ID)
		expect(status.complete, `missing: ${status.missing.join(', ')}`).toBe(true)

		const xml = await exportSidecar(request, COMPLETE_RECORD_ID)
		expect(xml).toContain('https://www.nationaalarchief.nl/mdto')
		expect(elementCount(xml, 'informatieobject'), 'one informatieobject').toBe(1)
		for (const element of ['identificatie', 'naam', 'waardering', 'classificatie', 'archiefvormer', 'aggregatieniveau', 'dekkingInTijd', 'beperkingGebruik', 'bestand', 'checksum']) {
			expect(elementCount(xml, element), `<${element}> in the sidecar`).toBeGreaterThan(0)
		}
		const checksum = /<(?:[\w-]+:)?checksumWaarde>([^<]*)</.exec(xml)
		expect(checksum?.[1] ?? '', 'the bestand carries the sha256 of the file').toMatch(/^[0-9a-f]{64}$/)

		// Only confirmed restrictions are exported.
		const restrictions = (status.supplement?.beperkingGebruik ?? []) as Array<Record<string, any>>
		const confirmed = restrictions.filter((entry) => entry.proposed !== true).map((entry) => String(entry.type))
		for (const type of confirmed) {
			expect(xml).toContain(type)
		}
		for (const entry of restrictions.filter((candidate) => candidate.proposed === true && !confirmed.includes(String(candidate.type)))) {
			expect(xml, `unconfirmed ${entry.type} is not exported`).not.toContain(String(entry.type))
		}

		// Deterministic: nothing changed, so nothing in the bytes changes.
		expect(await exportSidecar(request, COMPLETE_RECORD_ID)).toBe(xml)

		// The JSON projection is the same document.
		const json = JSON.parse(await exportSidecar(request, COMPLETE_RECORD_ID, 'json'))
		expect(json.MDTO, 'the JSON projection has the MDTO root').toBeTruthy()
	})

	test('without an e-depot transport the panel offers the sidecar for manual delivery and says why', async ({ page, request }) => {
		const dossierId = await createDossier(request)
		try {
			const status = await mdtoStatus(request, dossierId)
			test.skip(status.transportConfigured, 'an e-depot transport is configured in OpenRegister on this instance')
			expect(status.mayTransfer, 'the signed-in admin is archive staff').toBe(true)

			await go(page, `dossiers/${dossierId}`)
			const panel = page.getByTestId('mdto-panel')
			await expect(panel).toBeVisible({ timeout: 30_000 })
			await expect(panel.getByTestId('mdto-manual-delivery')).toContainText(
				/No e-depot transport is configured in OpenRegister|geen e-depotverbinding ingesteld/,
			)
			const xmlLink = panel.getByTestId('mdto-export-xml')
			await expect(xmlLink).toBeVisible()
			await expect(xmlLink).toHaveAttribute('href', new RegExp(`/api/mdto/${dossierId}/export\\?format=xml$`))
			await expect(panel.getByTestId('mdto-transfer')).toHaveCount(0)

			// The link is a real download, not a silent no-op.
			const xml = await exportSidecar(request, dossierId)
			expect(elementCount(xml, 'informatieobject')).toBeGreaterThan(0)
		} finally {
			await request.delete(`${OBJECTS}/dossier/${dossierId}`, { headers: OCS })
		}
	})
})
