<?php

/**
 * A sibling app asks filinq to stamp a text on every page of a PDF.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * A typed command (ADR-041): the caller dispatches it with a PDF and a text
 * and reads the stamped PDF, or a refusal, back from the result slot.
 *
 * The stamped copy is only ever in this event; filinq stores nothing. When
 * filinq is not installed nobody handles the event, and `isHandled()` stays
 * false with no refusal code.
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
 */
class DocumentStampRequestedEvent extends Event {

	/**
	 * The stamped PDF, once handled.
	 *
	 * @var string|null
	 */
	private ?string $stampedPdf = null;

	/**
	 * Whether filinq stamped the PDF.
	 *
	 * @var bool
	 */
	private bool $handled = false;

	/**
	 * The refusal code, when refused.
	 *
	 * @var string|null
	 */
	private ?string $refusalCode = null;

	/**
	 * The refusal reason, when refused.
	 *
	 * @var string|null
	 */
	private ?string $refusalReason = null;

	/**
	 * Constructor.
	 *
	 * @param string $sourceApp     The calling app's id.
	 * @param string $pdfContent    The PDF bytes.
	 * @param string $text          The text, at most 200 characters, lines separated by a newline.
	 * @param string $placement     diagonal, footer or both.
	 * @param string $correlationId The caller's own reference, for its logs.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function __construct(
		private readonly string $sourceApp,
		private readonly string $pdfContent,
		private readonly string $text,
		private readonly string $placement = 'both',
		private readonly string $correlationId = '',
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * The calling app's id.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getSourceApp(): string {
		return $this->sourceApp;
	}//end getSourceApp()

	/**
	 * The PDF bytes to stamp.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getPdfContent(): string {
		return $this->pdfContent;
	}//end getPdfContent()

	/**
	 * The text to stamp.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getText(): string {
		return $this->text;
	}//end getText()

	/**
	 * Where the text goes: diagonal, footer or both.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getPlacement(): string {
		return $this->placement;
	}//end getPlacement()

	/**
	 * The caller's reference.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getCorrelationId(): string {
		return $this->correlationId;
	}//end getCorrelationId()

	/**
	 * The stamped PDF, or null when not stamped.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getStampedPdf(): ?string {
		return $this->stampedPdf;
	}//end getStampedPdf()

	/**
	 * Put the stamped PDF in the result slot.
	 *
	 * @param string $stampedPdf The stamped bytes.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function setStampedPdf(string $stampedPdf): void {
		$this->stampedPdf = $stampedPdf;
	}//end setStampedPdf()

	/**
	 * Whether filinq stamped the PDF.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function isHandled(): bool {
		return $this->handled;
	}//end isHandled()

	/**
	 * Mark whether filinq stamped the PDF.
	 *
	 * @param bool $handled Whether it did.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function setHandled(bool $handled): void {
		$this->handled = $handled;
	}//end setHandled()

	/**
	 * Record why the PDF was not stamped.
	 *
	 * @param string $code   not-a-pdf, encrypted, too-large or failed.
	 * @param string $reason Why, in words a caller can show.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function setRefusal(string $code, string $reason): void {
		$this->refusalCode   = $code;
		$this->refusalReason = $reason;
	}//end setRefusal()

	/**
	 * The refusal code, or null.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getRefusalCode(): ?string {
		return $this->refusalCode;
	}//end getRefusalCode()

	/**
	 * The refusal reason, or null.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function getRefusalReason(): ?string {
		return $this->refusalReason;
	}//end getRefusalReason()
}//end class
