<?php

/**
 * Template Language Service
 *
 * Resolves language preferences for template content retrieval and
 * provides utilities for working with multi-language template metadata.
 * Implements the fallback chain defined in REQ-I18N-011:
 * user language → app default → nl → en → first available.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCP\IConfig;
use OCP\IUser;

/**
 * Service for template language preference resolution.
 *
 * Implements the REQ-I18N-011 fallback chain and provides helpers
 * for detecting available languages in translatable template fields.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/register-i18n/tasks.md#task-1
 */
class TemplateLanguageService
{

    /**
     * Supported languages for the templates register.
     *
     * @var string[]
     */
    public const SUPPORTED_LANGUAGES = ['nl', 'en'];

    /**
     * Default language when no preference is available.
     *
     * @var string
     */
    public const DEFAULT_LANGUAGE = 'nl';

    /**
     * Constructor.
     *
     * @param IConfig $config System configuration for reading user language
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-1
     */
    public function __construct(
        private readonly IConfig $config,
    ) {
    }//end __construct()

    /**
     * Resolve the preferred language for a user following the fallback chain.
     *
     * Fallback order (REQ-I18N-011):
     *   1. User's Nextcloud language preference
     *   2. 'nl' (Dutch — primary language per REQ-I18N-050)
     *   3. 'en' (English — minimum secondary per REQ-I18N-051)
     *
     * @param IUser|null $user The authenticated Nextcloud user, or null for guests
     *
     * @return string BCP 47 language code (e.g. 'nl', 'en')
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-1
     */
    public function resolveUserLanguage(?IUser $user): string
    {
        if ($user === null) {
            return self::DEFAULT_LANGUAGE;
        }

        $userLang = $this->config->getUserValue(
            userId: $user->getUID(),
            appName: 'core',
            key: 'lang',
            default: ''
        );

        // Strip region subtag (e.g. 'en_US' → 'en', 'nl_NL' → 'nl').
        $base = strtolower(explode('_', explode('-', $userLang)[0])[0]);

        if ($base !== '' && in_array($base, self::SUPPORTED_LANGUAGES, strict: true) === true) {
            return $base;
        }

        return self::DEFAULT_LANGUAGE;

    }//end resolveUserLanguage()

    /**
     * Extract available language codes from a translatable template field.
     *
     * When OR stores a translatable value, it uses a language-keyed array:
     * {"nl": "Beschikking", "en": "Decision"}. This method detects that
     * structure and returns the available language codes.
     *
     * @param mixed $fieldValue The raw value of a translatable field
     *
     * @return string[] Array of BCP 47 language codes available in the field
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-1
     */
    public function getAvailableLanguages(mixed $fieldValue): array
    {
        if (is_array($fieldValue) === false) {
            return [];
        }

        $keys = array_keys($fieldValue);

        return array_values(
            array_filter($keys, static fn($k) => is_string($k) && strlen($k) >= 2 && strlen($k) <= 8)
        );

    }//end getAvailableLanguages()

    /**
     * Resolve the best available language for a translatable field value.
     *
     * Implements the fallback chain: preferred → nl → en → first available.
     *
     * @param mixed  $fieldValue        The raw translatable field (string or language-keyed array)
     * @param string $preferredLanguage The caller's preferred BCP 47 language code
     *
     * @return array{value: string, language: string, fallback: bool} Resolved value with metadata
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-1
     */
    public function resolveFieldValue(mixed $fieldValue, string $preferredLanguage): array
    {
        // Plain string — not yet language-keyed, serve as-is.
        if (is_string($fieldValue) === true) {
            return ['value' => $fieldValue, 'language' => self::DEFAULT_LANGUAGE, 'fallback' => false];
        }

        if (is_array($fieldValue) === false || empty($fieldValue) === true) {
            return ['value' => '', 'language' => self::DEFAULT_LANGUAGE, 'fallback' => false];
        }

        // Try preferred language.
        if (isset($fieldValue[$preferredLanguage]) === true) {
            return ['value' => $fieldValue[$preferredLanguage], 'language' => $preferredLanguage, 'fallback' => false];
        }

        // Fallback chain: nl → en → first available.
        foreach (['nl', 'en'] as $fallback) {
            if (isset($fieldValue[$fallback]) === true) {
                return ['value' => $fieldValue[$fallback], 'language' => $fallback, 'fallback' => true];
            }
        }

        $firstKey   = array_key_first($fieldValue);
        $firstValue = (string) ($fieldValue[$firstKey] ?? '');

        return ['value' => $firstValue, 'language' => (string) $firstKey, 'fallback' => true];

    }//end resolveFieldValue()

    /**
     * Build an Accept-Language header value from a preferred language code.
     *
     * Produces "nl, en;q=0.9" when preferred is 'nl', or "en, nl;q=0.9" when 'en',
     * so OR's LanguageMiddleware negotiates correctly.
     *
     * @param string $preferred The caller's preferred BCP 47 language code
     *
     * @return string RFC 9110 Accept-Language header value
     *
     * @spec openspec/changes/register-i18n/tasks.md#task-1
     */
    public function buildAcceptLanguageHeader(string $preferred): string
    {
        $others = array_filter(
            self::SUPPORTED_LANGUAGES,
            static fn($lang) => $lang !== $preferred
        );

        $parts = [$preferred];
        $q     = 0.9;

        foreach ($others as $lang) {
            $parts[] = sprintf('%s;q=%.1f', $lang, $q);
            $q      -= 0.1;
        }

        return implode(', ', $parts);

    }//end buildAcceptLanguageHeader()
}//end class
