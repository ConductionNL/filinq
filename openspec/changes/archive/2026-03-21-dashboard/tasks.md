# Tasks: dashboard

## Task 1: Dashboard View
- [x] Create `DashboardIndex.vue` as default landing page
- [x] Display consent statistics cards (Total, Pending, Approved, Objected)
- [x] Show recent consent activity list (10 most recent)
- [x] Embed Quick Anonymization widget

## Task 2: Nextcloud Dashboard Widgets
- [x] Register `AnonymizationWidget` (IWidget) in Application.php
- [x] Register `FileEntitiesWidget` (IWidget) in Application.php
- [x] Create `dashboard.js` entry point for widget rendering

## Task 3: SPA Navigation
- [x] Create Vue Router with Dashboard, Anonymization, Consent Management routes
- [x] Create `MainMenu.vue` with sidebar navigation

## Task 4: Unit Tests (ADR-009)
- [x] Verify dashboard controller renders template
- [x] Verify widget registration in Application.php

## Task 5: Documentation + Screenshots (ADR-010)
- [x] Take screenshot of dashboard page
- [x] Write feature documentation at `docs/features/dashboard.md`

## Task 6: i18n (ADR-005)
- [x] Add Dutch translations for dashboard UI strings
- [x] Add English translations for dashboard UI strings
