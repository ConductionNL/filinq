<template>
	<div class="template-index">
		<div class="template-index__header">
			<h2>{{ t('docudesk', 'Templates') }}</h2>
			<NcButton type="primary" @click="$router.push({ name: 'TemplateNew' })">
				{{ t('docudesk', 'New template') }}
			</NcButton>
		</div>
		<NcLoadingIcon v-if="templateStore.loading" />
		<table v-else-if="templateStore.templates.length > 0" class="template-index__table">
			<thead>
				<tr>
					<th>{{ t('docudesk', 'Name') }}</th>
					<th>{{ t('docudesk', 'Category') }}</th>
					<th>{{ t('docudesk', 'Namespace') }}</th>
					<th>{{ t('docudesk', 'Status') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="tmpl in templateStore.templates" :key="tmpl.id">
					<td>{{ tmpl.name }}</td>
					<td>{{ tmpl.category || '-' }}</td>
					<td>{{ tmpl.namespace }}</td>
					<td>
						<span v-if="tmpl.lockedBy">{{ t('docudesk', 'Locked by {user}', { user: tmpl.lockedBy }) }}</span>
					</td>
				</tr>
			</tbody>
		</table>
		<NcEmptyContent v-else :name="t('docudesk', 'No templates found')" />
	</div>
</template>
<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { useTemplateStore } from '../../store/modules/template.js'

export default {
	name: 'TemplateIndex',
	components: { NcButton, NcEmptyContent, NcLoadingIcon },
	computed: {
		templateStore() { return useTemplateStore() },
	},
	mounted() { this.templateStore.fetchTemplates() },
	methods: { t },
}
</script>
