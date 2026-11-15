<?php

/**
 * OpenCatalogi's category codes as the hand-off uses them: the picker offers
 * the code, and a stored TOOI basename is translated to it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Publication;

use OCA\Filinq\Service\Publication\OpenCatalogiPlatform;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Publication\OpenCatalogiPlatform
 */
class OpenCatalogiPlatformCategoryTest extends TestCase {

	private function platform(bool $installed = true, ?object $tooi = null): OpenCatalogiPlatform {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForAnyone')->with('opencatalogi')->willReturn($installed);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($tooi ?? new class() {
			public function informatiecategorieList(): array {
				return [
					'infocat001' => ['uri' => 'https://identifier.overheid.nl/tooi/def/thes/kern/c_139c6280', 'label' => 'Wetten en algemeen verbindende voorschriften'],
					'infocat009' => ['uri' => 'https://identifier.overheid.nl/tooi/def/thes/kern/c_8c840238', 'label' => 'Adviezen'],
					'' => ['uri' => 'https://identifier.overheid.nl/tooi/def/thes/kern/c_0', 'label' => 'Zonder code'],
					'infocat099' => 'not an entry',
				];
			}
		});

		return new OpenCatalogiPlatform(appManager: $apps, container: $container, rootFolder: $this->createMock(IRootFolder::class));
	}

	public function testThePickerOffersOpenCatalogisCode(): void {
		$this->assertSame(
			[
				['code' => 'infocat001', 'label' => 'Wetten en algemeen verbindende voorschriften'],
				['code' => 'infocat009', 'label' => 'Adviezen'],
			],
			$this->platform()->categories()
		);
	}

	public function testACodeOrATooiBasenameBecomesTheCode(): void {
		$platform = $this->platform();

		$this->assertSame('infocat001', $platform->toPlatformCode(' infocat001 '));
		$this->assertSame('infocat009', $platform->toPlatformCode('c_8c840238'));
		$this->assertNull($platform->toPlatformCode('notacategory'));
		$this->assertNull($platform->toPlatformCode('c_0'));
		$this->assertNull($platform->toPlatformCode(''));
	}

	public function testWithoutOpenCatalogiOrItsListNothingTranslates(): void {
		$this->assertNull($this->platform(installed: false)->toPlatformCode('infocat001'));
		$this->assertSame([], $this->platform(installed: false)->categories());

		$broken = new class() {
			public function informatiecategorieList(): array {
				throw new RuntimeException('vocabulary not loaded');
			}
		};
		$this->assertNull($this->platform(tooi: $broken)->toPlatformCode('infocat001'));
		$this->assertSame([], $this->platform(tooi: $broken)->categories());
	}
}
