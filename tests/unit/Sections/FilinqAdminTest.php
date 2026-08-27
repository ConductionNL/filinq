<?php

/**
 * Unit tests for the Filinq admin section
 *
 * Pins the identifiers Nextcloud's settings framework keys on: the section ID
 * that Settings\FilinqAdmin::getSection() must match, the icon path, the
 * translated name and the ordering priority.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Sections
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Sections;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Sections\FilinqAdmin;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OCA\Filinq\Sections\FilinqAdmin
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Sections
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FilinqAdminTest extends TestCase {

	/**
	 * The section under test.
	 *
	 * @var FilinqAdmin
	 */
	private FilinqAdmin $section;

	/**
	 * Mock translation service.
	 *
	 * @var MockObject&IL10N
	 */
	private MockObject $l10n;

	/**
	 * Mock URL generator.
	 *
	 * @var MockObject&IURLGenerator
	 */
	private MockObject $urlGenerator;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(originalClassName: IL10N::class);
		$this->urlGenerator = $this->createMock(originalClassName: IURLGenerator::class);

		$this->section = new FilinqAdmin(l10n: $this->l10n, urlGenerator: $this->urlGenerator);

	}//end setUp()

	/**
	 * Test that the section implements the icon-section contract
	 *
	 * @return void
	 */
	public function testSectionImplementsIIconSection(): void {
		$this->assertInstanceOf(expected: IIconSection::class, actual: $this->section);

	}//end testSectionImplementsIIconSection()

	/**
	 * Test that the section ID is the app id
	 *
	 * Settings\FilinqAdmin::getSection() must return this exact string or the
	 * settings page is registered against a section that does not exist and
	 * silently never renders.
	 *
	 * @return void
	 */
	public function testGetIdReturnsTheAppId(): void {
		$this->assertSame(expected: 'filinq', actual: $this->section->getID());
		$this->assertSame(expected: Application::APP_ID, actual: $this->section->getID());

	}//end testGetIdReturnsTheAppId()

	/**
	 * Test that the icon is resolved from this app's own image directory
	 *
	 * @return void
	 */
	public function testGetIconResolvesTheAppDarkIcon(): void {
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with($this->identicalTo('filinq'), $this->identicalTo('app-dark.svg'))
			->willReturn('/apps/filinq/img/app-dark.svg');

		$this->assertSame(
			expected: '/apps/filinq/img/app-dark.svg',
			actual: $this->section->getIcon()
		);

	}//end testGetIconResolvesTheAppDarkIcon()

	/**
	 * Test that the section name is passed through translation
	 *
	 * @return void
	 */
	public function testGetNameIsTranslated(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with($this->identicalTo('Filinq'))
			->willReturn('Filinq');

		$this->assertSame(expected: 'Filinq', actual: $this->section->getName());

	}//end testGetNameIsTranslated()

	/**
	 * Test that the section priority is pinned
	 *
	 * @return void
	 */
	public function testGetPriorityIsPinned(): void {
		$this->assertSame(expected: 97, actual: $this->section->getPriority());

	}//end testGetPriorityIsPinned()
}//end class
