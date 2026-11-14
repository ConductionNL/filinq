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
			</li>
		</ul>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
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
