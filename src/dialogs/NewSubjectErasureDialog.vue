<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
-->

<template>
	<NcDialog
		:name="t('filinq', 'New erasure request')"
		:noClose="busy"
		size="normal"
		@closing="$emit('close')">
		<template #default>
			<div class="new-subject-erasure">
				<NcTextField
					v-model="subject"
					:label="t('filinq', 'Person to remove')"
					:required="true" />
				<NcTextArea
					v-model="identifiers"
					:label="
						t(
							'filinq',
							'Other ways the documents name this person, one per line (email address, phone number, other spellings)',
						)
					" />
				<NcTextField
					v-model="ground"
					:label="t('filinq', 'Legal ground')"
					:required="true" />
				<NcTextField
					v-model="dueAt"
					type="date"
					:label="
						t('filinq', 'Answer due (optional, default one month)')
					" />
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
			</div>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="busy || !complete"
				data-testid="subject-erasure-create"
				@click="onCreate">
				{{ t('filinq', 'Record request') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcDialog,
	NcNoteCard,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import { createRequest, parseLines } from '../services/subjectErasures.js'

/**
 * Record an erasure request: the person, how the documents name them, and the
 * legal ground. Nothing is looked up or changed until the preview.
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export default {
	name: 'NewSubjectErasureDialog',
	components: { NcButton, NcDialog, NcNoteCard, NcTextArea, NcTextField },

	emits: ['close', 'created'],
	data() {
		return {
			subject: '',
			identifiers: '',
			ground: '',
			dueAt: '',
			busy: false,
			error: '',
		}
	},

	computed: {
		/**
		 * Whether the person and the ground are filled in.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		complete() {
			return this.subject.trim() !== '' && this.ground.trim() !== ''
		},
	},

	methods: {
		t,
		/**
		 * Record the request and hand it back.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async onCreate() {
			this.busy = true
			this.error = ''
			const answer = await createRequest({
				subject: this.subject.trim(),
				identifiers: parseLines(this.identifiers),
				ground: this.ground.trim(),
				dueAt: this.dueAt,
			})
			this.busy = false
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.$emit('created', answer.data)
		},
	},
}
</script>

<style scoped>
.new-subject-erasure {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}
</style>
