<template>
	<button
		:type="type"
		class="dd-button"
		:class="`dd-button--${variant}`"
		v-bind="$attrs"
		v-on="$listeners">
		<DdIcon
			v-if="icon"
			:name="icon"
			:size="iconSize"
			class="dd-button__icon" />
		<span class="dd-button__label">
			<slot>{{ label }}</slot>
		</span>
	</button>
</template>

<script>
import DdIcon from './DdIcon.vue'

/**
 * Pill button for DocuDesk.
 *
 * A fully-round button in one of three design-system variants:
 *   - `primary`   — blue fill, inset highlight, white text (main CTA).
 *   - `secondary` — transparent fill, dark-blue outline + text.
 *   - `tertiary`  — light-grey fill, white border, inset-white highlight.
 *
 * Size is driven by padding (never a fixed `height`), so all variants
 * share the same total height. An optional leading icon (resolved by
 * name through `DdIcon`) sits left of the label. All native attributes
 * and listeners (e.g. `disabled`, `@click`) are forwarded to the
 * underlying `<button>`.
 *
 * Usage:
 *   <DdButton variant="primary" :label="t('docudesk', 'Anonymize')" @click="run" />
 *   <DdButton icon="add" :label="t('docudesk', 'Select files')" @click="pick" />
 *   <DdButton><CustomMarkup /></DdButton>
 */
export default {
	name: 'DdButton',
	components: {
		DdIcon,
	},
	inheritAttrs: false,
	props: {
		/** Button text. Ignored when the default slot is used. */
		label: {
			type: String,
			default: '',
		},
		/**
		 * Leading icon name — matches an SVG in `src/assets/icons/`
		 * (see `DdIcon`). Empty string renders no icon.
		 */
		icon: {
			type: String,
			default: '',
		},
		/** Square icon size in pixels. */
		iconSize: {
			type: [Number, String],
			default: 20,
		},
		/** Native button `type`. */
		type: {
			type: String,
			default: 'button',
			validator: (v) => ['button', 'submit', 'reset'].includes(v),
		},
		/** Visual variant: `'primary'`, `'secondary'` or `'tertiary'`. */
		variant: {
			type: String,
			default: 'tertiary',
			validator: (v) => ['primary', 'secondary', 'tertiary'].includes(v),
		},
	},
}
</script>

<style scoped>
.dd-button {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	/* No fixed height — padding drives the total height so every variant
	   lines up at the same size. */
	padding: 12px 20px;
	border: 1px solid transparent;
	border-radius: var(--dd-radius-pill-full);
	color: var(--color-main-text, #02162e);
	font-size: 14px;
	font-weight: 500;
	line-height: 140%;
	white-space: nowrap;
	cursor: pointer;
	flex-shrink: 0;
	transition:
		background-color 0.15s ease,
		border-color 0.15s ease,
		box-shadow 0.15s ease,
		color 0.15s ease;
}

.dd-button__icon {
	color: currentColor;
}

/* Primary: blue fill, translucent-white border, inset highlight, white text */
.dd-button--primary {
	border-color: var(--dd-color-white-70, rgba(255, 255, 255, 0.70));
	background: var(--dd-color-blue, #2874d1);
	color: var(--dd-color-white, #fff);
	box-shadow:
		0 -2px 7px 0 #4698fc inset,
		0 5px 8px 0 rgba(255, 255, 255, 0.26) inset,
		0 4px 20px 0 rgba(0, 0, 0, 0.09);
}

/* Interactive states use !important to override Nextcloud core
   (core/css/inputs.css), whose generic `button:not(.button-vue,…)` rules
   — and especially its `:focus-visible` outline + box-shadow — are
   declared !important and otherwise win. */
.dd-button--primary:hover,
.dd-button--primary:focus {
	background: var(--dd-color-blue, #2874d1) !important;
	border-color: var(--dd-color-white-70, rgba(255, 255, 255, 0.70)) !important;
	color: var(--dd-color-white, #fff) !important;
	box-shadow:
		0 -2px 7px 0 #4698fc inset,
		0 5px 8px 0 rgba(255, 255, 255, 0.26) inset,
		0 6px 22px 0 rgba(0, 0, 0, 0.13) !important;
}

.dd-button--primary:focus-visible {
	outline: 2px solid var(--dd-color-white-70, rgba(255, 255, 255, 0.70)) !important;
	outline-offset: 2px;
}

/* Secondary: transparent fill, dark-blue outline + text */
.dd-button--secondary {
	border-color: var(--dd-color-dark-blue, #02162e);
	background: transparent;
	color: var(--dd-color-dark-blue, #02162e);
}

.dd-button--secondary:hover,
.dd-button--secondary:focus {
	border-color: var(--dd-color-dark-blue, #02162e) !important;
	background: var(--dd-color-light-grey, rgba(247, 247, 247, 0.70)) !important;
	color: var(--dd-color-dark-blue, #02162e) !important;
}

.dd-button--secondary:focus-visible {
	outline: 2px solid var(--dd-color-dark-blue, #02162e) !important;
	outline-offset: 2px;
	box-shadow: none !important;
}

/* Tertiary: light-grey fill, white border, inset-white + soft drop shadow */
.dd-button--tertiary {
	border-color: var(--dd-color-white, #fff);
	background: var(--dd-color-light-grey, rgba(247, 247, 247, 0.70));
	box-shadow:
		0 -2px 7px 0 var(--dd-color-white, #fff) inset,
		0 5px 8px 0 var(--dd-color-white, #fff) inset,
		0 4px 20px 0 rgba(0, 0, 0, 0.09);
}

/* Hover: solid white fill, border stays white (no dark outline). */
.dd-button--tertiary:hover {
	background: var(--dd-color-white, #fff) !important;
	border-color: var(--dd-color-white, #fff) !important;
	box-shadow:
		0 -2px 7px 0 var(--dd-color-white, #fff) inset,
		0 5px 8px 0 var(--dd-color-white, #fff) inset,
		0 6px 22px 0 rgba(0, 0, 0, 0.13) !important;
}

/* Focus: dark border, no NC ring (suppress NC's forced outline + shadow). */
.dd-button--tertiary:focus-visible {
	border-color: var(--dd-color-dark-blue, #02162e) !important;
	outline: none !important;
	box-shadow:
		0 -2px 7px 0 var(--dd-color-white, #fff) inset,
		0 5px 8px 0 var(--dd-color-white, #fff) inset,
		0 4px 20px 0 rgba(0, 0, 0, 0.09) !important;
}

.dd-button:disabled {
	opacity: 0.5;
	cursor: default;
}

.dd-button__label {
	white-space: nowrap;
}
</style>
