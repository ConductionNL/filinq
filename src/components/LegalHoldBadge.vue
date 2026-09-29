<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
-->
<template>
	<NcNoteCard
		v-if="status && status.held"
		type="warning"
		class="legal-hold-badge"
		data-testid="legal-hold-badge">
		<strong>{{ t('filinq', 'Under legal hold') }}</strong>
		<span v-if="caseNames">
			{{ t('filinq', 'Held by: {names}.', { names: caseNames }) }}
		</span>
		{{
			t(
				'filinq',
				'Destroying or deleting is switched off until the hold is released.',
			)
		}}
	</NcNoteCard>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcNoteCard } from '@nextcloud/vue'
import { holdStatus } from '../services/legalHolds.js'

/**
 * Says a record is under a legal hold, and tells the parent so it can switch
 * off destroying and deleting. Names the holding matter only to hold
 * authority: the server leaves the names out for everybody else.
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
 */
export default {
	name: 'LegalHoldBadge',
	components: { NcNoteCard },
	props: {
		/** The record (document or dossier) uuid. */
		objectId: { type: String, default: '' },
	},

	emits: ['held'],
	data() {
		return { status: null }
	},

	computed: {
		/**
		 * The names of the holding matters, when the server gave them.
		 *
		 * @return {string}
		 */
		caseNames() {
			return (this.status?.cases || []).map((c) => c.name).join(', ')
		},
	},

	watch: {
		objectId: {
			/**
			 * Ask whether the record is held.
			 *
			 * @param {string} objectId The record.
			 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.2
			 */
			async handler(objectId) {
				this.status = null
				this.$emit('held', false)
				const status = await holdStatus(objectId)
				if (objectId === this.objectId) {
					this.status = status
					this.$emit('held', status?.held === true)
				}
			},

			immediate: true,
		},
	},

	methods: { t },
}
</script>

<style scoped>
.legal-hold-badge strong {
	margin-inline-end: var(--default-grid-baseline, 4px);
}
</style>
