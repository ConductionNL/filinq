<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<section class="signing-delegations" data-testid="signing-delegations">
		<h3>{{ t('filinq', 'Delegated signing') }}</h3>

		<form class="signing-delegations__form" @submit.prevent="declare">
			<label for="delegation-delegate">{{ t('filinq', 'Colleague (user ID)') }}</label>
			<input id="delegation-delegate" v-model.trim="form.delegate" type="text" required>
			<label for="delegation-from">{{ t('filinq', 'From') }}</label>
			<input id="delegation-from" v-model="form.from" type="date" required>
			<label for="delegation-until">{{ t('filinq', 'Until') }}</label>
			<input id="delegation-until" v-model="form.until" type="date" required>
			<label for="delegation-types">{{ t('filinq', 'Document types (app/schema, comma separated)') }}</label>
			<input id="delegation-types" v-model="form.types" type="text" required>
			<NcButton type="submit" variant="primary" :disabled="busy">
				{{ t('filinq', 'Delegate') }}
			</NcButton>
		</form>
		<p v-if="error" class="signing-delegations__error" role="alert">
			{{ error }}
		</p>

		<h4>{{ t('filinq', 'Given by me') }}</h4>
		<p v-if="given.length === 0">
			{{ t('filinq', 'None') }}
		</p>
		<ul v-else>
			<li v-for="delegation in given" :key="delegation.id">
				{{ describe(delegation, delegation.delegate) }}
				<NcButton variant="tertiary" :disabled="busy" @click="withdraw(delegation.id)">
					{{ t('filinq', 'Withdraw') }}
				</NcButton>
			</li>
		</ul>

		<h4>{{ t('filinq', 'Given to me') }}</h4>
		<p v-if="received.length === 0">
			{{ t('filinq', 'None') }}
		</p>
		<ul v-else>
			<li v-for="delegation in received" :key="delegation.id">
				{{ describe(delegation, delegation.delegator) }}
			</li>
		</ul>
	</section>
</template>

<script>
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton } from '@nextcloud/vue'

const URL = '/apps/filinq/api/signing/delegations'

export default {
	name: 'SigningDelegations',
	components: { NcButton },

	data() {
		return {
			given: [],
			received: [],
			form: { delegate: '', from: '', until: '', types: '' },
			busy: false,
			error: '',
		}
	},

	/**
	 * Read the caller's delegations.
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-1.1
	 */
	mounted() {
		this.load()
	},

	methods: {
		t,

		/**
		 * Read the delegations the caller gave and received.
		 *
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-1.1
		 */
		async load() {
			try {
				const { data } = await axios.get(generateUrl(URL))
				this.given = data.given ?? []
				this.received = data.received ?? []
			} catch {
				this.error = t('filinq', 'Failed to read the delegations')
			}
		},

		/**
		 * Declare a delegation from the form.
		 *
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-1.1
		 */
		async declare() {
			this.busy = true
			this.error = ''
			try {
				await axios.post(generateUrl(URL), {
					delegate: this.form.delegate,
					from: this.form.from,
					until: this.form.until,
					types: this.form.types.split(',').map((type) => type.trim()).filter((type) => type !== ''),
				})
				this.form = { delegate: '', from: '', until: '', types: '' }
				await this.load()
			} catch (e) {
				this.error = e?.response?.data?.error ?? t('filinq', 'Failed to record the delegation')
			} finally {
				this.busy = false
			}
		},

		/**
		 * Withdraw a delegation the caller gave.
		 *
		 * @param {string} id The delegation.
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-1.1
		 */
		async withdraw(id) {
			this.busy = true
			try {
				await axios.delete(generateUrl(`${URL}/{id}`, { id }))
				await this.load()
			} catch {
				this.error = t('filinq', 'Failed to withdraw the delegation')
			} finally {
				this.busy = false
			}
		},

		/**
		 * One line for a delegation.
		 *
		 * @param {object} delegation The delegation.
		 * @param {string} person The other party.
		 * @return {string} The line.
		 *
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-1.1
		 */
		describe(delegation, person) {
			return t('filinq', '{person}, {from} to {until}: {types}', {
				person,
				from: delegation.from,
				until: delegation.until,
				types: (delegation.types ?? []).join(', '),
			})
		},
	},
}
</script>

<style scoped>
.signing-delegations {
	margin-top: 24px;
}

.signing-delegations__form {
	display: grid;
	grid-template-columns: max-content minmax(0, 320px);
	gap: 8px 12px;
	align-items: center;
}

.signing-delegations__error {
	color: var(--color-error-text);
}
</style>
