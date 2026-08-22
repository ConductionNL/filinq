# Tasks — beta-surface-alignment

- [x] 1. `appinfo/info.xml`: add `lang="en"`/`lang="nl"` summaries with real
      Dutch translation.
- [x] 2. `appinfo/info.xml`: rewrite `<description>` to name the full shipped
      feature set; fix "Word/Excel" template-format claim to PDF/ODF/HTML.
- [x] 3. `appinfo/info.xml`: fix `<documentation>` URLs from dead gitbook.io to
      live docudesk.conduction.nl.
- [x] 4. `appinfo/info.xml`: fix `<licence>` from `agpl` to `EUPL-1.2` to match
      actual SPDX headers and product-page claim.
- [x] 5. `conduction-website/src/pages/apps/docudesk.mdx`: fix status
      (Stable→Beta), version (v1.8→v0.0.34), remove Presidio-exclusivity,
      Word/Office claims, "Twelve templates," "per-instance certificate,"
      "TMLO archiving," SharePoint/Office365 claim, fabricated MCP
      two-pass-redaction Showcase card, fabricated Mail/Files-sidebar
      Showcase card, and three dead CTA links.
- [x] 6. `conduction-website/i18n/nl/.../docudesk.mdx`: mirror the same
      vocabulary/claim fixes in Dutch.
- [x] 7. `filinq/docs/intro.md`: remove SharePoint/Office365/WCAG claims,
      fix Word/Excel→PDF/ODF/HTML, fix install category, fix Presidio framing.
- [x] 8. `filinq/docs/features/external-integration.md`: replace fabricated
      `$integrationService` SharePoint/Office365 page with the real
      office-app-conversion + API-workflow-automation integration story.
- [x] 9. `filinq/docs/features/workflow-automation.md`: replace fabricated
      `$workflowService` visual-designer/FTP-SharePoint-monitoring page with
      the real event-listener + external-API automation story.
- [x] 10. `filinq/docs/features/wcag-compliance.md`: replace fabricated
      `$wcagService` WCAG-AAA/PDF-UA auto-fix page with an honest
      "UI-level WCAG AA via NC components; no document-content checker"
      page.
- [x] 11. `filinq/docs/GOVERNMENT-FEATURES.md`: fix licence line
      (AGPL→EUPL-1.2) and correct A-01/A-02/A-06 rows from false
      "Beschikbaar" WCAG-document-checking claims to honest
      "Via platform"/"N.v.t." status.
- [x] 12. Verify `img/app.svg` matches brand convention (24×24, `#fff` fill) —
      confirmed, no change needed.
- [x] 13. Write this openspec change (proposal.md, tasks.md, spec delta).
