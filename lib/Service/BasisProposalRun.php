<?php

/**
 * Basis Proposal Run
 *
 * What one pass of {@see LegalBasisProposalService::applyProposals} works
 * with: the relation mapper, the entity-type to base-slug mapping and the
 * file whose detections are being filled. Built once per pass and never
 * handed outside the service.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/propose-grondslag-per-entity-type/specs/grondslag-proposal/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The mapper, mapping and file of one proposal pass.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
final class BasisProposalRun {

	/**
	 * The EntityRelationMapper.
	 *
	 * @var mixed
	 */
	public mixed $mapper = null;

	/**
	 * Entity-type → base-slug[] mapping.
	 *
	 * @var array<string, array<int, string>>
	 */
	public array $mapping = [];

	/**
	 * The file whose detections are filled.
	 *
	 * @var int
	 */
	public int $fileId = 0;
}//end class
