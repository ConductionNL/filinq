<?php

/**
 * Portal contribution: the whole signer manifest, translated, as a golden file.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Portal
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/portal-signing-surface/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Portal;

use OCA\Filinq\Portal\PortalContributionProvider;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\TestCase;

/**
 * Locks every key, value and key order of the signer manifest.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PortalSignerManifestGoldenTest extends TestCase {

	/**
	 * The golden signer manifest.
	 *
	 * @var string
	 */
	private const GOLDEN = __DIR__ . '/../../fixtures/portal/signer-manifest.json';

	/**
	 * The signer manifest, with every label marked as translated, matches the golden file.
	 *
	 * @return void
	 */
	public function testSignerManifestMatchesGolden(): void {
		$l10n = $this->createMock(originalClassName: IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => 'nl:' . $text);
		$factory = $this->createMock(originalClassName: IFactory::class);
		$factory->method('get')->willReturn($l10n);

		$manifest = (new PortalContributionProvider(l10nFactory: $factory))->getContribution(subject: ['audience' => 'signer']);
		$json = json_encode(value: $manifest, flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

		$this->assertSame(expected: file_get_contents(filename: self::GOLDEN), actual: $json);
	}//end testSignerManifestMatchesGolden()
}//end class
