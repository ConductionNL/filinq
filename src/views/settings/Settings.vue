<template>
	<div>
		<NcSettingsSection
			name="DocuDesk"
			description="GDPR publication consent management and document metadata enrichment for Nextcloud"
			doc-url="https://docudesk.app" />

		<NcSettingsSection
			name="Consent Settings"
			description="Configure GDPR publication consent tracking settings">
			<div class="setting-item">
				<div class="input-field">
					<label for="objection-period">Objection Period (days)</label>
					<input
						id="objection-period"
						v-model.number="settings.publication_objection_period_days"
						type="number"
						min="1"
						max="365"
						placeholder="28">
				</div>
				<span class="setting-description">
					Minimum number of days entities have to submit an objection before publication (Wet Open Overheid: minimum 4 weeks)
				</span>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			name="Metadata Enrichment"
			description="Configure automatic metadata enrichment for documents">
			<div class="setting-item">
				<div class="setting-label">
					Language Detection
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.enable_language_detection"
					type="switch"
					@update:checked="settings.enable_language_detection = $event" />
				<div class="setting-description">
					Automatically detect the language of documents
				</div>
			</div>

			<div class="setting-item">
				<div class="setting-label">
					Keyword Extraction
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.enable_keyword_extraction"
					type="switch"
					@update:checked="settings.enable_keyword_extraction = $event" />
				<div class="setting-description">
					Automatically extract keywords from document content
				</div>
			</div>

			<div class="setting-item">
				<div class="setting-label">
					Topic Classification
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.enable_topic_classification"
					type="switch"
					@update:checked="settings.enable_topic_classification = $event" />
				<div class="setting-description">
					Automatically classify documents by topic
				</div>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			name="Data Storage"
			description="Configure Open Register integration for consent data storage">
			<div v-if="!loading">
				<div v-if="!openRegisterInstalled">
					<NcNoteCard type="info">
						Open Registers is not installed. It is required for DocuDesk to function properly.
					</NcNoteCard>
					<NcButton type="primary" @click="openLink('/index.php/settings/apps/organization/openregister', '_blank')">
						<template #icon>
							<Restart :size="20" />
						</template>
						Install Open Registers
					</NcButton>
				</div>

				<div v-for="type in objectTypes" :key="type">
					<h3>{{ type === 'publicationConsent' ? 'Publication Consent' : type }}</h3>
					<div class="selectionContainer">
						<NcSelect
							v-bind="availableRegistersOptions"
							v-model="sections[type].selectedRegister"
							input-label="Register"
							:loading="sections[type].loading"
							:disabled="loading || sections[type].loading"
							@input="onRegisterChange(type)" />

						<NcSelect
							v-if="sections[type].selectedRegister?.value"
							v-bind="globalSchemasOptions[sections[type].selectedRegister.value]"
							v-model="sections[type].selectedSchema"
							input-label="Schema"
							:loading="sections[type].loading"
							:disabled="loading || sections[type].loading" />

						<NcButton
							type="primary"
							:disabled="loading || saving ||
								sections[type].loading ||
								!sections[type].selectedRegister?.value ||
								!sections[type].selectedSchema?.value"
							@click="saveConfig(type)">
							<template #icon>
								<NcLoadingIcon v-if="loading || sections[type].loading" :size="20" />
								<Plus v-else :size="20" />
							</template>
							Save
						</NcButton>
					</div>
				</div>
			</div>
			<NcLoadingIcon
				v-if="loading"
				class="loadingIcon"
				:size="64"
				appearance="dark"
				name="Loading settings" />
		</NcSettingsSection>

		<div class="button-container">
			<NcButton type="primary" :disabled="saving" @click="saveAll">
				<template #icon>
					<NcLoadingIcon v-if="saving" :size="20" />
					<Plus v-else :size="20" />
				</template>
				Save All Settings
			</NcButton>
		</div>
	</div>
</template>

<script>
import { NcSettingsSection, NcNoteCard, NcSelect, NcButton, NcLoadingIcon, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Restart from 'vue-material-design-icons/Restart.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'Settings',
	components: {
		NcSettingsSection,
		NcNoteCard,
		NcSelect,
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		Plus,
		Restart,
	},
	data() {
		return {
			loading: false,
			saving: false,
			openRegisterInstalled: false,
			settingsData: {},
			availableRegisters: [],
			availableRegistersOptions: { options: [] },
			globalSchemasOptions: {},
			objectTypes: ['publicationConsent'],
			sections: {},
			settings: {
				publication_objection_period_days: 28,
				enable_language_detection: true,
				enable_keyword_extraction: true,
				enable_topic_classification: true,
			},
		}
	},
	mounted() {
		this.fetchAll()
	},
	methods: {
		onRegisterChange(type) {
			this.sections = {
				...this.sections,
				[type]: {
					...this.sections[type],
					selectedSchema: '',
				},
			}
		},
		fetchAll() {
			this.loading = true
			fetch('/index.php/apps/docudesk/api/settings', { method: 'GET' })
				.then((response) => response.json())
				.then((data) => {
					this.openRegisterInstalled = data.openRegisters
					this.settingsData = data
					this.availableRegisters = data.availableRegisters

					// Update local settings
					this.settings.publication_objection_period_days = data.publication_objection_period_days ?? 28
					this.settings.enable_language_detection = data.enable_language_detection ?? true
					this.settings.enable_keyword_extraction = data.enable_keyword_extraction ?? true
					this.settings.enable_topic_classification = data.enable_topic_classification ?? true

					// Build available registers options
					this.availableRegistersOptions = {
						options: (data.availableRegisters || []).map((register) => ({
							value: register.id.toString(),
							label: register.title,
						})),
					}

					// Build global schemas options per register
					this.globalSchemasOptions = {}
					;(data.availableRegisters || []).forEach((register) => {
						if (register.schemas) {
							this.globalSchemasOptions[register.id.toString()] = {
								options: register.schemas
									.filter((schema) => typeof schema === 'object')
									.map((schema) => ({
										value: schema.id.toString(),
										label: schema.title,
									})),
							}
						}
					})

					// Initialize sections
					const newSections = {}
					this.objectTypes.forEach((type) => {
						newSections[type] = {
							selectedRegister: this.availableRegistersOptions.options.find(
								(option) => option.value === data.configuration?.[`${type}_register`],
							) || '',
							selectedSchema: '',
							loading: false,
						}

						if (data.configuration?.[`${type}_register`] && data.configuration?.[`${type}_schema`]) {
							const regId = data.configuration[`${type}_register`]
							const opts = this.globalSchemasOptions[regId]
							if (opts) {
								const schemaOption = opts.options.find(
									(opt) => opt.value === data.configuration[`${type}_schema`],
								)
								newSections[type].selectedSchema = schemaOption || ''
							}
						}
					})

					this.sections = newSections
					this.loading = false
				})
				.catch((err) => {
					console.error(err)
					this.loading = false
				})
		},
		saveConfig(type) {
			this.sections[type].loading = true
			this.saving = true

			const payload = {
				[`${type}_register`]: this.sections[type].selectedRegister?.value || '',
				[`${type}_schema`]: this.sections[type].selectedSchema?.value || '',
				[`${type}_source`]: 'openregister',
			}

			fetch('/index.php/apps/docudesk/api/settings', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
			})
				.then((response) => response.json())
				.then(() => {
					showSuccess('Configuration saved')
				})
				.catch((err) => {
					console.error(err)
					showError('Failed to save configuration')
				})
				.finally(() => {
					this.saving = false
					this.sections[type].loading = false
				})
		},
		saveAll() {
			this.saving = true

			const payload = {
				publication_objection_period_days: String(this.settings.publication_objection_period_days),
				enable_language_detection: this.settings.enable_language_detection ? '1' : '0',
				enable_keyword_extraction: this.settings.enable_keyword_extraction ? '1' : '0',
				enable_topic_classification: this.settings.enable_topic_classification ? '1' : '0',
			}

			// Add register/schema configs
			this.objectTypes.forEach((type) => {
				payload[`${type}_register`] = this.sections[type]?.selectedRegister?.value || ''
				payload[`${type}_schema`] = this.sections[type]?.selectedSchema?.value || ''
				payload[`${type}_source`] = 'openregister'
			})

			fetch('/index.php/apps/docudesk/api/settings', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
			})
				.then((response) => response.json())
				.then(() => {
					showSuccess('All settings saved successfully')
				})
				.catch((err) => {
					console.error(err)
					showError('Failed to save settings')
				})
				.finally(() => {
					this.saving = false
				})
		},
		openLink(url, target = '') {
			window.open(url, target)
		},
	},
}
</script>

<style>
.selectionContainer {
	display: grid;
	grid-gap: 5px;
	grid-template-columns: 1fr;
}
.selectionContainer > * {
	margin-block-end: 10px;
}

.setting-item {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 8px 0;
	border-bottom: 1px solid var(--color-border-dark-translucent);
}

.setting-label {
	font-weight: bold;
	color: var(--color-main-text);
}

.setting-description {
	color: var(--color-text-lighter);
	font-size: 0.9em;
}

.input-field {
	margin-bottom: 8px;
}

.input-field label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
	color: var(--color-main-text);
}

.input-field input {
	width: 100%;
	max-width: 200px;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
}

.button-container {
	display: flex;
	justify-content: flex-end;
	margin-top: 16px;
	padding: 16px;
}
</style>
