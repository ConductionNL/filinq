## 1. One widget

- [ ] 1.1 `AnonymizationWidget`: route inside the app as today; on the Nextcloud dashboard (no router) send the browser to `/apps/filinq/my-documents` after a confirmed upload.
- [ ] 1.2 `AnonymizationWidget`: show the "Open Filinq" footer link when it renders outside the app.
- [ ] 1.3 `DashboardIndex` and `src/dashboard.js` render `AnonymizationWidget`; remove `AnonymizationDashboardWidget.vue`, its entries in the storage path golden, and `DossierNameDialog.vue`, which only it used.

## 2. Tests

- [ ] 2.1 Vitest `tests/vitest/anonymizationWidgetMerged.spec.js`: accepted and skipped file types, the dialog for one and for several files, routing inside the app and on the Nextcloud dashboard, the footer link.
- [ ] 2.2 Update the e2e page-components check of the dashboard widget, and the storage path golden, for the removed component.
