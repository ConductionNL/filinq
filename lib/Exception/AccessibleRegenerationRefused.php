<?php

/**
 * Filinq AccessibleRegenerationRefused
 *
 * Why a PDF is not regenerated as PDF/UA: Filinq did not generate it, so
 * there is no template to render again and Filinq does not retag its pages.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-document-filinq-generated-can-be-regenerated-as-pdfua-an-imported-one-cannot-req-ddpum-008
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A refused regeneration, with its reason code.
 */
class AccessibleRegenerationRefused extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $reason The reason code, such as `not-generated-by-filinq`.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $reason,
	) {
		parent::__construct(message: 'Regeneration refused: ' . $reason);

	}//end __construct()

	/**
	 * The reason code.
	 *
	 * @return string The reason.
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()
}//end class
