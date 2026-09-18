<?php

/**
 * Intake Default Rule Service
 *
 * Stamps an arriving document with the metadata somebody declared for its
 * channel or its sender, BEFORE any classifier has looked at it. Nothing
 * arrives unclassified, and every stamped value names the rule that chose it,
 * so a wrong value is traced to a rule rather than argued about.
 *
 * A classifier that disagrees later offers a suggestion beside the stamped
 * value. It never overwrites it: the rule is a decision somebody made, the
 * classification is a guess.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Finds the default rule that matches an arrival, and applies it.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */
class IntakeDefaultRuleService {

	/**
	 * The schema holding the rules.
	 *
	 * @var string
	 */
	public const SCHEMA = 'intakeDefaultRule';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The rule that stamps this arrival, or null when none matches.
	 *
	 * A rule with an empty channel matches every channel and a rule with an
	 * empty sender pattern matches every sender, so "everything from the
	 * scanner" and "everything from this supplier" are both one rule. When
	 * several match, the lowest `order` wins, which is the only reason `order`
	 * exists.
	 *
	 * @param string $channel The channel the document came through.
	 * @param string $sender The sender, as the channel reported it.
	 *
	 * @return array<string, mixed>|null The winning rule, or null.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function match(string $channel, string $sender): ?array {
		$candidates = [];
		foreach ($this->rules() as $rule) {
			if (($rule['active'] ?? true) === false) {
				continue;
			}

			$ruleChannel = trim((string)($rule['channel'] ?? ''));
			if ($ruleChannel !== '' && $ruleChannel !== $channel) {
				continue;
			}

			$pattern = trim((string)($rule['senderPattern'] ?? ''));
			if ($pattern !== '' && stripos($sender, $pattern) === false) {
				continue;
			}

			$candidates[] = $rule;
		}//end foreach

		if ($candidates === []) {
			return null;
		}

		usort(
			$candidates,
			static fn (array $left, array $right): int => ((int)($left['order'] ?? 100) <=> (int)($right['order'] ?? 100))
		);

		return $candidates[0];

	}//end match()

	/**
	 * Stamp one arriving document with the rule that matches it.
	 *
	 * A document nothing matches is stamped with nothing and SAYS SO: it
	 * carries an empty `stampedDefaults` and an empty `defaultRule`, rather
	 * than failing or silently looking like a document nobody has looked at.
	 *
	 * @param array<string, mixed> $document The document being created.
	 * @param string $channel The channel it came through.
	 * @param string $sender The sender.
	 *
	 * @return array<string, mixed> The document, with its stamp.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function stamp(array $document, string $channel, string $sender): array {
		$rule = $this->match(channel: $channel, sender: $sender);
		if ($rule === null) {
			$document['stampedDefaults'] = [];
			$document['defaultRule'] = '';

			return $document;
		}

		$stamps = [];
		if (isset($rule['stamps']) === true && is_array($rule['stamps']) === true) {
			$stamps = $rule['stamps'];
		}

		$document['stampedDefaults'] = $stamps;
		$document['defaultRule'] = (string)($rule['uuid'] ?? '');

		return $document;

	}//end stamp()

	/**
	 * Every rule in the register.
	 *
	 * @return array<int, array<string, mixed>> The rules, or an empty list when the register cannot be read.
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	private function rules(): array {
		try {
			$results = $this->objectResolver->resolve()->searchObjects(
				query: [
					'@self' => [
						'register' => IntakeRepository::REGISTER,
						'schema' => self::SCHEMA,
					],
				]
			);
		} catch (Throwable $e) {
			// An unreadable rule set stamps NOTHING rather than failing the
			// arrival: a document that never reached the inbox is worse than a
			// document that reached it unstamped, and the empty stamp says
			// which happened.
			$this->logger->warning(
				message: '[IntakeDefaultRuleService] could not read the default rules, stamping nothing',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$rules = [];
		foreach ($results as $result) {
			$data = $result;
			if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
				$data = $result->jsonSerialize();
			}

			if (is_array($data) === false) {
				continue;
			}

			$fields = $data;
			if (isset($data['object']) === true && is_array($data['object']) === true) {
				$fields = $data['object'];
			}

			$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));
			$rules[] = $fields;
		}

		return $rules;

	}//end rules()
}//end class
