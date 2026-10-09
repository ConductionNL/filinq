<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The intake inbox as a leaf on another app's record: the documents waiting to
be filed, those whose source reference names this record first, and an assign
button that files one on this record through filinq's own route. Plain markup
only, because the leaves bundle loads on other apps' pages.

@spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
-->

<template>
	<div class="filinq-intake-leaf" data-testid="filinq-document-intake-leaf">
		<p v-if="loading" class="filinq-intake-leaf__state">
			{{ t('filinq', 'Looking up waiting documents') }}
		</p>
		<p
			v-else-if="error"
			class="filinq-intake-leaf__state filinq-intake-leaf__state--error"
			role="alert">
			{{ error }}
		</p>
		<p v-else-if="rows.length === 0" class="filinq-intake-leaf__state">
			{{ t('filinq', 'No documents are waiting to be filed.') }}
		</p>
		<ul v-else class="filinq-intake-leaf__list">
			<li v-for="row in rows" :key="row.uuid" class="filinq-intake-leaf__item">
				<span class="filinq-intake-leaf__name">
					{{
						row.subject
						|| row.fileName
						|| t('filinq', 'Untitled document')
					}}
				</span>
				<span v-if="row.matchesHost" class="filinq-intake-leaf__match">
					{{ t('filinq', 'Names this record') }}
				</span>
				<button
					type="button"
					class="filinq-intake-leaf__assign"
					:disabled="busy !== ''"
					@click="assign(row)">
					{{ t('filinq', 'File on this record') }}
				</button>
			</li>
		</ul>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { assignToHost, orderForHost } from '../services/intakeLeaf.js'
import { listWaitingDocuments } from '../services/intakeService.js'

/**
 * The waiting documents for one host record.
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
export default {
	name: 'CnFilinqDocumentIntakeWidget',
	props: {
		/** The host object's register slug. */
		register: { type: String, default: '' },
		/** The host object's schema slug. */
		schema: { type: String, default: '' },
		/** The host object's id. */
		objectId: { type: String, default: '' },
		/** The host object's own reference, such as a case number, when the host passes one. */
		reference: { type: String, default: '' },
	},

	data() {
		return {
			rows: [],
			loading: true,
			busy: '',
			error: '',
		}
	},

	async mounted() {
		try {
			const waiting = await listWaitingDocuments()
			this.rows = orderForHost(waiting, {
				objectId: this.objectId,
				reference: this.reference,
			})
		} catch {
			this.error = t('filinq', 'The waiting documents could not be read.')
		} finally {
			this.loading = false
		}
	},

	methods: {
		t,

		/**
		 * File one waiting document on the host record, and take it off the list.
		 *
		 * @param {object} row The waiting document.
		 * @return {Promise<void>} Resolves once the answer is in.
		 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
		 */
		async assign(row) {
			this.busy = row.uuid
			this.error = ''
			try {
				await assignToHost(row.uuid, {
					register: this.register,
					schema: this.schema,
					objectId: this.objectId,
				})
				this.rows = this.rows.filter(
					(candidate) => candidate.uuid !== row.uuid,
				)
			} catch {
				this.error = t(
					'filinq',
					'The document could not be filed on this record.',
				)
			} finally {
				this.busy = ''
			}
		},
	},
}
</script>

<style scoped>
.filinq-intake-leaf__list {
	margin: 0;
	padding: 0;
	list-style: none;
}

.filinq-intake-leaf__item {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 8px;
	padding: 4px 0;
}

.filinq-intake-leaf__name {
	flex: 1 1 auto;
}

.filinq-intake-leaf__match,
.filinq-intake-leaf__state {
	color: var(--color-text-maxcontrast);
}

.filinq-intake-leaf__state {
	margin: 0;
}

.filinq-intake-leaf__state--error {
	color: var(--color-error);
}

.filinq-intake-leaf__assign {
	min-height: 34px;
}
</style>
