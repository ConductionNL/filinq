<script setup>
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { NcSelect } from '@nextcloud/vue'
import DdSkeleton from './DdSkeleton.vue'
</script>

<template>
	<!-- Loading skeleton — no item; placeholder while extraction resolves. -->
	<div v-if="loading" class="dd-entity-card dd-entity-card--skeleton">
		<DdSkeleton variant="text" :rows="2" />
		<div class="dd-entity-card__skeleton-row">
			<DdSkeleton variant="row" width="40%" height="24px" />
			<DdSkeleton variant="row" width="32px" height="24px" />
		</div>
	</div>

	<!-- Anonymised-document view — read-only, value hidden behind reveal. -->
	<div v-else-if="mode === 'anonymized'" class="dd-entity-card">
		<div class="dd-entity-card__header">
			<span class="dd-entity-card__type">{{ item.type }}</span>
			<span class="dd-entity-card__confidence">
				{{ n('docudesk', '%n occurrence', '%n occurrences', item.count) }}
			</span>
		</div>
		<div
			v-if="revealValues"
			class="dd-entity-card__value"
			:title="item.value || ''">
			{{ item.value || t('docudesk', 'Unknown value') }}
		</div>
		<div v-else class="dd-entity-card__value dd-entity-card__value--hidden">
			{{ item.placeholder }}
		</div>
		<div v-if="item.bases && item.bases.length" class="dd-entity-card__bases-tags">
			<span v-for="b in item.bases" :key="b" class="dd-entity-card__basis-tag">{{ b }}</span>
		</div>
		<div v-if="item._resolveError" class="dd-entity-card__error" :title="item._resolveError">
			{{ item._resolveError }}
		</div>
	</div>

	<!-- Review view — editable: include checkbox + grondslagen select. -->
	<div
		v-else
		class="dd-entity-card"
		:class="{
			'dd-entity-card--excluded': !item.included,
			'dd-entity-card--readonly': !editable,
		}">
		<div class="dd-entity-card__header">
			<input
				type="checkbox"
				class="dd-entity-card__checkbox"
				:checked="item.included"
				:disabled="!editable"
				:aria-label="t('docudesk', 'Include in anonymisation')"
				@change="$emit('toggle')">
			<span class="dd-entity-card__type">{{ item.type }}</span>
			<span class="dd-entity-card__confidence">
				{{ ((item.confidence || 0) * 100).toFixed(0) }}%
			</span>
		</div>
		<div class="dd-entity-card__value" :title="item.value">
			{{ item.value }}
		</div>
		<div class="dd-entity-card__controls">
			<NcSelect
				class="dd-entity-card__bases"
				:value="item._decisionBases || []"
				:options="basesOptions"
				:multiple="true"
				:input-label="t('docudesk', 'Grondslagen')"
				:placeholder="t('docudesk', 'Pick grondslagen…')"
				:disabled="!editable || !hasRelation"
				@input="$emit('set-bases', $event)" />
		</div>
		<div v-if="item._patchError" class="dd-entity-card__error" :title="item._patchError">
			{{ item._patchError }}
		</div>
	</div>
</template>

<script>
/**
 * Single detected-entity card for the file-viewer sidebar.
 *
 * Renders one of three states selected by props:
 *   - `loading`            → skeleton placeholder (no `item` required).
 *   - `mode="anonymized"`  → read-only summary of a removed entity, with
 *                            its original value hidden behind `revealValues`.
 *   - `mode="review"`      → editable: include checkbox + grondslagen select.
 *
 * The card owns no store state; it emits `toggle` and `set-bases` so the
 * parent can map them onto the right queue entry / index.
 */
export default {
	name: 'DdEntityCard',
	props: {
		/**
		 * Entity row. Required unless `loading` is set.
		 *   review:     { type, value, confidence, included, _decisionBases,
		 *                 relationIds, _patchError }
		 *   anonymized: { type, value, placeholder, count, bases, _resolveError }
		 */
		item: {
			type: Object,
			default: null,
		},
		/**
		 * Which card variant to render: 'review' (editable) or 'anonymized'
		 * (read-only). Ignored when `loading` is true.
		 */
		mode: {
			type: String,
			default: 'review',
			validator: (v) => ['review', 'anonymized'].includes(v),
		},
		/**
		 * Render the skeleton placeholder instead of an entity.
		 */
		loading: {
			type: Boolean,
			default: false,
		},
		/**
		 * Anonymised view only — show the original value instead of the
		 * `[<TYPE>: <id>]` placeholder.
		 */
		revealValues: {
			type: Boolean,
			default: false,
		},
		/**
		 * Review view only — options for the grondslagen multiselect.
		 */
		basesOptions: {
			type: Array,
			default: () => [],
		},
		/**
		 * Review view only — whether the card is editable. When `false`
		 * (grondslagen toggle off) the include checkbox and grondslagen
		 * select are disabled; the entity keeps its default values and the
		 * card reads as fixed. Live-reactive to the sidebar header toggle.
		 */
		editable: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['toggle', 'set-bases'],
	computed: {
		/**
		 * Whether the entity is backed by an OpenRegister relation — the
		 * grondslagen control only persists when a relation id exists.
		 *
		 * @return {boolean}
		 */
		hasRelation() {
			return Array.isArray(this.item?.relationIds) && this.item.relationIds.length > 0
		},
	},
}
</script>

<style lang="scss" scoped>
.dd-entity-card {
	padding: 10px 12px;
	border: 1px solid var(--color-border);
	background-color: var(--color-main-background);
	display: flex;
	flex-direction: column;
	gap: 6px;
	transition: opacity 0.15s ease;

	&--excluded {
		opacity: 0.55;
	}

	/* Read-only review card (grondslagen toggle off): the controls are
	 * disabled rather than hidden so the user still sees the fixed default
	 * values. The not-allowed cursor signals the card can't be edited. */
	&--readonly {
		.dd-entity-card__checkbox {
			cursor: not-allowed;
		}
	}

	&--skeleton {
		padding: 12px;
	}
}

.dd-entity-card__header {
	display: flex;
	align-items: center;
	gap: 8px;
}

.dd-entity-card__checkbox {
	flex: 0 0 auto;
}

.dd-entity-card__type {
	flex: 1 1 auto;
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	padding: 2px 8px;
	border-radius: var(--border-radius-large);
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element);
	display: inline-block;
	max-width: max-content;
}

.dd-entity-card__confidence {
	flex: 0 0 auto;
	font-size: 0.8rem;
	color: var(--color-text-maxcontrast);
}

.dd-entity-card__value {
	font-size: 0.95rem;
	font-weight: 500;
	overflow-wrap: anywhere;
}

.dd-entity-card__controls {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.dd-entity-card__bases {
	width: 100%;
}

.dd-entity-card__error {
	color: var(--color-error);
	font-size: 0.75rem;
}

/* Anonymised-document view — placeholder shown until the user reveals the
 * original value, plus the read-only grondslagen tags. */
.dd-entity-card__value--hidden {
	font-family: var(--font-face-monospace, monospace);
	color: var(--color-text-maxcontrast);
	font-weight: 400;
}

.dd-entity-card__bases-tags {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}

.dd-entity-card__basis-tag {
	font-size: 0.7rem;
	padding: 1px 6px;
	border-radius: 10px;
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.dd-entity-card__skeleton-row {
	display: flex;
	gap: 8px;
	align-items: center;
	margin-top: 8px;
}
</style>
