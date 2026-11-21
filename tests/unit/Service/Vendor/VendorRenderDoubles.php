<?php

/**
 * Doubles for the vendor render tests
 *
 * A render command with the shape of integriq's DocumentRenderRequestedEvent
 * (read on integriq `development`, lib/Event/DocumentRenderRequestedEvent.php):
 * constructor (sourceId, templateId, data, requestedBy, requestedByApp),
 * getJobId()/setJobId() and getRefusal()/refuse(reason, code).
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
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Vendor;

use OCP\EventDispatcher\Event;

/**
 * integriq's render command, as filinq dispatches it.
 */
class RenderRequestedEventDouble extends Event {

	/**
	 * The job id integriq answers.
	 *
	 * @var string|null
	 */
	private ?string $jobId = null;

	/**
	 * The refusal integriq answers.
	 *
	 * @var array{code: string, reason: string}|null
	 */
	private ?array $refusal = null;

	/**
	 * Constructor.
	 *
	 * @param string               $sourceId       The source.
	 * @param string               $templateId     The vendor template id.
	 * @param array<string, mixed> $data           The merge data.
	 * @param string               $requestedBy    The user.
	 * @param string               $requestedByApp The app.
	 */
	public function __construct(
		public readonly string $sourceId,
		public readonly string $templateId,
		public readonly array $data,
		public readonly string $requestedBy = '',
		public readonly string $requestedByApp = 'filinq',
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * The job id.
	 *
	 * @return string|null The job id.
	 */
	public function getJobId(): ?string {
		return $this->jobId;

	}//end getJobId()

	/**
	 * Answer a job id.
	 *
	 * @param string $jobId The job id.
	 *
	 * @return void
	 */
	public function setJobId(string $jobId): void {
		$this->jobId = $jobId;

	}//end setJobId()

	/**
	 * The refusal.
	 *
	 * @return array{code: string, reason: string}|null The refusal.
	 */
	public function getRefusal(): ?array {
		return $this->refusal;

	}//end getRefusal()

	/**
	 * Refuse the render.
	 *
	 * @param string $reason Why.
	 * @param string $code   The code.
	 *
	 * @return void
	 */
	public function refuse(string $reason, string $code): void {
		$this->refusal = ['code' => $code, 'reason' => $reason];

	}//end refuse()
}//end class
