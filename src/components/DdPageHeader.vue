<template>
	<div class="dd-page-header">
		<div v-if="icon || $slots.icon" class="dd-page-header__icon">
			<slot name="icon">
				<DdIcon :name="icon" :size="iconSize" />
			</slot>
		</div>
		<div class="dd-page-header__text">
			<h1 class="dd-page-header__title">
				{{ title }}
			</h1>
			<p v-if="description" class="dd-page-header__description">
				{{ description }}
			</p>
		</div>
		<div v-if="$slots.actions" class="dd-page-header__actions">
			<slot name="actions" />
		</div>
	</div>
</template>

<script>
/**
 * Reusable page header for DocuDesk views.
 *
 * Renders a page-level title with optional description, optional leading
 * icon (DdIcon by name, or fully custom via the `icon` slot), and an
 * optional `actions` slot for right-aligned controls (buttons, badges).
 *
 * Usage:
 *   <DdPageHeader :title="t('docudesk', 'Documents')" />
 *   <DdPageHeader title="Anonymization" icon="shield" description="..." />
 */
import DdIcon from './DdIcon.vue'

export default {
	name: 'DdPageHeader',
	components: { DdIcon },
	props: {
		/** Page title text. */
		title: {
			type: String,
			required: true,
		},
		/** Optional sub-line shown below the title. */
		description: {
			type: String,
			default: '',
		},
		/** Optional DdIcon name shown left of the title. */
		icon: {
			type: String,
			default: '',
		},
		/** Square icon size in pixels. */
		iconSize: {
			type: Number,
			default: 28,
		},
	},
}
</script>

<style scoped>
.dd-page-header {
	display: flex;
	align-items: center;
	gap: var(--dd-page-header-gap, 24px);
	padding-block: var(--dd-page-header-padding-block, 20px);
	padding-inline: var(--dd-page-header-padding-inline, 20px);
}

.dd-page-header__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	color: var(--color-main-text);
	flex-shrink: 0;
}

.dd-page-header__text {
	display: flex;
	flex-direction: column;
	justify-content: center;
	gap: 2px;
	min-width: 0;
	min-height: 40px;
	flex: 1;
}

.dd-page-header__title {
	font-size: 1.5rem;
	font-weight: 600;
	line-height: 1.2;
	margin: 0;
	color: var(--color-main-text);
	word-break: break-word;
}

.dd-page-header__description {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-size: 0.95rem;
}

.dd-page-header__actions {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-left: auto;
}
</style>
