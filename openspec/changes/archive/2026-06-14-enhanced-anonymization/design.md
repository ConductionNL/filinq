---
status: pr-created
pr: Codeberg PR docudesk#68 (pre-migration, not migrated to GitHub)
---

## Context
Current pipeline processes one file at a time. Batch processing needed for WOO compliance.

## Goals / Non-Goals
**Goals:** Multi-file batch processing, entity review, WOO profiles, audit reports
**Non-Goals:** Real-time collaboration, persistent batch history, custom NER models

## Decisions
1. Batch state in ICache (2h TTL)
2. Sequential extraction, batch anonymization
3. Entity review as intermediate API step
4. WOO profiles in IAppConfig
5. CSV reports via fputcsv()
