/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { describe, it, expect } from 'vitest'
import { Template } from './template'

describe('Template Entity', () => {
	it('creates a template instance with default values', () => {
		const template = new Template({})
		expect(template.id).toBe('')
		expect(template.name).toBe('')
		expect(template.content).toBe('')
		expect(template.category).toBe('')
		expect(template.outputFormat).toBe('html')
		expect(template.variables).toEqual([])
		expect(template.description).toBe('')
		expect(template.author).toBe('')
		expect(template.version).toBe('1.0.0')
	})

	it('creates a template instance with provided values', () => {
		const data = {
			id: '123',
			name: 'Test Template',
			content: 'Hello {{ name }}, welcome to {{ company }}!',
			category: 'Greeting',
			outputFormat: 'pdf',
			variables: ['name', 'company'],
			description: 'A test template',
			author: 'Test Author',
			version: '2.0.0',
			updated: '2023-01-01T00:00:00Z',
			created: '2022-01-01T00:00:00Z',
		}

		const template = new Template(data)
		expect(template.id).toBe('123')
		expect(template.name).toBe('Test Template')
		expect(template.content).toBe('Hello {{ name }}, welcome to {{ company }}!')
		expect(template.category).toBe('Greeting')
		expect(template.outputFormat).toBe('pdf')
		expect(template.variables).toEqual(['name', 'company'])
		expect(template.description).toBe('A test template')
		expect(template.author).toBe('Test Author')
		expect(template.version).toBe('2.0.0')
		expect(template.updated).toBe('2023-01-01T00:00:00Z')
		expect(template.created).toBe('2022-01-01T00:00:00Z')
	})

	it('validates a valid template object', () => {
		const data = {
			name: 'Test Template',
			content: 'Hello {{ name }}!',
		}

		const template = new Template(data)
		const result = template.validate()
		expect(result.success).toBe(true)
	})

	it('validates an invalid template object', () => {
		const data = {
			name: '', // Empty name
			content: '', // Empty content
		}

		const template = new Template(data)
		const result = template.validate()
		expect(result.success).toBe(false)
	})

	it('extracts variables from template content', () => {
		const template = new Template({
			content: 'Hello {{ name }}, your email is {{ email }}. Welcome to {{ company }}!',
		})

		const variables = template.extractVariables()
		expect(variables).toContain('name')
		expect(variables).toContain('email')
		expect(variables).toContain('company')
		expect(variables.length).toBe(3)
	})

	it('extracts variables with dots', () => {
		const template = new Template({
			content: 'Hello {{ user.name }}, your email is {{ user.email }}.',
		})

		const variables = template.extractVariables()
		expect(variables).toContain('user.name')
		expect(variables).toContain('user.email')
		expect(variables.length).toBe(2)
	})

	it('handles templates with no variables', () => {
		const template = new Template({
			content: 'Hello, this is a static template with no variables.',
		})

		const variables = template.extractVariables()
		expect(variables).toEqual([])
	})

	it('updates variables based on content', () => {
		const template = new Template({
			content: 'Hello {{ name }}, welcome to {{ company }}!',
			variables: [],
		})

		template.updateVariables()
		expect(template.variables).toContain('name')
		expect(template.variables).toContain('company')
		expect(template.variables.length).toBe(2)
	})
})
