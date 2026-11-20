<?php

/**
 * Unit tests for the anonymisation profiles per document type
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/specs/batch-anonymization/spec.md#requirement-an-anonymisation-profile-is-kept-per-document-type-req-scr-013
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\WooProfileService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * A review of a document of a type starts from that type's profile; any
 * other document uses the default profile.
 *
 * @covers \OCA\Filinq\Service\WooProfileService
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class WooProfileTypeProfilesTest extends TestCase {

	/**
	 * The app config values, by key.
	 *
	 * @var array<string, string>
	 */
	private array $store = [];

	/**
	 * The service over an in-memory app config.
	 *
	 * @return WooProfileService
	 */
	private function service(): WooProfileService {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => $this->store[$key] ?? $default
		);
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->store[$key] = $value;
				return true;
			}
		);

		return new WooProfileService(appConfig: $config);

	}//end service()

	/**
	 * An invoice uses the invoice profile: organisations kept, bank account numbers anonymised.
	 *
	 * @return void
	 */
	public function testAnInvoiceUsesTheInvoiceProfile(): void {
		$service = $this->service();
		$service->saveProfileFor(documentType: ' invoice ', profile: ['anonymize' => ['IBAN', 'PERSON'], 'keep' => ['ORGANIZATION']]);

		$this->assertSame(['anonymize' => ['IBAN', 'PERSON'], 'keep' => ['ORGANIZATION']], $service->getProfileFor(documentType: 'invoice'));
		$this->assertTrue($service->shouldAnonymize(entityType: 'IBAN', documentType: 'invoice'));
		$this->assertFalse($service->shouldAnonymize(entityType: 'ORGANIZATION', documentType: 'invoice'));
		$this->assertFalse($service->shouldAnonymize(entityType: 'EMAIL', documentType: 'invoice'), 'the type profile replaces the default, it does not add to it');

	}//end testAnInvoiceUsesTheInvoiceProfile()

	/**
	 * A type without a profile, and a document without a type, use the default profile.
	 *
	 * @return void
	 */
	public function testAnyOtherDocumentUsesTheDefaultProfile(): void {
		$service = $this->service();
		$service->saveProfile(profile: ['anonymize' => ['BSN'], 'keep' => ['DATE']]);
		$service->saveProfileFor(documentType: 'invoice', profile: ['anonymize' => ['IBAN'], 'keep' => []]);

		$this->assertSame(['anonymize' => ['BSN'], 'keep' => ['DATE']], $service->getProfileFor(documentType: 'letter'));
		$this->assertSame(['anonymize' => ['BSN'], 'keep' => ['DATE']], $service->getProfileFor(documentType: null));
		$this->assertSame(['anonymize' => ['BSN'], 'keep' => ['DATE']], $service->getProfileFor(documentType: '  '));
		$this->assertTrue($service->shouldAnonymize(entityType: 'BSN'), 'without a type the default profile still decides');

	}//end testAnyOtherDocumentUsesTheDefaultProfile()

	/**
	 * Several profiles are kept side by side, listed by type, and one can be unlinked.
	 *
	 * @return void
	 */
	public function testSeveralProfilesAreKeptAndOneCanBeUnlinked(): void {
		$service = $this->service();
		$service->saveProfileFor(documentType: 'permit', profile: ['anonymize' => ['ADDRESS'], 'keep' => []]);
		$service->saveProfileFor(documentType: 'invoice', profile: ['anonymize' => ['IBAN'], 'keep' => []]);
		$service->saveProfileFor(documentType: 'invoice', profile: ['anonymize' => ['IBAN', 'PHONE'], 'keep' => []]);

		$this->assertSame(['invoice', 'permit'], array_keys($service->typeProfiles()));
		$this->assertSame(['IBAN', 'PHONE'], $service->getProfileFor(documentType: 'invoice')['anonymize'], 'saving again replaces the profile');

		$service->removeProfileFor(documentType: 'invoice');

		$this->assertSame(['permit'], array_keys($service->typeProfiles()));
		$this->assertSame($service->getProfile(), $service->getProfileFor(documentType: 'invoice'));

	}//end testSeveralProfilesAreKeptAndOneCanBeUnlinked()

	/**
	 * A profile without a type or without both lists is refused, and a stored malformed entry is ignored.
	 *
	 * @return void
	 */
	public function testAMalformedProfileIsRefusedAndAMalformedEntryIgnored(): void {
		$service = $this->service();

		foreach ([['', ['anonymize' => [], 'keep' => []]], ['invoice', ['anonymize' => ['IBAN']]]] as [$type, $profile]) {
			try {
				$service->saveProfileFor(documentType: $type, profile: $profile);
				$this->fail('a malformed profile was saved');
			} catch (InvalidArgumentException $e) {
				$this->assertStringContainsString('document type', $e->getMessage());
			}
		}

		$this->store['filinq_woo_entity_profiles_by_type'] = '{"invoice":{"anonymize":"IBAN"},"permit":{"anonymize":["ADDRESS"],"keep":[]}}';
		$this->assertSame(['permit'], array_keys($service->typeProfiles()));
		$this->store['filinq_woo_entity_profiles_by_type'] = 'not json';
		$this->assertSame([], $service->typeProfiles());

	}//end testAMalformedProfileIsRefusedAndAMalformedEntryIgnored()
}//end class
