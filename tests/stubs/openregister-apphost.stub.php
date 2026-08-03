<?php

/**
 * PHPStan scan stub for OpenRegister's AppHost controller engine (ADR-040).
 *
 * Analysis-only — referenced from phpstan.neon `scanFiles` and NEVER loaded at
 * runtime (the runtime classes live in the openregister sibling app, which is
 * co-installed on the Nextcloud instance but is not a composer dependency of
 * this app and is therefore absent from the static-analysis path).
 *
 * Why a stub rather than a suppression: PHPStan refuses to let an
 * "extends unknown class" error be silenced through `ignoreErrors`, so the
 * `OCA\DocuDesk\Controller\PreferencesController` subclass had no way to
 * resolve its parent. Declaring the parent's real signature here is the
 * truthful fix — it also makes PHPStan verify that the named arguments
 * `Application::register()` passes to `new GenericPreferencesController(...)`
 * actually match the engine constructor, which a suppression would not.
 *
 * The signature below mirrors
 * `openregister/lib/AppHost/Controller/GenericPreferencesController.php`
 * verbatim; keep the two in sync when the engine contract changes.
 *
 * @category Test
 * @package  OCA\OpenRegister\AppHost\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\AppHost\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * PHPStan-only stub for the AppHost generic per-user preferences controller.
 *
 * The real class lives in the openregister sibling app (ADR-040) and serves
 * `GET|PUT /api/preferences/{key}` for every adopting leaf app.
 */
class GenericPreferencesController extends Controller
{
    /**
     * Construct the generic preferences controller.
     *
     * @param string       $appName     The calling (leaf) app id.
     * @param IRequest     $request     HTTP request.
     * @param IConfig      $config      The Nextcloud config (user values).
     * @param IUserSession $userSession The user session.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IConfig $config,
        private readonly IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }//end __construct()

    /**
     * Read a per-user preference value for the current user.
     *
     * @param string $key The preference key.
     *
     * @return JSONResponse `{value: string|null}`.
     */
    public function getPreference(string $key): JSONResponse
    {
        unset($key);

        return new JSONResponse(['value' => null]);
    }//end getPreference()

    /**
     * Write a per-user preference value for the current user.
     *
     * @param string $key   The preference key.
     * @param string $value The value to store (empty string clears it).
     *
     * @return JSONResponse `{value: string|null}`.
     */
    public function setPreference(string $key, string $value=''): JSONResponse
    {
        unset($key, $value);

        return new JSONResponse(['value' => null]);
    }//end setPreference()
}//end class
