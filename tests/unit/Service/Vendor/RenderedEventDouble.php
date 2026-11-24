<?php

/**
 * A double of integriq's DocumentRenderedEvent
 *
 * Carries the constructor and readers of integriq's outcome event
 * (`lib/Event/DocumentRenderedEvent.php` on integriq development): job id,
 * status, requestedBy, fileReference and error.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Vendor
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md#2-backend
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Vendor;

use OCP\EventDispatcher\Event;

/**
 * integriq's outcome of a vendor render.
 */
class RenderedEventDouble extends Event {

	/**
	 * Constructor.
	 *
	 * @param string $jobId         The integriq job.
	 * @param string $status        `rendered` or `failed`.
	 * @param string $requestedBy   The user the render was made for.
	 * @param string $fileReference The vendor's reference to the document.
	 * @param string $error         Why there is no document.
	 * @param string $document      The rendered document, as integriq hands it over.
	 */
	public function __construct(
		private readonly string $jobId,
		private readonly string $status,
		private readonly string $requestedBy = '',
		private readonly string $fileReference = '',
		private readonly string $error = '',
		private readonly string $document = '',
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * The job id.
	 *
	 * @return string
	 */
	public function getJobId(): string {
		return $this->jobId;
	}//end getJobId()

	/**
	 * The status.
	 *
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}//end getStatus()

	/**
	 * The user.
	 *
	 * @return string
	 */
	public function getRequestedBy(): string {
		return $this->requestedBy;
	}//end getRequestedBy()

	/**
	 * The vendor's file reference.
	 *
	 * @return string
	 */
	public function getFileReference(): string {
		return $this->fileReference;
	}//end getFileReference()

	/**
	 * The error.
	 *
	 * @return string
	 */
	public function getError(): string {
		return $this->error;
	}//end getError()

	/**
	 * The rendered document.
	 *
	 * @return string
	 */
	public function getDocument(): string {
		return $this->document;
	}//end getDocument()
}//end class
