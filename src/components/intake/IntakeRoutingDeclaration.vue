<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<section class="intake-routing" data-testid="intake-routing-declaration">
		<h3>{{ t('filinq', 'Routing per record type') }}</h3>
		<p class="intake-routing__hint">
			{{ t('filinq', 'A document filed against a declared record type goes to its group, and waits for acceptance when you ask for it.') }}
		</p>
		<form class="intake-routing__form" @submit.prevent="declare">
			<label for="intake-routing-app">{{ t('filinq', 'App') }}</label>
			<input id="intake-routing-app" v-model.trim="form.declaringApp" type="text" required>
			<label for="intake-routing-type">{{ t('filinq', 'Record type') }}</label>
			<input id="intake-routing-type" v-model.trim="form.typeReference" type="text" required>
			<label for="intake-routing-group">{{ t('filinq', 'Route to group') }}</label>
			<input id="intake-routing-group" v-model.trim="form.routeTo" type="text">
			<span />
			<label class="intake-routing__check">
				<input v-model="form.requiresAcceptance" type="checkbox">
				{{ t('filinq', 'The group accepts the document first') }}
			</label>
			<span />
			<NcButton type="submit" variant="primary" :disabled="busy">
				{{ t('filinq', 'Save routing') }}
			</NcButton>
		</form>
		<p v-if="message" class="intake-routing__message" role="status">
			{{ message }}
		</p>
		<p v-if="error" class="intake-routing__error" role="alert">
			{{ error }}
		</p>
	</section>
</template>

<script>
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'IntakeRoutingDeclaration',
	components: { NcButton },

	data() {
		return {
			form: { declaringApp: '', typeReference: '', routeTo: '', requiresAcceptance: false },
			busy: false,
			message: '',
			error: '',
		}
	},

	methods: {
		t,

		/**
		 * Store the routing declaration for one record type over intake#declareRouting.
		 *
		 * @spec openspec/changes/specify-capability-rows/tasks.md#task-7.1
		 */
		async declare() {
			this.busy = true
			this.message = ''
			this.error = ''
			try {
				await axios.post(generateUrl('/apps/filinq/api/intake/routing-rules'), { ...this.form })
				this.message = t('filinq', 'Routing saved for {type}', { type: this.form.typeReference })
			} catch (e) {
				this.error = e?.response?.data?.error ?? t('filinq', 'The routing could not be saved')
			} finally {
				this.busy = false
			}
		},
	},
}
</script>

<style scoped>
.intake-routing {
	margin-top: 24px;
}

.intake-routing__form {
	display: grid;
	grid-template-columns: max-content minmax(0, 320px);
	gap: 8px 12px;
	align-items: center;
}

.intake-routing__hint {
	color: var(--color-text-maxcontrast);
}

.intake-routing__error {
	color: var(--color-error-text);
}
</style>
