# Tasks: consent-management

## Task 1: Consent CRUD API
- [x] Implement `GET /api/consents` for listing
- [x] Implement `POST /api/consents` for creation
- [x] Implement `GET /api/consents/{id}` for detail
- [x] Implement `PUT /api/consents/{id}` for update
- [x] Implement `GET /api/consents/document/{documentId}` for document lookup

## Task 2: Consent Service
- [x] Implement `ConsentService::createConsentRequest()` with deadline calculation
- [x] Implement `ConsentService::updateConsentStatus()` with status validation
- [x] Implement `ConsentService::getConsentsByDocument()`

## Task 3: Consent CRUD Service
- [x] Extract controller logic to `ConsentCrudService`
- [x] Implement config resolution from settings
- [x] Handle register/schema not configured errors

## Task 4: Frontend
- [x] Create `ConsentIndex.vue` with stats cards and list
- [x] Create `ConsentDetail.vue` with editable fields
- [x] Create Pinia store for consent state management

## Task 5: Unit Tests (ADR-009)
- [x] Write `ConsentCrudServiceTest` with config, create, list, document lookup tests
- [x] Test null config handling

## Task 6: Documentation + Screenshots (ADR-010)
- [x] Take screenshot of consent management page
- [x] Write feature documentation at `docs/features/consent-management.md`

## Task 7: i18n (ADR-005)
- [x] Add Dutch translations for consent UI strings
- [x] Add English translations for consent UI strings
