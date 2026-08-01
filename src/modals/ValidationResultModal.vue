<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/specs/document-validation-checks/spec.md
-->

<template>
	<NcModal
		:show="show"
		:title="t('docudesk', 'Document validation')"
		@close="$emit('close')">
		<div class="validation-result-modal">
			<NcLoadingIcon v-if="loading" :size="32" />
			<NcNoteCard v-else-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<ValidationFindingsPanel
				v-else
				:status="status"
				:findings="findings"
				@ocr="$emit('ocr', $event)" />
		</div>
	</NcModal>
</template>

<script>
// @nextcloud/vue v9 dropped the `dist/Components/Nc*.js` path layout in
// favour of an exports map; the old deep specifiers resolve to nothing.
import { NcModal, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import ValidationFindingsPanel from '../components/ValidationFindingsPanel.vue'

export default {
	name: 'ValidationResultModal',
	components: { NcModal, NcLoadingIcon, NcNoteCard, ValidationFindingsPanel },
	props: {
		show: { type: Boolean, default: false },
		loading: { type: Boolean, default: false },
		error: { type: String, default: '' },
		status: { type: String, default: '' },
		findings: { type: Array, default: () => [] },
	},
}
</script>

<style scoped>
.validation-result-modal {
	padding: 16px;
	min-width: 320px;
}
</style>
