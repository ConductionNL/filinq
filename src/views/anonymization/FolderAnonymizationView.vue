<template>
	<div class="folder-anonymization">
		<h2>Folder Analysis &amp; Anonymization</h2>

		<!-- Step 1: Folder input -->
		<div v-if="!store.isActive" class="input-section">
			<p>Enter a folder path from your Nextcloud files to analyze all documents in it.</p>
			<div class="folder-input">
				<input
					v-model="folderPath"
					type="text"
					:placeholder="t('docudesk', 'e.g. Documents/contracts')"
					class="folder-path-input"
					@keyup.enter="startAnalysis">
				<NcButton type="primary" :disabled="!folderPath.trim() || store.processing" @click="startAnalysis">
					{{ store.processing ? t('docudesk', 'Starting...') : t('docudesk', 'Analyze Folder') }}
				</NcButton>
			</div>
		</div>

		<!-- Step 2: Extraction progress -->
		<div v-if="store.batchStatus === 'extracting'" class="progress-section">
			<h3>{{ t('docudesk', 'Analyzing files...') }}</h3>
			<NcProgressBar :value="store.progress" />
			<p class="progress-text">
				{{ store.extractedCount }} / {{ store.totalFiles }} {{ t('docudesk', 'files processed') }}
			</p>
			<div class="file-list">
				<div v-for="f in store.files" :key="f.fileId || f.fileName" class="file-item">
					<span class="file-name">{{ f.fileName }}</span>
					<span :class="'status-badge status-' + f.status">{{ f.status }}</span>
					<span v-if="f.entityCount" class="entity-count">{{ f.entityCount }} {{ t('docudesk', 'entities') }}</span>
				</div>
			</div>
		</div>

		<!-- Step 3: Entity review -->
		<div v-if="store.batchStatus === 'review'" class="review-section">
			<h3>{{ t('docudesk', 'Review Entities') }}</h3>
			<p>{{ t('docudesk', 'Folder') }}: <strong>{{ store.folderPath }}</strong></p>
			<EntityReviewTable
				:entities="store.entities"
				:file-count="store.filesWithEntities"
				@toggle="store.toggleEntity($event)"
				@bulk-select="store.setVisibleEntities($event, true)"
				@bulk-deselect="store.setVisibleEntities($event, false)" />
			<div class="action-bar">
				<NcButton type="primary" :disabled="store.selectedEntityCount === 0" @click="store.anonymizeBatch()">
					{{ t('docudesk', 'Anonymize %n entity', 'Anonymize %n entities', store.selectedEntityCount) }}
				</NcButton>
				<NcButton type="tertiary" @click="store.reset()">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
			</div>
		</div>

		<!-- Step 4: Anonymizing -->
		<div v-if="store.batchStatus === 'anonymizing'" class="loading-section">
			<NcLoadingIcon :size="44" />
			<p>{{ t('docudesk', 'Anonymizing documents...') }}</p>
		</div>

		<!-- Step 5: Completed -->
		<div v-if="store.batchStatus === 'completed'" class="completed-section">
			<NcNoteCard type="success">
				{{ t('docudesk', 'All documents in the folder have been anonymized. Anonymized copies have been saved with the _anonymized suffix.') }}
			</NcNoteCard>
			<div class="action-bar">
				<NcButton type="secondary" @click="downloadReport">
					{{ t('docudesk', 'Download Report') }}
				</NcButton>
				<NcButton type="primary" @click="store.reset()">
					{{ t('docudesk', 'Analyze Another Folder') }}
				</NcButton>
			</div>
		</div>

		<!-- Error state -->
		<div v-if="store.batchStatus === 'error'" class="error-section">
			<NcNoteCard type="error">
				{{ store.error || t('docudesk', 'An error occurred') }}
			</NcNoteCard>
			<NcButton type="primary" @click="store.reset()">
				{{ t('docudesk', 'Try Again') }}
			</NcButton>
		</div>
	</div>
</template>
<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { folderAnonymizationStore } from '../../store/store.js'
import EntityReviewTable from './EntityReviewTable.vue'

export default {
	name: 'FolderAnonymizationView',
	components: { NcButton, NcProgressBar, NcLoadingIcon, NcNoteCard, EntityReviewTable },
	/**
	 * Expose the folder anonymization store to the Options API.
	 *
	 * @spec openspec/changes/folder-analysis-anonymization/tasks.md#3-1
	 */
	setup() {
		return { store: folderAnonymizationStore }
	},
	data() {
		return { folderPath: '' }
	},
	methods: {
		t,
		/**
		 * Start a folder anonymization batch for the entered folder path.
		 *
		 * @spec openspec/changes/folder-analysis-anonymization/tasks.md#3-1
		 */
		startAnalysis() {
			const path = this.folderPath.trim()
			if (path) {
				folderAnonymizationStore.startFolderBatch(path)
			}
		},
		/**
		 * Open the completed batch report in a new tab.
		 *
		 * @spec openspec/changes/folder-analysis-anonymization/tasks.md#8-1
		 */
		downloadReport() {
			window.open(folderAnonymizationStore.getReportUrl(), '_blank')
		},
	},
}
</script>
<style scoped>
.folder-anonymization {
	padding: 20px;
	max-width: 900px;
}

.input-section p {
	color: var(--color-text-maxcontrast);
	margin-bottom: 16px;
}

.folder-input {
	display: flex;
	gap: 12px;
	align-items: center;
}

.folder-path-input {
	flex: 1;
	padding: 8px 12px;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	font-size: 14px;
}

.folder-path-input:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.progress-section {
	margin-top: 16px;
}

.progress-text {
	margin: 8px 0 16px;
	color: var(--color-text-maxcontrast);
}

.file-list {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	overflow: hidden;
}

.file-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 10px 14px;
	border-bottom: 1px solid var(--color-border);
}

.file-item:last-child {
	border-bottom: none;
}

.file-name {
	flex: 1;
}

.status-badge {
	padding: 2px 10px;
	border-radius: 12px;
	font-size: 0.8rem;
}

.status-uploaded {
	background: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.status-extracted {
	background: var(--color-success);
	color: white;
}

.status-error {
	background: var(--color-error);
	color: white;
}

.entity-count {
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
}

.review-section {
	margin-top: 16px;
}

.action-bar {
	display: flex;
	gap: 12px;
	margin-top: 16px;
}

.loading-section {
	text-align: center;
	padding: 48px;
}

.completed-section {
	margin-top: 16px;
}

.error-section {
	margin-top: 16px;
}

</style>
