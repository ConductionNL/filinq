<?php

/**
 * Contract of EmlPreviewService
 *
 * The public surface of the EmlPreviewService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use RuntimeException;

/**
 * Contract of EmlPreviewService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface EmlPreviewServiceInterface {
	/**
	 * Render the original EML identified by $fileId to a preview PDF/A-3b.
	 *
	 * An EMPTY entity set is passed so nothing is redacted: the assembled PDF
	 * is a faithful preview of the source message (original headers, body and
	 * renderable attachments). No file is written — the bytes are streamed by
	 * the caller. `EmlPdfAssemblyService::assemble()` throws
	 * `ConversionFailedException` on an unrecoverable render failure; that is
	 * left to propagate so the controller can surface a 422.
	 *
	 * @param int $fileId Nextcloud file id of the source .eml.
	 *
	 * @return string PDF/A-3b bytes of the original-message preview.
	 *
	 * @throws RuntimeException When OpenRegister is unavailable, the file
	 *                          cannot be resolved, or the anonymise-EML API
	 *                          is absent.
	 */
	public function renderOriginalPreview(int $fileId): string;
}//end interface
