/* eslint-disable no-console */
import { createPinia, setActivePinia } from 'pinia'
import { useObjectStore } from './object.js'

/**
 * Mock settings response
 */
const mockSettings = {
	objectTypes: ['character', 'item', 'skill'],
	configuration: {
		character_source: 'openregister',
		character_schema: '105',
		character_register: '20',
		item_source: 'openregister',
		item_schema: '109',
		item_register: '20',
		skill_source: 'openregister',
		skill_schema: '110',
		skill_register: '20',
	},
}

/**
 * Mock collection response
 */
const mockCollection = {
	total: 2,
	page: 1,
	perPage: 10,
	results: [
		{ id: '1', name: 'Test 1' },
		{ id: '2', name: 'Test 2' },
	],
}

/**
 * Mock single object response
 */
const mockObject = {
	id: '1',
	name: 'Test Object',
	description: 'Test Description',
}

/**
 * Mock related data responses
 */
const mockRelatedData = {
	logs: {
		total: 1,
		page: 1,
		perPage: 10,
		results: [{ id: '1', action: 'create', timestamp: '2024-04-13T00:00:00Z' }],
	},
	uses: {
		total: 1,
		page: 1,
		perPage: 10,
		results: [{ id: '1', usedBy: 'character-1', timestamp: '2024-04-13T00:00:00Z' }],
	},
	used: {
		total: 1,
		page: 1,
		perPage: 10,
		results: [{ id: '1', usedIn: 'event-1', timestamp: '2024-04-13T00:00:00Z' }],
	},
	files: {
		total: 1,
		page: 1,
		perPage: 10,
		results: [{ id: '1', filename: 'test.pdf', size: 1024 }],
	},
}

// Mock fetch globally
global.fetch = jest.fn()

describe('ObjectStore', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())
		store = useObjectStore()
		// Reset fetch mock
		fetch.mockReset()
	})

	describe('Settings', () => {
		it('fetches settings successfully', async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockSettings),
			})

			await store.fetchSettings()

			expect(store.settings).toEqual(mockSettings)
			expect(store.objectTypes).toEqual(['character', 'item', 'skill'])
			expect(fetch).toHaveBeenCalledWith('/index.php/apps/opencatalogi/api/settings')
		})

		it('handles settings fetch error', async () => {
			fetch.mockResolvedValueOnce({
				ok: false,
			})

			await expect(store.fetchSettings()).rejects.toThrow('Failed to fetch settings')
		})
	})

	describe('Schema Configuration', () => {
		beforeEach(async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockSettings),
			})
			await store.fetchSettings()
		})

		it('gets schema configuration for valid object type', () => {
			const config = store.getSchemaConfig('character')
			expect(config).toEqual({
				source: 'openregister',
				schema: '105',
				register: '20',
			})
		})

		it('throws error for invalid object type', () => {
			expect(() => store.getSchemaConfig('invalid')).toThrow('Invalid configuration for object type: invalid')
		})
	})

	describe('Collection Operations', () => {
		beforeEach(async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockSettings),
			})
			await store.fetchSettings()
		})

		it('fetches collection successfully', async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockCollection),
			})

			await store.fetchCollection('character')

			expect(store.collections.character).toEqual(mockCollection.results)
			expect(store.objects.character).toEqual({
				1: { id: '1', name: 'Test 1' },
				2: { id: '2', name: 'Test 2' },
			})
			expect(store.isLoading('character')).toBe(false)
			expect(store.getError('character')).toBeNull()
		})

		it('handles collection fetch error', async () => {
			fetch.mockResolvedValueOnce({
				ok: false,
			})

			await expect(store.fetchCollection('character')).rejects.toThrow('Failed to fetch character collection')
			expect(store.isLoading('character')).toBe(false)
			expect(store.getError('character')).toBe('Failed to fetch character collection')
		})
	})

	describe('Single Object Operations', () => {
		beforeEach(async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockSettings),
			})
			await store.fetchSettings()
		})

		it('fetches single object successfully', async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockObject),
			})

			await store.fetchObject('character', '1')

			expect(store.objects.character['1']).toEqual(mockObject)
			expect(store.isLoading('character_1')).toBe(false)
			expect(store.getError('character_1')).toBeNull()
		})

		it('creates object successfully', async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockObject),
			})

			const newObject = await store.createObject('character', { name: 'New Character' })

			expect(newObject).toEqual(mockObject)
			expect(store.objects.character['1']).toEqual(mockObject)
			expect(store.isLoading('character_create')).toBe(false)
			expect(store.getError('character_create')).toBeNull()
		})

		it('updates object successfully', async () => {
			const updatedObject = { ...mockObject, name: 'Updated Name' }
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(updatedObject),
			})

			const result = await store.updateObject('character', '1', { name: 'Updated Name' })

			expect(result).toEqual(updatedObject)
			expect(store.objects.character['1']).toEqual(updatedObject)
			expect(store.isLoading('character_1')).toBe(false)
			expect(store.getError('character_1')).toBeNull()
		})

		it('deletes object successfully', async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
			})

			// Add object to store first
			store.objects.character = { 1: mockObject }
			store.collections.character = [mockObject]

			await store.deleteObject('character', '1')

			expect(store.objects.character['1']).toBeUndefined()
			expect(store.collections.character).toEqual([])
			expect(store.isLoading('character_1')).toBe(false)
			expect(store.getError('character_1')).toBeNull()
		})
	})

	describe('Active Object Operations', () => {
		beforeEach(async () => {
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockSettings),
			})
			await store.fetchSettings()

			// Mock related data fetches
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.logs),
			})
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.uses),
			})
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.used),
			})
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.files),
			})
		})

		it('sets active object and fetches related data', async () => {
			await store.setActiveObject('character', mockObject)

			expect(store.activeObjects.character).toEqual(mockObject)
			expect(store.relatedData.character).toEqual({
				logs: mockRelatedData.logs,
				uses: mockRelatedData.uses,
				used: mockRelatedData.used,
				files: mockRelatedData.files,
			})
		})

		it('clears active object and related data', async () => {
			await store.setActiveObject('character', mockObject)
			store.clearActiveObject('character')

			expect(store.activeObjects.character).toBeUndefined()
			expect(store.relatedData.character).toBeUndefined()
		})

		it('updates active object when fetching same object', async () => {
			await store.setActiveObject('character', mockObject)

			const updatedObject = { ...mockObject, name: 'Updated Name' }
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(updatedObject),
			})

			// Mock related data fetches again
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.logs),
			})
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.uses),
			})
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.used),
			})
			fetch.mockResolvedValueOnce({
				ok: true,
				json: () => Promise.resolve(mockRelatedData.files),
			})

			await store.fetchObject('character', '1')

			expect(store.activeObjects.character).toEqual(updatedObject)
		})

		it('clears active object when deleting it', async () => {
			await store.setActiveObject('character', mockObject)

			fetch.mockResolvedValueOnce({
				ok: true,
			})

			await store.deleteObject('character', '1')

			expect(store.activeObjects.character).toBeUndefined()
			expect(store.relatedData.character).toBeUndefined()
		})

		it('handles related data fetch error', async () => {
			fetch.mockReset()
			fetch.mockRejectedValueOnce(new Error('Network error'))

			await expect(store.fetchRelatedData('character', '1', 'logs')).rejects.toThrow()
			expect(store.getError('character_1_logs')).toBe('Network error')
			expect(store.isLoading('character_1_logs')).toBe(false)
		})
	})
})
