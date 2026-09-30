<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
-->

<template>
	<NcDialog
		:name="t('filinq', 'End contract')"
		:noClose="busy"
		@closing="$emit('close')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Ending "{title}" early is final. The reason is kept on the contract.',
						{ title: contractTitle },
					)
				}}
			</p>
			<NcTextArea
				v-model="reason"
				:label="t('filinq', 'Why does the contract end?')"
				:required="true"
				data-testid="contract-terminate-reason" />
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
				data-testid="contract-terminate-confirm"
				@click="onConfirm">
				{{ t('filinq', 'End contract') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcNoteCard, NcTextArea } from '@nextcloud/vue'
import { terminateContract } from '../services/contracts.js'

/**
 * End one active contract early. The reason is mandatory: the button stays
 * off until there is one, and the server refuses an end without one too.
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export default {
	name: 'TerminateContractDialog',
	components: { NcButton, NcDialog, NcNoteCard, NcTextArea },
	props: {
		/** The contract id. */
		contractId: { type: String, required: true },
		/** The contract title, for the question. */
		contractTitle: { type: String, default: '' },
	},

	emits: ['close', 'terminated'],
	data() {
		return { reason: '', busy: false, error: '' }
	},

	methods: {
		t,
		/**
		 * End the contract and hand the stored contract back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
		 */
		async onConfirm() {
			this.busy = true
			this.error = ''
			const answer = await terminateContract(
				this.contractId,
				this.reason.trim(),
			)
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('terminated', answer.data)
		},
	},
}
</script>
