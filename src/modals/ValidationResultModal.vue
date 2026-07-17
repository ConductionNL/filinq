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
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
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
