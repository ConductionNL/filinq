<?php

/**
 * The plain-language counterpart of a formal letter.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\PlainRenditionRefusedException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads a template's plain-language declaration and renders its counterpart.
 *
 * 🔴 THE FORMAL RENDITION IS THE LEGAL TEXT AND NOTHING HERE TOUCHES IT.
 * REQ-DIO-04 says so in as many words. Every method on this class produces or
 * refuses the PLAIN rendition; the formal one is passed in so it can be NAMED,
 * never so it can be changed.
 *
 * 🔴 NO COUNTERPART MEANS NO RENDITION, NOT A GENERATED ONE. A template that
 * declares nothing gets the formal letter only. Inventing plain text from the
 * formal text would put words the organisation never approved in front of
 * somebody who is being told what they may no longer do, and it would do it
 * silently, because a plain letter that reads fluently looks right.
 *
 * 🔴 A REQUIRED STATEMENT THAT CANNOT BE RESOLVED REFUSES THE WHOLE GENERATION,
 * BEFORE EITHER RENDITION IS FILED. The alternative is a plain letter with a
 * hole where the bezwaartermijn should be, which is worse than no plain letter:
 * the reader believes they have been told the term and they have not. So the
 * plan is made before the formal document is stored, and the refusal is a throw
 * rather than a warning on the outcome.
 *
 * 🔑 A CORRECTION TAKES THE TWIN WITH IT. Both renditions come out of ONE
 * generation, so regenerating the formal one regenerates the plain one by
 * construction rather than by anybody remembering. `stale()` exists for the
 * records written before that was true, and for any path that ever files one
 * without the other: a plain rendition older than the formal document it
 * explains reads as stale rather than as current.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */
class PlainLanguageRenditionService {

	/**
	 * The template key carrying the declaration.
	 *
	 * @var string
	 */
	public const DECLARATION_KEY = 'plainLanguage';

	/**
	 * Collaborators.
	 *
	 * @param TemplateService              $templates The template store.
	 * @param PlainRenditionAcceptanceGate $gate      Holds an unaccepted machine draft inside.
	 * @param LoggerInterface              $logger    Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateService $templates,
		private readonly PlainRenditionAcceptanceGate $gate,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The plain-language counterpart a template declares, or null.
	 *
	 * @param array<string, mixed> $template The template.
	 *
	 * @return array{templateId: string, requiredStatements: array<int, string>, source: string}|null The declaration.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function counterpartOf(array $template): ?array {
		$declared = ($template[self::DECLARATION_KEY] ?? null);
		if (is_array($declared) === false) {
			return null;
		}

		$templateId = trim((string)($declared['templateId'] ?? ''));
		if ($templateId === '') {
			// A declaration naming no template is not a counterpart. Treating it
			// as one would refuse every generation from this template over a
			// rendition nothing can render.
			return null;
		}

		$statements = [];
		foreach ((array)($declared['requiredStatements'] ?? []) as $statement) {
			$key = trim((string)$statement);
			if ($key !== '') {
				$statements[] = $key;
			}
		}

		$source = trim((string)($declared['source'] ?? PlainRenditionAcceptanceGate::SOURCE_TEMPLATE));
		if ($source === '') {
			$source = PlainRenditionAcceptanceGate::SOURCE_TEMPLATE;
		}

		return [
			'templateId' => $templateId,
			'requiredStatements' => array_values(array_unique($statements)),
			'source' => $source,
		];

	}//end counterpartOf()

	/**
	 * Which required statements the data cannot answer.
	 *
	 * @param array<int, string>   $required The statements the template declares.
	 * @param array<string, mixed> $data     The resolved generation data.
	 *
	 * @return array<int, string> The unresolved statements, in declaration order.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function unresolved(array $required, array $data): array {
		$missing = [];

		foreach ($required as $statement) {
			$value = ($data[$statement] ?? null);

			// 🔴 AN EMPTY STRING IS UNRESOLVED. A statement resolving to '' puts
			// a blank where the term should be, and a blank reads as "there is
			// no term" to the person holding the letter. `0` and `false` are
			// left alone: those are answers.
			if ($value === null || (is_string($value) === true && trim($value) === '')) {
				$missing[] = $statement;
				continue;
			}

			if (is_array($value) === true && $value === []) {
				$missing[] = $statement;
			}
		}

		return $missing;

	}//end unresolved()

	/**
	 * Plan the plain rendition, or refuse the generation.
	 *
	 * Called BEFORE the formal document is filed, so a refusal leaves neither
	 * rendition behind.
	 *
	 * @param array<string, mixed> $template   The formal template.
	 * @param array<string, mixed> $data       The resolved generation data.
	 * @param array<string, mixed> $acceptance The acceptance a caller offers for a machine draft.
	 *
	 * @return array{templateId: string, content: string, statements: array<int, string>, source: string, acceptedBy: string, acceptedAt: string}|null The plan, or null when no counterpart is declared.
	 *
	 * @throws PlainRenditionRefusedException When a statement is unresolved, the acceptance is missing, or the counterpart template cannot be read.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function plan(array $template, array $data, array $acceptance = []): ?array {
		$counterpart = $this->counterpartOf(template: $template);
		if ($counterpart === null) {
			return null;
		}

		$missing = $this->unresolved(required: $counterpart['requiredStatements'], data: $data);
		if ($missing !== []) {
			throw new PlainRenditionRefusedException(
				message: 'The plain-language version of this letter needs ' . implode(', ', $missing) . ', and the data does not answer ' . (count($missing) === 1 ? 'it' : 'them') . '. Neither version was filed.',
				unresolved: $missing
			);
		}

		$accepted = $this->gate->require(source: $counterpart['source'], acceptance: $acceptance);

		try {
			$counterpartTemplate = $this->templates->getTemplate(id: $counterpart['templateId']);
		} catch (Throwable $e) {
			// 🔴 A COUNTERPART THAT CANNOT BE READ REFUSES THE GENERATION. Filing
			// the formal letter alone would quietly drop a rendition the
			// template says every reader gets, and nobody would see the
			// difference until somebody complained they could not read their
			// besluit.
			$this->logger->warning(
				'filinq.plain-rendition.counterpart-unreadable',
				['templateId' => $counterpart['templateId'], 'error' => $e->getMessage()]
			);

			throw new PlainRenditionRefusedException(
				message: 'The plain-language template this letter declares could not be read, so neither version was filed.',
				unresolved: ['counterpart'],
				previous: $e
			);
		}

		return [
			'templateId' => $counterpart['templateId'],
			'content' => (string)($counterpartTemplate['content'] ?? ''),
			'statements' => $counterpart['requiredStatements'],
			'source' => $counterpart['source'],
			'acceptedBy' => $accepted['acceptedBy'],
			'acceptedAt' => $accepted['acceptedAt'],
		];

	}//end plan()

	/**
	 * The record fields a produced plain rendition adds to the generation entry.
	 *
	 * @param array<string, mixed> $plan       The plan.
	 * @param array<string, mixed> $formal     The formal document as it was filed: `fileId` and `path`.
	 * @param array<string, mixed> $plainFile  The plain rendition as it was filed: `fileId` and `path`.
	 * @param string               $moment     When the plain rendition was produced (ISO 8601).
	 *
	 * @return array<string, mixed> The fields.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function recordFields(array $plan, array $formal, array $plainFile, string $moment): array {
		// 🔴 THE PLAIN RENDITION NAMES THE FORMAL DOCUMENT, ON THE RECORD AND ON
		// THE DOCUMENT. Somebody receives two letters about one decision; a
		// plain letter that does not say which decision it explains cannot be
		// matched back to it, and the reader is left with two documents and no
		// relation between them.
		$explains = trim((string)($formal['path'] ?? ''));
		if ($explains === '') {
			$explains = trim((string)($formal['fileId'] ?? ''));
		}

		return [
			'plainRenditionFileId' => ($plainFile['fileId'] ?? null),
			'plainRenditionFilePath' => ($plainFile['path'] ?? null),
			'plainRenditionExplains' => $explains,
			'plainRenditionSource' => $plan['source'],
			'plainRenditionAcceptedBy' => $plan['acceptedBy'],
			'plainRenditionAcceptedAt' => $plan['acceptedAt'],
			'plainRenditionGeneratedAt' => $moment,
		];

	}//end recordFields()

	/**
	 * Whether a generation record's plain rendition is older than the formal document.
	 *
	 * @param array<string, mixed> $record The generated-document record.
	 *
	 * @return bool True when the plain rendition is stale.
	 *
	 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
	 */
	public function stale(array $record): bool {
		$plain = trim((string)($record['plainRenditionGeneratedAt'] ?? ''));
		if ($plain === '') {
			// A record with no plain rendition is not stale, it simply has none.
			return false;
		}

		$formal = trim((string)($record['generatedAt'] ?? ''));
		if ($formal === '') {
			// 🔴 AN UNREADABLE FORMAL MOMENT READS AS STALE. The cheap error is
			// regenerating a plain rendition that was already current; the
			// expensive one is sending a plain letter describing a decision
			// that has since been corrected.
			return true;
		}

		$plainAt = strtotime($plain);
		$formalAt = strtotime($formal);

		if ($plainAt === false || $formalAt === false) {
			return true;
		}

		return $plainAt < $formalAt;

	}//end stale()
}//end class
