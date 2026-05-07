/**
 * DocuDesk landing page.
 *
 * Composes the brand <DetailHero> + <WidgetShelf> from
 * @conduction/docusaurus-preset/components, mirroring the connext page
 * at sites/www/src/pages/apps/docudesk.mdx.
 *
 * Written as .js (not .mdx) because the docs site has the docs plugin
 * pointed at `path: './'`, and an MDX file in src/pages/ trips the
 * MDX-ESM parser even with the docs plugin's `src/**` exclude.
 * Authoring the page in JSX keeps the same component composition.
 */

import React from 'react';
import Layout from '@theme/Layout';
import {
  DetailHero,
  WidgetShelf,
  AppMock,
} from '@conduction/docusaurus-preset/components';

const DOCUDESK_ICON = (
  <svg viewBox="0 0 24 24">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
  </svg>
);

const TAGLINE = (
  <>
    Document creation, anonymisation, signing, and templating on{' '}
    <span className="next-blue">Nextcloud</span>. Templates ship for the
    most-used Dutch government documents. Pulls fields straight from your
    registers.
  </>
);

function AnonymisePanel() {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 8, alignItems: 'stretch' }}>
      <div
        style={{
          height: 90,
          border: '2px dashed var(--c-cobalt-300)',
          borderRadius: 'var(--radius-md)',
          background: 'var(--c-cobalt-50)',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 8,
        }}
      >
        <span
          style={{
            width: 28,
            height: 32,
            clipPath: 'var(--hex-pointy-top)',
            background: 'var(--c-terracotta-500)',
          }}
        />
        <div style={{ height: 4, width: 80, background: 'var(--c-cobalt-400)', borderRadius: 1 }} />
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
        <span
          style={{
            width: 8,
            height: 9,
            clipPath: 'var(--hex-pointy-top)',
            background: 'var(--c-mint-500)',
          }}
        />
        <div style={{ flex: 1, height: 3, background: 'var(--c-cobalt-200)', borderRadius: 1 }} />
        <div style={{ height: 3, width: 26, background: 'var(--c-cobalt-100)', borderRadius: 1 }} />
      </div>
    </div>
  );
}

function TemplatesPanel() {
  const rows = [
    { pill: 'PDF', pillBg: 'var(--c-terracotta-100)', pillFg: 'var(--c-terracotta-700)' },
    { pill: 'DOCX', pillBg: 'var(--c-cobalt-100)', pillFg: 'var(--c-cobalt-700)' },
    { pill: 'PDF', pillBg: 'var(--c-terracotta-100)', pillFg: 'var(--c-terracotta-700)' },
    { pill: 'DOCX', pillBg: 'var(--c-cobalt-100)', pillFg: 'var(--c-cobalt-700)' },
  ];
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
      {rows.map((row, i) => (
        <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <span
            style={{
              display: 'inline-block',
              padding: '2px 6px',
              background: row.pillBg,
              color: row.pillFg,
              borderRadius: 2,
              fontFamily: 'var(--conduction-typography-font-family-code)',
              fontSize: 9,
              fontWeight: 700,
              letterSpacing: '0.04em',
            }}
          >
            {row.pill}
          </span>
          <div style={{ flex: 1, height: 4, background: 'var(--c-cobalt-200)', borderRadius: 1 }} />
          <div style={{ height: 3, width: 22, background: 'var(--c-cobalt-100)', borderRadius: 1 }} />
        </div>
      ))}
    </div>
  );
}

function AuditPanel() {
  const rows = [
    { accent: true, l1: '70%' },
    { accent: false, l1: '60%' },
    { accent: false, l1: '55%' },
    { accent: false, l1: '65%' },
  ];
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
      {rows.map((row, i) => (
        <div
          key={i}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 8,
            padding: '4px 0',
            borderBottom: i < 3 ? '1px solid var(--c-cobalt-50)' : 'none',
          }}
        >
          <span
            style={{
              width: 12,
              height: 14,
              clipPath: 'var(--hex-pointy-top)',
              background: row.accent ? 'var(--c-orange-knvb)' : 'var(--c-cobalt-300)',
            }}
          />
          <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 3 }}>
            <div style={{ height: 4, width: row.l1, background: 'var(--c-cobalt-700)', borderRadius: 1 }} />
            <div style={{ height: 3, width: '40%', background: 'var(--c-cobalt-200)', borderRadius: 1 }} />
          </div>
          <div
            style={{
              height: 18,
              width: 18,
              borderRadius: 3,
              background: row.accent ? 'var(--c-orange-knvb)' : 'var(--c-cobalt-50)',
              border: row.accent ? 'none' : '1px solid var(--c-cobalt-200)',
            }}
          />
        </div>
      ))}
    </div>
  );
}

const WIDGETS = [
  {
    title: 'Anonymise dropzone',
    desc: 'Drop an inbound email or file. DocuDesk strips PII before the document touches the register.',
    panel: <AnonymisePanel />,
  },
  {
    title: 'Document templates',
    desc: 'Word and PDF templates that bind directly to register schema fields. Edit in Office, regenerate any time.',
    panel: <TemplatesPanel />,
  },
  {
    title: 'Audit log',
    desc: 'Every generation, edit, signing, and archival action leaves a tamper-evident trail. Compliance evidence ships with the install.',
    panel: <AuditPanel />,
  },
];

export default function Home() {
  return (
    <Layout
      title="DocuDesk"
      description="Generate, anonymise, sign, and template documents on Nextcloud. Templates ship for the most-used Dutch government documents."
    >
      <main className="marketing-page">
        <DetailHero
          appId="docudesk"
          status={{ label: 'Stable', color: 'var(--c-mint-500)' }}
          version="v1.8"
          locales="NL · EN"
          title="DocuDesk"
          tagline={TAGLINE}
          primaryCta={{
            label: 'Install from app store',
            href: 'https://apps.nextcloud.com/apps/docudesk',
            tone: 'orange',
          }}
          secondaryCta={{ label: 'Read the docs', href: '/docs/intro' }}
          tertiaryCta={{
            label: 'View on GitHub',
            href: 'https://github.com/ConductionNL/docudesk',
          }}
          iconColor="var(--c-orange-knvb)"
          icon={DOCUDESK_ICON}
          illustration={<AppMock app="docudesk" />}
        />

        <WidgetShelf
          eyebrow="Widgets we ship"
          title="On every Nextcloud dashboard."
          lede="Install DocuDesk and these widgets show up on the home screen of every team member who handles documents."
          widgets={WIDGETS}
        />
      </main>
    </Layout>
  );
}
