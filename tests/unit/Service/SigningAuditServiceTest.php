<?php

/**
 * Unit tests for SigningAuditService — finding #289 regression coverage.
 *
 * Asserts:
 *  1. The dead-code `rejectUpdate()` / `rejectDelete()` methods no longer
 *     exist on the service — they were misleading (never wired into any
 *     mutation path) and gave the false impression that immutability was
 *     enforced in-app.
 *  2. The `signingAuditEntry` schema in `lib/Settings/docudesk_register.json`
 *     carries BOTH `immutable: true` and `appendOnly: true`, which is the
 *     storage-layer guard that actually enforces Archiefwet 1995
 *     immutability of audit records.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\SigningAuditService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for SigningAuditService immutability enforcement
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SigningAuditServiceTest extends TestCase
{


    /**
     * Dead-code `rejectUpdate()` no longer exists on the service
     *
     * @return void
     */
    public function testRejectUpdateMethodRemoved(): void
    {
        $ref = new ReflectionClass(SigningAuditService::class);
        $this->assertFalse(
            $ref->hasMethod('rejectUpdate'),
            'SigningAuditService::rejectUpdate() should be removed (finding #289 — '
            . 'dead code, never wired into any mutation path; immutability is '
            . 'enforced at the OR storage layer instead).'
        );

    }//end testRejectUpdateMethodRemoved()


    /**
     * Dead-code `rejectDelete()` no longer exists on the service
     *
     * @return void
     */
    public function testRejectDeleteMethodRemoved(): void
    {
        $ref = new ReflectionClass(SigningAuditService::class);
        $this->assertFalse(
            $ref->hasMethod('rejectDelete'),
            'SigningAuditService::rejectDelete() should be removed (finding #289).'
        );

    }//end testRejectDeleteMethodRemoved()


    /**
     * `logEvent()` and `getAuditTrail()` (the real surface) are still present
     *
     * @return void
     */
    public function testRealSurfaceStillPresent(): void
    {
        $ref = new ReflectionClass(SigningAuditService::class);
        $this->assertTrue($ref->hasMethod('logEvent'));
        $this->assertTrue($ref->hasMethod('getAuditTrail'));

    }//end testRealSurfaceStillPresent()


    /**
     * The audit schema in docudesk_register.json declares `immutable: true`
     * AND `appendOnly: true` — the storage-layer guard.
     *
     * @return void
     */
    public function testAuditSchemaIsImmutableAndAppendOnly(): void
    {
        $registerPath = __DIR__ . '/../../../lib/Settings/docudesk_register.json';
        $this->assertFileExists($registerPath);

        $raw     = file_get_contents($registerPath);
        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);

        $schemas = $decoded['components']['schemas'] ?? [];
        $this->assertArrayHasKey('signingAuditEntry', $schemas);

        $audit = $schemas['signingAuditEntry'];
        $this->assertTrue(
            $audit['immutable'] ?? false,
            'signingAuditEntry must declare immutable: true (Archiefwet 1995).'
        );
        $this->assertTrue(
            $audit['appendOnly'] ?? false,
            'signingAuditEntry must declare appendOnly: true so the OR storage '
            . 'layer rejects update/delete of existing audit entries (finding #289).'
        );

    }//end testAuditSchemaIsImmutableAndAppendOnly()


    /**
     * The audit register now also lists the schema (sanity check).
     *
     * @return void
     */
    public function testSigningRegisterIncludesAuditSchema(): void
    {
        $registerPath = __DIR__ . '/../../../lib/Settings/docudesk_register.json';
        $decoded      = json_decode(file_get_contents($registerPath), true);

        $signingSchemas = $decoded['components']['registers']['signing']['schemas'] ?? [];
        $this->assertContains('signingAuditEntry', $signingSchemas);

    }//end testSigningRegisterIncludesAuditSchema()


}//end class
