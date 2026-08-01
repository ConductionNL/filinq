<template>
	<div
		class="dd-view-toggle"
		role="group"
		:aria-label="ariaLabel">
		<!-- Sliding white background; sits behind the active segment -->
		<span
			class="dd-view-toggle__thumb"
			:class="{ 'dd-view-toggle__thumb--right': modelValue === 'list' }"
			aria-hidden="true" />

		<button
			type="button"
			class="dd-view-toggle__btn"
			:class="{ 'dd-view-toggle__btn--active': modelValue === 'tiles' }"
			:aria-pressed="modelValue === 'tiles'"
			@click="select('tiles')">
			<DdIcon name="tiles" :size="24" />
			<span class="dd-view-toggle__label">{{ tilesLabel }}</span>
		</button>

		<button
			type="button"
			class="dd-view-toggle__btn"
			:class="{ 'dd-view-toggle__btn--active': modelValue === 'list' }"
			:aria-pressed="modelValue === 'list'"
			@click="select('list')">
			<DdIcon name="list" :size="24" />
			<span class="dd-view-toggle__label">{{ listLabel }}</span>
		</button>
	</div>
</template>

<script>
import DdIcon from './DdIcon.vue'

/**
 * Segmented tiles/list view switch with a sliding white background.
 *
 * Controlled component: pass the active mode via `modelValue` (`'tiles'` or
 * `'list'`) and listen to `update:modelValue` for `v-model` two-way binding.
 * The white thumb animates left/right when the value changes.
 *
 * Usage:
 *   <DdViewToggle v-model="viewMode" />
 */
export default {
	name: 'DdViewToggle',
	components: {
		DdIcon,
	},
	// Vue 3 removed `model: { prop, event }`; a bare `v-model` always binds
	// `modelValue` + `update:modelValue`, so the prop and emit are renamed
	// rather than remapped. `change` is kept as a separate notification event.
	props: {
		/** Active view mode: `'tiles'` or `'list'`. */
		modelValue: {
			type: String,
			default: 'tiles',
			validator: (v) => ['tiles', 'list'].includes(v),
		},
		/** Label for the tiles segment. */
		tilesLabel: {
			type: String,
			default: 'Tiles',
		},
		/** Label for the list segment. */
		listLabel: {
			type: String,
			default: 'List',
		},
		/** Accessible name for the toggle group. */
		ariaLabel: {
			type: String,
			default: 'View mode',
		},
	},
	emits: ['update:modelValue', 'change'],
	methods: {
		/**
		 * Emit the chosen mode unless it is already active.
		 *
		 * @param {string} mode `'tiles'` or `'list'`.
		 *
		 * @spec exclude Local view-toggle UI control; no domain or persistence semantics.
		 */
		select(mode) {
			if (mode === this.modelValue) return
			this.$emit('update:modelValue', mode)
			this.$emit('change', mode)
		},
	},
}
</script>

<style scoped>
.dd-view-toggle {
	position: relative;
	display: inline-flex;
	padding: 4px;
	border-radius: var(--dd-radius-pill-full);
	background: var(--color-background-dark, #ededf0);
	box-sizing: border-box;
}

/* White pill that slides under the active segment */
.dd-view-toggle__thumb {
	position: absolute;
	top: 4px;
	left: 4px;
	width: calc(50% - 4px);
	height: calc(100% - 8px);
	border-radius: var(--dd-radius-pill-full);
	background: var(--color-main-background, #fff);
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
	transition: transform 0.25s ease;
	will-change: transform;
}

.dd-view-toggle__thumb--right {
	transform: translateX(100%);
}

.dd-view-toggle__btn {
	position: relative;
	z-index: 1;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	min-width: 0;
	flex: 1 1 50%;
	padding-block: 4px;
	padding-inline: 8px 12px;
	border: none;
	background: transparent;
	color: var(--color-text-maxcontrast, #61616c);
	cursor: pointer;
	white-space: nowrap;
	font-size: 14px;
	font-weight: 500;
	line-height: 140%;
	font-style: normal;
	transition: color 0.25s ease;
}

/* Neutralise the global NC button hover (background tint / border) */
.dd-view-toggle__btn:hover,
.dd-view-toggle__btn:focus,
.dd-view-toggle__btn:active {
	background: transparent;
	box-shadow: none;
}

.dd-view-toggle__btn:hover {
	color: var(--color-main-text, #02162e);
}

/* Keyboard focus ring (mouse hover stays clean) */
.dd-view-toggle__btn:focus-visible {
	outline: 2px solid var(--color-primary-element, #0b5fff);
	outline-offset: -2px;
	border-radius: var(--dd-radius-pill-full);
}

.dd-view-toggle__btn--active {
	color: var(--color-main-text, #02162e);
}

.dd-view-toggle__label {
	white-space: nowrap;
}
</style>
