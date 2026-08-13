<template>
	<label
		class="dd-toggle"
		:class="{ 'dd-toggle--checked': checked, 'dd-toggle--disabled': disabled }">
		<!-- Visually-hidden native checkbox keeps the control keyboard- and
		     screen-reader-accessible; the pill below is purely presentational. -->
		<input
			type="checkbox"
			class="dd-toggle__input"
			:checked="checked"
			:disabled="disabled"
			@change="$emit('update:checked', $event.target.checked)" />
		<span class="dd-toggle__track" aria-hidden="true">
			<span class="dd-toggle__knob" />
		</span>
		<span v-if="$slots.default || label" class="dd-toggle__label">
			<slot>{{ label }}</slot>
		</span>
	</label>
</template>

<script>
/**
 * Pill switch for DocuDesk.
 *
 * A 40×24 rounded track with a 20px knob that slides right when on, matching
 * the design-system toggle. The track turns blue (`--dd-color-blue`) when
 * checked and grey when off. An optional trailing label sits right of the
 * pill; pass it via the `label` prop or the default slot.
 *
 * Accessibility: a visually-hidden native `<input type="checkbox">` carries
 * the real state and focus, so the control is keyboard- and
 * screen-reader-operable; the pill is `aria-hidden` decoration.
 *
 * Usage:
 *   <DdToggle :checked="on" @update:checked="on = $event">Label</DdToggle>
 *   <DdToggle v-model:checked="on">Label</DdToggle>
 */
export default {
	name: 'DdToggle',
	// Vue 3 removed `model: { prop, event }`. The prop/event pair is kept as
	// `checked` / `update:checked` — that is the documented API and what the
	// only call site uses — which in Vue 3 is spelled `v-model:checked` when
	// two-way binding is wanted.
	props: {
		/** Whether the switch is on. */
		checked: {
			type: Boolean,
			default: false,
		},
		/** Trailing label text. Ignored when the default slot is used. */
		label: {
			type: String,
			default: '',
		},
		/** Disables interaction and dims the control. */
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['update:checked'],
}
</script>

<style scoped>
.dd-toggle {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	cursor: pointer;
}

.dd-toggle--disabled {
	cursor: default;
	opacity: 0.5;
}

/* Visually hidden, but still focusable and in the tab order. */
.dd-toggle__input {
	position: absolute;
	width: 1px;
	height: 1px;
	margin: -1px;
	padding: 0;
	border: 0;
	overflow: hidden;
	clip: rect(0 0 0 0);
	white-space: nowrap;
}

.dd-toggle__track {
	position: relative;
	flex-shrink: 0;
	width: 40px;
	height: 24px;
	border-radius: var(--dd-radius-pill-full, 12px);
	background: var(--color-border-dark, #d0d0d0);
	transition: background-color 0.15s ease;
}

.dd-toggle__knob {
	position: absolute;
	top: 2px;
	left: 2px;
	width: 20px;
	height: 20px;
	border-radius: 50%;
	background: var(--dd-color-white, #fff);
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
	transition: transform 0.15s ease;
}

.dd-toggle--checked .dd-toggle__track {
	background: var(--dd-color-blue, #2874d1);
}

/* Slide the knob to the right edge (40 − 20 − 2 − 2 = 16px). */
.dd-toggle--checked .dd-toggle__knob {
	transform: translateX(16px);
}

/* Focus ring on the pill when the hidden input is keyboard-focused. */
.dd-toggle__input:focus-visible + .dd-toggle__track {
	outline: 2px solid var(--dd-color-blue, #2874d1);
	outline-offset: 2px;
}

.dd-toggle__label {
	font-size: 14px;
	line-height: 140%;
	color: var(--color-main-text, #02162e);
}

/* WCAG 2.2 SC 2.3.3 — the knob still moves and the track still recolours, it
   just arrives instantly for users who ask the OS for reduced motion. */
@media (prefers-reduced-motion: reduce) {
	.dd-toggle__track,
	.dd-toggle__knob {
		transition: none;
	}
}
</style>
