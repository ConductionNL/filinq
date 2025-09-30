/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useReportStore } from './report'
import { Report } from '../../entities'

describe('Report Store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('sets report item', () => {
		const store = useReportStore()
		const report = { id: '1', documentTitle: 'Test Document' }
		store.setReportItem(report)
		expect(store.reportItem).toBeInstanceOf(Report)
		expect(store.reportItem.id).toBe('1')
		expect(store.reportItem.documentTitle).toBe('Test Document')
	})

	it('sets report list', () => {
		const store = useReportStore()
		const reportList = [
			{ id: '1', documentTitle: 'Test Document 1' },
			{ id: '2', documentTitle: 'Test Document 2' },
		]
		store.setReportList(reportList)
		expect(store.reportList).toHaveLength(2)
		expect(store.reportList[0]).toBeInstanceOf(Report)
		expect(store.reportList[0].id).toBe('1')
		expect(store.reportList[1].id).toBe('2')
	})
})
