# Consent Management

## Overview

Filinq provides GDPR-compliant publication consent tracking for entities detected in documents. Under the Wet Open Overheid (WOO), affected entities must be notified and given an objection period (minimum 4 weeks) before document publication.

## Features

- **Stats Cards**: At-a-glance view of Total, Pending, Approved, and Objected consent records
- **Consent List**: Browse all consent records with entity text, type, and status badges
- **Consent Detail**: View and update consent status, notification status, and publication decision
- **Automatic Deadline**: Objection deadline calculated from configurable period (default 28 days)

## Screenshot

![Consent Management](/screenshots/consent-management.png)

## API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/consents` | List all consent records |
| POST | `/api/consents` | Create consent record |
| GET | `/api/consents/{id}` | Get specific consent |
| PUT | `/api/consents/{id}` | Update consent status |
| GET | `/api/consents/document/{documentId}` | Get consents for document |

## Status Lifecycle

| Field | Values |
|-------|--------|
| consentStatus | pending, consent_given, no_response, anonymized |
| notificationStatus | pending, sent, delivered, failed, skipped |
| publicationDecision | pending, publish_with_consent, publish_anonymized, reject |
