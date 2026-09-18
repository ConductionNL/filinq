<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
-->

<template>
	<div class="signing-folder">
		<h2>{{ t('filinq', 'Signing folder') }}</h2>
		<p class="signing-folder__lead">
			{{ t('filinq', 'Everything still waiting for your signature, from every case.') }}
		</p>

		<NcLoadingIcon v-if="signingStore.loading && entries.length === 0" :size="44" />

		<NcNoteCard v-if="signingStore.error" type="error">
			{{ signingStore.error }}
		</NcNoteCard>

		<NcEmptyContent
			v-else-if="entries.length === 0 && !signingStore.loading"
			:name="t('filinq', 'Nothing is waiting for your signature')"
			:description="t('filinq', 'Documents appear here as soon as somebody asks you to sign one.')" />

		<template v-else>
			<div class="signing-folder__actions">
				<NcButton
					variant="primary"
					:disabled="selected.length === 0 || signingStore.loading"
					@click="signSelection">
					{{ t('filinq', 'Sign selected') }} ({{ selected.length }})
				</NcButton>
				<span class="signing-folder__count">
					{{ t('filinq', '{shown} of {total} shown', { shown: entries.length, total: signingStore.folderTotal }) }}
				</span>
			</div>

			<table class="signing-folder__table">
				<thead>
					<tr>
						<!-- Selection column: a control header with no name of
						     its own, so scope= would associate nothing. -->
						<th />
						<th scope="col">{{ t('filinq', 'Document') }}</th>
						<th scope="col">{{ t('filinq', 'Case') }}</th>
						<th scope="col">{{ t('filinq', 'Asked by') }}</th>
						<th scope="col">{{ t('filinq', 'Asked on') }}</th>
						<th scope="col">{{ t('filinq', 'Deadline') }}</th>
						<th scope="col">{{ t('filinq', 'Level') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="entry in entries" :key="entry.requestId">
						<td>
							<input
								type="checkbox"
								:aria-label="t('filinq', 'Select {document}', { document: entry.documentName })"
								:checked="selected.includes(entry.requestId)"
								@change="toggle(entry.requestId)">
						</td>
						<td>
							<NcButton variant="tertiary" @click="readDocument(entry)">
								{{ entry.documentName }}
							</NcButton>
						</td>
						<td>{{ caseOf(entry) }}</td>
						<td>{{ entry.requestedBy }}</td>
						<td>{{ asDate(entry.requestedAt) }}</td>
						<td>{{ asDate(entry.deadline) }}</td>
						<td>{{ entry.signatureLevel }}</td>
					</tr>
				</tbody>
			</table>
		</template>

		<section v-if="results.length > 0" class="signing-folder__results">
			<h3>{{ t('filinq', 'What the last pass did') }}</h3>
			<ul>
				<li v-for="result in results" :key="result.requestId">
					<span v-if="result.signed">
						{{ t('filinq', 'Signed: {document}', { document: result.documentName || result.requestId }) }}
					</span>
					<span v-else>
						{{ t('filinq', 'Not signed: {reason}', { reason: result.reason }) }}
					</span>
				</li>
			</ul>
		</section>

		<SigningFolderDocumentModal
			v-if="reading"
			:show="true"
			:documentName="reading.documentName"
			:recordLabel="caseOf(reading)"
			:fileId="reading.documentFileId"
			@close="reading = null" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import SigningFolderDocumentModal from '../../modals/SigningFolderDocumentModal.vue'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningFolder',
	components: {
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		SigningFolderDocumentModal,
	},

	/**
	 * Read the folder on mount. It is a query, so every visit asks again and
	 * a request cancelled elsewhere is simply gone.
	 *
	 * @return {object} The store and the translator.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	setup() {
		const signingStore = useSigningStore()
		signingStore.fetchSigningFolder()
		return { signingStore, t }
	},

	data() {
		return {
			selected: [],
			results: [],
			reading: null,
		}
	},

	computed: {
		/**
		 * The entries of the folder page currently held.
		 *
		 * @return {Array} The folder entries.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		entries() {
			return this.signingStore.folderEntries
		},
	},

	methods: {
		/**
		 * Add or remove one document from the selection.
		 *
		 * @param {string} requestId The signing request.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		toggle(requestId) {
			if (this.selected.includes(requestId)) {
				this.selected = this.selected.filter((id) => id !== requestId)
				return
			}
			this.selected = [...this.selected, requestId]
		},

		/**
		 * Sign the selection in one pass and show what happened per document.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		async signSelection() {
			const outcome = await this.signingStore.signFolderSelection(this.selected)
			this.results = outcome?.results ?? []
			this.selected = []
			await this.signingStore.fetchSigningFolder()
		},

		/**
		 * Read one document without leaving the folder.
		 *
		 * @param {object} entry The folder entry.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		readDocument(entry) {
			this.reading = entry
		},

		/**
		 * The case an entry belongs to, as the consuming app labels it.
		 *
		 * @param {object} entry The folder entry.
		 * @return {string} The label, or the reference when there is none.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		caseOf(entry) {
			const record = entry?.record ?? {}
			return record.label || record.reference || record.id || ''
		},

		/**
		 * A timestamp in the reader's own locale, or a dash.
		 *
		 * @param {string} value An ISO 8601 timestamp.
		 * @return {string} The formatted date.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		asDate(value) {
			if (!value) {
				return '-'
			}
			return new Date(value).toLocaleDateString()
		},
	},
}
</script>

<style scoped>
.signing-folder {
	padding: 20px;
}

.signing-folder__lead {
	color: var(--color-text-maxcontrast);
	margin-bottom: 16px;
}

.signing-folder__actions {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.signing-folder__count {
	color: var(--color-text-maxcontrast);
}

.signing-folder__table {
	width: 100%;
	border-collapse: collapse;
}

.signing-folder__table th,
.signing-folder__table td {
	text-align: start;
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
}

.signing-folder__results {
	margin-top: 24px;
}
</style>
