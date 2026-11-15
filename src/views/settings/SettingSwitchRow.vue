<!--
  SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  SPDX-License-Identifier: EUPL-1.2

  One on/off setting of the admin settings page: the label, the switch and
  the description under it. Attributes such as data-testid go to the switch.
-->
<template>
	<div class="setting-item">
		<div class="setting-label">
			{{ label }}
		</div>
		<NcCheckboxRadioSwitch
			:aria-label="label"
			:modelValue="modelValue"
			type="switch"
			v-bind="$attrs"
			@update:modelValue="$emit('update:modelValue', $event)" />
		<div class="setting-description">
			{{ description }}
		</div>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'

export default {
	name: 'SettingSwitchRow',
	components: {
		NcCheckboxRadioSwitch,
	},

	inheritAttrs: false,

	props: {
		/** The setting's name, also the switch's accessible name. */
		label: {
			type: String,
			required: true,
		},

		/** What the setting does. */
		description: {
			type: String,
			required: true,
		},

		/** Whether the setting is on, passed to the switch as it is. */
		modelValue: {
			type: null,
			default: undefined,
		},
	},

	emits: ['update:modelValue'],
}
</script>

<style scoped src="./settingItem.css"></style>
