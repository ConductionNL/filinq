/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * llm-entity-detection-provider, task 5.2: an administrator switches an
 * organisation to the local language-model provider and tests it from the
 * admin settings; with the endpoint unreachable, detection falls back to the
 * default provider and the review says so; another organisation stays on the
 * default provider throughout.
 *
 * WHAT IT SEEDS ITSELF. Every test uploads its own small text document into
 * `/DocuDesk` (the folder My Documents lists), with an e-mail address in it so
 * the default detection finds something. It points the provider at a loopback
 * address nothing listens on (`http://127.0.0.1:9` unless
 * `FILINQ_E2E_UNREACHABLE_HERMIQ` names another), which `require_local`
 * allows and which never answers: that is the "endpoint unreachable" case,
 * made without a fault injector. Every test puts the detection settings and
 * the admin's active organisation back the way it found them.
 *
 * WHAT IT READS FROM THE INSTANCE. `FILINQ_E2E_HERMIQ_ENDPOINT` and
 * `FILINQ_E2E_HERMIQ_AGENTFLOW` name a live local Hermiq agentflow; with them
 * the configure-and-test scenario expects "available" and the
 * two-organisation scenario expects organisation A's run to be the language
 * model's own. Without them both scenarios still run, against the unreachable
 * endpoint, and assert the honest fallback instead, which the spec allows
 * ("uses the LLM provider (or its honest fallback)"). The two-organisation
 * scenario needs a second organisation the admin belongs to: it takes
 * `FILINQ_E2E_SECOND_ORGANISATION` (a uuid), else the second organisation
 * OpenRegister lists for the admin, and skips when there is none.
 *
 * WHERE "THE REPORT" IS. The spec says the fallback warning shows "in the
 * report". In the code it is in the extract payload (`detectionProvider`,
 * `detectionFellBack`, `detectionWarning`) and in the file viewer's review
 * sidebar (`detection-fallback-warning`), which is what task 3.2 built; the
 * batch CSV report carries no provider column. The test asserts both places
 * that exist.
 *
 * EXCLUDED, as the spec marks them `@e2e exclude`: default-provider
 * equivalence, the provider ids, the non-admin 403 matrix, the non-local
 * refusal, a live agentflow's own entities, the confidence clamp, the unknown
 * type and malformed output. PHPUnit covers them.
 *
 * Not run in the change that wrote it (decision 139): the live pass runs it.
 */

// @e2e openspec/changes/archive/2026-11-24-llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md#one-organisation-opts-into-the-llm-provider
// @e2e openspec/changes/archive/2026-11-24-llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md#llm-unavailable-falls-back-with-a-visible-warning
// @e2e openspec/changes/archive/2026-11-24-llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md#admin-configures-and-tests-the-llm-provider

import type { APIRequestContext, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { go, waitForAppReady, waitForNcContentReady } from './_helpers.ts'

/** filinq's detection-provider settings (admin). */
const SETTINGS_API = '/index.php/apps/filinq/api/detection-provider/settings'
const TEST_API = '/index.php/apps/filinq/api/detection-provider/test'

/** Detection of one file; `force` re-runs it. */
const extractApi = (fileId: number) => `/index.php/apps/filinq/api/anonymization/extract/${fileId}`

/** OpenRegister's organisations. */
const ORGS_API = '/index.php/apps/openregister/api/organisations'

/** The OCS header every call carries. */
const OCS = { 'OCS-APIRequest': 'true' }

/** The signed-in user the WebDAV seed writes for. */
const USER = process.env.NC_ADMIN_USER ?? 'admin'

/** The folder My Documents lists. */
const DAV_DIR = '/DocuDesk'

/** Endpoints (see the header). */
const LIVE_ENDPOINT = process.env.FILINQ_E2E_HERMIQ_ENDPOINT ?? ''
const LIVE_AGENTFLOW = process.env.FILINQ_E2E_HERMIQ_AGENTFLOW ?? 'e2e-entities'
const UNREACHABLE = process.env.FILINQ_E2E_UNREACHABLE_HERMIQ ?? 'http://127.0.0.1:9'
const SECOND_ORG = process.env.FILINQ_E2E_SECOND_ORGANISATION ?? ''

/** The server's fallback warning, English (DetectionProviderResolver). */
const FALLBACK_WARNING = 'LLM detection unavailable — fell back to the default detection'

/** What the settings endpoint answers. */
interface DetectionSettings {
	providers: Record<string, string>
	endpoint: string
	agentflow: string
	requireLocal: boolean
}

/** What an extract answers, as far as these tests read it. */
interface Extraction {
	entities: Array<{ type: string; value: string }>
	detectionProvider?: string
	detectionFellBack?: boolean
	detectionWarning?: string | null
}

/**
 * Read the detection settings.
 *
 * @param request The authenticated request context.
 * @return The settings.
 */
async function readSettings(request: APIRequestContext): Promise<DetectionSettings> {
	const res = await request.get(SETTINGS_API, { headers: OCS })
	expect(res.status(), `GET ${SETTINGS_API}`).toBe(200)

	return res.json()
}

/**
 * Write detection settings.
 *
 * @param request The authenticated request context.
 * @param data The fields to write.
 * @return The settings after the write.
 */
async function writeSettings(request: APIRequestContext, data: Record<string, unknown>): Promise<DetectionSettings> {
	const res = await request.put(SETTINGS_API, { headers: OCS, data })
	expect(res.status(), `PUT ${SETTINGS_API} ${JSON.stringify(data)} -> ${await res.text().catch(() => '')}`).toBe(200)

	return res.json()
}

/**
 * Put the settings back: the endpoint fields, and each named organisation's provider.
 *
 * @param request The authenticated request context.
 * @param saved The settings as they were.
 * @param organisations The organisations whose provider the test may have changed.
 * @return Resolves once restored.
 */
async function restoreSettings(request: APIRequestContext, saved: DetectionSettings, organisations: string[]): Promise<void> {
	await writeSettings(request, { endpoint: saved.endpoint, agentflow: saved.agentflow, requireLocal: saved.requireLocal })
	for (const organisation of organisations) {
		await writeSettings(request, { organisation, provider: saved.providers[organisation] ?? 'default' })
	}
}

/**
 * The admin's organisations and the active one.
 *
 * @param request The authenticated request context.
 * @return The organisation uuids and the active uuid.
 */
async function organisations(request: APIRequestContext): Promise<{ all: string[]; active: string }> {
	const res = await request.get(ORGS_API, { headers: OCS })
	expect(res.status(), `GET ${ORGS_API}`).toBe(200)
	const body = await res.json()
	const all = (Array.isArray(body.results) ? body.results : [])
		.map((row: { uuid?: string }) => String(row.uuid ?? ''))
		.filter((uuid: string) => uuid !== '')
	const active = String(body.active?.uuid ?? all[0] ?? '')
	expect(active, 'the admin has an active organisation').not.toBe('')

	return { all, active }
}

/**
 * Make an organisation the admin's active one.
 *
 * @param request The authenticated request context.
 * @param uuid The organisation.
 * @return Resolves once switched.
 */
async function setActive(request: APIRequestContext, uuid: string): Promise<void> {
	const res = await request.post(`${ORGS_API}/${uuid}/set-active`, { headers: OCS })
	expect(res.ok(), `set-active ${uuid} -> ${res.status()}`).toBeTruthy()
}

/**
 * Upload a text document into /DocuDesk and answer its file id.
 *
 * @param request The authenticated request context.
 * @param name The file name.
 * @return The file id.
 */
async function seedDocument(request: APIRequestContext, name: string): Promise<number> {
	const url = `/remote.php/dav/files/${USER}${DAV_DIR}/${encodeURIComponent(name)}`
	await request.fetch(`/remote.php/dav/files/${USER}${DAV_DIR}`, { method: 'MKCOL', headers: OCS }).catch(() => null)
	const put = await request.put(url, {
		headers: OCS,
		data: `Notitie ${name}.\n\nNeem contact op met marieke.jansen@example.nl over het verzoek.\n`,
	})
	expect([201, 204], `PUT ${name} -> ${put.status()}`).toContain(put.status())

	const find = await request.fetch(url, {
		method: 'PROPFIND',
		headers: { ...OCS, Depth: '0', 'Content-Type': 'application/xml' },
		data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns"><d:prop><oc:fileid/></d:prop></d:propfind>',
	})
	expect(find.status()).toBe(207)
	const match = /<oc:fileid>(\d+)<\/oc:fileid>/.exec(await find.text())
	expect(match, 'PROPFIND answers the file id').not.toBeNull()

	return Number(match![1])
}

/**
 * Run detection on a file again, for the active organisation.
 *
 * @param request The authenticated request context.
 * @param fileId The file.
 * @return The extract answer.
 */
async function detect(request: APIRequestContext, fileId: number): Promise<Extraction> {
	const res = await request.post(extractApi(fileId), { headers: OCS, data: { force: true } })
	expect(res.status(), `extract ${fileId} -> ${await res.text().catch(() => '')}`).toBe(200)

	return res.json()
}

/**
 * The entity set of an extract, as sorted `type:value` keys.
 *
 * @param extraction The extract answer.
 * @return The keys.
 */
function entityKeys(extraction: Extraction): string[] {
	return (extraction.entities ?? []).map((e) => `${e.type}:${e.value}`).sort()
}

/**
 * Open filinq's admin settings at the detection-provider section.
 *
 * @param page The page.
 * @return Resolves once the section shows.
 */
async function openDetectionSection(page: Page): Promise<void> {
	await page.goto('/index.php/settings/admin/filinq', { waitUntil: 'domcontentloaded' })
	await waitForNcContentReady(page)
	await expect(page.getByRole('heading', { name: 'Entity detection provider' })).toBeVisible({ timeout: 30_000 })
}

test.describe('the detection provider per organisation', () => {
	test.setTimeout(240_000)

	test('the admin sets the language model with a local endpoint, saves, and tests the connection', async ({ page, request }) => {
		const saved = await readSettings(request)
		const { active } = await organisations(request)
		const endpoint = LIVE_ENDPOINT !== '' ? LIVE_ENDPOINT : UNREACHABLE

		try {
			await openDetectionSection(page)

			await page.getByTestId('detection-settings-provider').click()
			await page.getByRole('option', { name: 'Local language model (Hermiq agentflow)' }).click()
			await page.getByLabel('Hermiq endpoint').fill(endpoint)
			await page.getByLabel('Agentflow').fill(LIVE_AGENTFLOW)

			const savedAnswer = page.waitForResponse((r) => r.url().includes('/api/detection-provider/settings') && r.request().method() === 'PUT')
			await page.getByTestId('detection-settings-save').click()
			expect((await savedAnswer).status()).toBe(200)
			await expect(page.getByText('Detection settings saved')).toBeVisible()

			const stored = await readSettings(request)
			expect(stored.providers[active], 'the active organisation now uses the language model').toBe('llm')
			expect(stored.endpoint).toBe(endpoint)
			expect(stored.agentflow).toBe(LIVE_AGENTFLOW)

			const tested = page.waitForResponse((r) => r.url().includes('/api/detection-provider/test'))
			await page.getByTestId('detection-settings-test').click()
			const verdict = await (await tested).json()
			const result = page.getByTestId('detection-settings-test-result')
			if (LIVE_ENDPOINT !== '') {
				expect(verdict.available, JSON.stringify(verdict)).toBe(true)
				await expect(result).toContainText('The language model provider answers and can be used.')
			} else {
				expect(verdict).toEqual({ available: false, reason: 'unreachable' })
				await expect(result).toContainText('The endpoint did not answer. Check that Hermiq runs and the address is right.')
			}

			// The API's own test answers the same as the button showed.
			const direct = await request.post(TEST_API, { headers: OCS })
			expect(direct.status()).toBe(200)
			expect((await direct.json()).available).toBe(verdict.available)
		} finally {
			await restoreSettings(request, saved, [active])
		}
	})

	test('with the endpoint unreachable, detection falls back to the default provider and the review warns', async ({ page, request }) => {
		const saved = await readSettings(request)
		const { active } = await organisations(request)
		const base = `llm-fallback-e2e-${Date.now()}`
		const fileId = await seedDocument(request, `${base}.txt`)

		try {
			await writeSettings(request, { endpoint: UNREACHABLE, agentflow: 'e2e-entities', requireLocal: true })
			await writeSettings(request, { organisation: active, provider: 'llm' })

			// Through the UI: opening the document runs its first detection, and the sidebar says what ran.
			await go(page, 'my-documents')
			const row = page.locator('#content tr, .app-content tr').filter({ hasText: base }).first()
			await expect(row, 'the seeded document is listed in /DocuDesk').toBeVisible({ timeout: 30_000 })
			await row.click()
			await waitForAppReady(page)
			const warning = page.getByTestId('detection-fallback-warning')
			await expect(warning).toBeVisible({ timeout: 60_000 })
			await expect(warning).toContainText(FALLBACK_WARNING)
			await expect(page.getByTestId('detection-provider-note')).toHaveCount(0)

			// The payload names the engine that actually ran.
			const fellBack = await detect(request, fileId)
			expect(fellBack.detectionProvider).toBe('default')
			expect(fellBack.detectionFellBack).toBe(true)
			expect(String(fellBack.detectionWarning ?? '')).toContain(FALLBACK_WARNING)
			expect(fellBack.entities.length, 'the default provider found the e-mail address').toBeGreaterThan(0)

			// They are the default provider's own entities: the same set a plain default run finds.
			await writeSettings(request, { organisation: active, provider: 'default' })
			const plain = await detect(request, fileId)
			expect(plain.detectionProvider).toBe('default')
			expect(plain.detectionFellBack).toBe(false)
			expect(entityKeys(fellBack)).toEqual(entityKeys(plain))
		} finally {
			await restoreSettings(request, saved, [active])
		}
	})

	test('organisation A opts into the language model and organisation B stays on the default', async ({ request }) => {
		const saved = await readSettings(request)
		const { all, active } = await organisations(request)
		const orgA = active
		const orgB = SECOND_ORG !== '' ? SECOND_ORG : (all.find((uuid) => uuid !== orgA) ?? '')
		test.skip(orgB === '', 'FILINQ_E2E_SECOND_ORGANISATION is not set and the admin belongs to one organisation only')

		const base = `llm-two-orgs-e2e-${Date.now()}`
		const fileA = await seedDocument(request, `${base}-a.txt`)
		const fileB = await seedDocument(request, `${base}-b.txt`)

		try {
			await writeSettings(request, {
				endpoint: LIVE_ENDPOINT !== '' ? LIVE_ENDPOINT : UNREACHABLE,
				agentflow: LIVE_AGENTFLOW,
				requireLocal: true,
			})
			await writeSettings(request, { organisation: orgA, provider: 'llm' })
			const stored = await writeSettings(request, { organisation: orgB, provider: 'default' })
			expect(stored.providers[orgA]).toBe('llm')
			expect(stored.providers[orgB] ?? 'default', 'choosing default leaves B out of the map').toBe('default')

			await setActive(request, orgA)
			const runA = await detect(request, fileA)
			if (LIVE_ENDPOINT !== '') {
				expect(runA.detectionProvider, 'A ran on the language model').toBe('llm')
				expect(runA.detectionFellBack).toBe(false)
			} else {
				expect(runA.detectionProvider, 'A fell back honestly').toBe('default')
				expect(runA.detectionFellBack).toBe(true)
				expect(String(runA.detectionWarning ?? '')).toContain(FALLBACK_WARNING)
			}

			await setActive(request, orgB)
			const runB = await detect(request, fileB)
			expect(runB.detectionProvider, 'B uses the default provider').toBe('default')
			expect(runB.detectionFellBack, 'B never asked for the language model, so nothing fell back').toBe(false)

			// A's choice did not move B, and B's did not move A.
			const after = await readSettings(request)
			expect(after.providers[orgA]).toBe('llm')
			expect(after.providers[orgB] ?? 'default').toBe('default')
		} finally {
			await setActive(request, active).catch(() => {})
			await restoreSettings(request, saved, [orgA, orgB])
		}
	})
})
