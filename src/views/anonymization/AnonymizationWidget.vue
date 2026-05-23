<template>
	<div class="anonymization-content">
		<h2 class="pageHeader">
			{{ t('docudesk', 'Anonymization') }}
		</h2>

		<!-- Step indicator bar -->
		<div class="step-indicator">
			<div v-for="(step, index) in steps"
				:key="step.label"
				class="step"
				:class="{ active: index < stepNumber, current: index === stepNumber }">
				<div class="step-circle">
					{{ index + 1 }}
				</div>
				<span class="step-label">{{ step.label }}</span>
			</div>
		</div>

		<!-- Idle / Upload state -->
		<div v-if="currentStep === 'idle' || currentStep === 'uploading' || currentStep === 'queued'" class="upload-section">
			<div
				class="drop-zone"
				:class="{ dragging: isDragging }"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop.prevent="handleDrop">
				<Upload :size="48" />
				<p class="drop-text">
					{{ t('docudesk', 'Drag and drop a file here, or click to select') }}
				</p>
				<input
					ref="fileInput"
					type="file"
					class="file-input"
					@change="handleFileSelect">
				<NcButton type="secondary" @click="$refs.fileInput.click()">
					{{ t('docudesk', 'Select File') }}
				</NcButton>
			</div>

			<div v-if="currentStep === 'uploading'" class="progress-section">
				<p>{{ t('docudesk', 'Uploading {name}...', { name: selectedFileName }) }}</p>
				<NcProgressBar :value="uploadProgress" />
			</div>
		</div>

		<!-- Extracting state -->
		<div v-if="currentStep === 'extracting'" class="processing-section">
			<NcLoadingIcon :size="44" />
			<p class="processing-text">
				{{ t('docudesk', 'Analyzing document for personal data...') }}
			</p>
		</div>

		<!-- Anonymizing state -->
		<div v-if="currentStep === 'anonymizing'" class="processing-section">
			<NcLoadingIcon :size="44" />
			<p class="processing-text">
				{{ t('docudesk', 'Anonymizing {count} entities...', { count: extractionResult?.entityCount || 0 }) }}
			</p>
		</div>

		<!-- Completed state -->
		<div v-if="currentStep === 'completed'" class="results-section">
			<!-- No entities found -->
			<template v-if="!extractionResult?.entities?.length">
				<NcNoteCard type="info">
					{{ t('docudesk', 'No personal data found in this document.') }}
				</NcNoteCard>
			</template>

			<!-- Entities found and anonymized -->
			<template v-else>
				<NcNoteCard type="success">
					{{ t('docudesk', 'Document anonymized successfully! {count} entities replaced.', { count: anonymizationResult?.replacementCount || 0 }) }}
				</NcNoteCard>

				<!-- Anonymized file info -->
				<div v-if="anonymizationResult" class="file-info">
					<FileDocumentOutline :size="20" />
					<span>{{ anonymizationResult.anonymizedFileName }}</span>
				</div>

				<!-- Entity table -->
				<div class="entity-table-wrapper">
					<h3>{{ t('docudesk', 'Detected Entities') }}</h3>
					<table class="entity-table">
						<thead>
							<tr>
								<th>{{ t('docudesk', 'Type') }}</th>
								<th>{{ t('docudesk', 'Value') }}</th>
								<th>{{ t('docudesk', 'Confidence') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(entity, index) in extractionResult.entities" :key="index">
								<td>
									<span class="entity-type-badge">{{ entity.type }}</span>
								</td>
								<td>{{ entity.value }}</td>
								<td>{{ formatConfidence(entity.confidence) }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</template>

			<NcButton type="primary" class="reset-button" @click="anonymizationStore.reset()">
				{{ t('docudesk', 'Anonymize Another') }}
			</NcButton>
		</div>

		<!-- Error state -->
		<div v-if="currentStep === 'error'" class="error-section">
			<NcNoteCard type="error">
				{{ errorMessage }}
			</NcNoteCard>
			<NcButton type="primary" class="reset-button" @click="anonymizationStore.reset()">
				{{ t('docudesk', 'Try Again') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import Upload from 'vue-material-design-icons/Upload.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import { anonymizationStore } from '../../store/store.js'

// Map a file-entry `status` (per useAnonymizationStore — see
// src/store/modules/anonymization.js) onto the 0-based step index
// rendered by the step-indicator bar.
const STATUS_TO_STEP = {
	queued: 0,
	uploading: 0,
	extracting: 1,
	anonymizing: 2,
	completed: 3,
	error: 3,
}

export default {
	name: 'AnonymizationWidget',
	components: {
		NcButton,
		NcProgressBar,
		NcLoadingIcon,
		NcNoteCard,
		Upload,
		FileDocumentOutline,
	},
	// Vue 2.7 setup() runs before data() and merges its return value into
	// the component instance. Exposing the Pinia `anonymizationStore`
	// here lets the template (and computeds/methods via `this`) reach
	// the store reactively without the Vue 2.7 `<script setup>` SFC
	// compiler quirk where imports declared in `<script setup>` do NOT
	// bind into a sibling Options API `<script>` block — which
	// previously left `anonymizationStore` undefined and broke every
	// v-if in the template.
	setup() {
		return { anonymizationStore }
	},
	data() {
		return {
			isDragging: false,
			selectedFileName: '',
			steps: [
				{ label: t('docudesk', 'Upload') },
				{ label: t('docudesk', 'Analyze') },
				{ label: t('docudesk', 'Anonymize') },
				{ label: t('docudesk', 'Done') },
			],
		}
	},
	computed: {
		// The widget template was originally written against an older
		// store API (`currentStep`, `stepNumber`, `uploadProgress`,
		// `extractionResult`, `anonymizationResult`). The current
		// `useAnonymizationStore` (Pinia) instead exposes a per-file
		// queue under `files[]`, each with a `status` field. The
		// computeds below adapt the new store shape to the old
		// vocabulary so the existing template renders correctly without
		// a wider refactor (see follow-up to unify on the queue model).
		currentFile() {
			const files = this.anonymizationStore.files
			if (!files || files.length === 0) {
				return null
			}
			return files[files.length - 1]
		},
		currentStep() {
			if (!this.currentFile) {
				return 'idle'
			}
			return this.currentFile.status
		},
		stepNumber() {
			if (!this.currentFile) {
				return 0
			}
			return STATUS_TO_STEP[this.currentFile.status] ?? 0
		},
		uploadProgress() {
			// The store doesn't track per-byte upload progress yet;
			// surface an indeterminate-style value while uploading.
			return this.currentStep === 'uploading' ? 0 : 0
		},
		extractionResult() {
			if (!this.currentFile) {
				return null
			}
			return {
				entityCount: this.currentFile.entityCount,
				// The store currently doesn't persist individual
				// entities returned by /extract — the table renders
				// from this list, so leave it empty until the store
				// is extended to keep them.
				entities: [],
			}
		},
		anonymizationResult() {
			if (!this.currentFile || !this.currentFile.anonymizedFileName) {
				return null
			}
			return {
				anonymizedFileName: this.currentFile.anonymizedFileName,
				replacementCount: this.currentFile.replacementCount,
			}
		},
		errorMessage() {
			return this.currentFile?.error || t('docudesk', 'An unexpected error occurred.')
		},
	},
	methods: {
		t,
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				this.startPipeline(files[0])
			}
		},
		handleFileSelect(event) {
			const files = event.target?.files
			if (files && files.length > 0) {
				this.startPipeline(files[0])
			}
		},
		startPipeline(file) {
			this.selectedFileName = file.name
			this.anonymizationStore.addFiles([file])
		},
		formatConfidence(confidence) {
			if (typeof confidence === 'number') {
				return (confidence * 100).toFixed(1) + '%'
			}
			return 'N/A'
		},
	},
}
</script>

<style scoped>
.anonymization-content {
	padding: 20px;
	max-width: 800px;
}

/* Step indicator */
.step-indicator {
	display: flex;
	justify-content: space-between;
	margin-bottom: 32px;
	padding: 0 16px;
}

.step {
	display: flex;
	flex-direction: column;
	align-items: center;
	flex: 1;
	position: relative;
}

.step-circle {
	width: 32px;
	height: 32px;
	border-radius: 50%;
	border: 2px solid var(--color-border);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 0.85rem;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	background-color: var(--color-main-background);
	margin-bottom: 6px;
}

.step.active .step-circle {
	border-color: var(--color-success);
	background-color: var(--color-success);
	color: white;
}

.step.current .step-circle {
	border-color: var(--color-primary);
	background-color: var(--color-primary);
	color: white;
}

.step-label {
	font-size: 0.8rem;
	color: var(--color-text-maxcontrast);
}

.step.active .step-label,
.step.current .step-label {
	color: var(--color-main-text);
	font-weight: 500;
}

/* Upload section */
.upload-section {
	margin-bottom: 24px;
}

.drop-zone {
	border: 2px dashed var(--color-border);
	border-radius: 12px;
	padding: 48px 24px;
	text-align: center;
	cursor: pointer;
	transition: border-color 0.2s, background-color 0.2s;
}

.drop-zone:hover,
.drop-zone.dragging {
	border-color: var(--color-primary);
	background-color: var(--color-primary-element-light);
}

.drop-text {
	margin: 12px 0 16px 0;
	color: var(--color-text-maxcontrast);
}

.file-input {
	display: none;
}

.progress-section {
	margin-top: 16px;
	text-align: center;
}

.progress-section p {
	margin-bottom: 8px;
	color: var(--color-text-maxcontrast);
}

/* Processing section */
.processing-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 48px 24px;
}

.processing-text {
	margin-top: 16px;
	color: var(--color-text-maxcontrast);
	font-size: 1.1rem;
}

/* Results section */
.results-section {
	margin-bottom: 24px;
}

.file-info {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 16px;
	margin: 16px 0;
	border-radius: 8px;
	background-color: var(--color-background-dark);
}

.entity-table-wrapper {
	margin: 24px 0;
}

.entity-table-wrapper h3 {
	margin-bottom: 12px;
}

.entity-table {
	width: 100%;
	border-collapse: collapse;
}

.entity-table th,
.entity-table td {
	padding: 10px 12px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.entity-table th {
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	text-transform: uppercase;
}

.entity-type-badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.8rem;
	font-weight: 500;
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element);
}

.reset-button {
	margin-top: 24px;
}

/* Error section */
.error-section {
	margin-bottom: 24px;
}
</style>
