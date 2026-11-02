<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
-->

<template>
	<NcDialog
		:name="t('filinq', 'Erase this person from the documents?')"
		:noClose="busy"
		size="normal"
		@closing="$emit('close')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'The person is replaced by [VERWIJDERD] in {count} documents, and earlier versions that still name them are deleted. This cannot be undone. The documents themselves stay.',
						{ count: erasable },
					)
				}}
			</p>
			<p v-if="refused > 0">
				{{
					t(
						'filinq',
						'{count} documents are refused because of a legal hold, and stay as they are.',
						{ count: refused },
					)
				}}
			</p>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="error"
				:disabled="busy"
				data-testid="subject-erasure-run"
				@click="onRun">
				{{ t('filinq', 'Erase') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcNoteCard } from '@nextcloud/vue'
import { runRequest } from '../services/subjectErasures.js'

/**
 * The last check before an erasure runs: how many documents change and how
 * many are refused.
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export default {
	name: 'RunSubjectErasureDialog',
	components: { NcButton, NcDialog, NcNoteCard },

	props: {
		requestId: { type: String, required: true },
		erasable: { type: Number, default: 0 },
		refused: { type: Number, default: 0 },
	},

	emits: ['close', 'ran'],
	data() {
		return { busy: false, error: '' }
	},

	methods: {
		t,
		/**
		 * Run the erasure and hand the outcome back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async onRun() {
			this.busy = true
			this.error = ''
			const answer = await runRequest(this.requestId)
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('ran', answer.data)
		},
	},
}
</script>
