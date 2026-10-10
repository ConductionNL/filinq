<?php

/**
 * Filinq DocumentStampRequestedEvent
 *
 * Public cross-app command a sibling app dispatches to have filinq stamp a
 * text on every page of a PDF, for example decidiq putting a council member's
 * name and the date on a confidential paper before serving it. Handled
 * synchronously by DocumentStampRequestedListener; the dispatching app reads
 * the stamped PDF, or the refusal, back off the same instance.
 *
 * The stamped copy is personal to one reader, so it travels as bytes in the
 * result slot and is never stored as a file by filinq.
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * Ask filinq to stamp a text on every page of a PDF.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
 */
class DocumentStampRequestedEvent extends Event {

	/**
	 * The stamped PDF bytes, once handled.
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
	 * Why filinq did not stamp, as a code.
	 *
	 * @var string
	 */
	private string $refusalCode = '';

	/**
	 * Why filinq did not stamp, as a sentence.
	 *
	 * @var string
	 */
	private string $refusalReason = '';

	/**
	 * Constructor.
	 *
	 * @param string $sourceApp     The app asking, for the log.
	 * @param string $pdfContent    The PDF bytes to stamp.
	 * @param string $text          The text, at most 200 characters; lines split on a newline.
	 * @param string $placement     diagonal, footer or both.
	 * @param string $correlationId The caller's own reference, echoed in the log.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
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
	 * The app that asked.
	 *
	 * @return string The app id.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getSourceApp(): string {
		return $this->sourceApp;

	}//end getSourceApp()

	/**
	 * The PDF to stamp.
	 *
	 * @return string The PDF bytes.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getPdfContent(): string {
		return $this->pdfContent;

	}//end getPdfContent()

	/**
	 * The stamp text.
	 *
	 * @return string The text.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getText(): string {
		return $this->text;

	}//end getText()

	/**
	 * Where the text goes.
	 *
	 * @return string diagonal, footer or both.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getPlacement(): string {
		return $this->placement;

	}//end getPlacement()

	/**
	 * The caller's reference.
	 *
	 * @return string The correlation id.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getCorrelationId(): string {
		return $this->correlationId;

	}//end getCorrelationId()

	/**
	 * The stamped PDF, once handled.
	 *
	 * @return string|null The bytes, or null when not handled.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getStampedPdf(): ?string {
		return $this->stampedPdf;

	}//end getStampedPdf()

	/**
	 * Store the stamped PDF and mark the event handled.
	 *
	 * @param string $stampedPdf The stamped bytes.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function setStampedPdf(string $stampedPdf): void {
		$this->stampedPdf = $stampedPdf;
		$this->handled = true;

	}//end setStampedPdf()

	/**
	 * Whether filinq stamped the PDF.
	 *
	 * @return bool True when the stamped PDF is in the result slot.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function isHandled(): bool {
		return $this->handled;

	}//end isHandled()

	/**
	 * Mark the event handled or not.
	 *
	 * @param bool $handled Whether it was handled.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function setHandled(bool $handled): void {
		$this->handled = $handled;

	}//end setHandled()

	/**
	 * Record why the PDF was not stamped.
	 *
	 * @param string $code   not-a-pdf, encrypted, too-large or failed.
	 * @param string $reason A sentence for the log or the reader.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function setRefusal(string $code, string $reason): void {
		$this->refusalCode = $code;
		$this->refusalReason = $reason;
		$this->handled = false;

	}//end setRefusal()

	/**
	 * The refusal code, '' when none.
	 *
	 * @return string The code.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getRefusalCode(): string {
		return $this->refusalCode;

	}//end getRefusalCode()

	/**
	 * The refusal reason, '' when none.
	 *
	 * @return string The reason.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function getRefusalReason(): string {
		return $this->refusalReason;

	}//end getRefusalReason()
}//end class
