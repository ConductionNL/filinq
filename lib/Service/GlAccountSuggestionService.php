<?php

/**
 * GL Account Suggestion Service
 *
 * Orchestrates the "which grootboekrekening should this be booked to"
 * pipeline on top of an existing `financialExtraction`: resolves the
 * supplier identity, ranks candidate GL accounts by windowed booking
 * frequency (the deterministic, zero-AI baseline), falls back to
 * admin-edited keyword/category rules when no history exists, optionally
 * re-ranks through the local Nextcloud Assistant provider (absent-safe),
 * and records booking corrections as history for future suggestions.
 *
 * Filinq never hardcodes a chart of accounts — every account code/label is
 * an opaque string supplied by the consumer (via a correction, a
 * `candidateAccounts` entry, or an admin-authored mapping rule).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Event\GlAccountSuggestedEvent;
use OCA\Filinq\Service\Suggestion\CategoryKeywordMapper;
use OCA\Filinq\Service\Suggestion\HistoryRanker;
use OCA\Filinq\Service\Suggestion\SupplierIdentityResolver;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\TaskProcessing\Task as TaskProcessingTask;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\Task as TextProcessingTask;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Orchestrates GL-account suggestion for a financial extraction.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
 */
class GlAccountSuggestionService {
	/**
	 * Constructor.
	 *
	 * @param SettingsService $settingsService Resolves OpenRegister's ObjectService.
	 * @param OpenRegisterResolver $registerResolver Resolves register/schema bindings, failing closed.
	 * @param IEventDispatcher $eventDispatcher Dispatches the sibling suggestion event.
	 * @param ContainerInterface $container DI container, for the optional AI provider.
	 * @param LoggerInterface $logger Logger.
	 * @param SupplierIdentityResolver $identityResolver Pure supplier-identity resolver.
	 * @param HistoryRanker $historyRanker Pure windowed-frequency ranker.
	 * @param CategoryKeywordMapper $keywordMapper Pure cold-start keyword matcher.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly OpenRegisterResolver $registerResolver,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
		private readonly SupplierIdentityResolver $identityResolver,
		private readonly HistoryRanker $historyRanker,
		private readonly CategoryKeywordMapper $keywordMapper,
	) {

	}//end __construct()

	/**
	 * Compute a GL-account suggestion for a prior financial extraction and
	 * dispatch the sibling `nl.conduction.filinq.gl-account.suggested`
	 * event (REQ-GLS-06).
	 *
	 * @param string $extractionId The `financialExtraction` object id.
	 * @param array<int, array<string, mixed>> $candidateAccounts Optional consumer-supplied allow-list
	 *                                                            (each `{code, label?}`).
	 * @param string $sourceApp Requesting app id.
	 * @param string $requestedBy Nextcloud user id that requested the
	 *                            suggestion.
	 *
	 * @return array<string, mixed> `{extractionId, supplierIdentity, identityType, suggestedAccounts, source}`.
	 *
	 * @throws RuntimeException (code 404) When no extraction exists for the given id.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function suggest(string $extractionId, array $candidateAccounts, string $sourceApp, string $requestedBy): array {
		$extraction = $this->loadExtraction(extractionId: $extractionId);
		$fields = (array)($extraction['fields'] ?? []);

		$resolved = $this->identityResolver->resolve(fields: $fields);
		[$suggestions, $source] = $this->computeSuggestions(
			resolved: $resolved,
			fields: $fields,
			candidateAccounts: $candidateAccounts
		);

		$result = [
			'extractionId' => $extractionId,
			'supplierIdentity' => ($resolved['identity'] ?? null),
			'identityType' => ($resolved['identityType'] ?? null),
			'suggestedAccounts' => $suggestions,
			'source' => $source,
		];

		$this->dispatchSuggestedEvent(extractionId: $extractionId, result: $result, sourceApp: $sourceApp, requestedBy: $requestedBy);

		return $result;
	}//end suggest()

	/**
	 * Record a human-chosen GL account as booking history for future
	 * suggestions (REQ-GLS-05). No-op when the extraction has no resolvable
	 * supplier identity — there is no key to group the booking by.
	 *
	 * @param string $extractionId The `financialExtraction` object id.
	 * @param string $accountCode Opaque account code supplied by the consumer.
	 * @param string|null $accountLabel Optional opaque account label.
	 * @param string $correctedBy Nextcloud user id submitting the correction (unused for
	 *                            storage today, reserved for future per-user attribution).
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) $correctedBy reserved for future attribution
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	public function recordBooking(string $extractionId, string $accountCode, ?string $accountLabel, string $correctedBy): void {
		$extraction = $this->findExtractionSafely(extractionId: $extractionId);
		if ($extraction === null) {
			return;
		}

		$fields = (array)($extraction['fields'] ?? []);
		$resolved = $this->identityResolver->resolve(fields: $fields);
		if ($resolved === null) {
			return;
		}

		$booking = [
			'supplierIdentity' => $resolved['identity'],
			'identityType' => $resolved['identityType'],
			'accountCode' => $accountCode,
			'accountLabel' => $accountLabel,
			'bookedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'source' => 'correction',
			'extractionId' => $extractionId,
			'sourceApp' => (string)($extraction['sourceApp'] ?? ''),
		];

		$objectService = $this->settingsService->getObjectService();
		// Fails closed on the WRITE. This is the tuning corpus every future
		// suggestion is ranked against; writing it to register '' loses the
		// correction silently, and the next suggestion is then wrong for a
		// reason nobody can see.
		['register' => $register, 'schema' => $schema] = $this->registerResolver->getGlAccountBookingRegisterAndSchema();
		$objectService->saveObject(object: $booking, register: $register, schema: $schema);

	}//end recordBooking()

	/**
	 * Compute the deterministic (history-first, keyword-fallback) suggestion
	 * set, then apply the optional AI re-rank.
	 *
	 * @param array<string, mixed>|null $resolved Resolved supplier identity
	 *                                            (`{identity,
	 *                                            identityType}`), or null.
	 * @param array<string, mixed> $fields The extraction's `fields` map.
	 * @param array<int, array<string, mixed>> $candidateAccounts Optional consumer allow-list.
	 *
	 * @return array{0: array<int, array<string, mixed>>, 1: string} `[suggestions, source]`.
	 */
	private function computeSuggestions(?array $resolved, array $fields, array $candidateAccounts): array {
		if ($resolved === null) {
			return [[], 'none'];
		}

		$candidateCodes = $this->candidateCodes(candidateAccounts: $candidateAccounts);
		$bookings = $this->loadBookingHistory(supplierIdentity: $resolved['identity']);
		$ranked = $this->historyRanker->rank(bookings: $bookings, candidateCodes: $candidateCodes);

		if ($ranked !== []) {
			return [$this->applyAiReRank(suggestions: $ranked, fields: $fields), 'history'];
		}

		$rules = $this->loadMappingRules();
		$matchText = trim(((string)($fields['supplierName'] ?? '')) . ' ' . $resolved['identity']);
		$match = $this->keywordMapper->match(text: $matchText, rules: $rules);

		if ($match !== null) {
			return [$this->applyAiReRank(suggestions: [$match], fields: $fields), 'keyword-rule'];
		}

		return [[], 'none'];
	}//end computeSuggestions()

	/**
	 * Extract the candidate account codes from an optional consumer-supplied
	 * allow-list.
	 *
	 * @param array<int, array<string, mixed>> $candidateAccounts Optional allow-list (each `{code, label?}`).
	 *
	 * @return array<int, string> The candidate codes, or an empty array when none supplied.
	 */
	private function candidateCodes(array $candidateAccounts): array {
		$codes = [];
		foreach ($candidateAccounts as $candidate) {
			$code = trim((string)($candidate['code'] ?? ''));
			if ($code !== '') {
				$codes[] = $code;
			}
		}

		return $codes;
	}//end candidateCodes()

	/**
	 * Load a `financialExtraction` object by id, throwing a 404 when absent.
	 *
	 * @param string $extractionId The extraction id.
	 *
	 * @return array<string, mixed> The extraction object as an array.
	 *
	 * @throws RuntimeException (code 404) When no extraction exists.
	 */
	private function loadExtraction(string $extractionId): array {
		$extraction = $this->findExtractionSafely(extractionId: $extractionId);
		if ($extraction === null) {
			throw new RuntimeException('Financial extraction not found: ' . $extractionId, 404);
		}

		return $extraction;
	}//end loadExtraction()

	/**
	 * Load a `financialExtraction` object by id without throwing.
	 *
	 * @param string $extractionId The extraction id.
	 *
	 * @return array<string, mixed>|null The extraction object as an array, or null when absent.
	 */
	private function findExtractionSafely(string $extractionId): ?array {
		$objectService = $this->settingsService->getObjectService();
		// Throws when unbound rather than returning null. A lookup against
		// register '' finds nothing, which is INDISTINGUISHABLE from "that
		// extraction does not exist" — the caller then reports an unknown
		// extraction id when the real cause is an unconfigured instance.
		// Both controllers catch Exception, so this surfaces as an honest
		// error response instead of a wrong answer.
		['register' => $register, 'schema' => $schema] = $this->registerResolver->getFinancialExtractionRegisterAndSchema();

		$object = $objectService->find(id: $extractionId, register: $register, schema: $schema);
		if ($object === null) {
			return null;
		}

		return $this->toArray(object: $object);
	}//end findExtractionSafely()

	/**
	 * Load `glAccountBooking` history rows for a resolved supplier identity.
	 *
	 * @param string $supplierIdentity The resolved supplier identity.
	 *
	 * @return array<int, array<string, mixed>> The booking rows (each `{accountCode, accountLabel, bookedAt}`).
	 */
	private function loadBookingHistory(string $supplierIdentity): array {
		$objectService = $this->settingsService->getObjectService();
		// Throws when unbound. "No booking history" and "not configured" both
		// produced an empty array, and the ranker treats the first as a
		// legitimate cold start — so an unconfigured instance silently ranked
		// every supplier as brand new.
		['register' => $register, 'schema' => $schema] = $this->registerResolver->getGlAccountBookingRegisterAndSchema();

		$query = [
			'@self' => ['register' => $register, 'schema' => $schema],
			'supplierIdentity' => $supplierIdentity,
		];
		$results = $objectService->searchObjects($query);

		$bookings = [];
		foreach ((array)$results as $result) {
			$row = $this->toArray(object: $result);
			$bookings[] = [
				'accountCode' => (string)($row['accountCode'] ?? ''),
				'accountLabel' => ($row['accountLabel'] ?? null),
				'bookedAt' => (string)($row['bookedAt'] ?? ''),
			];
		}

		return $bookings;
	}//end loadBookingHistory()

	/**
	 * Load all `glAccountMappingRule` rows for the tenant.
	 *
	 * @return array<int, array<string, mixed>> The rule rows (each `{keywords[], accountCode,
	 *                                          accountLabel, priority, enabled}`).
	 */
	private function loadMappingRules(): array {
		$objectService = $this->settingsService->getObjectService();
		// Throws when unbound. Mapping rules are the cold-start path: with no
		// history AND no rules the service honestly returns nothing. An
		// unconfigured binding produced that same nothing for a different
		// reason, and the operator had no way to tell the two apart.
		['register' => $register, 'schema' => $schema] = $this->registerResolver->getGlAccountMappingRuleRegisterAndSchema();

		$query = ['@self' => ['register' => $register, 'schema' => $schema]];
		$results = $objectService->searchObjects($query);

		$rules = [];
		foreach ((array)$results as $result) {
			$row = $this->toArray(object: $result);
			$rules[] = [
				'keywords' => (array)($row['keywords'] ?? []),
				'accountCode' => (string)($row['accountCode'] ?? ''),
				'accountLabel' => ($row['accountLabel'] ?? null),
				'priority' => (int)($row['priority'] ?? 0),
				'enabled' => (bool)($row['enabled'] ?? true),
			];
		}

		return $rules;
	}//end loadMappingRules()

	/**
	 * Apply an optional AI-backend re-rank pass to the deterministic
	 * candidate set (REQ-GLS-04). Absent-safe: returns the input unchanged
	 * when no provider is available, the AI call fails, or the response
	 * cannot be safely applied. Never introduces a code outside the input
	 * candidate set.
	 *
	 * @param array<int, array<string, mixed>> $suggestions Deterministic candidates.
	 * @param array<string, mixed> $fields Extraction fields (prompt context).
	 *
	 * @return array<int, array<string, mixed>> The (possibly reordered) candidates.
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	private function applyAiReRank(array $suggestions, array $fields): array {
		if ($suggestions === []) {
			return $suggestions;
		}

		$manager = $this->resolveAiManager();
		if ($manager === null) {
			return $suggestions;
		}

		try {
			$raw = $this->runAiTask(manager: $manager, suggestions: $suggestions, fields: $fields);
			$decoded = json_decode($this->stripCodeFences(text: $raw), associative: true);
			if (is_array($decoded) === false || is_array($decoded['order'] ?? null) === false) {
				return $suggestions;
			}

			return $this->reorderByAi(suggestions: $suggestions, order: $decoded['order']);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: GL-account AI re-rank failed, returning deterministic result: ' . $e->getMessage()
			);
			return $suggestions;
		}//end try

	}//end applyAiReRank()

	/**
	 * Reorder the deterministic candidates according to an AI-supplied code
	 * order, ignoring any code not already present in the candidate set.
	 *
	 * @param array<int, array<string, mixed>> $suggestions Deterministic candidates.
	 * @param array<int, mixed> $order AI-supplied preferred code order.
	 *
	 * @return array<int, array<string, mixed>> The reordered candidates.
	 */
	private function reorderByAi(array $suggestions, array $order): array {
		$byCode = [];
		foreach ($suggestions as $suggestion) {
			$byCode[$suggestion['code']] = $suggestion;
		}

		$reordered = [];
		foreach ($order as $code) {
			$code = (string)$code;
			if (isset($byCode[$code]) === true) {
				$reordered[] = $byCode[$code];
				unset($byCode[$code]);
			}
		}

		// Append any candidates the AI response omitted, preserving their
		// original relative order — never drop a deterministic candidate.
		foreach ($suggestions as $suggestion) {
			if (isset($byCode[$suggestion['code']]) === true) {
				$reordered[] = $suggestion;
			}
		}

		return $reordered;
	}//end reorderByAi()

	/**
	 * Resolve the preferred available local AI text-processing manager.
	 *
	 * Mirrors FinancialExtractionService::resolveAiManager() exactly: prefers
	 * `OCP\TaskProcessing\IManager` (NC 30+), falls back to the deprecated
	 * `OCP\TextProcessing\IManager`. Both resolved lazily and guarded so this
	 * class loads cleanly when neither namespace exists.
	 *
	 * @return array{type: string, manager: object}|null Null when unavailable.
	 */
	private function resolveAiManager(): ?array {
		if (interface_exists('OCP\\TaskProcessing\\IManager') === true) {
			try {
				return [
					'type' => 'task',
					'manager' => $this->container->get('OCP\\TaskProcessing\\IManager'),
				];
			} catch (Throwable $e) {
				// Fall through to the legacy TextProcessing manager.
			}
		}

		if (interface_exists('OCP\\TextProcessing\\IManager') === true) {
			try {
				return [
					'type' => 'text',
					'manager' => $this->container->get('OCP\\TextProcessing\\IManager'),
				];
			} catch (Throwable $e) {
				return null;
			}
		}

		return null;
	}//end resolveAiManager()

	/**
	 * Run a single re-rank prompt through the resolved local AI manager and
	 * return its raw text output.
	 *
	 * @param array<string, mixed> $manager Resolved AI manager (`{type, manager}`).
	 * @param array<int, array<string, mixed>> $suggestions Deterministic candidates (prompt context).
	 * @param array<string, mixed> $fields Extraction fields (prompt context).
	 *
	 * @return string Raw model output.
	 */
	private function runAiTask(array $manager, array $suggestions, array $fields): string {
		$prompt = $this->buildPrompt(suggestions: $suggestions, fields: $fields);

		if ($manager['type'] === 'task') {
			$task = new TaskProcessingTask(TextToText::ID, ['input' => $prompt], 'filinq', null);

			$completed = $manager['manager']->runTask($task);
			$output = $completed->getOutput();
			return (string)($output['output'] ?? '');
		}

		$task = new TextProcessingTask(FreePromptTaskType::class, $prompt, 'filinq', null);

		return (string)$manager['manager']->runTask($task);
	}//end runAiTask()

	/**
	 * Build the re-rank prompt for the AI enhancement step.
	 *
	 * @param array<int, array<string, mixed>> $suggestions Deterministic candidates.
	 * @param array<string, mixed> $fields Extraction fields.
	 *
	 * @return string The prompt.
	 */
	private function buildPrompt(array $suggestions, array $fields): string {
		$supplierName = (string)($fields['supplierName'] ?? 'unknown supplier');
		$codes = array_map(
			static function (array $suggestion): string {
				$label = ($suggestion['label'] ?? null);
				if ($label !== null && $label !== '') {
					return $suggestion['code'] . ' (' . $label . ')';
				}

				return $suggestion['code'];
			},
			$suggestions
		);

		return 'Given a Dutch/English financial document from supplier "' . $supplierName . '", rank these '
			. 'candidate bookkeeping GL account codes in order of best fit: ' . implode(', ', $codes) . '. '
			. 'Return ONLY a strict JSON object (no markdown, no commentary) of the exact shape '
			. '{"order": ["code1", "code2", ...]} using only the codes given, best first. Do not invent '
			. 'new codes.';

	}//end buildPrompt()

	/**
	 * Strip ```json ... ``` / ``` ... ``` code fences from a model response, if present.
	 *
	 * @param string $text Raw model output.
	 *
	 * @return string
	 */
	private function stripCodeFences(string $text): string {
		$trimmed = trim($text);
		if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $trimmed, $matches) === 1) {
			return trim($matches[1]);
		}

		return $trimmed;
	}//end stripCodeFences()

	/**
	 * Dispatch the sibling `nl.conduction.filinq.gl-account.suggested`
	 * event. Fail-soft: the already-computed result is still returned to the
	 * caller even if event dispatch fails (mirrors
	 * FinancialExtractionService::dispatchCompletionEvent()).
	 *
	 * @param string $extractionId The extraction id.
	 * @param array<string, mixed> $result The computed suggestion result.
	 * @param string $sourceApp Requesting app id.
	 * @param string $requestedBy Requesting user id.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/ai-gl-account-suggestion/spec.md
	 */
	private function dispatchSuggestedEvent(string $extractionId, array $result, string $sourceApp, string $requestedBy): void {
		try {
			$event = new GlAccountSuggestedEvent(
				extractionId: $extractionId,
				supplierIdentity: $result['supplierIdentity'],
				identityType: $result['identityType'],
				suggestedAccounts: $result['suggestedAccounts'],
				source: $result['source'],
				sourceApp: $sourceApp,
				requestedBy: $requestedBy,
			);

			$this->eventDispatcher->dispatchTyped($event);
		} catch (Throwable $e) {
			$this->logger->error(
				'Filinq: GL-account suggestion computed but event dispatch failed: ' . $e->getMessage(),
				['exception' => $e]
			);
		}

	}//end dispatchSuggestedEvent()

	/**
	 * Normalise an ObjectService result to an array (mirrors
	 * FinancialExtractionService::toArray()).
	 *
	 * @param mixed $object The ObjectEntity (or array) to normalise.
	 *
	 * @return array<string, mixed> The serialized object.
	 */
	private function toArray(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return $object->jsonSerialize();
		}

		return (array)$object;
	}//end toArray()
}//end class
