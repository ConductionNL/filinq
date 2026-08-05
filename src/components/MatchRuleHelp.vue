<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'

/**
 * Explains the exact / not-exact match switch.
 *
 * Self-contained: the button toggles its own panel, so it can be dropped next to
 * any switch without the parent tracking state.
 *
 * The panel exists because "not exact" invites the assumption that matching
 * becomes fuzzy or partial, and it does not — `PolicyMatchService::ruleMatches`
 * compares the two NORMALISED forms for full equality:
 *
 *     $entityTextNormalised === $this->normalise($value)
 *
 * So casing and accents stop mattering, but nothing else does: a longer name
 * still will not match, and a typo still will not match. Saying that plainly is
 * the whole point of the panel.
 */
export default {
	name: 'MatchRuleHelp',
	components: {
		NcButton,
		NcNoteCard,
		InformationOutline,
	},
	data() {
		return {
			open: false,
		}
	},
	computed: {
		/**
		 * Worked examples for a rule whose value is `Jansen`.
		 *
		 * Kept as data rather than markup so each row is one translatable
		 * sentence, and so the matches/does-not-match split stays obvious.
		 *
		 * @return {Array<{text: string, matches: boolean, why: string}>} Examples.
		 */
		examples() {
			return [
				{
					text: 'JANSEN',
					matches: true,
					why: t('docudesk', 'only the casing differs'),
				},
				{
					text: 'Jansén',
					matches: true,
					why: t('docudesk', 'the accent is removed during normalisation'),
				},
				{
					text: ' jansen ',
					matches: true,
					why: t('docudesk', 'surrounding spaces are trimmed'),
				},
				{
					text: 'Jan Jansen',
					matches: false,
					why: t('docudesk', 'the whole value must be equal, not just contain it'),
				},
				{
					text: 'Janssen',
					matches: false,
					why: t('docudesk', 'different letters — normalisation does not correct typos'),
				},
			]
		},
	},
	methods: {
		t,
		toggle() {
			this.open = !this.open
		},
	},
}
</script>

<template>
	<span class="match-rule-help">
		<NcButton
			type="tertiary-no-background"
			:aria-label="t('docudesk', 'How does matching work?')"
			:pressed="open"
			@click="toggle">
			<template #icon>
				<InformationOutline :size="20" />
			</template>
		</NcButton>

		<NcNoteCard v-if="open" type="info" class="match-rule-help__panel">
			<h5>{{ t('docudesk', 'Exact match on') }}</h5>
			<p>
				{{ t('docudesk', 'The value must be identical, character for character — including capitals and accents.') }}
			</p>

			<h5>{{ t('docudesk', 'Exact match off (normalised)') }}</h5>
			<p>
				{{ t('docudesk', 'Both your value and the text found in the document are simplified in the same way, and the results are then compared. Simplifying means:') }}
			</p>
			<ol>
				<li>{{ t('docudesk', 'spaces at the start and end are removed;') }}</li>
				<li>{{ t('docudesk', 'accented and non-Latin letters are converted to plain letters (é becomes e, ö becomes o);') }}</li>
				<li>{{ t('docudesk', 'everything is converted to lower case.') }}</li>
			</ol>
			<p>
				{{ t('docudesk', 'The simplified value is what gets saved, so the rule shows exactly what it matches on.') }}
			</p>

			<h5>{{ t('docudesk', 'Examples for a rule with the value “Jansen”') }}</h5>
			<ul class="match-rule-help__examples">
				<li v-for="example in examples" :key="example.text">
					<span class="match-rule-help__verdict" :class="{ 'match-rule-help__verdict--no': !example.matches }">
						{{ example.matches ? t('docudesk', 'matches') : t('docudesk', 'no match') }}
					</span>
					<code>{{ example.text }}</code>
					<span class="match-rule-help__why">— {{ example.why }}</span>
				</li>
			</ul>

			<p class="match-rule-help__warning">
				{{ t('docudesk', 'Note: this is not a fuzzy search. After simplifying, the two values must still be exactly equal — a rule does not match a longer name that merely contains it, nor a misspelling.') }}
			</p>
		</NcNoteCard>
	</span>
</template>

<style scoped>
.match-rule-help {
	display: contents;
}

.match-rule-help__panel {
	/* Span the whole rule row rather than squeezing into one grid/flex cell. */
	flex-basis: 100%;
	grid-column: 1 / -1;
	margin-block: 4px;
}

.match-rule-help__panel h5 {
	margin: 8px 0 2px;
	font-weight: bold;
}

.match-rule-help__panel h5:first-child {
	margin-top: 0;
}

.match-rule-help__panel p,
.match-rule-help__panel ol,
.match-rule-help__panel ul {
	margin: 2px 0;
}

.match-rule-help__panel ol {
	padding-inline-start: 1.4em;
	list-style: decimal;
}

.match-rule-help__examples {
	list-style: none;
	padding-inline-start: 0;
}

.match-rule-help__examples li {
	display: flex;
	gap: 6px;
	align-items: baseline;
	flex-wrap: wrap;
}

.match-rule-help__verdict {
	min-width: 5.5em;
	font-weight: bold;
	color: var(--color-success-text, var(--color-success));
}

.match-rule-help__verdict--no {
	color: var(--color-error-text, var(--color-error));
}

.match-rule-help__why {
	color: var(--color-text-maxcontrast);
}

.match-rule-help__warning {
	margin-top: 8px;
}
</style>
