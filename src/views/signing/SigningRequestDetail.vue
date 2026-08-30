<template>
	<div class="signing-request-detail">
		<NcLoadingIcon
			v-if="signingStore.loading && !signingStore.signingRequest"
			:size="44" />
		<template v-else-if="signingStore.signingRequest">
			<h2>{{ signingStore.signingRequest.documentName }}</h2>
			<div class="detail-grid">
				<div>
					<strong>{{ t('filinq', 'Status') }}</strong
					>: {{ signingStore.signingRequest.status }}
				</div>
				<div>
					<strong>{{ t('filinq', 'Level') }}</strong
					>: {{ signingStore.signingRequest.signatureLevel }}
				</div>
				<div>
					<strong>{{ t('filinq', 'Mode') }}</strong
					>: {{ signingStore.signingRequest.signingMode }}
				</div>
				<div>
					<strong>{{ t('filinq', 'Provider') }}</strong
					>: {{ signingStore.signingRequest.provider }}
				</div>
			</div>
			<NcButton
				v-if="signingStore.signingRequest.documentFileId"
				variant="secondary"
				@click="openVerify">
				{{ t('filinq', 'Verify') }}
			</NcButton>
			<h3>{{ t('filinq', 'Audit Trail') }}</h3>
			<table v-if="signingStore.auditTrail.length > 0" class="audit-table">
				<thead>
					<tr>
						<th scope="col">{{ t('filinq', 'Action') }}</th>
						<th scope="col">{{ t('filinq', 'Actor') }}</th>
						<th scope="col">{{ t('filinq', 'Timestamp') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="entry in signingStore.auditTrail"
						:key="entry.id || entry.uuid">
						<td>{{ entry.action }}</td>
						<td>{{ entry.actorDisplayName }}</td>
						<td>
							{{
								entry.timestamp
									? new Date(entry.timestamp).toLocaleString()
									: '-'
							}}
						</td>
					</tr>
				</tbody>
			</table>
			<p v-else>
				{{ t('filinq', 'No audit entries yet.') }}
			</p>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningRequestDetail',
	components: { NcButton, NcLoadingIcon },
	props: {
		/**
		 * The signing request to show.
		 *
		 * MUST be named `id`, because that is the name of the route
		 * parameter. src/main.js builds this route with `props: true` for
		 * any path containing a `:`, and vue-router's `props: true` passes
		 * `route.params` through BY NAME. The manifest route is
		 * `/signing/:id`, so a prop called anything else is simply never
		 * supplied — this was declared `requestId` and arrived `undefined`,
		 * which made the page fetch `/api/signing/requests/undefined` and
		 * render blank for every request. Its sibling
		 * SignatureVerification has always worked because its route is
		 * `/signing/verify/:fileId` and its prop is `fileId` — the names
		 * match there by accident of naming, not by design.
		 */
		id: { type: String, required: true },
	},

	/**
	 * Load the signing request and its audit trail on mount.
	 *
	 * @param props
	 * @spec openspec/changes/digital-signing-integration/tasks.md#8-3
	 */
	setup(props) {
		const signingStore = useSigningStore()
		signingStore.fetchSigningRequest(props.id)
		signingStore.fetchAuditTrail(props.id)
		return { signingStore, t }
	},

	methods: {
		/**
		 * Navigate to the restored SignatureVerification page for this
		 * request's document file id.
		 *
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-signing-authoring-and-verify-are-reachable-with-trust-actions-gated-req-ddosr-004
		 */
		openVerify() {
			this.$router.push({
				name: 'SignatureVerification',
				params: {
					fileId: String(this.signingStore.signingRequest.documentFileId),
				},
			})
		},
	},
}
</script>

<style scoped>
.signing-request-detail {
	padding: 20px;
}

.detail-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 12px;
	margin-bottom: 20px;
}

.audit-table {
	width: 100%;
	border-collapse: collapse;
}

.audit-table th,
.audit-table td {
	padding: 10px;
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}
</style>
