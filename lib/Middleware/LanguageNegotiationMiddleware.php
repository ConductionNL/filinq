<?php

/**
 * DocuDesk Language Negotiation Middleware
 *
 * Bridges OpenRegister's request-scoped `LanguageService` to DocuDesk's
 * controllers. When a docudesk endpoint is hit (e.g. `/apps/docudesk/api/...`),
 * the OR `LanguageMiddleware` is NOT invoked because Nextcloud only runs
 * middleware registered by the app handling the route. This middleware
 * replays OR's negotiation rules — query overrides → Accept-Language →
 * default — directly on OR's `LanguageService`, so subsequent OR calls
 * made by docudesk services (via `ObjectService`) see the correct
 * preferred language and the `TranslationHandler` resolves translatable
 * properties to the right variant.
 *
 * Honours, in priority order (mirrors OR's LanguageMiddleware):
 *   1. `?_lang=<bcp47>` query parameter (canonical override)
 *   2. `?language=<bcp47>` query parameter (alias)
 *   3. `Accept-Language` header (RFC 9110)
 *   4. Register default (resolved at render time by OR)
 *   5. Hardcoded `'nl'` fallback (OR default)
 *
 * Also forwards the write-side `X-Translation-Target-Language` header on
 * POST/PUT/PATCH so OR's `TranslationHandler::normalizeTranslationsForSave()`
 * stores translatable values under the intended language code.
 *
 * Emits `Content-Language` + `X-Content-Language-Fallback` on responses,
 * matching the OR contract documented in
 * `openregister/openspec/specs/register-i18n/spec.md`.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Middleware
 * @package  OCA\DocuDesk\Middleware
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-3-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Middleware;

use OCA\OpenRegister\Service\LanguageService;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Bridges OpenRegister's LanguageService into DocuDesk's controller
 * request lifecycle.
 *
 * @package OCA\DocuDesk\Middleware
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class LanguageNegotiationMiddleware extends Middleware
{

    /**
     * Basic BCP-47 syntax check used to discard malformed overrides.
     *
     * Lax by design — never 400 on a malformed tag; fall through.
     *
     * @var string
     */
    private const BCP47_PATTERN = '/^[a-z]{2,3}(-[a-zA-Z0-9]{2,8})*$/';

    /**
     * Constructor.
     *
     * @param IRequest        $request         The incoming request.
     * @param LanguageService $languageService Request-scoped OR language service.
     * @param LoggerInterface $logger          Logger for invalid-tag warnings.
     */
    public function __construct(
        private readonly IRequest $request,
        private readonly LanguageService $languageService,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Resolve the preferred language and write-side target language
     * from the incoming request and stash them on OR's LanguageService.
     *
     * @param mixed  $controller The controller instance.
     * @param string $methodName The method name being called.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-3-2
     */
    public function beforeController($controller, $methodName): void
    {
        // 1. Query-parameter overrides take precedence over Accept-Language.
        $resolvedFromQuery = $this->resolveFromQueryParams();
        if ($resolvedFromQuery !== null) {
            $this->languageService->setPreferredLanguage($resolvedFromQuery);
            $this->languageService->setRequestedLanguageSource('query');
        }

        // 2. Accept-Language header is the next priority.
        $acceptLanguage = $this->request->getHeader('Accept-Language');
        if ($acceptLanguage !== '' && $acceptLanguage !== null) {
            $acceptedLanguages = LanguageService::parseAcceptLanguageHeader($acceptLanguage);
            $this->languageService->setAcceptedLanguages($acceptedLanguages);

            if (empty($acceptedLanguages) === false && $resolvedFromQuery === null) {
                $preferred = strtolower(explode('-', $acceptedLanguages[0])[0]);
                $this->languageService->setPreferredLanguage($preferred);
                $this->languageService->setRequestedLanguageSource('header');
            }
        }

        // 3. _translations=all (return-all override).
        $translations = $this->request->getParam('_translations');
        if ($translations === 'all') {
            $this->languageService->setReturnAllTranslations(true);
        }

        // 4. Write-side X-Translation-Target-Language on mutating verbs.
        $method = strtoupper((string) $this->request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) === true) {
            $targetHeader = $this->request->getHeader('X-Translation-Target-Language');
            if ($targetHeader !== '' && $targetHeader !== null) {
                $targetTrim = trim($targetHeader);
                if (preg_match(self::BCP47_PATTERN, $targetTrim) !== 1) {
                    $this->logger->warning(
                        sprintf(
                            '[DocuDesk LanguageNegotiationMiddleware] Invalid X-Translation-Target-Language "%s" — ignoring.',
                            $targetTrim
                        )
                    );
                    return;
                }

                $this->languageService->setTargetLanguage($targetTrim);
            }
        }
    }//end beforeController()

    /**
     * Emit language response headers so docudesk responses surface the
     * resolved language the same way OR responses do.
     *
     * @param mixed    $controller The controller instance.
     * @param string   $methodName The method name that was called.
     * @param Response $response   The response object.
     *
     * @return Response The modified response with language headers.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterController($controller, $methodName, Response $response): Response
    {
        $language = $this->languageService->getPreferredLanguage();
        $response->addHeader('Content-Language', $language);

        if ($this->languageService->isFallbackUsed() === true) {
            $response->addHeader('X-Content-Language-Fallback', 'true');
        }

        return $response;
    }//end afterController()

    /**
     * Resolve a language from `?_lang=` or `?language=`, in that order.
     *
     * Returns null when neither is set, or when neither value passes
     * basic BCP-47 syntax validation. Invalid tags log a warning and
     * cause the lookup to fall through to the next priority level —
     * we never 400 on a malformed language tag.
     *
     * @return string|null The resolved tag, or null when no valid query override is present.
     */
    private function resolveFromQueryParams(): ?string
    {
        foreach (['_lang', 'language'] as $name) {
            $value = $this->request->getParam($name);
            if ($value === null || $value === '') {
                continue;
            }

            if (is_string($value) === false) {
                continue;
            }

            $trimmed = trim($value);
            if (preg_match(self::BCP47_PATTERN, $trimmed) !== 1) {
                $this->logger->warning(
                        sprintf(
                    "[DocuDesk LanguageNegotiationMiddleware] Invalid ?%s value '%s' — falling through",
                    $name,
                    $trimmed
                )
                        );
                continue;
            }

            return $trimmed;
        }//end foreach

        return null;
    }//end resolveFromQueryParams()
}//end class
