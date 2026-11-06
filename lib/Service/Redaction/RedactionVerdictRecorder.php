<?php

/**
 * The verifier's verdict, written where a published copy can be asked about it.
 *
 * 🔴 A VERIFICATION NOBODY RECORDED CANNOT BE PRODUCED WHEN IT IS ASKED FOR.
 * The verifier reads the produced bytes and answers clean, leaking or
 * unverifiable, and until now that answer lived for the length of one request.
 * Six months later, when somebody asks whether the copy that was published was
 * ever checked, the only honest answer is "probably". So the verdict goes onto
 * the `anonymizationLink`, which is the row that already pairs the original
 * with the copy, and which is facetable.
 *
 * 🔴 IT IS RUN ON THE BYTES THAT WERE ACTUALLY WRITTEN, LAST. The grondslagen
 * summary appends a page after the redaction, and a PDF/A or PDF/UA rewrite
 * re-lays the text. Verifying before either of those would record a verdict
 * about a file that no longer exists, which is worse than recording none: it
 * carries the authority of a check.
 *
 * 🔴 A NODE IT CANNOT READ IS `unverifiable`, NEVER ABSENT. An empty field and
 * a clean verdict look the same to anything that filters on "was it checked",
 * and one of them means nobody looked.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Verifies the written copy and puts the verdict in the run result.
 */
class RedactionVerdictRecorder {

	/**
	 * Constructor.
	 *
	 * @param RedactionIrreversibilityVerifier $verifier The recovery routes.
	 * @param LoggerInterface                  $logger   Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly RedactionIrreversibilityVerifier $verifier,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Verify the produced copy and record the verdict on the run result.
	 *
	 * @param array<string, mixed> $resultInfo     The run result so far.
	 * @param mixed                $anonymisedNode The file as it was written.
	 * @param array<int, mixed>    $redactedValues The values that must not be recoverable.
	 * @param string               $outputMode     The mode that produced it.
	 *
	 * @return array<string, mixed> The result, carrying `redactionVerification`.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function record(
		array $resultInfo,
		mixed $anonymisedNode,
		array $redactedValues,
		string $outputMode = '',
	): array {
		$bytes = $this->bytesOf(node: $anonymisedNode);
		if ($bytes === null) {
			$resultInfo['redactionVerification'] = [
				'verdict' => RedactionIrreversibilityVerifier::UNVERIFIABLE,
				'outputMode' => $outputMode,
				'routesChecked' => [],
				'findings' => [],
				'mayBePublished' => false,
				'why' => 'the written copy could not be read back, so nothing was examined',
			];

			return $resultInfo;
		}

		$resultInfo['redactionVerification'] = $this->verifier->verify(
			bytes: $bytes,
			redactedValues: $this->valuesOf(entities: $redactedValues),
			outputMode: $outputMode
		);

		return $resultInfo;

	}//end record()

	/**
	 * The bytes of the written copy, or null when they cannot be read.
	 *
	 * @param mixed $node The file node.
	 *
	 * @return string|null The bytes.
	 *
	 * @spec exclude Read helper behind record().
	 */
	private function bytesOf(mixed $node): ?string {
		if (is_object($node) === false || method_exists(object_or_class: $node, method: 'getContent') === false) {
			return null;
		}

		try {
			$content = $node->getContent();
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[RedactionVerdictRecorder] the written copy could not be read back for verification',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (is_string($content) === false) {
			return null;
		}

		return $content;

	}//end bytesOf()

	/**
	 * The values a set of entities says must not be recoverable.
	 *
	 * @param array<int, mixed> $entities The mapped entities.
	 *
	 * @return array<int, string> The values.
	 *
	 * @spec exclude Reduction helper behind record().
	 */
	private function valuesOf(array $entities): array {
		$values = [];
		foreach ($entities as $entity) {
			if (is_string($entity) === true) {
				$values[] = $entity;
				continue;
			}

			if (is_array($entity) === false) {
				continue;
			}

			$value = (string)($entity['text'] ?? ($entity['value'] ?? ''));
			if (trim($value) !== '') {
				$values[] = $value;
			}
		}

		return array_values(array_unique($values));

	}//end valuesOf()
}//end class
