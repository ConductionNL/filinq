<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog } from '@nextcloud/vue'

// Shown when the prohibition guard refuses a skip decision (HTTP 422).
// `block` is the endpoint's 422 body: { threshold, prohibitionMatch: {...} }.
// The force action is offered only for a sub-threshold (releasable) match —
// an absolute match (confidence >= threshold) cannot be skipped at all.
export default {
	name: 'ProhibitionBlockedDialog',
	components: { NcButton, NcDialog },
	props: {
		open: { type: Boolean, default: false },
		block: { type: Object, default: null },
	},
	emits: ['update:open', 'force'],
	computed: {
		match() {
			return this.block?.prohibitionMatch ?? null
		},
		releasable() {
			return this.match ? this.match.absolute === false : false
		},
	},
	methods: {
		t,
		close() {
			this.$emit('update:open', false)
		},
		confirmForce() {
			this.$emit('force')
			this.$emit('update:open', false)
		},
	},
}
</script>

<template>
	<NcDialog
		:open="open"
		:name="t('docudesk', 'Entity may not be skipped')"
		@update:open="$emit('update:open', $event)">
		<div v-if="match" class="prohibition-blocked">
			<p>
				{{ t('docudesk', '“{name}” matches the prohibition rule “{rule}” and must be anonymised.', { name: match.entityName, rule: match.ruleName }) }}
			</p>
			<p class="confidence">
				{{ t('docudesk', 'Detection confidence {conf}% (threshold {thr}%).', { conf: Math.round((match.confidence || 0) * 100), thr: Math.round((block.threshold || 0) * 100) }) }}
			</p>
			<p v-if="!releasable" class="warn-text">
				{{ t('docudesk', 'This match is at or above the threshold and cannot be skipped.') }}
			</p>
			<p v-else>
				{{ t('docudesk', 'You may override and skip it anyway; the override is recorded in the audit trail.') }}
			</p>
		</div>
		<template #actions>
			<NcButton @click="close">
				{{ t('docudesk', 'Keep anonymised') }}
			</NcButton>
			<NcButton v-if="releasable" variant="warning" @click="confirmForce">
				{{ t('docudesk', 'Skip anyway (force)') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.prohibition-blocked p {
	margin-block: 4px;
}

.prohibition-blocked .confidence {
	color: var(--color-text-maxcontrast);
}
</style>
