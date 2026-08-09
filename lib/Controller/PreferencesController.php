<?php

/**
 * Per-user preferences controller.
 *
 * Local implementation of the per-user key/value preference API, mirroring the
 * existing `HealthController` / `MetricsController` pattern. Behaviourally
 * identical to OpenRegister's AppHost `GenericPreferencesController` it used to
 * subclass — same auth posture, same key sanitisation, same per-user scoping,
 * same JSON shapes — but it needs NOTHING from OpenRegister: the whole
 * implementation is OCP (`IConfig` + `IUserSession`), so there is no engine to
 * delegate to and no container lookup to make.
 *
 * ⚠️ DO NOT "simplify" this back into a subclass of the AppHost generic.
 * Nextcloud's router `ReflectionClass()`es every file in `lib/Controller/` while
 * MATCHING a route, so an unresolvable parent makes EVERY route in DocuDesk
 * return HTTP 500, not just this one. DocuDesk does not declare
 * `<app>openregister</app>`, so an admin can create exactly that configuration.
 * `extends` is resolved by the AUTOLOADER, not the DI container, so lazy DI
 * cannot rescue it. See decidesk#377 / #388.
 *
 * WHY THIS CLASS EXISTS: Nextcloud resolves a route `name` of the form
 * `foo#bar` to the class `OCA\<App>\Controller\FooController` — it always
 * prefixes the app's own `Controller` namespace. A route named
 * `AppHost\Controller\GenericPreferences#getPreference` therefore does NOT
 * resolve to OpenRegister's class (nor to the container alias registered for
 * it in `Application::register()`); Nextcloud looked for
 * `OCA\DocuDesk\Controller\PreferencesController`, which did not exist, and
 * every request to `/api/preferences/{key}` failed with
 * `QueryNotFoundException` → HTTP 500.
 *
 * That broke the shared `CnSupportDialog` widget, which reads and writes the
 * `support-dialog-seen` preference on every app load. Caught by the e2e 5xx
 * guard on 2026-07-24, not by unit tests (no test exercised the route).
 *
 * Declaring the subclass here gives Nextcloud exactly the class name it
 * derives from the route, while the behaviour stays OpenRegister's (ADR-022 —
 * consume, don't reimplement).
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/specs/preferences-api/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Serves `GET|PUT /api/preferences/{key}` for DocuDesk.
 *
 * Behaviour (auth posture, key sanitisation, per-user scoping) is a
 * byte-for-byte reimplementation of OpenRegister's generic. `#[NoAdminRequired]`
 * was previously INHERITED from the generic; it is declared explicitly on both
 * methods here so the auth posture is unchanged by dropping the inheritance —
 * any logged-in user may read and write their OWN preferences, and the
 * `$user === null` guard still rejects anonymous callers with 401.
 */
class PreferencesController extends Controller
{
    /**
     * Constructor.
     *
     * @param IRequest     $request     The request object.
     * @param IConfig      $config      Nextcloud config service (per-user values).
     * @param IUserSession $userSession The current user session.
     *
     * @return void
     */
    public function __construct(
        IRequest $request,
        private readonly IConfig $config,
        private readonly IUserSession $userSession,
    ) {
        parent::__construct(appName: Application::APP_ID, request: $request);

    }//end __construct()

    /**
     * GET /api/preferences/{key} — read one preference for the current user.
     *
     * @param string $key The preference key (sanitised before use).
     *
     * @return JSONResponse `{value: string|null}`, or 401/400 on a bad caller/key.
     *
     * @spec openspec/specs/preferences-api/spec.md
     */
    #[NoAdminRequired]
    public function getPreference(string $key): JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(data: ['message' => 'Not logged in'], statusCode: Http::STATUS_UNAUTHORIZED);
        }

        $safeKey = $this->sanitizeKey(key: $key);
        if ($safeKey === '') {
            return new JSONResponse(data: ['message' => 'Invalid key'], statusCode: Http::STATUS_BAD_REQUEST);
        }

        $value = $this->config->getUserValue(
            userId: $user->getUID(),
            appName: $this->appName,
            key: 'pref_'.$safeKey,
            default: ''
        );

        $stored = null;
        if ($value !== '') {
            $stored = $value;
        }

        return new JSONResponse(data: ['value' => $stored]);

    }//end getPreference()

    /**
     * PUT /api/preferences/{key} — write (or clear) one preference.
     *
     * An empty `$value` deletes the stored preference, matching the generic.
     *
     * @param string $key   The preference key (sanitised before use).
     * @param string $value The value to store; empty string clears it.
     *
     * @return JSONResponse `{value: string|null}`, or 401/400 on a bad caller/key.
     *
     * @spec openspec/specs/preferences-api/spec.md
     */
    #[NoAdminRequired]
    public function setPreference(string $key, string $value=''): JSONResponse
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new JSONResponse(data: ['message' => 'Not logged in'], statusCode: Http::STATUS_UNAUTHORIZED);
        }

        $safeKey = $this->sanitizeKey(key: $key);
        if ($safeKey === '') {
            return new JSONResponse(data: ['message' => 'Invalid key'], statusCode: Http::STATUS_BAD_REQUEST);
        }

        if ($value === '') {
            $this->config->deleteUserValue(
                userId: $user->getUID(),
                appName: $this->appName,
                key: 'pref_'.$safeKey
            );

            return new JSONResponse(data: ['value' => null]);
        }

        $this->config->setUserValue(
            userId: $user->getUID(),
            appName: $this->appName,
            key: 'pref_'.$safeKey,
            value: $value
        );

        return new JSONResponse(data: ['value' => $value]);

    }//end setPreference()

    /**
     * Reduce a caller-supplied key to a safe, bounded storage key.
     *
     * Lowercased, restricted to `[a-z0-9-]`, capped at 64 characters.
     *
     * @param string $key The raw key from the URL.
     *
     * @return string The sanitised key, or '' when nothing survives.
     */
    private function sanitizeKey(string $key): string
    {
        $safe = preg_replace(pattern: '/[^a-z0-9-]/', replacement: '', subject: strtolower($key));

        return substr((string) $safe, offset: 0, length: 64);

    }//end sanitizeKey()
}//end class
