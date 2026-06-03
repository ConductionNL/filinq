<template>
	<div class="dd-search-bar" :class="{ 'dd-search-bar--has-value': hasValue }">
		<DdIcon name="search" :size="24" class="dd-search-bar__icon" />
		<input
			ref="input"
			v-model="localValue"
			type="text"
			class="dd-search-bar__input"
			:placeholder="placeholder"
			:aria-label="ariaLabel || placeholder"
			@input="onInput"
			@keydown.esc="clear">
		<button
			v-if="clearable && hasValue"
			type="button"
			class="dd-search-bar__clear"
			:aria-label="clearLabel"
			:title="clearLabel"
			@click="clear">
			<DdIcon name="close" :size="16" />
		</button>
	</div>
</template>

<script>
/**
 * Reusable search input for DocuDesk.
 *
 * - v-model on the search query string.
 * - Optional clear button (X) when there is a value.
 * - Optional debounce on the emitted `input` event so consumers
 *   are not hit on every keystroke.
 */
import DdIcon from './DdIcon.vue'

export default {
	name: 'DdSearchBar',
	components: { DdIcon },
	model: {
		prop: 'value',
		event: 'input',
	},
	props: {
		/** Current search value (v-model). */
		value: {
			type: String,
			default: '',
		},
		/** Placeholder text shown when the input is empty. */
		placeholder: {
			type: String,
			default: '',
		},
		/** Optional aria-label; falls back to placeholder. */
		ariaLabel: {
			type: String,
			default: '',
		},
		/** Show a clear (X) button when there is a value. */
		clearable: {
			type: Boolean,
			default: true,
		},
		/** Debounce in ms before emitting the new value. 0 disables debounce. */
		debounce: {
			type: Number,
			default: 200,
		},
		/** Translated label for the clear button (and aria). */
		clearLabel: {
			type: String,
			default: 'Clear',
		},
	},
	data() {
		return {
			localValue: this.value,
			debounceTimer: null,
		}
	},
	computed: {
		hasValue() {
			return this.localValue !== '' && this.localValue != null
		},
	},
	watch: {
		/**
		 * Sync external value changes (e.g. parent reset) into the local copy
		 * without re-emitting.
		 *
		 * @param {string} newValue New value coming from the parent.
		 */
		value(newValue) {
			if (newValue !== this.localValue) {
				this.localValue = newValue
			}
		},
	},
	beforeDestroy() {
		if (this.debounceTimer) {
			clearTimeout(this.debounceTimer)
		}
	},
	methods: {
		/**
		 * Schedule a debounced emit; with debounce=0 emits immediately.
		 */
		onInput() {
			if (this.debounceTimer) {
				clearTimeout(this.debounceTimer)
			}
			if (!this.debounce) {
				this.$emit('input', this.localValue)
				return
			}
			this.debounceTimer = setTimeout(() => {
				this.$emit('input', this.localValue)
			}, this.debounce)
		},
		/**
		 * Clear the input, emit the empty value immediately (bypassing debounce),
		 * and refocus the input so the user can keep typing.
		 */
		clear() {
			if (this.debounceTimer) {
				clearTimeout(this.debounceTimer)
				this.debounceTimer = null
			}
			this.localValue = ''
			this.$emit('input', '')
			this.$nextTick(() => {
				this.$refs.input?.focus?.()
			})
		},
		/** Programmatic focus, exposed to parents via $refs. */
		focus() {
			this.$refs.input?.focus?.()
		},
	},
}
</script>

<style scoped>
.dd-search-bar {
	position: relative;
	display: flex;
	align-items: center;
	max-width: 360px;
	flex: 1;
}

.dd-search-bar__icon {
	position: absolute;
	left: 14px;
	color: var(--color-text-maxcontrast);
	pointer-events: none;
}

.dd-search-bar__input {
	width: 100%;
	padding: 10px 40px 10px 48px;
	border: 1px solid var(--dd-search-bar-border, #D9D9D9);
	border-radius: 999px;
	background: var(--color-main-background, #FFF);
	font-size: 14px;
	font-weight: 300;
	box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.09);
	min-width: 392px;
	max-width: 100%;
	min-height: 40px;
}

.dd-search-bar__input::placeholder {
	font-weight: 300;
}

.dd-search-bar__input:focus {
	outline: none;
	border-color: var(--color-primary-element);
}

.dd-search-bar__clear {
	position: absolute;
	right: 8px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 28px;
	height: 28px;
	padding: 0;
	border: none;
	border-radius: 50%;
	background: transparent;
	color: var(--color-text-maxcontrast);
	cursor: pointer;
	transition: background-color 0.15s ease, color 0.15s ease;
}

.dd-search-bar__clear:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}

.dd-search-bar__clear:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 1px;
}
</style>
