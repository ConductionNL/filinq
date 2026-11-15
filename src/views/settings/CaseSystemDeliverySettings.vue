<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Case system')"
		:description="
			t(
				'filinq',
				'Choose where generated documents go in the case system. Integriq sends them; Filinq keeps track of each delivery.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<h3 class="case-system__heading">
				{{ t('filinq', 'Destinations') }}
			</h3>
			<p v-if="destinations.length === 0" class="case-system__hint">
				{{ t('filinq', 'No destinations yet.') }}
			</p>
			<table v-else class="case-system__table">
				<thead>
					<tr>
						<th scope="col">
							{{ t('filinq', 'Applies to') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Document type') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Confidentiality') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Status') }}
						</th>
						<th scope="col">
							<span class="hidden-visually">{{ t('filinq', 'Actions') }}</span>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="destination in destinations" :key="destination.id">
						<td>{{ scopeOf(destination) }}</td>
						<td>{{ destination.informatieobjecttype }}</td>
						<td>{{ destination.vertrouwelijkheidaanduiding }}</td>
						<td>
							{{
								destination.active ? t('filinq', 'Active') : t('filinq', 'Inactive')
							}}
						</td>
						<td>
							<NcButton variant="tertiary" @click="edit(destination)">
								{{ t('filinq', 'Edit') }}
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="case-system__form">
				<legend>
					{{
						form.id ? t('filinq', 'Edit destination') : t('filinq', 'Add destination')
					}}
				</legend>
				<NcTextField
					v-model="form.templateNamespace"
					:label="t('filinq', 'Template namespace')" />
				<NcTextField
					v-model="form.templateId"
					:label="t('filinq', 'Template ID')"
					:helperText="
						t('filinq', 'A destination for one template wins over one for its namespace.')
					" />
				<NcTextField v-model="form.sourceId" :label="t('filinq', 'Bridge source')" />
				<NcTextField
					v-model="form.informatieobjecttype"
					:label="t('filinq', 'Document type in the case system')" />
				<NcSelect
					v-model="form.vertrouwelijkheidaanduiding"
					:inputLabel="t('filinq', 'Confidentiality')"
					:options="confidentialityOptions"
					:clearable="false" />
				<NcTextField
					v-model="form.bronorganisatie"
					:label="t('filinq', 'Source organisation (RSIN)')" />
				<NcCheckboxRadioSwitch v-model="form.active" type="switch">
					{{ t('filinq', 'Active') }}
				</NcCheckboxRadioSwitch>
				<div class="case-system__actions">
					<NcButton variant="primary" :disabled="saving" @click="save">
						{{ t('filinq', 'Save destination') }}
					</NcButton>
					<NcButton v-if="form.id" variant="tertiary" @click="reset">
						{{ t('filinq', 'Cancel') }}
					</NcButton>
				</div>
			</fieldset>

			<h3 class="case-system__heading">
				{{ t('filinq', 'Deliveries waiting or failed') }}
			</h3>
			<p v-if="deliveries.length === 0" class="case-system__hint">
				{{ t('filinq', 'Nothing is waiting for the case system.') }}
			</p>
			<ul v-else class="case-system__deliveries">
				<li
					v-for="delivery in deliveries"
					:key="delivery.id"
					class="case-system__delivery">
					<div>
						<strong>{{ documentName(delivery) }}</strong>
						<span class="case-system__status">{{ statusLabel(delivery) }}</span>
						<p v-if="delivery.errorMessage" class="case-system__error">
							{{ delivery.errorMessage }}
						</p>
					</div>
					<NcButton
						v-if="delivery.deliveryStatus === 'writeback_failed'"
						:disabled="retrying === delivery.id"
						@click="retry(delivery)">
						{{ t('filinq', 'Retry') }}
					</NcButton>
				</li>
			</ul>
		</template>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcLoadingIcon,
	NcSelect,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'

// The case-system part of the admin bridge panel
// (generate-store-in-case-system REQ-GSC-001, REQ-GSC-003). It talks to its
// own admin-only endpoints; filinq never calls the case system, integriq
// picks up each waiting delivery.
const EMPTY_FORM = {
	id: '',
	templateNamespace: '',
	templateId: '',
	sourceId: '',
	informatieobjecttype: '',
	vertrouwelijkheidaanduiding: 'vertrouwelijk',
	bronorganisatie: '',
	active: true,
}

export default {
	name: 'CaseSystemDeliverySettings',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcSelect,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			saving: false,
			retrying: '',
			destinations: [],
			deliveries: [],
			form: { ...EMPTY_FORM },
		}
	},

	computed: {
		/**
		 * The confidentiality levels of the ZGW Documenten API.
		 *
		 * @return {Array<string>} The levels.
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
		 */
		confidentialityOptions() {
			return [
				'openbaar',
				'beperkt_openbaar',
				'intern',
				'zaakvertrouwelijk',
				'vertrouwelijk',
				'confidentieel',
				'geheim',
				'zeer_geheim',
			]
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,

		/**
		 * One of the panel's endpoints.
		 *
		 * @param {string} path The path under api/bridge.
		 * @return {string} The URL.
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
		 */
		url(path) {
			return generateUrl('/apps/filinq/api/bridge/' + path)
		},

		/**
		 * What a destination applies to, in words.
		 *
		 * @param {object} destination The destination.
		 * @return {string} The template or the namespace.
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
		 */
		scopeOf(destination) {
			if (destination.templateId) {
				return t('filinq', 'Template {template}', { template: destination.templateId })
			}
			return t('filinq', 'Namespace {namespace}', { namespace: destination.templateNamespace })
		},

		/**
		 * The document a delivery carries, by its file name.
		 *
		 * @param {object} delivery The delivery.
		 * @return {string} The file name, else the document id.
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
		 */
		documentName(delivery) {
			return delivery.metadata?.bestandsnaam || delivery.generatedDocumentId || ''
		},

		/**
		 * A delivery's status, in words.
		 *
		 * @param {object} delivery The delivery.
		 * @return {string} Waiting or Failed.
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
		 */
		statusLabel(delivery) {
			return delivery.deliveryStatus === 'writeback_failed'
				? t('filinq', 'Failed')
				: t('filinq', 'Waiting')
		},

		/**
		 * Load the destinations and the open deliveries.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
		 */
		async load() {
			try {
				const { data } = await axios.get(this.url('case-system'))
				this.destinations = data.destinations || []
				this.deliveries = data.deliveries || []
			} catch {
				showError(t('filinq', 'Could not load the case-system settings'))
			} finally {
				this.loading = false
			}
		},

		/**
		 * Put a destination in the form.
		 *
		 * @param {object} destination The destination.
		 * @return {void}
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
		 */
		edit(destination) {
			this.form = { ...EMPTY_FORM }
			for (const field of Object.keys(EMPTY_FORM)) {
				if (destination[field] !== undefined && destination[field] !== null) {
					this.form[field] = destination[field]
				}
			}
		},

		/**
		 * Empty the form.
		 *
		 * @return {void}
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
		 */
		reset() {
			this.form = { ...EMPTY_FORM }
		},

		/**
		 * Save the destination; the server refuses a second active one with its reason.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-an-admin-declares-where-generated-documents-go-in-the-case-system-req-gsc-001
		 */
		async save() {
			this.saving = true
			try {
				await axios.post(this.url('destinations'), { ...this.form })
				showSuccess(t('filinq', 'Destination saved'))
				this.reset()
				await this.load()
			} catch (error) {
				showError(
					error?.response?.data?.error
						|| t('filinq', 'Could not save the destination'),
				)
			} finally {
				this.saving = false
			}
		},

		/**
		 * Put a failed delivery back in the queue.
		 *
		 * @param {object} delivery The delivery.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
		 */
		async retry(delivery) {
			this.retrying = delivery.id
			try {
				const { data } = await axios.post(
					this.url('deliveries/' + encodeURIComponent(delivery.id) + '/retry'),
				)
				this.deliveries = this.deliveries.map((row) => (row.id === delivery.id ? { ...row, ...data } : row))
				showSuccess(t('filinq', 'The delivery is waiting again'))
			} catch {
				showError(t('filinq', 'Could not retry the delivery'))
			} finally {
				this.retrying = ''
			}
		},
	},
}
</script>

<style scoped>
.case-system__heading {
	margin-top: calc(var(--default-grid-baseline) * 4);
}

.case-system__hint,
.case-system__status {
	color: var(--color-text-maxcontrast);
}

.case-system__status {
	margin-inline-start: calc(var(--default-grid-baseline) * 2);
}

.case-system__table {
	width: 100%;
	max-width: 960px;
	border-collapse: collapse;
}

.case-system__table th,
.case-system__table td {
	text-align: start;
	padding: calc(var(--default-grid-baseline) * 2);
	border-bottom: 1px solid var(--color-border);
}

.case-system__form {
	max-width: 640px;
	margin-top: calc(var(--default-grid-baseline) * 4);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: calc(var(--default-grid-baseline) * 3);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
}

.case-system__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
}

.case-system__deliveries {
	max-width: 960px;
	padding: 0;
	list-style: none;
}

.case-system__delivery {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: calc(var(--default-grid-baseline) * 2) 0;
	border-bottom: 1px solid var(--color-border);
}

.case-system__error {
	color: var(--color-error-text);
}
</style>
