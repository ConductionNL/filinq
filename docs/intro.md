---
title: Filinq Documentation
description: Get started with Filinq, document generation, anonymisation, and signing on Nextcloud. Render PDF, ODF and HTML from templates with OCR and anonymisation.
---

# Filinq Documentation

Filinq generates, anonymizes, and signs PDF, ODF (.odt), or HTML documents in a GDPR-compliant manner, all while keeping your data secure within your local Nextcloud instance.

## The Power of Local Processing

Imagine a world where your sensitive documents never have to leave your premises, yet you still have all the power of modern cloud collaboration. That's Filinq. Running on your local Nextcloud instance, it's like having a secure document fortress with a sophisticated diplomatic corps.

When your organization needs to process sensitive documents - whether it's generating decisions, anonymizing personal data, or signing outbound correspondence - everything happens within your walls. Your data stays your data.

## Key Features

- 📄 Document generation from Twig/HTML templates, output as PDF/ODF/HTML, with Open Register field binding
- 🔒 GDPR-compliant document anonymization, with entity detection routed through a configurable backend (regex, OpenAnonymiser, Presidio, or an LLM) via Open Register
- ✍️ Digital signing with eIDAS signature levels (SES, AdES, QES) and multi-signer workflows
- 📊 Document metadata extraction and enhancement (language detection, keyword extraction, topic classification)
- 📝 Publication consent management (GDPR & Wet Open Overheid compliant)
- 🔍 OCR text extraction for scanned and image-based documents
- 📋 Complete audit trail for compliance
- ⚡ High performance local operations
- 🌐 Multi-language support
- ✅ Document validation & quality control

## Why Nextcloud?

We chose Nextcloud as our platform for several compelling reasons:

### Enterprise-Grade Security
By leveraging Nextcloud's secure infrastructure, Filinq ensures all document processing happens within your controlled environment. This means sensitive data never leaves your premises while still enabling collaborative features and integrations with external systems.

### Easy Installation and Updates
Filinq is available directly through the Nextcloud App Store, making installation as simple as a few clicks:

1. Log in to your Nextcloud instance as an administrator
2. Navigate to the Apps section
3. Find Filinq in the Organization category
4. Click "Install"

Note: Filinq requires [OpenRegister](https://apps.nextcloud.com/apps/openregister) to be installed. Anonymization runs through a configurable entity-detection backend — the regex backend works out of the box, while OpenAnonymiser, Microsoft Presidio, or an LLM require the corresponding companion service to be deployed and configured in Open Register.

The app will automatically stay up-to-date with your Nextcloud instance, ensuring you always have the latest features and security updates.

### Scalability and Performance
Nextcloud's architecture allows Filinq to handle everything from individual document processing to large-scale batch operations, all while maintaining optimal performance within your local environment.

## Contributing

We welcome contributions to improve the documentation! If you'd like to contribute, please visit our [GitHub repository](https://github.com/ConductionNL/filinq). There you can:

- Report issues or suggest improvements by opening an issue
- Submit pull requests with documentation changes
- Engage with the community in discussions

For detailed contribution guidelines, please refer to the CONTRIBUTING.md file in our repository.
