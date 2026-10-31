<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
-->

<template>
	<NcDialog
		:name="t('filinq', 'Release legal hold')"
		:noClose="busy"
		@closing="$emit('close')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Releasing "{name}" lets its records be destroyed or deleted again when their term allows. Records another active hold covers stay frozen. Released is final.',
						{ name: holdName },
					)
				}}
			</p>
			<NcTextArea
				v-model="reason"
				:label="t('filinq', 'Why is the hold released?')"
				:required="true"
				data-testid="legal-hold-release-reason" />
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="warning"
				:disabled="busy || reason.trim() === ''"
				data-testid="legal-hold-release-confirm"
				@click="onConfirm">
				{{ t('filinq', 'Release') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcNoteCard, NcTextArea } from '@nextcloud/vue'
import { releaseCase } from '../services/legalHolds.js'

/**
 * Release one legal hold. The reason is mandatory: the button stays off until
 * there is one, and the server refuses a release without one as well.
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export default {
	name: 'ReleaseLegalHoldDialog',
	components: { NcButton, NcDialog, NcNoteCard, NcTextArea },
	props: {
		/** The case uuid. */
		holdId: { type: String, required: true },
		/** The case name, for the question. */
		holdName: { type: String, default: '' },
	},

	emits: ['close', 'released'],
	data() {
		return { reason: '', busy: false, error: '' }
	},

	methods: {
		t,
		/**
		 * Release, and hand the released case back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		async onConfirm() {
			this.busy = true
			this.error = ''
			const answer = await releaseCase(this.holdId, this.reason.trim())
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('released', answer.data)
		},
	},
}
</script>
