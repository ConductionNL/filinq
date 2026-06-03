<template>
	<div>
		<!-- Version Information -->
		<CnVersionInfoCard
			:app-name="'DocuDesk'"
			:app-version="appVersion"
			:is-up-to-date="true"
			:show-update-button="true"
			:title="t('docudesk', 'Version Information')"
			:description="t('docudesk', 'Information about the current DocuDesk installation')">
			<template #footer>
				<div class="cn-support-info">
					<h4>{{ t('docudesk', 'Support') }}</h4>
					<p>
						{{ t('docudesk', 'For support, contact us at') }}
						<a href="mailto:support@conduction.nl">support@conduction.nl</a>
					</p>
					<p>
						{{ t('docudesk', 'For a Service Level Agreement (SLA), contact') }}
						<a href="mailto:sales@conduction.nl">sales@conduction.nl</a>
					</p>
				</div>
			</template>
		</CnVersionInfoCard>

		<NcSettingsSection
			name="DocuDesk"
			:description="t('docudesk', 'GDPR publication consent management and document metadata enrichment for Nextcloud')"
			doc-url="https://docudesk.app" />

		<NcSettingsSection
			:name="t('docudesk', 'Consent Settings')"
			:description="t('docudesk', 'Configure GDPR publication consent tracking settings')">
			<div class="setting-item">
				<div class="input-field">
					<label for="objection-period">{{ t('docudesk', 'Objection Period (days)') }}</label>
					<input
						id="objection-period"
						v-model.number="settings.publication_objection_period_days"
						type="number"
						min="1"
						max="365"
						placeholder="28">
				</div>
				<span class="setting-description">
					{{ t('docudesk', 'Minimum number of days entities have to submit an objection before publication (Wet Open Overheid: minimum 4 weeks)') }}
				</span>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('docudesk', 'Anonymisation')"
			:description="t('docudesk', 'Configure how anonymised documents are written back to Nextcloud')">
			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Always export anonymised documents as PDF') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings['docudesk.anonymisation.default_output_format'] === 'pdf'"
					type="switch"
					@update:checked="settings['docudesk.anonymisation.default_output_format'] = $event ? 'pdf' : 'preserve'" />
				<div class="setting-description">
					{{ t('docudesk', 'When enabled, anonymised files are converted to PDF/A-3b before being written back to Nextcloud Files. PDF flattens the text into a glyph stream, which makes the redaction much harder to revert by editing the document, and strips most metadata channels that would otherwise still name the original entities. When disabled, anonymised files keep their native format (DOCX, ODT, …). Callers can still override per-request by sending outputFormat: "pdf" or "preserve".') }}
				</div>
				<div v-if="settings['docudesk.anonymisation.default_output_format'] === 'pdf'" class="setting-description">
					<em>{{ t('docudesk', 'Conversion requires either a supported Office app integration (Collabora, OnlyOffice, or Euro Office) for the best fidelity, or the bundled PhpWord + mPDF fallback for DOC/DOCX/ODT/RTF/HTML/TXT. Spreadsheet and presentation formats are not supported in the fallback tier and will return an error unless an Office app is configured.') }}</em>
				</div>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('docudesk', 'Metadata Enrichment')"
			:description="t('docudesk', 'Configure automatic metadata enrichment for documents')">
			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Language Detection') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.enable_language_detection"
					type="switch"
					@update:checked="settings.enable_language_detection = $event" />
				<div class="setting-description">
					{{ t('docudesk', 'Automatically detect the language of documents') }}
				</div>
			</div>

			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Keyword Extraction') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.enable_keyword_extraction"
					type="switch"
					@update:checked="settings.enable_keyword_extraction = $event" />
				<div class="setting-description">
					{{ t('docudesk', 'Automatically extract keywords from document content') }}
				</div>
			</div>

			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Topic Classification') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.enable_topic_classification"
					type="switch"
					@update:checked="settings.enable_topic_classification = $event" />
				<div class="setting-description">
					{{ t('docudesk', 'Automatically classify documents by topic') }}
				</div>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('docudesk', 'OCR Document Scanning')"
			:description="t('docudesk', 'Configure Tesseract OCR for extracting text from scanned documents and images')">
			<!-- Tesseract availability status -->
			<div v-if="ocrStatus.tesseractAvailable" class="setting-item">
				<NcNoteCard type="success">
					{{ t('docudesk', 'Tesseract OCR is installed: {version}', { version: ocrStatus.tesseractVersion || 'unknown' }) }}
				</NcNoteCard>
			</div>
			<div v-else class="setting-item">
				<NcNoteCard type="warning">
					{{ t('docudesk', 'Tesseract OCR is not installed. Install tesseract-ocr on the server to enable OCR for scanned documents.') }}
				</NcNoteCard>
			</div>

			<!-- OCR enable/disable toggle -->
			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Enable OCR') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.ocr_enabled"
					type="switch"
					@update:checked="settings.ocr_enabled = $event" />
				<div class="setting-description">
					{{ t('docudesk', 'Automatically extract text from scanned documents and images using Tesseract OCR') }}
				</div>
			</div>

			<!-- Language selection -->
			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'OCR Languages') }}
				</div>
				<div class="ocr-languages">
					<NcCheckboxRadioSwitch
						:checked="ocrLanguages.nld"
						@update:checked="ocrLanguages.nld = $event">
						{{ t('docudesk', 'Dutch (nld)') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						:checked="ocrLanguages.eng"
						@update:checked="ocrLanguages.eng = $event">
						{{ t('docudesk', 'English (eng)') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						:checked="ocrLanguages.deu"
						@update:checked="ocrLanguages.deu = $event">
						{{ t('docudesk', 'German (deu)') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						:checked="ocrLanguages.fra"
						@update:checked="ocrLanguages.fra = $event">
						{{ t('docudesk', 'French (fra)') }}
					</NcCheckboxRadioSwitch>
				</div>
				<div class="setting-description">
					{{ t('docudesk', 'Select languages for OCR text recognition. At least one language must be selected.') }}
				</div>
			</div>

			<!-- DPI configuration -->
			<div class="setting-item">
				<div class="input-field">
					<label for="ocr-dpi">{{ t('docudesk', 'OCR DPI') }}</label>
					<input
						id="ocr-dpi"
						v-model.number="settings.ocr_dpi"
						type="number"
						min="72"
						max="600"
						placeholder="300">
				</div>
				<div class="setting-description">
					{{ t('docudesk', 'DPI for PDF-to-image conversion during OCR. Higher values improve accuracy but increase processing time. Default: 300.') }}
				</div>
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('docudesk', 'Data Storage')"
			:description="t('docudesk', 'Configure Open Register integration for consent data storage')">
			<div v-if="!loading">
				<div v-if="!openRegisterInstalled">
					<NcNoteCard type="info">
						{{ t('docudesk', 'Open Registers is not installed. It is required for DocuDesk to function properly.') }}
					</NcNoteCard>
					<NcButton type="primary" @click="openLink('/index.php/settings/apps/organization/openregister', '_blank')">
						<template #icon>
							<Restart :size="20" />
						</template>
						{{ t('docudesk', 'Install Open Registers') }}
					</NcButton>
				</div>

				<div v-for="type in objectTypes" :key="type">
					<h3>{{ type === 'publicationConsent' ? t('docudesk', 'Publication Consent') : type }}</h3>
					<div class="selectionContainer">
						<NcSelect
							v-bind="availableRegistersOptions"
							v-model="sections[type].selectedRegister"
							:input-label="t('docudesk', 'Register')"
							:loading="sections[type].loading"
							:disabled="loading || sections[type].loading"
							@input="onRegisterChange(type)" />

						<NcSelect
							v-if="sections[type].selectedRegister?.value"
							v-bind="globalSchemasOptions[sections[type].selectedRegister.value]"
							v-model="sections[type].selectedSchema"
							:input-label="t('docudesk', 'Schema')"
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
							{{ t('docudesk', 'Save') }}
						</NcButton>
					</div>
				</div>
			</div>
			<NcLoadingIcon
				v-if="loading"
				class="loadingIcon"
				:size="64"
				appearance="dark"
				:name="t('docudesk', 'Loading settings')" />
		</NcSettingsSection>

		<div class="button-container">
			<NcButton type="primary" :disabled="saving" @click="saveAll">
				<template #icon>
					<NcLoadingIcon v-if="saving" :size="20" />
					<Plus v-else :size="20" />
				</template>
				{{ t('docudesk', 'Save All Settings') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { NcSettingsSection, NcNoteCard, NcSelect, NcButton, NcLoadingIcon, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { CnVersionInfoCard } from '@conduction/nextcloud-vue'
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
		CnVersionInfoCard,
		Plus,
		Restart,
	},
	data() {
		return {
			appVersion: document.getElementById('admin-settings')?.dataset?.version || 'Unknown',
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
				ocr_enabled: true,
				ocr_dpi: 300,
				'docudesk.anonymisation.default_output_format': 'pdf',
			},
			ocrLanguages: {
				nld: true,
				eng: true,
				deu: false,
				fra: false,
			},
			ocrStatus: {
				tesseractAvailable: false,
				tesseractVersion: null,
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
					this.settings.ocr_enabled = data.ocr_enabled ?? true
					this.settings.ocr_dpi = data.ocr_dpi ?? 300
					this.settings['docudesk.anonymisation.default_output_format'] = data['docudesk.anonymisation.default_output_format'] ?? 'pdf'

					// Parse OCR languages
					const ocrLangStr = data.ocr_languages || 'nld+eng'
					const activeLangs = ocrLangStr.split('+')
					this.ocrLanguages = {
						nld: activeLangs.includes('nld'),
						eng: activeLangs.includes('eng'),
						deu: activeLangs.includes('deu'),
						fra: activeLangs.includes('fra'),
					}

					// OCR status
					this.ocrStatus = data.ocrStatus || { tesseractAvailable: false, tesseractVersion: null }

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
					showSuccess(t('docudesk', 'Configuration saved'))
				})
				.catch((err) => {
					console.error(err)
					showError(t('docudesk', 'Failed to save configuration'))
				})
				.finally(() => {
					this.saving = false
					this.sections[type].loading = false
				})
		},
		saveAll() {
			this.saving = true

			// Build OCR language string from checkboxes
			const ocrLangs = Object.entries(this.ocrLanguages)
				.filter(([, enabled]) => enabled)
				.map(([lang]) => lang)
				.join('+') || 'eng'

			const payload = {
				publication_objection_period_days: String(this.settings.publication_objection_period_days),
				enable_language_detection: this.settings.enable_language_detection ? '1' : '0',
				enable_keyword_extraction: this.settings.enable_keyword_extraction ? '1' : '0',
				enable_topic_classification: this.settings.enable_topic_classification ? '1' : '0',
				ocr_enabled: this.settings.ocr_enabled ? '1' : '0',
				ocr_languages: ocrLangs,
				ocr_dpi: String(this.settings.ocr_dpi),
				'docudesk.anonymisation.default_output_format': this.settings['docudesk.anonymisation.default_output_format'] === 'pdf' ? 'pdf' : 'preserve',
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
					showSuccess(t('docudesk', 'All settings saved successfully'))
				})
				.catch((err) => {
					console.error(err)
					showError(t('docudesk', 'Failed to save settings'))
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

<style scoped>
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

.ocr-languages {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin: 4px 0;
}

.button-container {
	display: flex;
	justify-content: flex-end;
	margin-top: 16px;
	padding: 16px;
}

</style>
