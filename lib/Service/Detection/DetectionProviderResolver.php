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
	 * @param string|null      $organisationUuid The organisation, or null when none is active.
	 * @param DetectionRequest $request          The file, the enabled types and whether it is a
	 *                                           re-analysis. Without `force`, a file that already
	 *                                           has LLM relations keeps them, with the operator's
	 *                                           decisions on them.
	 *
	 * @return DetectionResult What ran, and the entities the LLM provider wrote (empty for default or kept).
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.1
	 */
	public function run(?string $organisationUuid, DetectionRequest $request): DetectionResult {
		$provider = $this->forOrganisation(organisationUuid: $organisationUuid);
		if ($provider->id() !== LlmDetectionProvider::ID) {
			return $this->defaultProvider->detect(request: $request, text: '');
		}

		if ($this->resumes(request: $request) === true) {
			// Resuming: the LLM already ran on this file. Rerunning would replace
			// its relations and lose the operator's skip and bases decisions.
			return new DetectionResult(entities: [], providerUsed: LlmDetectionProvider::ID);
		}

		if ($provider->isAvailable() === false) {
			return $this->fallBack(request: $request, cause: 'unavailable');
		}

		return $this->runLlm(provider: $provider, request: $request);
	}//end run()

	/**
	 * Whether this is a resumed run over a file the LLM already ran on.
	 *
	 * @param DetectionRequest $request The request.
	 *
	 * @return bool True when it is not a re-analysis and the file has LLM relations.
	 */
	private function resumes(DetectionRequest $request): bool {
		if ($request->force === true) {
			return false;
		}

		return $this->catalogue->hasLlmRelations(fileId: $request->fileId);
	}//end resumes()

	/**
	 * Run the LLM provider and store what it found, or fall back when it fails.
	 *
	 * @param DetectionProviderInterface $provider The LLM provider.
	 * @param DetectionRequest           $request  The file and the enabled types.
	 *
	 * @return DetectionResult The LLM's entities, or the fallback result.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.1
	 */
	private function runLlm(DetectionProviderInterface $provider, DetectionRequest $request): DetectionResult {
		try {
			$entities = $this->detectAcrossChunks(provider: $provider, request: $request);
			$this->catalogue->replace(fileId: $request->fileId, entities: $entities);
		} catch (Throwable $e) {
			$this->logger->warning(
				'[DetectionProviderResolver] LLM detection failed; falling back to the default provider.',
				['fileId' => $request->fileId, 'exception' => $e]
			);
			return $this->fallBack(request: $request, cause: $e->getMessage());
		}

		return new DetectionResult(entities: $entities, providerUsed: LlmDetectionProvider::ID);
	}//end runLlm()

	/**
	 * Run the provider of the current user's active organisation.
	 *
	 * @param DetectionRequest $request The file, the enabled types and whether it is a re-analysis.
	 *
	 * @return DetectionResult What ran.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
	 */
	public function runForActiveOrganisation(DetectionRequest $request): DetectionResult {
		return $this->run(organisationUuid: $this->catalogue->activeOrganisationUuid(), request: $request);
	}//end runForActiveOrganisation()

	/**
	 * Detect over every chunk; positions become absolute and each entity keeps its chunk.
	 *
	 * @param DetectionProviderInterface $provider The provider.
	 * @param DetectionRequest           $request  The file and the enabled types.
	 *
	 * @return list<array<string, mixed>> The entities, de-duplicated across overlapping chunks.
	 */
	private function detectAcrossChunks(DetectionProviderInterface $provider, DetectionRequest $request): array {
		$entities = [];
		foreach ($this->catalogue->chunks(fileId: $request->fileId) as $chunk) {
			foreach ($provider->detect(request: $request, text: $chunk['text'])->entities as $entity) {
				$entity = self::inChunk(entity: $entity, chunk: $chunk);
				$key    = implode("\0", [$entity['type'], $entity['value'], (string) $entity['positionStart'], (string) $entity['positionEnd']]);
				$entities[$key] ??= $entity;
			}
		}

		return array_values($entities);
	}//end detectAcrossChunks()

	/**
	 * An entity found in a chunk, with positions made absolute and the chunk it came from.
	 *
	 * @param array<string, mixed> $entity The entity, positions relative to the chunk.
	 * @param array<string, mixed> $chunk  The chunk: `id`, `startOffset`.
	 *
	 * @return array<string, mixed> The entity.
	 */
	private static function inChunk(array $entity, array $chunk): array {
		if ($entity['positionStart'] !== null) {
			$entity['positionStart'] += $chunk['startOffset'];
			$entity['positionEnd']   += $chunk['startOffset'];
		}

		$entity['chunkId'] = $chunk['id'];

		return $entity;
	}//end inChunk()

	/**
	 * The default provider's result, marked as a fallback with the operator warning.
	 *
	 * @param DetectionRequest $request The file and the enabled types.
	 * @param string           $cause   Why, for the log only.
	 *
	 * @return DetectionResult The fallback result.
	 */
	private function fallBack(DetectionRequest $request, string $cause): DetectionResult {
		$this->logger->info('[DetectionProviderResolver] Fell back to the default provider.', ['fileId' => $request->fileId, 'cause' => $cause]);
		$default = $this->defaultProvider->detect(request: $request, text: '');

		return new DetectionResult(
			entities: $default->entities,
			providerUsed: $default->providerUsed,
			fellBack: true,
			warning: $this->l10n->t('LLM detection unavailable — fell back to the default detection; results may miss context-dependent entities.')
		);
	}//end fallBack()
}//end class
