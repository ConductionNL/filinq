# Admin Settings

## Overview

DocuDesk provides a dedicated administration settings section in the Nextcloud admin panel, accessible only to administrators. The settings page allows configuration of GDPR consent tracking, metadata enrichment toggles, and OpenRegister data storage integration.

## Features

- **Version Information**: Displays current DocuDesk version and support contact details
- **Consent Settings**: Configure the objection period (days) for WOO publication consent (default: 28 days)
- **Metadata Enrichment**: Toggle automatic language detection, keyword extraction, and topic classification
- **Data Storage**: Configure OpenRegister register and schema for consent records and templates

## Access

Navigate to **Admin Settings > DocuDesk** in the Nextcloud admin panel.

## Screenshot

![Admin Settings](/screenshots/admin-settings.png)

## API

- `GET /apps/docudesk/api/settings` - Retrieve current settings
- `POST /apps/docudesk/api/settings` - Update settings

## Configuration Keys

| Key | Default | Description |
|-----|---------|-------------|
| `publication_objection_period_days` | 28 | Minimum objection period in days |
| `enable_language_detection` | 1 | Enable automatic language detection |
| `enable_keyword_extraction` | 1 | Enable automatic keyword extraction |
| `enable_topic_classification` | 1 | Enable automatic topic classification |
