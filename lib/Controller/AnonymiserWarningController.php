<?php

/**
 * Anonymiser Warning Controller
 *
 * Provides admin endpoints for managing the per-admin dismissal state of the
 * anonymiser backend warning banner. Dismissal is stored as a per-user IConfig
 * value so two admins can independently choose to see or hide the banner.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Admin endpoints to dismiss or restore the anonymiser backend warning banner.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-3
 */
class AnonymiserWarningController extends Controller {

	/**
	 * IConfig key under which the per-admin dismissal flag is stored.
	 *
	 * @var string
	 */
	public const DISMISSED_KEY = 'anonymiser_warning_dismissed';

	/**
	 * Constructor.
	 *
	 * @param IRequest $request HTTP request object.
	 * @param IConfig $config Nextcloud config (provides per-user values).
	 * @param IUserSession $userSession Current user session.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		IRequest $request,
		private readonly IConfig $config,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: Application::APP_ID, request: $request);

	}//end __construct()

	/**
	 * Dismiss the anonymiser backend warning for the current admin.
	 *
	 * Persists a per-user config value so the banner is suppressed on
	 * subsequent page loads for this admin only (ADR-017).
	 *
	 * @return JSONResponse JSON response with the new dismissed state.
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-3
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function dismiss(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['message' => 'Not authenticated'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$this->config->setUserValue(
			userId: $user->getUID(),
			appName: Application::APP_ID,
			key: self::DISMISSED_KEY,
			value: '1'
		);

		$this->logger->info(
			'Anonymiser backend warning dismissed',
			['userId' => $user->getUID()]
		);

		return new JSONResponse(['dismissed' => true]);
	}//end dismiss()

	/**
	 * Reset (restore) the anonymiser backend warning for the current admin.
	 *
	 * Clears the per-user dismissal flag so the banner is shown again on the
	 * next page load. Provides a path for admins who want to re-read the
	 * install guidance after initially dismissing it.
	 *
	 * @return JSONResponse JSON response with the new dismissed state.
	 *
	 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-3
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function reset(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['message' => 'Not authenticated'],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$this->config->deleteUserValue(
			userId: $user->getUID(),
			appName: Application::APP_ID,
			key: self::DISMISSED_KEY
		);

		$this->logger->info(
			'Anonymiser backend warning reset',
			['userId' => $user->getUID()]
		);

		return new JSONResponse(['dismissed' => false]);
	}//end reset()
}//end class
