<?php

/**
 * The shipped app metadata lists Filinq in the workflow app-store category
 * next to organization (flow-operations REQ-DDFLO-009).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-3.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use PHPUnit\Framework\TestCase;

/**
 * Reads appinfo/info.xml.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @coversNothing
 */
class InfoXmlTest extends TestCase {

	/**
	 * Both the organization and the workflow category are declared.
	 *
	 * @return void
	 */
	public function testTheWorkflowCategoryIsDeclaredBesideOrganization(): void {
		$info = simplexml_load_file(__DIR__ . '/../../../appinfo/info.xml');
		$this->assertNotFalse($info);

		$categories = array_map('strval', iterator_to_array($info->category, false));

		$this->assertContains('organization', $categories);
		$this->assertContains('workflow', $categories);

	}//end testTheWorkflowCategoryIsDeclaredBesideOrganization()
}//end class
