/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTemplateStore } from './template'
import { Template } from '../../entities'

describe('Template Store', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('sets template item', () => {
		const store = useTemplateStore()
		const template = { id: '1', name: 'Test Template' }
		store.setTemplateItem(template)
		expect(store.templateItem).toBeInstanceOf(Template)
		expect(store.templateItem.id).toBe('1')
		expect(store.templateItem.name).toBe('Test Template')
	})

	it('sets template list', () => {
		const store = useTemplateStore()
		const templateList = [
			{ id: '1', name: 'Test Template 1' },
			{ id: '2', name: 'Test Template 2' },
		]
		store.setTemplateList(templateList)
		expect(store.templateList).toHaveLength(2)
		expect(store.templateList[0]).toBeInstanceOf(Template)
		expect(store.templateList[0].id).toBe('1')
		expect(store.templateList[1].id).toBe('2')
	})

	it('extracts variables from template content', () => {
		const template = new Template({
			id: '1',
			name: 'Test Template',
			content: 'Hello {{ name }}, welcome to {{ company }}!',
		})
		const variables = template.extractVariables()
		expect(variables).toContain('name')
		expect(variables).toContain('company')
		expect(variables.length).toBe(2)
	})
})
