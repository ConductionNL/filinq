<!--
  SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  SPDX-License-Identifier: EUPL-1.2

  One index page for both entity publication policy surfaces: publish never
  (prohibition) and publish always (standing consent). The manifest page
  picks the surface through `config.surface`.
-->
<template>
	<div>
		<CnIndexPage
			:title="rules.title"
			:description="rules.description"
			:showTitle="true"
			:objects="rules.store[rules.listKey]"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="rules.store.loading"
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
			<!-- `below-header`: CnIndexPage defines no `above-table` slot. -->
			<template #below-header>
				<div class="policy-stats">
					<CnStatsBlock
						:title="t('filinq', 'Total')"
						:count="stats.total"
						:countLabel="t('filinq', 'rules')"
						variant="default"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('filinq', 'Active')"
						:count="stats.active"
						:countLabel="t('filinq', 'active')"
						:variant="rules.activeVariant"
						horizontal
						showZeroCount />
					<CnStatsBlock
						:title="t('filinq', 'Inactive')"
						:count="stats.inactive"
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

			<template #[`column-${rules.badge.field}`]="{ row }">
				<CnStatusBadge
					:label="row[rules.badge.field] || '-'"
					:colorMap="$data[rules.badge.mapKey]" />
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

		<!-- The form modal owns its form state (ADR-004 gate-13). -->
		<PolicyRuleFormModal
			:surface="surface"
			:open="dialogOpen"
			:editingRecord="editingRecord"
			:saving="saving"
			:formError="formError"
			@update:open="dialogOpen = $event"
			@submit="onModalSubmit"
			@cancel="dialogOpen = false" />

		<CnConfirmDialog
			v-if="deleteTarget"
			ref="confirmDialog"
			:dialogTitle="rules.deleteTitle"
			:message="deleteMessage"
			:confirmLabel="t('filinq', 'Delete')"
			variant="error"
			@confirm="executeDelete($refs.confirmDialog)"
			@close="cancelDelete" />
	</div>
</template>

<script>
import {
	CnConfirmDialog,
	CnIndexPage,
	CnStatsBlock,
	CnStatusBadge,
} from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import PolicyRuleFormModal from '../../dialogs/PolicyRuleFormModal.vue'
import {
	indexPaging,
	recordDeletion,
	recordFormDialog,
} from '../../mixins/recordIndexPage.js'
import { prohibitionStore, standingConsentStore } from '../../store/store.js'

/**
 * What differs between the two surfaces. Texts are functions so each
 * `t()` call stays literal for the translation extractor.
 *
 * @type {Record<string, object>}
 */
const SURFACES = {
	prohibition: {
		store: prohibitionStore,
		listKey: 'prohibitions',
		statsKey: 'prohibitionStats',
		fetch: 'fetchProhibitions',
		create: 'createProhibition',
		update: 'updateProhibition',
		remove: 'deleteProhibition',
		activeVariant: 'error',
		nameField: 'primaryName',
		deleteFailedLog: 'Failed to delete prohibition:',
		// Severity values mirror the publicationProhibition schema enum
		// (`high` / `medium` / `low`) in filinq_register.json.
		badge: {
			field: 'severity',
			mapKey: 'severityColorMap',
			map: { high: 'error', medium: 'warning', low: 'default' },
		},
		texts: () => ({
			title: t('filinq', 'Publish never'),
			description: t(
				'filinq',
				'Entity-level deny rules. A matched entity is always anonymised, regardless of the per-document consent workflow.',
			),
			deleteTitle: t('filinq', 'Delete prohibition'),
			empty: t('filinq', 'No publication prohibitions defined.'),
			unnamed: t('filinq', 'this prohibition'),
		}),
		columns: () => [
			{
				key: 'primaryName',
				label: t('filinq', 'Primary name'),
				sortable: true,
			},
			{ key: 'entityType', label: t('filinq', 'Type'), sortable: true },
			{ key: 'matchRules', label: t('filinq', 'Match rules') },
			{ key: 'severity', label: t('filinq', 'Severity'), sortable: true },
			{ key: 'reason', label: t('filinq', 'Reason') },
			{ key: 'active', label: t('filinq', 'Status'), sortable: true },
		],
		editingRecord: (row) => ({
			primaryName: row.primaryName || '',
			entityType: row.entityType || 'PERSON',
			reason: row.reason || '',
			legalAuthority: row.legalAuthority || '',
			caseReference: row.caseReference || '',
			severity: row.severity || 'medium',
			jurisdiction: row.jurisdiction || '',
			validUntil: row.validUntil || '',
			active: row.active !== false,
			matchRules: copyMatchRules(row),
		}),
	},
	standingConsent: {
		store: standingConsentStore,
		listKey: 'standingConsents',
		statsKey: 'standingConsentStats',
		fetch: 'fetchStandingConsents',
		create: 'createStandingConsent',
		update: 'updateStandingConsent',
		remove: 'deleteStandingConsent',
		activeVariant: 'success',
		nameField: 'entityText',
		deleteFailedLog: 'Failed to delete standing consent:',
		badge: {
			field: 'consentMethod',
			mapKey: 'methodColorMap',
			map: {
				paper: 'default',
				digital_signature: 'primary',
				verbal_recorded: 'warning',
				opt_in_form: 'success',
			},
		},
		texts: () => ({
			title: t('filinq', 'Publish always'),
			description: t(
				'filinq',
				'Entity-level allow rules. A matched entity may be published without per-document objection workflow, unless a publish-never rule also matches.',
			),
			deleteTitle: t('filinq', 'Delete standing consent'),
			empty: t('filinq', 'No standing publication consents defined.'),
			unnamed: t('filinq', 'this standing consent'),
		}),
		columns: () => [
			{ key: 'entityText', label: t('filinq', 'Entity'), sortable: true },
			{ key: 'entityType', label: t('filinq', 'Type'), sortable: true },
			{ key: 'matchRules', label: t('filinq', 'Match rules') },
			{ key: 'consentMethod', label: t('filinq', 'Method'), sortable: true },
			{ key: 'validUntil', label: t('filinq', 'Valid until'), sortable: true },
			{ key: 'active', label: t('filinq', 'Status'), sortable: true },
		],
		editingRecord: (row) => ({
			entityText: row.entityText || '',
			entityType: row.entityType || 'PERSON',
			consentMethod: row.consentMethod || '',
			consentDocument: row.consentDocument || '',
			consentScope: row.consentScope || '',
			legalBasis: row.legalBasis || '',
			validFrom: row.validFrom || '',
			validUntil: row.validUntil || '',
			active: row.active !== false,
			matchRules: copyMatchRules(row),
			consentStatus: row.consentStatus || 'consent_given',
			publicationDecision: row.publicationDecision || 'publish_with_consent',
			notificationStatus: row.notificationStatus || 'skipped',
		}),
	},
}

/**
 * A row's match rules as plain `{ type, value }` copies.
 *
 * @param {object} row The policy rule.
 * @return {object[]} The rules.
 */
function copyMatchRules(row) {
	return Array.isArray(row.matchRules)
		? row.matchRules.map((r) => ({ type: r.type, value: r.value }))
		: []
}

export default {
	name: 'PolicyRuleIndex',
	components: {
		CnIndexPage,
		CnStatsBlock,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		PolicyRuleFormModal,
		CnConfirmDialog,
		Delete,
		DotsHorizontal,
		Pencil,
	},

	mixins: [indexPaging, recordFormDialog, recordDeletion],

	props: {
		/** The policy surface this page lists: `prohibition` or `standingConsent`. */
		surface: {
			type: String,
			required: true,
			validator: (value) => value in SURFACES,
		},
	},

	data() {
		const badge = SURFACES[this.surface].badge
		return {
			entityTypeColorMap: {
				PERSON: 'warning',
				ORGANIZATION: 'primary',
				OTHER: 'default',
			},

			[badge.mapKey]: badge.map,

			activeColorMap: {
				[t('filinq', 'Active')]: SURFACES[this.surface].activeVariant,
				[t('filinq', 'Inactive')]: 'default',
			},
		}
	},

	computed: {
		/**
		 * The surface's store, texts and store actions.
		 *
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		rules() {
			const surface = SURFACES[this.surface]
			return { ...surface, ...surface.texts() }
		},

		/**
		 * Log line for a failed delete, read by the recordDeletion mixin.
		 *
		 * @return {string}
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		deleteFailedLog() {
			return this.rules.deleteFailedLog
		},

		/**
		 * The total, active and inactive counts of the surface.
		 *
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		stats() {
			return this.rules.store[this.rules.statsKey]
		},

		/**
		 * CnDataTable column set of the surface.
		 *
		 * @return {object[]}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		tableColumns() {
			return this.rules.columns()
		},

		/**
		 * One page of the client-side list: the store holds every rule.
		 *
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		paginationData() {
			const total = this.rules.store[this.rules.listKey].length
			const pages = Math.ceil(total / this.pageSize)
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},

		/**
		 * Empty-state text: the store's error when loading failed, otherwise
		 * the surface's no-rules message.
		 *
		 * @return {string}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		emptyText() {
			return this.rules.store.error || this.rules.empty
		},

		/**
		 * Body text of the delete confirmation dialog.
		 *
		 * @return {string}
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		deleteMessage() {
			const name =
				this.deleteTarget?.[this.rules.nameField] || this.rules.unnamed
			return t('filinq', 'Delete "{name}"? This cannot be undone.', { name })
		},
	},

	mounted() {
		this.reloadRecords()
	},

	methods: {
		t,

		/**
		 * Load the surface's rules from the store.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		reloadRecords() {
			return this.rules.store[this.rules.fetch]()
		},

		/**
		 * Create a rule from the form dialog's payload.
		 *
		 * @param {object} formData The submitted form.
		 * @return {Promise<object>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
		 */
		createRecord(formData) {
			return this.rules.store[this.rules.create](formData)
		},

		/**
		 * Update a rule from the form dialog's payload.
		 *
		 * @param {string} id The record id.
		 * @param {object} formData The submitted form.
		 * @return {Promise<object>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
		 */
		updateRecord(id, formData) {
			return this.rules.store[this.rules.update](id, formData)
		},

		/**
		 * Delete a rule by id.
		 *
		 * @param {string} id The record id.
		 * @return {Promise<void>}
		 * @spec openspec/specs/orphaned-surface-restoration/spec.md#requirement-policy-surfaces-are-reachable-menu-ownership-deferred-req-ddosr-005
		 */
		deleteRecord(id) {
			return this.rules.store[this.rules.remove](id)
		},

		/**
		 * The form values the surface's modal starts from for a row.
		 *
		 * @param {object} row The rule to edit.
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-rbac-must-govern-writes-to-both-policy-surfaces
		 */
		toEditingRecord(row) {
			return this.rules.editingRecord(row)
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
