<?php

/**
 * Registry Enrichment Service
 *
 * Asks the base registries what the identifiers on an arriving document
 * belong to. A KvK number goes to the Handelsregister; a BSN goes to the BRP
 * only under the purpose the matching intake rule declares and only when an
 * admin switched BRP lookups on. The answer becomes a suggestion a registrar
 * accepts, corrects or dismisses; nothing is written onto a record here.
 *
 * The lookups are OpenRegister's providers, resolved from the container by
 * class name the way DocumentObjectServiceResolver resolves its ObjectService,
 * so filinq installs and runs without OpenRegister and simply says so.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Intake\RegistryAnswer;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns the KvK numbers and BSNs detected on a file into registry suggestions.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md
 */
class RegistryEnrichmentService {

	/**
	 * The admin setting that allows BRP lookups at all; off unless set to '1'.
	 *
	 * @var string
	 */
	public const BRP_SETTING = 'intake.brp_lookup_enabled';

	/**
	 * OpenRegister's Handelsregister provider.
	 *
	 * @var string
	 */
	public const KVK_PROVIDER = 'OCA\OpenRegister\Service\Integration\Providers\KvkProvider';

	/**
	 * OpenRegister's BRP provider.
	 *
	 * @var string
	 */
	public const BRP_PROVIDER = 'OCA\OpenRegister\Service\Integration\Providers\BrpPersonProvider';

	/**
	 * OpenRegister's BSN format, which carries the elfproef.
	 *
	 * @var string
	 */
	public const BSN_FORMAT = 'OCA\OpenRegister\Formats\BsnFormat';

	/**
	 * The note when the KvK provider cannot be reached at all.
	 *
	 * @var string
	 */
	public const NOTE_KVK_NOT_CONFIGURED = 'KvK lookup is not configured';

	/**
	 * The note when the KvK source answered that it is down or refused.
	 *
	 * @var string
	 */
	public const NOTE_KVK_UNAVAILABLE = 'KvK lookup is unavailable right now';

	/**
	 * The note when the Handelsregister knows no company under the number.
	 *
	 * @var string
	 */
	public const NOTE_KVK_UNKNOWN = 'The Handelsregister knows no company under a KvK number on this document';

	/**
	 * The note when the BRP provider or the elfproef cannot be reached.
	 *
	 * @var string
	 */
	public const NOTE_BRP_NOT_CONFIGURED = 'BRP lookup is not configured';

	/**
	 * The note when BRP lookups are on but the matching rule declares no purpose.
	 *
	 * @var string
	 */
	public const NOTE_BRP_NO_PURPOSE = 'BRP lookup needs a purpose on the matching intake rule';

	/**
	 * The note when a number read as a BSN fails the elfproef.
	 *
	 * @var string
	 */
	public const NOTE_BSN_INVALID = 'A number read as a BSN fails its check and was not looked up';

	/**
	 * The note when the BRP source answered that it is down or refused.
	 *
	 * @var string
	 */
	public const NOTE_BRP_UNAVAILABLE = 'BRP lookup is unavailable right now';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface      $container  The container the OpenRegister providers come from.
	 * @param IAppManager             $appManager Whether OpenRegister is installed.
	 * @param FileEntityStatsService  $entities   The identifiers entity detection found on the file.
	 * @param IntakeDefaultRuleService $rules     The rule the document was stamped with, for its purpose.
	 * @param IAppConfig              $config     The admin switch for BRP lookups.
	 * @param ITimeFactory            $clock      When the registry answered.
	 * @param LoggerInterface         $logger     Diagnostics; never receives a BSN.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly FileEntityStatsService $entities,
		private readonly IntakeDefaultRuleService $rules,
		private readonly IAppConfig $config,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The registry suggestions and the lookup note for one intake document.
	 *
	 * A lookup that cannot run adds a note saying why and touches nothing
	 * else; a document without detected identifiers gets neither.
	 *
	 * @param array<string, mixed> $document The stored intake document.
	 *
	 * @return array{registrySuggestion: list<array<string, mixed>>, registryLookupNote: string} What to set on it.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-lookup-that-cannot-run-says-why-req-ibr-003
	 */
	public function enrich(array $document): array {
		$identifiers = $this->identifiersOf(fileId: (int)($document['file'] ?? 0));

		$suggestions = [];
		$notes = [];
		foreach ($identifiers['kvk'] as $kvkNumber) {
			[$suggestion, $note] = $this->lookupCompany(kvkNumber: $kvkNumber);
			$this->collect(suggestions: $suggestions, notes: $notes, suggestion: $suggestion, note: $note);
		}

		if ($identifiers['bsn'] !== []) {
			$purpose = $this->purposeFor(document: $document);
			foreach ($identifiers['bsn'] as $bsn) {
				[$suggestion, $note] = $this->lookupPerson(bsn: $bsn, purpose: $purpose);
				$this->collect(suggestions: $suggestions, notes: $notes, suggestion: $suggestion, note: $note);
			}
		}

		return [
			'registrySuggestion' => $suggestions,
			'registryLookupNote' => implode('. ', array_keys($notes)),
		];

	}//end enrich()

	/**
	 * The KvK numbers and BSNs entity detection found on a file, each once.
	 *
	 * @param int $fileId The file.
	 *
	 * @return array{kvk: list<string>, bsn: list<string>} The identifiers, digits only.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-kvk-number-on-an-arrival-becomes-a-registry-suggestion-req-ibr-001
	 */
	public function identifiersOf(int $fileId): array {
		$found = ['kvk' => [], 'bsn' => []];
		if ($fileId <= 0) {
			return $found;
		}

		$mapper = $this->entities->tryGetEntityRelationMapper();
		if ($mapper === null) {
			return $found;
		}

		try {
			$relations = $mapper->findEntitiesForFile($fileId);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[RegistryEnrichmentService] could not read the entities of this file',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return $found;
		}

		foreach ($relations as $relation) {
			$type = '';
			$value = '';
			if (method_exists($relation, 'getType') === true) {
				$type = strtoupper((string)$relation->getType());
			}

			if (method_exists($relation, 'getValue') === true) {
				$value = (string)preg_replace('/\D/', '', (string)$relation->getValue());
			}

			if ($type === 'KVK' && strlen($value) === 8) {
				$found['kvk'][$value] = $value;
			}

			if ($type === 'BSN' && strlen($value) === 9) {
				$found['bsn'][$value] = $value;
			}
		}//end foreach

		return ['kvk' => array_values($found['kvk']), 'bsn' => array_values($found['bsn'])];

	}//end identifiersOf()

	/**
	 * Whether the admin switched BRP lookups on. Off by default.
	 *
	 * @return bool True when on.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-the-brp-is-asked-only-under-a-declared-purpose-req-ibr-002
	 */
	public function brpLookupEnabled(): bool {
		return $this->config->getValueString('filinq', self::BRP_SETTING, '0') === '1';

	}//end brpLookupEnabled()

	/**
	 * Mask a BSN down to its last three digits.
	 *
	 * @param string $bsn The BSN.
	 *
	 * @return string The masked form, such as `******782`.
	 *
	 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-the-brp-is-asked-only-under-a-declared-purpose-req-ibr-002
	 */
	public static function maskBsn(string $bsn): string {
		return str_repeat('*', max(0, (strlen($bsn) - 3))) . substr($bsn, -3);

	}//end maskBsn()

	/**
	 * Add one lookup's outcome to the collected suggestions and notes.
	 *
	 * @param list<array<string, mixed>> $suggestions The suggestions so far.
	 * @param array<string, true>        $notes       The notes so far, by text.
	 * @param array<string, mixed>|null  $suggestion  The lookup's suggestion, if any.
	 * @param string                     $note        The lookup's note, if any.
	 *
	 * @return void
	 */
	private function collect(array &$suggestions, array &$notes, ?array $suggestion, string $note): void {
		if ($suggestion !== null) {
			$suggestions[] = $suggestion;
		}

		if ($note !== '') {
			$notes[$note] = true;
		}

	}//end collect()

	/**
	 * Look one KvK number up in the Handelsregister.
	 *
	 * @param string $kvkNumber The 8-digit number.
	 *
	 * @return array{0: array<string, mixed>|null, 1: string} The suggestion or null, and a note or ''.
	 */
	private function lookupCompany(string $kvkNumber): array {
		$provider = $this->provider(className: self::KVK_PROVIDER);
		if ($provider === null || method_exists($provider, 'lookupByKvkNumber') === false) {
			return [null, self::NOTE_KVK_NOT_CONFIGURED];
		}

		try {
			$answer = $provider->lookupByKvkNumber($kvkNumber);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[RegistryEnrichmentService] the KvK lookup failed',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [null, self::NOTE_KVK_UNAVAILABLE];
		}

		if (is_array($answer) === false) {
			return [null, self::NOTE_KVK_UNAVAILABLE];
		}

		$unavailable = $this->unavailableNote(answer: $answer, notConfigured: self::NOTE_KVK_NOT_CONFIGURED, down: self::NOTE_KVK_UNAVAILABLE);
		if ($unavailable !== '') {
			return [null, $unavailable];
		}

		$row = $this->firstRow(answer: $answer);
		if ($row === null) {
			return [null, self::NOTE_KVK_UNKNOWN];
		}

		$fields = RegistryAnswer::company(row: $row);
		$fields['kvkNumber'] = $kvkNumber;

		return [$this->suggestion(source: 'kvk', identifier: $kvkNumber, fields: $fields), ''];

	}//end lookupCompany()

	/**
	 * Look one BSN up in the BRP, under every guard the BRP needs.
	 *
	 * The order is the guard: the switch, the purpose, the elfproef, and only
	 * then the query. The BSN appears in no note and no log line; the
	 * suggestion carries it masked.
	 *
	 * @param string $bsn     The 9-digit number.
	 * @param string $purpose The purpose the matching rule declares, or ''.
	 *
	 * @return array{0: array<string, mixed>|null, 1: string} The suggestion or null, and a note or ''.
	 */
	private function lookupPerson(string $bsn, string $purpose): array {
		if ($this->brpLookupEnabled() === false) {
			return [null, ''];
		}

		if ($purpose === '') {
			return [null, self::NOTE_BRP_NO_PURPOSE];
		}

		$format = $this->provider(className: self::BSN_FORMAT);
		$provider = $this->provider(className: self::BRP_PROVIDER);
		if ($format === null || method_exists($format, 'validate') === false
			|| $provider === null || method_exists($provider, 'lookupByBsn') === false
		) {
			return [null, self::NOTE_BRP_NOT_CONFIGURED];
		}

		if ($format->validate($bsn) !== true) {
			return [null, self::NOTE_BSN_INVALID];
		}

		try {
			$answer = $provider->lookupByBsn($bsn);
		} catch (Throwable $e) {
			// The message of a transport failure can echo the request: only the fact is logged.
			$this->logger->warning(
				message: '[RegistryEnrichmentService] the BRP lookup failed',
				context: ['file' => __FILE__, 'line' => __LINE__]
			);

			return [null, self::NOTE_BRP_UNAVAILABLE];
		}

		if (is_array($answer) === false) {
			return [null, self::NOTE_BRP_UNAVAILABLE];
		}

		$unavailable = $this->unavailableNote(answer: $answer, notConfigured: self::NOTE_BRP_NOT_CONFIGURED, down: self::NOTE_BRP_UNAVAILABLE);
		if ($unavailable !== '') {
			return [null, $unavailable];
		}

		$person = $this->firstRow(answer: $answer);
		if ($person === null) {
			return [null, ''];
		}

		$suggestion = $this->suggestion(source: 'brp', identifier: self::maskBsn(bsn: $bsn), fields: RegistryAnswer::person(person: $person));
		$suggestion['purpose'] = $purpose;

		return [$suggestion, ''];

	}//end lookupPerson()

	/**
	 * The purpose the document's stamped rule declares for a BRP lookup.
	 *
	 * @param array<string, mixed> $document The intake document.
	 *
	 * @return string The purpose, or '' when the rule declares none or is gone.
	 */
	private function purposeFor(array $document): string {
		$rule = $this->rules->find(uuid: (string)($document['defaultRule'] ?? ''));
		if ($rule === null || is_string($rule['brpPurpose'] ?? null) === false) {
			return '';
		}

		return trim($rule['brpPurpose']);

	}//end purposeFor()

	/**
	 * The note for a degraded provider answer, or '' when it answered.
	 *
	 * The providers degrade to `{unavailable, cause}`; a missing source is a
	 * configuration matter, everything else is the registry being down.
	 *
	 * @param array<string, mixed> $answer        The provider's answer.
	 * @param string               $notConfigured The note for a missing source.
	 * @param string               $down          The note for any other cause.
	 *
	 * @return string The note.
	 */
	private function unavailableNote(array $answer, string $notConfigured, string $down): string {
		if (($answer['unavailable'] ?? false) !== true) {
			return '';
		}

		if (($answer['cause'] ?? '') === 'openconnector-source-missing') {
			return $notConfigured;
		}

		return $down;

	}//end unavailableNote()

	/**
	 * The first result row of a provider answer.
	 *
	 * @param array<string, mixed> $answer The answer.
	 *
	 * @return array<string, mixed>|null The row, or null when there is none.
	 */
	private function firstRow(array $answer): ?array {
		$results = ($answer['results'] ?? []);
		if (is_array($results) === false || $results === []) {
			return null;
		}

		$row = reset($results);
		if (is_array($row) === false) {
			return null;
		}

		return $row;

	}//end firstRow()

	/**
	 * One registry suggestion, offered and not yet written.
	 *
	 * @param string               $source     `kvk` or `brp`.
	 * @param string               $identifier The number, masked for a BSN.
	 * @param array<string, mixed> $fields     Name, address and for KvK the number.
	 *
	 * @return array<string, mixed> The suggestion.
	 */
	private function suggestion(string $source, string $identifier, array $fields): array {
		return [
			'source' => $source,
			'identifier' => $identifier,
			'fields' => $fields,
			'retrievedAt' => $this->clock->getDateTime()->format(DATE_ATOM),
			'written' => false,
		];

	}//end suggestion()

	/**
	 * An OpenRegister class from the container, or null when it cannot be had.
	 *
	 * @param string $className The class.
	 *
	 * @return object|null The instance.
	 */
	private function provider(string $className): ?object {
		if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
			return null;
		}

		try {
			$instance = $this->container->get($className);
		} catch (Throwable $e) {
			$this->logger->info(
				message: '[RegistryEnrichmentService] an OpenRegister registry class is not available',
				context: ['file' => __FILE__, 'line' => __LINE__, 'class' => $className, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (is_object($instance) === false) {
			return null;
		}

		return $instance;

	}//end provider()
}//end class
