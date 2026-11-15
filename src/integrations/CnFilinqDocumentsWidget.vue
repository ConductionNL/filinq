<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
  -->
<template>
	<div v-if="!summarised" class="filinq-documents-leaf">
		<p v-if="loading" class="filinq-documents-leaf__state">
			{{ t('filinq', 'Looking up documents') }}
		</p>
		<p
			v-else-if="error"
			class="filinq-documents-leaf__state filinq-documents-leaf__state--error">
			{{ t('filinq', 'The documents could not be loaded') }}
		</p>
		<p
			v-else-if="rows.length === 0 && !carded"
			class="filinq-documents-leaf__state">
			{{ t('filinq', 'No documents yet') }}
		</p>
		<div
			v-if="!loading && !error && rows.length > 1"
			class="filinq-documents-leaf__sort"
			role="group"
			:aria-label="t('filinq', 'Sort documents')">
			<button
				v-for="column in sortColumns"
				:key="column.key"
				type="button"
				class="filinq-documents-leaf__sort-button"
				:aria-pressed="sort.key === column.key ? 'true' : 'false'"
				:data-testid="'documents-sort-' + column.key"
				@click="sortOn(column.key)">
				{{ column.label }}
				<span v-if="sort.key === column.key" aria-hidden="true">{{ sort.direction === 'asc' ? '↑' : '↓' }}</span>
				<span v-if="sort.key === column.key" class="hidden-visually">{{ sort.direction === 'asc' ? t('filinq', 'ascending') : t('filinq', 'descending') }}</span>
			</button>
		</div>
		<ul v-if="!loading && !error && rows.length > 0" class="filinq-documents-leaf__list">
			<li
				v-for="row in sortedRows"
				:key="row.fileId"
				class="filinq-documents-leaf__item">
				<a class="filinq-documents-leaf__link" :href="fileLink(row)">{{
					row.name
				}}</a>
				<span v-if="row.mtime" class="filinq-documents-leaf__date">{{ asDate(row.mtime) }}</span>
				<span
					v-if="row.record && row.record.status"
					class="filinq-documents-leaf__status">
					{{ row.record.status }}
				</span>
				<span v-if="row.record && row.record.uuid" class="filinq-documents-leaf__actions">
					<button
						type="button"
						class="filinq-documents-leaf__sort-button"
						:aria-expanded="linking === row.record.uuid && linkMode === 'link' ? 'true' : 'false'"
						:data-testid="'documents-link-' + row.fileId"
						@click="toggleLinkForm(row)">
						{{ t('filinq', 'Link to another record') }}
					</button>
					<button
						type="button"
						class="filinq-documents-leaf__sort-button"
						:aria-expanded="linking === row.record.uuid && linkMode === 'copy' ? 'true' : 'false'"
						:data-testid="'documents-copy-' + row.fileId"
						@click="toggleLinkForm(row, 'copy')">
						{{ t('filinq', 'Copy to another record') }}
					</button>
					<button
						type="button"
						class="filinq-documents-leaf__sort-button"
						:disabled="busy"
						:data-testid="'documents-unlink-' + row.fileId"
						@click="removeLink(row)">
						{{ t('filinq', 'Remove link') }}
					</button>
				</span>
				<form
					v-if="row.record && linking === row.record.uuid"
					class="filinq-documents-leaf__link-form"
					@submit.prevent="linkMode === 'copy' ? copyToOtherRecord(row) : linkToRecord(row)">
					<label>
						{{ t('filinq', 'Register') }}
						<input v-model="linkForm.register" type="text" required>
					</label>
					<label>
						{{ t('filinq', 'Record type') }}
						<input v-model="linkForm.schema" type="text" required>
					</label>
					<label>
						{{ t('filinq', 'Record ID') }}
						<input v-model="linkForm.id" type="text" required>
					</label>
					<button type="submit" class="filinq-documents-leaf__sort-button" :disabled="busy">
						{{ linkMode === 'copy' ? t('filinq', 'Copy') : t('filinq', 'Link') }}
					</button>
				</form>
			</li>
		</ul>
		<p v-if="linkMessage" class="filinq-documents-leaf__state" role="status">
			{{ linkMessage }}
		</p>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { changeDomainLink, copyToRecord, linkTarget } from './documentDomainLinks.js'
import { documentLinkRows } from './documentLinkRows.js'
import { nextSort, sortDocumentRows } from './documentRowSort.js'
import { hostObjectLeaf } from './hostObjectLeaf.js'
import { leafReportMixin } from './leafReport.js'

/**
 * The documents Filinq holds for one object.
 *
 * Reads Filinq's own flat case-document list, which already takes exactly the
 * object context a leaf is handed and already applies Filinq's access control:
 * a record whose file the reader cannot see is left out by the server, so this
 * component neither filters nor hides anything itself.
 */
export default {
	name: 'CnFilinqDocumentsWidget',

	extends: hostObjectLeaf,
	mixins: [leafReportMixin],

	data() {
		return {
			rows: [],
			loading: true,
			error: false,
			sort: { key: 'name', direction: 'asc' },
			linking: '',
			linkMode: 'link',
			linkForm: { register: '', schema: '', id: '' },
			linkMessage: '',
			busy: false,
		}
	},

	computed: {
		/**
		 * The rows in the order the user chose; it lasts while the case stays open.
		 *
		 * @return {Array<object>} The sorted rows.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.1
		 */
		sortedRows() {
			return sortDocumentRows(this.rows, this.sort)
		},

		/**
		 * The columns to sort on, with their labels.
		 *
		 * @return {Array<{key: string, label: string}>} The columns.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.1
		 */
		sortColumns() {
			return [
				{ key: 'name', label: t('filinq', 'Name') },
				{ key: 'mtime', label: t('filinq', 'Date') },
				{ key: 'size', label: t('filinq', 'Size') },
				{ key: 'status', label: t('filinq', 'Status') },
			]
		},

		/**
		 * What this leaf reports to a carding host or a "linked" card.
		 *
		 * @return {object} The report.
		 * @spec openspec/specs/leaves-library-card/spec.md#requirement-the-documents-leaf-reports-to-the-library-card-and-the-linked-card
		 */
		leafReport() {
			return {
				count: this.rows.length,
				emptyLabel: t('filinq', 'No documents yet'),
				noun: t('filinq', 'documents'),
				rows: documentLinkRows(this.rows),
			}
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		/**
		 * Sort on a column; choosing it again flips the order.
		 *
		 * @param {string} key The column.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.1
		 */
		sortOn(key) {
			this.sort = nextSort(this.sort, key)
		},

		/**
		 * A modification time as a local date.
		 *
		 * @param {number} mtime Seconds since the epoch.
		 * @return {string} The date.
		 */
		asDate(mtime) {
			return new Date(mtime * 1000).toLocaleDateString()
		},

		/**
		 * The Files deep link for one row.
		 *
		 * @param {object} row One flat-list row.
		 * @return {string} A link to the file in Files.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		fileLink(row) {
			return generateUrl('/f/{fileId}', { fileId: row.fileId })
		},

		/**
		 * Open or close the link or copy form of one document.
		 *
		 * @param {object} row One flat-list row.
		 * @param {'link'|'copy'} [mode] Link the document, or copy it.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
		 */
		toggleLinkForm(row, mode = 'link') {
			this.linkMessage = ''
			const open = this.linking === row.record.uuid && this.linkMode === mode
			this.linking = open ? '' : row.record.uuid
			this.linkMode = mode
			this.linkForm = { register: '', schema: '', id: '' }
		},

		/**
		 * Copy a document to the record the form names; it stays on this
		 * record too. The server refuses a record the user may not write,
		 * and nothing is written then.
		 *
		 * @param {object} row One flat-list row.
		 * @return {Promise<void>} Resolves once the answer is shown.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-6.1
		 */
		async copyToOtherRecord(row) {
			const target = linkTarget(this.linkForm)
			if (target === null) {
				this.linkMessage = t('filinq', 'Name the register, the record type and the ID of the record.')
				return
			}
			this.busy = true
			const answer = await copyToRecord(
				row.fileId,
				{ register: this.register, schema: this.schema, id: this.objectId },
				target,
				this.requestToken(),
			)
			this.busy = false
			if (answer.ok) {
				this.linking = ''
				this.linkMessage = t('filinq', 'A copy of {name} is now on the other record.', { name: row.name })
				return
			}
			this.linkMessage = answer.status === 403
				? t('filinq', 'You may not copy this document to that record.')
				: t('filinq', 'The document could not be copied.')
		},

		/**
		 * Link a document to the record the form names. The server refuses a
		 * record the user may not write, and nothing is stored then.
		 *
		 * @param {object} row One flat-list row.
		 * @return {Promise<void>} Resolves once the answer is shown.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
		 */
		async linkToRecord(row) {
			const target = linkTarget(this.linkForm)
			if (target === null) {
				this.linkMessage = t('filinq', 'Name the register, the record type and the ID of the record.')
				return
			}
			this.busy = true
			const answer = await changeDomainLink('POST', row.record.uuid, target, this.requestToken())
			this.busy = false
			if (answer.ok) {
				this.linking = ''
				this.linkMessage = t('filinq', '{name} is now also on the other record.', { name: row.name })
				return
			}
			this.linkMessage = this.refusal(answer)
		},

		/**
		 * Remove a document's link to this record; it leaves this list and
		 * stays on its other records and with its creator.
		 *
		 * @param {object} row One flat-list row.
		 * @return {Promise<void>} Resolves once the list reflects the answer.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
		 */
		async removeLink(row) {
			this.busy = true
			const answer = await changeDomainLink(
				'DELETE',
				row.record.uuid,
				{ register: this.register, schema: this.schema, id: this.objectId },
				this.requestToken(),
			)
			this.busy = false
			if (answer.ok) {
				this.rows = this.rows.filter((one) => one.fileId !== row.fileId)
				this.linkMessage = t('filinq', '{name} is no longer linked to this record.', { name: row.name })
				return
			}
			this.linkMessage = this.refusal(answer)
		},

		/**
		 * The sentence for a link or unlink the server did not do.
		 *
		 * @param {{status: number, error: string}} answer The answer.
		 * @return {string} The sentence.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.5
		 */
		refusal(answer) {
			if (answer.status === 403) {
				return t('filinq', 'You may not change the documents of that record.')
			}
			return t('filinq', 'The link could not be changed.')
		},

		/**
		 * Read the flat list for the host object.
		 *
		 * @return {Promise<void>} Resolves once `rows` reflects the answer.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		load() {
			return this.loadHostRows('/apps/filinq/api/case-documents/files')
		},
	},
}
</script>

<style scoped>
.filinq-documents-leaf__list {
	list-style: none;
	margin: 0;
	padding: 0;
}

.filinq-documents-leaf__item {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	justify-content: space-between;
	gap: 8px;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.filinq-documents-leaf__sort {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	margin-bottom: 4px;
}

.filinq-documents-leaf__sort-button {
	background: var(--color-main-background);
	color: var(--color-main-text);
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius);
	padding: 2px 8px;
	cursor: pointer;
}

.filinq-documents-leaf__sort-button[aria-pressed='true'] {
	border-color: var(--color-primary-element);
}

.filinq-documents-leaf__actions {
	display: flex;
	gap: 4px;
}

.filinq-documents-leaf__link-form {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	gap: 8px;
	flex-basis: 100%;
}

.filinq-documents-leaf__link-form label {
	display: flex;
	flex-direction: column;
	font-size: 0.9em;
}

.filinq-documents-leaf__date,
.filinq-documents-leaf__status {
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
}

.filinq-documents-leaf__state {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.filinq-documents-leaf__state--error {
	color: var(--color-error);
}
</style>
