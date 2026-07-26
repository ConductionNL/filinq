<?php

/**
 * Per-user preferences controller.
 *
 * Thin subclass of OpenRegister's AppHost `GenericPreferencesController`,
 * mirroring the existing `HealthController` / `MetricsController` pattern.
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
 * @author  Conduction Development Team <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/specs/preferences-api/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\OpenRegister\AppHost\Controller\GenericPreferencesController;

/**
 * Serves `GET|PUT /api/preferences/{key}` for DocuDesk.
 *
 * Behaviour (auth posture, key sanitisation, per-user scoping) is inherited
 * unchanged from OpenRegister's generic implementation.
 */
class PreferencesController extends GenericPreferencesController
{

}//end class
