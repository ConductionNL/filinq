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

const appId = 'docudesk'
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
	verify: {
		// Public, account-free verification portal (signature-verification-portal).
		// Deliberately its OWN entry point — NOT the `main` bundle — so the
		// guest-layout page (templates/verify.php, served via
		// PublicVerificationController, no NC session) never pulls in the
		// authenticated manifest shell (CnPageRenderer/VueRouter/the full
		// menu-layout/registry). Still shares the -shared-vendor / -shared-nc-vue
		// chunks so an anonymous visitor's browser reuses the same cached Vue /
		// @conduction/nextcloud-vue baseline as an authenticated docudesk tab.
		import: path.join(__dirname, 'src', 'verify.js'),
		filename: appId + '-verify.js',
	},
}

// Use local source when available (monorepo dev), otherwise fall back to npm package.
// `USE_LOCAL_LIB=false` forces the published package even when a sibling checkout
// is present — without it a local build can never reproduce what CI and production
// build (they have no sibling, so they always resolve the npm dist).
const localLib = path.resolve(__dirname, '../nextcloud-vue/src')
const useLocalLib = process.env.USE_LOCAL_LIB !== 'false' && fs.existsSync(localLib)

webpackConfig.resolve.alias = {
	...webpackConfig.resolve.alias,
	...(useLocalLib ? { '@conduction/nextcloud-vue': localLib } : {}),
	'vue$': path.resolve(__dirname, 'node_modules/vue'),
	'pinia$': path.resolve(__dirname, 'node_modules/pinia'),
	'@nextcloud/vue$': path.resolve(__dirname, 'node_modules/@nextcloud/vue'),
	'@nextcloud/dialogs': path.resolve(__dirname, 'node_modules/@nextcloud/dialogs'),
	// @nextcloud/axios 2.6.0+ ships ESM-only (no dist/index.cjs).
	// Point at the ESM build; webpack 5 handles it natively. Replaces the
	// older alias that targeted dist/index.cjs (no longer present in 2.6+).
	'@nextcloud/axios$': path.resolve(__dirname, 'node_modules/@nextcloud/axios/dist/index.js'),
}

// Share Vue + @nextcloud/vue + pinia + icons + @conduction/nextcloud-vue
// across the main / settings / dashboard entry-points so each bundle no
// longer inlines its own ~3 MB framework copy. Stable filenames mean each
// entry's `Util::addScript` PHP call can reference the chunk directly
// without a manifest. The shared chunks are loaded once per page and
// cached across navigations between docudesk's own pages. As docudesk
// grows additional dashboard widgets, every new widget adds only its
// per-widget delta on top of the shared baseline.
webpackConfig.optimization = {
	...(webpackConfig.optimization || {}),
	// Webpack's default TerserPlugin spawns `cpus - 1` worker processes
	// (15 on a 16-core machine), which OOM-kills the build inside the
	// memory-capped WSL VM. Two workers keep peak memory bounded while
	// still parallelizing minification.
	minimizer: [
		new TerserPlugin({ parallel: 2 }),
	],
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
