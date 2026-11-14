<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="waarmerk-run-actions">
		<NcButton
			v-if="anonymizedFileId && isPdf"
			wide
			variant="secondary"
			data-testid="run-waarmerk"
			@click="open('seal')">
			<template #icon>
				<CertificateOutline :size="20" />
			</template>
			{{ t('filinq', 'Add waarmerk') }}
		</NcButton>
		<NcButton
			v-if="anonymizationLinkId"
			wide
			variant="secondary"
			data-testid="run-certificate"
			@click="open('certificate')">
			<template #icon>
				<CertificateOutline :size="20" />
			</template>
			{{ t('filinq', 'Anonymisation certificate') }}
		</NcButton>
		<WaarmerkSealModal
			:show="mode !== ''"
			:mode="mode || 'seal'"
			:fileId="Number(anonymizedFileId) || 0"
			:linkId="anonymizationLinkId || ''"
			:fileName="fileName"
			@close="mode = ''" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import CertificateOutline from 'vue-material-design-icons/CertificateOutline.vue'
import WaarmerkSealModal from '../modals/WaarmerkSealModal.vue'

// The waarmerk actions on a finished anonymisation run (document-waarmerk-certification
// REQ-DDWMK-002/003): seal the anonymised PDF, or certify the run.
export default {
	name: 'WaarmerkRunActions',
	components: { CertificateOutline, NcButton, WaarmerkSealModal },

	props: {
		/** The anonymised file id. */
		anonymizedFileId: { type: [Number, String], default: null },
		/** The anonymised file name. */
		fileName: { type: String, default: '' },
		/** The run's anonymizationLink id. */
		anonymizationLinkId: { type: String, default: null },
	},

	data() {
		return { mode: '' }
	},

	computed: {
		/**
		 * Only a PDF can carry a waarmerk page.
		 *
		 * @return {boolean}
		 */
		isPdf() {
			return (this.fileName || '').toLowerCase().endsWith('.pdf')
		},
	},

	methods: {
		t,

		/**
		 * Open the dialog in a mode.
		 *
		 * @param {string} mode `seal` or `certificate`.
		 * @return {void}
		 */
		open(mode) {
			this.mode = mode
		},
	},
}
</script>

<style scoped>
.waarmerk-run-actions {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin: 8px 0;
}
</style>
