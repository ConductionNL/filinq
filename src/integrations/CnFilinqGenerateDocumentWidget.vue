<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="filinq-generate-leaf">
		<p v-if="loading" class="filinq-generate-leaf__state">
			{{ t('filinq', 'Looking up templates') }}
		</p>
		<p
			v-else-if="error"
			class="filinq-generate-leaf__state filinq-generate-leaf__state--error">
			{{ t('filinq', 'The templates could not be loaded') }}
		</p>
		<p v-else-if="rows.length === 0" class="filinq-generate-leaf__state">
			{{ t('filinq', 'No templates apply to this item') }}
		</p>
		<form v-else class="filinq-generate-leaf__form" @submit.prevent="submit">
			<fieldset class="filinq-generate-leaf__fieldset">
				<legend>{{ t('filinq', 'Choose a template') }}</legend>
				<label
					v-for="row in rows"
					:key="row.id"
					class="filinq-generate-leaf__choice">
					<input
						v-model="chosenId"
						type="radio"
						:name="groupName"
						:value="row.id" />
					<span>
						{{ row.name }}
						<span v-if="row.description" class="filinq-generate-leaf__description">{{ row.description }}</span>
					</span>
				</label>
			</fieldset>
			<button type="submit" :disabled="generatable === false || busy">
				{{ busy ? t('filinq', 'Generating') : t('filinq', 'Generate') }}
			</button>
			<p
				v-if="outcome"
				role="status"
				class="filinq-generate-leaf__state" :class="[{ 'filinq-generate-leaf__state--error': outcome.failed }]">
				{{ outcome.message }}
			</p>
		</form>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { buildGenerateRequest, canGenerate, storedFile } from '../services/generateDocumentRequest.js'
import { hostObjectLeaf } from './hostObjectLeaf.js'

let instanceCount = 0

/**
 * Generate a document from a template on one object.
 *
 * Lists the templates whose scope fits the host object through
 * `GET /apps/filinq/api/templates/for-object` and generates the chosen one
 * through Filinq's own `POST /apps/filinq/api/documents/generate`, with the
 * host object as its data. Nothing is asked of the host app.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
export default {
	name: 'CnFilinqGenerateDocumentWidget',
	mixins: [hostObjectLeaf],

	data() {
		instanceCount += 1
		return {
			rows: [],
			chosenId: '',
			loading: true,
			error: false,
			busy: false,
			outcome: null,
			groupName: 'filinq-generate-template-' + instanceCount,
		}
	},

	computed: {
		/**
		 * The chosen template row.
		 *
		 * @return {object|null} The row, or null.
		 *
		 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
		 */
		chosen() {
			return this.rows.find((row) => row.id === this.chosenId) || null
		},

		/**
		 * Whether a template is chosen for a complete host object.
		 *
		 * @return {boolean} True when Generate can run.
		 *
		 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
		 */
		generatable() {
			return canGenerate(this.chosen, this.host())
		},
	},

	/**
	 * Load the templates that apply to the host object.
	 *
	 * @return {void}
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
	 */
	mounted() {
		this.loadHostRows('/apps/filinq/api/templates/for-object')
	},

	methods: {
		t,

		/**
		 * The host object.
		 *
		 * @return {{register: string, schema: string, objectId: string}} The host.
		 *
		 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
		 */
		host() {
			return { register: this.register, schema: this.schema, objectId: this.objectId }
		},

		/**
		 * Generate the chosen template and say where it went.
		 *
		 * @return {Promise<void>} Resolves once the outcome is shown.
		 *
		 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
		 */
		async submit() {
			if (this.generatable === false || this.busy === true) {
				return
			}
			this.busy = true
			this.outcome = null
			try {
				const response = await fetch(generateUrl('/apps/filinq/api/documents/generate'), {
					method: 'POST',
					headers: {
						requesttoken: this.requestToken(),
						Accept: 'application/json',
						'Content-Type': 'application/json',
					},
					body: JSON.stringify(buildGenerateRequest({ template: this.chosen, host: this.host() })),
				})
				const body = await response.json().catch(() => ({}))
				const file = response.ok ? storedFile(body) : null
				this.outcome = file
					? { failed: false, message: t('filinq', 'Generated {name}', { name: file.name || this.chosen.name }) }
					: { failed: true, message: t('filinq', 'The document could not be generated') }
			} catch {
				this.outcome = { failed: true, message: t('filinq', 'The document could not be generated') }
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.filinq-generate-leaf__form {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.filinq-generate-leaf__fieldset {
	border: none;
	margin: 0;
	padding: 0;
}

.filinq-generate-leaf__choice {
	display: flex;
	align-items: flex-start;
	gap: 8px;
}

.filinq-generate-leaf__description {
	display: block;
	color: var(--color-text-maxcontrast);
}

.filinq-generate-leaf__state--error {
	color: var(--color-error-text, var(--color-error));
}
</style>
