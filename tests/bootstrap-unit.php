<?php
/**
 * Bootstrap file for standalone PHPUnit tests (no Nextcloud server required)
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../vendor/autoload.php';

// Load global-namespace stubs first (\OC class, etc.).
require_once __DIR__.'/stubs/GlobalStubs.php';

// Load Nextcloud OCP stubs (no NC server required).
require_once __DIR__.'/stubs/NextcloudStubs.php';

// Load OCP event-dispatcher contracts before OR stubs that reference them
// (Event / IEventDispatcher / IEventListener). The OCP package ships them
// in vendor/nextcloud/ocp but does not classmap-autoload, so we require
// them by hand to keep the OR stubs file self-contained.
$ocpEventDispatcherDir = __DIR__ . '/../vendor/nextcloud/ocp/OCP/EventDispatcher';
if (is_dir($ocpEventDispatcherDir) === true) {
    foreach (['Event.php', 'IEventListener.php', 'IEventDispatcher.php', 'ABroadcastedEvent.php', 'GenericEvent.php'] as $ocpEventFile) {
        $ocpEventPath = $ocpEventDispatcherDir . '/' . $ocpEventFile;
        if (is_file($ocpEventPath) === true) {
            require_once $ocpEventPath;
        }
    }
}

// Load OCP's real AppFramework\Db exception types (DoesNotExistException +
// its IMapperException contract) — CustomDictionaryController /
// CustomDictionaryService (custom-dictionary-recognition) throw/catch the
// real class so a 404 maps correctly; same "real file, not classmapped"
// situation as the EventDispatcher contracts above.
$ocpDbExceptionDir = __DIR__ . '/../vendor/nextcloud/ocp/OCP/AppFramework/Db';
if (is_dir($ocpDbExceptionDir) === true) {
    foreach (['IMapperException.php', 'DoesNotExistException.php'] as $ocpDbFile) {
        $ocpDbPath = $ocpDbExceptionDir . '/' . $ocpDbFile;
        if (is_file($ocpDbPath) === true) {
            require_once $ocpDbPath;
        }
    }
}

// Load OCP's public system-tag API (files_confidential label consumption,
// files-confidential-labels) — same "real file, not classmapped" situation
// as the EventDispatcher/AppFramework\Db contracts above.
$ocpSystemTagDir = __DIR__ . '/../vendor/nextcloud/ocp/OCP/SystemTag';
if (is_dir($ocpSystemTagDir) === true) {
    foreach (['ISystemTag.php', 'TagNotFoundException.php', 'ISystemTagManager.php', 'ISystemTagObjectMapper.php'] as $ocpTagFile) {
        $ocpTagPath = $ocpSystemTagDir . '/' . $ocpTagFile;
        if (is_file($ocpTagPath) === true) {
            require_once $ocpTagPath;
        }
    }
}

// Load OpenRegister stubs for mocking.
require_once __DIR__.'/stubs/OpenRegisterStubs.php';

// Shared test-only trait. The composer PSR-4 dev prefix maps
// OCA\DocuDesk\Tests\ to tests/, which cannot resolve the lower-cased
// tests/unit/ directory segment, so non-test helper classes under tests/unit
// are required explicitly (PHPUnit loads *Test.php files by path).
require_once __DIR__.'/unit/Service/BuildsAnonymizationService.php';
