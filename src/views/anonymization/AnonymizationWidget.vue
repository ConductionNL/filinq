<template>
	<div class="anonymization-content">
		<h2 class="pageHeader">
			{{ t('docudesk', 'Anonymization') }}
		</h2>

		<!-- Drop zone — always present so users can queue more files at any time -->
		<div class="upload-section">
			<div
				class="drop-zone"
				:class="{ dragging: isDragging }"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop.prevent="handleDrop"
				@click="$refs.fileInput.click()">
				<Upload :size="48" />
				<p class="drop-text">
					{{ anonymizationStore.hasFiles
						? t('docudesk', 'Drop more files, or click to select')
						: t('docudesk', 'Drag and drop files here, or click to select')
					}}
				</p>
				<input
					ref="fileInput"
					type="file"
					multiple
					class="file-input"
					@change="handleFileSelect">
				<NcButton type="secondary" @click.stop="$refs.fileInput.click()">
					{{ t('docudesk', 'Select Files') }}
				</NcButton>
			</div>
		</div>

		<!-- Empty state — only shown when no files queued yet -->
		<NcEmptyContent
			v-if="!anonymizationStore.hasFiles"
			class="empty-state"
			:name="t('docudesk', 'No files queued')"
			:description="t('docudesk', 'Drop a document above to start the anonymization pipeline.')">
			<template #icon>
				<FileDocumentOutline />
			</template>
		</NcEmptyContent>

		<!-- One card per file in the queue -->
		<div v-if="anonymizationStore.hasFiles" class="file-list">
			<div class="file-list-header">
				<h3>{{ t('docudesk', 'Queue') }}</h3>
				<NcButton
					v-if="canClear"
					type="tertiary"
					@click="anonymizationStore.clearCompleted()">
					{{ t('docudesk', 'Clear finished') }}
				</NcButton>
			</div>

			<div v-for="file in anonymizationStore.files" :key="file.id" class="file-card">
				<!-- Header: name + status -->
				<div class="file-card-header">
					<div class="file-card-title">
						<FileDocumentOutline :size="20" />
						<span class="file-name" :title="file.name">{{ file.name }}</span>
					</div>
					<span class="file-status" :class="`status-${file.status}`">
						{{ statusLabel(file.status) }}
					</span>
				</div>

				<!-- Per-file step indicator -->
				<div class="step-indicator">
					<div
						v-for="(step, index) in steps"
						:key="step.label"
						class="step"
						:class="stepClass(file, index)">
						<div class="step-circle">
							{{ index + 1 }}
						</div>
						<span class="step-label">{{ step.label }}</span>
					</div>
				</div>

				<!-- In-flight state -->
				<div
					v-if="file.status === 'uploading' || file.status === 'extracting' || file.status === 'anonymizing'"
					class="processing-section">
					<NcLoadingIcon :size="32" />
					<p class="processing-text">
						{{ processingText(file) }}
					</p>
				</div>

				<!-- Completed without entities -->
				<NcNoteCard
					v-if="file.status === 'completed' && file.entityCount === 0"
					type="info">
					{{ t('docudesk', 'No personal data found in this document.') }}
				</NcNoteCard>

				<!-- Completed with entities -->
				<template v-if="file.status === 'completed' && file.entityCount > 0">
					<NcNoteCard type="success">
						{{ t('docudesk', 'Document anonymized successfully! {count} entities replaced.', { count: file.replacementCount }) }}
					</NcNoteCard>

					<div v-if="file.anonymizedFileName" class="file-info">
						<FileDocumentOutline :size="20" />
						<span>{{ file.anonymizedFileName }}</span>
					</div>

					<!-- Per-file entity table -->
					<div v-if="file.entities && file.entities.length > 0" class="entity-table-wrapper">
						<h4>{{ t('docudesk', 'Detected Entities') }}</h4>
						<table class="entity-table">
							<thead>
								<tr>
									<th>{{ t('docudesk', 'Type') }}</th>
									<th>{{ t('docudesk', 'Value') }}</th>
									<th>{{ t('docudesk', 'Confidence') }}</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="(entity, index) in file.entities" :key="index">
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

				<!-- Error state -->
				<NcNoteCard v-if="file.status === 'error'" type="error">
					{{ file.error || t('docudesk', 'An unexpected error occurred.') }}
				</NcNoteCard>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard, NcEmptyContent } from '@nextcloud/vue'
import Upload from 'vue-material-design-icons/Upload.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import { anonymizationStore } from '../../store/store.js'

// Per-file pipeline state → 0-based step index for the indicator bar.
// 'queued' is treated as "about to start step 0" so the first dot is the
// current one (not yet active).
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
		NcLoadingIcon,
		NcNoteCard,
		NcEmptyContent,
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
	/**
	 * Expose the anonymization pipeline store reactively to the Options API.
	 *
	 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
	 */
	setup() {
		return { anonymizationStore }
	},
	data() {
		return {
			isDragging: false,
			steps: [
				{ label: t('docudesk', 'Upload') },
				{ label: t('docudesk', 'Analyze') },
				{ label: t('docudesk', 'Anonymize') },
				{ label: t('docudesk', 'Done') },
			],
		}
	},
	computed: {
		/**
		 * Whether any completed/errored files exist that can be cleared.
		 *
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		canClear() {
			return this.anonymizationStore.files.some(
				(f) => f.status === 'completed' || f.status === 'error',
			)
		},
	},
	methods: {
		t,
		/**
		 * Queue files dropped onto the pipeline drop zone.
		 *
		 * @param event
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				this.anonymizationStore.addFiles(files)
			}
		},
		/**
		 * Queue files chosen via the file picker into the pipeline.
		 *
		 * @param event
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		handleFileSelect(event) {
			const files = event.target?.files
			if (files && files.length > 0) {
				this.anonymizationStore.addFiles(files)
			}
			// Reset input so the same files can be re-selected.
			event.target.value = ''
		},
		/**
		 * Map a file's status to its pipeline step index.
		 *
		 * @param file
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		stepIndex(file) {
			return STATUS_TO_STEP[file.status] ?? 0
		},
		/**
		 * Compute the CSS state classes for a pipeline step.
		 *
		 * @param file
		 * @param index
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		stepClass(file, index) {
			const current = this.stepIndex(file)
			if (file.status === 'completed') {
				return { active: true }
			}
			if (file.status === 'error') {
				return { error: index === current }
			}
			return {
				active: index < current,
				current: index === current,
			}
		},
		/**
		 * Human-readable label for a file processing status.
		 *
		 * @param status
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		statusLabel(status) {
			const labels = {
				queued: t('docudesk', 'Queued'),
				uploading: t('docudesk', 'Uploading'),
				extracting: t('docudesk', 'Analyzing'),
				anonymizing: t('docudesk', 'Anonymizing'),
				completed: t('docudesk', 'Done'),
				error: t('docudesk', 'Error'),
			}
			return labels[status] || status
		},
		/**
		 * Progress message describing the current processing stage of a file.
		 *
		 * @param file
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		processingText(file) {
			if (file.status === 'uploading') {
				return t('docudesk', 'Uploading {name}...', { name: file.name })
			}
			if (file.status === 'extracting') {
				return t('docudesk', 'Analyzing document for personal data...')
			}
			if (file.status === 'anonymizing') {
				return t('docudesk', 'Anonymizing {count} entities...', { count: file.entityCount || 0 })
			}
			return ''
		},
		/**
		 * Format a detection confidence score as a percentage.
		 *
		 * @param confidence
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
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
	max-width: 900px;
}

/* Upload section */
.upload-section {
	margin-bottom: 24px;
}

.drop-zone {
	border: 2px dashed var(--color-border);
	border-radius: 12px;
	padding: 32px 24px;
	text-align: center;
	cursor: pointer;
	transition: border-color 0.2s, background-color 0.2s;
}

.drop-zone:hover {
	border-color: var(--color-primary);
}

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

/* Empty state */
.empty-state {
	margin: 24px 0;
}

/* File list */
.file-list {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.file-list-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

.file-list-header h3 {
	margin: 0;
}

.file-card {
	border: 1px solid var(--color-border);
	border-radius: 12px;
	padding: 16px;
	background-color: var(--color-main-background);
}

.file-card-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 16px;
}

.file-card-title {
	display: flex;
	align-items: center;
	gap: 8px;
	overflow: hidden;
}

.file-name {
	font-weight: 600;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.file-status {
	font-size: 0.85rem;
	padding: 2px 10px;
	border-radius: 12px;
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
	white-space: nowrap;
}

.file-status.status-completed {
	background-color: var(--color-success);
	color: white;
}

.file-status.status-error {
	background-color: var(--color-error);
	color: white;
}

/* Step indicator */
.step-indicator {
	display: flex;
	justify-content: space-between;
	margin-bottom: 16px;
	padding: 0 8px;
}

.step {
	display: flex;
	flex-direction: column;
	align-items: center;
	flex: 1;
}

.step-circle {
	width: 28px;
	height: 28px;
	border-radius: 50%;
	border: 2px solid var(--color-border);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 0.8rem;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	background-color: var(--color-main-background);
	margin-bottom: 4px;
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

.step.error .step-circle {
	border-color: var(--color-error);
	background-color: var(--color-error);
	color: white;
}

.step-label {
	font-size: 0.75rem;
	color: var(--color-text-maxcontrast);
}

.step.active .step-label,
.step.current .step-label,
.step.error .step-label {
	color: var(--color-main-text);
	font-weight: 500;
}

/* Processing section */
.processing-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 24px;
}

.processing-text {
	margin-top: 12px;
	color: var(--color-text-maxcontrast);
}

/* Result info */
.file-info {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 10px 12px;
	margin: 12px 0;
	border-radius: 8px;
	background-color: var(--color-background-dark);
}

/* Entity table */
.entity-table-wrapper {
	margin-top: 16px;
}

.entity-table-wrapper h4 {
	margin: 0 0 8px 0;
}

.entity-table {
	width: 100%;
	border-collapse: collapse;
}

.entity-table th,
.entity-table td {
	padding: 8px 10px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.entity-table th {
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	font-size: 0.8rem;
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
</style>
