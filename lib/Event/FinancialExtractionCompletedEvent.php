<?php

/**
 * DocuDesk FinancialExtractionCompletedEvent
 *
 * Public cross-app event DocuDesk dispatches when a financial-document field
 * extraction (scan-en-herken) completes and the requester asked for a
 * callback event. This is the canonical home of the
 * `nl.conduction.docudesk.extraction.completed` wire contract. Consumer
 * fleet apps (e.g. shillinq's receipt-extraction-consume) subscribe to it to
 * turn the extracted fields into a purchase invoice — DocuDesk owns the
 * extraction only, never the consumer's downstream side effect (ADR-022).
 *
 * @category  Event
 * @package   OCA\DocuDesk\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCP\EventDispatcher\Event;

/**
 * Cross-app completion event: DocuDesk reports a completed financial
 * extraction. Fully immutable — DocuDesk constructs it from the persisted
 * extraction result; consumers only read.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
class FinancialExtractionCompletedEvent extends Event
{
    /**
     * Construct the completion event.
     *
     * @param string               $documentUri       URI of the source document/file.
     * @param string               $requestedBy       Nextcloud user id that initiated the extraction.
     * @param string               $sourceApp         App id of the requester (e.g. 'shillinq').
     * @param string               $docType           `receipt` or `supplier-invoice`.
     * @param array<string, mixed> $fields            The REQ-FIN-03 field set.
     * @param array<string, float> $fieldConfidence   Per-field confidence (0..1).
     * @param float                $overallConfidence Aggregate confidence (0..1).
     *
     * @return void
     */
    public function __construct(
        private readonly string $documentUri,
        private readonly string $requestedBy,
        private readonly string $sourceApp,
        private readonly string $docType,
        private readonly array $fields,
        private readonly array $fieldConfidence,
        private readonly float $overallConfidence,
    ) {
        parent::__construct();

    }//end __construct()

    /**
     * Get the URI of the source document/file.
     *
     * @return string The document URI.
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getDocumentUri(): string
    {
        return $this->documentUri;

    }//end getDocumentUri()

    /**
     * Get the Nextcloud user id that initiated the extraction.
     *
     * @return string The requesting user id.
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getRequestedBy(): string
    {
        return $this->requestedBy;

    }//end getRequestedBy()

    /**
     * Get the app id of the requester.
     *
     * @return string The source app id.
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getSourceApp(): string
    {
        return $this->sourceApp;

    }//end getSourceApp()

    /**
     * Get the document type (`receipt` or `supplier-invoice`).
     *
     * @return string The document type.
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getDocType(): string
    {
        return $this->docType;

    }//end getDocType()

    /**
     * Get the extracted field set (REQ-FIN-03 shape).
     *
     * @return array<string, mixed> The extracted fields.
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getFields(): array
    {
        return $this->fields;

    }//end getFields()

    /**
     * Get the per-field confidence map.
     *
     * @return array<string, float> Confidence (0..1) keyed by field name.
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getFieldConfidence(): array
    {
        return $this->fieldConfidence;

    }//end getFieldConfidence()

    /**
     * Get the aggregate confidence.
     *
     * @return float The overall confidence (0..1).
     *
     * @spec openspec/changes/financial-document-field-extraction/tasks.md#4-1
     */
    public function getOverallConfidence(): float
    {
        return $this->overallConfidence;

    }//end getOverallConfidence()

    /**
     * Build the canonical wire payload for
     * `nl.conduction.docudesk.extraction.completed`.
     *
     * @return array<string, mixed> The canonical payload shape.
     *
     * @spec openspec/specs/financial-document-field-extraction/spec.md
     */
    public function toPayload(): array
    {
        return [
            'documentUri'       => $this->documentUri,
            'requestedBy'       => $this->requestedBy,
            'sourceApp'         => $this->sourceApp,
            'docType'           => $this->docType,
            'fields'            => $this->fields,
            'fieldConfidence'   => $this->fieldConfidence,
            'overallConfidence' => $this->overallConfidence,
        ];

    }//end toPayload()
}//end class
