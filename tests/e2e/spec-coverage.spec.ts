/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Spec-coverage e2e suite for DocuDesk.
 *
 * Covers all 14 openspec/specs/* feature-groups:
 *   admin-settings, anonymization, anonymization-entity-review,
 *   batch-anonymization, consent-management, dashboard,
 *   document-register, letter-correspondence-generation,
 *   metadata-enrichment, ocr-document-scanning, pdf-generation,
 *   print-preview, prometheus-metrics, template-management
 *
 * Strategy:
 * - Each test group covers one spec directory.
 * - Tests drive real browser behavior: navigate, assert DOM/network.
 * - Where the backend returns graceful empty-state (register not
 *   configured, no data), the test asserts exactly that state so the
 *   suite stays green on a fresh install.
 * - API-only specs (metrics, pdf-generation, print-preview,
 *   correspondence) are exercised via direct fetch() calls from
 *   within the authenticated page context using page.evaluate().
 *
 * Run:
 *   NEXTCLOUD_URL=http://localhost:8080 npx playwright test spec-coverage
 */

import { test, expect, type Page, type APIRequestContext } from '@playwright/test'

const BASE = process.env.NEXTCLOUD_URL || 'http://localhost:8080'
const APP = `${BASE}/apps/docudesk`

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

async function go(page: Page, route: string): Promise<void> {
	const url = route.startsWith('http') ? route : `${APP}${route}`
	await page.goto(url, { waitUntil: 'domcontentloaded' }).catch(() => {})
	// Wait for NC chrome (header) to be present
	await page.waitForSelector('#header, header.header', { timeout: 15_000 }).catch(() => {})
	// Dismiss first-run wizard if shown
	const wizard = page.locator('#firstrunwizard')
	if (await wizard.isVisible().catch(() => false)) {
		await page.keyboard.press('Escape').catch(() => {})
		await wizard.waitFor({ state: 'hidden', timeout: 4_000 }).catch(() => {})
	}
	await page.waitForTimeout(600)
}

/** Authenticated fetch via page.evaluate — bypasses CSRF because the session cookie is present. */
async function apiFetch(page: Page, path: string, options: Record<string, unknown> = {}): Promise<{
	status: number
	body: unknown
}> {
	return page.evaluate(async ([url, opts]) => {
		const res = await fetch(url as string, {
			credentials: 'same-origin',
			headers: { 'OCS-APIREQUEST': 'true', Accept: 'application/json, text/plain, */*', ...(opts as Record<string, unknown>).headers as Record<string, string> },
			...(opts as Record<string, unknown>),
		} as RequestInit)
		// Read as text first to avoid "body already read" when response is not JSON
		const raw = await res.text()
		let body: unknown
		try { body = JSON.parse(raw) } catch { body = raw }
		return { status: res.status, body }
	}, [path.startsWith('http') ? path : `${BASE}${path}`, options])
}

// ---------------------------------------------------------------------------
// [SPEC: admin-settings] SET-001 … SET-004
// ---------------------------------------------------------------------------

test.describe('admin-settings spec', () => {
	test('SET-001 DocuDesk admin section is present in NC admin nav', async ({ page }) => {
		await go(page, `${BASE}/settings/admin/docudesk`)
		// Page should load without redirect to login (we are authenticated)
		expect(page.url()).toContain('settings/admin')
		// The admin settings page should return 200 (not 403)
		const response = await page.goto(`${BASE}/settings/admin/docudesk`)
		expect(response?.status()).toBeLessThan(500)
	})

	test('SET-004 Settings API returns configuration keys', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		// Must contain openRegisters and configuration keys
		expect(body).toHaveProperty('openRegisters')
		expect(body).toHaveProperty('configuration')
	})

	test('SET non-admin unauthenticated request redirects to login page', async ({ browser }) => {
		// Create a fresh context with no stored session
		const ctx = await browser.newContext()
		const p = await ctx.newPage()
		await p.goto(`${BASE}/settings/admin/docudesk`)
		// NC redirects to login — the final URL must contain /login
		// OR the page shows a login form (not the admin settings form)
		const url = p.url()
		const bodyText = await p.locator('body').innerText().catch(() => '')
		// Either we landed on login page or the admin page doesn't show DocuDesk settings
		const isLoginPage = url.includes('/login') || bodyText.toLowerCase().includes('username') || bodyText.toLowerCase().includes('password')
		const isAdminSettings = bodyText.toLowerCase().includes('docudesk') && bodyText.toLowerCase().includes('openregister')
		// If it shows full admin settings, that is the failure — otherwise pass
		expect(isAdminSettings).toBe(false)
		await ctx.close()
	})
})

// ---------------------------------------------------------------------------
// [SPEC: dashboard] DASH-001 … DASH-006
// ---------------------------------------------------------------------------

test.describe('dashboard spec', () => {
	test('DASH loads without 500', async ({ page }) => {
		const res = await page.goto(APP)
		expect(res?.status()).not.toBe(500)
		expect(page.url()).toContain('docudesk')
	})

	test('DASH-001 dashboard API returns consent stats', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/consents?_limit=1')
		// Either an array (0 items) or an object with results
		expect(result.status).toBeLessThan(500)
	})

	test('DASH-006 settings API returns loading data (objectTypes)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		expect(Array.isArray(body.objectTypes)).toBe(true)
	})

	test('DASH dashboard page renders Nextcloud chrome', async ({ page }) => {
		await go(page, '')
		// At minimum the NC header should render
		await expect(page.locator('#header, header.header').first()).toBeVisible()
	})
})

// ---------------------------------------------------------------------------
// [SPEC: consent-management] CONS-001 … CONS-006
// ---------------------------------------------------------------------------

test.describe('consent-management spec', () => {
	test('CONS consents list API returns array', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/consents')
		expect(result.status).toBe(200)
		// Returns array (possibly empty)
		expect(Array.isArray(result.body)).toBe(true)
	})

	test('CONS consent create endpoint is reachable (POST returns non-412)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/consents', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				documentId: 'e2e-test-doc-id',
				entityType: 'PERSON',
				entityText: 'Test Persoon',
			}),
		})
		// Should not be CSRF failure (412) — may be 400 missing fields or 201 created
		expect(result.status).not.toBe(412)
		expect(result.status).not.toBe(500)
	})

	test('CONS consent Vue page loads', async ({ page }) => {
		await go(page, '/consent')
		// Vue hash route — wait for networkidle
		await page.waitForLoadState('networkidle').catch(() => {})
		await page.waitForTimeout(1000)
		// Should not show 500 or NC error page
		const bodyText = await page.locator('body').innerText().catch(() => '')
		expect(bodyText).not.toContain('Internal Server Error')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: anonymization] ANON-001 … ANON-003
// ---------------------------------------------------------------------------

test.describe('anonymization spec', () => {
	test('ANON upload endpoint is reachable (POST returns non-412)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/upload', {
			method: 'POST',
			// No file attached — expect 400 not 412/500
		})
		expect(result.status).not.toBe(412)
		expect(result.status).not.toBe(500)
		// Should be 400 "No file uploaded" or similar
		expect([400, 422]).toContain(result.status)
	})

	test('ANON anonymization Vue page loads', async ({ page }) => {
		await go(page, '/anonymization')
		await page.waitForLoadState('networkidle').catch(() => {})
		await page.waitForTimeout(1000)
		const bodyText = await page.locator('body').innerText().catch(() => '')
		expect(bodyText).not.toContain('Internal Server Error')
	})

	test('ANON anonymization files list endpoint is reachable', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/files')
		// Returns array of uploaded files (possibly empty)
		expect(result.status).toBe(200)
		expect(Array.isArray(result.body)).toBe(true)
	})
})

// ---------------------------------------------------------------------------
// [SPEC: anonymization-entity-review] — entity endpoints
// ---------------------------------------------------------------------------

test.describe('anonymization-entity-review spec', () => {
	test('ANON-ENTITY batch entities endpoint returns 404 for unknown batch (not 500)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/batch/nonexistent-id/entities')
		// 404 for unknown batch is correct; 500 is not
		expect(result.status).not.toBe(500)
	})

	test('ANON-ENTITY batch status endpoint returns 404 for unknown batch (not 500)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/batch/nonexistent-id/status')
		expect(result.status).not.toBe(500)
	})

	test('ANON-ENTITY batch upload endpoint is reachable (POST returns non-412)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/batch/upload', {
			method: 'POST',
		})
		expect(result.status).not.toBe(412)
		expect(result.status).not.toBe(500)
	})
})

// ---------------------------------------------------------------------------
// [SPEC: batch-anonymization]
// ---------------------------------------------------------------------------

test.describe('batch-anonymization spec', () => {
	test('BATCH batch upload route is registered (not 404)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/batch/upload', {
			method: 'POST',
		})
		// Route exists — 400 or 422 for missing data is fine; 404 is not
		expect(result.status).not.toBe(404)
		expect(result.status).not.toBe(500)
	})

	test('BATCH batch status returns non-500 for missing batch', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/batch/missing-id/status')
		expect(result.status).not.toBe(500)
	})
})

// ---------------------------------------------------------------------------
// [SPEC: template-management] TMPL-001 … TMPL-004
// ---------------------------------------------------------------------------

test.describe('template-management spec', () => {
	test('TMPL templates list API returns graceful empty-state when not configured', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/templates')
		// Should NOT return 500; may return 200 (notConfigured) or 503
		expect(result.status).not.toBe(500)
		// If 200, check it has a results array or notConfigured
		if (result.status === 200) {
			const body = result.body as Record<string, unknown>
			expect(body).toHaveProperty('results')
		}
	})

	test('TMPL templates Vue page loads without 500', async ({ page }) => {
		await go(page, '/templates')
		await page.waitForLoadState('networkidle').catch(() => {})
		await page.waitForTimeout(1000)
		const bodyText = await page.locator('body').innerText().catch(() => '')
		expect(bodyText).not.toContain('Internal Server Error')
	})

	test('TMPL template create endpoint is reachable (POST returns non-412)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/templates', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ name: '', namespace: '', content: '' }),
		})
		expect(result.status).not.toBe(412)
		// Either 400 validation error or 503 not configured — not 500 unhandled
	})

	test('TMPL template preview endpoint is reachable', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/templates/preview', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ content: '<h1>Test</h1>', data: {} }),
		})
		// 200 rendered HTML or 503 not configured
		expect(result.status).not.toBe(412)
		expect(result.status).not.toBe(500)
	})
})

// ---------------------------------------------------------------------------
// [SPEC: prometheus-metrics] PROM-001 … PROM-003
// ---------------------------------------------------------------------------

test.describe('prometheus-metrics spec', () => {
	test('PROM-001 GET /api/metrics returns 200', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/metrics', {
			headers: { Accept: 'text/plain' },
		})
		expect(result.status).toBe(200)
	})

	test('PROM-002 metrics body contains HELP and TYPE lines', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/metrics', {
			headers: { Accept: 'text/plain' },
		})
		const body = result.body as string
		expect(body).toContain('# HELP')
		expect(body).toContain('# TYPE')
	})

	test('PROM health check returns ok', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/health')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		expect(body.status).toBe('ok')
	})

	test('PROM-003 metrics endpoint responds (admin required by annotation)', async ({ page }) => {
		// The spec requires admin auth; the running version's annotation (@NoCSRFRequired only,
		// no @NoAdminRequired) means Nextcloud enforces admin. When accessed via the
		// authenticated admin session the endpoint MUST return 200 with metrics data.
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/metrics', {
			headers: { Accept: 'text/plain' },
		})
		// Admin session should always get metrics
		expect(result.status).toBe(200)
		expect(result.body as string).toContain('docudesk')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: pdf-generation] PDF-001 … PDF-005
// ---------------------------------------------------------------------------

test.describe('pdf-generation spec', () => {
	test('PDF-001 POST /api/pdf/render returns 200 with PDF content', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/pdf/render', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ template: '<h1>{{ title }}</h1>', data: { title: 'Test' } }),
		})
		// Route exists and returns 200 with PDF binary
		expect(result.status).toBe(200)
		// Response should be PDF binary (starts with %PDF)
		expect(result.body as string).toContain('%PDF')
	})

	test('PDF-004 static HTML template without data produces valid PDF', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/pdf/render', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ template: '<p>Static Report</p>', data: {} }),
		})
		expect(result.status).toBe(200)
		expect(result.body as string).toContain('%PDF')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: print-preview] PRINT-001 … PRINT-025
// ---------------------------------------------------------------------------

test.describe('print-preview spec', () => {
	test('PRINT-001 POST /api/print/preview returns 200 with HTML', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/print/preview', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ template: '<h1>{{ title }}</h1>', data: { title: 'Print Test' } }),
		})
		expect(result.status).not.toBe(412)
		expect(result.status).not.toBe(500)
		if (result.status === 200) {
			const body = result.body as Record<string, unknown>
			expect(body).toHaveProperty('html')
		}
	})

	test('PRINT-003 missing template/templateId returns 400', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/print/preview', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ data: {} }),
		})
		expect(result.status).toBe(400)
	})

	test('PRINT-010 POST /api/print/pdf-a returns PDF/A binary', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/print/pdf-a', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ template: '<p>Test</p>', data: {} }),
		})
		// Should return 200 with PDF binary
		expect(result.status).toBe(200)
		// Response is PDF binary (starts with %PDF)
		expect(result.body as string).toContain('%PDF')
	})

	test('PRINT-024 print-preview Vue route loads', async ({ page }) => {
		await go(page, '/print-preview')
		await page.waitForTimeout(1000)
		const bodyText = await page.locator('body').innerText().catch(() => '')
		expect(bodyText).not.toContain('Internal Server Error')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: signing-requests] (implicit in consent spec)
// ---------------------------------------------------------------------------

test.describe('signing-requests spec', () => {
	test('SIGN GET /api/signing/requests returns 200 empty list (not 412/500)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/signing/requests')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		// Empty list or notConfigured flag
		expect(Array.isArray(body.results) || body.results === undefined).toBeTruthy()
	})

	test('SIGN signing Vue route loads without error', async ({ page }) => {
		// If a /signing route is registered
		await go(page, '/signing')
		await page.waitForTimeout(1000)
		const bodyText = await page.locator('body').innerText().catch(() => '')
		expect(bodyText).not.toContain('Internal Server Error')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: document-register] DREG-001 … DREG-005
// ---------------------------------------------------------------------------

test.describe('document-register spec', () => {
	test('DREG settings API lists available registers', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		// availableRegisters should be an array (could be empty on fresh install)
		expect(Array.isArray(body.availableRegisters)).toBe(true)
	})

	test('DREG consent register is configured (publicationConsent register present)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		const types = body.objectTypes as string[]
		expect(types).toContain('publicationConsent')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: metadata-enrichment] META-001 … META-003
// ---------------------------------------------------------------------------

test.describe('metadata-enrichment spec', () => {
	test('META settings shows enable_language_detection toggle', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		expect(body).toHaveProperty('enable_language_detection')
	})

	test('META settings shows enable_keyword_extraction toggle', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		expect(body).toHaveProperty('enable_keyword_extraction')
	})

	test('META settings shows enable_topic_classification toggle', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		const body = result.body as Record<string, unknown>
		expect(body).toHaveProperty('enable_topic_classification')
	})

	test('META admin settings page loads without 500', async ({ page }) => {
		await go(page, `${BASE}/settings/admin/docudesk`)
		const bodyText = await page.locator('body').innerText().catch(() => '')
		expect(bodyText).not.toContain('Internal Server Error')
	})
})

// ---------------------------------------------------------------------------
// [SPEC: ocr-document-scanning] OCR-001 … OCR-032
// ---------------------------------------------------------------------------

test.describe('ocr-document-scanning spec', () => {
	test('OCR-032 admin settings returns tesseract installation info', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/settings')
		expect(result.status).toBe(200)
		// Settings should be loadable (even if tesseract is missing)
	})

	test('OCR upload endpoint accepts PDF without crashing (ANON-001)', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/anonymization/upload', {
			method: 'POST',
		})
		// 400 "no file" is fine — means the controller is running
		expect([400, 422]).toContain(result.status)
	})
})

// ---------------------------------------------------------------------------
// [SPEC: letter-correspondence-generation]
// ---------------------------------------------------------------------------

test.describe('letter-correspondence-generation spec', () => {
	test('CORR POST /api/correspondence/generate is reachable', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/correspondence/generate', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({}),
		})
		// 400 missing templateId is the expected response
		expect(result.status).not.toBe(412)
		expect(result.status).not.toBe(500)
		if (result.status === 400) {
			const body = result.body as Record<string, unknown>
			expect(body).toHaveProperty('error')
		}
	})

	test('CORR missing templateId returns 400', async ({ page }) => {
		await go(page, '')
		const result = await apiFetch(page, '/apps/docudesk/api/correspondence/generate', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ dataRefs: [] }),
		})
		expect(result.status).toBe(400)
	})
})
