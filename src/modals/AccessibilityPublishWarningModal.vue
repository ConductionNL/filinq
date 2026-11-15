<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Before a document goes to publication: its open accessibility findings,
and the choice to publish anyway. Only a finding an admin set to blocking
takes that choice away.

@spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.2
-->

<template>
	<NcModal
		v-if="show"
		:show="show"
		:name="t('filinq', 'Accessibility before publishing')"
		size="normal"
		@close="$emit('close')">
		<div class="publish-warning" data-testid="accessibility-publish-warning">
			<h2 class="publish-warning__title">
				{{ t('filinq', 'Accessibility before publishing') }}
			</h2>
			<NcNoteCard v-if="!checked" type="warning">
				{{
					t(
						'filinq',
						'The accessibility of this document could not be checked. You can still publish it.',
					)
				}}
			</NcNoteCard>
			<template v-else>
				<NcNoteCard :type="blocking ? 'error' : 'warning'">
					{{
						blocking
							? t(
									'filinq',
									'This document has open accessibility findings that your organisation set to block publication. Fix them first.',
								)
							: t(
									'filinq',
									'This document has open accessibility findings. People who use a screen reader may not be able to read it.',
								)
					}}
				</NcNoteCard>
				<ValidationFindingsPanel status="warnings" :findings="findings" />
			</template>
			<div class="publish-warning__actions">
				<NcButton @click="$emit('close')">
					{{ t('filinq', 'Cancel') }}
				</NcButton>
				<NcButton
					v-if="!blocking"
					variant="primary"
					data-testid="publish-anyway"
					@click="$emit('confirm')">
					{{ t('filinq', 'Publish anyway') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcModal, NcNoteCard } from '@nextcloud/vue'
import ValidationFindingsPanel from '../components/ValidationFindingsPanel.vue'

export default {
	name: 'AccessibilityPublishWarningModal',
	components: { NcButton, NcModal, NcNoteCard, ValidationFindingsPanel },
	props: {
		show: { type: Boolean, default: false },
		checked: { type: Boolean, default: true },
		findings: { type: Array, default: () => [] },
		blocking: { type: Boolean, default: false },
	},

	emits: ['close', 'confirm'],

	methods: {
		t,
	},
}
</script>

<style scoped>
.publish-warning {
	padding: 16px 20px;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.publish-warning__title {
	margin: 0;
}

.publish-warning__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
