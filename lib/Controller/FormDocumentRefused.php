<?php

/**
 * Form document refused
 *
 * A document reference or a submitted form that filinq refuses, with the HTTP status to answer.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use RuntimeException;

/**
 * A refused document reference or form render; the code is the HTTP status.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/forms-as-documents/specs/document-creatie-sjablonen/spec.md#requirement-a-file-is-a-value-a-field-can-hold-and-a-form-becomes-a-document-req-dft-05
 */
class FormDocumentRefused extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $message The message, in English; the controller translates it.
	 * @param int    $code    The HTTP status.
	 *
	 * @return void
	 */
	public function __construct(string $message, int $code) {
		parent::__construct(message: $message, code: $code);

	}//end __construct()
}//end class
