<script setup>
import { translate as t } from '@nextcloud/l10n'
import { batchAnonymizationStore } from '../../store/store.js'
</script>
<template>
	<div class="batch-anonymization">
		<h2>{{ t('docudesk', 'Batch Anonymization') }}</h2>
		<div class="step-indicator">
			<div v-for="(step, index) in steps" :key="step.label" class="step" :class="{ active: index < batchAnonymizationStore.stepNumber, current: index === batchAnonymizationStore.stepNumber }">
				<div class="step-circle">{{ index + 1 }}</div>
				<span class="step-label">{{ step.label }}</span>
			</div>
		</div>
		<div v-if="!batchAnonymizationStore.isActive" class="upload-section">
			<div class="drop-zone" :class="{ dragging: isDragging }" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
				<p>{{ t('docudesk', 'Drag and drop multiple files here, or click to select') }}</p>
				<input ref="fileInput" type="file" multiple class="file-input" @change="handleFileSelect">
				<NcButton type="secondary" @click="$refs.fileInput.click()">{{ t('docudesk', 'Select Files') }}</NcButton>
			</div>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'extracting'" class="extracting-section">
			<h3>{{ t('docudesk', 'Analyzing documents...') }}</h3>
			<NcProgressBar :value="batchAnonymizationStore.progress" />
			<p>{{ batchAnonymizationStore.progress }}%</p>
			<div class="file-list">
				<div v-for="file in batchAnonymizationStore.files" :key="file.fileId || file.fileName" class="file-item">
					<span>{{ file.fileName }}</span>
					<NcLoadingIcon v-if="file.status === 'uploaded'" :size="16" />
					<span v-if="file.status === 'extracted'" class="status-done">&#10003; {{ file.entityCount }} entities</span>
					<span v-if="file.status === 'error'" class="status-error">&#10007;</span>
				</div>
			</div>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'review'" class="review-section">
			<h3>{{ t('docudesk', 'Review Detected Entities') }}</h3>
			<EntityReviewTable :entities="batchAnonymizationStore.entities" :file-count="batchAnonymizationStore.filesWithEntities" @toggle="batchAnonymizationStore.toggleEntity($event)" @bulk-select="batchAnonymizationStore.setVisibleEntities($event, true)" @bulk-deselect="batchAnonymizationStore.setVisibleEntities($event, false)" @confidence-change="handleConfidenceChange" />
			<NcButton type="primary" @click="batchAnonymizationStore.anonymizeBatch()">{{ t('docudesk', 'Anonymize {count} entities', { count: batchAnonymizationStore.selectedEntityCount }) }}</NcButton>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'anonymizing'" class="processing-section">
			<NcLoadingIcon :size="44" />
			<p>{{ t('docudesk', 'Anonymizing documents...') }}</p>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'completed'" class="completed-section">
			<NcNoteCard type="success">{{ t('docudesk', 'Batch anonymization completed!') }}</NcNoteCard>
			<div class="summary-stats">
				<div class="stat"><span class="stat-value">{{ batchAnonymizationStore.totalFiles }}</span><span class="stat-label">{{ t('docudesk', 'Files') }}</span></div>
				<div class="stat"><span class="stat-value">{{ batchAnonymizationStore.selectedEntityCount }}</span><span class="stat-label">{{ t('docudesk', 'Entities') }}</span></div>
			</div>
			<div class="completed-actions">
				<NcButton type="secondary" @click="downloadReport">{{ t('docudesk', 'Download Report (CSV)') }}</NcButton>
				<NcButton type="primary" @click="batchAnonymizationStore.reset()">{{ t('docudesk', 'Start New Batch') }}</NcButton>
			</div>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'error'" class="error-section">
			<NcNoteCard type="error">{{ batchAnonymizationStore.error || t('docudesk', 'An error occurred.') }}</NcNoteCard>
			<NcButton type="primary" @click="batchAnonymizationStore.reset()">{{ t('docudesk', 'Try Again') }}</NcButton>
		</div>
	</div>
</template>
<script>
import { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import EntityReviewTable from './EntityReviewTable.vue'
export default {
	name: 'BatchAnonymizationView',
	components: { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard, EntityReviewTable },
	data() { return { isDragging: false, steps: [{ label: 'Upload' }, { label: 'Analyze' }, { label: 'Review' }, { label: 'Anonymize' }, { label: 'Done' }] } },
	methods: {
		handleDrop(event) { this.isDragging = false; const files = event.dataTransfer?.files; if (files?.length > 0) { batchAnonymizationStore.uploadBatch(files) } },
		handleFileSelect(event) { const files = event.target?.files; if (files?.length > 0) { batchAnonymizationStore.uploadBatch(files) } },
		handleConfidenceChange(t) { batchAnonymizationStore.minConfidence = t; batchAnonymizationStore.fetchEntities() },
		downloadReport() { window.open(batchAnonymizationStore.getReportUrl(), '_blank') },
	},
}
</script>
<style scoped>
.batch-anonymization { padding: 20px; max-width: 900px }
.step-indicator { display: flex; justify-content: space-between; margin-bottom: 32px; padding: 0 16px }
.step { display: flex; flex-direction: column; align-items: center; flex: 1 }
.step-circle { width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--color-border); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600; color: var(--color-text-maxcontrast); background: var(--color-main-background); margin-bottom: 6px }
.step.active .step-circle { border-color: var(--color-success); background: var(--color-success); color: white }
.step.current .step-circle { border-color: var(--color-primary); background: var(--color-primary); color: white }
.step-label { font-size: 0.8rem; color: var(--color-text-maxcontrast) }
.drop-zone { border: 2px dashed var(--color-border); border-radius: 12px; padding: 48px 24px; text-align: center; cursor: pointer }
.drop-zone:hover, .drop-zone.dragging { border-color: var(--color-primary); background: var(--color-primary-element-light) }
.file-input { display: none }
.file-list { margin: 16px 0 }
.file-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-bottom: 1px solid var(--color-border) }
.status-done { color: var(--color-success) }
.status-error { color: var(--color-error) }
.processing-section { display: flex; flex-direction: column; align-items: center; padding: 48px }
.summary-stats { display: flex; gap: 32px; margin: 24px 0 }
.stat { display: flex; flex-direction: column; align-items: center }
.stat-value { font-size: 2rem; font-weight: 700; color: var(--color-primary) }
.stat-label { font-size: 0.85rem; color: var(--color-text-maxcontrast) }
.completed-actions { display: flex; gap: 12px; margin-top: 24px }
</style>
