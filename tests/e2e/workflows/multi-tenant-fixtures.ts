/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Two organisations for the multi-tenant-hardening specs.
 *
 * WHAT IT SETS UP. Three run-stamped users: `adminA` creates organisation A
 * (and so administers it), `adminB` creates organisation B, and `memberA`
 * joins A as a plain member. Each user works in its OWN request context and
 * browser context, authenticated by basic auth with an EMPTY storage state,
 * so no admin session leaks into what a tenant user sees. Only the user
 * accounts themselves are made with the instance admin's stored session.
 *
 * Objects are created by the users, never by the admin, because OpenRegister
 * stamps an object with its creator's ACTIVE organisation; an object the
 * admin wrote would carry the admin's organisation and prove nothing.
 *
 * Base URL comes from the Playwright config (tests/e2e/base-url.ts); there is
 * no localhost fallback.
 */
import type { APIRequestContext, Browser, BrowserContext, Playwright } from '@playwright/test'

import { expect } from '@playwright/test'

export const OR_API = '/index.php/apps/openregister/api'
export const FILINQ_API = '/index.php/apps/filinq/api'

/** One tenant user: its credentials, its organisation and its API context. */
export interface TenantUser {
	userId: string
	password: string
	api: APIRequestContext
}

export interface Tenants {
	stamp: string
	orgA: string
	orgB: string
	orgAName: string
	orgBName: string
	adminA: TenantUser
	adminB: TenantUser
	memberA: TenantUser
	admin: APIRequestContext
}

const EMPTY_STATE = { cookies: [], origins: [] }

/** The group the register lets write templates and house styles. */
const TEMPLATE_EDITORS = 'filinq-template-editors'

/**
 * Parse a JSON body, tolerating an empty or HTML answer.
 *
 * @param text The body.
 * @return The parsed body, or `{raw}`.
 */
function parse(text: string): Record<string, unknown> {
	try {
		return JSON.parse(text)
	} catch {
		return { raw: text.slice(0, 300) }
	}
}

/**
 * Call an API and return its status and parsed body.
 *
 * @param api    The request context.
 * @param method The HTTP verb.
 * @param url    The url.
 * @param data   The JSON body, for a write.
 * @return The status and body.
 */
export async function call(
	api: APIRequestContext,
	method: string,
	url: string,
	data?: Record<string, unknown>,
): Promise<{ status: number; body: Record<string, unknown> }> {
	const res = await api.fetch(url, { method, data })
	return { status: res.status(), body: parse(await res.text()) }
}

/**
 * A request context for a user, by basic auth and with an empty storage state.
 *
 * @param playwright The Playwright instance.
 * @param baseURL    The instance.
 * @param userId     The user.
 * @param password   The password.
 * @return The context.
 */
async function userApi(playwright: Playwright, baseURL: string, userId: string, password: string): Promise<APIRequestContext> {
	return await playwright.request.newContext({
		baseURL,
		storageState: EMPTY_STATE,
		httpCredentials: { username: userId, password, send: 'always' },
		extraHTTPHeaders: { 'OCS-APIRequest': 'true', Accept: 'application/json' },
	})
}

/**
 * A browser context signed in as a tenant user, with an empty storage state.
 *
 * @param browser The browser.
 * @param user    The tenant user.
 * @return The context.
 */
export async function userBrowser(browser: Browser, user: TenantUser): Promise<BrowserContext> {
	// Basic auth signs in API calls but not Nextcloud's pages (they redirect
	// to the login form), so the browser signs in through the form.
	const context = await browser.newContext({ storageState: EMPTY_STATE })
	// A new user is shown the one-time support dialog and the walkthrough,
	// whose overlays swallow clicks; global-setup seeds the same flags for
	// the admin's stored state.
	await context.addInitScript(() => {
		try {
			window.localStorage.setItem('cn-support-dialog-shown:filinq', '1')
			window.localStorage.setItem('cn-walkthrough-seen:filinq', '999.0.0')
		} catch {
			/* localStorage unavailable: the overlays stay, the spec fails visibly */
		}
	})
	const page = await context.newPage()
	await page.goto('/index.php/login', { waitUntil: 'domcontentloaded', timeout: 45_000 })
	await page.locator('#user').fill(user.userId)
	await page.locator('#password').fill(user.password)
	await Promise.all([
		page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 45_000 }),
		page.locator('button[type=submit]').first().click(),
	])
	await page.close()
	return context
}

/**
 * Create a user through the provisioning API as the instance admin.
 *
 * @param admin    The admin's request context.
 * @param userId   The user id.
 * @param password The password.
 */
async function createUser(admin: APIRequestContext, userId: string, password: string): Promise<void> {
	const res = await admin.post('/ocs/v2.php/cloud/users?format=json', {
		headers: { 'OCS-APIRequest': 'true' },
		form: { userid: userId, password },
	})
	expect(res.status(), `could not create user ${userId}: ${(await res.text()).slice(0, 200)}`).toBeLessThan(400)
	// The tenant users seed templates and house styles, which the register
	// lets template editors write only (docs/authorization-decisions.md).
	const joined = await admin.post(`/ocs/v2.php/cloud/users/${userId}/groups?format=json`, {
		headers: { 'OCS-APIRequest': 'true' },
		form: { groupid: TEMPLATE_EDITORS },
	})
	expect(joined.status(), `could not add ${userId} to ${TEMPLATE_EDITORS}`).toBeLessThan(400)
}

/**
 * Create an organisation as a user and make it that user's active one.
 *
 * @param user The user.
 * @param name The organisation name.
 * @return The organisation uuid.
 */
async function createOrganisation(user: TenantUser, name: string): Promise<string> {
	const created = await call(user.api, 'POST', `${OR_API}/organisations`, { name, description: 'e2e multi-tenant-hardening' })
	expect(created.status, `could not create organisation ${name}: ${JSON.stringify(created.body).slice(0, 200)}`).toBeLessThan(400)
	const org = (created.body.organisation ?? created.body) as Record<string, unknown>
	const uuid = String(org.uuid ?? org.id ?? '')
	expect(uuid, 'the new organisation has a uuid').not.toBe('')
	await setActive(user, uuid)
	return uuid
}

/**
 * Make an organisation the user's active one, and check it took.
 *
 * @param user The user.
 * @param uuid The organisation.
 */
export async function setActive(user: TenantUser, uuid: string): Promise<void> {
	const res = await call(user.api, 'POST', `${OR_API}/organisations/${uuid}/set-active`)
	expect(res.status, `could not activate ${uuid} for ${user.userId}`).toBeLessThan(400)
	const active = await call(user.api, 'GET', `${OR_API}/organisations/active`)
	const org = (active.body.activeOrganisation ?? active.body.organisation ?? active.body) as Record<string, unknown>
	expect(String(org?.uuid ?? ''), `${user.userId} is active in ${uuid}`).toBe(uuid)
}

/**
 * Set up the three users and the two organisations.
 *
 * @param playwright The Playwright instance.
 * @param admin      The instance admin's request context (stored session).
 * @param baseURL    The instance.
 * @return The tenants.
 */
export async function setUpTenants(playwright: Playwright, admin: APIRequestContext, baseURL: string): Promise<Tenants> {
	const stamp = `${Date.now()}`
	const password = `E2e-mt-${stamp}-pw!`
	const ids = { adminA: `e2e-mt-a-${stamp}`, adminB: `e2e-mt-b-${stamp}`, memberA: `e2e-mt-m-${stamp}` }
	await admin.post('/ocs/v2.php/cloud/groups?format=json', {
		headers: { 'OCS-APIRequest': 'true' },
		form: { groupid: TEMPLATE_EDITORS },
	})
	for (const id of Object.values(ids)) {
		await createUser(admin, id, password)
	}
	const user = async (userId: string): Promise<TenantUser> => ({
		userId,
		password,
		api: await userApi(playwright, baseURL, userId, password),
	})
	const adminA = await user(ids.adminA)
	const adminB = await user(ids.adminB)
	const memberA = await user(ids.memberA)

	const orgAName = `E2E Demostad ${stamp}`
	const orgBName = `E2E Rivierstad ${stamp}`
	const orgA = await createOrganisation(adminA, orgAName)
	const orgB = await createOrganisation(adminB, orgBName)

	// memberA joins A through its admin, as a plain member.
	const joined = await call(adminA.api, 'POST', `${OR_API}/organisations/${orgA}/join`, { userId: memberA.userId })
	expect(joined.status, `could not add ${memberA.userId} to A: ${JSON.stringify(joined.body).slice(0, 200)}`).toBeLessThan(400)
	await setActive(memberA, orgA)

	// The caller's `request` fixture belongs to its beforeAll and cannot be
	// used again in afterAll, so the teardown gets an admin context of its own.
	const teardownAdmin = await playwright.request.newContext({
		baseURL,
		storageState: EMPTY_STATE,
		httpCredentials: {
			username: process.env.NC_ADMIN_USER ?? 'admin',
			password: process.env.NC_ADMIN_PASS ?? 'admin',
			send: 'always',
		},
		extraHTTPHeaders: { 'OCS-APIRequest': 'true', Accept: 'application/json' },
	})

	return { stamp, orgA, orgB, orgAName, orgBName, adminA, adminB, memberA, admin: teardownAdmin }
}

/**
 * Remove the run's users and dispose their contexts.
 *
 * @param tenants The tenants.
 */
export async function tearDownTenants(tenants: Tenants | undefined): Promise<void> {
	if (!tenants) return
	for (const user of [tenants.adminA, tenants.adminB, tenants.memberA]) {
		await user.api.dispose()
		await tenants.admin.delete(`/ocs/v2.php/cloud/users/${user.userId}?format=json`, {
			headers: { 'OCS-APIRequest': 'true' },
		})
	}
	await tenants.admin.dispose()
}

/**
 * Create a consent record as a user through filinq's own route.
 *
 * @param user  The user.
 * @param text  A distinctive entity text.
 * @return The created consent.
 */
export async function createConsent(user: TenantUser, text: string): Promise<Record<string, unknown>> {
	const res = await call(user.api, 'POST', `${FILINQ_API}/consents`, {
		documentId: `e2e-doc-${text}`,
		entityType: 'PERSON',
		entityText: text,
	})
	expect(res.status, `could not create a consent as ${user.userId}: ${JSON.stringify(res.body).slice(0, 300)}`).toBeLessThan(400)
	return (res.body.consent ?? res.body) as Record<string, unknown>
}

/**
 * The organisation an OpenRegister object carries in its envelope.
 *
 * @param object The object as OpenRegister serialises it.
 * @return The organisation uuid, or ''.
 */
export function organisationOf(object: Record<string, unknown>): string {
	const self = (object['@self'] ?? {}) as Record<string, unknown>
	const org = self.organisation ?? object.organisation
	if (org && typeof org === 'object') {
		return String((org as Record<string, unknown>).uuid ?? '')
	}
	return String(org ?? '')
}
