<?php

/**
 * Output format choice
 *
 * Which output an anonymisation run produces: the per-call value, else the
 * tenant default, else pdf-only.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCP\IAppConfig;

/**
 * Resolves the effective `outputFormat` of an anonymisation request, shared by
 * the single-file and the batch endpoint.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class OutputFormatChoice {

	/**
	 * Default-output-format tenant config key. The per-call
	 * `outputFormat` request param overrides this when supplied.
	 */
	public const DEFAULT_OUTPUT_FORMAT_KEY = 'filinq.anonymisation.default_output_format';

	/**
	 * Supported values for the `outputFormat` request param + tenant
	 * config. Anything else from the request results in HTTP 400.
	 *
	 * - `pdf-only` (default): convert to PDF and delete the native
	 *   anonymised intermediate so only the PDF remains.
	 * - `pdf`: convert to PDF but keep the native intermediate too.
	 * - `preserve`: skip conversion; native format is the only output.
	 */
	public const VALID_OUTPUT_FORMATS = ['pdf-only', 'pdf', 'preserve'];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig Tenant configuration provider (reads
	 *                              filinq.anonymisation.default_output_format)
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {

	}//end __construct()

	/**
	 * Resolve the effective `outputFormat` for this request.
	 *
	 * Order: per-call value (when supplied and valid) → tenant default
	 * from IAppConfig → hard-coded `"pdf-only"` fallback.
	 *
	 * Returns `null` when the per-call value is supplied but invalid;
	 * the caller maps that to HTTP 400.
	 *
	 * @param array<string,mixed> $params Request params.
	 *
	 * @return string|null Resolved outputFormat ('pdf-only'|'pdf'|'preserve'),
	 *                     or null when an invalid value was supplied.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function resolve(array $params): ?string {
		if (array_key_exists('outputFormat', $params) === true) {
			return self::perCall(value: $params['outputFormat']);
		}

		return $this->tenantDefault();

	}//end resolve()

	/**
	 * The per-call value when it is one of the supported formats.
	 *
	 * @param mixed $value The `outputFormat` request param.
	 *
	 * @return string|null The value, or null when it is not supported.
	 */
	private static function perCall(mixed $value): ?string {
		if (is_string($value) === false
			|| in_array($value, self::VALID_OUTPUT_FORMATS, true) === false
		) {
			return null;
		}

		return $value;

	}//end perCall()

	/**
	 * The tenant default, or pdf-only when it is not a supported format.
	 *
	 * @return string The tenant's output format.
	 */
	private function tenantDefault(): string {
		$tenantDefault = $this->appConfig->getValueString(
			'filinq',
			self::DEFAULT_OUTPUT_FORMAT_KEY,
			'pdf-only'
		);

		if (in_array($tenantDefault, self::VALID_OUTPUT_FORMATS, true) === false) {
			// Malformed tenant setting falls back to spec default
			// rather than rejecting the call.
			return 'pdf-only';
		}

		return $tenantDefault;

	}//end tenantDefault()
}//end class
