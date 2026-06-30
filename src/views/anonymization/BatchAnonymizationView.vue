<template>
	<div class="batch-anonymization">
		<h2>{{ t('docudesk', 'Batch Anonymization') }}</h2>
		<div v-if="!batchAnonymizationStore.isActive" class="upload-section">
			<div class="drop-zone" @dragover.prevent @drop.prevent="handleDrop">
				<p>{{ t('docudesk', 'Drag and drop files here') }}</p>
				<input ref="fileInput"
					type="file"
					multiple
					style="display:none"
					@change="handleFileSelect">
				<NcButton type="secondary" @click="$refs.fileInput.click()">
					{{ t('docudesk', 'Select Files') }}
				</NcButton>
			</div>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'extracting'">
			<h3>{{ t('docudesk', 'Analyzing...') }}</h3>
			<NcProgressBar :value="batchAnonymizationStore.progress" />
			<div v-for="f in batchAnonymizationStore.files" :key="f.fileId||f.fileName" class="file-item">
				{{ f.fileName }} - {{ f.status }} <span v-if="f.entityCount">({{ n('docudesk', '%n entity', '%n entities', f.entityCount) }})</span>
			</div>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'review'">
			<h3>{{ t('docudesk', 'Review Entities') }}</h3>
			<EntityReviewTable
				:entities="batchAnonymizationStore.entities"
				:file-count="batchAnonymizationStore.filesWithEntities"
				@toggle="batchAnonymizationStore.toggleEntity($event)"
				@bulk-select="batchAnonymizationStore.setVisibleEntities($event, true)"
				@bulk-deselect="batchAnonymizationStore.setVisibleEntities($event, false)" />
			<NcButton type="primary" @click="batchAnonymizationStore.anonymizeBatch()">
				{{ n('docudesk', 'Anonymize %n entity', 'Anonymize %n entities', batchAnonymizationStore.selectedEntityCount) }}
			</NcButton>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'anonymizing'" style="text-align:center;padding:48px">
			<NcLoadingIcon :size="44" /><p>{{ t('docudesk', 'Anonymizing...') }}</p>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'completed'">
			<NcNoteCard type="success">
				{{ t('docudesk', 'Batch anonymization completed!') }}
			</NcNoteCard>
			<NcButton type="secondary" @click="window.open(batchAnonymizationStore.getReportUrl(), '_blank')">
				{{ t('docudesk', 'Download Report') }}
			</NcButton>
			<NcButton type="primary" @click="batchAnonymizationStore.reset()">
				{{ t('docudesk', 'New Batch') }}
			</NcButton>
		</div>
		<div v-if="batchAnonymizationStore.batchStatus === 'error'">
			<NcNoteCard type="error">
				{{ batchAnonymizationStore.error || t('docudesk', 'An error occurred') }}
			</NcNoteCard>
			<NcButton type="primary" @click="batchAnonymizationStore.reset()">
				{{ t('docudesk', 'Try Again') }}
			</NcButton>
		</div>
	</div>
</template>
<script>
import { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { batchAnonymizationStore } from '../../store/store.js'
import EntityReviewTable from './EntityReviewTable.vue'

export default {
	name: 'BatchAnonymizationView',
	components: { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard, EntityReviewTable },
	setup() {
		return { batchAnonymizationStore }
	},
	data() { return { isDragging: false } },
	methods: {
		handleDrop(e) { const f = e.dataTransfer?.files; if (f?.length) batchAnonymizationStore.uploadBatch(f) },
		handleFileSelect(e) { const f = e.target?.files; if (f?.length) batchAnonymizationStore.uploadBatch(f) },
	},
}
</script>
<style scoped>
.batch-anonymization {
	padding: 20px; max-width: 900px
}

.drop-zone {
	border: 2px dashed var(--color-border); border-radius: var(--border-radius-large); padding: 48px; text-align: center
}

.file-item {
	padding: 8px 12px; border-bottom: 1px solid var(--color-border)
}
</style>
