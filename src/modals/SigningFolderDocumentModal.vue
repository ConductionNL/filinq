<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
-->

<template>
	<NcModal :show="show" :title="documentName" size="large" @close="$emit('close')">
		<div class="signing-folder-document">
			<p class="signing-folder-document__context">
				{{ recordLabel }}
			</p>
			<iframe
				v-if="fileId"
				:src="documentUrl"
				:title="t('filinq', 'Document you are about to sign')"
				class="signing-folder-document__frame" />
			<NcEmptyContent
				v-else
				:name="t('filinq', 'This request carries no document')" />
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcEmptyContent, NcModal } from '@nextcloud/vue'

export default {
	name: 'SigningFolderDocumentModal',
	components: { NcModal, NcEmptyContent },
	props: {
		show: { type: Boolean, default: false },
		documentName: { type: String, default: '' },
		recordLabel: { type: String, default: '' },
		fileId: { type: String, default: '' },
	},

	emits: ['close'],

	/**
	 * Hand the translator to the template.
	 *
	 * @return {object} The bindings the template reads.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	setup() {
		return { t }
	},

	computed: {
		/**
		 * The document, shown inside the folder.
		 *
		 * Nextcloud's own file route renders the Viewer, so a signer reads
		 * the document in place instead of navigating into Files and losing
		 * the folder they were working through.
		 *
		 * @return {string} The file URL.
		 *
		 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
		 */
		documentUrl() {
			return generateUrl(`/f/${this.fileId}`)
		},
	},
}
</script>

<style scoped>
.signing-folder-document {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding: 16px;
	height: 75vh;
}

.signing-folder-document__context {
	color: var(--color-text-maxcontrast);
}

.signing-folder-document__frame {
	flex: 1;
	width: 100%;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
}
</style>
