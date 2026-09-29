<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
  -->
<template>
	<div class="filinq-merge-leaf">
		<p v-if="loading" class="filinq-merge-leaf__state">
			{{ t('filinq', 'Looking up documents') }}
		</p>
		<p
			v-else-if="error"
			class="filinq-merge-leaf__state filinq-merge-leaf__state--error">
			{{ t('filinq', 'The documents could not be loaded') }}
		</p>
		<p v-else-if="rows.length === 0" class="filinq-merge-leaf__state">
			{{ t('filinq', 'No documents yet') }}
		</p>
		<form v-else class="filinq-merge-leaf__form" @submit.prevent="submit">
			<fieldset class="filinq-merge-leaf__fieldset">
				<legend>
					{{
						t(
							'filinq',
							'Choose the documents to merge, then put them in order.',
						)
					}}
				</legend>
				<label
					v-for="row in rows"
					:key="row.fileId"
					class="filinq-merge-leaf__choice">
					<input
						type="checkbox"
						:checked="selection.includes(row.fileId)"
						@change="toggle(row.fileId)" />
					{{ row.name }}
				</label>
			</fieldset>

			<div v-if="selection.length > 0" class="filinq-merge-leaf__order">
				<p :id="orderLabelId" class="filinq-merge-leaf__heading">
					{{ t('filinq', 'In the PDF, in this order') }}
				</p>
				<ol :aria-labelledby="orderLabelId" class="filinq-merge-leaf__list">
					<li
						v-for="(fileId, index) in selection"
						:key="fileId"
						class="filinq-merge-leaf__item">
						<span class="filinq-merge-leaf__name">{{
							nameOf(fileId)
						}}</span>
						<button
							type="button"
							:disabled="index === 0"
							:aria-label="
								t('filinq', 'Move {name} up', {
									name: nameOf(fileId),
								})
							"
							@click="move(index, -1)">
							{{ t('filinq', 'Move up') }}
						</button>
						<button
							type="button"
							:disabled="index === selection.length - 1"
							:aria-label="
								t('filinq', 'Move {name} down', {
									name: nameOf(fileId),
								})
							"
							@click="move(index, 1)">
							{{ t('filinq', 'Move down') }}
						</button>
					</li>
				</ol>
			</div>

			<label class="filinq-merge-leaf__field">
				{{ t('filinq', 'Cover page') }}
				<select v-model="coverTemplateRef">
					<option value="">
						{{ t('filinq', 'No cover page') }}
					</option>
					<option
						v-for="template in templates"
						:key="template.ref"
						:value="template.ref">
						{{ template.name }}
					</option>
				</select>
			</label>

			<label class="filinq-merge-leaf__choice">
				<input v-model="bookmarks" type="checkbox" />
				{{ t('filinq', 'A bookmark per document') }}
			</label>

			<label class="filinq-merge-leaf__field">
				{{ t('filinq', 'Name of the PDF') }}
				<input v-model="name" type="text" />
			</label>

			<p v-if="!mergeable" class="filinq-merge-leaf__state">
				{{ t('filinq', 'Choose at least two documents') }}
			</p>
			<button type="submit" :disabled="!mergeable || busy">
				{{ busy ? t('filinq', 'Merging') : t('filinq', 'Merge') }}
			</button>

			<p
				v-if="outcome"
				role="status"
				class="filinq-merge-leaf__state"
				:class="{ 'filinq-merge-leaf__state--error': outcome.failed }">
				{{ outcome.message }}
				<a v-if="outcome.link" :href="outcome.link">{{
					t('filinq', 'Open the PDF')
				}}</a>
			</p>
		</form>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	buildMergeRequest,
	canMerge,
	moveInSelection,
	toggleInSelection,
} from '../services/mergeSelection.js'

let instanceCount = 0

/**
 * Merge the documents of one object into one PDF.
 *
 * Lists the host object's documents through Filinq's case-document list, which
 * already leaves out files the reader cannot see, and sends the chosen order to
 * `POST /apps/filinq/api/merge`. The server writes the PDF beside the first
 * document, so on a case page it lands in the case folder.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
export default {
	name: 'CnFilinqMergeToPdfWidget',
	props: {
		/** The host object's register slug. */
		register: { type: String, default: '' },
		/** The host object's schema slug. */
		schema: { type: String, default: '' },
		/** The host object's id. */
		objectId: { type: String, default: '' },
	},

	data() {
		instanceCount += 1
		return {
			rows: [],
			templates: [],
			selection: [],
			coverTemplateRef: '',
			bookmarks: true,
			name: '',
			loading: true,
			error: false,
			busy: false,
			outcome: null,
			orderLabelId: 'filinq-merge-order-' + instanceCount,
		}
	},

	computed: {
		/**
		 * Whether the chosen documents are enough for a merge.
		 *
		 * @return {boolean} True with two or more.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		mergeable() {
			return canMerge(this.selection)
		},
	},

	/**
	 * Load the documents and the cover templates.
	 *
	 * @return {void}
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	mounted() {
		this.load()
		this.loadTemplates()
	},

	methods: {
		/**
		 * The listed name of a selected document.
		 *
		 * @param {number} fileId The file id.
		 * @return {string} Its name.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		nameOf(fileId) {
			const row = this.rows.find((candidate) => candidate.fileId === fileId)
			return row ? row.name : String(fileId)
		},

		/**
		 * Add or remove a document.
		 *
		 * @param {number} fileId The file id.
		 * @return {void}
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		toggle(fileId) {
			this.selection = toggleInSelection(this.selection, fileId)
		},

		/**
		 * Move a selected document one place.
		 *
		 * @param {number} index Its position.
		 * @param {number} delta -1 up, 1 down.
		 * @return {void}
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		move(index, delta) {
			this.selection = moveInSelection(this.selection, index, delta)
		},

		/**
		 * Read the host object's documents.
		 *
		 * @return {Promise<void>} Resolves once `rows` reflects the answer.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		async load() {
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
				const body = await this.request(
					generateUrl('/apps/filinq/api/case-documents/files')
						+ '?'
						+ query.toString(),
				)
				this.rows = Array.isArray(body.results) ? body.results : []
			} catch {
				this.error = true
			} finally {
				this.loading = false
			}
		},

		/**
		 * Read the templates a cover page can be made from. Without them the
		 * picker offers only "No cover page", which is still a valid merge.
		 *
		 * @return {Promise<void>} Resolves once `templates` is set.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		async loadTemplates() {
			try {
				const body = await this.request(
					generateUrl('/apps/filinq/api/templates'),
				)
				const results = Array.isArray(body.results) ? body.results : []
				this.templates = results
					.map((template) => ({
						ref: String(template.uuid || template.id || ''),
						name: template.name || '',
					}))
					.filter((template) => template.ref !== '')
			} catch {
				this.templates = []
			}
		},

		/**
		 * Send the merge and say what came of it.
		 *
		 * @return {Promise<void>} Resolves once `outcome` is set.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		async submit() {
			if (this.mergeable === false || this.busy === true) {
				return
			}
			this.busy = true
			this.outcome = null
			const payload = buildMergeRequest({
				rows: this.rows,
				selection: this.selection,
				coverTemplateRef: this.coverTemplateRef,
				bookmarks: this.bookmarks,
				name: this.name,
				host: {
					register: this.register,
					schema: this.schema,
					objectId: this.objectId,
				},
			})
			try {
				const response = await fetch(generateUrl('/apps/filinq/api/merge'), {
					method: 'POST',
					headers: {
						requesttoken: this.requestToken(),
						Accept: 'application/json',
						'Content-Type': 'application/json',
					},
					body: JSON.stringify(payload),
				})
				const job = await response.json().catch(() => ({}))
				this.outcome = this.describe(response.status, job)
			} catch {
				this.outcome = {
					failed: true,
					message: t('filinq', 'The merge failed: {reason}', {
						reason: t('filinq', 'the server could not be reached'),
					}),

					link: '',
				}
			} finally {
				this.busy = false
			}
		},

		/**
		 * What the handler reads after a merge.
		 *
		 * @param {number} status The HTTP status.
		 * @param {object} job    The answer body.
		 * @return {{failed: boolean, message: string, link: string}} The outcome.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		describe(status, job) {
			if (status === 202) {
				return {
					failed: false,
					message: t(
						'filinq',
						'The merge is queued. The PDF appears beside the first document when it is ready.',
					),

					link: '',
				}
			}
			if (status === 200 && job.status === 'done' && job.resultFileId) {
				return {
					failed: false,
					message: t('filinq', 'The PDF is ready.'),
					link: generateUrl('/f/{fileId}', { fileId: job.resultFileId }),
				}
			}
			const reason =
				job.lastError || job.error || t('filinq', 'no reason was given')
			return {
				failed: true,
				message: t('filinq', 'The merge failed: {reason}', { reason }),
				link: '',
			}
		},

		/**
		 * GET a Filinq route as JSON.
		 *
		 * @param {string} url The route.
		 * @return {Promise<object>} The body.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		async request(url) {
			const response = await fetch(url, {
				headers: {
					requesttoken: this.requestToken(),
					Accept: 'application/json',
				},
			})
			if (response.ok === false) {
				throw new Error('HTTP ' + response.status)
			}
			return response.json()
		},

		/**
		 * The current page's request token, read from the DOM because this
		 * widget renders inside another app's page.
		 *
		 * @return {string} The token, or ''.
		 *
		 * @spec openspec/specs/document-merge/spec.md
		 */
		requestToken() {
			const head = document.getElementsByTagName('head')[0]
			return (head && head.getAttribute('data-requesttoken')) || ''
		},
	},
}
</script>

<style scoped>
.filinq-merge-leaf__form {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.filinq-merge-leaf__fieldset {
	border: none;
	margin: 0;
	padding: 0;
}

.filinq-merge-leaf__choice,
.filinq-merge-leaf__field {
	display: flex;
	align-items: center;
	gap: 8px;
}

.filinq-merge-leaf__field {
	flex-direction: column;
	align-items: stretch;
}

.filinq-merge-leaf__list {
	margin: 0;
	padding-inline-start: 20px;
}

.filinq-merge-leaf__item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.filinq-merge-leaf__name {
	flex: 1;
}

.filinq-merge-leaf__heading {
	font-weight: bold;
	margin: 0;
}

.filinq-merge-leaf__state {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.filinq-merge-leaf__state--error {
	color: var(--color-error);
}
</style>
