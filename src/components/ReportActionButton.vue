<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The action button of a conformance report modal: a spinner and a busy label
while its step runs. Attributes and listeners (data-testid, @click) fall
through to the NcButton.

@spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
-->

<template>
	<NcButton :variant="variant" :disabled="disabled">
		<template #icon>
			<NcLoadingIcon v-if="busy" :size="18" />
		</template>
		{{ busy ? busyLabel : label }}
	</NcButton>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'

export default {
	name: 'ReportActionButton',
	components: { NcButton, NcLoadingIcon },

	props: {
		label: { type: String, required: true },
		busyLabel: { type: String, required: true },
		busy: { type: Boolean, default: false },
		disabled: { type: Boolean, default: false },
		variant: { type: String, default: 'primary' },
	},
}
</script>
