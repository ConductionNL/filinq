<?php

/**
 * Unit tests for NextcloudMountCapabilityProbe
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DomainDirectory;
use OCA\Filinq\Service\NextcloudMountCapabilityProbe;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The probe answers what a home mount can be asked, and null for everything
 * Nextcloud does not expose or that cannot be reached.
 *
 * `OCP\Files\Mount\IMountPoint` is not loadable in the unit environment, so the
 * node double carries `getMountPoint()` as an added method returning a plain
 * object with `getMountType()`; the probe reads it by duck typing.
 *
 * @covers \OCA\Filinq\Service\NextcloudMountCapabilityProbe
 * @uses   \OCA\Filinq\Service\DomainDirectory
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 */
class NextcloudMountCapabilityProbeTest extends TestCase {

	/**
	 * The file tree.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * The owner's files root.
	 *
	 * @var Folder&MockObject
	 */
	private Folder&MockObject $userFolder;

	/**
	 * Set up the boundaries.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userFolder = $this->createMock(Folder::class);

	}//end setUp()

	/**
	 * Build the probe over a real directory naming the given owner.
	 *
	 * @param string $owner The configured folder owner (whitespace is trimmed by the directory).
	 *
	 * @return NextcloudMountCapabilityProbe
	 */
	private function probe(string $owner = 'records'): NextcloudMountCapabilityProbe {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')
			->with('filinq', DomainDirectory::OWNER_KEY, '')
			->willReturn($owner);

		$directory = new DomainDirectory(
			new DocumentObjectServiceResolver(
				$this->createMock(ContainerInterface::class),
				$this->createMock(IAppManager::class)
			),
			$config,
			$this->createMock(LoggerInterface::class)
		);

		return new NextcloudMountCapabilityProbe($this->rootFolder, $directory);

	}//end probe()

	/**
	 * The owner's folder holds the given node at `Domains/HR`.
	 *
	 * @param Node $node The node.
	 *
	 * @return void
	 */
	private function nodeAtPath(Node $node): void {
		$this->rootFolder->method('getUserFolder')->with('records')->willReturn($this->userFolder);
		$this->userFolder->method('nodeExists')->with('Domains/HR')->willReturn(true);
		$this->userFolder->method('get')->with('Domains/HR')->willReturn($node);

	}//end nodeAtPath()

	/**
	 * A node whose mount reports the given type, or throws when the type is null.
	 *
	 * @param string|null $mountType The mount type, or null to make the lookup throw.
	 *
	 * @return Node&MockObject
	 */
	private function nodeOnMount(?string $mountType): Node&MockObject {
		$node = $this->getMockBuilder(Node::class)->addMethods(['getMountPoint'])->getMockForAbstractClass();
		if ($mountType === null) {
			$node->method('getMountPoint')->willThrowException(new RuntimeException('no mount'));

			return $node;
		}

		$node->method('getMountPoint')->willReturn(
			new class ($mountType) {
				/**
				 * Constructor.
				 *
				 * @param string $type The mount type.
				 *
				 * @return void
				 */
				public function __construct(private readonly string $type) {
				}//end __construct()

				/**
				 * The mount type.
				 *
				 * @return string
				 */
				public function getMountType(): string {
					return $this->type;
				}//end getMountType()
			}
		);

		return $node;

	}//end nodeOnMount()

	/**
	 * An updateable folder is writable, a read-only one is not.
	 *
	 * @return void
	 */
	public function testWritabilityFollowsTheNode(): void {
		$node = $this->createMock(Node::class);
		$node->expects($this->exactly(2))->method('isUpdateable')->willReturnOnConsecutiveCalls(true, false);
		$this->nodeAtPath($node);

		$probe = $this->probe();
		$this->assertTrue($probe->isWritable('Domains/HR'));
		$this->assertFalse($probe->isWritable('Domains/HR'));

	}//end testWritabilityFollowsTheNode()

	/**
	 * A node that throws on the permission check is unknown.
	 *
	 * @return void
	 */
	public function testAFailingPermissionCheckIsUnknown(): void {
		$node = $this->createMock(Node::class);
		$node->method('isUpdateable')->willThrowException(new RuntimeException('storage gone'));
		$this->nodeAtPath($node);

		$this->assertNull($this->probe()->isWritable('Domains/HR'));

	}//end testAFailingPermissionCheckIsUnknown()

	/**
	 * Without a configured owner nothing is looked up and everything is unknown.
	 *
	 * @return void
	 */
	public function testWithoutAnOwnerEverythingIsUnknown(): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$probe = $this->probe(owner: '  ');
		$this->assertNull($probe->isWritable('Domains/HR'));
		$this->assertNull($probe->supportsVersions('Domains/HR'));
		$this->assertNull($probe->supportsPerGroupPermissions('Domains/HR'));

	}//end testWithoutAnOwnerEverythingIsUnknown()

	/**
	 * A path that does not exist is unknown, and the node is never fetched.
	 *
	 * @return void
	 */
	public function testAMissingPathIsUnknown(): void {
		$this->rootFolder->method('getUserFolder')->willReturn($this->userFolder);
		$this->userFolder->expects($this->exactly(2))->method('nodeExists')->with('Nowhere')->willReturn(false);
		$this->userFolder->expects($this->never())->method('get');

		$probe = $this->probe();
		$this->assertNull($probe->isWritable('Nowhere'));
		$this->assertNull($probe->supportsVersions('Nowhere'));

	}//end testAMissingPathIsUnknown()

	/**
	 * An owner whose storage cannot be opened is unknown.
	 *
	 * @return void
	 */
	public function testAnUnreachableUserFolderIsUnknown(): void {
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('records')
			->willThrowException(new RuntimeException('no such user'));

		$this->assertNull($this->probe()->isWritable('Domains/HR'));

	}//end testAnUnreachableUserFolderIsUnknown()

	/**
	 * A home mount (empty mount type) supports versions and per-group permissions.
	 *
	 * @return void
	 */
	public function testAHomeMountSupportsVersionsAndGroupPermissions(): void {
		$this->nodeAtPath($this->nodeOnMount(''));

		$probe = $this->probe();
		$this->assertTrue($probe->supportsVersions('Domains/HR'));
		$this->assertTrue($probe->supportsPerGroupPermissions('Domains/HR'));

	}//end testAHomeMountSupportsVersionsAndGroupPermissions()

	/**
	 * Any other mount type is unconfirmed, not denied.
	 *
	 * @return void
	 */
	public function testAnExternalMountIsUnconfirmed(): void {
		$this->nodeAtPath($this->nodeOnMount('external'));

		$probe = $this->probe();
		$this->assertNull($probe->supportsVersions('Domains/HR'));
		$this->assertNull($probe->supportsPerGroupPermissions('Domains/HR'));

	}//end testAnExternalMountIsUnconfirmed()

	/**
	 * A mount that cannot be read is unknown.
	 *
	 * @return void
	 */
	public function testAnUnreadableMountIsUnknown(): void {
		$this->nodeAtPath($this->nodeOnMount(null));

		$this->assertNull($this->probe()->supportsVersions('Domains/HR'));

	}//end testAnUnreadableMountIsUnknown()

	/**
	 * Whether writes pass through filinq is never claimed, even on a home mount, and nothing is asked.
	 *
	 * @return void
	 */
	public function testWritesPassingThroughFilinqIsNeverClaimed(): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->assertNull($this->probe()->writesPassThroughFilinq('Domains/HR'));

	}//end testWritesPassingThroughFilinqIsNeverClaimed()
}//end class
