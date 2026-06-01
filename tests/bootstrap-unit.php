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

// Load OpenRegister stubs for mocking.
require_once __DIR__.'/stubs/OpenRegisterStubs.php';
