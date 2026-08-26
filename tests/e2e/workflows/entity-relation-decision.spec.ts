// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 Conduction B.V.
/**
 * E2e coverage for the EntityRelation decision listener.
 *
 * WHAT THIS PROVES, AND WHY THE SHAPE MATTERS
 * -------------------------------------------
 * Three of the four scenarios below are NEGATIVE — "no consent record is
 * created". Every one of them was satisfied before the listener existed, by
 * nothing happening at all (ConductionNL/filinq#805). The absence of a bug and
 * the absence of the feature are indistinguishable from outside.
 *
 * So the positive case runs FIRST and is asserted hardest. If
 * `skip-activation creates a consent record` fails, the two negatives below it
 * stop being evidence of anything and should be read as unproven rather than
 * passing.
 *
 * WHY THIS DRIVES THE REAL PIPELINE
 * ---------------------------------
 * OpenRegister exposes only PATCH on `/api/entity-relations/{id}` — there is no
 * POST. Relations exist solely as a product of a detection run, so there is no
 * shortcut fixture: the batch below is provisioned and analysed for real, and
 * the relation ids come back on the consolidated-entities response.
 *
 * @e2e consent-management::skip-activation-triggers-consent-creation
 * @e2e consent-management::bases-only-change-does-not-trigger-consent-creation
 * @e2e consent-management::reversal-event-does-not-trigger-consent-creation
 */

import { test, expect, type APIRequestContext, type Page } from '@playwright/test'
import {
	harvestToken,
	jsonHeaders,
	API,
	TEST_PREFIX,
	createDavFolder,
	createDavFile,
} from './_fixtures'

const FOLDER = `${TEST_PREFIX}-reldecision`
const TERM = `Beslissing Roerdomp ${TEST_PREFIX}`

interface Fixture {
	token: string
	batchId: string
}

let fixture: Fixture | null = null

/**
 * Provision a folder with one document and analyse it into entity relations.
 *
 * @param page The page, used only to harvest a request token.
 * @param req  The API request context.
 *
 * @return The provisioned fixture.
 */
async function provision(page: Page, req: APIRequestContext): Promise<Fixture> {
	if (fixture !== null) return fixture

	const token = await harvestToken(page)

	await createDavFolder(req, token, FOLDER)
	await createDavFile(
		req,
		token,
		`${FOLDER}/besluit.txt`,
		`Dit besluit betreft ${TERM} en is genomen op 1 mei.`,
	)

	// A custom-dictionary term guarantees a deterministic detection without
	// depending on which NER backend this instance runs.
	const dict = await req.post(`${API}/custom-dictionaries`, {
		headers: jsonHeaders(token),
		data: {
			label: `${TEST_PREFIX}-dict`,
			description: 'entity-relation listener e2e fixture',
			matchMode: 'exact',
			active: true,
		},
	})
	expect(dict.status(), 'creating the dictionary must answer 2xx').toBeLessThan(
		300,
	)
	const dictId = (await dict.json()).id ?? (await dict.json()).uuid

	await req.post(`${API}/custom-dictionaries/${dictId}/terms`, {
		headers: jsonHeaders(token),
		data: { value: TERM, label: TERM, active: true },
	})

	const batch = await req.post(`${API}/anonymization/batch/folder`, {
		headers: jsonHeaders(token),
		data: { folderPath: FOLDER },
	})
	expect(batch.status(), 'starting the folder batch must answer 200').toBe(200)
	const batchId = (await batch.json()).batchId

	const extract = await req.post(`${API}/anonymization/batch/${batchId}/extract`, {
		headers: jsonHeaders(token),
		data: {},
	})
	expect(
		extract.status(),
		'the extract step must answer 200 — a 404 here means the batch record did not '
			+ 'survive the request, which is a cache-backend problem rather than a listener one',
	).toBe(200)

	fixture = { token, batchId }
	return fixture
}

/**
 * Read the consolidated entities for the batch, which carry their relation ids.
 *
 * @param req The API request context.
 * @param f   The provisioned fixture.
 *
 * @return The parsed response.
 */
async function entities(
	req: APIRequestContext,
	f: Fixture,
): Promise<Record<string, unknown>> {
	const url = `${API}/anonymization/batch/${f.batchId}/entities`
	const res = await req.get(url, { headers: jsonHeaders(f.token) })
	expect(res.status(), `GET ${url} must answer 200`).toBe(200)
	return await res.json()
}

/**
 * Pull the first usable relation id out of a consolidated-entities payload.
 *
 * @param payload The entities response.
 *
 * @return The relation id.
 */
function firstRelationId(payload: Record<string, unknown>): number {
	const list = (payload.entities ?? payload.results ?? []) as Array<
		Record<string, unknown>
	>
	for (const e of list) {
		const ids = e.relationIds as number[] | undefined
		if (Array.isArray(ids) === true && ids.length > 0) return ids[0]
	}
	throw new Error(
		'no entity in the batch carries a relationId — the fixture failed to produce '
			+ 'relations, so nothing below would be testing the listener',
	)
}

/**
 * Count publication-consent records whose entity text matches the fixture term.
 *
 * @param req The API request context.
 * @param f   The provisioned fixture.
 *
 * @return How many records match.
 */
async function consentCount(req: APIRequestContext, f: Fixture): Promise<number> {
	const res = await req.get(`${API}/consents`, { headers: jsonHeaders(f.token) })
	expect(res.status(), `GET ${API}/consents must answer 200`).toBe(200)
	const body = await res.json()
	const rows = (body.results ?? body.consents ?? body ?? []) as Array<
		Record<string, unknown>
	>
	if (Array.isArray(rows) === false) return 0
	return rows.filter((r) => String(r.entityText ?? '').includes(TERM)).length
}

/**
 * PATCH a relation the way the review UI does.
 *
 * @param req     The API request context.
 * @param f       The provisioned fixture.
 * @param id      The relation id.
 * @param payload The fields to send.
 *
 * @return The response status.
 */
async function patchRelation(
	req: APIRequestContext,
	f: Fixture,
	id: number,
	payload: Record<string, unknown>,
): Promise<number> {
	const res = await req.patch(
		`/index.php/apps/openregister/api/entity-relations/${id}`,
		{ headers: jsonHeaders(f.token), data: payload },
	)
	return res.status()
}

test.describe('entity-relation decision listener', () => {
	test.slow()

	// ---------------------------------------------------------------------
	// THE POSITIVE CONTROL. Runs first, deliberately.
	// @e2e consent-management::skip-activation-triggers-consent-creation
	// ---------------------------------------------------------------------
	test('skip activation creates a consent record for the entity', async ({
		page,
		request,
	}) => {
		const f = await provision(page, request)
		const relationId = firstRelationId(await entities(request, f))

		const before = await consentCount(request, f)

		const status = await patchRelation(request, f, relationId, {
			skipAnonymization: true,
		})
		expect(status, 'the relation PATCH must succeed').toBeLessThan(300)

		// POLLED, BUT ON A SHORT LEASH. The listener runs synchronously inside
		// the PATCH, so the record should already exist when the response
		// returns; the 15s window covers OpenRegister's own write settling, not
		// an asynchronous listener. Keeping it short matters — a generous
		// timeout here would let a listener that never fires look merely slow.
		await expect
			.poll(async () => await consentCount(request, f), {
				message:
					'activating skipAnonymization must create a publicationConsent record; '
					+ 'if this count never rises, nothing is subscribed to '
					+ 'EntityRelationDecisionUpdatedEvent (see #805)',
				timeout: 15_000,
			})
			.toBeGreaterThan(before)
	})

	// ---------------------------------------------------------------------
	// @e2e consent-management::bases-only-change-does-not-trigger-consent-creation
	// ---------------------------------------------------------------------
	test('a bases-only change creates no consent record', async ({
		page,
		request,
	}) => {
		const f = await provision(page, request)
		const relationId = firstRelationId(await entities(request, f))

		const before = await consentCount(request, f)

		const status = await patchRelation(request, f, relationId, {
			bases: ['persoonsgegevens'],
		})
		expect(status, 'the relation PATCH must succeed').toBeLessThan(300)

		await page.waitForTimeout(2000)
		expect(
			await consentCount(request, f),
			'a bases-only edit must not create a consent record — skipAnonymization is '
				+ 'absent from the diff, so isSkipAnonymizationActivated() is false',
		).toBe(before)
	})

	// ---------------------------------------------------------------------
	// @e2e consent-management::reversal-event-does-not-trigger-consent-creation
	// ---------------------------------------------------------------------
	test('a reversal creates no consent record and leaves the existing one alone', async ({
		page,
		request,
	}) => {
		const f = await provision(page, request)
		const relationId = firstRelationId(await entities(request, f))

		// Establish the true -> false direction this scenario is about.
		await patchRelation(request, f, relationId, { skipAnonymization: true })
		await page.waitForTimeout(2000)
		const afterActivation = await consentCount(request, f)

		const status = await patchRelation(request, f, relationId, {
			skipAnonymization: false,
		})
		expect(status, 'the reversal PATCH must succeed').toBeLessThan(300)

		await page.waitForTimeout(2000)
		expect(
			await consentCount(request, f),
			'a reversal must neither create a record nor remove the one the activation '
				+ 'created — the listener takes no action in this direction',
		).toBe(afterActivation)
	})
})
