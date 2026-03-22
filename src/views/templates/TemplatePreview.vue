<template>
	<div class="template-preview">
		<h3>{{ t('docudesk', 'Preview') }}</h3>
		<div class="template-preview__data">
			<label>{{ t('docudesk', 'Sample data (JSON)') }}</label>
			<textarea v-model="sampleDataJson"
				class="template-preview__textarea"
				:placeholder="t('docudesk', '{ &quot;naam&quot;: &quot;Jan de Vries&quot;, &quot;datum&quot;: &quot;2026-03-22&quot; }')" />
			<NcButton type="primary"
				:disabled="loading"
				@click="onPreview">
				{{ t('docudesk', 'Render preview') }}
			</NcButton>
		</div>
		<div v-if="previewHtml" class="template-preview__output" v-html="previewHtml" />
		<div v-if="error" class="template-preview__error">
			{{ error }}
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import { useTemplateStore } from '../../store/modules/template.js'

export default {
	name: 'TemplatePreview',
	components: {
		NcButton,
	},
	props: {
		content: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			sampleDataJson: '{}',
			previewHtml: '',
			loading: false,
			error: null,
		}
	},
	methods: {
		t,
		async onPreview() {
			this.loading = true
			this.error = null
			try {
				const data = JSON.parse(this.sampleDataJson)
				const store = useTemplateStore()
				const html = await store.previewTemplate(this.content, data)
				if (html) {
					this.previewHtml = html
				} else {
					this.error = store.error || t('docudesk', 'Preview failed')
				}
			} catch (err) {
				this.error = err.message
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style scoped lang="scss">
.template-preview {
	padding: 16px;
	border-left: 1px solid var(--color-border);

	h3 {
		margin: 0 0 12px;
	}

	&__data {
		display: flex;
		flex-direction: column;
		gap: 8px;
		margin-bottom: 16px;

		label {
			font-weight: bold;
			font-size: 13px;
		}
	}

	&__textarea {
		width: 100%;
		min-height: 100px;
		font-family: monospace;
		font-size: 12px;
		padding: 8px;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius);
	}

	&__output {
		padding: 16px;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius);
		background: white;
		min-height: 200px;
	}

	&__error {
		padding: 12px;
		background: var(--color-error);
		color: white;
		border-radius: var(--border-radius);
		margin-top: 8px;
	}
}
</style>
