<?php

/**
 * Settings Controller
 *
 * Controller for handling settings-related operations in Filinq.
 * Provides functionality for managing consent and metadata enrichment settings.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use Exception;
use OCA\Filinq\Controller\Support\AppConfiguration;
use OCA\Filinq\Controller\Support\SettingsBackends;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Controller for handling settings-related operations
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymiser-backend-warning/tasks.md#task-3
 */
class SettingsController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * SettingsController constructor
	 *
	 * @param IRequest $request The request object
	 * @param AppConfiguration $system Installed apps and the system configuration.
	 * @param IGroupManager $groupManager The group manager
	 * @param IUserSession $userSession The user session
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param SettingsBackends $backends The settings service and the anonymiser backend state.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly AppConfiguration $system,
		private readonly IGroupManager $groupManager,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
		private readonly SettingsBackends $backends,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * Retrieve the current settings
	 *
	 * @return JSONResponse JSON response containing the current settings
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	public function index(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			$isAdmin = $user !== null && $this->groupManager->isAdmin($user->getUID());
			$dismissed = $this->warningDismissed(userId: $user?->getUID());

			return new JSONResponse(
				array_merge($this->backends->settings->getAllSettings(), $this->environment(isAdmin: $isAdmin, dismissed: $dismissed))
			);
		} catch (Exception $e) {
			return $this->failed(message: 'Failed to retrieve settings', exception: $e);
		}//end try

	}//end index()

	/**
	 * Whether the session user dismissed the anonymiser warning; never without a user.
	 *
	 * @param string|null $userId The session user's id.
	 *
	 * @return bool True when the user stored a dismissal.
	 */
	private function warningDismissed(?string $userId): bool {
		if ($userId === null) {
			return false;
		}

		return $this->system->config->getUserValue(
			userId: $userId,
			appName: 'filinq',
			key: AnonymiserWarningController::DISMISSED_KEY,
			default: ''
		) === '1';

	}//end warningDismissed()

	/**
	 * What the settings page needs besides the stored settings.
	 *
	 * @param bool $isAdmin   Whether the session user is an admin.
	 * @param bool $dismissed Whether the session user dismissed the regex warning.
	 *
	 * @return array<string, mixed> openRegisters, libresignAvailable, isAdmin and anonymiserBackend.
	 */
	private function environment(bool $isAdmin, bool $dismissed): array {
		return [
			'openRegisters' => in_array(needle: 'openregister', haystack: $this->system->apps->getInstalledApps()),
			// The signing provider picker offers LibreSign only when it can sign.
			'libresignAvailable' => $this->system->apps->isEnabledForAnyone('libresign'),
			'isAdmin' => $isAdmin,
			'anonymiserBackend' => $this->anonymiserBackendPayload(dismissed: $dismissed),
		];

	}//end environment()

	/**
	 * The refusal for a caller who may not change the settings: 401 without a
	 * session, 403 for a signed-in user who is no administrator.
	 *
	 * @return JSONResponse|null The refusal, or null for an administrator.
	 */
	private function adminRefusal(): ?JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		if ($this->groupManager->isAdmin($user->getUID()) === false) {
			return new JSONResponse(
				data: ['error' => 'Admin privileges required'],
				statusCode: Http::STATUS_FORBIDDEN
			);
		}

		return null;

	}//end adminRefusal()

	/**
	 * Log a failure and answer 500 with its message.
	 *
	 * @param string    $message   The log line.
	 * @param Exception $exception The failure.
	 *
	 * @return JSONResponse The 500 response.
	 */
	private function failed(string $message, Exception $exception): JSONResponse {
		$this->logger->error($message, ['exception' => $exception->getMessage()]);
		return new JSONResponse(['error' => $exception->getMessage()], 500);

	}//end failed()

	/**
	 * The detection backend OpenRegister reports, and the warning it calls for.
	 *
	 * A dismissal hides only the regex warning. Unknown, disabled and
	 * unavailable mean every anonymisation is refused, so those stay up.
	 *
	 * @param bool $dismissed Whether this admin dismissed the regex warning.
	 *
	 * @return array<string, mixed> The state plus warning, showWarning,
	 *                              warningDismissed and appApiInstalled.
	 *
	 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-4
	 */
	private function anonymiserBackendPayload(bool $dismissed): array {
		$state = $this->backends->anonymiser->getState();
		$warning = $this->backends->anonymiser->warningFor(state: $state);

		$shown = $warning !== null;
		if ($warning === 'regex' && $dismissed === true) {
			$shown = false;
		}

		return array_merge(
			$state,
			[
				'warning' => $warning,
				'warningDismissed' => $dismissed,
				'showWarning' => $shown,
				'appApiInstalled' => $this->system->apps->isInstalled('app_api'),
			]
		);

	}//end anonymiserBackendPayload()

	/**
	 * Handle the post request to update settings
	 *
	 * Admin-only: writing settings (including signing_verification_secret,
	 * register/schema pointers, signing provider) must be restricted to
	 * administrators to prevent authenticated non-admin users from forging
	 * the HMAC verification secret or redirecting data to attacker-controlled
	 * registers (wave-3 C1).
	 *
	 * @return JSONResponse JSON response containing the updated settings
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 * @spec openspec/changes/redaction-four-eyes-setting/tasks.md#task-1
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function create(): JSONResponse {
		try {
			$refusal = $this->adminRefusal();
			if ($refusal !== null) {
				return $refusal;
			}

			$data = $this->request->getParams();

			$updatedData = $this->backends->settings->updateSettings($data);

			return new JSONResponse($updatedData);
		} catch (Exception $e) {
			// A refused value (an unknown four-eyes choice, a bad subfolder
			// name) is the caller's mistake and nothing was written: 400.
			if ($e instanceof RuntimeException && $e->getCode() === 400) {
				return new JSONResponse(['error' => $e->getMessage()], 400);
			}

			return $this->failed(message: 'Failed to update settings', exception: $e);
		}//end try

	}//end create()

	/**
	 * Update settings — the canonical ADR-066 write verb.
	 *
	 * `OCA\OpenRegister\AppHost\Routes::standard()` ships `settings#update`
	 * (PUT /api/settings) for EVERY app, and Filinq's own SettingsController
	 * had no `update()`: the route existed, resolved to this class, and blew up
	 * on dispatch. Same write path (and the same admin guard) as
	 * {@see create()}, which stays for the fleet's legacy POST dialect.
	 *
	 * @return JSONResponse JSON response containing the updated settings
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function update(): JSONResponse {
		return $this->create();
	}//end update()

	/**
	 * Re-run the register/schema initialisation from the app's register JSON.
	 *
	 * Counterpart to `settings#load` (POST /api/settings/load) in the canonical
	 * AppHost route table — likewise routed for every app and likewise missing
	 * here until now.
	 *
	 * @return JSONResponse The initialisation result.
	 *
	 * @spec openspec/specs/admin-settings/spec.md
	 */
	#[AuthorizedAdminSetting(FilinqAdmin::class)]
	public function load(): JSONResponse {
		try {
			$refusal = $this->adminRefusal();
			if ($refusal !== null) {
				return $refusal;
			}

			return new JSONResponse($this->backends->settings->initialize());
		} catch (Exception $e) {
			return $this->failed(message: 'Failed to load register configuration', exception: $e);
		}//end try

	}//end load()
}//end class
