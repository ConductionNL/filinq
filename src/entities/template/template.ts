/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { SafeParseReturnType, z } from 'zod'
import { TTemplate } from './template.types'

export class Template implements TTemplate {

	public id: string
	public name: string
	public content: string
	public category: string
	public outputFormat: string
	public variables: Array<string>
	public description: string
	public author: string
	public version: string
	public updated: string
	public created: string

	constructor(template: TTemplate) {
		this.id = template.id || ''
		this.name = template.name || ''
		this.content = template.content || ''
		this.category = template.category || ''
		this.outputFormat = template.outputFormat || 'html'
		this.variables = template.variables || []
		this.description = template.description || ''
		this.author = template.author || ''
		this.version = template.version || '1.0.0'
		this.updated = template.updated || ''
		this.created = template.created || ''
	}

	public validate(): SafeParseReturnType<TTemplate, unknown> {
		const schema = z.object({
			id: z.string().optional(),
			name: z.string().min(1),
			content: z.string().min(1),
			category: z.string().optional(),
			outputFormat: z.string().optional(),
			variables: z.array(z.string()).optional(),
			description: z.string().optional(),
			author: z.string().optional(),
			version: z.string().optional(),
			updated: z.string().optional(),
			created: z.string().optional(),
		})

		return schema.safeParse(this)
	}

	/**
	 * Extract variables from the template content
	 *
	 * @return {Array<string>} Array of variable names found in the template
	 */
	public extractVariables(): Array<string> {
		// Extract variables from the template content using regex
		// This regex looks for {{ variable }} pattern in Twig templates
		const regex = /\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/g
		const variables = new Set<string>()
		let match

		while ((match = regex.exec(this.content)) !== null) {
			variables.add(match[1])
		}

		return Array.from(variables)
	}

	/**
	 * Update the variables array based on the template content
	 */
	public updateVariables(): void {
		this.variables = this.extractVariables()
	}

}
