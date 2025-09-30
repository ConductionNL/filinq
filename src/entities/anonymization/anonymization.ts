/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { SafeParseReturnType, z } from 'zod'
import { TAnonymization } from './anonymization.types'

/**
 * Anonymization entity class
 *
 * Represents an anonymization log for a document, tracking the anonymization process
 * and storing information about detected and replaced entities.
 *
 * @see website/docs/api/anonymization-logs.md for documentation
 *
 * @package docudesk
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
export class Anonymization implements TAnonymization {

	/**
	 * Unique identifier for the anonymization log
	 *
	 * @readonly
	 */
	public readonly id: string

	/**
	 * Nextcloud node ID of the original document
	 */
	public nodeId: number

	/**
	 * Hash of the file content
	 */
	public fileHash: string

	/**
	 * Original name of the document
	 */
	public originalFileName: string

	/**
	 * Name of the anonymized document
	 */
	public anonymizedFileName: string

	/**
	 * Path of the anonymized document
	 */
	public anonymizedFilePath: string

	/**
	 * Status of the anonymization operation
	 */
	public status: 'pending' | 'processing' | 'completed' | 'failed'

	/**
	 * Message about the anonymization process
	 */
	public message: string

	/**
	 * List of entities found during anonymization
	 */
	public entities: Array<{
        entityType: string;
        text: string;
        score: number;
        startPosition?: number;
        endPosition?: number;
    }>

	/**
	 * List of entity replacements made during anonymization
	 */
	public replacements: Array<{
        entityType: string;
        originalText: string;
        replacementText: string;
        key?: string;
        start?: number;
        end?: number;
    }>

	/**
	 * Start time of the anonymization process (timestamp)
	 */
	public startTime: number

	/**
	 * End time of the anonymization process (timestamp)
	 */
	public endTime: number | null

	/**
	 * Duration of the anonymization process in seconds
	 */
	public processingTime: number | null

	/**
	 * Creates a new Anonymization instance
	 *
	 * @param {TAnonymization} anonymization - Anonymization data
	 */
	constructor(anonymization: TAnonymization = {}) {
		this.id = anonymization.id || ''
		this.nodeId = anonymization.nodeId || 0
		this.fileHash = anonymization.fileHash || ''
		this.originalFileName = anonymization.originalFileName || ''
		this.anonymizedFileName = anonymization.anonymizedFileName || ''
		this.anonymizedFilePath = anonymization.anonymizedFilePath || ''
		this.status = anonymization.status || 'pending'
		this.message = anonymization.message || ''
		this.entities = anonymization.entities || []
		this.replacements = anonymization.replacements || []
		this.startTime = anonymization.startTime || 0
		this.endTime = anonymization.endTime || null
		this.processingTime = anonymization.processingTime || null
	}

	/**
	 * Validates the anonymization data
	 *
	 * @return {SafeParseReturnType<TAnonymization, unknown>} Validation result
	 */
	public validate(): SafeParseReturnType<TAnonymization, unknown> {
		const entitySchema = z.object({
			entityType: z.string(),
			text: z.string(),
			score: z.number(),
			startPosition: z.number().optional(),
			endPosition: z.number().optional(),
		})

		const replacementSchema = z.object({
			entityType: z.string(),
			originalText: z.string(),
			replacementText: z.string(),
			key: z.string().optional(),
			start: z.number().optional(),
			end: z.number().optional(),
		})

		const schema = z.object({
			id: z.string().optional(),
			nodeId: z.number().optional(),
			fileHash: z.string().optional(),
			originalFileName: z.string().optional(),
			anonymizedFileName: z.string().optional(),
			anonymizedFilePath: z.string().optional(),
			status: z.enum(['pending', 'processing', 'completed', 'failed']).optional(),
			message: z.string().optional(),
			entities: z.array(entitySchema).optional(),
			replacements: z.array(replacementSchema).optional(),
			startTime: z.number().optional(),
			endTime: z.number().nullable().optional(),
			processingTime: z.number().nullable().optional(),
		})

		return schema.safeParse(this)
	}

	/**
	 * Gets the entity counts by type
	 *
	 * @return {Record<string, number>} Entity counts by type
	 */
	public getEntityCounts(): Record<string, number> {
		const counts: Record<string, number> = {}

		this.entities.forEach(entity => {
			if (!counts[entity.entityType]) {
				counts[entity.entityType] = 0
			}
			counts[entity.entityType]++
		})

		return counts
	}

	/**
	 * Calculates the anonymization success rate
	 *
	 * @return {number} Success rate as a percentage
	 */
	public getSuccessRate(): number {
		if (this.entities.length === 0) {
			return 100
		}

		return (this.replacements.length / this.entities.length) * 100
	}

	/**
	 * Gets the processing time in seconds
	 *
	 * @return {number} Processing time in seconds
	 */
	public getProcessingTime(): number {
		if (!this.startTime || !this.endTime) {
			return 0
		}

		return this.endTime - this.startTime
	}

}
