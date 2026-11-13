<?php

/**
 * Detection Provider Resolver
 *
 * The single place the detection provider is chosen, and the single place the
 * LLM provider falls back.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Chooses an organisation's provider and runs it, falling back honestly.
 *
 * OpenRegister's own detection always runs during extraction, so the default
 * provider's entities are already in the catalogue when this runs. For an
 * organisation on `llm`, the LLM provider is run over every extracted chunk and
 * its entities are written only when every chunk succeeded. When it is
 * unavailable, throws, times out or answers malformed, nothing of it is
 * written and the result names `default`, says it fell back, and carries a
 * warning for the operator. A default result is never presented as an LLM one.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class DetectionProviderResolver {

	/**
	 * Constructor.
	 *
	 * @param DetectionProviderConfig  $config          The per-organisation choice.
	 * @param DefaultDetectionProvider $defaultProvider OpenRegister's detection.
	 * @param LlmDetectionProvider     $llmProvider     The hermiq agentflow.
	 * @param DetectionCatalogue       $catalogue       OpenRegister chunk reads and entity writes.
	 * @param IL10N                    $l10n            Translations for the warning.
	 * @param LoggerInterface          $logger          Logger.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.2
	 */
	public function __construct(
		private readonly DetectionProviderConfig $config,
		private readonly DefaultDetectionProvider $defaultProvider,
		private readonly LlmDetectionProvider $llmProvider,
		private readonly DetectionCatalogue $catalogue,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * The provider an organisation is on.
	 *
	 * @param string|null $organisationUuid The organisation, or null when none is active.
	 *
	 * @return DetectionProviderInterface The provider.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.2
	 */
	public function forOrganisation(?string $organisationUuid): DetectionProviderInterface {
		if ($this->config->providerFor(organisationUuid: $organisationUuid) === LlmDetectionProvider::ID) {
			return $this->llmProvider;
		}

		return $this->defaultProvider;
	}//end forOrganisation()

	/**
	 * Run the organisation's provider over a file that OpenRegister has extracted.
	 *
	 * @param string|null             $organisationUuid The organisation, or null when none is active.
	 * @param int                     $fileId           The file.
	 * @param array<int, string>|null $entityTypes      The enabled types, or null for all.
	 * @param bool                    $force            A re-analysis. Without it, a file that already
	 *                                                  has LLM relations keeps them, with the
	 *                                                  operator's decisions on them.
	 *
	 * @return DetectionResult What ran, and the entities the LLM provider wrote (empty for default or kept).
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.1
	 */
	public function run(?string $organisationUuid, int $fileId, ?array $entityTypes, bool $force=false): DetectionResult {
		$provider = $this->forOrganisation(organisationUuid: $organisationUuid);
		if ($provider->id() !== LlmDetectionProvider::ID) {
			return $this->defaultProvider->detect(fileId: $fileId, text: '', entityTypes: $entityTypes);
		}

		if ($force === false && $this->catalogue->hasLlmRelations(fileId: $fileId) === true) {
			// Resuming: the LLM already ran on this file. Rerunning would replace
			// its relations and lose the operator's skip and bases decisions.
			return new DetectionResult(entities: [], providerUsed: LlmDetectionProvider::ID);
		}

		if ($provider->isAvailable() === false) {
			return $this->fallBack(fileId: $fileId, entityTypes: $entityTypes, cause: 'unavailable');
		}

		try {
			$entities = $this->detectAcrossChunks(provider: $provider, fileId: $fileId, entityTypes: $entityTypes);
			$this->catalogue->replace(fileId: $fileId, entities: $entities);
		} catch (Throwable $e) {
			$this->logger->warning(
				'[DetectionProviderResolver] LLM detection failed; falling back to the default provider.',
				['fileId' => $fileId, 'exception' => $e]
			);
			return $this->fallBack(fileId: $fileId, entityTypes: $entityTypes, cause: $e->getMessage());
		}

		return new DetectionResult(entities: $entities, providerUsed: LlmDetectionProvider::ID);
	}//end run()

	/**
	 * Run the provider of the current user's active organisation.
	 *
	 * @param int                     $fileId      The file.
	 * @param array<int, string>|null $entityTypes The enabled types, or null for all.
	 * @param bool                    $force       A re-analysis.
	 *
	 * @return DetectionResult What ran.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
	 */
	public function runForActiveOrganisation(int $fileId, ?array $entityTypes, bool $force): DetectionResult {
		return $this->run(
			organisationUuid: $this->catalogue->activeOrganisationUuid(),
			fileId: $fileId,
			entityTypes: $entityTypes,
			force: $force
		);
	}//end runForActiveOrganisation()

	/**
	 * Detect over every chunk; positions become absolute and each entity keeps its chunk.
	 *
	 * @param DetectionProviderInterface $provider    The provider.
	 * @param int                        $fileId      The file.
	 * @param array<int, string>|null    $entityTypes The enabled types.
	 *
	 * @return list<array<string, mixed>> The entities, de-duplicated across overlapping chunks.
	 */
	private function detectAcrossChunks(DetectionProviderInterface $provider, int $fileId, ?array $entityTypes): array {
		$entities = [];
		foreach ($this->catalogue->chunks(fileId: $fileId) as $chunk) {
			$result = $provider->detect(fileId: $fileId, text: $chunk['text'], entityTypes: $entityTypes);
			foreach ($result->entities as $entity) {
				if ($entity['positionStart'] !== null) {
					$entity['positionStart'] += $chunk['startOffset'];
					$entity['positionEnd']   += $chunk['startOffset'];
				}

				$entity['chunkId'] = $chunk['id'];
				$key = implode("\0", [$entity['type'], $entity['value'], (string) $entity['positionStart'], (string) $entity['positionEnd']]);
				$entities[$key] ??= $entity;
			}
		}

		return array_values($entities);
	}//end detectAcrossChunks()

	/**
	 * The default provider's result, marked as a fallback with the operator warning.
	 *
	 * @param int                     $fileId      The file.
	 * @param array<int, string>|null $entityTypes The enabled types.
	 * @param string                  $cause       Why, for the log only.
	 *
	 * @return DetectionResult The fallback result.
	 */
	private function fallBack(int $fileId, ?array $entityTypes, string $cause): DetectionResult {
		$this->logger->info('[DetectionProviderResolver] Fell back to the default provider.', ['fileId' => $fileId, 'cause' => $cause]);
		$default = $this->defaultProvider->detect(fileId: $fileId, text: '', entityTypes: $entityTypes);

		return new DetectionResult(
			entities: $default->entities,
			providerUsed: $default->providerUsed,
			fellBack: true,
			warning: $this->l10n->t('LLM detection unavailable — fell back to the default detection; results may miss context-dependent entities.')
		);
	}//end fallBack()
}//end class
