<?php

/**
 * Field Placement Check
 *
 * Accepts or refuses the field placements a new signing request carries:
 * the placement rules, the provider's ability to carry fields, and the page
 * count of the document the fields go on.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use InvalidArgumentException;
use OCA\Filinq\Service\SignedArtifactProducer;
use RuntimeException;

/**
 * Checks a new request's field placements before the request is stored.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
 */
class FieldPlacementCheck {

	/**
	 * Constructor.
	 *
	 * @param FieldPlacements        $rules    The placement rules.
	 * @param FieldPlacementRenderer $renderer Reads the page count a placement must stay within.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function __construct(
		private readonly FieldPlacements $rules = new FieldPlacements(),
		private readonly FieldPlacementRenderer $renderer = new FieldPlacementRenderer(),
	) {

	}//end __construct()

	/**
	 * The normalised placements, or a 400 naming what is wrong.
	 *
	 * The rules come from FieldPlacements. LibreSign's request-signature call
	 * has no field input (its visible elements need the sign-request ids it
	 * creates), so a LibreSign request with placements is refused instead of
	 * being signed without them.
	 *
	 * @param mixed  $placements  The `fieldPlacements` the caller sent.
	 * @param int    $signerCount How many signers the request names.
	 * @param string $provider    The request's provider.
	 *
	 * @return list<array<string, mixed>> The placements, empty when there are none.
	 *
	 * @throws RuntimeException 400 when a placement breaks a rule or the provider cannot carry placements.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function accept(mixed $placements, int $signerCount, string $provider): array {
		try {
			$normalised = $this->rules->normalise(placements: $placements, signerCount: $signerCount);
		} catch (InvalidArgumentException $e) {
			throw new RuntimeException(message: $e->getMessage(), code: 400, previous: $e);
		}

		if ($normalised !== [] && $provider === LibreSignProvider::IDENTIFIER) {
			throw new RuntimeException(message: 'LibreSign places its own fields: send this request without field placements', code: 400);
		}

		return $normalised;

	}//end accept()

	/**
	 * Refuse a placement on a page the document does not have.
	 *
	 * @param list<array<string, mixed>> $placements The accepted placements.
	 * @param string                     $pdf        The document's bytes.
	 *
	 * @return void
	 *
	 * @throws RuntimeException 400 when the document is not a PDF the renderer can read, or a page is missing.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function assertPagesExist(array $placements, string $pdf): void {
		try {
			$pages = $this->renderer->pageCount(pdf: $pdf);
		} catch (RuntimeException $e) {
			throw new RuntimeException(message: $e->getMessage(), code: 400, previous: $e);
		}

		foreach ($placements as $placement) {
			if ($placement['page'] > $pages) {
				throw new RuntimeException(message: 'A field is placed on page ' . $placement['page'] . ' of a document with ' . $pages . ' pages', code: 400);
			}
		}

	}//end assertPagesExist()

	/**
	 * Check a new request's field placements and put them on the request.
	 *
	 * Runs before the request is stored. The document is read only when there
	 * are placements, through the producer's access check.
	 *
	 * @param array<string, mixed>   $request     The request about to be stored.
	 * @param mixed                  $placements  The `fieldPlacements` the caller sent.
	 * @param int                    $signerCount How many signers the request names.
	 * @param SignedArtifactProducer $producer    Reads the document's bytes.
	 *
	 * @return array<string, mixed> The request, with `fieldPlacements` when there are any.
	 *
	 * @throws RuntimeException 400 when a placement breaks a rule, names a page the document lacks, or the provider cannot carry placements.
	 *
	 * @spec openspec/changes/archive/2026-09-30-bulk-signing-field-builder/tasks.md#task-3.1
	 */
	public function apply(array $request, mixed $placements, int $signerCount, SignedArtifactProducer $producer): array {
		$accepted = $this->accept(placements: $placements, signerCount: $signerCount, provider: (string) ($request['provider'] ?? ''));
		if ($accepted === []) {
			return $request;
		}

		$this->assertPagesExist(placements: $accepted, pdf: $producer->documentContent(request: $request));
		$request['fieldPlacements'] = $accepted;

		return $request;

	}//end apply()
}//end class
