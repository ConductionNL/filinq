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
		<p v-else-if="rows.length === 0 && !carded" class="filinq-documents-leaf__state">
			{{ t('filinq', 'No documents yet') }}
		</p>
		<ul v-else-if="rows.length > 0" class="filinq-documents-leaf__list">
			<li
				v-for="row in rows"
				:key="row.fileId"
				class="filinq-documents-leaf__item">
				<a class="filinq-documents-leaf__link" :href="fileLink(row)">{{
					row.name
				}}</a>
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

	mixins: [leafReportMixin],
	props: {
		/** The host object's register slug. */
		register: { type: String, default: '' },
		/** The host object's schema slug. */
		schema: { type: String, default: '' },
		/** The host object's id. */
		objectId: { type: String, default: '' },
	},

	data() {
		return {
			rows: [],
			loading: true,
			error: false,
		}
	},

	computed: {
		/**
		 * What this leaf reports to a carding host or a "linked" card.
		 *
		 * @return {object} The report.
		 * @spec openspec/changes/leaves-library-card/specs/leaves-library-card/spec.md#requirement-the-documents-leaf-reports-to-the-library-card-and-the-linked-card
		 */
		leafReport() {
			return {
				count: this.rows.length,
				emptyLabel: t('filinq', 'No documents yet'),
				noun: t('filinq', 'documents'),
				rows: this.rows.map((row) => ({ label: row.name, href: this.fileLink(row), icon: 'FileDocumentOutline' })),
			}
		},
	},

	mounted() {
		this.load()
	},

	methods: {
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
		async load() {
			// Without an object there is nothing to list, and asking anyway
			// would return the caller's whole document set, which is not what
			// a per-object leaf means.
			if (this.objectId === '') {
				this.loading = false
				return
			}

			const query = new URLSearchParams({
				register: this.register,
				schema: this.schema,
				id: this.objectId,
			})

			try {
				const response = await fetch(
					generateUrl('/apps/filinq/api/case-documents/files')
						+ '?'
						+ query.toString(),
					{
						headers: {
							requesttoken: this.requestToken(),
							Accept: 'application/json',
						},
					},
				)
				if (response.ok === false) {
					this.error = true
					return
				}
				const body = await response.json()
				this.rows = Array.isArray(body.results) ? body.results : []
			} catch {
				this.error = true
			} finally {
				this.loading = false
			}
		},

		/**
		 * The current page's request token.
		 *
		 * Read from the DOM rather than from `@nextcloud/auth`, because this
		 * component renders inside ANOTHER app's page and must not assume the
		 * host bundled the same helper.
		 *
		 * @return {string} The token, or an empty string when there is none.
		 *
		 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
		 */
		requestToken() {
			const head = document.getElementsByTagName('head')[0]
			return (head && head.getAttribute('data-requesttoken')) || ''
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
