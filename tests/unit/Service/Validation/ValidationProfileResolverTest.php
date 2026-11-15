<?php

/**
 * The validation profile for a document type: built-in defaults, per-type and default overrides, and which severity overrides are kept.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Validation;


use OCA\Filinq\Service\Validation\ValidationProfileResolver;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\Validation\ValidationProfileResolver
 */
class ValidationProfileResolverTest extends TestCase {

	/**
	 * The built-in severities: warning everywhere, archival checks off.
	 */
	private const DEFAULT_SEVERITIES = [
		'format-not-allowed' => 'warning',
		'extension-mime-mismatch' => 'warning',
		'file-unreadable' => 'warning',
		'pdf-encrypted' => 'warning',
		'text-layer-missing' => 'warning',
		'metadata-incomplete' => 'warning',
		'pdfa-conformance-failed' => 'off',
		'pdfa-font-not-embedded' => 'off',
		'pdf-not-tagged' => 'warning',
		'pdf-language-missing' => 'warning',
		'pdf-title-missing' => 'warning',
		'pdfua-identifier-missing' => 'warning',
	];

	/**
	 * Build a resolver whose stored profiles are the given string.
	 *
	 * @param string               $stored The stored validation.profiles value.
	 * @param LoggerInterface|null $logger The logger, when a test watches it.
	 *
	 * @return ValidationProfileResolver
	 */
	private function resolver(string $stored, ?LoggerInterface $logger = null): ValidationProfileResolver {
		$config = $this->createMock(IAppConfig::class);
		$config->expects($this->once())->method('getValueString')->with('filinq', 'validation.profiles', '')->willReturn($stored);

		return new ValidationProfileResolver(logger: $logger ?? $this->createMock(LoggerInterface::class), appConfig: $config);
	}//end resolver()

	public function testWithoutStoredProfilesTheDefaultsApply(): void {
		$profile = $this->resolver(stored: '')->resolve(documentType: 'besluit');

		$this->assertSame(
			[
				'allowedMimes' => [
					'application/pdf',
					'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'application/msword',
					'application/vnd.oasis.opendocument.text',
					'text/plain',
					'text/markdown',
					'text/html',
				],
				'requiredFields' => [],
				'severities' => self::DEFAULT_SEVERITIES,
			],
			$profile
		);
	}//end testWithoutStoredProfilesTheDefaultsApply()

	public function testInvalidJsonIsLoggedAndFallsBackToDefaults(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with('Invalid filinq.validation.profiles JSON; using defaults.');

		$profile = $this->resolver(stored: '{not json', logger: $logger)->resolve(documentType: 'besluit');

		$this->assertSame(self::DEFAULT_SEVERITIES, $profile['severities']);
	}//end testInvalidJsonIsLoggedAndFallsBackToDefaults()

	public function testScalarJsonAndAScalarProfileFallBackToDefaults(): void {
		$this->assertSame(self::DEFAULT_SEVERITIES, $this->resolver(stored: '"text"')->resolve(documentType: 'x')['severities']);
		$this->assertSame(self::DEFAULT_SEVERITIES, $this->resolver(stored: '{"x": "strict"}')->resolve(documentType: 'x')['severities']);
	}//end testScalarJsonAndAScalarProfileFallBackToDefaults()

	public function testTheTypeProfileWinsOverTheDefaultProfile(): void {
		$stored = json_encode([
			'default' => ['requiredFields' => ['title']],
			'besluit' => ['requiredFields' => ['b' => 'zaaknummer', 'a' => 'datum'], 'allowedMimes' => ['application/pdf']],
		]);

		$typed = $this->resolver(stored: $stored)->resolve(documentType: 'besluit');
		$this->assertSame(['zaaknummer', 'datum'], $typed['requiredFields']);
		$this->assertSame(['application/pdf'], $typed['allowedMimes']);

		$other = $this->resolver(stored: $stored)->resolve(documentType: 'brief');
		$this->assertSame(['title'], $other['requiredFields']);
		$this->assertCount(7, $other['allowedMimes']);

		$untyped = $this->resolver(stored: $stored)->resolve(documentType: '');
		$this->assertSame(['title'], $untyped['requiredFields']);
	}//end testTheTypeProfileWinsOverTheDefaultProfile()

	public function testAnUnmatchedTypeWithoutDefaultProfileGetsTheDefaults(): void {
		$profile = $this->resolver(stored: '{"besluit": {"requiredFields": ["x"]}}')->resolve(documentType: 'brief');

		$this->assertSame([], $profile['requiredFields']);
	}//end testAnUnmatchedTypeWithoutDefaultProfileGetsTheDefaults()

	public function testNonListOverridesAreIgnored(): void {
		$profile = $this->resolver(stored: '{"default": {"allowedMimes": "application/pdf", "requiredFields": null, "severities": "blocking"}}')->resolve(documentType: '');

		$this->assertCount(7, $profile['allowedMimes']);
		$this->assertSame([], $profile['requiredFields']);
		$this->assertSame(self::DEFAULT_SEVERITIES, $profile['severities']);
	}//end testNonListOverridesAreIgnored()

	public function testOnlyKnownChecksWithKnownSeveritiesAreOverridden(): void {
		$stored = json_encode([
			'default' => [
				'severities' => [
					'pdf-encrypted' => 'blocking',
					'pdfa-conformance-failed' => 'warning',
					'pdf-not-tagged' => 'off',
					'pdf-title-missing' => 'fatal',
					'made-up-check' => 'blocking',
				],
			],
		]);

		$severities = $this->resolver(stored: $stored)->resolve(documentType: 'any')['severities'];

		$expected = array_merge(
			self::DEFAULT_SEVERITIES,
			['pdf-encrypted' => 'blocking', 'pdfa-conformance-failed' => 'warning', 'pdf-not-tagged' => 'off']
		);
		$this->assertSame($expected, $severities);
	}//end testOnlyKnownChecksWithKnownSeveritiesAreOverridden()
}//end class
