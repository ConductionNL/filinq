<?php

/**
 * EML Anonymization Service
 *
 * The EML replacement for the standard anonymizeDocument + convertToPdf path:
 * OpenRegister's `anonymizeDocument()` throws on `message/rfc822`, so EML
 * inputs are routed to OR's dedicated anonymise-EML API and assembled into a
 * PDF/A-3b written beside the source. Extracted verbatim from
 * AnonymizationService.
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

use OCA\Filinq\Exception\ConversionFailedException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Anonymises EML input through OpenRegister and assembles it into a PDF/A-3b.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class EmlAnonymizationService {
	/**
	 * Constructor for EmlAnonymizationService
	 *
	 * @param LoggerInterface $logger Logger for API-failure diagnostics.
	 * @param EntityDetectionService $entityDetection Parser for the anonymisation result payload.
	 * @param EmlPdfAssemblyService $emlAssembly Assembles OR's redacted EML result into a PDF/A-3b.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly EntityDetectionService $entityDetection,
		private readonly EmlPdfAssemblyService $emlAssembly,
	) {

	}//end __construct()

	/**
	 * Whether a file node is an EML (email) input.
	 *
	 * Detected by MIME `message/rfc822` or a `.eml` extension. EML inputs are
	 * routed to the dedicated anonymise-EML + assembly path.
	 *
	 * @param mixed $node The source file node.
	 *
	 * @return bool True when the node is an EML message.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function isEmlInput(mixed $node): bool {
		if (is_object($node) === false) {
			return false;
		}

		if (method_exists($node, 'getMimeType') === true
			&& (string)$node->getMimeType() === 'message/rfc822'
		) {
			return true;
		}

		if (method_exists($node, 'getName') === true) {
			$name = (string)$node->getName();
			$dot = strrpos($name, '.');
			if ($dot !== false && strtolower(substr($name, ($dot + 1))) === 'eml') {
				return true;
			}
		}

		return false;
	}//end isEmlInput()

	/**
	 * Anonymise an EML input and assemble the redacted result into a PDF/A-3b.
	 *
	 * EML always produces a PDF — `outputFormat: "preserve"` is silently
	 * overridden here (the caller is never told; design D8), because OR redacts
	 * components, not a re-serialised native `.eml`, so there is no native
	 * intermediate to keep. On OR API failure a `ConversionFailedException` is
	 * raised with NO raw-parse fallback (design D9), so the controller maps it
	 * to HTTP 422 and no un-redacted content is ever written.
	 *
	 * @param int $fileId Source Nextcloud file ID.
	 * @param mixed $node Source EML file node.
	 * @param mixed $fileService OR FileService (resolved reflectively).
	 * @param array<int, array<string,mixed>> $mappedEntities Entities to redact.
	 * @param string $scope Placeholder-numbering scope.
	 * @param string|null $dossierKey Stable dossier folder id, or null.
	 *
	 * @return array{resultInfo: array<string, mixed>, node: mixed,
	 *               placeholderMap: array<string, string>} The result info the controller expects,
	 *                                                      the written PDF node, and OR's per-entity
	 *                                                      placeholder map for the summary.
	 *
	 * @throws ConversionFailedException On OR API failure or assembly failure.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function anonymize(
		int $fileId,
		mixed $node,
		mixed $fileService,
		array $mappedEntities,
		string $scope,
		?string $dossierKey,
	): array {
		$structure = $this->requestStructure(
			fileId: $fileId,
			node: $node,
			fileService: $fileService,
			mappedEntities: $mappedEntities,
			scope: $scope,
			dossierKey: $dossierKey
		);

		$sourceName = '';
		if (method_exists($node, 'getName') === true) {
			$sourceName = (string)$node->getName();
		}

		// The assemble() call throws ConversionFailedException on unrecoverable
		// failure; let it propagate so the controller surfaces 422.
		$pdfBytes = $this->emlAssembly->assemble(result: $structure, sourceFilename: $sourceName);

		$parent = $node->getParent();
		// Fall back to a generic base name when the source node has no name.
		$safeName = $sourceName;
		if ($safeName === '') {
			$safeName = 'email';
		}

		$outputName = $this->stripExtension(name: $safeName) . '_anonymized.pdf';
		if ($parent->nodeExists($outputName) === true) {
			$parent->get($outputName)->delete();
		}

		$pdfNode = $parent->newFile($outputName, $pdfBytes);

		$this->logger->info(
			'EML anonymised and assembled to PDF',
			[
				'fileId' => $fileId,
				'entityCount' => count($mappedEntities),
			]
		);

		$placeholderMap = [];
		if (method_exists($fileService, 'getLastPlaceholderMap') === true) {
			$placeholderMap = $fileService->getLastPlaceholderMap();
		}

		return [
			'resultInfo' => $this->buildResultInfo(pdfNode: $pdfNode, mappedEntities: $mappedEntities),
			'node' => $pdfNode,
			'placeholderMap' => $placeholderMap,
		];

	}//end anonymize()

	/**
	 * Call OpenRegister's anonymise-EML API and validate its return value.
	 *
	 * @param int $fileId Source Nextcloud file ID.
	 * @param mixed $node Source EML file node.
	 * @param mixed $fileService OR FileService (resolved reflectively).
	 * @param array<int, array<string,mixed>> $mappedEntities Entities to redact.
	 * @param string $scope Placeholder-numbering scope.
	 * @param string|null $dossierKey Stable dossier folder id, or null.
	 *
	 * @return object The redacted EML structure.
	 *
	 * @throws ConversionFailedException When the API is absent, throws, or returns no structure.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function requestStructure(
		int $fileId,
		mixed $node,
		mixed $fileService,
		array $mappedEntities,
		string $scope,
		?string $dossierKey,
	): object {
		if (method_exists($fileService, 'anonymizeEmlStructured') === false) {
			throw new ConversionFailedException(
				message: 'OpenRegister does not expose the anonymise-EML API; cannot anonymise EML input.',
				attempts: [
					[
						'name' => 'eml',
						'available' => false,
						'supports' => true,
						'reason' => 'anonymizeEmlStructured not present on OpenRegister FileService',
					],
				]
			);
		}

		try {
			$structure = $fileService->anonymizeEmlStructured($node, $mappedEntities, $scope, $dossierKey);
		} catch (ConversionFailedException $e) {
			throw $e;
		} catch (Throwable $e) {
			// NO raw-parse fallback — leaking un-redacted EML is the worse
			// failure. Surface as a typed conversion failure (HTTP 422).
			$this->logger->warning(
				'EML anonymise-API failed; no raw-parse fallback.',
				['fileId' => $fileId, 'exception' => get_class($e), 'message' => $e->getMessage()]
			);
			throw new ConversionFailedException(
				message: 'OpenRegister anonymise-EML API failed: ' . $e->getMessage(),
				attempts: [
					[
						'name' => 'eml',
						'available' => true,
						'supports' => true,
						'reason' => 'anonymizeEmlStructured threw: ' . $e->getMessage(),
					],
				],
				previous: $e
			);
		}//end try

		if (is_object($structure) === false) {
			throw new ConversionFailedException(
				message: 'OpenRegister anonymise-EML API returned no structure.',
				attempts: [
					[
						'name' => 'eml',
						'available' => true,
						'supports' => true,
						'reason' => 'anonymizeEmlStructured returned non-object',
					],
				]
			);
		}

		return $structure;
	}//end requestStructure()

	/**
	 * Build the anonymise result payload for the assembled EML PDF.
	 *
	 * #286: do not fabricate replacementCount from count($mappedEntities).
	 * The EML output is an assembled binary PDF, so the replacement layer
	 * cannot verify how many mapped entities actually appeared in — and were
	 * therefore removed from — the source text (same limitation as any binary
	 * format). Surface the truth: how many were attempted, that none could be
	 * verified, and let the legacy replacementCount fall back to the attempted
	 * count with replacementsVerified=false telling callers it is unconfirmed.
	 * This preserves the #286 anti-fabrication fix on the EML path (issue #312).
	 *
	 * @param mixed $pdfNode The assembled PDF node.
	 * @param array<int, array<string,mixed>> $mappedEntities Entities forwarded to OpenRegister.
	 *
	 * @return array<string, mixed> The result info.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function buildResultInfo(mixed $pdfNode, array $mappedEntities): array {
		$resultInfo = $this->entityDetection->parseAnonymizationResult($pdfNode);

		$resultInfo['replacementsAttempted'] = count($mappedEntities);
		$resultInfo['replacementsApplied'] = null;
		$resultInfo['replacementsVerified'] = false;
		$resultInfo['unmatchedEntities'] = [];
		// The replacementsApplied value is null on the EML path (the assembled
		// binary PDF cannot confirm applied replacements), so the legacy
		// replacementCount deliberately falls back to the attempted count with
		// replacementsVerified=false marking it unconfirmed (#286/#312).
		$resultInfo['replacementCount'] = $resultInfo['replacementsAttempted'];
		// OR's anonymise-EML path does not surface a residual list; the
		// assembled PDF is the authoritative redacted output.
		$resultInfo['complete'] = true;
		$resultInfo['residualCount'] = 0;
		$resultInfo['residualEntities'] = [];

		return $resultInfo;
	}//end buildResultInfo()

	/**
	 * Return $name without its trailing `.ext`.
	 *
	 * @param string $name File name with extension.
	 *
	 * @return string Name without extension.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	private function stripExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return $name;
		}

		return substr($name, 0, $dotPos);
	}//end stripExtension()
}//end class
