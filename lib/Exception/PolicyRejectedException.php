<?php

/**
 * Policy Rejected Exception
 *
 * Thrown when a publication-prohibition rule blocks consent creation.
 * Carries the matching rule UUID and name so callers can surface
 * operator-facing detail without re-querying the policy layer.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;

/**
 * Thrown when PolicyMatchService returns a prohibition match during consent creation.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */
class PolicyRejectedException extends RuntimeException {
	/**
	 * Construct a PolicyRejectedException.
	 *
	 * @param string $ruleUuid UUID of the matching prohibition rule.
	 * @param string $ruleName Human-readable name of the rule.
	 * @param string $message Optional detail message.
	 * @param int $code Optional error code.
	 * @param \Throwable $previous Optional previous exception.
	 */
	public function __construct(
		private readonly string $ruleUuid,
		private readonly string $ruleName,
		string $message = 'Publication prohibited by policy rule',
		int $code = 0,
		?\Throwable $previous = null,
	) {
		parent::__construct(message: $message, code: $code, previous: $previous);

	}//end __construct()

	/**
	 * Get the UUID of the matching prohibition rule.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
	 */
	public function getRuleUuid(): string {
		return $this->ruleUuid;
	}//end getRuleUuid()

	/**
	 * Get the human-readable name of the matching prohibition rule.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
	 */
	public function getRuleName(): string {
		return $this->ruleName;
	}//end getRuleName()
}//end class
