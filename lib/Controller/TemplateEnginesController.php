<?php

/**
 * Template Engines Controller
 *
 * GET /api/templates/engines: the engines this instance can render a
 * template with. Always `filinq`; one `vendor:<slug>` per active document
 * generation source integriq holds. Read only.
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
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\Vendor\RenderEngine;
use OCA\Filinq\Service\Vendor\VendorRenderSources;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Lists the render engines a template can name.
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
 */
class TemplateEnginesController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param IRequest            $request     The request.
	 * @param VendorRenderSources $sources     integriq's document generation sources.
	 * @param IUserSession        $userSession The signed-in user.
	 * @param RenderEngine        $engine      Names each vendor engine.
	 */
	public function __construct(
		IRequest $request,
		private readonly VendorRenderSources $sources,
		private readonly IUserSession $userSession,
		private readonly RenderEngine $engine = new RenderEngine(),
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * The engines: `filinq` first, then one per active vendor source.
	 *
	 * The list names no credentials and no configuration, only the slug and
	 * the display name, so any signed-in user may read it.
	 *
	 * @return JSONResponse 200 with `results` [{id, name, type}]; 401 without a user.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		$vendors = array_map(
			fn (array $source): array => [
				'id' => $this->engine->forVendor(slug: $source['slug']),
				'name' => $source['name'],
				'type' => 'vendor',
			],
			$this->sources->active()
		);

		return new JSONResponse(data: ['results' => array_merge([['id' => RenderEngine::FILINQ, 'name' => 'Filinq', 'type' => 'filinq']], $vendors)]);

	}//end index()
}//end class
