<?php

/**
 * Sanitize choice
 *
 * Whether an anonymisation run sanitizes its delivered artifact: the per-call
 * `sanitize` value, else the tenant default, else false.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCP\IAppConfig;

/**
 * Resolves the effective `sanitize` flag of an anonymisation request, shared by
 * the single-file and the batch endpoint.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-4
 */
final class SanitizeChoice {

	/**
	 * Tenant default key. The per-call `sanitize` request param overrides it.
	 */
	public const DEFAULT_KEY = 'filinq.sanitization.default';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig Tenant configuration (reads filinq.sanitization.default).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Resolve the effective `sanitize` flag for this request.
	 *
	 * Order: the per-call boolean when supplied → the tenant default → false.
	 * Returns null when the per-call value is supplied but is not a boolean;
	 * the caller maps that to HTTP 400.
	 *
	 * @param array<string, mixed> $params Request params.
	 *
	 * @return bool|null The flag, or null when an invalid value was supplied.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-4
	 */
	public function resolve(array $params): ?bool {
		if (array_key_exists('sanitize', $params) === true) {
			if (is_bool($params['sanitize']) === false) {
				return null;
			}

			return $params['sanitize'];
		}

		$tenantDefault = strtolower(trim($this->appConfig->getValueString('filinq', self::DEFAULT_KEY, '0')));

		return in_array($tenantDefault, ['1', 'true', 'yes', 'on'], true);

	}//end resolve()
}//end class
