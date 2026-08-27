const path = require('path')
const fs = require('fs')
const TerserPlugin = require('terser-webpack-plugin')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.stats = {
	colors: true,
	modules: false,
}

// Inline-SVG handling for our custom icon set:
// SVGs in src/assets/icons/ are loaded as raw source strings so DdIcon can
// inject them with v-html and let CSS `currentColor` flow through.
// Remaining PNGs / SVGs elsewhere keep the default asset/inline (data URI)
// behavior from @nextcloud/webpack-vue-config.
const iconsDir = path.resolve(__dirname, 'src/assets/icons')

// Font handling: @nextcloud/webpack-vue-config's default rule matches
// woff2?|ttf|eot with asset/inline, which would base64-bake each ~350 KB
// woff2 (and ~900 KB ttf fallback) straight into the JS bundle on every
// page load. Nextcloud core itself serves fonts as standalone files under
// core/fonts/ referenced via @font-face url(); we mirror that by emitting
// our fonts as asset/resource so the browser fetches and caches them once
// as real files instead of inflating the bundle.
const fontsDir = path.resolve(__dirname, 'src/assets/fonts')

webpackConfig.module.rules = webpackConfig.module.rules.map((rule) => {
	if (rule && rule.type === 'asset/inline') {
		return { ...rule, exclude: [iconsDir, fontsDir] }
	}
	return rule
})
webpackConfig.module.rules.push({
	test: /\.svg$/,
	include: [iconsDir],
	type: 'asset/source',
})
webpackConfig.module.rules.push({
	test: /\.(woff2?|ttf|eot)$/,
	include: [fontsDir],
	type: 'asset/resource',
})

const appId = 'filinq'
webpackConfig.entry = {
	main: {
		import: path.join(__dirname, 'src', 'main.js'),
		filename: appId + '-main.js',
	},
	adminSettings: {
		import: path.join(__dirname, 'src', 'settings.js'),
		filename: appId + '-settings.js',
	},
	dashboard: {
		import: path.join(__dirname, 'src', 'dashboard.js'),
		filename: appId + '-dashboard.js',
	},
}

// Use local source when available (monorepo dev), otherwise fall back to npm package.
// `USE_LOCAL_LIB=false` forces the published package even when a sibling checkout
// is present — without it a local build can never reproduce what CI and production
// build (they have no sibling, so they always resolve the npm dist).
//
// ⚠️ USE_LOCAL_LIB is opt-IN (ADR-090). Building against a developer's working
// checkout is the wrong default for a build that can ship.
//
// The sibling is validated against THIS app's own declared range. The previous
// test was `major < 2`, on the premise that a bad sibling would be 1.x. The
// sibling today is 2.0.5 while this app declares 2.2.0-vue3.16 — both major 2 —
// so the test waved through a version the app never asked for.
//
// The failure that skew produces is not obvious from the version alone. Building
// against the sibling also pulls packages out of the SIBLING's node_modules, and
// a stale vue-demi shim there (its postinstall picks v2/v2.7/v3 and does not
// re-run on `npm install`) yields errors of the form
//   export 'default' (imported as 'Vue') was not found in 'vue'
// — a Vue-2-shaped failure from a library that is itself Vue 3.
//
// Fail CLOSED: if the check cannot run, the sibling is refused. A guard that
// degrades to "allow" is not a guard.
const localLib = path.resolve(__dirname, '../nextcloud-vue/src')
const localLibPkg = path.resolve(__dirname, '../nextcloud-vue/package.json')
let useLocalLib = process.env.USE_LOCAL_LIB === 'true' && fs.existsSync(localLib)
if (useLocalLib) {
	let localVersion = 'unreadable'
	let satisfied = false
	try {
		// eslint-disable-next-line n/no-extraneous-require
		const semver = require('semver')
		const required =
			require('./package.json').dependencies['@conduction/nextcloud-vue']
		localVersion = String(
			JSON.parse(fs.readFileSync(localLibPkg, 'utf8')).version || '',
		)
		satisfied = semver.satisfies(localVersion, required, {
			includePrerelease: true,
		})
	} catch (e) {
		satisfied = false
	}

	if (!satisfied) {
		// eslint-disable-next-line no-console
		console.warn(
			`[filinq] IGNORING sibling @conduction/nextcloud-vue@${localVersion} — `
				+ "it does not satisfy this app's declared range. Building against the npm dist.",
		)
		useLocalLib = false
	}
}

webpackConfig.resolve.alias = {
	...webpackConfig.resolve.alias,
	...(useLocalLib ? { '@conduction/nextcloud-vue': localLib } : {}),
	vue$: path.resolve(__dirname, 'node_modules/vue'),
	pinia$: path.resolve(__dirname, 'node_modules/pinia'),
	// @nextcloud/vue@9, @nextcloud/dialogs@7 and vue-router@5 are ESM-only:
	// their package.json has NO `main` and NO `module`, only an `exports` map.
	// A Vue-2-era alias to the package DIRECTORY bypasses `exports` entirely
	// and then looks for a main/index.js that does not exist, so every import
	// fails with "Can't resolve '@nextcloud/vue'". Alias to the absolute FILE.
	// The exact-match (`$`) form keeps deep imports going through the exports
	// map.
	'@nextcloud/vue$': path.resolve(
		__dirname,
		'node_modules/@nextcloud/vue/dist/index.mjs',
	),
	'@nextcloud/dialogs$': path.resolve(
		__dirname,
		'node_modules/@nextcloud/dialogs/dist/index.mjs',
	),
	// dialogs v7 ships its stylesheet behind the exports map at dist/style.css.
	// Register this BEFORE any bare-package alias: enhanced-resolve takes the
	// first match, and a directory alias would send this to a root style.css
	// that does not exist.
	'@nextcloud/dialogs/style.css$': path.resolve(
		__dirname,
		'node_modules/@nextcloud/dialogs/dist/style.css',
	),
	// @nextcloud/vue@9 hard-depends on vue-router ^5.1.0 while this app is on
	// vue-router 4, so npm installs a SECOND nested copy
	// (node_modules/@nextcloud/vue/node_modules/vue-router). Two router
	// instances mean two different injection keys: NcAppNavigationItem's
	// RouterLink would look up a router this app never provided, and
	// navigation dies with no console error. Force every `vue-router`
	// specifier onto this app's single copy.
	'vue-router$': path.resolve(
		__dirname,
		'node_modules/vue-router/dist/vue-router.mjs',
	),
	// @nextcloud/axios 2.6.0+ ships ESM-only (no dist/index.cjs).
	// Point at the ESM build; webpack 5 handles it natively. Replaces the
	// older alias that targeted dist/index.cjs (no longer present in 2.6+).
	'@nextcloud/axios$': path.resolve(
		__dirname,
		'node_modules/@nextcloud/axios/dist/index.js',
	),
}

// @conduction/nextcloud-vue bundles a FilePicker chunk that imports node's
// `path`. Webpack 5 no longer polyfills node builtins automatically, so
// without this the Vue 3 build fails with "Can't resolve 'path'".
webpackConfig.resolve.fallback = {
	...(webpackConfig.resolve.fallback || {}),
	path: require.resolve('path-browserify'),
}

// Share Vue + @nextcloud/vue + pinia + icons + @conduction/nextcloud-vue
// across the main / settings / dashboard entry-points so each bundle no
// longer inlines its own ~3 MB framework copy. Stable filenames mean each
// entry's `Util::addScript` PHP call can reference the chunk directly
// without a manifest. The shared chunks are loaded once per page and
// cached across navigations between filinq's own pages. As filinq
// grows additional dashboard widgets, every new widget adds only its
// per-widget delta on top of the shared baseline.
webpackConfig.optimization = {
	...(webpackConfig.optimization || {}),
	// Webpack's default TerserPlugin spawns `cpus - 1` worker processes
	// (15 on a 16-core machine), which OOM-kills the build inside the
	// memory-capped WSL VM. Two workers keep peak memory bounded while
	// still parallelizing minification.
	minimizer: [new TerserPlugin({ parallel: 2 })],
	splitChunks: {
		...(webpackConfig.optimization?.splitChunks || {}),
		chunks: 'all',
		cacheGroups: {
			default: false,
			defaultVendors: false,
			ncVue: {
				name: appId + '-shared-nc-vue',
				// Matches both node_modules entries AND the monorepo-dev alias
				// `../nextcloud-vue/src/...` which webpack resolves outside
				// node_modules when @conduction/nextcloud-vue is aliased to it.
				test: /[\\/]node_modules[\\/](@nextcloud[\\/]vue|@conduction[\\/]nextcloud-vue)[\\/]|[\\/]nextcloud-vue[\\/]src[\\/]/,
				priority: 30,
				reuseExistingChunk: true,
				enforce: true,
				filename: appId + '-shared-nc-vue.js',
			},
			vendor: {
				name: appId + '-shared-vendor',
				test: /[\\/]node_modules[\\/](vue|pinia|vue-material-design-icons|@vueuse|core-js)[\\/]/,
				priority: 20,
				reuseExistingChunk: true,
				enforce: true,
				filename: appId + '-shared-vendor.js',
			},
		},
	},
}

module.exports = webpackConfig
