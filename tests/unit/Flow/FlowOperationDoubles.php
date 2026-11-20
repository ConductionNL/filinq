<?php

/**
 * Shared doubles for the Flow operation tests: a translating IL10N and a URL generator.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FlowOperationTrigger;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Builds the doubles every operation takes.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
trait FlowOperationDoubles {

	/**
	 * An IL10N that fills its placeholders and otherwise returns the source.
	 *
	 * @return IL10N&MockObject The translator.
	 */
	private function l10n(): IL10N {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array|string $parameters = []): string => vsprintf($text, (array) $parameters)
		);

		return $l10n;

	}//end l10n()

	/**
	 * A URL generator answering image paths under /apps/filinq/img/.
	 *
	 * @return IURLGenerator&MockObject The generator.
	 */
	private function urls(): IURLGenerator {
		$urls = $this->createMock(IURLGenerator::class);
		$urls->method('imagePath')->willReturnCallback(
			static fn (string $app, string $image): string => '/apps/' . $app . '/img/' . $image
		);

		return $urls;

	}//end urls()

	/**
	 * A trigger that must not be used.
	 *
	 * @return FlowOperationTrigger&MockObject The trigger.
	 */
	private function idleTrigger(): FlowOperationTrigger {
		return $this->createMock(FlowOperationTrigger::class);

	}//end idleTrigger()
}//end trait
