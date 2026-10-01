<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Generate from a register object: one button per active wizard that asks for
an object of this register and schema. The wizard opens with what the object
answers already filled in, as suggestions to review. Renders nothing when no
wizard fits.

@spec openspec/changes/guided-document-wizard/tasks.md#4-3
-->

<template>
	<span v-if="wizards.length" class="wizard-entry-actions">
		<NcButton
			v-for="wizard in wizards"
			:key="wizard.uuid"
			variant="secondary"
			:data-testid="'wizard-entry-' + wizard.uuid"
			@click="open(wizard)">
			{{ t('filinq', 'Generate with wizard: {name}', { name: wizard.name }) }}
		</NcButton>
	</span>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import { listWizardsFor } from '../services/wizard.js'

export default {
	name: 'WizardEntryActions',
	components: { NcButton },

	props: {
		register: {
			type: String,
			required: true,
		},

		schema: {
			type: String,
			required: true,
		},

		objectId: {
			type: String,
			required: true,
		},
	},

	data() {
		return { wizards: [] }
	},

	/**
	 * Load the wizards that fit this object.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#4-3
	 */
	async mounted() {
		const result = await listWizardsFor(this.register, this.schema)
		this.wizards = result.ok ? result.data.results || [] : []
	},

	methods: {
		t,

		/**
		 * Open the wizard with this object as its entry.
		 *
		 * @param {object} wizard The wizard.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-3
		 */
		open(wizard) {
			this.$router.push({
				name: 'WizardRunner',
				params: { id: wizard.templateId },
				query: {
					register: this.register,
					schema: this.schema,
					objectId: this.objectId,
				},
			})
		},
	},
}
</script>

<style scoped>
.wizard-entry-actions {
	display: contents;
}
</style>
