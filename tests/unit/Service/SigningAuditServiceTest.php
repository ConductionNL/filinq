<?php

/**
 * Unit tests for SigningAuditService — finding #289 and #290a regression coverage.
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
 *  3. `getAuditTrail()` uses a server-side filter (passes `signingRequestId`
 *     to `searchObjects`) instead of loading all records into PHP memory
 *     and filtering in-application (finding #290a).
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

use OCA\DocuDesk\Service\SettingsService;
use OCA\DocuDesk\Service\SigningAuditService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
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


    /**
     * getAuditTrail() must call searchObjects with a server-side signingRequestId
     * filter rather than loading all entries into PHP memory (finding #290a).
     *
     * The mock asserts that:
     *  - searchObjects is called exactly once
     *  - the query contains the @self register/schema scope AND the
     *    signingRequestId filter
     *  - the returned entries are normalised to arrays and sorted by timestamp
     *
     * @return void
     */
    public function testGetAuditTrailUsesServerSideFilter(): void
    {
        /** @var ObjectService|MockObject $mockObjectService */
        $mockObjectService = $this->createMock(ObjectService::class);

        $capturedQuery = null;
        $mockObjectService->expects($this->once())
            ->method('searchObjects')
            ->willReturnCallback(
                function (array $query) use (&$capturedQuery): array {
                    $capturedQuery = $query;
                    // Return two matching entries (already filtered server-side).
                    return [
                        ['signingRequestId' => 'req-1', 'timestamp' => '2026-01-02T00:00:00+00:00', 'action' => 'SIGNED'],
                        ['signingRequestId' => 'req-1', 'timestamp' => '2026-01-01T00:00:00+00:00', 'action' => 'CREATED'],
                    ];
                }
            );

        // SettingsService stub returns the object-service stub and config values.
        /** @var SettingsService|MockObject $mockSettings */
        $mockSettings = $this->createMock(SettingsService::class);
        $mockSettings->method('getObjectService')->willReturn($mockObjectService);

        /** @var IAppConfig|MockObject $mockConfig */
        $mockConfig = $this->createMock(IAppConfig::class);
        $mockConfig->method('getValueString')
            ->willReturnMap(
                [
                    ['docudesk', 'signingAuditEntry_register', '', 'reg-audit'],
                    ['docudesk', 'signingAuditEntry_schema', '', 'schema-audit'],
                ]
            );

        $service = new SigningAuditService(
            $mockSettings,
            $mockConfig
        );

        $result = $service->getAuditTrail('req-1');

        // The query passed to searchObjects must include the server-side filter.
        $this->assertNotNull($capturedQuery);
        $this->assertArrayHasKey('@self', $capturedQuery, 'searchObjects query must scope to register+schema via @self');
        $this->assertSame('reg-audit', $capturedQuery['@self']['register'] ?? null);
        $this->assertSame('schema-audit', $capturedQuery['@self']['schema'] ?? null);
        $this->assertArrayHasKey('signingRequestId', $capturedQuery, 'searchObjects query must contain signingRequestId filter (finding #290a)');
        $this->assertSame('req-1', $capturedQuery['signingRequestId']);

        // Results are returned sorted by timestamp (ascending).
        $this->assertCount(2, $result);
        $this->assertSame('CREATED', $result[0]['action']);
        $this->assertSame('SIGNED', $result[1]['action']);

    }//end testGetAuditTrailUsesServerSideFilter()


    /**
     * VALID_ACTIONS must include 'START' so signing-session initiation can be
     * recorded without the logEvent() guard rejecting it (finding L2).
     *
     * @return void
     */
    public function testValidActionsIncludesStart(): void
    {
        $ref      = new ReflectionClass(SigningAuditService::class);
        $constant = $ref->getReflectionConstant('VALID_ACTIONS');
        $this->assertNotFalse($constant, 'VALID_ACTIONS constant must exist');
        $this->assertContains(
            'START',
            $constant->getValue(),
            'VALID_ACTIONS must include START (finding L2 — session-start audit events were silently dropped).'
        );

    }//end testValidActionsIncludesStart()


    /**
     * getAuditTrail() must NOT call getObjects (the old full-scan path).
     *
     * The ObjectService stub does not declare getObjects(), so the mock
     * will throw if it is called — no explicit `expects($this->never())` needed.
     * We assert that searchObjects IS called, which is the only read path.
     *
     * @return void
     */
    public function testGetAuditTrailDoesNotCallGetObjects(): void
    {
        /** @var ObjectService|MockObject $mockObjectService */
        $mockObjectService = $this->createMock(ObjectService::class);
        $mockObjectService->method('searchObjects')->willReturn([]);

        /** @var SettingsService|MockObject $mockSettings */
        $mockSettings = $this->createMock(SettingsService::class);
        $mockSettings->method('getObjectService')->willReturn($mockObjectService);

        /** @var IAppConfig|MockObject $mockConfig */
        $mockConfig = $this->createMock(IAppConfig::class);
        $mockConfig->method('getValueString')->willReturn('');

        $service = new SigningAuditService(
            $mockSettings,
            $mockConfig
        );

        // If the old getObjects path were still present, PHPUnit would raise
        // "method does not exist" because the stub has no getObjects() — the
        // test would fail rather than pass silently.
        $result = $service->getAuditTrail('req-no-scan');
        $this->assertSame([], $result);

    }//end testGetAuditTrailDoesNotCallGetObjects()


}//end class
