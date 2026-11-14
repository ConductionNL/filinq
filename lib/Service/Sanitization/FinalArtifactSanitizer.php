<?php

/**
 * Final-artifact sanitization pass of an anonymise run
 *
 * Runs the outbound sanitization pass on the file an anonymise run actually
 * delivers: after the PDF conversion and after any grondslagen summary was
 * appended, because the conversion writes fresh metadata the source-side pass
 * never saw. A failure never discards the anonymised file: it is kept, flagged
 * as unsanitized, and the run answers with a warning instead of an error.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Sanitization
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

namespace OCA\Filinq\Service\Sanitization;

use OCA\Filinq\Kernel\Contracts\DocumentSanitizationServiceInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sanitizes the delivered artifact of an anonymise run, warning instead of failing.
 *
 * @spec openspec/specs/anonymization/spec.md#requirement-the-anonymise-endpoint-must-accept-an-optional-sanitize-flag-for-the-final-artifact-req-ddsan-007
 */
class FinalArtifactSanitizer {

	/**
	 * Constructor.
	 *
	 * @param DocumentSanitizationServiceInterface $sanitizer The outbound sanitization pass.
	 * @param LoggerInterface                      $logger    The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentSanitizationServiceInterface $sanitizer,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Sanitize the artifact the run delivered and report it on the result.
	 *
	 * On success the result gains `sanitization` (the derivative, its report and
	 * record). On any failure the anonymised file stays as it is, `sanitization`
	 * says it is unsanitized, and `sanitizationWarning` says why.
	 *
	 * @param array<string, mixed> $resultInfo The finished run result, `anonymizedFileId` among it.
	 * @param array<string, mixed> $context    The run context: `userId` and `anonymisedNode`.
	 *
	 * @return array<string, mixed> The result with the sanitization outcome.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-4
	 */
	public function apply(array $resultInfo, array $context): array {
		$fileId = (int) ($resultInfo['anonymizedFileId'] ?? 0);
		if ($fileId <= 0) {
			return self::warn(resultInfo: $resultInfo, code: 'no_final_artifact', fileId: null);
		}

		$userId = self::actingUser(context: $context);
		if ($userId === '') {
			return self::warn(resultInfo: $resultInfo, code: 'no_acting_user', fileId: $fileId);
		}

		try {
			$outcome = $this->sanitizer->sanitize(fileId: $fileId, userId: $userId, trigger: 'anonymisation');
		} catch (Throwable $e) {
			$this->logger->warning(
				'Final-artifact sanitization failed; the anonymised file is kept unsanitized',
				['fileId' => $fileId, 'reason' => $e->getMessage()]
			);
			$code = $e->getMessage();
			if ($code === '') {
				$code = 'internal';
			}

			return self::warn(resultInfo: $resultInfo, code: $code, fileId: $fileId);
		}

		if (($outcome['sanitized'] ?? false) !== true) {
			return self::warn(resultInfo: $resultInfo, code: (string) ($outcome['reason'] ?? 'sanitization_skipped'), fileId: $fileId);
		}

		$resultInfo['sanitization'] = [
			'sanitized'         => true,
			'fileId'            => $fileId,
			'sanitizedFileId'   => $outcome['sanitizedFileId'] ?? null,
			'sanitizedFileName' => $outcome['sanitizedFileName'] ?? null,
			'report'            => $outcome['report'] ?? [],
			'recordId'          => (string) ($outcome['recordId'] ?? ''),
		];

		return $resultInfo;

	}//end apply()

	/**
	 * Who the pass runs for: the acting user, else the owner of the delivered file.
	 *
	 * The folder job and the batch path run without an acting user; the file
	 * they just wrote belongs to its owner, whose folder the pass reads it through.
	 *
	 * @param array<string, mixed> $context The run context.
	 *
	 * @return string The user id, '' when there is none.
	 */
	private static function actingUser(array $context): string {
		$userId = (string) ($context['userId'] ?? '');
		if ($userId !== '') {
			return $userId;
		}

		$node = ($context['anonymisedNode'] ?? null);
		if (is_object($node) === false || method_exists($node, 'getOwner') === false) {
			return '';
		}

		try {
			$owner = $node->getOwner();
		} catch (Throwable) {
			return '';
		}

		if (is_object($owner) === false || method_exists($owner, 'getUID') === false) {
			return '';
		}

		return (string) $owner->getUID();

	}//end actingUser()

	/**
	 * The result with the anonymised file kept and flagged unsanitized.
	 *
	 * @param array<string, mixed> $resultInfo The run result.
	 * @param string               $code       Why the pass did not clean the file.
	 * @param int|null             $fileId     The kept, unsanitized file.
	 *
	 * @return array<string, mixed> The result with the warning.
	 */
	private static function warn(array $resultInfo, string $code, ?int $fileId): array {
		$resultInfo['sanitization'] = [
			'sanitized'         => false,
			'fileId'            => $fileId,
			'unsanitizedFileId' => $fileId,
		];
		$resultInfo['sanitizationWarning'] = [
			'code'              => $code,
			'message'           => 'The anonymised file was kept but not sanitized: ' . $code,
			'unsanitizedFileId' => $fileId,
		];

		return $resultInfo;

	}//end warn()
}//end class
