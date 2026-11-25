<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The intake inbox: every document that arrived through a scanner, a shared
mailbox or digital post and does not belong to a record yet. A clerk assigns one
to a record or rejects it with a reason, and either way it leaves the inbox.

@visual exclude No pixel baseline CI runs yet: the page is driven through its /intake route by
	tests/e2e/workflows/intake-inbox.spec.ts (list, assign, reject, reject without a reason), which
	reaches it by URL rather than by component name. Its baseline in tests/e2e/visual is not collected
	by the CI Playwright project.

@spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
@spec openspec/changes/archive/2026-11-25-intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md
-->

<template>
	<div>
		<CnIndexPage
			:title="title"
			:description="description"
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
			<template #below-header>
				<NcNoteCard
					v-if="reachWarningText"
					type="warning"
					data-testid="intake-reach-warning">
					{{ reachWarningText }}
				</NcNoteCard>
				<NcNoteCard
					v-if="fillError"
					type="error"
					data-testid="intake-fill-error">
					{{ fillError }}
				</NcNoteCard>
				<div class="intake-modes">
					<NcButton
						:variant="mode === 'waiting' ? 'primary' : 'secondary'"
						data-testid="intake-mode-waiting"
						@click="setMode('waiting')">
						{{ t('filinq', 'Waiting') }}
					</NcButton>
					<NcButton
						:variant="mode === 'detached' ? 'primary' : 'secondary'"
						data-testid="intake-mode-detached"
						@click="setMode('detached')">
						{{ t('filinq', 'Taken off a record') }}
					</NcButton>
					<NcButton
						:variant="mode === 'overdue' ? 'primary' : 'secondary'"
						data-testid="intake-mode-overdue"
						@click="setMode('overdue')">
						{{ t('filinq', 'Overdue') }}
					</NcButton>
					<NcButton
						variant="tertiary"
						data-testid="intake-print-separators"
						@click="separatorsOpen = true">
						{{ t('filinq', 'Print separator sheets') }}
					</NcButton>
				</div>
			</template>

			<template #column-channel="{ row }">
				<CnStatusBadge
					:label="channelLabel(row.channel)"
					:colorMap="channelColorMap" />
			</template>

			<template #column-readingState="{ row }">
				<CnStatusBadge
					v-if="readingLabel(row.readingState)"
					:label="readingLabel(row.readingState)"
					:colorMap="readingColorMap"
					:title="row.readingError || ''" />
			</template>

			<template #column-acknowledgement="{ row }">
				<CnStatusBadge
					v-if="acknowledgementLabel(row.acknowledgement)"
					:label="acknowledgementLabel(row.acknowledgement)"
					:colorMap="acknowledgementColorMap"
					:title="acknowledgementDetail(row.acknowledgement)"
					data-testid="intake-acknowledgement" />
			</template>

			<template #column-receivedAt="{ row }">
				{{ formatDate(row.receivedAt) }}
			</template>

			<template #column-handlingDeadline="{ row }">
				<span v-if="row.handlingDeadline" class="intake-deadline" :title="row.deadlineReason || ''">
					{{ formatDay(row.handlingDeadline) }}
					<CnStatusBadge
						:label="countdownLabel(row.handlingDeadline, today)"
						:colorMap="{ [countdownLabel(row.handlingDeadline, today)]: countdownTone(row.handlingDeadline, today) }"
						data-testid="intake-countdown" />
				</span>
				<span v-else>-</span>
			</template>

			<template #column-duplicateOf="{ row }">
				<CnStatusBadge
					v-if="row.duplicateOf"
					:label="t('filinq', 'Possible duplicate')"
					:colorMap="{ [t('filinq', 'Possible duplicate')]: 'warning' }"
					:title="duplicateTitle(row)"
					data-testid="intake-duplicate-badge" />
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
					<NcActionButton
						v-if="canFill(row)"
						closeAfterClick
						data-testid="intake-fill-fields"
						@click="fill(row, row.assignedTo)">
						<template #icon>
							<FormTextbox :size="20" />
						</template>
						{{ t('filinq', 'Fill fields from document') }}
					</NcActionButton>
					<NcActionButton
						v-if="row.file"
						closeAfterClick
						data-testid="intake-open-notes"
						@click="openNotes(row)">
						<template #icon>
							<CommentTextOutline :size="20" />
						</template>
						{{ t('filinq', 'Notes') }}
					</NcActionButton>
					<NcActionButton
						v-if="mayRemovePassword(row)"
						closeAfterClick
						data-testid="intake-remove-password"
						@click="openUnlock(row)">
						<template #icon>
							<LockOpenVariant :size="20" />
						</template>
						{{ t('filinq', 'Remove password') }}
					</NcActionButton>
					<NcActionButton closeAfterClick @click="openReject(row)">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('filinq', 'Reject') }}
					</NcActionButton>
					<NcActionButton
						v-if="row.duplicateOf"
						closeAfterClick
						data-testid="intake-reject-duplicate"
						@click="rejectAsDuplicate(row)">
						<template #icon>
							<ContentDuplicate :size="20" />
						</template>
						{{ t('filinq', 'Reject as duplicate') }}
					</NcActionButton>
					<NcActionButton
						closeAfterClick
						data-testid="intake-change-deadline"
						@click="openDeadline(row)">
						<template #icon>
							<CalendarClock :size="20" />
						</template>
						{{ t('filinq', 'Change deadline') }}
					</NcActionButton>
					<NcActionButton
						v-if="stampedCopyUrl(row)"
						closeAfterClick
						data-testid="intake-open-stamped-copy"
						@click="openStampedCopy(row)">
						<template #icon>
							<FileDocumentOutline :size="20" />
						</template>
						{{ t('filinq', 'Open stamped copy') }}
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

		<FieldSuggestionDialog
			v-if="suggestion"
			:suggestion="suggestion"
			:saving="saving"
			:error="actionError"
			@confirm="decide"
			@cancel="closeDialogs" />

		<IntakeNotesDialog
			v-if="notesTarget"
			:document="notesTarget"
			@close="notesTarget = null" />

		<IntakeDeadlineDialog
			v-if="deadlineTarget"
			:document="deadlineTarget"
			:saving="saving"
			:error="actionError"
			@confirm="changeDeadline"
			@cancel="closeDialogs" />
		<!-- REQ-PPR-004: the document then names the copy and keeps what arrived. -->
		<PdfPasswordModal
			:show="unlockTarget !== null"
			:intakeUuid="unlockTarget ? unlockTarget.uuid : ''"
			@unlocked="load"
			@close="unlockTarget = null" />

		<SeparatorSheetsDialog
			v-if="separatorsOpen"
			@close="separatorsOpen = false" />

		<IntakeRoutingDeclaration />
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcActionButton, NcActions, NcButton, NcNoteCard } from '@nextcloud/vue'
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import CommentTextOutline from 'vue-material-design-icons/CommentTextOutline.vue'
import ContentDuplicate from 'vue-material-design-icons/ContentDuplicate.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import FormTextbox from 'vue-material-design-icons/FormTextbox.vue'
import LockOpenVariant from 'vue-material-design-icons/LockOpenVariant.vue'
import IntakeRoutingDeclaration from '../../components/intake/IntakeRoutingDeclaration.vue'
import FieldSuggestionDialog from '../../dialogs/FieldSuggestionDialog.vue'
import FinalDocumentReasonDialog from '../../dialogs/FinalDocumentReasonDialog.vue'
import IntakeAssignDialog from '../../dialogs/IntakeAssignDialog.vue'
import IntakeDeadlineDialog from '../../dialogs/IntakeDeadlineDialog.vue'
import IntakeNotesDialog from '../../dialogs/IntakeNotesDialog.vue'
import SeparatorSheetsDialog from '../../dialogs/SeparatorSheetsDialog.vue'
import PdfPasswordModal from '../../modals/PdfPasswordModal.vue'
import {
	decideFields,
	fetchFieldFillStatus,
	idOf,
	suggestFields,
} from '../../services/fieldFill.js'
import {
	acknowledgementColorMap,
	acknowledgementDetail,
	acknowledgementLabel,
	stampedCopyUrl,
} from '../../services/intakeAcknowledgement.js'
import {
	countdownLabel,
	countdownTone,
	localToday,
} from '../../services/intakeDeadline.js'
import {
	reachWarning,
	readingColorMap,
	readingLabel,
} from '../../services/intakeReading.js'
import {
	assignIntakeDocument,
	changeIntakeDeadline,
	fetchWaitingInbox,
	listDetachedDocuments,
	listOverdueDocuments,
	rejectIntakeDocument,
} from '../../services/intakeService.js'
import { mayRemovePassword } from '../../services/pdfPassword.js'

export default {
	name: 'IntakeIndex',
	components: {
		NcNoteCard,
		CnIndexPage,
		CnStatusBadge,
		NcButton,
		CommentTextOutline,
		CalendarClock,
		ContentDuplicate,
		Delete,
		DotsHorizontal,
		FieldSuggestionDialog,
		FileDocumentOutline,
		FinalDocumentReasonDialog,
		FolderOutline,
		FormTextbox,
		IntakeAssignDialog,
		IntakeNotesDialog,
		IntakeDeadlineDialog,
		IntakeRoutingDeclaration,
		SeparatorSheetsDialog,
		PdfPasswordModal,
		LockOpenVariant,
		NcActionButton,
		NcActions,
	},

	data() {
		return {
			documents: [],
			mode: 'waiting',
			loading: false,
			refreshing: false,
			saving: false,
			assignTarget: null,
			rejectTarget: null,
			notesTarget: null,
			deadlineTarget: null,
			today: localToday(),
			unlockTarget: null,
			actionError: '',
			loadError: '',
			notificationReach: null,
			separatorsOpen: false,
			fieldFillAvailable: false,
			suggestion: null,
			fillError: '',
			readingColorMap: readingColorMap(),
			acknowledgementColorMap: acknowledgementColorMap(),
			channelColorMap: {
				[t('filinq', 'Scan')]: 'primary',
				[t('filinq', 'Mail')]: 'warning',
				[t('filinq', 'Digital post')]: 'default',
			},
		}
	},

	computed: {
		/**
		 * The warning shown when a failed reading would reach nobody.
		 *
		 * @return {string} The warning, or '' when somebody will be told.
		 * @spec openspec/changes/archive/2026-10-09-intake-failure-reaches-someone/tasks.md#task-3.3
		 */
		reachWarningText() {
			return reachWarning(this.notificationReach)
		},

		title() {
			if (this.mode === 'overdue') {
				return t('filinq', 'Overdue')
			}
			return this.mode === 'detached'
				? t('filinq', 'Taken off a record')
				: t('filinq', 'Intake')
		},

		description() {
			if (this.mode === 'overdue') {
				return t(
					'filinq',
					'Documents past their handling deadline that are not handled yet, the longest overdue first.',
				)
			}
			if (this.mode === 'detached') {
				return t(
					'filinq',
					'Documents somebody took off the record they were filed on, with the reason they gave. They are waiting again.',
				)
			}
			return t(
				'filinq',
				'Documents that arrived before anyone knew which record they belong to. Assign one to a record, or reject it with a reason.',
			)
		},

		/**
		 * The inbox columns, with the reading state of an arriving scan and
		 * whether the sender was told it arrived.
		 *
		 * @return {Array<object>} The columns.
		 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-2.2
		 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
		 */
		tableColumns() {
			const columns = [
				{ key: 'channel', label: t('filinq', 'Channel'), sortable: true },
				{ key: 'sender', label: t('filinq', 'Sender'), sortable: true },
				{ key: 'subject', label: t('filinq', 'Subject'), sortable: true },
				{
					key: 'receivedAt',
					label: t('filinq', 'Received'),
					sortable: true,
				},
				{
					key: 'handlingDeadline',
					label: t('filinq', 'Deadline'),
					sortable: true,
				},
				{ key: 'duplicateOf', label: t('filinq', 'Duplicate') },
				{ key: 'readingState', label: t('filinq', 'Text') },
				{ key: 'acknowledgement', label: t('filinq', 'Acknowledged') },
			]
			if (this.mode === 'detached') {
				columns.push({ key: 'detachReason', label: t('filinq', 'Reason') })
				columns.push({
					key: 'detachedBy',
					label: t('filinq', 'Taken off by'),
				})
			}
			return columns
		},

		emptyText() {
			if (this.loadError) {
				return this.loadError
			}
			if (this.mode === 'detached') {
				return t('filinq', 'No document has been taken off a record.')
			}
			if (this.mode === 'overdue') {
				return t('filinq', 'Nothing is overdue.')
			}
			return t(
				'filinq',
				'Nothing is waiting. Every document that arrived has a record.',
			)
		},

		rejectExplanation() {
			const subject =
				this.rejectTarget?.subject || t('filinq', 'this document')
			return t(
				'filinq',
				'Say why "{subject}" does not belong here. The reason stays with the document.',
				{ subject },
			)
		},
	},

	/**
	 * Load the intake list and the field fill status.
	 *
	 * @return {void}
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	mounted() {
		this.load()
		this.loadFieldFill()
	},

	methods: {
		t,

		/**
		 * Switch between what is waiting and what came back off a record.
		 *
		 * @param {string} mode Either 'waiting' or 'detached'.
		 * @return {Promise<void>} Resolves once the list has settled.
		 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
		 */
		async setMode(mode) {
			this.mode = mode
			this.closeDialogs()
			await this.load()
		},

		readingLabel,
		countdownLabel,
		countdownTone,

		/**
		 * A deadline date in the browser's own format.
		 *
		 * @param {string} value The date (Y-m-d).
		 * @return {string} The date as the reader writes it.
		 */
		formatDay(value) {
			const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ''))
			if (!match) {
				return value || '-'
			}
			return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])).toLocaleDateString()
		},

		/**
		 * The tooltip of a possible duplicate, naming the document it arrived as before.
		 *
		 * @param {object} row The possible duplicate.
		 * @return {string} The tooltip.
		 */
		duplicateTitle(row) {
			return t('filinq', 'The same file arrived before as "{subject}"', { subject: this.firstSubject(row) })
		},

		/**
		 * The subject of the document a possible duplicate arrived as before.
		 *
		 * @param {object} row The possible duplicate.
		 * @return {string} The first document's subject, or its id when it is not in this list.
		 * @spec openspec/changes/archive/2026-11-25-intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-a-registrar-resolves-a-duplicate-in-one-step-req-iws-004
		 */
		firstSubject(row) {
			const first = this.documents.find((document) => document.uuid === row.duplicateOf)
			return first?.subject || row.duplicateOf
		},

		acknowledgementLabel,

		acknowledgementDetail,

		stampedCopyUrl,

		/**
		 * Open the copy with the received stamp in a new tab. The arrived file
		 * stays as it arrived.
		 *
		 * @param {object} row The intake document.
		 * @return {void}
		 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
		 */
		openStampedCopy(row) {
			const url = stampedCopyUrl(row)
			if (url) {
				window.open(url, '_blank', 'noopener')
			}
		},

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
		 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
		 */
		async load() {
			this.loading = true
			this.loadError = ''
			try {
				if (this.mode === 'detached') {
					this.documents = await listDetachedDocuments()
					this.notificationReach = null
				} else if (this.mode === 'overdue') {
					this.documents = await listOverdueDocuments()
					this.notificationReach = null
				} else {
					const inbox = await fetchWaitingInbox()
					this.documents = inbox.results
					this.notificationReach = inbox.notificationReach
				}
			} catch {
				this.documents = []
				this.notificationReach = null
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

		mayRemovePassword,

		/**
		 * Ask for the password of a row's locked PDF.
		 *
		 * @param {object} row The intake document.
		 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
		 */
		openUnlock(row) {
			this.closeDialogs()
			this.unlockTarget = row
		},

		/**
		 * Open the notes of one waiting document.
		 *
		 * @param {object} row The intake document.
		 * @return {void}
		 * @spec openspec/changes/work-document-notes/specs/document-notes/spec.md#requirement-notes-sit-beside-the-document-on-filinqs-screens-req-wdn-002
		 */
		openNotes(row) {
			this.assignTarget = null
			this.rejectTarget = null
			this.notesTarget = row
		},

		openReject(row) {
			this.actionError = ''
			this.assignTarget = null
			this.rejectTarget = row
		},

		openDeadline(row) {
			this.actionError = ''
			this.assignTarget = null
			this.rejectTarget = null
			this.deadlineTarget = row
		},

		/**
		 * Close every open dialog, the field suggestion dialog included.
		 *
		 * @return {void}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		closeDialogs() {
			this.assignTarget = null
			this.rejectTarget = null
			this.deadlineTarget = null
			this.suggestion = null
			this.actionError = ''
		},

		/**
		 * Whether model filling is on and a text model is there; the action
		 * is not shown otherwise (REQ-EMF-004).
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async loadFieldFill() {
			try {
				this.fieldFillAvailable = (await fetchFieldFillStatus())?.available === true
			} catch {
				this.fieldFillAvailable = false
			}
		},

		/**
		 * Whether a row's document can fill the record it is on.
		 *
		 * @param {object} row The intake document.
		 * @return {boolean}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		canFill(row) {
			return this.fieldFillAvailable && this.mode === 'waiting' && Boolean(row?.file) && onRecord(row?.assignedTo)
		},

		/**
		 * Offer the fields of a document that was just assigned to a record
		 * (REQ-EMF-003); quiet when the schema has no profile.
		 *
		 * @param {object} document The intake document.
		 * @param {{register: string, schema: string, id: string}} target The record.
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async offerFields(document, target) {
			if (this.fieldFillAvailable && document?.file) {
				await this.fill(document, target, true)
			}
		},

		/**
		 * Ask the model for the record's declared fields from a document.
		 *
		 * @param {object} row The intake document.
		 * @param {{register: string, schema: string, id: string}} target The record it is on.
		 * @param {boolean} [offered] Offered after an assign: a schema without a
		 *     profile, or no model, then says nothing.
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async fill(row, target, offered = false) {
			this.fillError = ''
			this.actionError = ''
			try {
				this.suggestion = await suggestFields({
					fileId: row.file,
					register: target.register,
					schema: target.schema,
					objectId: target.id,
				})
			} catch (error) {
				this.suggestion = null
				if (offered && [409, 503].includes(error?.response?.status)) {
					return
				}
				this.fillError = error?.response?.data?.error
					|| t('filinq', 'The fields could not be filled from the document.')
			}
		},

		/**
		 * Write the clerk's decisions to the record.
		 *
		 * @param {object} decisions Per field `{decision, value?}`.
		 * @return {Promise<void>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
		 */
		async decide(decisions) {
			const id = idOf(this.suggestion)
			if (!id) {
				return
			}
			this.saving = true
			this.actionError = ''
			try {
				await decideFields(id, decisions)
				this.closeDialogs()
			} catch (error) {
				this.actionError = error?.response?.data?.error
					|| t('filinq', 'The decisions could not be saved.')
			} finally {
				this.saving = false
			}
		},

		/**
		 * Move the open document's deadline.
		 *
		 * @param {object} change The new deadline and the reason.
		 * @return {Promise<void>} Resolves once the server has answered.
		 * @spec openspec/changes/archive/2026-11-25-intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-every-waiting-document-has-a-handling-deadline-req-iws-001
		 */
		async changeDeadline(change) {
			const uuid = this.deadlineTarget?.uuid
			if (!uuid) {
				return
			}
			this.saving = true
			this.actionError = ''
			try {
				await changeIntakeDeadline(uuid, change.deadline, change.reason)
				this.closeDialogs()
				await this.load()
			} catch (error) {
				this.actionError =
					error?.response?.data?.error
					|| t('filinq', 'The deadline was not changed.')
			} finally {
				this.saving = false
			}
		},

		/**
		 * Reject a possible duplicate through the existing reject, naming the first document.
		 *
		 * @param {object} row The possible duplicate.
		 * @return {Promise<void>} Resolves once the server has answered.
		 * @spec openspec/changes/archive/2026-11-25-intake-worklist-deadline-and-duplicates/specs/intake-worklist-signals/spec.md#requirement-a-registrar-resolves-a-duplicate-in-one-step-req-iws-004
		 */
		async rejectAsDuplicate(row) {
			this.rejectTarget = row
			await this.reject(t('filinq', 'Duplicate of {subject}', { subject: this.firstSubject(row) }))
		},

		/**
		 * Assign the open document to the picked record.
		 *
		 * @param {object} target The record, as register, schema and id.
		 * @return {Promise<void>} Resolves once the server has answered.
		 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
		 */
		async assign(target) {
			const uuid = this.assignTarget?.uuid
			if (!uuid) {
				return
			}
			this.saving = true
			this.actionError = ''
			try {
				const document = this.assignTarget
				await assignIntakeDocument(uuid, target)
				this.closeDialogs()
				await this.load()
				await this.offerFields(document, target)
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
		 * @spec openspec/changes/archive/2026-11-25-document-intake-inbox/specs/document-intake-inbox/spec.md
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
					error?.response?.data?.error
					|| t('filinq', 'The rejection was refused.')
			} finally {
				this.saving = false
			}
		},
	},
}
/**
 * Whether a target names a whole record.
 *
 * @param {object} target `{register, schema, id}`.
 * @return {boolean}
 */
function onRecord(target) {
	return Boolean(target?.register && target?.schema && target?.id)
}
</script>

<style scoped>
.intake-modes {
	display: flex;
	gap: 8px;
	margin-bottom: 12px;
}

.intake-deadline {
	display: inline-flex;
	align-items: center;
	gap: 6px;
}
</style>
