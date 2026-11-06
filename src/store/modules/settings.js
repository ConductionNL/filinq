import { defineStore } from 'pinia'

/**
 * The settings, OpenRegister availability and admin status in a settings
 * response; a missing or null flag is false.
 *
 * @param {object} data The parsed response body.
 * @return {{config: object, openRegisters: boolean, isAdmin: boolean}}
 *
 * @spec openspec/specs/admin-settings/spec.md#requirement-settings-rest-api-req-set-06
 */
function settingsFromResponse(data) {
	return {
		config: data.config || data,
		openRegisters: data.openRegisters ?? false,
		isAdmin: data.isAdmin ?? false,
	}
}

export const useSettingsStore = defineStore('settings', {
	state: () => ({
		config: null,
		openRegisters: false,
		isAdmin: false,
		loading: false,
		error: null,
		initialized: false,
	}),
	getters: {
		isLoading: (state) => state.loading,
		getError: (state) => state.error,
		isInitialized: (state) => state.initialized,
		getConfig: (state) => state.config,
		hasOpenRegisters: (state) => state.openRegisters,
		getIsAdmin: (state) => state.isAdmin,
	},
	actions: {
		/**
		 * Fetch Filinq settings, OpenRegister availability and admin status.
		 *
		 * @spec openspec/specs/admin-settings/spec.md#requirement-settings-rest-api-req-set-06
		 */
		async fetchSettings() {
			this.loading = true
			this.error = null

			try {
				const response = await fetch('/index.php/apps/filinq/api/settings', {
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						requesttoken: OC.requestToken,
						'OCS-APIREQUEST': 'true',
					},
				})

				if (!response.ok) {
					throw new Error(
						`Failed to fetch settings: ${response.statusText}`,
					)
				}

				const settings = settingsFromResponse(await response.json())
				this.config = settings.config
				this.openRegisters = settings.openRegisters
				this.isAdmin = settings.isAdmin
				this.initialized = true

				return this.config
			} catch (error) {
				this.error = error.message
				console.error('Error fetching Filinq settings:', error)
				return null
			} finally {
				this.loading = false
			}
		},

		/**
		 * Persist Filinq settings via the settings REST API.
		 *
		 * @param settingsData
		 * @spec openspec/specs/admin-settings/spec.md#requirement-settings-rest-api-req-set-06
		 */
		async saveSettings(settingsData) {
			this.loading = true
			this.error = null

			try {
				const response = await fetch('/index.php/apps/filinq/api/settings', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						requesttoken: OC.requestToken,
						'OCS-APIREQUEST': 'true',
					},
					body: JSON.stringify(settingsData),
				})

				if (!response.ok) {
					throw new Error(
						`Failed to save settings: ${response.statusText}`,
					)
				}

				const data = await response.json()
				this.config = data.config || data

				return this.config
			} catch (error) {
				this.error = error.message
				console.error('Error saving Filinq settings:', error)
				return null
			} finally {
				this.loading = false
			}
		},
	},
})
