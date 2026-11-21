<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="archival-wiring" data-testid="archival-wiring">
		<NcNoteCard v-if="readFailed" type="warning" data-testid="archival-wiring-unread">
			{{ t('filinq', 'OpenRegister\'s archival settings cannot be read.') }}
		</NcNoteCard>
		<template v-else-if="settings !== null">
			<NcNoteCard v-if="state === 'wired'" type="success" data-testid="archival-wiring-state">
				{{ t('filinq', 'OpenRegister reads the selectielijst and keeps its destruction lists in filinq\'s register.') }}
			</NcNoteCard>
			<NcNoteCard v-else-if="state === 'elsewhere'" type="warning" data-testid="archival-wiring-state">
				{{ t('filinq', 'The archival settings point at the register {owner}. Another app may own them; they change only when you link filinq\'s register here.', { owner: owner || t('filinq', 'another register') }) }}
			</NcNoteCard>
			<NcNoteCard v-else type="info" data-testid="archival-wiring-state">
				{{ t('filinq', 'OpenRegister has no archival register yet, so it cannot look up a selectielijst category or draw up a destruction list.') }}
			</NcNoteCard>

			<NcNoteCard v-if="wiring === null" type="warning" data-testid="archival-wiring-missing">
				{{ t('filinq', 'Filinq\'s register or its selectielijst and destruction list schemas are not imported, so there is nothing to link yet.') }}
			</NcNoteCard>

			<div v-else-if="state !== 'wired'" class="archival-wiring__action">
				<template v-if="confirming">
					<p data-testid="archival-wiring-confirm">
						{{ t('filinq', 'Point OpenRegister\'s selectielijst, destruction lists and archival register at filinq\'s register?') }}
					</p>
					<NcButton variant="primary" :disabled="saving" data-testid="archival-wiring-yes" @click="wire">
						{{ t('filinq', 'Link') }}
					</NcButton>
					<NcButton variant="tertiary" :disabled="saving" @click="confirming = false">
						{{ t('filinq', 'Cancel') }}
					</NcButton>
				</template>
				<NcButton v-else variant="secondary" data-testid="archival-wiring-link" @click="confirming = true">
					{{ t('filinq', 'Link the archive register') }}
				</NcButton>
			</div>

			<p v-if="error !== ''" class="archival-wiring__error" data-testid="archival-wiring-error">
				{{ error }}
			</p>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import { currentOwner, loadArchivalSettings, wireArchivalRegister, wiringFor, wiringState } from '../../services/archivalWiring.js'

// OpenRegister's archival settings change only through this button: no
// repair step or boot hook writes them, because another app may own them
// (archiefwet-retention-engine REQ-DDARE-002).
export default {
	name: 'ArchivalWiringSettings',
	components: {
		NcButton,
		NcNoteCard,
	},

	props: {
		/**
		 * The registers the settings page lists, schemas expanded.
		 */
		registers: {
			type: Array,
			default: () => [],
		},
	},

	data() {
		return {
			settings: null,
			readFailed: false,
			confirming: false,
			saving: false,
			error: '',
		}
	},

	computed: {
		/**
		 * What the action would write, or null when filinq's schemas are not there.
		 *
		 * @return {object|null}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
		 */
		wiring() {
			return wiringFor(this.registers)
		},

		/**
		 * Where the settings point now.
		 *
		 * @return {string} unset, wired or elsewhere.
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
		 */
		state() {
			return wiringState(this.settings, this.wiring)
		},

		/**
		 * The register that owns the selectielijst wiring now.
		 *
		 * @return {string}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
		 */
		owner() {
			return currentOwner(this.settings, this.registers)
		},
	},

	/**
	 * Reads OpenRegister's archival settings once; reading writes nothing.
	 *
	 * @return {Promise<void>}
	 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
	 */
	async mounted() {
		const read = await loadArchivalSettings()
		if (read.ok === true) {
			this.settings = read.data ?? {}
		} else {
			this.readFailed = true
		}
	},

	methods: {
		t,

		/**
		 * Write the wiring the admin confirmed, and show what OpenRegister stored.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
		 */
		async wire() {
			if (this.wiring === null) {
				return
			}

			this.saving = true
			this.error = ''
			const written = await wireArchivalRegister(this.settings, this.wiring)
			this.saving = false
			if (written.ok === true) {
				this.settings = written.data ?? { ...this.settings, ...this.wiring }
				this.confirming = false
				return
			}

			this.error = written.error || t('filinq', 'OpenRegister did not store the archival settings.')
		},
	},
}
</script>

<style scoped>
.archival-wiring__action {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: var(--default-grid-baseline);
	margin-top: calc(var(--default-grid-baseline) * 2);
}

.archival-wiring__action p {
	flex-basis: 100%;
	margin: 0;
	color: var(--color-main-text);
}

.archival-wiring__error {
	margin-top: var(--default-grid-baseline);
	color: var(--color-error-text);
}
</style>
