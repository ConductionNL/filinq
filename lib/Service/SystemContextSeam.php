<?php

/**
 * System Context Seam
 *
 * The one named place where Filinq lets OpenRegister drop its RBAC and
 * organisation filtering, for work that has no user to scope it by: a
 * background sweep across every organisation, or an instance-admin
 * aggregate. Everything else runs under OpenRegister's enforcing defaults.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs a system operation across organisations, with a stated and logged reason.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-openregister-enforcement-is-on-by-default-bypasses-are-removed-req-ddmth-002
 */
class SystemContextSeam {

	/**
	 * Longest reason accepted; a reason is a sentence, not a payload.
	 *
	 * @var int
	 */
	private const MAX_REASON_LENGTH = 200;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Records every use of the seam.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Run an operation as the system, so OpenRegister applies neither RBAC nor organisation filtering.
	 *
	 * The elevation is OpenRegister's own `runAsSystem()`: the operation makes
	 * ordinary ObjectService calls and passes no bypass flag, so the seam is the
	 * only code in Filinq that relaxes enforcement.
	 *
	 * The reason is a literal written at the call site ("objection-deadline
	 * sweep over every organisation"), never a value from a request. It is
	 * logged with the calling class and method on every use, so a cross-
	 * organisation read can always be traced to the code that asked for it.
	 * A reason that is empty, multi-line or longer than a sentence is refused
	 * before anything runs.
	 *
	 * @param ObjectServiceInterface $objectService The OpenRegister object service.
	 * @param literal-string         $reason        Why this operation may see every organisation.
	 * @param callable               $operation     The operation.
	 *
	 * @return mixed What the operation returns.
	 *
	 * @throws InvalidArgumentException When the reason is missing or not a single short line.
	 *
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#2-backend-enforcement
	 */
	public function run(ObjectServiceInterface $objectService, string $reason, callable $operation): mixed {
		$reason = trim($reason);
		if ($reason === ''
			|| strlen($reason) > self::MAX_REASON_LENGTH
			|| preg_match('/[\r\n\x00]/', $reason) === 1
		) {
			throw new InvalidArgumentException(
				'A system operation across organisations needs a short, single-line reason written at the call site.'
			);
		}

		$this->logger->info(
			'Filinq ran an operation as the system, across organisations',
			['reason' => $reason, 'caller' => $this->caller()]
		);

		return $objectService->runAsSystem($operation);
	}//end run()

	/**
	 * The class and method that called the seam.
	 *
	 * @return string `Class::method`, or the function name outside a class.
	 */
	private function caller(): string {
		$frame = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? []);

		return ltrim((string)($frame['class'] ?? '') . '::' . (string)($frame['function'] ?? 'unknown'), ':');
	}//end caller()
}//end class
