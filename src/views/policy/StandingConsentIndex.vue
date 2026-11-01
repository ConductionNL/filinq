<script setup>
import { translate as t } from '@nextcloud/l10n'
import { standingConsentStore } from '../../store/store.js'
</script>

<template>
	<div>
		<CnIndexPage
			ref="indexPage"
			:title="t('filinq', 'Publish always')"
			:description="
				t(
					'filinq',
					'Entity-level allow rules. A matched entity may be published without per-document objection workflow, unless a publish-never rule also matches.',
				)
			"
			:showTitle="true"
			:objects="standingConsentStore.standingConsents"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="standingConsentStore.loading"
			:selectable="false"
			:showEditAction="false"
			:showCopyAction="false"
			:showDeleteAction="false"
			:showMassImport="false"
			:showMassExport="false"
			:showMassCopy="false"
			:showMassDelete="false"
			:showViewToggle="false"
			:showAdd="true"
			rowKey="id"
			:emptyText="emptyText"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@pageChanged="onPageChanged"
			@pageSizeChanged="onPageSizeChanged"
			@add="openCreateDialog">
			<!-- `below-header`, not `above-table` — CnIndexPage defines no
			     `above-table` slot and Vue drops an unmatched named slot
			     silently, so these stats rendered nothing at all. -->
			<template #below-header>
				<div class="policy-stats">
					<CnStatsBlock
						:title="t('filinq', 'Total')"
						:count="standingConsentStore.standingConsentStats.total"
						:countLabel="t('filinq', 'rules')"
						variant="default"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('filinq', 'Active')"
						:count="standingConsentStore.standingConsentStats.active"
						:countLabel="t('filinq', 'active')"
						variant="success"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('filinq', 'Inactive')"
						:count="standingConsentStore.standingConsentStats.inactive"
						:countLabel="t('filinq', 'inactive')"
						variant="default"
						horizontal
						showZeroCount />
				</div>
			</template>

			<template #column-entityType="{ row }">
				<CnStatusBadge
					:label="row.entityType || t('filinq', 'Unknown')"
					:colorMap="entityTypeColorMap" />
			</template>

			<template #column-consentMethod="{ row }">
				<CnStatusBadge
					:label="row.consentMethod || '-'"
					:colorMap="methodColorMap" />
			</template>

			<template #column-active="{ row }">
				<CnStatusBadge
					:label="
						row.active === false
							? t('filinq', 'Inactive')
							: t('filinq', 'Active')
					"
					:colorMap="activeColorMap" />
			</template>

			<template #column-matchRules="{ row }">
				{{ formatMatchRules(row.matchRules) }}
			</template>

			<template #row-actions="{ row }">
				<NcActions>
					<template #icon>
						<DotsHorizontal :size="20" />
					</template>
					<NcActionButton closeAfterClick @click="openEditDialog(row)">
						<template #icon>
							<Pencil :size="20" />
						</template>
						{{ t('filinq', 'Edit') }}
					</NcActionButton>
					<NcActionButton closeAfterClick @click="confirmDelete(row)">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('filinq', 'Delete') }}
					</NcActionButton>
				</NcActions>
			</template>
		</CnIndexPage>

		<!--
			ADR-004 gate-13: modal lives in its own component, not inline.
			StandingConsentFormModal handles its own form state; we only own
			the open flag, the record being edited, and the save outcome.
			(Previously an inline NcDialog duplicated this exact form —
			replaced here; openspec/changes/orphaned-surface-restoration.)
		-->
		<StandingConsentFormModal
			:open="dialogOpen"
			:editingRecord="editingRecord"
			:saving="saving"
			:formError="formError"
			@update:open="dialogOpen = $event"
			@submit="onModalSubmit"
			@cancel="dialogOpen = false" />

		<!--
			Delete confirmation. Replaces window.confirm(): the deletion below
			runs only from @confirm, so an explicit confirmation is still
			required before anything is removed.
		-->
		<ConfirmActionDialog
			v-if="deleteTarget"
			:name="t('filinq', 'Delete standing consent')"
			:message="deleteMessage"
			:busy="deleting"
			@confirm="executeDelete"
			@cancel="cancelDelete" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatsBlock, CnStatusBadge } from '@conduction/nextcloud-vue'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import StandingConsentFormModal from '../../dialogs/StandingConsentFormModal.vue'
import {
	indexPaging,
	recordDeletion,
	recordFormDialog,
} from '../../mixins/recordIndexPage.js'

export default {
	name: 'StandingConsentIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		StandingConsentFormModal,
		ConfirmActionDialog,
		Delete,
		DotsHorizontal,
		Pencil,
	},

	mixins: [indexPaging, recordFormDialog, recordDeletion],
	deleteFailedLog: 'Failed to delete standing consent:',

	data() {
		return {
			entityTypeColorMap: {
				PERSON: 'warning',
				ORGANIZATION: 'primary',
				OTHER: 'default',
			},

			methodColorMap: {
				paper: 'default',
				digital_signature: 'primary',
				verbal_recorded: 'warning',
				opt_in_form: 'success',
			},

			activeColorMap: {
				[t('filinq', 'Active')]: 'success',
				[t('filinq', 'Inactive')]: 'default',
			},
		}
	},

	computed: {
		/**
		 * CnDataTable column set for the Standing Publication Consents surface.
		 *
		 * @return {object[]}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		tableColumns() {
			return [
				{
					key: 'entityText',
					label: t('filinq', 'Entity'),
					sortable: true,
				},
				{ key: 'entityType', label: t('filinq', 'Type'), sortable: true },
				{ key: 'matchRules', label: t('filinq', 'Match rules') },
				{
					key: 'consentMethod',
					label: t('filinq', 'Method'),
					sortable: true,
				},
				{
					key: 'validUntil',
					label: t('filinq', 'Valid until'),
					sortable: true,
				},
				{ key: 'active', label: t('filinq', 'Status'), sortable: true },
			]
		},

		paginationData() {
			const total = standingConsentStore.standingConsents.length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},

		/**
		 * Empty-state text for the Standing Publication Consents surface — the
		 * store's error when loading failed, otherwise the no-records message.
		 *
		 * @return {string}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		emptyText() {
			if (standingConsentStore.error) {
				return standingConsentStore.error
			}
			return t('filinq', 'No standing publication consents defined.')
		},

		/**
		 * Body text of the delete confirmation dialog.
		 *
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		deleteMessage() {
			const name =
				this.deleteTarget?.entityText || t('filinq', 'this standing consent')
			return t('filinq', 'Delete "{name}"? This cannot be undone.', { name })
		},
	},

	mounted() {
		standingConsentStore.fetchStandingConsents()
	},

	methods: {
		/**
		 * Load the standing consent list from the store.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		reloadRecords() {
			return standingConsentStore.fetchStandingConsents()
		},

		/**
		 * Create a standing consent from the form dialog's payload.
		 *
		 * @param {object} formData The submitted form.
		 * @return {Promise<object>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
		 */
		createRecord(formData) {
			return standingConsentStore.createStandingConsent(formData)
		},

		/**
		 * Update a standing consent from the form dialog's payload.
		 *
		 * @param {string} id The record id.
		 * @param {object} formData The submitted form.
		 * @return {Promise<object>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
		 */
		updateRecord(id, formData) {
			return standingConsentStore.updateStandingConsent(id, formData)
		},

		/**
		 * Delete a standing consent by id.
		 *
		 * @param {string} id The record id.
		 * @return {Promise<void>}
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		deleteRecord(id) {
			return standingConsentStore.deleteStandingConsent(id)
		},

		/**
		 * The form values the standing-consent modal starts from for a row.
		 *
		 * @param {object} row The standing-consent object to edit.
		 * @return {object}
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		toEditingRecord(row) {
			return {
				entityText: row.entityText || '',
				entityType: row.entityType || 'PERSON',
				consentMethod: row.consentMethod || '',
				consentDocument: row.consentDocument || '',
				consentScope: row.consentScope || '',
				legalBasis: row.legalBasis || '',
				validFrom: row.validFrom || '',
				validUntil: row.validUntil || '',
				active: row.active !== false,
				matchRules: Array.isArray(row.matchRules)
					? row.matchRules.map((r) => ({ type: r.type, value: r.value }))
					: [],

				consentStatus: row.consentStatus || 'consent_given',
				publicationDecision:
					row.publicationDecision || 'publish_with_consent',

				notificationStatus: row.notificationStatus || 'skipped',
			}
		},
	},
}
</script>

<style scoped>
.policy-stats {
	display: flex;
	gap: 16px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}
</style>
