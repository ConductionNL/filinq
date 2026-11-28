<?php

/**
 * The Middleware component's registrations
 *
 * @category  Middleware
 * @package   OCA\Filinq\Middleware
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/api-contract/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Middleware;

use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers this app's middleware, in order. The AppInfo bootstrap calls this
 * instead of naming each middleware.
 *
 * @category Middleware
 * @package  OCA\Filinq\Middleware
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/api-contract/spec.md
 */
final class MiddlewareRegistrar {

	/**
	 * Register the middleware.
	 *
	 * @param IRegistrationContext $context Registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/register-i18n/spec.md
	 * @spec openspec/specs/api-contract/spec.md
	 */
	public function register(IRegistrationContext $context): void {
		// The register-i18n adoption (Task 3.2): wire the filinq-side
		// language-negotiation middleware so OR's `LanguageService`
		// sees Accept-Language / ?_lang / X-Translation-Target-Language
		// on requests that hit filinq routes (the OR LanguageMiddleware
		// only fires for OR's own routes). This lets the OR
		// TranslationHandler resolve translatable properties on filinq
		// objects to the right variant.
		$context->registerMiddleware(LanguageNegotiationMiddleware::class);

		// API-Version header and problem details on the JSON API (spec api-contract).
		$context->registerMiddleware(ApiContractMiddleware::class);
	}//end register()
}//end class
