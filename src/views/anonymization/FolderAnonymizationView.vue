<template>
	<div class="folder-anonymization">
		<h2>{{ t('docudesk', 'Folder Analysis & Anonymization') }}</h2>

		<!-- Step 1: Folder input -->
		<div v-if="!store.isActive" class="input-section">
			<p>
				{{
					t(
						'docudesk',
						'Enter a folder path from your Nextcloud files to analyze all documents in it.',
					)
				}}
			</p>
			<div class="folder-input">
				<input
					v-model="folderPath"
					type="text"
					:aria-label="t('docudesk', 'Folder path to analyse')"
					:placeholder="t('docudesk', 'e.g. Documents/contracts')"
					class="folder-path-input"
					@keyup.enter="startAnalysis" />
				<NcButton
					variant="primary"
					:disabled="!folderPath.trim() || store.processing"
					@click="startAnalysis">
					{{
						store.processing
							? t('docudesk', 'Starting...')
							: t('docudesk', 'Analyze Folder')
					}}
				</NcButton>
			</div>
		</div>

		<!-- Step 2: Extraction progress + optional dossier creation (Wave 4a) -->
		<div
			v-if="
				store.batchStatus === 'extracting' || store.batchStatus === 'review'
			"
			class="dossier-card">
			<h3>{{ t('docudesk', 'Optional: bind a dossier') }}</h3>
			<p class="muted">
				{{
					t(
						'docudesk',
						'Creating a dossier for this folder enables the per-dossier grondslagen report after anonymisation. You can skip this and anonymise without a dossier.',
					)
				}}
			</p>

			<div v-if="!store.hasDossier">
				<div class="row">
					<label class="inline-label">
						<span>{{ t('docudesk', 'Name') }}</span>
						<input
							v-model="store.dossier.name"
							type="text"
							:placeholder="t('docudesk', 'Dossier name')"
							class="text-input" />
					</label>
					<label class="inline-label">
						<span>{{ t('docudesk', 'Description (optional)') }}</span>
						<input
							v-model="store.dossier.description"
							type="text"
							:placeholder="t('docudesk', 'Short description')"
							class="text-input" />
					</label>
				</div>
				<div class="row">
					<label class="inline-label bases-label">
						<span>{{
							t('docudesk', 'Default grondslagen (Woo Art. 5)')
						}}</span>
						<select
							v-model="store.dossier.bases"
							multiple
							class="bases-select">
							<option
								v-for="basis in store.basesOptions"
								:key="basis"
								:value="basis">
								{{ basis }}
							</option>
						</select>
					</label>
				</div>
				<div class="row">
					<NcButton
						variant="primary"
						:disabled="store.dossier.creating || !store.folderId"
						@click="store.createDossier()">
						{{
							store.dossier.creating
								? t('docudesk', 'Creating dossier…')
								: t('docudesk', 'Create dossier for this folder')
						}}
					</NcButton>
				</div>
				<NcNoteCard v-if="store.dossier.error" type="error">
					{{ store.dossier.error }}
				</NcNoteCard>
			</div>

			<div v-else class="dossier-summary">
				<NcNoteCard type="success">
					<div>
						{{ t('docudesk', 'Dossier created.') }}
						<strong>{{ store.dossier.name }}</strong>
					</div>
					<div class="muted">
						UUID: <code>{{ store.dossier.uuid }}</code>
					</div>
					<div
						v-if="store.dossier.bases && store.dossier.bases.length"
						class="muted">
						{{ t('docudesk', 'Grondslagen') }}:
						{{ store.dossier.bases.join(', ') }}
					</div>
				</NcNoteCard>
			</div>
		</div>

		<div v-if="store.batchStatus === 'extracting'" class="progress-section">
			<h3>{{ t('docudesk', 'Analyzing files...') }}</h3>
			<NcProgressBar :value="store.progress" />
			<p class="progress-text">
				{{ store.extractedCount }} / {{ store.totalFiles }}
				{{ t('docudesk', 'files processed') }}
			</p>
			<div class="file-list">
				<div
					v-for="f in store.files"
					:key="f.fileId || f.fileName"
					class="file-item">
					<span class="file-name">{{ f.fileName }}</span>
					<span :class="'status-badge status-' + f.status">{{
						f.status
					}}</span>
					<span v-if="f.entityCount" class="entity-count"
						>{{ f.entityCount }} {{ t('docudesk', 'entities') }}</span
					>
				</div>
			</div>
		</div>

		<!-- Step 3: Entity review -->
		<div v-if="store.batchStatus === 'review'" class="review-section">
			<h3>{{ t('docudesk', 'Review Entities') }}</h3>
			<p>
				{{ t('docudesk', 'Folder') }}:
				<strong>{{ store.folderPath }}</strong>
			</p>
			<EntityReviewTable
				:entities="store.entities"
				:fileCount="store.filesWithEntities"
				:defaultBases="store.dossier.bases || []"
				@toggle="store.toggleEntity($event)"
				@bulkSelect="store.setVisibleEntities($event, true)"
				@bulkDeselect="store.setVisibleEntities($event, false)"
				@basesChange="store.setEntityBases($event.idx, $event.bases)"
				@skipChange="store.setEntitySkip($event.idx, $event.skip)" />
			<label class="flag-row">
				<input v-model="store.appendBasisSummary" type="checkbox" />
				<span>{{
					t(
						'docudesk',
						'Append a grondslagen-summary page to each anonymised PDF (Wave 4a)',
					)
				}}</span>
			</label>
			<!-- Hard warning: per-dossier placeholder numbers are carried across
				the folder's files, so the whole folder MUST be published as ONE
				publication/dossier. Splitting it into separate publications would
				re-introduce the cross-publication linking key the scope-local
				numbering exists to prevent. -->
			<NcNoteCard type="warning">
				{{
					t(
						'docudesk',
						'This folder is anonymised as one dossier: the same person keeps the same placeholder number ([PERSON: 1], …) across every file. You MUST publish the result as a single publication/dossier — do NOT split these files into separate publications, or the shared numbers would let readers re-link a person across them.',
					)
				}}
			</NcNoteCard>
			<div class="action-bar">
				<NcButton
					variant="primary"
					:disabled="store.selectedEntityCount === 0"
					@click="store.anonymizeFolder()">
					{{
						n(
							'docudesk',
							'Anonymize %n entity',
							'Anonymize %n entities',
							store.selectedEntityCount,
						)
					}}
				</NcButton>
				<NcButton variant="tertiary" @click="store.reset()">
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
				{{
					t(
						'docudesk',
						'All documents in the folder have been anonymized. Anonymized copies have been saved with the _anonymized suffix.',
					)
				}}
			</NcNoteCard>

			<!-- Wave 4a: dossier grondslagen report -->
			<div v-if="store.hasDossier" class="dossier-report-block">
				<h4>{{ t('docudesk', 'Dossier grondslagen report') }}</h4>
				<p class="muted">
					{{
						t(
							'docudesk',
							'Regenerates grondslagen.pdf at the dossier root, aggregating every anonymised file under this dossier.',
						)
					}}
				</p>
				<div class="action-bar">
					<NcButton
						variant="secondary"
						:disabled="store.report.generating"
						@click="store.generateDossierReport()">
						{{
							store.report.generating
								? t('docudesk', 'Generating…')
								: t(
										'docudesk',
										'Generate dossier grondslagen report',
									)
						}}
					</NcButton>
				</div>
				<NcNoteCard v-if="store.report.error" type="error">
					{{ store.report.error }}
				</NcNoteCard>
				<NcNoteCard v-if="store.report.result" type="success">
					<div>
						{{ t('docudesk', 'Report generated at') }}:
						<strong>{{ store.report.result.filePath }}</strong>
					</div>
				</NcNoteCard>
			</div>

			<div class="action-bar">
				<!--
					The batch CSV "Download Report" button is temporarily removed.
					The folder flow now anonymises file-by-file via the single-file
					endpoint and no longer runs the batch-anonymise step, so the
					batch record is never marked 'completed' and GET /batch/{id}/report
					would return HTTP 409 / empty. It comes back once per-file results
					are written back to the batch record (or a client-side summary is built).
				-->
				<NcButton variant="primary" @click="store.reset()">
					{{ t('docudesk', 'Analyze Another Folder') }}
				</NcButton>
			</div>
		</div>

		<!-- Error state -->
		<div v-if="store.batchStatus === 'error'" class="error-section">
			<NcNoteCard type="error">
				{{ store.error || t('docudesk', 'An error occurred') }}
			</NcNoteCard>
			<NcButton variant="primary" @click="store.reset()">
				{{ t('docudesk', 'Try Again') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard, NcProgressBar } from '@nextcloud/vue'
import EntityReviewTable from './EntityReviewTable.vue'
import { folderAnonymizationStore } from '../../store/store.js'

export default {
	name: 'FolderAnonymizationView',
	components: {
		NcButton,
		NcProgressBar,
		NcLoadingIcon,
		NcNoteCard,
		EntityReviewTable,
	},

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
		// Temporarily removed alongside the batch CSV "Download Report" button —
		// see the store's getReportUrl() note. Comes back when the batch report
		// is wired to the per-file anonymisation results.
		// downloadReport() {
		//     window.open(folderAnonymizationStore.getReportUrl(), '_blank')
		// },
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
	border-radius: var(--border-radius-large);
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

/* Wave 4a additions: dossier-creation card, flag toggle, report block. */
.dossier-card {
	border: 1px solid var(--color-border);
	border-radius: var(--dd-radius-md);
	padding: 16px;
	margin: 16px 0;
	background-color: var(--color-main-background);
}

.dossier-card h3 {
	margin-top: 0;
}

.muted {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
	margin-bottom: 8px;
}

.row {
	display: flex;
	gap: 12px;
	align-items: flex-start;
	flex-wrap: wrap;
	margin-bottom: 12px;
}

.inline-label {
	display: flex;
	flex-direction: column;
	gap: 4px;
	flex: 1;
	min-width: 200px;
}

.inline-label > span {
	color: var(--color-text-maxcontrast);
	font-size: 12px;
}

.text-input {
	padding: 6px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
}

.bases-label {
	flex: 1 1 100%;
}

.bases-select {
	width: 100%;
	min-height: 110px;
	font-size: 12px;
}

.dossier-summary code {
	font-family: monospace;
	font-size: 12px;
}

.flag-row {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	margin: 12px 0;
	cursor: pointer;
}

.dossier-report-block {
	border-top: 1px solid var(--color-border);
	padding-top: 12px;
	margin-top: 12px;
}

.dossier-report-block h4 {
	margin-top: 0;
}
</style>
