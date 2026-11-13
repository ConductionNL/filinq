// @vitest-environment jsdom
/**
 * The theme the page is drawn in, from Nextcloud's body attributes and the
 * system preference, and the contract labels and money formatting.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { afterEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => '«' + text + '»' }))

const { getTheme } = await import('../../src/services/getTheme.js')
const labels = await import('../../src/services/contractLabels.js')

/**
 * Let the system say whether it prefers light.
 *
 * @param {boolean} light Whether `(prefers-color-scheme: light)` matches.
 * @return {void}
 */
function preferLight(light) {
	window.matchMedia = vi.fn((query) => ({ matches: query === '(prefers-color-scheme: light)' && light }))
}

afterEach(() => {
	for (const attribute of ['data-theme-light', 'data-theme-dark', 'data-theme-default']) {
		document.body.removeAttribute(attribute)
	}
	delete window.matchMedia
})

describe('getTheme', () => {
	it('is light when the body names no theme', () => {
		expect(getTheme()).toBe('light')
	})

	it('follows an explicit light or dark theme without asking the system', () => {
		window.matchMedia = vi.fn()

		document.body.setAttribute('data-theme-dark', '')
		expect(getTheme()).toBe('dark')

		document.body.setAttribute('data-theme-light', '')
		expect(getTheme()).toBe('light')

		expect(window.matchMedia).not.toHaveBeenCalled()
	})

	it('follows the system preference under the default theme', () => {
		document.body.setAttribute('data-theme-default', '')

		preferLight(true)
		expect(getTheme()).toBe('light')

		preferLight(false)
		expect(getTheme()).toBe('dark')
		expect(window.matchMedia).toHaveBeenCalledWith('(prefers-color-scheme: light)')
	})
})

describe('contractLabels', () => {
	it('translates known statuses, buckets and fields', () => {
		expect(labels.statusLabel('terminated')).toBe('«Terminated»')
		expect(labels.bucketLabel('noticeDue')).toBe('«Notice due within 30 days»')
		expect(labels.bucketLabel('expiring')).toBe('«Ending within 90 days»')
		expect(labels.suggestionFieldLabel('noticePeriodDays')).toBe('«Notice period in days»')
	})

	it('shows an unknown value as it is', () => {
		expect(labels.statusLabel('suspended')).toBe('suspended')
		expect(labels.bucketLabel('someday')).toBe('someday')
		expect(labels.suggestionFieldLabel('vatNumber')).toBe('vatNumber')
	})

	it('formats money in the given currency, euro by default', () => {
		const format = (value, currency) => new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value)

		expect(labels.money(1234.5)).toBe(format(1234.5, 'EUR'))
		expect(labels.money('99', 'USD')).toBe(format(99, 'USD'))
		expect(labels.money(5, '')).toBe(format(5, 'EUR'))
	})

	it('shows nothing for no value', () => {
		expect(labels.money(null)).toBe('')
		expect(labels.money(undefined)).toBe('')
		expect(labels.money('')).toBe('')
		expect(labels.money(0)).not.toBe('')
	})

	it('falls back to value and code for a currency the formatter refuses', () => {
		expect(labels.money(10, 'NOT-A-CODE')).toBe('10 NOT-A-CODE')
	})
})
