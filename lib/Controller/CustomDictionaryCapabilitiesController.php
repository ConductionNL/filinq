<?php

/**
 * Custom Dictionary Capabilities Controller
 *
 * Tells the dictionary pages what the caller may do, so they offer only the
 * changes the server will accept.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/dictionary-actions-for-maintainers/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\CustomDictionaryAccessGate;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Answers whether the caller maintains custom dictionaries.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class CustomDictionaryCapabilitiesController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                     $appName The app name.
	 * @param IRequest                   $request The request.
	 * @param CustomDictionaryAccessGate $gate    The maintainer check the write endpoints use.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly CustomDictionaryAccessGate $gate,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * What the caller may do with custom dictionaries.
	 *
	 * @return JSONResponse `{ canMaintain: bool }`
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/changes/dictionary-actions-for-maintainers/specs/custom-dictionary-recognition/spec.md
	 */
	public function show(): JSONResponse {
		return new JSONResponse(['canMaintain' => $this->gate->mayMaintain()]);

	}//end show()
}//end class
