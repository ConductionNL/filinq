<template>
	<CnAdminSettingsShell
		app-id="docudesk"
		app-name="DocuDesk"
		doc-url="https://docudesk.app"
		:show-reimport="false">
		<!-- Anonymiser backend warning (shown when regex-only and admin has not dismissed) -->
		<AnonymiserBackendWarning
			v-if="isAdmin"
			:show-warning="anonymiserBackend.showWarning"
			:app-api-installed="anonymiserBackend.appApiInstalled"
			@dismissed="onAnonymiserWarningDismissed" />

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
			<div v-if="isAdmin && anonymiserBackend.warningDismissed" class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Show anonymiser backend warning') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="false"
					type="switch"
					@update:checked="resetAnonymiserWarning" />
				<div class="setting-description">
					{{ t('docudesk', 'Re-enable the anonymiser backend warning banner. It was previously dismissed and will appear again on the next page load.') }}
				</div>
			</div>

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

		<NcSettingsSection
			:name="t('docudesk', 'Digital Signing')"
			:description="t('docudesk', 'Configure digital document signing capabilities (eIDAS SES, AdES, QES)')">
			<div class="setting-item">
				<div class="setting-label">
					{{ t('docudesk', 'Enable Digital Signing') }}
				</div>
				<NcCheckboxRadioSwitch
					:checked="settings.signing_enabled"
					type="switch"
					@update:checked="settings.signing_enabled = $event" />
				<div class="setting-description">
					{{ t('docudesk', 'Allow users to create and manage digital signing requests') }}
				</div>
			</div>

			<div class="setting-item">
				<div class="input-field">
					<label for="signing-provider">{{ t('docudesk', 'Signing Provider') }}</label>
					<select
						id="signing-provider"
						v-model="settings.signing_provider"
						style="padding: 8px; border: 1px solid var(--color-border); border-radius: var(--border-radius); background-color: var(--color-main-background); color: var(--color-main-text);">
						<option value="native">
							{{ t('docudesk', 'Native (built-in SES)') }}
						</option>
						<option value="validsign">
							{{ t('docudesk', 'ValidSign') }}
						</option>
					</select>
				</div>
				<div class="setting-description">
					{{ t('docudesk', 'The signing provider to use for new signing requests') }}
				</div>
			</div>

			<div class="setting-item">
				<div class="input-field">
					<label for="signing-default-level">{{ t('docudesk', 'Default Signature Level') }}</label>
					<select
						id="signing-default-level"
						v-model="settings.signing_default_level"
						style="padding: 8px; border: 1px solid var(--color-border); border-radius: var(--border-radius); background-color: var(--color-main-background); color: var(--color-main-text);">
						<option value="SES">
							{{ t('docudesk', 'SES — Simple Electronic Signature') }}
						</option>
						<option value="AdES">
							{{ t('docudesk', 'AdES — Advanced Electronic Signature') }}
						</option>
						<option value="QES">
							{{ t('docudesk', 'QES — Qualified Electronic Signature (PKIoverheid)') }}
						</option>
					</select>
				</div>
				<div class="setting-description">
					{{ t('docudesk', 'The eIDAS signature level applied to new signing requests unless overridden') }}
				</div>
			</div>

			<div class="setting-item">
				<div class="input-field">
					<label for="signing-expiry-days">{{ t('docudesk', 'Default Request Expiry (days)') }}</label>
					<input
						id="signing-expiry-days"
						v-model.number="settings.signing_request_expiry_days"
						type="number"
						min="1"
						max="365"
						placeholder="30">
				</div>
				<div class="setting-description">
					{{ t('docudesk', 'Number of days before an unsigned signing request expires (1–365). Archiefwet requires minimum 10-year audit trail retention.') }}
				</div>
			</div>
		</NcSettingsSection>

		<!-- AVG Art. 30 processing-activity register (provided by OpenRegister) -->
		<NcSettingsSection
			v-if="isAdmin"
			:name="t('docudesk', 'Processing Activity Register (AVG Art. 30)')"
			:description="t('docudesk', 'DocuDesk\'s document-processing activities are recorded in OpenRegister\'s platform processing-activity register. The Art. 30 register, per-access logging, exports, and access control are provided by OpenRegister; DocuDesk contributes the four activity categories.')"
			doc-url="https://conduction.gitbook.io/docudesk-nextcloud/">
			<div v-if="!openRegisterInstalled" class="setting-item">
				<NcNoteCard type="warning">
					{{ t('docudesk', 'OpenRegister is not installed. The processing-activity register and Art. 30 export are provided by OpenRegister and are unavailable until it is installed.') }}
				</NcNoteCard>
			</div>
			<template v-else>
				<!-- Controller-identity record state + configure prompt (OR-PA-1) -->
				<div class="setting-item">
					<NcNoteCard type="info">
						{{ t('docudesk', 'The verantwoordelijke (controller) identity for the Art. 30 register is maintained centrally in OpenRegister. If it has not been configured, the export still succeeds with identity fields rendered as "not configured". Configure it once in OpenRegister to have it appear on every export.') }}
					</NcNoteCard>
					<NcButton type="secondary" @click="openLink('/index.php/settings/admin/openregister', '_blank')">
						<template #icon>
							<OpenInNew :size="20" />
						</template>
						{{ t('docudesk', 'Configure controller identity in OpenRegister') }}
					</NcButton>
				</div>

				<!-- Activity catalogue (the four DocuDesk categories) -->
				<div class="setting-item">
					<div class="setting-label">
						{{ t('docudesk', 'DocuDesk processing activities') }}
					</div>
					<div class="setting-description">
						{{ t('docudesk', 'DocuDesk declares four processing activities. They are seeded into OpenRegister as drafts when the DocuDesk register configuration is imported; activate them in OpenRegister to make DocuDesk processing attributable in the Art. 30 register.') }}
					</div>
					<ul class="processing-activities">
						<li v-for="activity in processingActivities" :key="activity.code">
							<strong>{{ activity.name }}</strong>
							<span class="processing-meta">{{ activity.purpose }}</span>
							<span class="processing-meta">{{ t('docudesk', 'Retention: {ref}', { ref: activity.retention }) }}</span>
						</li>
					</ul>
				</div>

				<!-- Art. 30 export entry point (delegates to OpenRegister, OR-PA-7) -->
				<div class="setting-item">
					<div class="setting-label">
						{{ t('docudesk', 'Art. 30 export') }}
					</div>
					<div class="setting-description">
						{{ t('docudesk', 'The Art. 30 export and per-subject (betrokkene) inzage extract are produced by OpenRegister, scoped to DocuDesk\'s registers, and never contain literal personal data beyond what the data subject is entitled to. Access is restricted to administrators and the privacy officer (FG) group; non-admins are denied.') }}
					</div>
					<div class="processing-export-actions">
						<NcButton type="primary" @click="openProcessingExport">
							<template #icon>
								<FileExportOutline :size="20" />
							</template>
							{{ t('docudesk', 'Open processing-activity log in OpenRegister') }}
						</NcButton>
						<NcButton type="secondary" @click="openLink('/index.php/apps/openregister/api/avg/verwerkingen/betrokkene', '_blank')">
							<template #icon>
								<AccountSearchOutline :size="20" />
							</template>
							{{ t('docudesk', 'Per-subject (betrokkene) extract') }}
						</NcButton>
					</div>
					<div class="setting-description">
						<em>{{ t('docudesk', 'Note: the per-access read log and per-subject extract are available now. The aggregate Art. 30 register export to JSON/CSV/PDF is a forthcoming OpenRegister capability; until it lands, use the read-log query surface above.') }}</em>
					</div>
				</div>
			</template>
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
	</CnAdminSettingsShell>
</template>

<script>
import { NcSettingsSection, NcNoteCard, NcSelect, NcButton, NcLoadingIcon, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { CnAdminSettingsShell } from '@conduction/nextcloud-vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Restart from 'vue-material-design-icons/Restart.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import FileExportOutline from 'vue-material-design-icons/FileExportOutline.vue'
import AccountSearchOutline from 'vue-material-design-icons/AccountSearchOutline.vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import AnonymiserBackendWarning from '../../components/AnonymiserBackendWarning.vue'

export default {
	name: 'Settings',
	components: {
		NcSettingsSection,
		NcNoteCard,
		NcSelect,
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		CnAdminSettingsShell,
		AnonymiserBackendWarning,
		Plus,
		Restart,
		OpenInNew,
		FileExportOutline,
		AccountSearchOutline,
	},
	data() {
		return {
			loading: false,
			saving: false,
			isAdmin: false,
			openRegisterInstalled: false,
			anonymiserBackend: {
				method: 'regex',
				appApiInstalled: false,
				warningDismissed: false,
				showWarning: false,
			},
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
				signing_enabled: false,
				signing_provider: 'native',
				signing_default_level: 'SES',
				signing_request_expiry_days: 30,
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
	computed: {
		/**
		 * The four DocuDesk processing activities surfaced in the AVG Art. 30
		 * compliance section. Mirrors the x-openregister-processing catalogue
		 * annotations in lib/Settings/docudesk_register.json (authoring source
		 * of truth); the register itself is owned and rendered by OpenRegister.
		 *
		 * @return {Array<{code: string, name: string, purpose: string, retention: string}>} Catalogue rows.
		 * @spec openspec/specs/processing-activity-export/spec.md
		 */
		processingActivities() {
			return [
				{
					code: 'docudesk-anonymisation',
					name: t('docudesk', 'Anonymisation of documents'),
					purpose: t('docudesk', 'Pseudonymise / redact personal data detected in documents for Wet Open Overheid publication.'),
					retention: t('docudesk', 'P7Y (selectielijst category to be confirmed)'),
				},
				{
					code: 'docudesk-ocr',
					name: t('docudesk', 'OCR text extraction'),
					purpose: t('docudesk', 'Extract machine-readable text from scanned documents and images.'),
					retention: t('docudesk', 'not declared'),
				},
				{
					code: 'docudesk-metadata-enrichment',
					name: t('docudesk', 'Document metadata enrichment'),
					purpose: t('docudesk', 'Language detection, keyword extraction, and topic classification.'),
					retention: t('docudesk', 'not declared'),
				},
				{
					code: 'docudesk-signing',
					name: t('docudesk', 'Digital document signing'),
					purpose: t('docudesk', 'Maintain a tamper-evident audit trail of electronic signing activities.'),
					retention: t('docudesk', 'P10Y (Archiefwet 1995 selectielijst cat. 5.1.3)'),
				},
			]
		},
	},
	mounted() {
		this.fetchAll()
	},
	methods: {
		/**
		 * Reset the selected schema when the register for a type changes.
		 *
		 * @param type
		 * @spec openspec/specs/admin-settings/spec.md#requirement-openregister-integration-configuration-req-set-02
		 */
		onRegisterChange(type) {
			this.sections = {
				...this.sections,
				[type]: {
					...this.sections[type],
					selectedSchema: '',
				},
			}
		},
		/**
		 * Load all settings, available registers and OpenRegister status.
		 *
		 * @spec openspec/specs/admin-settings/spec.md#requirement-settings-rest-api-req-set-06
		 */
		fetchAll() {
			this.loading = true
			fetch('/index.php/apps/docudesk/api/settings', { method: 'GET' })
				.then((response) => response.json())
				.then((data) => {
					this.openRegisterInstalled = data.openRegisters
					this.isAdmin = data.isAdmin ?? false
					this.settingsData = data
					this.availableRegisters = data.availableRegisters

					// Backend warning state.
					if (data.anonymiserBackend) {
						this.anonymiserBackend = {
							method: data.anonymiserBackend.method ?? 'regex',
							appApiInstalled: data.anonymiserBackend.appApiInstalled ?? false,
							warningDismissed: data.anonymiserBackend.warningDismissed ?? false,
							showWarning: data.anonymiserBackend.showWarning ?? false,
						}
					}

					// Update local settings
					this.settings.publication_objection_period_days = data.publication_objection_period_days ?? 28
					this.settings.enable_language_detection = data.enable_language_detection ?? true
					this.settings.enable_keyword_extraction = data.enable_keyword_extraction ?? true
					this.settings.enable_topic_classification = data.enable_topic_classification ?? true
					this.settings.ocr_enabled = data.ocr_enabled ?? true
					this.settings.ocr_dpi = data.ocr_dpi ?? 300
					// Signing settings
					this.settings.signing_enabled = data.signing_enabled === '1' || data.signing_enabled === true
					this.settings.signing_provider = data.signing_provider || 'native'
					this.settings.signing_default_level = data.signing_default_level || 'SES'
					this.settings.signing_request_expiry_days = parseInt(data.signing_request_expiry_days, 10) || 30
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
		/**
		 * Save the register/schema configuration for a single object type.
		 *
		 * @param type
		 * @spec openspec/specs/admin-settings/spec.md#requirement-openregister-integration-configuration-req-set-02
		 */
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
		/**
		 * Save all DocuDesk settings (consent period, feature toggles, OCR, registers).
		 *
		 * @spec openspec/specs/admin-settings/spec.md#requirement-settings-rest-api-req-set-06
		 */
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
				signing_enabled: this.settings.signing_enabled ? '1' : '0',
				signing_provider: this.settings.signing_provider || 'native',
				signing_default_level: this.settings.signing_default_level || 'SES',
				signing_request_expiry_days: String(this.settings.signing_request_expiry_days || 30),
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
		/**
		 * Handle the anonymiser backend warning being dismissed.
		 * Hides the banner immediately without a page reload.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-8
		 */
		onAnonymiserWarningDismissed() {
			this.anonymiserBackend = { ...this.anonymiserBackend, showWarning: false, warningDismissed: true }
		},

		/**
		 * Re-enable the anonymiser backend warning by calling the reset endpoint.
		 * Clears the per-admin dismissal flag; the banner appears on the next load.
		 *
		 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-8
		 */
		async resetAnonymiserWarning() {
			try {
				const response = await fetch(
					'/index.php/apps/docudesk/api/admin/anonymiser-warning/reset',
					{ method: 'POST' },
				)
				if (response.ok === false) {
					throw new Error('HTTP ' + response.status)
				}
				this.anonymiserBackend = { ...this.anonymiserBackend, warningDismissed: false, showWarning: true }
			} catch (err) {
				showError(t('docudesk', 'Failed to reset the anonymiser backend warning'))
			}
		},

		/**
		 * Open an external documentation/configuration link.
		 *
		 * @param url
		 * @param target
		 * @spec openspec/specs/admin-settings/spec.md#requirement-external-documentation-urls-req-set-09
		 */
		openLink(url, target = '') {
			window.open(url, target)
		},

		/**
		 * Open OpenRegister's AVG per-access processing log (verwerkingenlogging)
		 * scoped to DocuDesk's registers. The export and access control are
		 * provided by OpenRegister (OR-PA-7/OR-PA-8); DocuDesk only deep-links.
		 *
		 * @spec openspec/specs/processing-activity-export/spec.md#requirement-the-admin-ui-must-surface-the-platform-register-scoped-to-docudesk
		 */
		openProcessingExport() {
			const registers = ['document', 'signing', 'dossier', 'consent']
				.map((slug) => `register[]=${encodeURIComponent(slug)}`)
				.join('&')
			this.openLink(`/index.php/apps/openregister/api/avg/verwerkingen?${registers}`, '_blank')
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

.processing-activities {
	list-style: none;
	margin: 8px 0 0;
	padding: 0;
}

.processing-activities li {
	display: flex;
	flex-direction: column;
	gap: 2px;
	padding: 6px 0;
	border-bottom: 1px solid var(--color-border);
}

.processing-meta {
	color: var(--color-text-lighter);
	font-size: 0.9em;
}

.processing-export-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin: 4px 0;
}

</style>
