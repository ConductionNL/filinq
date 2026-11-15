<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Document webhooks')"
		:description="
			t(
				'filinq',
				'Tell an outside system when a document is signed, anonymised, generated or registered. OpenRegister delivers each event and signs it with the secret.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<NcNoteCard v-if="!deliveryAvailable" type="warning">
				{{ t('filinq', 'Delivery is not available on this server') }}
			</NcNoteCard>

			<ul v-if="receivers.length > 0" class="document-webhooks__list">
				<li
					v-for="receiver in receivers"
					:key="receiver.id"
					class="document-webhooks__row">
					<div class="document-webhooks__summary">
						<strong>{{ receiver.name }}</strong>
						<span class="document-webhooks__url">{{ receiver.url }}</span>
						<span>{{ eventLabels(receiver.events) }}</span>
						<span class="document-webhooks__status">{{ lastDelivery(receiver) }}</span>
					</div>
					<div class="document-webhooks__actions">
						<NcButton :disabled="busy" @click="sendTest(receiver)">
							{{ t('filinq', 'Send a test') }}
						</NcButton>
						<NcButton variant="error" :disabled="busy" @click="remove(receiver)">
							{{ t('filinq', 'Delete') }}
						</NcButton>
					</div>
				</li>
			</ul>
			<p v-else class="document-webhooks__hint">
				{{ t('filinq', 'No receivers yet.') }}
			</p>

			<fieldset class="document-webhooks__form">
				<legend>{{ t('filinq', 'Add a receiver') }}</legend>
				<NcTextField v-model="form.name" :label="t('filinq', 'Name')" />
				<NcTextField
					v-model="form.url"
					type="url"
					:label="t('filinq', 'Receiving URL')" />
				<div class="document-webhooks__events">
					<NcCheckboxRadioSwitch
						v-for="option in eventOptions"
						:key="option.id"
						v-model="form.events"
						:value="option.id"
						type="checkbox">
						{{ option.label }}
					</NcCheckboxRadioSwitch>
				</div>
				<NcPasswordField
					v-model="form.secret"
					:label="t('filinq', 'Secret')"
					:helperText="secretError"
					:error="secretError !== ''" />
				<p v-if="formError !== ''" class="document-webhooks__error">
					{{ formError }}
				</p>
				<NcButton variant="primary" :disabled="busy" @click="save">
					{{ t('filinq', 'Add receiver') }}
				</NcButton>
			</fieldset>
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
	NcNoteCard,
	NcPasswordField,
	NcSettingsSection,
	NcTextField,
} from '@nextcloud/vue'

// The document webhooks section (connect-document-webhooks REQ-CDW-003/004).
// It talks to OpenRegister's own webhook API: filinq adds no controller,
// no delivery and no log. A receiver is an OpenRegister webhook whose events
// name one of filinq's four document events.
export const EVENT_PREFIX = 'OCA\\Filinq\\Event\\Webhook\\'

export const EVENTS = {
	signed: EVENT_PREFIX + 'DocumentSignedEvent',
	anonymised: EVENT_PREFIX + 'DocumentAnonymisedEvent',
	generated: EVENT_PREFIX + 'DocumentGeneratedEvent',
	registered: EVENT_PREFIX + 'DocumentRegisteredEvent',
}

/**
 * An empty add form.
 *
 * @return {object} The form.
 */
function emptyForm() {
	return { name: '', url: '', events: [], secret: '' }
}

export default {
	name: 'DocumentWebhooksSection',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcNoteCard,
		NcPasswordField,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			busy: false,
			deliveryAvailable: true,
			receivers: [],
			form: emptyForm(),
			secretError: '',
			formError: '',
		}
	},

	computed: {
		/**
		 * The four events an admin can pick, in words.
		 *
		 * @return {Array<object>} Class name and label per event.
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		eventOptions() {
			return [
				{ id: EVENTS.signed, label: t('filinq', 'Document signed') },
				{ id: EVENTS.anonymised, label: t('filinq', 'Document anonymised') },
				{ id: EVENTS.generated, label: t('filinq', 'Document generated') },
				{ id: EVENTS.registered, label: t('filinq', 'Document registered') },
			]
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,

		/**
		 * An OpenRegister webhook API URL.
		 *
		 * @param {string} path The path below `/api/webhooks`.
		 * @return {string} The URL.
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-delivery-is-openregister-s-req-cdw-004
		 */
		url(path = '') {
			return generateUrl('/apps/openregister/api/webhooks' + path)
		},

		/**
		 * Whether a webhook listens to at least one filinq document event.
		 *
		 * @param {object} webhook The OpenRegister webhook.
		 * @return {boolean} True for a document receiver.
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		isReceiver(webhook) {
			return (webhook.events || []).some(
				(event) => typeof event === 'string' && event.startsWith(EVENT_PREFIX),
			)
		},

		/**
		 * The labels of a receiver's document events.
		 *
		 * @param {Array<string>} events The webhook's event names.
		 * @return {string} The labels, comma separated.
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		eventLabels(events) {
			return this.eventOptions
				.filter((option) => (events || []).includes(option.id))
				.map((option) => option.label)
				.join(', ')
		},

		/**
		 * The result of a receiver's last delivery, in words.
		 *
		 * @param {object} receiver The webhook.
		 * @return {string} The result.
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		lastDelivery(receiver) {
			const success = receiver.lastSuccessAt || ''
			const failure = receiver.lastFailureAt || ''
			if (success === '' && failure === '') {
				return t('filinq', 'Nothing delivered yet')
			}
			if (success >= failure) {
				return t('filinq', 'Last delivery arrived')
			}
			return t('filinq', 'Last delivery failed')
		},

		/**
		 * Load the receivers and whether this OpenRegister delivers filinq events.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-delivery-is-openregister-s-req-cdw-004
		 */
		async load() {
			try {
				const [list, catalogue] = await Promise.all([
					axios.get(this.url()),
					axios.get(this.url('/events')),
				])
				this.receivers = (list.data?.results || []).filter((webhook) =>
					this.isReceiver(webhook),
				)
				this.deliveryAvailable = (catalogue.data?.events || []).some(
					(event) =>
						typeof event?.class === 'string'
						&& event.class.startsWith(EVENT_PREFIX),
				)
			} catch {
				showError(t('filinq', 'Could not load the document webhooks'))
			} finally {
				this.loading = false
			}
		},

		/**
		 * Add a receiver. Refused without a secret, a name, a URL or an event.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		async save() {
			this.secretError = ''
			this.formError = ''
			if (this.form.secret.trim() === '') {
				this.secretError = t(
					'filinq',
					'Type a secret so the receiver can check each delivery',
				)
				return
			}
			if (
				this.form.name.trim() === ''
				|| this.form.url.trim() === ''
				|| this.form.events.length === 0
			) {
				this.formError = t(
					'filinq',
					'Type a name and a URL and pick at least one event',
				)
				return
			}

			this.busy = true
			try {
				const { data } = await axios.post(this.url(), {
					name: this.form.name.trim(),
					url: this.form.url.trim(),
					method: 'POST',
					events: [...this.form.events],
					secret: this.form.secret,
					enabled: true,
				})
				this.receivers.push(data)
				this.form = emptyForm()
				showSuccess(t('filinq', 'Receiver added'))
			} catch {
				showError(t('filinq', 'Could not add the receiver'))
			} finally {
				this.busy = false
			}
		},

		/**
		 * Send a test delivery to a receiver.
		 *
		 * @param {object} receiver The webhook.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		async sendTest(receiver) {
			this.busy = true
			try {
				const { data } = await axios.post(this.url('/' + receiver.id + '/test'))
				if (data?.success === true) {
					showSuccess(t('filinq', 'The test arrived'))
				} else {
					showError(t('filinq', 'The test did not arrive'))
				}
			} catch {
				showError(t('filinq', 'The test did not arrive'))
			} finally {
				this.busy = false
				await this.refresh(receiver)
			}
		},

		/**
		 * Read a receiver again, for its last delivery.
		 *
		 * @param {object} receiver The webhook.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		async refresh(receiver) {
			try {
				const { data } = await axios.get(this.url('/' + receiver.id))
				const index = this.receivers.findIndex((row) => row.id === receiver.id)
				if (index !== -1 && data) {
					this.receivers.splice(index, 1, data)
				}
			} catch {
				// The row keeps its previous state; the test result was already shown.
			}
		},

		/**
		 * Delete a receiver.
		 *
		 * @param {object} receiver The webhook.
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-an-admin-sets-up-a-receiver-from-filinq-s-settings-req-cdw-003
		 */
		async remove(receiver) {
			this.busy = true
			try {
				await axios.delete(this.url('/' + receiver.id))
				this.receivers = this.receivers.filter((row) => row.id !== receiver.id)
				showSuccess(t('filinq', 'Receiver deleted'))
			} catch {
				showError(t('filinq', 'Could not delete the receiver'))
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.document-webhooks__list {
	margin-bottom: calc(var(--default-grid-baseline) * 4);
	max-width: 800px;
}

.document-webhooks__row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
	padding: calc(var(--default-grid-baseline) * 2) 0;
	border-bottom: 1px solid var(--color-border);
}

.document-webhooks__summary {
	display: flex;
	flex-direction: column;
}

.document-webhooks__url,
.document-webhooks__status,
.document-webhooks__hint {
	color: var(--color-text-maxcontrast);
}

.document-webhooks__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
}

.document-webhooks__form {
	max-width: 640px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: calc(var(--default-grid-baseline) * 3);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
}

.document-webhooks__error {
	color: var(--color-error-text);
}
</style>
