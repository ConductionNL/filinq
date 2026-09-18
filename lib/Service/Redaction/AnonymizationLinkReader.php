<?php

/**
 * Which redacted copy belongs to which original.
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

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads `anonymizationLink` rows, which pair a source file with its copy.
 */
class AnonymizationLinkReader {

	/**
	 * The schema these rows live in.
	 *
	 * @var string
	 */
	public const SCHEMA = 'anonymizationLink';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The link for one source file, or null when it has no redacted copy.
	 *
	 * 🔴 A READ THAT FAILS RETURNS NULL, WHICH READS AS "NOT REDACTED YET" AND
	 * STOPS THE LIST. The other direction would compose a list over records
	 * whose redaction state nobody could confirm.
	 *
	 * @param int $sourceFileId The original's file id.
	 *
	 * @return array<string, mixed>|null The link.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function forSource(int $sourceFileId): ?array {
		if ($sourceFileId <= 0) {
			return null;
		}

		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => IntakeRepository::REGISTER,
						'schema' => self::SCHEMA,
					],
					'sourceFileId' => $sourceFileId,
				]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[AnonymizationLinkReader] could not read the link, so the record counts as not ready',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'sourceFileId' => $sourceFileId,
					'error' => $e->getMessage(),
				]
			);

			return null;
		}

		if (is_array($results) === false || $results === []) {
			return null;
		}

		return $this->normalise(row: $results[0]);

	}//end forSource()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The link.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		if (isset($data['object']) === true && is_array($data['object']) === true) {
			return $data['object'];
		}

		return $data;

	}//end normalise()
}//end class
