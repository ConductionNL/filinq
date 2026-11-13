<?php

/**
 * The shape of thematiq's profile mapped onto a house style, and what a house
 * style adds to the PDF options.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\HuisstijlProfileMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\HuisstijlProfileMapper
 */
class HuisstijlProfileMapperTest extends TestCase {

	private HuisstijlProfileMapper $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = new HuisstijlProfileMapper();
	}//end setUp()

	/**
	 * The documented thematiq profile of one token set.
	 *
	 * @param string $logo The logo URL.
	 *
	 * @return array<string, mixed> The profile.
	 */
	private function profile(string $logo = '/apps/thematiq/document-style/logo?v=1'): array {
		return [
			'tokenSet' => ['id' => 'bergen', 'name' => 'Gemeente Bergen'],
			'organisation' => 'Gemeente Bergen',
			'logo' => ['url' => $logo, 'mime' => 'image/png'],
			'cover' => ['url' => '/apps/thematiq/document-style/cover?v=2', 'mime' => 'image/jpeg'],
			'colours' => ['primary' => '#154273', 'primaryText' => '#ffffff', 'text' => '#000000', 'background' => '#ffffff', 'accent' => null],
			'fonts' => [
				'heading' => ['family' => 'Fira Sans', 'url' => '/apps/thematiq/font/3'],
				'body' => ['family' => 'Noto Serif', 'url' => null],
			],
			'footer' => [
				'lines' => ['Gemeente Bergen', 'Postbus 175, 1860 AD Bergen'],
				'accessibilityUrl' => 'https://bergen.nl/toegankelijkheid',
				'privacyUrl' => '',
			],
			'warnings' => [],
		];
	}//end profile()

	public function testTheProfileMapsOntoTheHouseStyleFields(): void {
		$mapped = $this->mapper->mapProfile($this->profile());

		$this->assertSame('Gemeente Bergen', $mapped['name']);
		$this->assertSame('thematiq', $mapped['managedBy']);
		$this->assertSame('bergen', $mapped['tokenSetId']);
		$this->assertSame('/apps/thematiq/document-style/logo?v=1', $mapped['logo']);
		$this->assertSame('#154273', $mapped['primaryColor']);
		$this->assertSame(
			['heading' => ['family' => 'Fira Sans', 'url' => '/apps/thematiq/font/3'], 'body' => ['family' => 'Noto Serif', 'url' => null]],
			$mapped['fonts']
		);
		$this->assertSame('/apps/thematiq/document-style/cover?v=2', $mapped['coverImage']);
		$this->assertSame(['Gemeente Bergen', 'Postbus 175, 1860 AD Bergen'], $mapped['footerLines']);
		$this->assertSame(
			'<div class="huisstijl-footer"><p>Gemeente Bergen</p><p>Postbus 175, 1860 AD Bergen</p>'
			. '<p><a href="https://bergen.nl/toegankelijkheid">https://bergen.nl/toegankelijkheid</a></p></div>',
			$mapped['footerHtml']
		);
		$this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $mapped['profileHash']);
	}//end testTheProfileMapsOntoTheHouseStyleFields()

	public function testWarningsDoNotChangeTheHash(): void {
				$warned = $this->profile();
		$warned['warnings'] = ['primary on background fails AA'];

		$this->assertSame($this->mapper->mapProfile($this->profile())['profileHash'], $this->mapper->mapProfile($warned)['profileHash']);
		$this->assertNotSame(
			$this->mapper->mapProfile($this->profile())['profileHash'],
			$this->mapper->mapProfile($this->profile('/other'))['profileHash']
		);
	}//end testWarningsDoNotChangeTheHash()

	public function testFooterTextCanNeverBeReadAsTwigOrMarkup(): void {
		$profile = $this->profile();
		$profile['footer'] = ['lines' => ['{{ app.request }}', '<b>{% set x = 1 %}</b>', '  ', 42], 'accessibilityUrl' => '', 'privacyUrl' => 'https://x.nl/"p"'];

		$html = $this->mapper->mapProfile($profile)['footerHtml'];

		$this->assertStringNotContainsString('{', $html);
		$this->assertStringNotContainsString('<b>', $html);
		$this->assertStringContainsString('&#123;&#123; app.request &#125;&#125;', $html);
		$this->assertStringContainsString('href="https://x.nl/&quot;p&quot;"', $html);
		$this->assertSame(2, substr_count($html, '<p>') - 1);
	}//end testFooterTextCanNeverBeReadAsTwigOrMarkup()

	public function testTheNameFallsBackToTheOrganisationThenTheTokenSet(): void {
				$profile = ['tokenSet' => ['id' => 'set-7', 'name' => ''], 'organisation' => 'Uitgeest'];
		$this->assertSame('Uitgeest', $this->mapper->mapProfile($profile)['name']);

		$profile['organisation'] = '';
		$mapped = $this->mapper->mapProfile($profile);
		$this->assertSame('set-7', $mapped['name']);
		$this->assertNull($mapped['logo']);
		$this->assertNull($mapped['coverImage']);
		$this->assertSame('', $mapped['footerHtml']);
		$this->assertSame(['heading' => ['family' => null, 'url' => null], 'body' => ['family' => null, 'url' => null]], $mapped['fonts']);
	}//end testTheNameFallsBackToTheOrganisationThenTheTokenSet()

	public function testIsManagedOnlyForThematiq(): void {
		$this->assertTrue($this->mapper->isManaged(['managedBy' => 'thematiq']));
		$this->assertFalse($this->mapper->isManaged(['managedBy' => 'someone']));
		$this->assertFalse($this->mapper->isManaged([]));
	}//end testIsManagedOnlyForThematiq()

	public function testThePdfGetsTheFontsWithFilinqsFontForAMissingFamily(): void {
		$assets = $this->mapper->pdfAssets([], ['fonts' => ['heading' => ['family' => ' Fira Sans ', 'url' => '/f/3'], 'body' => ['family' => null, 'url' => '']], 'coverImage' => '/cover']);

		$this->assertSame(
			['fonts' => ['heading' => ['family' => 'Fira Sans', 'url' => '/f/3'], 'body' => ['family' => 'DejaVu Sans', 'url' => null]]],
			$assets
		);
	}//end testThePdfGetsTheFontsWithFilinqsFontForAMissingFamily()

	public function testTheCoverComesOnlyWhenTheTemplateAsksForIt(): void {
		$this->assertSame(['coverImage' => '/cover'], $this->mapper->pdfAssets(['coverPage' => true], ['coverImage' => '/cover']));
		$this->assertSame([], $this->mapper->pdfAssets(['coverPage' => 'yes'], ['coverImage' => '/cover']));
		$this->assertSame([], $this->mapper->pdfAssets(['coverPage' => true], ['coverImage' => '']));
		$this->assertSame([], $this->mapper->pdfAssets(['coverPage' => true], null));
	}//end testTheCoverComesOnlyWhenTheTemplateAsksForIt()
}//end class
