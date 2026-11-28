<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
  -
  - @spec openspec/changes/archive/2026-11-25-share-document-links/tasks.md#task-3.3
-->
<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Shared links')"
			:description="
				t(
					'filinq',
					'Links to documents made for someone outside the organisation: what left, by whom and until when. A link is never shown here.',
				)
			"
			:showTitle="true"
			:objects="rows"
			:columns="tableColumns"
			:loading="loading"
			v-bind="$options.listOnlyIndexPage"
			:showAdd="false"
			rowKey="id"
			:emptyText="t('filinq', 'No documents have been shared by link yet.')"
			:refreshing="refreshing"
			@refresh="refresh">
			<template #column-status="{ row }">
				<CnStatusBadge
					:label="stateLabel(row)"
					:colorMap="{ [stateLabel(row)]: stateColor(row) }" />
			</template>

			<template #column-kind="{ row }">
				{{
					row.kind === 'bundle'
						? t('filinq', 'Bundle')
						: t('filinq', 'Document')
				}}
			</template>

			<template #column-sharedAt="{ row }">
				{{ formatDate(row.sharedAt) }}
			</template>

			<template #row-actions="{ row }">
				<NcButton
					v-if="takeBackAllowed(row)"
					variant="tertiary"
					:disabled="busy === row.id"
					data-testid="shared-link-take-back"
					@click="target = row">
					{{ t('filinq', 'Take back') }}
				</NcButton>
			</template>
		</CnIndexPage>

		<NcNoteCard v-if="error" type="error" data-testid="shared-links-error">
			{{ error }}
		</NcNoteCard>

		<CnConfirmDialog
			v-if="target"
			:dialogTitle="t('filinq', 'Take the link back')"
			:message="
				t(
					'filinq',
					'The link to {what} stops working at once. This cannot be undone.',
					{ what: target.what },
				)
			"
			:confirmLabel="t('filinq', 'Take back')"
			variant="error"
			@confirm="takeBack"
			@close="target = null" />
	</div>
</template>

<script>
import { CnConfirmDialog, CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import { LIST_ONLY_INDEX_PAGE } from '../../constants/indexPage.js'
import {
	canTakeBack,
	listShares,
	revokeShare,
	shareRow,
	shareStateLabel,
} from '../../services/documentShare.js'

/**
 * The links a clerk made, or every link for an admin, with Take back.
 */
export default {
	name: 'SharedLinksIndex',
	listOnlyIndexPage: LIST_ONLY_INDEX_PAGE,
	components: {
		CnConfirmDialog,
		CnIndexPage,
		CnStatusBadge,
		NcButton,
		NcNoteCard,
	},

	data() {
		return {
			rows: [],
			loading: false,
			refreshing: false,
			error: '',
			target: null,
			busy: '',
		}
	},

	computed: {
		tableColumns() {
			return [
				{ key: 'what', label: t('filinq', 'What left') },
				{ key: 'kind', label: t('filinq', 'Kind') },
				{ key: 'note', label: t('filinq', 'For') },
				{ key: 'sharedBy', label: t('filinq', 'Shared by') },
				{ key: 'sharedAt', label: t('filinq', 'Shared on') },
				{ key: 'expiresAt', label: t('filinq', 'Works until') },
				{ key: 'status', label: t('filinq', 'State') },
			]
		},

		user() {
			const user = getCurrentUser()
			return user ? { uid: user.uid, isAdmin: user.isAdmin === true } : null
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		stateLabel: shareStateLabel,

		stateColor(row) {
			if (row.status === 'active') {
				return 'success'
			}
			return row.status === 'revoked' ? 'error' : 'warning'
		},

		takeBackAllowed(row) {
			return canTakeBack(row) && this.user !== null && (this.user.isAdmin || row.sharedBy === this.user.uid)
		},

		formatDate(value) {
			return value ? new Date(value).toLocaleString() : ''
		},

		async load() {
			this.loading = true
			const outcome = await listShares(this.user)
			this.loading = false
			if (!outcome.ok) {
				this.error = outcome.error || t('filinq', 'The shared links could not be read. Try again later.')
				return
			}
			this.error = ''
			this.rows = outcome.data.results
		},

		async refresh() {
			this.refreshing = true
			await this.load()
			this.refreshing = false
		},

		async takeBack() {
			const row = this.target
			this.target = null
			this.busy = row.id
			const outcome = await revokeShare(row.id)
			this.busy = ''
			if (!outcome.ok) {
				this.error = outcome.error || t('filinq', 'The link could not be taken back. Try again later.')
				return
			}
			this.error = ''
			this.rows = this.rows.map((existing) => (existing.id === row.id ? shareRow({ ...existing, ...outcome.data }) : existing))
		},
	},
}
</script>
