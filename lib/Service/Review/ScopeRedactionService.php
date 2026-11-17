<?php

/**
 * Scope redaction: a page, a range of pages or a whole document in one action.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\File;

/**
 * Validates, stores and commits scope decisions.
 *
 * A decision is one row of the review model beside an entity or a region:
 * `scope` (page, pageRange or document), `fromPage` and `toPage` (1-based,
 * inclusive, absent for document) and at least one ground. A page or range is
 * committed as one full-page region per page on OpenRegister's image seam, so
 * the seam does the pixel work; filinq burns nothing itself. A whole document
 * is withheld: no output is written and its anonymisation link says so.
 *
 * Every refusal happens before anything is written.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */
class ScopeRedactionService {

	/**
	 * One page.
	 */
	public const SCOPE_PAGE = 'page';

	/**
	 * A range of pages, from and to inclusive.
	 */
	public const SCOPE_RANGE = 'pageRange';

	/**
	 * The whole document.
	 */
	public const SCOPE_DOCUMENT = 'document';

	/**
	 * The scopes a decision may take.
	 *
	 * @var list<string>
	 */
	public const SCOPES = [self::SCOPE_PAGE, self::SCOPE_RANGE, self::SCOPE_DOCUMENT];

	/**
	 * The box that covers a whole page, normalised to 0..1.
	 *
	 * @var array{x: float, y: float, w: float, h: float}
	 */
	public const FULL_PAGE = ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0];

	/**
	 * The refusal when OpenRegister has no image seam.
	 */
	public const NO_SEAM = "Page redaction needs OpenRegister's image seam, so nothing was written.";

	/**
	 * Constructor.
	 *
	 * @param ScopeDecisionStore  $store Where decisions and their outcome are kept.
	 * @param ImageSeamGateway    $seam  OpenRegister's image seam.
	 * @param DocumentPageCounter $pages The document's page count.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ScopeDecisionStore $store,
		private readonly ImageSeamGateway $seam,
		private readonly DocumentPageCounter $pages = new DocumentPageCounter(),
	) {

	}//end __construct()

	/**
	 * The document's decisions, its page count and whether page scopes can be committed.
	 *
	 * @param File $file The document.
	 *
	 * @return array{decisions: list<array<string, mixed>>, pageCount: int, pageScopesAvailable: bool} The state.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
	 */
	public function state(File $file): array {
		return [
			'decisions' => $this->store->decisionsFor(fileId: (int) $file->getId()),
			'pageCount' => $this->pages->count(file: $file),
			'pageScopesAvailable' => $this->seam->available(),
		];

	}//end state()

	/**
	 * Store a decision on a document, counting its pages.
	 *
	 * @param File                 $file   The document.
	 * @param array<string, mixed> $input  The decision as entered.
	 * @param string               $userId Who decided.
	 *
	 * @return array<string, mixed> The stored decision.
	 *
	 * @throws ScopeDecisionRefusedException When the decision does not fit; nothing is stored.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	public function decide(File $file, array $input, string $userId): array {
		return $this->record(fileId: (int) $file->getId(), input: $input, pageCount: $this->pages->count(file: $file), userId: $userId);

	}//end decide()

	/**
	 * Commit a decision on a document, counting its pages.
	 *
	 * @param File                 $file   The document.
	 * @param array<string, mixed> $input  The decision.
	 * @param string               $userId Who commits.
	 *
	 * @return array{scope: string, withheld: bool, outputFileId: int|null, burnedPages: list<int>} The outcome.
	 *
	 * @throws ScopeDecisionRefusedException When the decision does not fit or the seam is absent; nothing is written.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function commitOn(File $file, array $input, string $userId): array {
		return $this->commit(file: $file, input: $input, pageCount: $this->pages->count(file: $file), userId: $userId);

	}//end commitOn()

	/**
	 * Validate and normalise a decision.
	 *
	 * @param array<string, mixed> $input     The decision as entered.
	 * @param int                  $pageCount The document's page count, 0 when not known.
	 *
	 * @return array{scope: string, fromPage?: int, toPage?: int, bases: list<string>} The decision.
	 *
	 * @throws ScopeDecisionRefusedException When the decision does not fit the document.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	public function validate(array $input, int $pageCount): array {
		$scope = (string) ($input['scope'] ?? '');
		if (in_array($scope, self::SCOPES, true) === false) {
			throw new ScopeDecisionRefusedException(
				message: sprintf('Unknown scope "%s"; use page, pageRange or document.', $scope)
			);
		}

		$bases = $this->basesOf(input: $input);
		if ($scope === self::SCOPE_DOCUMENT) {
			return ['scope' => $scope, 'bases' => $bases];
		}

		[$from, $to] = $this->pagesOf(input: $input, scope: $scope, pageCount: $pageCount);

		return ['scope' => $scope, 'fromPage' => $from, 'toPage' => $to, 'bases' => $bases];

	}//end validate()

	/**
	 * Validate a decision and store it as one row of the document's review.
	 *
	 * @param int                  $fileId    The document.
	 * @param array<string, mixed> $input     The decision as entered.
	 * @param int                  $pageCount The document's page count, 0 when not known.
	 * @param string               $userId    Who decided.
	 *
	 * @return array<string, mixed> The stored decision.
	 *
	 * @throws ScopeDecisionRefusedException When the decision does not fit; nothing is stored.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	public function record(int $fileId, array $input, int $pageCount, string $userId): array {
		$decision = $this->validate(input: $input, pageCount: $pageCount);
		$decision['decidedBy'] = $userId;
		$decision['decidedAt'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

		return $this->store->append(fileId: $fileId, decision: $decision);

	}//end record()

	/**
	 * The full-page regions a page or range decision sends to the seam, one per page.
	 *
	 * @param array<string, mixed> $decision A validated decision.
	 *
	 * @return list<array{page: int, box: array{x: float, y: float, w: float, h: float}}> The regions.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function fullPageRegions(array $decision): array {
		if (($decision['scope'] ?? '') === self::SCOPE_DOCUMENT) {
			return [];
		}

		$regions = [];
		foreach (range((int) $decision['fromPage'], (int) $decision['toPage']) as $page) {
			$regions[] = ['page' => $page, 'box' => self::FULL_PAGE];
		}

		return $regions;

	}//end fullPageRegions()

	/**
	 * Commit a decision: burn its pages through the seam, or withhold the document.
	 *
	 * @param File                 $file      The source document.
	 * @param array<string, mixed> $input     The decision.
	 * @param int                  $pageCount The document's page count, 0 when not known.
	 * @param string               $userId    Who commits.
	 *
	 * @return array{scope: string, withheld: bool, outputFileId: int|null, burnedPages: list<int>} The outcome.
	 *
	 * @throws ScopeDecisionRefusedException When the decision does not fit or the seam is absent; nothing is written.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function commit(File $file, array $input, int $pageCount, string $userId = ''): array {
		$decision = $this->validate(input: $input, pageCount: $pageCount);
		$fileId = (int) $file->getId();

		if ($decision['scope'] === self::SCOPE_DOCUMENT) {
			$this->store->withhold(fileId: $fileId, bases: $decision['bases'], userId: $userId);

			return ['scope' => self::SCOPE_DOCUMENT, 'withheld' => true, 'outputFileId' => null, 'burnedPages' => []];
		}

		if ($this->seam->available() === false) {
			throw new ScopeDecisionRefusedException(message: self::NO_SEAM);
		}

		$regions = $this->fullPageRegions(decision: $decision);
		$output = $this->seam->burn(file: $file, regions: $regions, bases: $decision['bases']);
		$this->store->recordBurn(fileId: $fileId, outputFileId: (int) $output->getId(), burnedRegionCount: count($regions));

		return [
			'scope' => $decision['scope'],
			'withheld' => false,
			'outputFileId' => (int) $output->getId(),
			'burnedPages' => array_column($regions, 'page'),
		];

	}//end commit()

	/**
	 * The decision's grounds, trimmed and without duplicates; at least one.
	 *
	 * @param array<string, mixed> $input The decision as entered.
	 *
	 * @return list<string> The grounds.
	 *
	 * @throws ScopeDecisionRefusedException When there is no ground.
	 */
	private function basesOf(array $input): array {
		$bases = [];
		$raw = $input['bases'] ?? [];
		if (is_array($raw) === false) {
			$raw = [];
		}

		foreach ($raw as $basis) {
			if (is_scalar($basis) === false) {
				continue;
			}

			$basis = trim((string) $basis);
			if ($basis !== '' && in_array($basis, $bases, true) === false) {
				$bases[] = $basis;
			}
		}

		if ($bases === []) {
			throw new ScopeDecisionRefusedException(message: 'A redaction needs at least one ground.');
		}

		return $bases;

	}//end basesOf()

	/**
	 * The first and last page of a page or range decision.
	 *
	 * @param array<string, mixed> $input     The decision as entered.
	 * @param string               $scope     page or pageRange.
	 * @param int                  $pageCount The document's page count, 0 when not known.
	 *
	 * @return array{0: int, 1: int} From and to, inclusive.
	 *
	 * @throws ScopeDecisionRefusedException When the pages do not fit the document.
	 */
	private function pagesOf(array $input, string $scope, int $pageCount): array {
		if ($pageCount < 1) {
			throw new ScopeDecisionRefusedException(
				message: 'The page count of this document is not known, so it cannot be redacted by page.'
			);
		}

		$from = $this->pageNumber(value: ($input['fromPage'] ?? null), label: 'fromPage');
		$to = $from;
		if ($scope === self::SCOPE_RANGE) {
			$to = $this->pageNumber(value: ($input['toPage'] ?? null), label: 'toPage');
		}

		if ($from > $to) {
			throw new ScopeDecisionRefusedException(
				message: sprintf('A range must start at or before its last page (%d is after %d).', $from, $to)
			);
		}

		if ($to > $pageCount) {
			$pages = sprintf('page %d', $from);
			if ($from !== $to) {
				$pages = sprintf('pages %d to %d', $from, $to);
			}

			throw new ScopeDecisionRefusedException(
				message: sprintf('The document has %d pages, so %s cannot be redacted.', $pageCount, $pages)
			);
		}

		return [$from, $to];

	}//end pagesOf()

	/**
	 * A 1-based page number.
	 *
	 * @param mixed  $value The entered value.
	 * @param string $label The field, for the refusal.
	 *
	 * @return int The page.
	 *
	 * @throws ScopeDecisionRefusedException When it is not a whole number from 1.
	 */
	private function pageNumber(mixed $value, string $label): int {
		if (is_int($value) === false && (is_string($value) === false || preg_match('/^\d+$/', trim($value)) !== 1)) {
			throw new ScopeDecisionRefusedException(message: sprintf('%s must be a page number from 1.', $label));
		}

		$page = (int) $value;
		if ($page < 1) {
			throw new ScopeDecisionRefusedException(message: sprintf('%s must be a page number from 1.', $label));
		}

		return $page;

	}//end pageNumber()
}//end class
