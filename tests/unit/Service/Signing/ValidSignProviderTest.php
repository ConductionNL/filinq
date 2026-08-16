<?php

/**
 * Unit tests for ValidSignProvider
 *
 * Covers identifier, level support, and the stub behaviour of initiateSigning,
 * checkStatus and downloadSignedDocument per REQ-SIGN-03.
 *
 * @category  Tests
 * @package   OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-signing/tasks.md#2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Signing;

use OCA\DocuDesk\Service\Signing\ValidSignProvider;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for ValidSignProvider stub implementation
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ValidSignProviderTest extends TestCase {

	/**
	 * @var IAppConfig|MockObject
	 */
	private IAppConfig|MockObject $config;

	/**
	 * @var ValidSignProvider
	 */
	private ValidSignProvider $provider;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IAppConfig::class);

		$this->provider = new ValidSignProvider(
			config: $this->config
		);

	}//end setUp()

	/**
	 * getIdentifier() returns the canonical 'validsign' identifier.
	 *
	 * @return void
	 */
	public function testGetIdentifierReturnsValidsign(): void {
		$this->assertSame('validsign', $this->provider->getIdentifier());

	}//end testGetIdentifierReturnsValidsign()

	/**
	 * supportsLevel() returns true for all three eIDAS levels.
	 *
	 * @return void
	 */
	public function testSupportsLevelForEidasLevels(): void {
		$this->assertTrue($this->provider->supportsLevel(level: 'SES'));
		$this->assertTrue($this->provider->supportsLevel(level: 'AdES'));
		$this->assertTrue($this->provider->supportsLevel(level: 'QES'));

	}//end testSupportsLevelForEidasLevels()

	/**
	 * supportsLevel() returns false for unknown levels.
	 *
	 * @return void
	 */
	public function testSupportsLevelReturnsFalseForUnknown(): void {
		$this->assertFalse($this->provider->supportsLevel(level: 'UNKNOWN'));
		$this->assertFalse($this->provider->supportsLevel(level: ''));
		$this->assertFalse($this->provider->supportsLevel(level: 'ses'));

	}//end testSupportsLevelReturnsFalseForUnknown()

	/**
	 * initiateSigning() throws when the provider is not configured (no sourceId).
	 *
	 * @return void
	 */
	public function testInitiateSigningThrowsWhenNotConfigured(): void {
		$this->config->method('getValueString')->willReturn('{}');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('ValidSign provider is not configured');

		$this->provider->initiateSigning(
			documentPath: '/tmp/test.pdf',
			documentName: 'test.pdf',
			signers: [['userId' => 'alice']],
			level: 'SES'
		);

	}//end testInitiateSigningThrowsWhenNotConfigured()

	/**
	 * initiateSigning() returns a success result when sourceId is configured.
	 *
	 * @return void
	 */
	public function testInitiateSigningSucceedsWhenConfigured(): void {
		$this->config->method('getValueString')->willReturn(json_encode(['sourceId' => 'vs-source-123']));

		$result = $this->provider->initiateSigning(
			documentPath: '/tmp/test.pdf',
			documentName: 'test.pdf',
			signers: [['userId' => 'alice']],
			level: 'SES'
		);

		$this->assertTrue($result['success']);
		$this->assertArrayHasKey('externalId', $result);
		$this->assertStringStartsWith('validsign-', $result['externalId']);

	}//end testInitiateSigningSucceedsWhenConfigured()

	/**
	 * checkStatus() returns a pending status response (stub).
	 *
	 * @return void
	 */
	public function testCheckStatusReturnsPendingStub(): void {
		$result = $this->provider->checkStatus(externalId: 'validsign-abc123');

		$this->assertSame('pending', $result['status']);
		$this->assertIsArray($result['signers']);
		$this->assertNull($result['completedAt']);

	}//end testCheckStatusReturnsPendingStub()

	/**
	 * downloadSignedDocument() always throws (not yet implemented).
	 *
	 * @return void
	 */
	public function testDownloadSignedDocumentAlwaysThrows(): void {
		$this->expectException(RuntimeException::class);

		$this->provider->downloadSignedDocument(externalId: 'validsign-abc123');

	}//end testDownloadSignedDocumentAlwaysThrows()
}//end class
