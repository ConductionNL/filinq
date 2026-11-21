<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The archivist's review of one destruction list (vernietigingslijst): the
records OpenRegister proposes to destroy, and the decision. The archivist
approves the list in full, keeps records off it with a reason each and
approves the rest, or rejects it with a reason.

Every call is OpenRegister's own `api/archival/destruction-lists*`; who may
review is OpenRegister's archivist check, so a 403 is shown as it comes.

@spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
-->

<template>
	<section
		class="destruction-list-review"
		:aria-label="t('filinq', 'Destruction list')"
		data-testid="destruction-list-review">
		<NcLoadingIcon v-if="loading && !list" :size="32" />
		<NcNoteCard v-else-if="!list" type="error">
			{{ error || t('filinq', 'The destruction lists could not be read.') }}
		</NcNoteCard>
		<template v-else>
			<p data-testid="destruction-list-status">
				{{
					t('filinq', 'Status: {status}', { status: statusLabel(status) })
				}}
			</p>

			<CnDataTable
				:columns="entryColumns"
				:rows="entries"
				rowKey="uuid"
				:tableLabel="t('filinq', 'Records proposed for destruction')"
				:emptyText="t('filinq', 'This list proposes no records.')"
				data-testid="destruction-list-entries">
				<template #column-exclusion="{ row }">
					<template v-if="exclusions[row.uuid]">
						<span class="destruction-list-review__kept">
							{{
								t('filinq', 'Kept: {reason}', {
									reason: exclusions[row.uuid],
								})
							}}
						</span>
						<NcButton
							v-if="decidable"
							variant="tertiary"
							@click="include(row.uuid)">
							{{ t('filinq', 'Put back on the list') }}
						</NcButton>
					</template>
					<NcButton
						v-else-if="decidable"
						variant="secondary"
						@click="excluding = row">
						{{ t('filinq', 'Keep this record') }}
					</NcButton>
				</template>
			</CnDataTable>

			<div v-if="decidable" class="destruction-list-review__actions">
				<NcButton
					variant="primary"
					:disabled="saving"
					data-testid="destruction-list-approve"
					@click="approve">
					{{ approveLabel }}
				</NcButton>
				<NcButton
					variant="error"
					:disabled="saving"
					data-testid="destruction-list-reject"
					@click="rejecting = true">
					{{ t('filinq', 'Reject list') }}
				</NcButton>
			</div>
		</template>

		<RequiredReasonModal
			v-if="excluding"
			:dialogTitle="t('filinq', 'Keep {title}', { title: excluding.title })"
			:label="t('filinq', 'Why is this record kept?')"
			:description="t('filinq', 'The reason is recorded with the approval.')"
			:submit="exclude"
			@done="excluding = null"
			@close="excluding = null" />

		<RequiredReasonModal
			v-if="rejecting"
			:dialogTitle="t('filinq', 'Reject destruction list')"
			:label="t('filinq', 'Why is the list rejected?')"
			:description="t('filinq', 'Nothing on a rejected list is destroyed.')"
			:submit="reject"
			@done="rejecting = false"
			@close="rejecting = false" />
	</section>
</template>

<script>
import { CnDataTable } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import RequiredReasonModal from '../../modals/RequiredReasonModal.vue'
import {
	approveDestructionList,
	entryRows,
	isDecidable,
	listStatus,
	loadDestructionList,
	rejectDestructionList,
} from '../../services/destructionLists.js'

/**
 * The label of a destruction list status.
 *
 * @param {string} status The status.
 * @return {string} The label.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function destructionStatusLabel(status) {
	const labels = {
		in_review: t('filinq', 'In review'),
		awaiting_second_approval: t('filinq', 'Awaiting second approval'),
		approved: t('filinq', 'Approved'),
		rejected: t('filinq', 'Rejected'),
		completed: t('filinq', 'Completed'),
		executing: t('filinq', 'Executing'),
		executed: t('filinq', 'Executed'),
	}
	return labels[status] || status || t('filinq', 'Unknown')
}

/**
 * A date as the user's locale writes it.
 *
 * @param {string} value An ISO date or date-time.
 * @return {string} The date, or empty.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */
export function localDate(value) {
	const date = value ? new Date(value) : null
	return date && !Number.isNaN(date.getTime()) ? date.toLocaleDateString() : ''
}

export default {
	name: 'DestructionListReview',
	components: {
		CnDataTable,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		RequiredReasonModal,
	},

	props: {
		/** The uuid of the list under review (`@objectId`); empty reads the route's `:id`. */
		objectId: { type: String, default: '' },
	},

	data() {
		return {
			list: null,
			exclusions: {},
			excluding: null,
			rejecting: false,
			loading: false,
			saving: false,
			error: '',
		}
	},

	computed: {
		/**
		 * @return {string} The uuid of the list under review.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		listId() {
			return this.objectId || this.$route?.params?.id || ''
		},

		/**
		 * @return {Array<object>} The columns of the proposed records.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		entryColumns() {
			return [
				{ key: 'title', label: t('filinq', 'Title') },
				{ key: 'schema', label: t('filinq', 'Schema') },
				{ key: 'register', label: t('filinq', 'Register') },
				{ key: 'actionDate', label: t('filinq', 'Archive action date') },
				{
					key: 'classification',
					label: t('filinq', 'Selection list category'),
				},
				{ key: 'exclusion', label: t('filinq', 'Decision') },
			]
		},

		/**
		 * @return {Array<object>} The proposed records, the action date in the user's locale.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		entries() {
			return entryRows(this.list).map((row) => ({
				...row,
				actionDate: localDate(row.archiefactiedatum),
			}))
		},

		/**
		 * @return {string} The status of the list.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		status() {
			return listStatus(this.list)
		},

		/**
		 * @return {boolean} Whether the list still waits for a decision.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		decidable() {
			return isDecidable(this.status)
		},

		/**
		 * @return {string} "Approve list", or "Approve the rest" once a record is kept.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		approveLabel() {
			return Object.keys(this.exclusions).length > 0
				? t('filinq', 'Approve the rest')
				: t('filinq', 'Approve list')
		},
	},

	watch: {
		/**
		 * Read the list again when the page moves to another one.
		 *
		 * @return {void}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		listId() {
			this.load()
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		statusLabel: destructionStatusLabel,

		/**
		 * Read the list under review.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		async load() {
			this.loading = true
			this.error = ''
			const result = await loadDestructionList(this.listId)
			this.loading = false
			this.exclusions = {}
			if (!result.ok) {
				this.list = null
				this.error = result.error
				return
			}
			this.list = result.data
		},

		/**
		 * Keep one record off the list, with its reason. Nothing is sent yet;
		 * the kept records go with the approval.
		 *
		 * @param {string} reason Why the record is kept.
		 * @return {Promise<{ok: boolean}>} Always ok: the dialog requires the reason.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		async exclude(reason) {
			this.exclusions = { ...this.exclusions, [this.excluding.uuid]: reason }
			return { ok: true }
		},

		/**
		 * Put a kept record back on the list.
		 *
		 * @param {string} uuid The record's uuid.
		 * @return {void}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		include(uuid) {
			const rest = { ...this.exclusions }
			delete rest[uuid]
			this.exclusions = rest
		},

		/**
		 * Approve the list: in full, or the rest with the kept records and their reasons.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		async approve() {
			this.saving = true
			const result = await approveDestructionList(
				this.listId,
				Object.keys(this.exclusions),
				this.exclusions,
			)
			this.saving = false
			if (!result.ok) {
				showError(
					result.error || t('filinq', 'The list could not be approved.'),
				)
				return
			}
			this.list = { ...this.list, ...result.data }
			this.exclusions = {}
			showSuccess(
				t('filinq', 'Destruction list: {status}', {
					status: destructionStatusLabel(this.status),
				}),
			)
		},

		/**
		 * Reject the list with the reason the dialog asked for.
		 *
		 * @param {string} reason Why the list is rejected.
		 * @return {Promise<object>} OpenRegister's answer, for the dialog.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		async reject(reason) {
			const result = await rejectDestructionList(this.listId, reason)
			if (result.ok) {
				this.list = { ...this.list, ...result.data }
				this.exclusions = {}
			}
			return result
		},
	},
}
</script>

<style scoped>
.destruction-list-review__kept {
	color: var(--color-text-maxcontrast);
	margin-inline-end: calc(var(--default-grid-baseline) * 2);
}

.destruction-list-review__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 4);
}
</style>
