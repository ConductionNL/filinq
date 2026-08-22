<?php

/**
 * Unit tests for the Filinq admin settings page
 *
 * Pins the settings form's app/template pair, the version handed to the
 * frontend through initial state, the section binding and the delegation
 * posture (no delegated config keys — full admin only).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
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

namespace OCA\Filinq\Tests\Unit\Settings;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Sections\FilinqAdmin as FilinqAdminSection;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OCA\Filinq\Settings\FilinqAdmin
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FilinqAdminSettingsTest extends TestCase {

	/**
	 * The settings page under test.
	 *
	 * @var FilinqAdmin
	 */
	private FilinqAdmin $settings;

	/**
	 * Mock app manager.
	 *
	 * @var MockObject&IAppManager
	 */
	private MockObject $appManager;

	/**
	 * Mock initial-state service.
	 *
	 * @var MockObject&IInitialState
	 */
	private MockObject $initialState;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(originalClassName: IAppManager::class);
		$this->initialState = $this->createMock(originalClassName: IInitialState::class);

		$this->settings = new FilinqAdmin(
			appManager: $this->appManager,
			initialState: $this->initialState
		);

	}//end setUp()

	/**
	 * Test that the settings page implements the delegated-settings contract
	 *
	 * @return void
	 */
	public function testSettingsImplementsIDelegatedSettings(): void {
		$this->assertInstanceOf(expected: IDelegatedSettings::class, actual: $this->settings);

	}//end testSettingsImplementsIDelegatedSettings()

	/**
	 * Test that the form renders this app's admin template
	 *
	 * @return void
	 */
	public function testGetFormRendersTheAdminTemplate(): void {
		$this->appManager->method('getAppVersion')->willReturn('1.2.3');

		$response = $this->settings->getForm();

		$this->assertInstanceOf(expected: TemplateResponse::class, actual: $response);
		$this->assertSame(expected: 'filinq', actual: $response->getApp());
		$this->assertSame(expected: 'settings/admin', actual: $response->getTemplateName());
		$this->assertSame(expected: [], actual: $response->getParams());
		$this->assertSame(expected: '', actual: $response->getRenderAs());

	}//end testGetFormRendersTheAdminTemplate()

	/**
	 * Test that the app version is read for this app and pushed to initial state
	 *
	 * @return void
	 */
	public function testGetFormProvidesTheAppVersionAsInitialState(): void {
		$this->appManager->expects($this->once())
			->method('getAppVersion')
			->with($this->identicalTo(Application::APP_ID))
			->willReturn('1.2.3');

		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with($this->identicalTo('version'), $this->identicalTo('1.2.3'));

		$this->settings->getForm();

	}//end testGetFormProvidesTheAppVersionAsInitialState()

	/**
	 * Test that the settings page binds to the Filinq admin section
	 *
	 * The section ID must match Sections\FilinqAdmin::getID(); a mismatch
	 * registers the page against a non-existent section and it never renders.
	 *
	 * @return void
	 */
	public function testGetSectionMatchesTheAdminSectionId(): void {
		$section = new FilinqAdminSection(
			l10n: $this->createMock(originalClassName: IL10N::class),
			urlGenerator: $this->createMock(originalClassName: IURLGenerator::class)
		);

		$this->assertSame(expected: 'filinq', actual: $this->settings->getSection());
		$this->assertSame(expected: $section->getID(), actual: $this->settings->getSection());

	}//end testGetSectionMatchesTheAdminSectionId()

	/**
	 * Test that the settings priority is pinned
	 *
	 * @return void
	 */
	public function testGetPriorityIsPinned(): void {
		$this->assertSame(expected: 10, actual: $this->settings->getPriority());

	}//end testGetPriorityIsPinned()

	/**
	 * Test that no display name is supplied, so the section name is used
	 *
	 * @return void
	 */
	public function testGetNameReturnsNull(): void {
		$this->assertNull(actual: $this->settings->getName());

	}//end testGetNameReturnsNull()

	/**
	 * Test that no config keys are delegated to sub-admins
	 *
	 * An entry appearing here would grant a delegated admin write access to
	 * that app-config key, so the empty array is a security-relevant default.
	 *
	 * @return void
	 */
	public function testGetAuthorizedAppConfigDelegatesNothing(): void {
		$this->assertSame(expected: [], actual: $this->settings->getAuthorizedAppConfig());

	}//end testGetAuthorizedAppConfigDelegatesNothing()
}//end class
