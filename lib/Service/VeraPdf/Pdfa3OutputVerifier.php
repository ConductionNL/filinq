<?php

/**
 * Checks what a PDF/A-3 conversion made with veraPDF before it is handed
 * back.
 *
 * The marker guard in Pdfa3ConversionService stays the floor: it proves the
 * output claims PDF/A-3. This adds the truth when veraPDF is installed. By
 * default a failing verdict is logged and kept on the report while the
 * bytes still go out, so installing veraPDF never breaks a conversion that
 * worked the day before. With filinq.pdfa3.strict_verify on, a failing
 * verdict, or a validator that cannot answer, stops the conversion.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCA\Filinq\Exception\VeraPdfException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * The X-Docudesk-Pdfa3-Verified verdict for one conversion.
 */
class Pdfa3OutputVerifier {

	public const CFG_STRICT = 'filinq.pdfa3.strict_verify';

	public const VERIFIED = 'true';

	public const NOT_VERIFIED = 'false';

	public const SKIPPED = 'skipped';

	/**
	 * Output whose pages Filinq rendered itself.
	 */
	public const ORIGIN_RENDERED = ConformanceGuidance::ORIGIN_RENDERED;

	/**
	 * Output whose pages were imported whole from another PDF.
	 */
	public const ORIGIN_IMPORTED = ConformanceGuidance::ORIGIN_IMPORTED;

	/**
	 * Constructor.
	 *
	 * @param ConformanceService $conformance The conformance check.
	 * @param IAppConfig         $appConfig   The app config.
	 * @param LoggerInterface    $logger      The log.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ConformanceService $conformance,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Verify conversion output.
	 *
	 * @param string   $bytes        The PDF/A-3 output.
	 * @param string   $origin       ConformanceGuidance::ORIGIN_RENDERED or ORIGIN_IMPORTED.
	 * @param int|null $sourceFileId The converted file, whose report row it becomes.
	 *
	 * @return string VERIFIED, NOT_VERIFIED or SKIPPED.
	 *
	 * @throws Pdfa3ConversionException In strict mode, when the output fails or cannot be checked.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.5
	 */
	public function verify(string $bytes, string $origin, ?int $sourceFileId): string {
		if ($this->conformance->isAvailable() === false) {
			return self::SKIPPED;
		}

		try {
			$report = $this->conformance->checkConversion(bytes: $bytes, origin: $origin, sourceFileId: $sourceFileId);
		} catch (VeraPdfException $e) {
			$this->logger->warning(
				message: '[Pdfa3OutputVerifier] veraPDF gave no verdict on the conversion output',
				context: ['reason' => $e->getReason(), 'error' => $e->getMessage()]
			);
			if ($this->isStrict() === true) {
				throw $this->refusal(detail: 'veraPDF could not check the output (' . $e->getReason() . ').');
			}

			return self::SKIPPED;
		}

		if (($report['stored'] ?? false) === false && $sourceFileId !== null) {
			$this->logger->warning(message: '[Pdfa3OutputVerifier] the conversion verdict could not be stored', context: ['fileId' => $sourceFileId]);
		}

		if ($report['compliant'] === true) {
			return self::VERIFIED;
		}

		$this->logger->warning(
			message: '[Pdfa3OutputVerifier] conversion output fails PDF/A',
			context: [
				'fileId' => $sourceFileId,
				'failedRules' => array_column((array) $report['failedRules'], 'ruleId'),
				'fonts' => $report['fontsNotEmbedded'],
			]
		);
		if ($this->isStrict() === true) {
			throw $this->refusal(detail: sprintf('veraPDF found %d failed rules.', (int) $report['failedRuleCount']));
		}

		return self::NOT_VERIFIED;

	}//end verify()

	/**
	 * Whether strict verification is on.
	 *
	 * @return bool True when on.
	 */
	private function isStrict(): bool {
		return in_array(strtolower($this->appConfig->getValueString('filinq', self::CFG_STRICT, 'false')), ['true', '1', 'yes', 'on'], true);

	}//end isStrict()

	/**
	 * The fail-loud refusal of strict mode.
	 *
	 * @param string $detail What veraPDF said.
	 *
	 * @return Pdfa3ConversionException The exception.
	 */
	private function refusal(string $detail): Pdfa3ConversionException {
		return new Pdfa3ConversionException(
			reason: Pdfa3ConversionException::REASON_OUTPUT_VALIDATION_FAILED,
			message: 'The PDF/A-3 output does not pass veraPDF, and strict verification is on: ' . $detail,
			adminHint: sprintf(
				'See the conformanceReport of the source file, or set %s to "false" to return failing output with X-Docudesk-Pdfa3-Verified: false.',
				self::CFG_STRICT
			),
			code: 422
		);

	}//end refusal()
}//end class
