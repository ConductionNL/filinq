<?php

/**
 * Replacement Verification Service
 *
 * Derives the real replacement statistics of an anonymisation run from the
 * source document's textual projection, so `replacementCount` is never
 * fabricated from the number of entities that were sent (issue #286).
 * Extracted verbatim from AnonymizationService.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/anonymization/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Computes attempted/applied replacement statistics for an anonymisation run.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class ReplacementVerificationService {
	/**
	 * Constructor for ReplacementVerificationService
	 *
	 * @param LoggerInterface $logger Logger for unreadable-source diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Read a Nextcloud file node's content as text, safely.
	 *
	 * Returns the raw content for text-like MIME types (text/*,
	 * application/json, application/xml, text/csv, …). Returns null for
	 * binary formats (PDF, DOCX, XLSX, …) where the file content is a
	 * compressed/encoded container and entity values are NOT findable
	 * literally with str_ipos. Callers MUST treat a null return as
	 * "verification not possible" and surface a `replacementsVerified=false`
	 * flag rather than silently reporting zero or all replacements.
	 *
	 * @param mixed $node Nextcloud file node (OCP\Files\File or compatible).
	 *
	 * @return string|null Text content, or null when the node is binary /
	 *                     unreadable / not a file.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function readNodeText(mixed $node): ?string {
		if (is_object($node) === false) {
			return null;
		}

		try {
			// We need both a content reader AND a MIME-type oracle to
			// know whether the bytes will be findable as literal text.
			if (method_exists($node, 'getMimeType') === false
				|| method_exists($node, 'getContent') === false
			) {
				return null;
			}

			if ($this->isTextLikeMime(mimeType: (string)$node->getMimeType()) === false) {
				return null;
			}

			$content = $node->getContent();
			if (is_string($content) === false || $content === '') {
				return null;
			}

			return $content;
		} catch (Throwable $e) {
			$this->logger->debug(
				'Could not read node content for replacement verification; falling back to '
				. 'unverified count: ' . $e->getMessage(),
				['exception' => $e]
			);
			return null;
		}//end try

	}//end readNodeText()

	/**
	 * Whether a MIME type carries entity values as literal, findable text.
	 *
	 * @param string $mimeType The node's MIME type.
	 *
	 * @return bool True when the content can be searched literally.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function isTextLikeMime(string $mimeType): bool {
		return (str_starts_with($mimeType, 'text/') === true
			|| $mimeType === 'application/json'
			|| $mimeType === 'application/xml'
			|| $mimeType === 'application/x-yaml'
			|| $mimeType === 'application/x-ndjson'
		);

	}//end isTextLikeMime()

	/**
	 * Compute real replacement statistics for an anonymization run.
	 *
	 * For each mapped entity, check whether its literal text is present
	 * (case-insensitive, mirroring OR's str_ireplace semantics) in the
	 * original source text. Entities that are not present cannot have
	 * been replaced — they are surfaced as `unmatchedEntities`. When the
	 * source text could not be read at all (binary format such as PDF /
	 * DOCX — see readNodeText()), verification is reported as
	 * impossible (`replacementsVerified=false`, `replacementsApplied=null`).
	 *
	 * @param array<int, array<string, mixed>> $mappedEntities Entities sent to OR.
	 * @param string|null $originalText Textual projection of the
	 *                                  original source, or null
	 *                                  when the file is binary.
	 *
	 * @return array{
	 *     replacementsAttempted: int,
	 *     replacementsApplied: int|null,
	 *     replacementsVerified: bool,
	 *     unmatchedEntities: array<int, array{text: string, entityType: string}>
	 * }
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function verify(array $mappedEntities, ?string $originalText): array {
		$attempted = count($mappedEntities);

		if ($originalText === null) {
			return [
				'replacementsAttempted' => $attempted,
				'replacementsApplied' => null,
				'replacementsVerified' => false,
				'unmatchedEntities' => [],
			];
		}

		$applied = 0;
		$unmatched = [];

		foreach ($mappedEntities as $entity) {
			$text = (string)($entity['text'] ?? '');
			if ($text === '') {
				continue;
			}

			// Case-insensitive search via mb_stripos with explicit UTF-8
			// encoding mirrors the str_ireplace semantics used in OR's
			// DocumentProcessingHandler::replaceWordsInTextDocument while
			// being safe for multibyte content.
			$found = mb_stripos($originalText, $text, 0, 'UTF-8');

			if ($found !== false) {
				$applied++;
				continue;
			}

			$unmatched[] = [
				'text' => $text,
				'entityType' => (string)($entity['entityType'] ?? 'UNKNOWN'),
			];
		}//end foreach

		return [
			'replacementsAttempted' => $attempted,
			'replacementsApplied' => $applied,
			'replacementsVerified' => true,
			'unmatchedEntities' => $unmatched,
		];

	}//end verify()

	/**
	 * Log the run's replacement statistics, warning on a discrepancy.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param array<string, mixed> $verification The verify() outcome.
	 * @param int $residualCount Number of residual entities (PII-free: count only).
	 *
	 * @return void
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function logStats(int $fileId, array $verification, int $residualCount): void {
		$this->logger->info(
			'Document anonymized',
			[
				'fileId' => $fileId,
				'replacementsAttempted' => $verification['replacementsAttempted'],
				'replacementsApplied' => $verification['replacementsApplied'],
				'replacementsVerified' => $verification['replacementsVerified'],
				'unmatchedCount' => count($verification['unmatchedEntities']),
				// PII-free: count only, never the residual text.
				'residualCount' => $residualCount,
			]
		);

		if ($verification['replacementsVerified'] === true
			&& $verification['replacementsAttempted'] !== $verification['replacementsApplied']
		) {
			$this->logger->warning(
				'Anonymization replacement-count discrepancy: not all sent entities were '
				. 'found literally in the source text',
				[
					'fileId' => $fileId,
					'replacementsAttempted' => $verification['replacementsAttempted'],
					'replacementsApplied' => $verification['replacementsApplied'],
					'unmatchedEntities' => $verification['unmatchedEntities'],
				]
			);
		}

	}//end logStats()
}//end class
