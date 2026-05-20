<script setup>
import { translate as t } from '@nextcloud/l10n'
import { anonymizationPocStore } from '../../store/store.js'
</script>

<template>
	<div class="anonymization-content">
		<h2 class="pageHeader">
			{{ t('docudesk', 'Anonymisation PoC') }}
		</h2>
		<p class="page-description">
			{{ t('docudesk', 'Proof-of-concept surface for the manual-entity anonymisation flow. Upload a file, review the detected entities, and try the new "Add manual entity" action — it sends operator-supplied text to OpenRegister\'s chunk-aware matcher and appends the result to the review table. Not a production publication-prep page; the frontend team uses this as a working reference (request/response shapes, error mapping, idempotency UX) for the production version.') }}
		</p>

		<NcNoteCard v-if="anonymizationPocStore.basesError" type="warning">
			{{ t('docudesk', 'Grondslagen catalogue could not be loaded — falling back to the seeded list.') }}
			<span class="muted">({{ anonymizationPocStore.basesError }})</span>
		</NcNoteCard>

		<!-- Drop zone -->
		<div
			class="drop-zone"
			:class="{ dragging: isDragging, busy: anonymizationPocStore.isProcessing }"
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
		<div v-if="anonymizationPocStore.hasFiles" class="queue">
			<div class="queue-header">
				<h3>{{ t('docudesk', 'Queue') }} ({{ anonymizationPocStore.files.length }})</h3>
				<NcButton
					v-if="anonymizationPocStore.hasExtracted"
					type="primary"
					:disabled="anonymizationPocStore.isProcessing"
					@click="anonymizationPocStore.anonymiseAllExtracted()">
					{{ t('docudesk', 'Anonymise all reviewed files') }}
				</NcButton>
				<NcButton
					v-if="anonymizationPocStore.hasCompleted"
					type="tertiary"
					:disabled="anonymizationPocStore.isProcessing"
					@click="anonymizationPocStore.clearCompleted()">
					{{ t('docudesk', 'Clear completed') }}
				</NcButton>
				<NcButton
					type="tertiary"
					:disabled="anonymizationPocStore.isProcessing"
					@click="anonymizationPocStore.reset()">
					{{ t('docudesk', 'Reset') }}
				</NcButton>
			</div>

			<div v-for="entry in anonymizationPocStore.files" :key="entry.id" class="file-card">
				<div class="file-card-header">
					<FileDocument :size="20" />
					<span class="file-name" :title="entry.name">{{ entry.name }}</span>
					<CnStatusBadge
						:label="statusLabel(entry.status)"
						:color-map="statusBadgeColorMap(entry.status)" />
					<span v-if="entry.entityCount" class="muted">
						{{ entry.entityCount }} {{ t('docudesk', 'entities') }}
					</span>
				</div>

				<!-- Loading state -->
				<div v-if="isActiveStatus(entry.status)" class="file-loading">
					<NcLoadingIcon :size="20" />
					<span>{{ statusLabel(entry.status) }}…</span>
				</div>

				<!-- Manual-entity action results (one banner per call) -->
				<div v-if="entry.manualEntityNotices && entry.manualEntityNotices.length" class="manual-entity-notices">
					<NcNoteCard
						v-for="(notice, ni) in entry.manualEntityNotices"
						:key="`mn-${ni}`"
						:type="manualNoticeType(notice)">
						<strong>{{ noticePrefix(notice) }}</strong>
						{{ noticeBody(notice) }}
					</NcNoteCard>
				</div>

				<!-- Review state -->
				<div v-if="entry.status === 'extracted'" class="review-section">
					<!-- Fix #3: hoist the no-relation-id condition so the operator
					     sees it as a blocking notice, not a per-row whisper. -->
					<NcNoteCard v-if="rowsMissingRelationId(entry).length > 0" type="warning">
						<strong>
							{{ t('docudesk', '{n} detected entities have no relation id.', { n: rowsMissingRelationId(entry).length }) }}
						</strong>
						{{ t('docudesk', 'Their grondslagen / skip decisions will not persist on anonymise. Re-extract the file or report this as a bug to OpenRegister.') }}
					</NcNoteCard>

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
							<tr v-for="(entity, idx) in entry.entities" :key="entity.relationId || `noid-${idx}`">
								<td class="entity-cell">
									<span :title="entity.value">{{ truncate(entity.value, 60) }}</span>
									<span v-if="entity._patchError" class="error-text" :title="entity._patchError">
										{{ truncate(entity._patchError, 80) }}
									</span>
								</td>
								<td>
									<CnStatusBadge
										:label="entity.type"
										:color-map="entityTypeBadgeColorMap(entity.type)" />
								</td>
								<td class="numeric">
									{{ formatConfidence(entity.confidence) }}
								</td>
								<td class="bases-cell">
									<NcSelect
										v-model="entity._decisionBases"
										:options="anonymizationPocStore.basesOptions"
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
							type="secondary"
							:disabled="anonymizationPocStore.isProcessing"
							@click="openManualEntityModal(entry)">
							<template #icon>
								<PlusCircleOutline :size="20" />
							</template>
							{{ t('docudesk', 'Add manual entity') }}
						</NcButton>
						<NcButton
							type="primary"
							:disabled="anonymizationPocStore.isProcessing"
							@click="anonymizationPocStore.anonymiseEntry(entry)">
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

		<AddManualEntityModal
			:open.sync="manualEntityModalOpen"
			:entry="manualEntityModalEntry" />
	</div>
</template>

<script>
import { NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcNoteCard, NcSelect } from '@nextcloud/vue'
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { generateUrl } from '@nextcloud/router'
import Upload from 'vue-material-design-icons/Upload.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FileDocument from 'vue-material-design-icons/FileDocument.vue'
import PlusCircleOutline from 'vue-material-design-icons/PlusCircleOutline.vue'

import AddManualEntityModal from './AddManualEntityModal.vue'

// Fix #2: status-enum keys instead of translated strings. The badge
// component reads the colour using the raw status as the lookup key,
// so the colour stays stable across language switches.
const STATUS_COLOR_MAP = {
	queued: 'default',
	uploading: 'primary',
	extracting: 'primary',
	extracted: 'warning',
	anonymising: 'warning',
	completed: 'success',
	error: 'error',
}

const ENTITY_TYPE_COLOR_MAP = {
	PERSON: 'warning',
	ORGANIZATION: 'primary',
	EMAIL: 'primary',
	PHONE_NUMBER: 'primary',
	IBAN_CODE: 'primary',
	IP_ADDRESS: 'primary',
	LOCATION: 'primary',
	OTHER: 'default',
}

export default {
	name: 'AnonymizationPocWidget',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		CnStatusBadge,
		Upload,
		Download,
		FileDocument,
		PlusCircleOutline,
		AddManualEntityModal,
	},
	data() {
		return {
			isDragging: false,
			manualEntityModalOpen: false,
			manualEntityModalEntry: null,
		}
	},
	mounted() {
		// Trigger the lazy-load now so the bases dropdown is populated by the
		// time the operator hits the review table — no perceptible flicker.
		anonymizationPocStore.ensureBases()
	},
	methods: {
		openPicker() {
			this.$refs.fileInput.value = ''
			this.$refs.fileInput.click()
		},
		handleFileSelect(event) {
			const files = event.target.files
			if (files && files.length > 0) {
				anonymizationPocStore.addFiles(files)
			}
		},
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				anonymizationPocStore.addFiles(files)
			}
		},
		openManualEntityModal(entry) {
			this.manualEntityModalEntry = entry
			this.manualEntityModalOpen = true
		},
		statusLabel(status) {
			// Localised label — separate from the colour-map lookup key
			// (Fix #2: don't conflate display string with state identifier).
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
		/**
		 * CnStatusBadge looks up colour by `label`, so we build a
		 * per-call colour map keyed on the localised label. Centralised
		 * here so the colour intent lives next to the enum-keyed source.
		 *
		 * @param {string} status Raw status enum.
		 * @return {object}
		 */
		statusBadgeColorMap(status) {
			return { [this.statusLabel(status)]: STATUS_COLOR_MAP[status] || 'default' }
		},
		entityTypeBadgeColorMap(type) {
			return { [type]: ENTITY_TYPE_COLOR_MAP[type] || 'default' }
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
		rowsMissingRelationId(entry) {
			return entry.entities.filter((e) => e.relationId == null)
		},
		/**
		 * NoteCard `type` for a manual-entity outcome banner. Zero-match
		 * is `info` (not an error — operator may intentionally add a
		 * value that's only present in other files); matches are
		 * `success`; idempotent re-submits (all skipped) are still
		 * success because the operator's intent IS recorded server-side.
		 *
		 * @param {object} notice {matchCount, matchesSkipped, zeroMatch, reused, message}
		 * @return {string}
		 */
		manualNoticeType(notice) {
			if (notice.zeroMatch) return 'info'
			return 'success'
		},
		noticePrefix(notice) {
			if (notice.zeroMatch) {
				return t('docudesk', 'Catalogue entry {state} — no matches in file.', {
					state: notice.reused ? t('docudesk', 'reused') : t('docudesk', 'created'),
				})
			}
			if (notice.matchCount === notice.matchesSkipped && notice.matchesSkipped > 0) {
				return t('docudesk', 'Already on the list — no new occurrences added.')
			}
			const insertedCount = (notice.matchCount || 0) - (notice.matchesSkipped || 0)
			return t('docudesk', '{n} occurrences added to the anonymisation list.', { n: insertedCount })
		},
		noticeBody(notice) {
			const parts = [t('docudesk', 'value: {v}, type: {t}', { v: notice.value, t: notice.type })]
			if (notice.message) {
				parts.push(notice.message)
			}
			if (!notice.zeroMatch && notice.matchesSkipped > 0) {
				parts.push(t('docudesk', '{n} already-existing relations skipped (idempotent).', { n: notice.matchesSkipped }))
			}
			return parts.join(' · ')
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

.manual-entity-notices {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin: 12px 0;
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
