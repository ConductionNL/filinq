<?php

/**
 * The Kernel component: the app identity, and no dependency on other components
 * than the Exception and Event leaves.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Kernel
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Kernel;

use OCA\Filinq\AppInfo\Application;
use OCA\Filinq\Kernel\AppIdentity;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Kernel is a leaf that every other component may depend on.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class KernelTest extends TestCase {

	/**
	 * The app id matches the bootstrap constant and the id in info.xml.
	 *
	 * @return void
	 */
	public function testTheAppIdMatchesTheBootstrapAndInfoXml(): void {
		$info = simplexml_load_string((string)file_get_contents(dirname(__DIR__, 3) . '/appinfo/info.xml'));

		$this->assertSame(expected: 'filinq', actual: AppIdentity::APP_ID);
		$this->assertSame(expected: AppIdentity::APP_ID, actual: Application::APP_ID);
		$this->assertSame(expected: AppIdentity::APP_ID, actual: trim((string)$info->id));

	}//end testTheAppIdMatchesTheBootstrapAndInfoXml()

	/**
	 * No Kernel file names a Filinq class outside the Kernel, Exception and Event leaves.
	 *
	 * @return void
	 */
	public function testKernelDependsOnNoOtherComponent(): void {
		$kernel   = dirname(__DIR__, 3) . '/lib/Kernel';
		$outgoing = [];

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kernel, RecursiveDirectoryIterator::SKIP_DOTS));
		foreach ($files as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}

			$source = (string)file_get_contents($file->getPathname());
			preg_match_all('/OCA\\\\Filinq\\\\(?!(?:Kernel|Exception|Event)\\b)[A-Za-z0-9_\\\\]+/', $source, $matches);
			foreach ($matches[0] as $name) {
				$outgoing[] = $file->getFilename() . ' -> ' . $name;
			}
		}

		$this->assertSame(expected: [], actual: $outgoing, message: 'Kernel must stay a leaf: ' . implode("\n", $outgoing));

	}//end testKernelDependsOnNoOtherComponent()
}//end class
