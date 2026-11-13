<?php

/**
 * Field fill controller
 *
 * The endpoints of the schema field suggestions: ask for suggestions on a
 * record from one of its files, decide them, and the admin switch and profiles.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\FieldFill\FieldFillProfileService;
use OCA\Filinq\Service\FieldFill\SchemaFieldFillService;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Schema field suggestions over HTTP.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 */
class FieldFillController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param IRequest                $request     The request.
	 * @param SchemaFieldFillService  $fieldFill   The suggestions.
	 * @param FieldFillProfileService $profiles    The declared profiles.
	 * @param IUserSession            $userSession The session user.
	 * @param IL10N                   $l10n        Translations.
	 * @param LoggerInterface         $logger      Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly SchemaFieldFillService $fieldFill,
		private readonly FieldFillProfileService $profiles,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * Ask for suggestions: `fileId`, `register`, `schema` and `objectId` in the body.
	 *
	 * The record is read with the caller's rights and the file is looked up in
	 * the caller's files, so a caller who cannot read either gets a 404.
	 *
	 * @return JSONResponse 201 with the suggestion, or the refusal.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	#[NoAdminRequired]
	public function fields(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		return $this->answer(
			action: fn (): array => $this->fieldFill->suggest(
				fileId: (int)$this->request->getParam('fileId', 0),
				register: $this->stringParam(name: 'register'),
				schema: $this->stringParam(name: 'schema'),
				objectId: $this->stringParam(name: 'objectId'),
				userId: $user->getUID()
			),
			status: Http::STATUS_CREATED
		);

	}//end fields()

	/**
	 * Decide a suggestion: `decisions` in the body, per field `decision` and, for change, `value`.
	 *
	 * @param string $id The suggestion.
	 *
	 * @return JSONResponse The decided suggestion, or the refusal.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	#[NoAdminRequired]
	public function decide(string $id): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		$decisions = $this->request->getParam('decisions', []);
		if (is_array($decisions) === false) {
			$decisions = [];
		}

		return $this->answer(
			action: fn (): array => $this->fieldFill->decide(id: $id, decisions: $decisions, userId: $user->getUID())
		);

	}//end decide()

	/**
	 * Whether model filling can be used, and which provider reads the documents.
	 *
	 * @return JSONResponse The state.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	#[NoAdminRequired]
	public function status(): JSONResponse {
		return $this->answer(action: fn (): array => $this->fieldFill->status());

	}//end status()

	/**
	 * Switch model filling on or off (admin): `enabled` in the body.
	 *
	 * @return JSONResponse The state after the change, or 503 when there is no model.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function setEnabled(): JSONResponse {
		$enabled = filter_var($this->request->getParam('enabled', false), FILTER_VALIDATE_BOOLEAN);

		return $this->answer(action: fn (): array => $this->fieldFill->setEnabled(enabled: $enabled));

	}//end setEnabled()

	/**
	 * Every declared profile (admin).
	 *
	 * @return JSONResponse The profiles.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function profiles(): JSONResponse {
		return $this->answer(action: fn (): array => ['results' => $this->profiles->all()]);

	}//end profiles()

	/**
	 * Store a profile (admin): `declaringApp`, `register`, `schema`, `fields` and `active` in the body.
	 *
	 * @param string|null $id The profile to replace, or none for a new one.
	 *
	 * @return JSONResponse The stored profile, or 400 for a refused one.
	 *
	 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function saveProfile(?string $id = null): JSONResponse {
		$profile = [
			'declaringApp' => $this->stringParam(name: 'declaringApp'),
			'register' => $this->stringParam(name: 'register'),
			'schema' => $this->stringParam(name: 'schema'),
			'fields' => $this->request->getParam('fields', []),
			'active' => filter_var($this->request->getParam('active', false), FILTER_VALIDATE_BOOLEAN),
		];

		return $this->answer(action: fn (): array => $this->profiles->save(profile: $profile, uuid: $id));

	}//end saveProfile()

	/**
	 * Run an action and answer its result, a refusal with its own status, or a 500.
	 *
	 * @param callable $action The action.
	 * @param int      $status The status of a success.
	 *
	 * @return JSONResponse The answer.
	 */
	private function answer(callable $action, int $status=Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse(data: $action(), statusCode: $status);
		} catch (FieldFillRefused $refused) {
			return new JSONResponse(data: ['error' => $this->l10n->t($refused->getMessage())], statusCode: $refused->getCode());
		} catch (Throwable $e) {
			$this->logger->error(message: 'Filinq: field fill failed: ' . $e->getMessage(), context: ['exception' => $e]);
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Filling fields from the document failed')],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

	}//end answer()

	/**
	 * A string parameter, trimmed; anything else reads as ''.
	 *
	 * @param string $name The parameter.
	 *
	 * @return string The value.
	 */
	private function stringParam(string $name): string {
		$value = $this->request->getParam($name, '');
		if (is_string($value) === false) {
			return '';
		}

		return trim($value);

	}//end stringParam()
}//end class
