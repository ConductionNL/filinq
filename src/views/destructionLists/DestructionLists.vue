<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Destruction lists (vernietigingslijsten): the lists OpenRegister draws up of
records whose retention has run out, and the archivist's review of one list.
The archivist approves a list in full, takes objects off it with a reason
each and approves the rest, or rejects it with a reason.

Everything is read and written through OpenRegister's own
`api/archival/destruction-lists*` routes; who may review is OpenRegister's
archivist check, so a 403 is shown as it comes, not hidden by the page.

@spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
@visual exclude No pixel baseline yet: the page needs a destruction list drawn up by OpenRegister on a
	seeded instance; tests/e2e/workflows/archiefwet-retention.spec.ts drives it there.
-->

<template>
	<div class="destruction-lists">
		<CnIndexPage
			v-if="!listId"
			:title="t('filinq', 'Destruction lists')"
			:description="
				t(
					'filinq',
					'Records whose retention period has ended are proposed for destruction here. Nothing is destroyed until an archivist approves the list.',
				)
			"
			:showTitle="true"
			:objects="rows"
			:columns="indexColumns"
			:loading="loading"
			v-bind="$options.listOnlyIndexPage"
			:showAdd="false"
			:rowClickToView="true"
			rowKey="uuid"
			:emptyText="t('filinq', 'No destruction lists.')"
			data-testid="destruction-lists-index"
			@rowClick="open"
			@refresh="load">
			<template #below-header>
				<NcNoteCard v-if="error" type="error">
					{{ error }}
				</NcNoteCard>
				<NcNoteCard v-else-if="!configured" type="warning">
					{{
						t(
							'filinq',
							'OpenRegister has no register for destruction lists yet. An administrator links it under Archiefbeheer in the settings.',
						)
					}}
				</NcNoteCard>
				<NcSelect
					v-model="statusFilter"
					class="destruction-lists__filter"
					:inputLabel="t('filinq', 'Status')"
					:options="statusOptions"
					label="label"
					:reduce="(option) => option.id"
					@update:modelValue="load" />
			</template>

			<template #column-status="{ row }">
				{{ statusLabel(row.status) }}
			</template>

			<template #column-createdAt="{ row }">
				{{ formatDate(row.createdAt) }}
			</template>
		</CnIndexPage>

		<section
			v-else
			class="destruction-lists__detail"
			:aria-label="t('filinq', 'Destruction list')"
			data-testid="destruction-list-detail">
			<NcButton variant="tertiary" @click="back">
				{{ t('filinq', 'All destruction lists') }}
			</NcButton>
			<h2>{{ t('filinq', 'Destruction list') }}</h2>
			<p data-testid="destruction-list-status">
				{{
					t('filinq', 'Status: {status}', { status: statusLabel(status) })
				}}
			</p>

			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<NcLoadingIcon v-if="loading" :size="32" />

			<CnDataTable
				v-else
				:columns="entryColumns"
				:rows="entries"
				rowKey="uuid"
				:tableLabel="t('filinq', 'Records proposed for destruction')"
				:emptyText="t('filinq', 'This list proposes no records.')"
				data-testid="destruction-list-entries">
				<template #column-archiefactiedatum="{ row }">
					{{ formatDate(row.archiefactiedatum) }}
				</template>
				<template #column-exclusion="{ row }">
					<template v-if="exclusions[row.uuid]">
						<span class="destruction-lists__excluded">
							{{
								t('filinq', 'Kept: {reason}', {
									reason: exclusions[row.uuid],
								})
							}}
						</span>
						<NcButton
							v-if="decidable"
							variant="tertiary"
							:data-testid="'destruction-list-include-' + row.uuid"
							@click="include(row.uuid)">
							{{ t('filinq', 'Put back on the list') }}
						</NcButton>
					</template>
					<NcButton
						v-else-if="decidable"
						variant="secondary"
						:data-testid="'destruction-list-exclude-' + row.uuid"
						@click="excluding = row">
						{{ t('filinq', 'Keep this record') }}
					</NcButton>
				</template>
			</CnDataTable>

			<div v-if="decidable && !loading" class="destruction-lists__actions">
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
		</section>

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
	</div>
</template>

<script>
import { CnDataTable, CnIndexPage } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard, NcSelect } from '@nextcloud/vue'
import RequiredReasonModal from '../../modals/RequiredReasonModal.vue'
import { LIST_ONLY_INDEX_PAGE } from '../../constants/indexPage.js'
import {
	approveDestructionList,
	DESTRUCTION_LIST_STATUSES,
	entryRows,
	isDecidable,
	listRows,
	listsConfigured,
	listStatus,
	loadDestructionList,
	loadDestructionLists,
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

export default {
	name: 'DestructionLists',
	components: {
		CnDataTable,
		CnIndexPage,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		RequiredReasonModal,
	},

	listOnlyIndexPage: LIST_ONLY_INDEX_PAGE,

	data() {
		return {
			rows: [],
			configured: true,
			statusFilter: '',
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
		 * @return {string} The uuid of the list on screen, empty on the overview.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		listId() {
			return this.$route?.params?.id || ''
		},

		/**
		 * @return {Array<object>} The overview's columns.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		indexColumns() {
			return [
				{ key: 'status', label: t('filinq', 'Status') },
				{ key: 'objectCount', label: t('filinq', 'Records') },
				{ key: 'createdAt', label: t('filinq', 'Created') },
			]
		},

		/**
		 * @return {Array<object>} The detail's columns.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		entryColumns() {
			return [
				{ key: 'title', label: t('filinq', 'Title') },
				{ key: 'schema', label: t('filinq', 'Schema') },
				{ key: 'register', label: t('filinq', 'Register') },
				{
					key: 'archiefactiedatum',
					label: t('filinq', 'Archive action date'),
				},
				{
					key: 'classification',
					label: t('filinq', 'Selection list category'),
				},
				{ key: 'exclusion', label: t('filinq', 'Decision') },
			]
		},

		/**
		 * @return {Array<object>} The status filter's choices, every status first.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		statusOptions() {
			return [
				{ id: '', label: t('filinq', 'Every status') },
				...DESTRUCTION_LIST_STATUSES.map((id) => ({
					id,
					label: destructionStatusLabel(id),
				})),
			]
		},

		/**
		 * @return {Array<object>} The proposed records of the list on screen.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		entries() {
			return entryRows(this.list)
		},

		/**
		 * @return {string} The status of the list on screen.
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
		 * A date as the user's locale writes it.
		 *
		 * @param {string} value An ISO date or date-time.
		 * @return {string} The date, or empty.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		formatDate(value) {
			const date = value ? new Date(value) : null
			return date && !Number.isNaN(date.getTime())
				? date.toLocaleDateString()
				: ''
		},

		/**
		 * Read the overview, or the list the route names.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		async load() {
			this.loading = true
			this.error = ''
			const result = this.listId
				? await loadDestructionList(this.listId)
				: await loadDestructionLists(this.statusFilter)
			this.loading = false
			if (!result.ok) {
				this.error =
					result.error
					|| t('filinq', 'The destruction lists could not be read.')
				return
			}
			if (this.listId) {
				this.list = result.data
				this.exclusions = {}
				return
			}
			this.configured = listsConfigured(result.data)
			this.rows = listRows(result.data)
		},

		/**
		 * Open one list.
		 *
		 * @param {object} row The overview row.
		 * @return {void}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		open(row) {
			this.$router.push({ name: 'DestructionLists', params: { id: row.uuid } })
		},

		/**
		 * Back to the overview.
		 *
		 * @return {void}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
		 */
		back() {
			this.list = null
			this.$router.push({ name: 'DestructionLists' })
		},

		/**
		 * Keep one record off the list, with its reason. Nothing is sent yet;
		 * the exclusions go with the approval.
		 *
		 * @param {string} reason Why the record is kept.
		 * @return {Promise<{ok: boolean}>} Always ok: the reason is required by the dialog.
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
.destruction-lists__detail {
	padding: calc(var(--default-grid-baseline) * 4);
}

.destruction-lists__filter {
	max-width: 320px;
}

.destruction-lists__excluded {
	color: var(--color-text-maxcontrast);
	margin-inline-end: calc(var(--default-grid-baseline) * 2);
}

.destruction-lists__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 4);
}
</style>
