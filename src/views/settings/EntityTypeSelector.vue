<template>
	<div class="entity-type-selector">
		<p class="entity-type-selector__hint">
			{{ hint }}
		</p>
		<div v-if="options.length > 0" class="entity-type-selector__grid">
			<NcCheckboxRadioSwitch
				v-for="type in options"
				:key="type"
				:model-value="isEnabled(type)"
				type="switch"
				@update:modelValue="toggle(type, $event)">
				{{ entityTypeLabel(type) }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { entityTypeLabel } from '../../services/entityTypes.js'

// Self-contained selector for the entity types detected automatically.
//
// Deliberately a controlled component: `value` is the enabled-type list and
// every toggle emits a brand-new array via `input` (v-model), so the parent
// remains the single owner of the selection. Kept isolated in its own file so
// it can be dropped into the settings page with a one-line insertion and
// reconciled against the in-development frontend with minimal conflict.
export default {
	name: 'EntityTypeSelector',
	components: {
		NcCheckboxRadioSwitch,
	},
	props: {
		// Entity types currently enabled for automatic detection.
		value: {
			type: Array,
			default: () => [],
		},
		// All selectable entity types (the curated list from the backend).
		options: {
			type: Array,
			default: () => [],
		},
	},
	computed: {
		// True when every selectable type is enabled — the point at which the
		// backend treats the selection as "detect all" (no whitelist sent).
		allEnabled() {
			return this.options.length > 0
				&& this.options.every((type) => this.value.includes(type))
		},
		hint() {
			if (this.options.length === 0) {
				return t('docudesk', 'No entity types are available.')
			}
			if (this.allEnabled) {
				return t('docudesk', 'All entity types are detected. Turn a type off to stop detecting it automatically — you can still add it manually per document.')
			}
			return t('docudesk', 'Only the enabled types are detected automatically. Disabled types can still be added manually per document.')
		},
	},
	methods: {
		entityTypeLabel,
		isEnabled(type) {
			return this.value.includes(type)
		},
		// Emit a fresh array (never mutate the prop) in the canonical option
		// order, so reactivity holds and the stored order is stable.
		toggle(type, checked) {
			const next = this.options.filter((candidate) => {
				if (candidate === type) {
					return checked
				}
				return this.value.includes(candidate)
			})
			this.$emit('input', next)
		},
	},
}
</script>

<style scoped>
.entity-type-selector__hint {
	color: var(--color-text-maxcontrast);
	margin-block-end: 8px;
}

.entity-type-selector__grid {
	display: flex;
	flex-wrap: wrap;
	gap: 4px 24px;
}
</style>
