<template>
	<div class="template-detail">
		<NcButton type="tertiary" @click="navigationStore.setSelected('templates')">
			{{ t('docudesk', 'Back to templates') }}
		</NcButton>
		<h2>{{ t('docudesk', 'Template editor') }}</h2>
		<p>{{ t('docudesk', 'Advanced template editing with WYSIWYG, preview, versioning, and conditional sections.') }}</p>
	</div>
</template>
<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import { navigationStore } from '../../store/store.js'

export default {
	name: 'TemplateDetail',
	components: { NcButton },
	data() {
		return { navigationStore }
	},
	methods: { t },
}
</script>
