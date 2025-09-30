/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { describe, it, expect } from 'vitest'
import { Anonymization } from './anonymization'

describe('Anonymization Entity', () => {
	it('creates an anonymization instance with default values', () => {
		const anonymization = new Anonymization({})
		expect(anonymization.id).toBe('')
		expect(anonymization.nodeId).toBe(0)
		expect(anonymization.fileHash).toBe('')
		expect(anonymization.originalFileName).toBe('')
		expect(anonymization.anonymizedFileName).toBe('')
		expect(anonymization.anonymizedFilePath).toBe('')
		expect(anonymization.status).toBe('pending')
		expect(anonymization.message).toBe('')
		expect(anonymization.entities).toEqual([])
		expect(anonymization.replacements).toEqual([])
		expect(anonymization.startTime).toBe(0)
		expect(anonymization.endTime).toBe(null)
		expect(anonymization.processingTime).toBe(null)
	})

	it('creates an anonymization instance with provided values', () => {
		const data = {
			id: '230ea667-4f66-4040-8b9d-c2bfab86282d',
			nodeId: 12673,
			fileHash: '293bc95ff577f0d8faaf54477fc45304',
			originalFileName: 'test25.txt',
			anonymizedFileName: 'test25_anonymized.txt',
			anonymizedFilePath: '/path/to/test25_anonymized.txt',
			status: 'completed',
			message: 'Anonymization completed successfully',
			entities: [
				{
					entityType: 'PERSON',
					text: 'John Doe',
					score: 0.95,
					startPosition: 10,
					endPosition: 18,
				},
			],
			replacements: [
				{
					entityType: 'PERSON',
					originalText: 'John Doe',
					replacementText: '[PERSON]',
					key: 'abc123',
					start: 10,
					end: 18,
				},
			],
			startTime: 1742178348.186755,
			endTime: 1742178349.186755,
			processingTime: 1.0,
		}

		const anonymization = new Anonymization(data)
		expect(anonymization.id).toBe('230ea667-4f66-4040-8b9d-c2bfab86282d')
		expect(anonymization.nodeId).toBe(12673)
		expect(anonymization.fileHash).toBe('293bc95ff577f0d8faaf54477fc45304')
		expect(anonymization.originalFileName).toBe('test25.txt')
		expect(anonymization.anonymizedFileName).toBe('test25_anonymized.txt')
		expect(anonymization.anonymizedFilePath).toBe('/path/to/test25_anonymized.txt')
		expect(anonymization.status).toBe('completed')
		expect(anonymization.message).toBe('Anonymization completed successfully')
		expect(anonymization.entities).toHaveLength(1)
		expect(anonymization.entities[0].entityType).toBe('PERSON')
		expect(anonymization.replacements).toHaveLength(1)
		expect(anonymization.replacements[0].originalText).toBe('John Doe')
		expect(anonymization.startTime).toBe(1742178348.186755)
		expect(anonymization.endTime).toBe(1742178349.186755)
		expect(anonymization.processingTime).toBe(1.0)
	})

	it('validates a valid anonymization object', () => {
		const data = {
			id: '230ea667-4f66-4040-8b9d-c2bfab86282d',
			nodeId: 12673,
			fileHash: '293bc95ff577f0d8faaf54477fc45304',
			originalFileName: 'test25.txt',
			status: 'completed',
			replacements: [
				{
					entityType: 'PERSON',
					originalText: 'John Doe',
					replacementText: '[PERSON]',
					key: 'abc123',
					start: 10,
					end: 18,
				},
			],
		}

		const anonymization = new Anonymization(data)
		const result = anonymization.validate()
		expect(result.success).toBe(true)
	})

	it('validates an invalid anonymization object', () => {
		const data = {
			id: '230ea667-4f66-4040-8b9d-c2bfab86282d',
			nodeId: '12673', // Invalid nodeId type (should be number)
			status: 'invalid_status', // Invalid status value
		}

		const anonymization = new Anonymization(data)
		const result = anonymization.validate()
		expect(result.success).toBe(false)
	})

	it('counts entities by type', () => {
		const anonymization = new Anonymization({
			entities: [
				{ entityType: 'PERSON', text: 'John Doe', score: 0.95 },
				{ entityType: 'PERSON', text: 'Jane Smith', score: 0.92 },
				{ entityType: 'EMAIL', text: 'john@example.com', score: 0.98 },
				{ entityType: 'LOCATION', text: 'New York', score: 0.90 },
			],
		})

		const counts = anonymization.getEntityCounts()
		expect(counts.PERSON).toBe(2)
		expect(counts.EMAIL).toBe(1)
		expect(counts.LOCATION).toBe(1)
	})

	it('calculates success rate correctly', () => {
		const anonymization = new Anonymization({
			entities: [
				{ entityType: 'PERSON', text: 'John Doe', score: 0.95 },
				{ entityType: 'PERSON', text: 'Jane Smith', score: 0.92 },
				{ entityType: 'EMAIL', text: 'john@example.com', score: 0.98 },
			],
			replacements: [
				{ entityType: 'PERSON', originalText: 'John Doe', replacementText: '[PERSON]' },
				{ entityType: 'PERSON', originalText: 'Jane Smith', replacementText: '[PERSON]' },
			],
		})

		expect(anonymization.getSuccessRate()).toBeCloseTo(66.67, 1)
	})

	it('calculates processing time correctly', () => {
		const anonymization = new Anonymization({
			startTime: 1742178348.186755,
			endTime: 1742178349.186755,
		})

		expect(anonymization.getProcessingTime()).toBe(1.0)
	})

	it('returns 0 processing time when times are not set', () => {
		const anonymization = new Anonymization({
			startTime: null,
			endTime: null,
		})

		expect(anonymization.getProcessingTime()).toBe(0)
	})
})
