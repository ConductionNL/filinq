import { defineStore } from 'pinia'

/**
 * @typedef {object} Schema
 * @property {number} id - The schema ID
 * @property {string} uuid - The schema UUID
 * @property {string} slug - The schema slug
 * @property {string} title - The schema title
 * @property {object} properties - The schema properties
 */

/**
 * @typedef {object} Settings
 * @property {Array<string>} objectTypes - Available object types
 * @property {object} configuration - Configuration settings
 */

/** @typedef {{[key: string]: any}} ObjectState */
/** @typedef {ReturnType<typeof setTimeout>} Timer */

/**
 * @typedef {object} RelatedDataTypes
 * @property {object} logs - Log entries
 * @property {object} uses - Usage records
 * @property {object} used - Where object is used
 * @property {object} files - Associated files
 */

/**
 * Store for managing objects in OpenCatalogi.
 * @module Store
 * @package
 * @author Ruben Linde
 * @copyright 2024
 * @license AGPL-3.0-or-later
 * @version 1.0.0
 * @see {@link https://github.com/opencatalogi/opencatalogi}
 */
export const useObjectStore = defineStore('object', {
	state: () => ({
		/** @type {{objectTypes: Array<string>, configuration: {[key: string]: any}}|null} */
		settings: null,
		/** @type {{[key: string]: {[key: string]: any}}} */
		objects: {},
		/** @type {{[key: string]: {results: Array<any>}}} */
		collections: {},
		/** @type {{[key: string]: boolean}} */
		loading: {},
		/** @type {{[key: string]: string|null}} */
		errors: {},
		/** @type {{[key: string]: any}} */
		activeObjects: {},
		/** @type {{[key: string]: {logs: Array<any>, uses: any, used: any, files: any}}} */
		relatedData: {},
		/** @type {{[key: string]: string}} */
		searchTerms: {},
		/** @type {{[key: string]: ReturnType<typeof setTimeout>|null}} */
		searchDebounceTimers: {},
		/** @type {{[key: string]: {total: number, page: number, pages: number, limit: number, next: string|null, prev: string|null}}} */
		pagination: {},
		/** @type {{[key: string]: boolean|null}} */
		success: {},
		/** @type {{[key: string]: {schema: string, register: string}}} */
		objectTypeRegistry: {},
	}),

	getters: {
		/**
		 * Get object types from settings
		 * @param {ObjectState} state - Store state
		 * @return {Array<string>}
		 */
		objectTypes: (state) => state.settings?.objectTypes || [],

		/**
		 * Get available registers from settings
		 * @param {ObjectState} state - Store state
		 * @return {Array<{id: string, title: string, schemas: Array<{id: string, title: string}>}>}
		 */
		availableRegisters: (state) => state.settings?.availableRegisters || [],

		/**
		 * Get available schemas from settings
		 * @param {ObjectState} state - Store state
		 * @return {Array<{id: string, title: string, registerId: string, registerTitle: string}>}
		 */
		availableSchemas: (state) => {
			if (!state.settings?.availableRegisters) return []
			return state.settings.availableRegisters.flatMap(register =>
				register.schemas.map(schema => ({
					...schema,
					registerId: register.id,
					registerTitle: register.title,
				})),
			)
		},

		/**
		 * Get loading state for specific type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => boolean}
		 */
		isLoading: (state) => (type) => state.loading[type] || false,

		/**
		 * Get error for specific type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => string|null}
		 */
		getError: (state) => (type) => state.errors[type] || null,

		/**
		 * Get collection for specific type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => {results: Array<any>}}
		 */
		getCollection: (state) => (type) => {
			console.info('getCollection called for type:', type, {
				collection: state.collections[type],
				collectionType: typeof state.collections[type],
				hasResults: state.collections[type]?.results?.length > 0,
			})
			return state.collections[type] || { results: [] }
		},

		/**
		 * Get search term for specific type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => string}
		 */
		getSearchTerm: (state) => (type) => state.searchTerms[type] || '',

		/**
		 * Get single object
		 * @param {ObjectState} state - Store state
		 * @return {(type: string, id: string) => object | null}
		 */
		getObject: (state) => (type, id) => state.objects[type]?.[id] || null,

		/**
		 * Get active object for type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => object | null}
		 */
		getActiveObject: (state) => (type) => state.activeObjects[type] || null,

		/**
		 * Get related data for active object
		 * @param {ObjectState} state - Store state
		 * @return {(type: string, dataType: string) => object | null}
		 */
		getRelatedData: (state) => (type, dataType) => state.relatedData[type]?.[dataType] || null,

		/**
		 * Get pagination info for type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => {total: number, page: number, pages: number, limit: number}}
		 */
		getPagination: (state) => (type) => state.pagination[type] || { total: 0, page: 1, pages: 1, limit: 20 },

		/**
		 * Check if there are more pages to load for type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => boolean}
		 */
		hasMorePages: (state) => (type) => {
			const pagination = state.pagination[type]
			return pagination ? (pagination.next !== null || pagination.page < pagination.pages) : false
		},

		/**
		 * Check if there are previous pages available
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => boolean}
		 */
		hasPreviousPages: (state) => (type) => {
			const pagination = state.pagination[type]
			return pagination ? (pagination.prev !== null || pagination.page > 1) : false
		},

		/**
		 * Get audit trails for type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => Array<any>}
		 */
		getAuditTrails: (state) => (type) => state.relatedData[type]?.logs || [],

		/**
		 * Get state for specific type
		 * @param {ObjectState} state - Store state
		 * @return {(type: string) => {success: boolean|null, error: string|null}}
		 */
		getState: (state) => (type) => ({
			success: state.success[type] || null,
			error: state.errors[type] || null,
		}),

		/**
		 * Get object type configuration for a schema slug
		 * @param {ObjectState} state - Store state
		 * @return {(slug: string) => {schema: string, register: string}|null}
		 */
		getObjectType: (state) => (slug) => state.objectTypeRegistry[slug] || null,

		/**
		 * Check if an object type exists
		 * @param {ObjectState} state - Store state
		 * @return {(slug: string) => boolean}
		 */
		hasObjectType: (state) => (slug) => !!state.objectTypeRegistry[slug],
	},

	actions: {
		/**
		 * Set collection for type
		 * @param {string} type - Object type
		 * @param {Array} results - Collection results
		 * @param {boolean} append - Whether to append results to existing collection
		 */
		setCollection(type, results, append = false) {
			console.info('setCollection called with:', {
				type,
				resultsLength: results?.length,
				append,
				currentCollection: this.collections[type],
				currentCollectionType: typeof this.collections[type],
			})

			// Initialize if needed
			if (!this.collections[type] || !append) {
				console.info('Initializing collection for type:', type)
				this.collections[type] = { results: [] }
			}

			// Update the collection using reactive assignment
			const newResults = append
				? [...(this.collections[type].results || []), ...results]
				: results

			console.info('Setting new results:', {
				newResultsLength: newResults?.length,
				firstItem: newResults?.[0],
			})

			// Use reactive assignment for collections
			this.collections = {
				...this.collections,
				[type]: {
					results: newResults,
				},
			}

			console.info('Collection after update:', {
				type,
				collection: this.collections[type],
				length: this.collections[type]?.results?.length,
			})
		},

		/**
		 * Set loading state for type
		 * @param {string} type - Object type
		 * @param {boolean} isLoading - Loading state
		 */
		setLoading(type, isLoading) {
			this.loading = {
				...this.loading,
				[type]: isLoading,
			}
			console.info('Loading state set:', { type, isLoading })
		},

		/**
		 * Set error for type
		 * @param {string} type - Object type
		 * @param {string|null} error - Error message
		 */
		setError(type, error) {
			this.errors = {
				...this.errors,
				[type]: error,
			}
			if (error) {
				console.error('Error set for type:', type, error)
			}
		},

		/**
		 * Set active object for type and fetch related data
		 * @param {string} type - Object type
		 * @param {object} object - Object to set as active
		 * @return {Promise<void>}
		 */
		async setActiveObject(type, object) {
			console.info('setActiveObject called with:', { type, object })
			// Log the current state before update
			console.info('Current activeObjects state:', { ...this.activeObjects })
			// Update using reactive assignment
			this.activeObjects = {
				...this.activeObjects,
				[type]: object,
			}
			// Log the state after update
			console.info('Updated activeObjects state:', { ...this.activeObjects })

			// Initialize related data structure if not exists
			console.info('Initializing relatedData for type:', type)
			this.relatedData = {
				...this.relatedData,
				[type]: {
					logs: null,
					uses: null,
					used: null,
					files: null,
				},
			}

			// Fetch related data in parallel
			if (object?.id) {
				console.info('Fetching related data for:', { type, objectId: object.id })
				const fetchPromises = []
				const dataTypes = ['logs', 'uses', 'used', 'files']
				// const dataTypes = ['logs', 'uses']
				for (const dataType of dataTypes) {
					if (!this.relatedData[type][dataType]) {
						fetchPromises.push(this.fetchRelatedData(type, object.id, dataType))
					}
				}
				await Promise.all(fetchPromises)
				console.info('Finished fetching related data')
			} else {
				console.info('No object ID provided, skipping related data fetch')
			}
			console.info('setActiveObject completed')
		},

		/**
		 * Clear active object for type
		 * @param {string} type - Object type
		 */
		clearActiveObject(type) {
			this.activeObjects = {
				...this.activeObjects,
				[type]: null,
			}
			this.relatedData = {
				...this.relatedData,
				[type]: {
					logs: null,
					uses: null,
					used: null,
					files: null,
				},
			}
		},

		/**
		 * Register a new object type
		 * @param {string} slug - The schema slug to use as type identifier
		 * @param {string} schema - The schema ID
		 * @param {string} register - The register ID
		 * @return {Promise<void>}
		 */
		async registerObjectType(slug, schema, register) {
			if (this.objectTypeRegistry[slug]) {
				console.info(`Object type ${slug} already registered`)
				return
			}

			// Add the object type configuration
			this.objectTypeRegistry = {
				...this.objectTypeRegistry,
				[slug]: { schema, register },
			}

			// Initialize the collection for this type
			if (!this.collections[slug]) {
				this.collections = {
					...this.collections,
					[slug]: { results: [] },
				}
			}

			// Fetch the initial collection
			await this.fetchCollection(slug)
		},

		/**
		 * Unregister an object type
		 * @param {string} slug - The schema slug to unregister
		 */
		unregisterObjectType(slug) {
			if (!this.objectTypeRegistry[slug]) {
				return
			}

			// Remove the object type configuration
			const { [slug]: _, ...remainingTypes } = this.objectTypeRegistry
			this.objectTypeRegistry = remainingTypes

			// Clear associated data
			if (this.collections[slug]) {
				const { [slug]: _, ...remainingCollections } = this.collections
				this.collections = remainingCollections
			}

			if (this.activeObjects[slug]) {
				const { [slug]: _, ...remainingActiveObjects } = this.activeObjects
				this.activeObjects = remainingActiveObjects
			}

			if (this.relatedData[slug]) {
				const { [slug]: _, ...remainingRelatedData } = this.relatedData
				this.relatedData = remainingRelatedData
			}
		},

		/**
		 * Get schema configuration for object type
		 * @param {string} objectType - Type of object
		 * @return {{source: string, schema: string, register: string}}
		 * @throws {Error} If settings not found or invalid configuration
		 */
		getSchemaConfig(objectType) {
			// First check if this is a registered object type
			const objectTypeConfig = this.objectTypeRegistry[objectType]
			if (objectTypeConfig) {
				return {
					source: 'openregister',
					schema: objectTypeConfig.schema,
					register: objectTypeConfig.register,
				}
			}

			// Fall back to settings configuration
			if (!this.settings) {
				throw new Error('Settings not loaded')
			}

			const config = this.settings.configuration
			const source = config[`${objectType}_source`]
			const schema = config[`${objectType}_schema`]
			const register = config[`${objectType}_register`]

			if (!source || !schema || !register) {
				throw new Error(`Invalid configuration for object type: ${objectType}`)
			}

			return { source, schema, register }
		},

		/**
		 * Constructs the API endpoint URL for objects
		 * @param {string} type - Object type
		 * @param {string|null} id - Object ID (optional)
		 * @param {string|null} action - Additional action (e.g., 'logs', 'uses') (optional)
		 * @param {object} params - Query parameters
		 * @return {string} The constructed URL
		 * @private
		 */
		_constructApiUrl(type, id = null, action = null, params = {}) {
			const config = this.getSchemaConfig(type)
			const baseUrl = '/index.php/apps/openregister/api/objects'

			// Construct the path with register and schema
			let url = `${baseUrl}/${config.register}/${config.schema}`

			// Add ID and action if provided
			if (id) {
				url += `/${id}`
				if (action) {
					// Special case for audit trails
					if (action === 'logs') {
						url += '/audit-trails'
					} else {
						url += `/${action}`
					}
				}
			}

			// Add pagination and other query parameters
			const queryParams = new URLSearchParams({
				_limit: params._limit || 20,
				_page: params._page || 1,
				extend: params.extend || '@self.schema',
				...params,
			})

			// Remove source, schema, and register from query params as they're now in the URL
			queryParams.delete('_source')
			queryParams.delete('_schema')
			queryParams.delete('_register')

			return `${url}?${queryParams}`
		},

		/**
		 * Fetch collection of objects
		 * @param {string} type - Object type
		 * @param {object} params - Query parameters
		 * @param {boolean} append - Whether to append results to existing collection
		 * @return {Promise<void>}
		 */
		async fetchCollection(type, params = {}, append = false) {
			console.info('fetchCollection started:', { type, params, append })
			this.setLoading(type, true)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				// Add extend parameter if not explicitly set
				const queryParams = {
					...params,
					extend: params.extend || '@self.schema',
				}

				const response = await fetch(this._constructApiUrl(type, null, null, queryParams))
				if (!response.ok) throw new Error(`Failed to fetch ${type} collection`)

				const data = await response.json()
				console.info('API Response:', data)

				// Update pagination info - handle both pagination formats
				const paginationInfo = {
					total: data.total || 0,
					page: data.page || 1,
					pages: data.pages || (data.next ? Math.ceil((data.total || 0) / (data.limit || 20)) : 1),
					limit: data.limit || 20,
					next: data.next || null,
					prev: data.prev || null,
				}

				this.setPagination(type, paginationInfo)

				// Set the collection using the new method
				this.setCollection(type, data.results, append)

				// Update objects cache with extended data
				if (!this.objects[type]) {
					this.objects[type] = {}
				}
				data.results.forEach(item => {
					this.objects[type][item.id] = { ...item }
				})
			} catch (error) {
				console.error(`Error fetching ${type} collection:`, error)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(type, false)
			}
		},

		/**
		 * Fetch single object
		 * @param {string} type - Object type
		 * @param {string} id - Object ID
		 * @param {object} params - Query parameters
		 * @return {Promise<void>}
		 */
		async fetchObject(type, id, params = {}) {
			this.setLoading(`${type}_${id}`, true)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				// Add extend parameter if not explicitly set
				const queryParams = {
					...params,
					extend: params.extend || '@self.schema',
				}

				const response = await fetch(this._constructApiUrl(type, id, null, queryParams))
				if (!response.ok) throw new Error(`Failed to fetch ${type} object`)

				const data = await response.json()
				if (!this.objects[type]) this.objects[type] = {}
				this.objects[type][id] = data

				// If this object is currently active, update it and its related data
				if (this.activeObjects[type]?.id === id) {
					await this.setActiveObject(type, data)
				}
			} catch (error) {
				console.error(`Error fetching ${type} object:`, error)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(`${type}_${id}`, false)
			}
		},

		/**
		 * Fetch related data for object
		 * @param {string} type - Object type
		 * @param {string} id - Object ID
		 * @param {string} dataType - Type of related data (logs, uses, used, files)
		 * @param {object} params - Query parameters
		 * @return {Promise<void>}
		 */
		async fetchRelatedData(type, id, dataType, params = {}) {
			this.setLoading(`${type}_${id}_${dataType}`, true)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				// Add extend parameter for 'uses' and 'used' data types
				const queryParams = {
					...params,
					...(dataType === 'uses' || dataType === 'used' ? { extend: params.extend || '@self.schema' } : {}),
				}

				const response = await fetch(this._constructApiUrl(type, id, dataType, queryParams))
				if (!response.ok) throw new Error(`Failed to fetch ${dataType} for ${type}`)

				const data = await response.json()
				if (!this.relatedData[type]) {
					this.relatedData[type] = {}
				}

				// For audit trails, store the results array
				if (dataType === 'logs') {
					this.relatedData[type][dataType] = data.results || []
				} else {
					this.relatedData[type][dataType] = data
				}
			} catch (error) {
				console.error(`Error fetching ${dataType} for ${type}:`, error)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(`${type}_${id}_${dataType}`, false)
			}
		},

		/**
		 * Fetch and update settings
		 * @return {Promise<void>}
		 */
		async fetchSettings() {
			try {
				const response = await fetch('/index.php/apps/docudesk/api/settings')
				if (!response.ok) throw new Error('Failed to fetch settings')
				this.settings = await response.json()
			} catch (error) {
				console.error('Error fetching settings:', error)
				throw error
			}
		},

		/**
		 * Create new object
		 * @param {string} type - Object type
		 * @param {object} data - Object data
		 * @return {Promise<object>}
		 */
		async createObject(type, data) {
			this.setLoading(`${type}_create`, true)
			this.setError(`${type}_create`, null)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				const response = await fetch(
					this._constructApiUrl(type),
					{
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify(data),
					},
				)
				if (!response.ok) throw new Error(`Failed to create ${type} object`)

				const newObject = await response.json()
				if (!this.objects[type]) this.objects[type] = {}
				this.objects[type][newObject.id] = newObject

				// Refresh the collection to ensure it's up to date
				await this.fetchCollection(type)

				// Set the active object
				this.setActiveObject(type, newObject)

				// Set success state
				this.setState(type, { success: true, error: null })

				return newObject
			} catch (error) {
				console.error(`Error creating ${type} object:`, error)
				this.setError(`${type}_create`, error.message)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(`${type}_create`, false)
			}
		},

		async saveObject(objectItem, { register, schema }) {
			if (!objectItem || !register || !schema) {
				throw new Error('Object item, register and schema are required')
			}

			const isNewObject = !objectItem['@self'].id
			const endpoint = this._buildObjectPath({
				register,
				schema,
				objectId: isNewObject ? '' : objectItem['@self'].id,
			})

			objectItem['@self'].updated = new Date().toISOString()

			try {
				const response = await fetch(endpoint, {
					method: isNewObject ? 'POST' : 'PUT',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(objectItem),
				})

				const data = await response.json()
				this.setObjectItem(data)
				await this.refreshObjectList({ register, schema })
				return { response, data }
			} catch (error) {
				console.error('Error saving object:', error)
				throw error
			}
		},

		/**
		 * Update existing object
		 * @param {string} type - Object type
		 * @param {string} id - Object ID
		 * @param {object} data - Updated object data
		 * @return {Promise<object>}
		 */
		async updateObject(type, id, data) {
			this.setLoading(`${type}_${id}`, true)
			this.setError(`${type}_${id}`, null)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				const response = await fetch(
					this._constructApiUrl(type, id),
					{
						method: 'PUT',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify(data),
					},
				)
				if (!response.ok) throw new Error(`Failed to update ${type} object`)

				const updatedObject = await response.json()
				if (!this.objects[type]) this.objects[type] = {}
				this.objects[type][id] = updatedObject

				// Refresh the collection to ensure it's up to date
				await this.fetchCollection(type)

				// If this is the active object, update it
				if (this.activeObjects[type]?.id === id) {
					this.activeObjects[type] = updatedObject
				}

				// Set success state
				this.setState(type, { success: true, error: null })

				return updatedObject
			} catch (error) {
				console.error(`Error updating ${type} object:`, error)
				this.setError(`${type}_${id}`, error.message)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(`${type}_${id}`, false)
			}
		},

		/**
		 * Delete object
		 * @param {string} type - Object type
		 * @param {string} id - Object ID
		 * @return {Promise<void>}
		 */
		async deleteObject(type, id) {
			this.setLoading(`${type}_${id}`, true)
			this.setError(`${type}_${id}`, null)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				const response = await fetch(
					this._constructApiUrl(type, id),
					{ method: 'DELETE' },
				)
				if (!response.ok) throw new Error(`Failed to delete ${type} object`)

				// Remove from objects
				if (this.objects[type]) {
					delete this.objects[type][id]
				}

				// If this was the active object, clear it
				if (this.activeObjects[type]?.id === id) {
					this.clearActiveObject(type)
				}

				// Refresh the collection to ensure it's up to date
				await this.fetchCollection(type)

				// Set success state
				this.setState(type, { success: true, error: null })
			} catch (error) {
				console.error(`Error deleting ${type} object:`, error)
				this.setError(`${type}_${id}`, error.message)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(`${type}_${id}`, false)
			}
		},

		/**
		 * Set search term for type
		 * @param {string} type - Object type
		 * @param {string} term - Search term
		 */
		setSearchTerm(type, term) {
			// Initialize search term if it doesn't exist
			if (!this.searchTerms[type]) {
				this.searchTerms = {
					...this.searchTerms,
					[type]: '',
				}
			}

			// Update search term with reactive assignment
			this.searchTerms = {
				...this.searchTerms,
				[type]: term,
			}

			// Clear existing debounce timer
			if (this.searchDebounceTimers[type]) {
				clearTimeout(this.searchDebounceTimers[type])
			}

			// Set new debounce timer
			this.searchDebounceTimers = {
				...this.searchDebounceTimers,
				[type]: setTimeout(() => {
					this.fetchCollection(type, term ? { _search: term } : {})
				}, 500),
			}
		},

		/**
		 * Clear search term for type
		 * @param {string} type - Object type
		 */
		clearSearchTerm(type) {
			// Clear the search term
			this.searchTerms = {
				...this.searchTerms,
				[type]: '',
			}

			// Clear any existing debounce timer
			if (this.searchDebounceTimers[type]) {
				clearTimeout(this.searchDebounceTimers[type])
				this.searchDebounceTimers = {
					...this.searchDebounceTimers,
					[type]: null,
				}
			}

			// Fetch collection without search term
			this.fetchCollection(type)
		},

		/**
		 * Set pagination info for type
		 * @param {string} type - Object type
		 * @param {{total: number, page: number, pages: number, limit: number}} pagination - Pagination info
		 */
		setPagination(type, pagination) {
			this.pagination = {
				...this.pagination,
				[type]: pagination,
			}
		},

		/**
		 * Load next page of results
		 * @param {string} type - Object type
		 * @return {Promise<void>}
		 */
		async loadMore(type) {
			const pagination = this.getPagination(type)

			if (pagination.next) {
				// Extract query parameters from the next URL
				const url = new URL(pagination.next)
				const params = Object.fromEntries(url.searchParams)
				await this.fetchCollection(type, params, true)
			} else if (pagination.page < pagination.pages) {
				await this.fetchCollection(type, {
					_page: pagination.page + 1,
					_limit: pagination.limit,
				}, true)
			}
		},

		/**
		 * Load previous page of results
		 * @param {string} type - Object type
		 * @return {Promise<void>}
		 */
		async loadPrevious(type) {
			const pagination = this.getPagination(type)

			if (pagination.prev) {
				// Extract query parameters from the prev URL
				const url = new URL(pagination.prev)
				const params = Object.fromEntries(url.searchParams)
				await this.fetchCollection(type, params, false)
			} else if (pagination.page > 1) {
				await this.fetchCollection(type, {
					_page: pagination.page - 1,
					_limit: pagination.limit,
				}, false)
			}
		},

		/**
		 * Preload collections for all available schemas
		 * This function should be called once when the application initializes
		 * @return {Promise<void>}
		 */
		async preloadCollections() {
			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				// Get all available object types from settings
				const objectTypes = this.objectTypes

				console.info('Preloading collections for object types:', objectTypes)

				// Load collections for all object types in parallel
				await Promise.allSettled(
					objectTypes.map(async (type) => {
						try {
							await this.fetchCollection(type)
						} catch (error) {
							console.warn(`Failed to preload collection for type ${type}:`, error)
							// Don't throw here to allow other types to load
						}
					}),
				)

				console.info('Finished preloading collections')
			} catch (error) {
				console.error('Error during preload:', error)
				// Don't throw here to allow the application to continue
			}
		},

		/**
		 * Set state for specific type
		 * @param {string} type - Object type
		 * @param {{success: boolean|null, error: string|null}} state - State to set
		 */
		setState(type, { success, error }) {
			if (success !== undefined) {
				this.success = {
					...this.success,
					[type]: success,
				}
			}
			if (error !== undefined) {
				this.errors = {
					...this.errors,
					[type]: error,
				}
			}
		},

		/**
		 * Copy an existing object
		 * @param {string} type - Object type
		 * @param {string} id - Object ID to copy
		 * @return {Promise<object>} The newly created copy
		 */
		async copyObject(type, id) {
			this.setLoading(`${type}_${id}_copy`, true)
			this.setError(`${type}_${id}_copy`, null)
			this.setState(type, { success: null, error: null })

			try {
				// Ensure settings are loaded first
				if (!this.settings) {
					await this.fetchSettings()
				}

				// Get the original object
				const originalObject = this.objects[type]?.[id]
				if (!originalObject) {
					throw new Error(`Object ${id} of type ${type} not found`)
				}

				// Create a copy of the object without the id
				const { id: _, ...objectData } = originalObject

				// Add "Copy of" to the title or name
				if (objectData.title) {
					objectData.title = `Kopie van ${objectData.title}`
				} else if (objectData.name) {
					objectData.name = `Kopie van ${objectData.name}`
				}

				// Create the new object
				const newObject = await this.createObject(type, objectData)

				// Set success state
				this.setState(type, { success: true, error: null })

				return newObject
			} catch (error) {
				console.error(`Error copying ${type} object:`, error)
				this.setError(`${type}_${id}_copy`, error.message)
				this.setState(type, { success: false, error: error.message })
				throw error
			} finally {
				this.setLoading(`${type}_${id}_copy`, false)
			}
		},
	},
})
