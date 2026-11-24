<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Confirms sending one composed letter by email. On opening it asks the server
where the letter would go and under which subject, so the officer sees the
address before anything is sent; a recipient without an address, or an
instance without a mail server, is said here and Send stays off.

The dialog does not send. It emits `confirm` with the PDF choice; the page
sends through the correspondence store and passes back `saving` and the
server's `error`, so a refused send keeps the dialog open with the reason.

@spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
-->

<template>
	<NcDialog
		:name="t('filinq', 'Send the letter by email')"
		data-testid="correspondence-send-dialog"
		@closing="$emit('cancel')">
		<template #default>
			<p v-if="loading" data-testid="correspondence-send-loading">
				{{ t('filinq', 'Looking up the recipient…') }}
			</p>
			<dl v-else-if="preview" class="correspondence-send__preview">
				<dt>{{ t('filinq', 'To') }}</dt>
				<dd data-testid="correspondence-send-to">
					{{ preview.to }}
				</dd>
				<dt>{{ t('filinq', 'Subject') }}</dt>
				<dd data-testid="correspondence-send-subject">
					{{ preview.subject }}
				</dd>
			</dl>
			<NcCheckboxRadioSwitch
				v-model="attachPdf"
				:disabled="saving || !preview"
				data-testid="correspondence-send-pdf">
				{{ t('filinq', 'Attach the letter as PDF') }}
			</NcCheckboxRadioSwitch>
			<p
				v-if="shownError"
				class="correspondence-send__error"
				role="alert"
				data-testid="correspondence-send-error">
				{{ shownError }}
			</p>
		</template>
		<template #actions>
			<CnDialogActions
				:saving="saving"
				:canSubmit="Boolean(preview) && !loading"
				:confirmLabel="t('filinq', 'Send')"
				@cancel="$emit('cancel')"
				@submit="confirm" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcCheckboxRadioSwitch, NcDialog } from '@nextcloud/vue'

export default {
	name: 'CorrespondenceSendDialog',
	components: { CnDialogActions, NcCheckboxRadioSwitch, NcDialog },

	props: {
		// The request the letter is composed from: templateId, dataRefs, options.
		request: { type: Object, required: true },
		// True while the page is waiting for the send.
		saving: { type: Boolean, default: false },
		// What the server said when it refused or failed the send.
		error: { type: String, default: '' },
	},

	emits: ['confirm', 'cancel'],

	data() {
		return {
			loading: true,
			preview: null,
			previewError: '',
			attachPdf: false,
		}
	},

	computed: {
		shownError() {
			return this.error || this.previewError
		},
	},

	mounted() {
		this.loadPreview()
	},

	methods: {
		t,

		/**
		 * Ask the server for the address and the subject.
		 *
		 * @return {Promise<void>} Resolves once the preview or its refusal is shown.
		 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
		 */
		async loadPreview() {
			this.loading = true
			try {
				const { data } = await axios.post(
					generateUrl('/apps/filinq/api/correspondence/send/preview'),
					this.request,
				)
				this.preview = { to: data?.to || '', subject: data?.subject || '' }
			} catch (err) {
				this.preview = null
				this.previewError = err.response?.data?.error || t('filinq', 'The recipient could not be looked up.')
			} finally {
				this.loading = false
			}
		},

		/**
		 * Hand the PDF choice to the page, which sends.
		 *
		 * @return {void}
		 * @spec openspec/changes/archive/2026-11-24-letter-email-sending/tasks.md#task-3
		 */
		confirm() {
			this.$emit('confirm', { attachPdf: this.attachPdf })
		},
	},
}
</script>

<style scoped>
.correspondence-send__preview {
	display: grid;
	grid-template-columns: max-content 1fr;
	gap: calc(var(--default-grid-baseline) * 1) calc(var(--default-grid-baseline) * 4);
	margin-block-end: calc(var(--default-grid-baseline) * 3);
}

.correspondence-send__preview dt {
	color: var(--color-text-maxcontrast);
}

.correspondence-send__preview dd {
	margin: 0;
	overflow-wrap: anywhere;
}

.correspondence-send__error {
	color: var(--color-error-text);
	margin-block-start: calc(var(--default-grid-baseline) * 2);
}
</style>
