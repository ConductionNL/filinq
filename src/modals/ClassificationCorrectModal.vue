<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Correct a suggestion and confirm it in one step. The record keeps what the
classifier suggested beside what the person chose.

@spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-1
-->

<template>
	<NcModal
		v-if="record"
		:show="true"
		:name="t('filinq', 'Correct and confirm')"
		size="normal"
		@close="$emit('close')">
		<div
			class="classification-correct"
			data-testid="classification-correct-modal">
			<h2 class="classification-correct__title">
				{{ t('filinq', 'Correct and confirm') }}
			</h2>
			<p class="classification-correct__file">
				{{ record.fileName }}
			</p>
			<ClassificationChoices
				:record="record"
				:dossiers="dossiers"
				@change="choices = $event" />
			<div class="classification-correct__actions">
				<NcButton @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton
					variant="primary"
					:disabled="busy"
					data-testid="classification-correct-confirm"
					@click="$emit('confirm', choices)">
					{{ t('filinq', 'Confirm') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal } from '@nextcloud/vue'
import ClassificationChoices from '../components/ClassificationChoices.vue'

export default {
	name: 'ClassificationCorrectModal',
	components: { ClassificationChoices, NcButton, NcModal },

	props: {
		record: {
			type: Object,
			default: null,
		},

		dossiers: {
			type: Array,
			default: () => [],
		},

		busy: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['close', 'confirm'],

	data() {
		return { choices: {} }
	},

	methods: { t },
}
</script>

<style scoped>
.classification-correct {
	padding: 20px;
}

.classification-correct__file {
	color: var(--color-text-maxcontrast);
	margin-block-end: 12px;
}

.classification-correct__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-block-start: 20px;
}
</style>
