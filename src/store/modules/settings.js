import { defineStore } from 'pinia'

export const useSettingsStore = defineStore(
	'settings',
	{
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
			async fetchSettings() {
				this.loading = true
				this.error = null

				try {
					const response = await fetch(
						'/index.php/apps/docudesk/api/settings',
						{
							method: 'GET',
							headers: {
								'Content-Type': 'application/json',
								requesttoken: OC.requestToken,
								'OCS-APIREQUEST': 'true',
							},
						},
					)

					if (!response.ok) {
						throw new Error(`Failed to fetch settings: ${response.statusText}`)
					}

					const data = await response.json()
					this.config = data.config || data
					if (data.openRegisters !== undefined && data.openRegisters !== null) {
						this.openRegisters = data.openRegisters
					} else {
						this.openRegisters = false
					}

					if (data.isAdmin !== undefined && data.isAdmin !== null) {
						this.isAdmin = data.isAdmin
					} else {
						this.isAdmin = false
					}

					this.initialized = true

					return this.config
				} catch (error) {
					this.error = error.message
					console.error('Error fetching DocuDesk settings:', error)
					return null
				} finally {
					this.loading = false
				}
			},

			async saveSettings(settingsData) {
				this.loading = true
				this.error = null

				try {
					const response = await fetch(
						'/index.php/apps/docudesk/api/settings',
						{
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								requesttoken: OC.requestToken,
								'OCS-APIREQUEST': 'true',
							},
							body: JSON.stringify(settingsData),
						},
					)

					if (!response.ok) {
						throw new Error(`Failed to save settings: ${response.statusText}`)
					}

					const data = await response.json()
					this.config = data.config || data

					return this.config
				} catch (error) {
					this.error = error.message
					console.error('Error saving DocuDesk settings:', error)
					return null
				} finally {
					this.loading = false
				}
			},
		},
	},
)
