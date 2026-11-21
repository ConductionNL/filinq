<?php

/**
 * Intake Registry Enrichment Job
 *
 * Asks the base registries about the identifiers on one arrived intake
 * document and stores the suggestions and the lookup note on it. Runs after
 * the arrival was saved, so a slow or failing registry never touches the
 * arrival itself.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Service\RegistryEnrichmentService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stores the registry suggestions of one intake document.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
 */
class IntakeRegistryEnrichmentJob extends QueuedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory              $clock      The job clock.
	 * @param IntakeRepositoryInterface $repository The intake document store.
	 * @param RegistryEnrichmentService $enrichment The registry lookups.
	 * @param LoggerInterface           $logger     Diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $clock,
		private readonly IntakeRepositoryInterface $repository,
		private readonly RegistryEnrichmentService $enrichment,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);

	}//end __construct()

	/**
	 * Look the document's identifiers up and store what came back.
	 *
	 * A document that is gone, or a lookup round that yields neither a
	 * suggestion nor a note, is left as it is.
	 *
	 * @param mixed $argument `{uuid}` of the intake document.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-lookup-that-cannot-run-says-why-req-ibr-003
	 */
	protected function run(mixed $argument): void {
		$uuid = '';
		if (is_array($argument) === true) {
			$uuid = (string)($argument['uuid'] ?? '');
		}

		$document = $this->repository->findByUuid(uuid: $uuid);
		if ($document === null) {
			$this->logger->warning('IntakeRegistryEnrichmentJob: the intake document is gone', ['uuid' => $uuid]);
			return;
		}

		try {
			$outcome = $this->enrichment->enrich(document: $document);
		} catch (Throwable $e) {
			$this->logger->warning('IntakeRegistryEnrichmentJob: the registry lookup failed', ['uuid' => $uuid, 'error' => get_class($e)]);
			return;
		}

		if ($outcome['registrySuggestion'] === [] && $outcome['registryLookupNote'] === '') {
			return;
		}

		$this->repository->save(document: array_merge($document, $outcome), uuid: $uuid);

	}//end run()
}//end class
