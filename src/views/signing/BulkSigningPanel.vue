<!--
	@visual exclude Deliberately unregistered, so no route reaches it and no
	browser can drive it. This is not an oversight being waived: the same
	exemption is asserted independently by tests/unit/reachability.spec.js,
	whose KNOWN_HEADLESS allow-list carries this exact file under
	kind:"orphaned-view" with the reason "owned-by:bulk-signing-field-builder
	— the enriched bulk-sign + field-placement surface is being actively
	rebuilt there". The day this component is registered, that allow-list
	entry has to go, and this marker with it — the exclusion cannot outlive
	its reason silently. Its live sibling SigningRequestDetail is covered by
	a real browser test in tests/e2e/workflows/signing-workflow.spec.ts.
-->
<template>
	<div class="bulk-signing-panel">
		<h2>{{ t('filinq', 'Bulk Signing') }}</h2>
		<NcLoadingIcon v-if="signingStore.loading" :size="44" />
		<NcEmptyContent
			v-else-if="pending.length === 0"
			:name="t('filinq', 'No pending signing requests')" />
		<template v-else>
			<NcButton
				variant="primary"
				:disabled="selected.length === 0"
				@click="bulkSign">
				{{ t('filinq', 'Sign Selected') }} ({{ selected.length }})
			</NcButton>
			<table class="bulk-table">
				<thead>
					<tr>
						<!-- Selection column: a control header with no name of its
						     own, so scope= would associate nothing. -->
						<th />
						<th scope="col">{{ t('filinq', 'Document') }}</th>
						<th scope="col">{{ t('filinq', 'Level') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="req in pending"
						:key="req.id || req.uuid"
						@click="toggle(req.id || req.uuid)">
						<td>
							<input
								type="checkbox"
								:aria-label="
									t('filinq', 'Select {document}', {
										document: req.documentName,
									})
								"
								:checked="selected.includes(req.id || req.uuid)" />
						</td>
						<td>{{ req.documentName }}</td>
						<td>{{ req.signatureLevel }}</td>
					</tr>
				</tbody>
			</table>
		</template>
	</div>
</template>

<script>
import { showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'BulkSigningPanel',
	components: { NcButton, NcLoadingIcon, NcEmptyContent },
	data() {
		return { selected: [] }
	},

	computed: {
		/**
		 * Pinia signing store accessor for the bulk-signing panel.
		 *
		 * @spec openspec/changes/digital-signing-integration/tasks.md#8-5
		 */
		signingStore() {
			return useSigningStore()
		},

		/**
		 * Requests eligible for bulk signing (PENDING or IN_PROGRESS).
		 *
		 * @spec openspec/changes/digital-signing-integration/tasks.md#8-5
		 */
		pending() {
			return this.signingStore.signingRequests.filter((r) =>
				['PENDING', 'IN_PROGRESS'].includes(r.status),
			)
		},
	},

	mounted() {
		this.signingStore.fetchSigningRequests()
	},

	methods: {
		t,
		/**
		 * Toggle selection of a request in the bulk-signing batch.
		 *
		 * @param id
		 * @spec openspec/changes/digital-signing-integration/tasks.md#8-5
		 */
		toggle(id) {
			const idx = this.selected.indexOf(id)
			if (idx === -1) {
				this.selected.push(id)
			} else {
				this.selected.splice(idx, 1)
			}
		},

		/**
		 * Submit the selected requests for batch signing.
		 *
		 * @spec openspec/changes/digital-signing-integration/tasks.md#8-5
		 */
		async bulkSign() {
			await this.signingStore.bulkSign(this.selected)
			showSuccess(t('filinq', 'Bulk signing completed'))
			this.selected = []
		},
	},
}
</script>

<style scoped>
.bulk-signing-panel {
	padding: 20px;
}

.bulk-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 16px;
}

.bulk-table th,
.bulk-table td {
	padding: 10px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.bulk-table tr {
	cursor: pointer;
}

.bulk-table tr:hover {
	background: var(--color-background-hover);
}
</style>
