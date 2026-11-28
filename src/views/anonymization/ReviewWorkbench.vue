<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="review-workbench" data-testid="review-workbench">
		<header class="review-workbench__header">
			<h2 class="review-workbench__title">
				{{ t('filinq', 'Review workbench') }}
				<span class="review-workbench__file">{{ original.fileName }}</span>
			</h2>
			<nav class="review-workbench__rules" :aria-label="t('filinq', 'Rule lists')">
				<router-link :to="{ name: 'Prohibitions' }">
					{{ t('filinq', 'Always anonymise (publish-never rules)') }}
				</router-link>
				<router-link :to="{ name: 'StandingConsents' }">
					{{ t('filinq', 'Never anonymise (standing consents)') }}
				</router-link>
			</nav>
		</header>

		<section
			class="review-workbench__status"
			:class="'review-workbench__status--' + state"
			data-testid="review-status"
			aria-live="polite">
			<p class="review-workbench__state">
				{{ stateText }}
			</p>
			<p v-if="review && review.review" class="review-workbench__by">
				{{ t('filinq', 'Last marked by {user} on {date}', { user: review.review.checkedBy, date: checkedOnText }) }}
			</p>
			<p v-if="rendition" class="review-workbench__rendition" data-testid="review-rendition">
				{{ renditionText }}
			</p>
			<template v-if="noTextLayer && state !== 'reviewed'">
				<p class="review-workbench__unreadable" role="status" data-testid="no-text-layer">
					{{ t('filinq', 'Detection could not read the pages of this document, so it found nothing on them. Read every page yourself.') }}
				</p>
				<NcCheckboxRadioSwitch v-model="byEye" data-testid="by-eye">
					{{ t('filinq', 'I read every page of this document by eye') }}
				</NcCheckboxRadioSwitch>
			</template>
			<NcButton
				v-if="state !== 'reviewed'"
				variant="primary"
				:disabled="busy || !entry || (entry.status !== 'extracted' && !noTextLayer) || (noTextLayer && !byEye)"
				data-testid="mark-reviewed"
				@click="markReviewed">
				{{ state === 'stale' ? t('filinq', 'Review again and mark as reviewed') : t('filinq', 'Mark as reviewed') }}
			</NcButton>
			<NcButton
				v-else
				variant="secondary"
				:disabled="busy"
				data-testid="unmark-reviewed"
				@click="unmarkReviewed">
				{{ t('filinq', 'Remove reviewed mark') }}
			</NcButton>
			<p v-if="error" class="review-workbench__error" role="alert">
				{{ error }}
			</p>
		</section>

		<div class="review-workbench__body">
			<div
				ref="compare"
				class="review-workbench__compare"
				@mouseup="captureSelection"
				@keyup="captureSelection">
				<DocumentCompare
					:original="originalPane"
					:delivered="anonymized"
					:labels="{ original: t('filinq', 'Original'), delivered: t('filinq', 'Anonymised') }"
					:pendingText="t('filinq', 'Anonymisation has not been committed for this document yet.')" />
			</div>

			<aside class="review-workbench__panel" :aria-label="t('filinq', 'Findings')">
				<NcButton
					variant="secondary"
					:disabled="!selectedText || !entry"
					data-testid="add-selection"
					@click="addOpen = true">
					{{ t('filinq', 'Add selection as entity') }}
				</NcButton>
				<p class="review-workbench__legend">
					{{ t('filinq', 'Rules pre-apply: an always-anonymise rule pre-selects a finding, a never-anonymise rule pre-excludes it. Your choice overrides both.') }}
				</p>
				<p v-if="loading" class="review-workbench__loading">
					{{ t('filinq', 'Loading findings…') }}
				</p>
				<EntityReviewTable
					v-else-if="entry"
					:entities="entry.entities || []"
					:fileCount="1"
					:thresholds="thresholds"
					:proposalMapping="proposalMapping"
					@toggle="onToggle"
					@bulkSelect="onBulk($event, true)"
					@bulkDeselect="onBulk($event, false)"
					@basesChange="scheduleRefresh"
					@skipChange="scheduleRefresh" />
			</aside>
		</div>

		<AddManualEntityModal
			:open="addOpen"
			:selectedText="selectedText"
			:basesOptions="basesOptions"
			:proposalMapping="proposalMapping"
			:save="saveManualEntity"
			@added="refreshReview"
			@update:open="addOpen = $event" />
	</div>
</template>

<script>
/**
 * The review workbench for one document: the original beside the anonymised
 * result, the findings table as the one decision panel, text selection to a
 * manual entity, and the per-document "reviewed" mark the commit gate reads.
 *
 * Every decision goes through the anonymisation store's entry, the same model
 * the file viewer's sidebar uses, so there is one entity state only.
 */
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import DocumentCompare from '../../components/compare/DocumentCompare.vue'
import AddManualEntityModal from '../../dialogs/AddManualEntityModal.vue'
import EntityReviewTable from './EntityReviewTable.vue'
import { fetchBaseOptions } from '../../services/bases.js'
import {
	anonymizedFileOf,
	checkReview,
	decidedUncertainRelationIds,
	fetchAnonymizationLink,
	fetchReview,
	findingValues,
	renditionPane,
	reviewState,
	selectedTextIn,
	uncheckReview,
} from '../../services/documentReview.js'
import { anonymizationStore } from '../../store/store.js'

/**
 * How long after a decision the review state is read back, so the decision's
 * own write has landed first.
 */
const REFRESH_DELAY_MS = 800

export default {
	name: 'ReviewWorkbench',

	components: { AddManualEntityModal, DocumentCompare, EntityReviewTable, NcButton, NcCheckboxRadioSwitch },

	data() {
		return {
			entry: null,
			link: null,
			review: null,
			loading: true,
			busy: false,
			error: '',
			selectedText: '',
			addOpen: false,
			basesOptions: [],
			proposalMapping: {},
			refreshTimer: null,
			byEye: false,
		}
	},

	computed: {
		/**
		 * The document under review.
		 *
		 * @return {number}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
		 */
		fileId() {
			return Number(this.$route?.params?.fileId)
		},

		/**
		 * The original's pane: name, type and path from the link that opened the workbench.
		 *
		 * @return {{fileId: number, fileName: string, mimeType: string, path: string}}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
		 */
		original() {
			const query = this.$route?.query || {}
			return {
				fileId: this.fileId,
				fileName: String(query.name || this.link?.sourceFileName || this.entry?.name || ''),
				mimeType: String(query.mime || ''),
				path: String(query.path || this.link?.sourceFilePath || this.entry?.filePath || ''),
			}
		},

		/**
		 * The review rendition the server prepared, or null.
		 *
		 * @return {object|null}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
		 */
		rendition() {
			return this.review?.rendition || null
		},

		/**
		 * Whether detection could not read the rendition's pages.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.2
		 */
		noTextLayer() {
			return this.rendition?.textLayer === 'none'
		},

		/**
		 * The detection floor and certain-from, from the review status.
		 *
		 * @return {object|null}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		thresholds() {
			return this.review?.thresholds || null
		},

		/**
		 * The original's pane as reviewed: the converted rendition when there is one.
		 *
		 * @return {object}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
		 */
		originalPane() {
			return renditionPane(this.original, this.rendition)
		},

		/**
		 * When the reviewed mark was set, as a local date and time; the stored ISO moment is not for people.
		 *
		 * @return {string}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.5
		 */
		checkedOnText() {
			const iso = this.review?.review?.checkedOn || ''
			const date = new Date(iso)
			if (iso === '' || Number.isNaN(date.getTime())) {
				return iso
			}
			return date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
		},

		/**
		 * What the rendition line says: how the reviewed PDF was made and its text layer.
		 *
		 * @return {string}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.1
		 */
		renditionText() {
			const r = this.rendition || {}
			switch (r.kind) {
			case 'converted':
				return t('filinq', 'Reviewed as a PDF converted by {backend}: {name}', { backend: r.backend || '?', name: r.fileName || '' })
			case 'ocr-text-layer':
				return r.textLayer === 'ocr'
					? t('filinq', 'Reviewed as a scan with a text layer recognised by OCR ({backend})', { backend: r.backend || '?' })
					: t('filinq', 'Reviewed as a scan without a text layer')
			default:
				return t('filinq', 'Reviewed as the PDF itself, with its own text layer')
			}
		},

		/**
		 * The anonymised pane's file, or null while nothing was committed.
		 *
		 * @return {object|null}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
		 */
		anonymized() {
			return anonymizedFileOf(this.link)
		},

		/**
		 * `reviewed`, `stale`, `unchecked` or `unknown`.
		 *
		 * @return {string}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
		 */
		state() {
			return reviewState(this.review)
		},

		/**
		 * What the status card says.
		 *
		 * @return {string}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
		 */
		stateText() {
			switch (this.state) {
			case 'reviewed':
				return t('filinq', 'This document is marked as reviewed.')
			case 'stale':
				return t('filinq', 'The findings changed after the last review. Review the document again before it is anonymised.')
			case 'unchecked':
				return t('filinq', 'This document has not been marked as reviewed yet.')
			default:
				return t('filinq', 'The review state could not be read.')
			}
		},
	},

	async mounted() {
		await this.load()
	},

	beforeUnmount() {
		clearTimeout(this.refreshTimer)
	},

	methods: {
		t,

		/**
		 * Load the findings, the anonymised result, the review state and the pickers' options.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
		 */
		async load() {
			this.loading = true
			try {
				const [link, entry, bases, mapping] = await Promise.all([
					fetchAnonymizationLink(this.fileId),
					anonymizationStore.ensureExtracted(this.original),
					fetchBaseOptions(),
					this.fetchProposalMapping(),
				])
				this.link = link
				this.entry = entry
				this.basesOptions = bases
				this.proposalMapping = mapping
			} finally {
				this.loading = false
			}
			await this.refreshReview()
		},

		/**
		 * The admin's entity type → grondslag mapping; empty when unreadable.
		 *
		 * @return {Promise<object>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		async fetchProposalMapping() {
			try {
				const response = await axios.get(generateUrl('/apps/filinq/api/settings'))
				return response.data?.['filinq.grondslagen.entity_type_bases'] || {}
			} catch {
				return {}
			}
		},

		/**
		 * Read the review state back from the server, which decides whether the mark holds.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
		 */
		async refreshReview() {
			try {
				this.review = await fetchReview(this.fileId)
			} catch (err) {
				this.review = null
				this.error = err?.response?.data?.error || err?.message || ''
			}
		},

		/**
		 * Read the review state back once the decision just made has been written.
		 *
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
		 */
		scheduleRefresh() {
			clearTimeout(this.refreshTimer)
			this.refreshTimer = setTimeout(() => this.refreshReview(), REFRESH_DELAY_MS)
		},

		/**
		 * Include or exclude one finding, through the shared store.
		 *
		 * @param {number} idx The finding's index in the entry.
		 * @return {Promise<void>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
		 */
		async onToggle(idx) {
			await anonymizationStore.toggleEntity(this.entry, idx)
			await this.refreshReview()
		},

		/**
		 * Include or exclude the visible findings.
		 *
		 * @param {Array<number>} idxList The visible findings' indices.
		 * @param {boolean} included The new state.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.1
		 */
		onBulk(idxList, included) {
			anonymizationStore.setVisibleEntities(this.entry, idxList, included)
			this.scheduleRefresh()
		},

		/**
		 * Keep the text selected inside the original pane for "Add selection as entity".
		 *
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		captureSelection() {
			const pane = this.$refs.compare?.querySelector?.('[data-testid="document-compare-original"]') || null
			const text = selectedTextIn(window.getSelection?.() || null, pane)
			if (text !== '' || !this.addOpen) {
				this.selectedText = text
			}
		},

		/**
		 * Save a manual entity through the store; resolve to how many occurrences were found.
		 *
		 * A selection found nowhere adds no row.
		 *
		 * @param {{value: string, type: string, bases: Array<string>}} payload The entity.
		 * @return {Promise<number>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		async saveManualEntity(payload) {
			const data = await anonymizationStore.addManualEntity(this.entry, payload)
			const found = Number(data?.matchCount ?? (Array.isArray(data?.relations) ? data.relations.length : 0))
			if (found === 0 && Array.isArray(this.entry.entities)) {
				this.entry.entities = this.entry.entities.slice(1)
				this.entry.entityCount = this.entry.entities.length
			}
			return found
		},

		/**
		 * Mark the document reviewed: the workbench's mark, and the run's review mark
		 * the anonymise call also asks for.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
		 */
		async markReviewed() {
			this.busy = true
			this.error = ''
			try {
				this.review = await checkReview(this.fileId, '', {
					byEye: this.noTextLayer && this.byEye,
					decided: decidedUncertainRelationIds(this.entry?.entities, this.thresholds?.certainFrom),
				})
				await anonymizationStore.markEntryChecked(this.entry)
				if (this.entry?.reviewError) {
					this.error = this.entry.reviewError
				}
			} catch (err) {
				this.error = this.refusalText(err)
			} finally {
				this.busy = false
			}
		},

		/**
		 * The message of a refused check; an undecided uncertain finding is named.
		 *
		 * @param {Error} err The failure.
		 * @return {string}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
		 */
		refusalText(err) {
			const data = err?.response?.data || {}
			const message = data.error || err?.message || ''
			if (data.reason !== 'uncertain_undecided') {
				return message
			}
			const names = findingValues(this.entry?.entities, data.undecidedFindings)
			return message + ' ' + t('filinq', 'Undecided: {findings}', { findings: names.join(', ') })
		},

		/**
		 * Remove the reviewed mark.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.4
		 */
		async unmarkReviewed() {
			this.busy = true
			this.error = ''
			try {
				this.review = await uncheckReview(this.fileId)
			} catch (err) {
				this.error = err?.response?.data?.error || err?.message || ''
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.review-workbench {
	display: flex;
	flex-direction: column;
	gap: 12px;
	height: 100%;
	min-height: 0;
	padding: 12px;
}

.review-workbench__header {
	display: flex;
	flex-wrap: wrap;
	align-items: baseline;
	justify-content: space-between;
	gap: 8px;
	/* Clear the app-navigation toggle, which sits over the top-left corner of the content. */
	padding-inline-start: calc(var(--default-clickable-area, 34px) + 8px);
}

.review-workbench__title {
	margin: 0;
}

.review-workbench__file {
	margin-inline-start: 8px;
	font-weight: normal;
	color: var(--color-text-maxcontrast);
}

.review-workbench__rendition {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.review-workbench__unreadable {
	margin: 0;
	padding-inline-start: 8px;
	border-inline-start: 3px solid var(--color-warning);
}

.review-workbench__rules {
	display: flex;
	gap: 16px;
}

.review-workbench__rules a {
	color: var(--color-primary-element);
	text-decoration: underline;
}

.review-workbench__status {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 12px;
	padding: 8px 12px;
	border: 1px solid var(--color-border);
	border-inline-start-width: 4px;
	border-radius: var(--border-radius-large);
}

.review-workbench__status--reviewed {
	border-inline-start-color: var(--color-success);
}

.review-workbench__status--stale,
.review-workbench__status--unchecked {
	border-inline-start-color: var(--color-warning);
}

.review-workbench__state,
.review-workbench__by {
	margin: 0;
}

.review-workbench__by,
.review-workbench__legend,
.review-workbench__loading {
	color: var(--color-text-maxcontrast);
}

.review-workbench__error {
	margin: 0;
	color: var(--color-error-text);
}

.review-workbench__body {
	display: grid;
	grid-template-columns: minmax(0, 3fr) minmax(320px, 2fr);
	gap: 12px;
	flex: 1;
	min-height: 0;
}

@media (max-width: 1024px) {
	.review-workbench__body {
		grid-template-columns: 1fr;
	}
}

.review-workbench__compare {
	min-height: 60vh;
}

.review-workbench__panel {
	display: flex;
	flex-direction: column;
	gap: 8px;
	min-height: 0;
	/* A grid item keeps its content's width unless told otherwise: without
	   this the findings table widened the column past the page and slid under
	   the document panes. Now it scrolls inside its own pane. */
	min-width: 0;
	overflow: auto;
}

.review-workbench__legend {
	margin: 0;
	font-size: 0.9em;
}
</style>
