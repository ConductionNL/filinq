<template>
	<div class="version-history">
		<h3>{{ t('docudesk', 'Version history') }}</h3>
		<NcLoadingIcon v-if="loading" />
		<div v-else-if="versions.length === 0" class="version-history__empty">
			{{ t('docudesk', 'No versions yet') }}
		</div>
		<ul v-else class="version-history__list">
			<li v-for="version in versions"
				:key="version.id"
				class="version-history__item">
				<div class="version-history__item-header">
					<strong>{{ t('docudesk', 'Version {n}', { n: version.version }) }}</strong>
					<span class="version-history__editor">{{ version.editor }}</span>
				</div>
				<div class="version-history__item-meta">
					<span v-if="version.dateCreated">{{ formatDate(version.dateCreated) }}</span>
					<span v-if="version.changelog" class="version-history__changelog">
						{{ version.changelog }}
					</span>
				</div>
				<div class="version-history__item-actions">
					<NcButton type="tertiary" @click="$emit('restore', version)">
						<template #icon>
							<Restore :size="16" />
						</template>
						{{ t('docudesk', 'Restore') }}
					</NcButton>
				</div>
			</li>
		</ul>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import Restore from 'vue-material-design-icons/Restore.vue'

export default {
	name: 'VersionHistory',
	components: {
		NcButton,
		NcLoadingIcon,
		Restore,
	},
	props: {
		versions: {
			type: Array,
			default: () => [],
		},
		loading: {
			type: Boolean,
			default: false,
		},
	},
	methods: {
		t,
		formatDate(dateString) {
			try {
				return new Date(dateString).toLocaleString()
			} catch {
				return dateString
			}
		},
	},
}
</script>

<style scoped lang="scss">
.version-history {
	padding: 16px;
	border-left: 1px solid var(--color-border);

	h3 {
		margin: 0 0 12px;
	}

	&__empty {
		color: var(--color-text-maxcontrast);
		padding: 20px 0;
		text-align: center;
	}

	&__list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	&__item {
		padding: 10px 0;
		border-bottom: 1px solid var(--color-border);

		&:last-child {
			border-bottom: none;
		}
	}

	&__item-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	&__editor {
		font-size: 12px;
		color: var(--color-text-maxcontrast);
	}

	&__item-meta {
		display: flex;
		gap: 8px;
		font-size: 12px;
		color: var(--color-text-maxcontrast);
		margin: 4px 0;
	}

	&__changelog {
		font-style: italic;
	}

	&__item-actions {
		margin-top: 4px;
	}
}
</style>
