<script setup>
import { translate as t } from '@nextcloud/l10n'
import { anonymizationStore } from '../../store/store.js'
</script>

<template>
	<div class="anonymization-content">
		<h2 class="pageHeader">
			{{ t('docudesk', 'Anonymisation') }}
		</h2>
		<p class="page-description">
			{{ t('docudesk', 'Upload documents. Each file is uploaded and scanned for entities. Review the detected entities, optionally assign Woo Art. 5 grondslagen or mark entities to skip, then run anonymisation. (Smoke-test surface — not a production publication-prep page.)') }}
		</p>

		<!-- Drop zone -->
		<div
			class="drop-zone"
			:class="{ dragging: isDragging, busy: anonymizationStore.isProcessing }"
			@dragover.prevent="isDragging = true"
			@dragleave.prevent="isDragging = false"
			@drop.prevent="handleDrop">
			<Upload :size="48" />
			<p class="drop-text">
				{{ t('docudesk', 'Drag and drop files here, or click to select') }}
			</p>
			<input
				ref="fileInput"
				type="file"
				multiple
				class="file-input"
				@change="handleFileSelect">
			<NcButton type="secondary" @click="openPicker">
				{{ t('docudesk', 'Select files') }}
			</NcButton>
		</div>

		<!-- File queue -->
		<div v-if="anonymizationStore.hasFiles" class="queue">
			<div class="queue-header">
				<h3>{{ t('docudesk', 'Queue') }} ({{ anonymizationStore.files.length }})</h3>
				<NcButton
					v-if="anonymizationStore.hasExtracted"
					type="primary"
					:disabled="anonymizationStore.isProcessing"
					@click="anonymizationStore.anonymiseAllExtracted()">
					{{ t('docudesk', 'Anonymise all reviewed files') }}
				</NcButton>
				<NcButton
					v-if="anonymizationStore.hasCompleted"
					type="tertiary"
					:disabled="anonymizationStore.isProcessing"
					@click="anonymizationStore.clearCompleted()">
					{{ t('docudesk', 'Clear completed') }}
				</NcButton>
				<NcButton
					type="tertiary"
					:disabled="anonymizationStore.isProcessing"
					@click="anonymizationStore.reset()">
					{{ t('docudesk', 'Reset') }}
				</NcButton>
			</div>

			<div v-for="entry in anonymizationStore.files" :key="entry.id" class="file-card">
				<div class="file-card-header">
					<FileDocument :size="20" />
					<span class="file-name" :title="entry.name">{{ entry.name }}</span>
					<CnStatusBadge
						:label="statusLabel(entry.status)"
						:color-map="statusColorMap" />
					<span v-if="entry.entityCount" class="muted">
						{{ entry.entityCount }} {{ t('docudesk', 'entities') }}
					</span>
				</div>

				<!-- Loading state -->
				<div v-if="isActiveStatus(entry.status)" class="file-loading">
					<NcLoadingIcon :size="20" />
					<span>{{ statusLabel(entry.status) }}…</span>
				</div>

				<!-- Review state — Wave 1.3 surface -->
				<div v-if="entry.status === 'extracted'" class="review-section">
					<table class="review-table">
						<thead>
							<tr>
								<th>{{ t('docudesk', 'Entity') }}</th>
								<th>{{ t('docudesk', 'Type') }}</th>
								<th>{{ t('docudesk', 'Confidence') }}</th>
								<th>{{ t('docudesk', 'Grondslag (bases)') }}</th>
								<th>{{ t('docudesk', 'Skip') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(entity, idx) in entry.entities" :key="entity.relationId || idx">
								<td class="entity-cell">
									<span :title="entity.value">{{ truncate(entity.value, 60) }}</span>
									<span v-if="!entity.relationId" class="warn-text">
										{{ t('docudesk', '(no relation id — bases/skip will not persist)') }}
									</span>
									<span v-if="entity._patchError" class="error-text" :title="entity._patchError">
										{{ truncate(entity._patchError, 80) }}
									</span>
								</td>
								<td>
									<CnStatusBadge
										:label="entity.type"
										:color-map="entityTypeColorMap" />
								</td>
								<td class="numeric">
									{{ formatConfidence(entity.confidence) }}
								</td>
								<td class="bases-cell">
									<NcSelect
										v-model="entity._decisionBases"
										:options="basesOptions"
										:multiple="true"
										:input-label="t('docudesk', 'Grondslagen')"
										:placeholder="t('docudesk', 'Pick grondslagen…')"
										:disabled="!entity.relationId" />
								</td>
								<td>
									<NcCheckboxRadioSwitch
										:checked.sync="entity._decisionSkip"
										:disabled="!entity.relationId" />
								</td>
							</tr>
						</tbody>
					</table>
					<div class="review-actions">
						<NcButton
							type="primary"
							:disabled="anonymizationStore.isProcessing"
							@click="anonymizationStore.anonymiseEntry(entry)">
							{{ t('docudesk', 'Apply decisions and anonymise') }}
						</NcButton>
					</div>
				</div>

				<!-- Completed state -->
				<div v-if="entry.status === 'completed'" class="completed-section">
					<a
						v-if="entry.anonymizedFileId"
						:href="downloadUrl(entry.anonymizedFileId)"
						target="_blank"
						rel="noopener"
						class="download-link">
						<Download :size="18" />
						{{ entry.anonymizedFileName || t('docudesk', 'Download anonymised copy') }}
					</a>
					<span v-else class="muted">
						{{ t('docudesk', 'No entities detected — original file is fine to publish.') }}
					</span>
					<span v-if="entry.replacementCount" class="muted">
						· {{ entry.replacementCount }} {{ t('docudesk', 'replacements') }}
					</span>
				</div>

				<!-- Error state -->
				<div v-if="entry.status === 'error'" class="error-section">
					<span class="error-text" :title="entry.error">
						{{ entry.error || t('docudesk', 'Unknown error') }}
					</span>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcSelect } from '@nextcloud/vue'
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { generateUrl } from '@nextcloud/router'
import Upload from 'vue-material-design-icons/Upload.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FileDocument from 'vue-material-design-icons/FileDocument.vue'

// Woo Art. 5 grondslagen seeded by the dossier register (Wave 1.1).
// Hardcoded for the smoke-test widget — a production page would fetch
// /apps/openregister/api/objects/dossier/base instead so custom bases
// added by tenants surface too.
const BASES_OPTIONS = [
	'persoonsgegevens',
	'bijzondere-persoonsgegevens',
	'strafrechtelijk',
	'bedrijfs-fabricagegegevens',
	'onevenredige-benadeling',
	'nationale-veiligheid',
]

export default {
	name: 'AnonymizationWidget',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcSelect,
		CnStatusBadge,
		Upload,
		Download,
		FileDocument,
	},
	data() {
		return {
			isDragging: false,
			basesOptions: BASES_OPTIONS,
			statusColorMap: {
				[t('docudesk', 'Queued')]: 'default',
				[t('docudesk', 'Uploading')]: 'primary',
				[t('docudesk', 'Extracting')]: 'primary',
				[t('docudesk', 'Awaiting review')]: 'warning',
				[t('docudesk', 'Anonymising')]: 'warning',
				[t('docudesk', 'Completed')]: 'success',
				[t('docudesk', 'Error')]: 'error',
			},
			entityTypeColorMap: {
				PERSON: 'warning',
				ORGANIZATION: 'primary',
				OTHER: 'default',
			},
		}
	},
	methods: {
		openPicker() {
			this.$refs.fileInput.value = ''
			this.$refs.fileInput.click()
		},
		handleFileSelect(event) {
			const files = event.target.files
			if (files && files.length > 0) {
				anonymizationStore.addFiles(files)
			}
		},
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				anonymizationStore.addFiles(files)
			}
		},
		statusLabel(status) {
			const map = {
				queued: t('docudesk', 'Queued'),
				uploading: t('docudesk', 'Uploading'),
				extracting: t('docudesk', 'Extracting'),
				extracted: t('docudesk', 'Awaiting review'),
				anonymising: t('docudesk', 'Anonymising'),
				completed: t('docudesk', 'Completed'),
				error: t('docudesk', 'Error'),
			}
			return map[status] || status
		},
		isActiveStatus(status) {
			return status === 'queued' || status === 'uploading' || status === 'extracting' || status === 'anonymising'
		},
		downloadUrl(fileId) {
			return generateUrl(`/f/${fileId}`)
		},
		formatConfidence(c) {
			if (typeof c !== 'number') return '-'
			return (c * 100).toFixed(0) + '%'
		},
		truncate(text, max) {
			if (!text) return ''
			return text.length > max ? text.slice(0, max - 1) + '…' : text
		},
	},
}
</script>

<style scoped>
.anonymization-content {
	padding: 20px;
	max-width: 1200px;
	margin: 0 auto;
}

.pageHeader {
	margin: 0 0 8px;
}

.page-description {
	color: var(--color-text-maxcontrast);
	margin: 0 0 24px;
}

.drop-zone {
	border: 2px dashed var(--color-border);
	border-radius: 8px;
	padding: 32px;
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	transition: background-color 120ms ease, border-color 120ms ease;
	background-color: var(--color-main-background);
}

.drop-zone.dragging {
	border-color: var(--color-primary-element);
	background-color: var(--color-primary-element-light);
}

.drop-zone.busy {
	opacity: 0.85;
}

.drop-text {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.file-input {
	display: none;
}

.queue {
	margin-top: 24px;
}

.queue-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}

.queue-header h3 {
	flex: 1;
	margin: 0;
}

.file-card {
	border: 1px solid var(--color-border);
	border-radius: 8px;
	padding: 16px;
	margin-bottom: 16px;
	background-color: var(--color-main-background);
}

.file-card-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.file-name {
	font-weight: 600;
	flex: 1;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.file-loading {
	display: flex;
	align-items: center;
	gap: 8px;
	color: var(--color-text-maxcontrast);
	padding: 12px 0;
}

.review-section {
	margin-top: 12px;
}

.review-table {
	width: 100%;
	border-collapse: collapse;
}

.review-table th,
.review-table td {
	padding: 8px 12px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
	vertical-align: middle;
}

.review-table th {
	font-weight: 600;
	color: var(--color-text-maxcontrast);
}

.review-table th:nth-child(3),
.review-table td.numeric {
	text-align: right;
	width: 90px;
}

.review-table .bases-cell {
	min-width: 280px;
}

.entity-cell {
	max-width: 280px;
}

.warn-text {
	display: block;
	color: var(--color-warning);
	font-size: 12px;
	margin-top: 4px;
}

.error-text {
	display: block;
	color: var(--color-error);
	font-size: 12px;
}

.review-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 12px;
}

.completed-section {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 0;
}

.download-link {
	display: inline-flex;
	align-items: center;
	gap: 6px;
}

.error-section {
	padding: 8px 0;
}

.muted {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}
</style>
