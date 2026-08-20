module.exports = {
	extends: '@nextcloud/stylelint-config',
	rules: {
		'selector-pseudo-element-no-unknown': [
			true,
			{
				ignorePseudoElements: ['v-deep'],
			},
		],
		// Indentation is prettier's now — the same handover eslint-config-prettier
		// performs for eslint, and for the same reason: two formatters with
		// overlapping jurisdiction make a repo unfixable. They genuinely disagree
		// on continuation lines — a wrapped multi-line selector
		// (PdfViewer.vue:376) and a long wrapped `cursor:` value
		// (src/assets/app.css:185) are the two cases here.
		//
		// The handover LOSES nothing and GAINS coverage: this rule only ever saw
		// what the `stylelint` script's `src/**` glob passes it, so it reported
		// clean while `css/main.css` — outside that glob — sat at 78 space-indented
		// lines. Prettier's glob is `**/*.{js,ts,vue,css,scss}` and covers both.
		// `indentation` is also deprecated in stylelint 15 and removed in 16.
		indentation: null,
	},
}
