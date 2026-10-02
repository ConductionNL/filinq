<?php

/**
 * Signing Mandate Service
 *
 * Holds the per-type mandate declarations a consuming app makes about its own
 * records, and answers the one question the signing folder and the signing
 * path both ask: may this person sign a document of this record type.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use JsonException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use RuntimeException;

/**
 * Stores and applies the mandate a consuming app declares per record type.
 *
 * ADR-023: whether this person may sign this type of document is an action
 * check. The consuming app declares the rule against the type reference it
 * already sends on the signing request (`sourceApp` + `subjectSchema`), and
 * filinq applies it. Filinq invents no rule of its own: where a type carries
 * no declaration, every pending signer record stands, which is what the
 * folder showed before any of this existed.
 *
 * The declarations live in app config rather than in a register. They are
 * instance configuration written by an administrator, not records, and the
 * signingRequest schema is deprecated in favour of OR task sequences, so a
 * new schema here would be a write path nobody wants to migrate twice.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
 */
class SigningMandateService {

	/**
	 * App config key holding the declarations, as a JSON object keyed by type reference.
	 *
	 * @var string
	 */
	private const CONFIG_KEY = 'signingMandates';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $config App config, holding the declarations.
	 * @param IGroupManager $groupManager Resolves the groups a mandate names.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $config,
		private readonly IGroupManager $groupManager,
	) {

	}//end __construct()

	/**
	 * The type reference a signing request carries, or '' when it carries none.
	 *
	 * The reference is the consuming app plus the schema of the record the
	 * document belongs to. It is a reference to that app's type, never a copy
	 * of the record.
	 *
	 * @param array<string, mixed> $request The signing request.
	 *
	 * @return string `<app>/<schema>`, or '' when either half is missing.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function typeReference(array $request): string {
		$app = trim((string)($request['sourceApp'] ?? ''));
		$schema = trim((string)($request['subjectSchema'] ?? ''));

		if ($app === '' || $schema === '') {
			return '';
		}

		return $app . '/' . $schema;

	}//end typeReference()

	/**
	 * Every declaration on this instance, keyed by type reference.
	 *
	 * @return array<string, array{groups: list<string>, rule: string}>
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function declarations(): array {
		$raw = $this->config->getValueString('filinq', self::CONFIG_KEY, '');
		if ($raw === '') {
			return [];
		}

		try {
			$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			// A declaration store nobody can read is not a reason to let
			// everything through, but it is also not a rule: report it.
			throw new RuntimeException('Signing mandate declarations could not be read');
		}

		if (is_array($decoded) === false) {
			throw new RuntimeException('Signing mandate declarations could not be read');
		}

		return $decoded;

	}//end declarations()

	/**
	 * The declaration for one type reference, or null when the type carries none.
	 *
	 * @param string $typeReference The `<app>/<schema>` reference.
	 *
	 * @return array{groups: list<string>, rule: string}|null
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function declarationFor(string $typeReference): ?array {
		if ($typeReference === '') {
			return null;
		}

		$declarations = $this->declarations();

		return ($declarations[$typeReference] ?? null);

	}//end declarationFor()

	/**
	 * Record a consuming app's mandate for one of its record types.
	 *
	 * @param string $typeReference The `<app>/<schema>` reference the rule binds to.
	 * @param array<int, string> $groups The groups whose members hold the mandate.
	 * @param string $rule The rule in the consuming app's own words, quoted back on a refusal.
	 *
	 * @return array{groups: list<string>, rule: string} The stored declaration.
	 *
	 * @throws InvalidArgumentException When the reference, the groups or the rule is empty.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function declareMandate(string $typeReference, array $groups, string $rule): array {
		$typeReference = trim($typeReference);
		if (preg_match('#^[a-z0-9_-]+/[A-Za-z0-9_-]+$#', $typeReference) !== 1) {
			throw new InvalidArgumentException('A mandate binds to an <app>/<schema> type reference');
		}

		$cleanGroups = [];
		foreach ($groups as $group) {
			$group = trim((string)$group);
			if ($group !== '') {
				$cleanGroups[] = $group;
			}
		}

		if ($cleanGroups === []) {
			// An empty group list would read as "nobody signs this type",
			// which is indistinguishable from a typo. Refuse it, so a
			// mandate can never silently empty a folder.
			throw new InvalidArgumentException('A mandate names at least one group');
		}

		$rule = trim($rule);
		if ($rule === '') {
			throw new InvalidArgumentException('A mandate says in words which rule it is');
		}

		$declaration = ['groups' => array_values(array_unique($cleanGroups)), 'rule' => $rule];

		$declarations = $this->declarations();
		$declarations[$typeReference] = $declaration;
		$this->store(declarations: $declarations);

		return $declaration;

	}//end declareMandate()

	/**
	 * Withdraw the declaration for one type reference.
	 *
	 * @param string $typeReference The `<app>/<schema>` reference.
	 *
	 * @return bool True when a declaration was withdrawn, false when there was none.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function withdrawMandate(string $typeReference): bool {
		$declarations = $this->declarations();
		if (array_key_exists($typeReference, $declarations) === false) {
			return false;
		}

		unset($declarations[$typeReference]);
		$this->store(declarations: $declarations);

		return true;

	}//end withdrawMandate()

	/**
	 * May this user sign this request's document.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $userId The Nextcloud user asking.
	 *
	 * @return bool True when no declaration restricts the type, or the user holds the mandate.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function maySign(array $request, string $userId): bool {
		$declaration = $this->declarationFor(
			typeReference: $this->typeReference(request: $request)
		);
		if ($declaration === null) {
			return true;
		}

		if ($userId === '') {
			return false;
		}

		foreach (($declaration['groups'] ?? []) as $group) {
			if ($this->groupManager->isInGroup($userId, (string)$group) === true) {
				return true;
			}
		}

		return false;

	}//end maySign()

	/**
	 * Refuse a signature outside the mandate, naming the rule that refused it.
	 *
	 * @param array<string, mixed> $request The signing request.
	 * @param string $userId The Nextcloud user asking.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the user is outside the declared mandate.
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	public function assertMaySign(array $request, string $userId): void {
		if ($this->maySign(request: $request, userId: $userId) === true) {
			return;
		}

		$typeReference = $this->typeReference(request: $request);
		$declaration = $this->declarationFor(typeReference: $typeReference);
		$rule = (string)($declaration['rule'] ?? '');

		// A refusal that says only "not allowed" sends the signer to an
		// administrator with nothing to go on. Name the type, the rule and
		// the groups that hold it.
		throw new RuntimeException(
			'Signing refused by the mandate declared for ' . $typeReference . ': ' . $rule
			. ' (held by: ' . implode(', ', ($declaration['groups'] ?? [])) . ')'
		);

	}//end assertMaySign()

	/**
	 * Write the declarations back.
	 *
	 * @param array<string, mixed> $declarations The full declaration set.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-folder-across-cases/specs/document-signing/spec.md
	 */
	private function store(array $declarations): void {
		$this->config->setValueString('filinq', self::CONFIG_KEY, json_encode($declarations));

	}//end store()
}//end class
