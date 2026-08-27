<!--
	@visual exclude Deliberately unregistered, so no route reaches it and no
	browser can drive it. Superseded by the type:"index" SigningRequests page
	(Phase 8 decomposition — see that page's _note in src/manifest.json),
	which IS covered at /signing by tests/e2e/spec-coverage/signing.spec.ts.
	This is not an oversight being waived: the same exemption is asserted
	independently by tests/unit/reachability.spec.js, whose KNOWN_HEADLESS
	allow-list carries this exact file under kind:"orphaned-view". The day
	this component is registered, that allow-list entry has to go, and this
	marker with it — the exclusion cannot outlive its reason silently.
-->
<template>
	<div class="signing-request-list">
		<div class="signing-header">
			<h2>{{ t('filinq', 'Signing Requests') }}</h2>
		</div>
		<NcLoadingIcon v-if="signingStore.loading" :size="44" />
		<NcEmptyContent
			v-else-if="signingStore.signingRequests.length === 0"
			:name="t('filinq', 'No signing requests')" />
		<table v-else class="signing-table">
			<thead>
				<tr>
					<th scope="col">{{ t('filinq', 'Document') }}</th>
					<th scope="col">{{ t('filinq', 'Status') }}</th>
					<th scope="col">{{ t('filinq', 'Level') }}</th>
					<th scope="col">{{ t('filinq', 'Mode') }}</th>
					<th scope="col">{{ t('filinq', 'Deadline') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="request in signingStore.signingRequests"
					:key="request.id || request.uuid">
					<td>{{ request.documentName }}</td>
					<td>
						<span class="status-badge">{{ request.status }}</span>
					</td>
					<td>{{ request.signatureLevel }}</td>
					<td>{{ request.signingMode }}</td>
					<td>
						{{
							request.deadline
								? new Date(request.deadline).toLocaleDateString()
								: '-'
						}}
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningRequestList',
	components: { NcLoadingIcon, NcEmptyContent },
	/**
	 * Load all signing requests for the list view on mount.
	 *
	 * @spec openspec/changes/digital-signing-integration/tasks.md#8-2
	 */
	setup() {
		const signingStore = useSigningStore()
		signingStore.fetchSigningRequests()
		return { signingStore, t }
	},
}
</script>

<style scoped>
.signing-request-list {
	padding: 20px;
}

.signing-header {
	display: flex;
	justify-content: space-between;
	margin-bottom: 20px;
}

.signing-table {
	width: 100%;
	border-collapse: collapse;
}

.signing-table th,
.signing-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.status-badge {
	padding: 4px 8px;
	border-radius: var(--border-radius);
	font-size: 0.85em;
	font-weight: bold;
}
</style>
