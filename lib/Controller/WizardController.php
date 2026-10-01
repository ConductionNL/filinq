<?php

/**
 * Guided document wizards API
 *
 * api/wizards: author the wizard of a template, read it, remove it, and
 * prefill a run from a register object. Generation itself stays on
 * POST api/documents/generate with options.wizardContext.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/guided-document-wizard/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\Wizard\WizardRefused;
use OCA\Filinq\Service\Wizard\WizardService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * api/wizards and api/templates/{id}/wizard.
 */
class WizardController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string          $appName     The app id.
	 * @param IRequest        $request     The request.
	 * @param WizardService   $wizards     The wizards.
	 * @param IUserSession    $userSession The session.
	 * @param LoggerInterface $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly WizardService $wizards,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The active wizards that ask for an object of this register and schema.
	 *
	 * @param string $register The register.
	 * @param string $schema   The schema.
	 *
	 * @return JSONResponse {results}.
	 *
	 * @no-admin-idor-exempt lists wizards through OpenRegister as the caller, so only wizards the caller may read are answered; register and schema name no object.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#4-3
	 */
	#[NoAdminRequired]
	public function index(string $register='', string $schema=''): JSONResponse {
		return $this->answer(action: fn (): array => ['results' => $this->wizards->forObject(register: $register, schema: $schema)]);

	}//end index()

	/**
	 * Save a new wizard.
	 *
	 * @return JSONResponse {wizard, warnings}, or 403/409/422/423.
	 *
	 * @no-admin-idor-exempt creates a new object and names no existing one; who may create a wizard is the wizardDefinition schema's authorization (template editors), which OpenRegister enforces on the save as the caller.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#3-1
	 */
	#[NoAdminRequired]
	public function create(): JSONResponse {
		$body = $this->request->getParams();

		return $this->answer(action: fn (): array => $this->wizards->save(wizard: $body, userId: $this->userId()));

	}//end create()

	/**
	 * One wizard.
	 *
	 * @param string $id The wizard uuid.
	 *
	 * @return JSONResponse The wizard, or 404.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#3-1
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		return $this->answer(action: fn (): array => $this->wizards->requireWizard(uuid: $id));

	}//end show()

	/**
	 * Change a wizard.
	 *
	 * @param string $id The wizard uuid.
	 *
	 * @return JSONResponse {wizard, warnings}, or 403/404/409/422/423.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#3-1
	 */
	#[NoAdminRequired]
	public function update(string $id): JSONResponse {
		$body = $this->request->getParams();

		return $this->answer(
			action: function () use ($id, $body): array {
				// The per-object guard: OpenRegister reads as the caller, so a wizard they cannot read is not found.
				$this->wizards->requireWizard(uuid: $id);
				return $this->wizards->save(wizard: $body, userId: $this->userId(), uuid: $id);
			}
		);

	}//end update()

	/**
	 * Remove a wizard.
	 *
	 * @param string $id The wizard uuid.
	 *
	 * @return JSONResponse {deleted: true}, or 403/404/423.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#3-1
	 */
	#[NoAdminRequired]
	public function destroy(string $id): JSONResponse {
		return $this->answer(
			action: function () use ($id): array {
				$this->wizards->requireWizard(uuid: $id);
				$this->wizards->delete(uuid: $id, userId: $this->userId());
				return ['deleted' => true];
			}
		);

	}//end destroy()

	/**
	 * The active wizard of a template.
	 *
	 * @param string $id The template uuid.
	 *
	 * @return JSONResponse {wizard: object|null}.
	 *
	 * @no-admin-idor-exempt reads wizards through OpenRegister as the caller, so only wizards the caller may read are answered; the template id is not an object the caller is acting on.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#3-1
	 */
	#[NoAdminRequired]
	public function forTemplate(string $id): JSONResponse {
		return $this->answer(action: fn (): array => ['wizard' => $this->wizards->activeFor(templateId: $id)]);

	}//end forTemplate()

	/**
	 * Suggested answers for a run started from a register object.
	 *
	 * @param string $id       The wizard uuid.
	 * @param string $register The entry object's register.
	 * @param string $schema   The entry object's schema.
	 * @param string $objectId The entry object's id.
	 *
	 * @return JSONResponse {answers, unresolved}, or 404/422.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-5
	 */
	#[NoAdminRequired]
	public function prefill(string $id, string $register='', string $schema='', string $objectId=''): JSONResponse {
		return $this->answer(
			action: function () use ($id, $register, $schema, $objectId): array {
				$this->wizards->requireWizard(uuid: $id);
				return $this->wizards->prefill(uuid: $id, entry: ['register' => $register, 'schema' => $schema, 'id' => $objectId]);
			}
		);

	}//end prefill()

	/**
	 * The caller's user id.
	 *
	 * @return string The uid, '' without a session.
	 */
	private function userId(): string {
		return (string) $this->userSession->getUser()?->getUID();

	}//end userId()

	/**
	 * Run an action; a refusal keeps its status and errors, anything else is a 500 with a generic body.
	 *
	 * @param callable $action The action.
	 *
	 * @return JSONResponse The answer.
	 */
	private function answer(callable $action): JSONResponse {
		try {
			return new JSONResponse(data: $action());
		} catch (WizardRefused $e) {
			$body = ['error' => $e->getMessage()];
			if ($e->getErrors() !== []) {
				$body['errors'] = $e->getErrors();
			}

			return new JSONResponse(data: $body, statusCode: $e->getCode());
		} catch (Throwable $e) {
			if (in_array($e->getCode(), [Http::STATUS_FORBIDDEN, Http::STATUS_UNPROCESSABLE_ENTITY], true) === true) {
				// The repository's refusals: the caller may not author wizards, or OpenRegister refused the object.
				return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $e->getCode());
			}

			$this->logger->error('[WizardController] request failed', ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()
}//end class
