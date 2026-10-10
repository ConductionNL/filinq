<?php

/**
 * Entity search routes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\EntitySearch\EntitySearchService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Not a pass-through to OpenRegister's `/api/entities` (ADR-022): each route
 * adds what must not run in the browser. The group gate (admins plus
 * `entity_search.allowed_groups`, fail closed), the processing-log write that
 * must succeed before an answer leaves, and the per-document enrichment with
 * the caller's own file rights. Every route is open to signed-in users at
 * the framework level and runs the gate first, in the service.
 */
class EntitySearchController extends Controller {

	/**
	 * HTTP status per refusal.
	 *
	 * @var array<string, int>
	 */
	private const STATUS = [
		EntitySearchRefusedException::REASON_NOT_ALLOWED => Http::STATUS_FORBIDDEN,
		EntitySearchRefusedException::REASON_CONFIG_UNREADABLE => Http::STATUS_FORBIDDEN,
		EntitySearchRefusedException::REASON_NOT_FOUND => Http::STATUS_NOT_FOUND,
		EntitySearchRefusedException::REASON_INVALID => Http::STATUS_BAD_REQUEST,
		EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE => Http::STATUS_SERVICE_UNAVAILABLE,
		EntitySearchRefusedException::REASON_LOG_UNAVAILABLE => Http::STATUS_SERVICE_UNAVAILABLE,
	];

	/**
	 * Constructor.
	 *
	 * @param string              $appName     The app name.
	 * @param IRequest            $request     The request.
	 * @param EntitySearchService $search      The entity search.
	 * @param IUserSession        $userSession The signed-in user.
	 * @param IL10N               $l10n        Translations.
	 * @param LoggerInterface     $logger      For failures that are not refusals.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly EntitySearchService $search,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Whether the caller may use the entity search; 403 when not.
	 *
	 * @return JSONResponse {allowed: true}, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-3.1
	 */
	#[NoAdminRequired]
	public function access(): JSONResponse {
		return $this->answer(action: fn (): array => $this->search->access(userId: $this->userId()));

	}//end access()

	/**
	 * Search the catalogue.
	 *
	 * @return JSONResponse The page, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return $this->answer(
			action: fn (): array => $this->search->search(
				userId: $this->userId(),
				query: (string) $this->request->getParam('query', ''),
				type: (string) $this->request->getParam('type', ''),
				category: (string) $this->request->getParam('category', ''),
				limit: (int) $this->request->getParam('limit', EntitySearchService::DEFAULT_LIMIT),
				offset: (int) $this->request->getParam('offset', 0)
			)
		);

	}//end index()

	/**
	 * One entity and where it occurs.
	 *
	 * @param string $entityUuid The entity uuid.
	 *
	 * @return JSONResponse The entity, or a refusal.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
	 */
	#[NoAdminRequired]
	public function show(string $entityUuid): JSONResponse {
		return $this->answer(action: fn (): array => $this->search->detail(userId: $this->userId(), uuid: $entityUuid));

	}//end show()

	/**
	 * Run an action and answer with its result or its refusal.
	 *
	 * @param callable $action Returns the body.
	 *
	 * @return JSONResponse The response.
	 */
	private function answer(callable $action): JSONResponse {
		try {
			return new JSONResponse($action());
		} catch (EntitySearchRefusedException $refusal) {
			return $this->refused(refusal: $refusal);
		} catch (Throwable $e) {
			$this->logger->error(message: '[EntitySearchController] an entity lookup failed', context: ['error' => $e->getMessage()]);
			return new JSONResponse(['error' => $this->l10n->t('The entity search failed. Try again later.')], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()

	/**
	 * The response for a refusal. The body is neutral: it never says what exists.
	 *
	 * @param EntitySearchRefusedException $refusal The refusal.
	 *
	 * @return JSONResponse The response.
	 */
	private function refused(EntitySearchRefusedException $refusal): JSONResponse {
		$reason = $refusal->getReason();
		$messages = [
			EntitySearchRefusedException::REASON_NOT_ALLOWED => $this->l10n->t('You are not allowed to use the entity search.'),
			EntitySearchRefusedException::REASON_CONFIG_UNREADABLE => $this->l10n->t('You are not allowed to use the entity search.'),
			EntitySearchRefusedException::REASON_NOT_FOUND => $this->l10n->t('Not found'),
			EntitySearchRefusedException::REASON_INVALID => $this->l10n->t('Type a value, or choose a type or a category.'),
			EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE => $this->l10n->t('The entity catalogue of OpenRegister cannot be read right now.'),
			EntitySearchRefusedException::REASON_LOG_UNAVAILABLE => $this->l10n->t(
				'The search could not be recorded in the processing log, so it was not run.'
			),
		];

		return new JSONResponse(
			['error' => ($messages[$reason] ?? $this->l10n->t('The entity search failed. Try again later.')), 'reason' => $reason],
			(self::STATUS[$reason] ?? Http::STATUS_INTERNAL_SERVER_ERROR)
		);

	}//end refused()

	/**
	 * The signed-in user's id, '' when nobody is.
	 *
	 * @return string The user id.
	 */
	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end userId()
}//end class
