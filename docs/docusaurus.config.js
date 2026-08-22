// @ts-check

/**
 * Filinq documentation site.
 *
 * Built on @conduction/docusaurus-preset for brand defaults (tokens,
 * theme swizzles for Navbar / Footer, four-locale i18n scaffolding,
 * KvK / BTW copyright). Site-specific overrides — locales, sidebar
 * path, mermaid theme, redocusaurus preset for the OpenAPI spec, and
 * filinq-only navbar items — are passed through createConfig() opts.
 */

const { createConfig, baseFooterLinks } = require('@conduction/docusaurus-preset');

/* createConfig replaces themes wholesale when `themes:` is passed, so
   we re-include the brand theme plugin alongside @docusaurus/theme-mermaid.
   Without the brand theme entry the Navbar/Footer swizzles and
   brand.css auto-load would silently drop. */
const BRAND_THEME = require.resolve('@conduction/docusaurus-preset/theme');

const config = createConfig({
  title: 'Filinq',
  tagline: 'Flexible document management for your organization',
  /* Still the OLD host on purpose: the docs subdomain moves with DNS on its
     own schedule, and docs/static/CNAME must keep matching this value. */
  url: 'https://docudesk.conduction.nl',
  baseUrl: '/',

  organizationName: 'ConductionNL',
  projectName: 'filinq',

  /* Filinq ships en + nl markdown; the brand preset's default i18n
     block (nl/en/de/fr) is replaced wholesale here so we don't pull
     in stub locales the docs don't translate yet. */
  i18n: {
    defaultLocale: 'en',
    locales: ['en', 'nl'],
    localeConfigs: {
      en: { label: 'English' },
      nl: { label: 'Nederlands' },
    },
  },

  /* The filinq docs source lives at the repo root of `docs/` rather
     than under a `docs/` subfolder, so we override the preset's default
     `presets:` block to point `docs.path` at './' and disable the blog
     plugin. customCss carries filinq-specific CSS only — brand tokens
     and the theme swizzles are auto-loaded by the brand theme entry in
     `themes:` below. The redocusaurus preset is appended for the
     OpenAPI spec rendered at /api. */
  presets: [
    [
      'classic',
      {
        docs: {
          path: './',
          /* docs.path: './' makes plugin-content-docs scan every file
             in docs/, which collides with plugin-content-pages's own
             scan of docs/src/pages/. Exclude src/ (pages live there)
             plus the standard node_modules bucket. The
             features/pdf-generation page contains a markdown table
             with `{ top, right, bottom, left }` that the MDX parser
             reads as an MDX expression and trips on at SSR time;
             excluded until the page is rewritten with escaped braces
             (same pattern as openregister PR #1444). */
          exclude: [
            '**/node_modules/**',
            'src/**',
            'features/pdf-generation.md',
          ],
          sidebarPath: require.resolve('./sidebars.js'),
          editUrl: 'https://github.com/ConductionNL/filinq/edit/main/docs/',
        },
        blog: false,
        theme: {
          customCss: require.resolve('./src/css/custom.css'),
        },
      },
    ],
    [
      'redocusaurus',
      {
        specs: [
          {
            spec: 'static/oas/filinq-api.yaml',
            route: '/api',
          },
        ],
        theme: {
          primaryColor: '#1890ff',
        },
      },
    ],
  ],

  themes: [BRAND_THEME, '@docusaurus/theme-mermaid'],

  /* Cross-domain redirects to reclaim SEO equity from URLs Google has
     in its index from when each app shipped its own legal pages.
     Those pages were centralised on www.conduction.nl/{iso,privacy,
     terms} as part of the SEO baseline; the per-app legal slugs now
     404 in production. The plugin emits a static HTML page per `from`
     with a meta-refresh + <link rel="canonical"> to the new target,
     which Google treats as a 301 signal. */
  plugins: [
    [
      '@docusaurus/plugin-client-redirects',
      {
        redirects: [
          {from: '/iso/', to: 'https://www.conduction.nl/iso/'},
          {from: '/privacy/', to: 'https://www.conduction.nl/privacy/'},
          {from: '/terms/', to: 'https://www.conduction.nl/terms/'},
          {from: '/nl/terms/', to: 'https://www.conduction.nl/terms/'},
        ],
      },
    ],
  ],

  /* Brand navbar provides locale dropdown + GitHub by default; we
     replace items[] with filinq's own (Documentation sidebar link,
     API link, filinq GitHub, locale dropdown). Object.assign in
     createConfig is shallow, so items: replaces wholesale. */
  navbar: {
    items: [
      {
        type: 'docSidebar',
        sidebarId: 'tutorialSidebar',
        position: 'left',
        label: 'Documentation',
      },
      {
        href: '/api',
        label: 'API Documentation',
        position: 'right',
      },
      {
        href: 'https://github.com/ConductionNL/filinq',
        label: 'GitHub',
        position: 'right',
      },
      { type: 'localeDropdown', position: 'right' },
    ],
  },

  /* Per-property footer override (preset 1.2.0+): we pass `links` only,
     so the brand `style: 'dark'` and the brand KvK/BTW/IBAN/address
     copyright string both inherit unchanged. Single column: the brand
     "Conduction" anchor. Site-specific Product / Support columns may
     be added later. */
  footer: {
    links: [
      ...baseFooterLinks().filter((column) => column.title === 'Conduction'),
    ],
  },

  /* Drop the canal-footer's boat-sinking + kade-cyclist mini-games
     on this product-page footer (preset 1.3.0+). The static skyline +
     canal decoration are kept; the interactive layer goes away. */
  minigames: false,

  /* themeConfig is shallow-merged into the preset's defaults
     (colorMode + navbar + footer). prism + mermaid land alongside. */
  themeConfig: {
    image: 'img/og-filinq.png',
    prism: {
      theme: require('prism-react-renderer/themes/github'),
      darkTheme: require('prism-react-renderer/themes/dracula'),
    },
    mermaid: {
      theme: { light: 'default', dark: 'dark' },
    },
  },
});

/* createConfig doesn't pass-through arbitrary top-level fields; assign
   markdown directly so it makes it into the final Docusaurus config. */
config.markdown = {
  mermaid: true,
};

module.exports = config;
