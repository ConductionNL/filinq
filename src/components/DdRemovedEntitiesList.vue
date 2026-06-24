<script setup>
import { translate as t } from '@nextcloud/l10n'
import { NcNoteCard, NcButton } from '@nextcloud/vue'
import DdEntityCard from './DdEntityCard.vue'
</script>

<template>
	<div class="entities-list">
		<!-- Reveal note: original values stay hidden behind an explicit toggle
		     so showing the list doesn't silently re-expose the data the file
		     hid. Default is revealed — reviewing what was removed is the point. -->
		<NcNoteCard type="warning" class="reveal-note">
			<div>{{ t('docudesk', 'These items were removed from this document.') }}</div>
			<NcButton type="tertiary" class="reveal-toggle" @click="revealValues = !revealValues">
				{{ revealValues
					? t('docudesk', 'Hide original values')
					: t('docudesk', 'Reveal original values') }}
			</NcButton>
		</NcNoteCard>
		<DdEntityCard
			v-for="(item, idx) in items"
			:key="'removed-' + idx"
			:item="item"
			mode="anonymized"
			:reveal-values="revealValues" />
	</div>
</template>

<script>
/**
 * Read-only list of entities removed from an anonymised document.
 *
 * Renders one `DdEntityCard` (mode `anonymized`) per item with a shared
 * "reveal original values" toggle. Used in two places that previously each
 * duplicated this markup: the post-anonymise download step and the re-opened
 * anonymised-document view in `FileViewerSidebar`. The component owns its own
 * reveal state, so each list toggles independently.
 */
export default {
	name: 'DdRemovedEntitiesList',
	components: {
		NcNoteCard,
		NcButton,
		DdEntityCard,
	},
	props: {
		/**
		 * Anonymised-card rows to render. Each item follows the
		 * `mode="anonymized"` shape: { type, value, placeholder, count, bases,
		 * _resolveError }.
		 */
		items: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			// Show original values by default — the panel exists to review what
			// was removed. The toggle still lets the user hide them again (e.g.
			// while screen-sharing).
			revealValues: true,
		}
	},
}
</script>

<style lang="scss" scoped>
.entities-list {
	display: flex;
	flex-direction: column;
}

.reveal-note .reveal-toggle {
	margin-top: 6px;
}
</style>
