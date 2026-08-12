<?php

/**
 * DocuDesk DocumentSigningRequestedEvent
 *
 * Public cross-app event a consumer fleet app dispatches to ask DocuDesk to
 * raise a document signing request for one of its objects. The in-process,
 * autoloaded replacement for the broken
 * `$registry->call('docudesk','createSigningRequest',…)` delegation path:
 * dispatched via Nextcloud's IEventDispatcher and handled synchronously by
 * DocumentSigningRequestedListener, so the dispatching producer can read the
 * resolved signingRequestId back off the same instance.
 *
 * @category  Event
 * @package   OCA\DocuDesk\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Event;

use OCP\EventDispatcher\Event;

/**
 * Cross-app request event: a consumer app asks DocuDesk to raise a signing request.
 *
 * All request fields are immutable (constructor-injected getters). Nextcloud
 * typed dispatch is synchronous, so the single result slot (signingRequestId +
 * handled) is written by DocuDesk's listener and read by the producer right
 * after dispatch — the standard NC request/response-over-the-bus pattern.
 *
 * @category Event
 * @package  OCA\DocuDesk\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/docudesk-signing-events/specs/docudesk-signing-events/spec.md
 */
class DocumentSigningRequestedEvent extends Event {

	/**
	 * The id of the signing request DocuDesk created (result slot).
	 *
	 * @var string|null
	 */
	private ?string $signingRequestId = null;

	/**
	 * Whether DocuDesk's listener handled this request (result slot).
	 *
	 * @var boolean
	 */
	private bool $handled = false;

	/**
	 * Construct the request event.
	 *
	 * The six provenance fields (sourceApp, subjectRegister, subjectSchema,
	 * subjectId, externalReference, correlationId) are grouped into
	 * {@see SigningProvenance} — the same value object its sibling
	 * {@see SigningConcludedEvent} already takes. The two events describe the
	 * two ends of one exchange, so they now carry provenance the same way.
	 *
	 * The READ surface is unchanged: every flat accessor below still exists and
	 * still returns what it did, so listeners and consumers that only read the
	 * event need no change. Only construction moved.
	 *
	 * @param SigningProvenance $provenance Who asked, and about which object.
	 * @param string $subjectLabel Human display label for the subject
	 * @param string $documentReference NC Files file id / path or document content reference
	 * @param array<int, mixed> $signers Ordered signers list (userId/displayName/email/order)
	 * @param string $signatureLevel Signature level (SES|AdES|QES)
	 * @param string $signingMode Signing mode (sequential|parallel)
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SigningProvenance $provenance,
		private readonly string $subjectLabel = '',
		private readonly string $documentReference = '',
		private readonly array $signers = [],
		private readonly string $signatureLevel = 'SES',
		private readonly string $signingMode = 'sequential',
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * Get the provenance block.
	 *
	 * @return SigningProvenance The provenance.
	 */
	public function getProvenance(): SigningProvenance {
		return $this->provenance;
	}//end getProvenance()

	/**
	 * Get the consumer app that requested the signature.
	 *
	 * @return string The source app id.
	 */
	public function getSourceApp(): string {
		return $this->provenance->getSourceApp();
	}//end getSourceApp()

	/**
	 * Get the OpenRegister register of the originating object.
	 *
	 * @return string The subject register.
	 */
	public function getSubjectRegister(): string {
		return (string)$this->provenance->getSubjectRegister();
	}//end getSubjectRegister()

	/**
	 * Get the OpenRegister schema of the originating object.
	 *
	 * @return string The subject schema.
	 */
	public function getSubjectSchema(): string {
		return (string)$this->provenance->getSubjectSchema();
	}//end getSubjectSchema()

	/**
	 * Get the OpenRegister id of the originating object.
	 *
	 * @return string The subject id.
	 */
	public function getSubjectId(): string {
		return (string)$this->provenance->getSubjectId();
	}//end getSubjectId()

	/**
	 * Get the human display label for the subject.
	 *
	 * @return string The subject label.
	 */
	public function getSubjectLabel(): string {
		return $this->subjectLabel;
	}//end getSubjectLabel()

	/**
	 * Get the document reference (NC Files ref or content reference).
	 *
	 * @return string The document reference.
	 */
	public function getDocumentReference(): string {
		return $this->documentReference;
	}//end getDocumentReference()

	/**
	 * Get the ordered signers list.
	 *
	 * @return array<int, mixed> The signers.
	 */
	public function getSigners(): array {
		return $this->signers;
	}//end getSigners()

	/**
	 * Get the requested signature level (SES|AdES|QES).
	 *
	 * @return string The signature level.
	 */
	public function getSignatureLevel(): string {
		return $this->signatureLevel;
	}//end getSignatureLevel()

	/**
	 * Get the requested signing mode (sequential|parallel).
	 *
	 * @return string The signing mode.
	 */
	public function getSigningMode(): string {
		return $this->signingMode;
	}//end getSigningMode()

	/**
	 * Get the consumer's own external reference.
	 *
	 * @return string The external reference.
	 */
	public function getExternalReference(): string {
		return $this->provenance->getExternalReference();
	}//end getExternalReference()

	/**
	 * Get the correlation id echoed on the conclusion event.
	 *
	 * @return string The correlation id.
	 */
	public function getCorrelationId(): string {
		return $this->provenance->getCorrelationId();
	}//end getCorrelationId()

	/**
	 * Get the id of the signing request DocuDesk created (result slot).
	 *
	 * @return string|null Null until DocuDesk's listener has handled the event.
	 */
	public function getSigningRequestId(): ?string {
		return $this->signingRequestId;
	}//end getSigningRequestId()

	/**
	 * Set the resolved signing-request id (written by DocuDesk's listener).
	 *
	 * @param string $signingRequestId The created signing-request id.
	 *
	 * @return void
	 */
	public function setSigningRequestId(string $signingRequestId): void {
		$this->signingRequestId = $signingRequestId;

	}//end setSigningRequestId()

	/**
	 * Whether DocuDesk's listener handled this request.
	 *
	 * @return bool True when DocuDesk created a signing request.
	 */
	public function isHandled(): bool {
		return $this->handled;
	}//end isHandled()

	/**
	 * Mark whether DocuDesk's listener handled this request.
	 *
	 * @param bool $handled True when DocuDesk created a signing request.
	 *
	 * @return void
	 */
	public function setHandled(bool $handled): void {
		$this->handled = $handled;

	}//end setHandled()
}//end class
