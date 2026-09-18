<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The intake inbox: every document that arrived through a scanner, a shared
mailbox or digital post and does not belong to a record yet. A clerk assigns one
to a record or rejects it with a reason, and either way it leaves the inbox.

@spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
-->

<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Intake')"
			:description="
				t(
					'filinq',
					'Documents that arrived before anyone knew which record they belong to. Assign one to a record, or reject it with a reason.',
				)
			"
			:showTitle="true"
			:objects="documents"
			:columns="tableColumns"
			:loading="loading"
			:selectable="false"
			:showAdd="false"
			:showEditAction="false"
			:showCopyAction="false"
			:showDeleteAction="false"
			:showMassImport="false"
			:showMassExport="false"
			:showMassCopy="false"
			:showMassDelete="false"
			rowKey="uuid"
			:emptyText="emptyText"
			:refreshing="refreshing"
			data-testid="intake-index"
			@refresh="refresh">
			<template #column-channel="{ row }">
				<CnStatusBadge :label="channelLabel(row.channel)" :colorMap="channelColorMap" />
			</template>

			<template #column-receivedAt="{ row }">
				{{ formatDate(row.receivedAt) }}
			</template>

			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton closeAfterClick @click="openAssign(row)">
						<template #icon>
							<FolderOutline :size="20" />
						</template>
						{{ t('filinq', 'Assign') }}
					</NcActionButton>
					<NcActionButton closeAfterClick @click="openReject(row)">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('filinq', 'Reject') }}
					</NcActionButton>
				</NcActions>
			</template>
		</CnIndexPage>

		<IntakeAssignDialog
			v-if="assignTarget"
			:document="assignTarget"
			:saving="saving"
			:error="actionError"
			@confirm="assign"
			@cancel="closeDialogs" />

		<FinalDocumentReasonDialog
			v-if="rejectTarget"
			:name="t('filinq', 'Reject this document')"
			:explanation="rejectExplanation"
			:placeholder="t('filinq', 'Not ours, no case here')"
			:confirmLabel="t('filinq', 'Reject')"
			@confirm="reject"
			@cancel="closeDialogs" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import FinalDocumentReasonDialog from '../../dialogs/FinalDocumentReasonDialog.vue'
import IntakeAssignDialog from '../../dialogs/IntakeAssignDialog.vue'
import {
	assignIntakeDocument,
	listWaitingDocuments,
	rejectIntakeDocument,
} from '../../services/intakeService.js'

export default {
	name: 'IntakeIndex',
	components: {
		CnIndexPage,
		CnStatusBadge,
		Delete,
		DotsHorizontal,
		FinalDocumentReasonDialog,
		FolderOutline,
		IntakeAssignDialog,
		NcActionButton,
		NcActions,
	},

	data() {
		return {
			documents: [],
			loading: false,
			refreshing: false,
			saving: false,
			assignTarget: null,
			rejectTarget: null,
			actionError: '',
			loadError: '',
			channelColorMap: {
				[t('filinq', 'Scan')]: 'primary',
				[t('filinq', 'Mail')]: 'warning',
				[t('filinq', 'Digital post')]: 'default',
			},
		}
	},

	computed: {
		tableColumns() {
			return [
				{ key: 'channel', label: t('filinq', 'Channel'), sortable: true },
				{ key: 'sender', label: t('filinq', 'Sender'), sortable: true },
				{ key: 'subject', label: t('filinq', 'Subject'), sortable: true },
				{ key: 'receivedAt', label: t('filinq', 'Received'), sortable: true },
			]
		},

		emptyText() {
			if (this.loadError) {
				return this.loadError
			}
			return t('filinq', 'Nothing is waiting. Every document that arrived has a record.')
		},

		rejectExplanation() {
			const subject = this.rejectTarget?.subject || t('filinq', 'this document')
			return t(
				'filinq',
				'Say why "{subject}" does not belong here. The reason stays with the document.',
				{ subject },
			)
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,

		channelLabel(channel) {
			const labels = {
				scan: t('filinq', 'Scan'),
				mail: t('filinq', 'Mail'),
				digitalPost: t('filinq', 'Digital post'),
			}
			return labels[channel] || channel || t('filinq', 'Unknown')
		},

		formatDate(value) {
			if (!value) {
				return '-'
			}
			const parsed = new Date(value)
			if (Number.isNaN(parsed.getTime())) {
				return value
			}
			return parsed.toLocaleString()
		},

		/**
		 * Load what is waiting.
		 *
		 * @return {Promise<void>} Resolves once the list has settled.
		 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
		 */
		async load() {
			this.loading = true
			this.loadError = ''
			try {
				this.documents = await listWaitingDocuments()
			} catch {
				this.documents = []
				this.loadError = t('filinq', 'The intake inbox could not be read.')
			} finally {
				this.loading = false
			}
		},

		async refresh() {
			this.refreshing = true
			try {
				await this.load()
			} finally {
				this.refreshing = false
			}
		},

		openAssign(row) {
			this.actionError = ''
			this.rejectTarget = null
			this.assignTarget = row
		},

		openReject(row) {
			this.actionError = ''
			this.assignTarget = null
			this.rejectTarget = row
		},

		closeDialogs() {
			this.assignTarget = null
			this.rejectTarget = null
			this.actionError = ''
		},

		/**
		 * Assign the open document to the picked record.
		 *
		 * @param {object} target The record, as register, schema and id.
		 * @return {Promise<void>} Resolves once the server has answered.
		 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
		 */
		async assign(target) {
			const uuid = this.assignTarget?.uuid
			if (!uuid) {
				return
			}
			this.saving = true
			this.actionError = ''
			try {
				await assignIntakeDocument(uuid, target)
				this.closeDialogs()
				await this.load()
			} catch (error) {
				this.actionError =
					error?.response?.data?.error
					|| t('filinq', 'The assignment was refused.')
			} finally {
				this.saving = false
			}
		},

		/**
		 * Reject the open document with the given reason.
		 *
		 * @param {string} reason Why it does not belong here.
		 * @return {Promise<void>} Resolves once the server has answered.
		 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
		 */
		async reject(reason) {
			const uuid = this.rejectTarget?.uuid
			if (!uuid) {
				return
			}
			this.saving = true
			this.actionError = ''
			try {
				await rejectIntakeDocument(uuid, reason)
				this.closeDialogs()
				await this.load()
			} catch (error) {
				this.actionError =
					error?.response?.data?.error || t('filinq', 'The rejection was refused.')
			} finally {
				this.saving = false
			}
		},
	},
}
</script>
