<?php

/**
 * Final Document Failure Mapper
 *
 * Turns a failure from the final-document services into the status code and
 * the body the caller sees. A refusal because the document is final is a 409
 * carrying the sentence the guard wrote; anything unrecognised is logged here
 * and reported generically, so a failure never leaks a path or an identity.
 *
 * It lives beside the services rather than inside the controller because the
 * controller already carries four of them, and phpmd counts every type a class
 * names. Shaping a failure is its own job, and this is where it is done.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\DocumentFinalException;
use OCP\AppFramework\Http;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Maps a final-document failure onto a status code and a response body.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class FinalDocumentFailureMapper {

	/**
	 * Constructor.
	 *
	 * @param IL10N $l10n Localisation for the messages this class writes itself.
	 * @param LoggerInterface $logger Logger for the failures nobody recognised.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Shape one failure.
	 *
	 * The caller asked for something the document's state does not allow, so a
	 * refusal is a 409 and the sentence it carries is the one to show. A
	 * service refusal with a message of its own is a 400, a missing document a
	 * 404, and anything else is logged and answered with the caller's fallback.
	 *
	 * @param Throwable $exception The failure.
	 * @param string $fallback The localised message for an unrecognised failure.
	 *
	 * @return array{status: int, body: array<string, mixed>} The status code and the body.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function shape(Throwable $exception, string $fallback): array {
		if ($exception instanceof DocumentFinalException) {
			return [
				'status' => Http::STATUS_CONFLICT,
				'body' => [
					'error' => $exception->getMessage(),
					'reason' => 'document-final',
					'version' => $exception->getVersion(),
				],
			];
		}

		if ($exception instanceof RuntimeException) {
			$message = $exception->getMessage();
			if ($message === 'Document not found.') {
				return [
					'status' => Http::STATUS_NOT_FOUND,
					'body' => ['error' => $this->l10n->t('Document not found'), 'reason' => 'not-found'],
				];
			}

			return [
				'status' => Http::STATUS_BAD_REQUEST,
				'body' => ['error' => $message, 'reason' => 'refused'],
			];
		}

		$this->logger->error(
			message: '[FinalDocumentFailureMapper] a final-document request failed',
			context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $exception->getMessage()]
		);

		return [
			'status' => Http::STATUS_INTERNAL_SERVER_ERROR,
			'body' => ['error' => $fallback],
		];

	}//end shape()
}//end class
