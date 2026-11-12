<?php

/**
 * Golden traces of who the grondslagen summary acts for.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DossierSummaryDataService;
use OCA\Filinq\Service\GrondslagenPdfWriter;
use OCA\Filinq\Service\LegalBasesSummaryService;
use OCA\Filinq\Tests\Unit\Support\RecordsCalls;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The calls the session, the file tree, OpenRegister and the PDF writer see
 * when the summary authorises a dossier, writes a per-document summary and
 * renders a dossier summary, compared with JSON recorded from the
 * implementation that took eight constructor parameters. Each case names
 * the session user; the dossier folder only exists in alice's tree, so the
 * forged case shows the folder is looked up for the session user and for
 * no one else.
 *
 * @covers \OCA\Filinq\Service\LegalBasesSummaryService
 * @uses   \OCA\Filinq\Service\DossierSummaryDataService
 * @uses   \OCA\Filinq\Service\DossierObjectRepository
 * @uses   \OCA\Filinq\Service\BaseLabelResolver
 * @uses   \OCA\Filinq\Service\DossierEntityCollector
 * @uses   \OCA\Filinq\Service\DossierEntityTally
 * @uses   \OCA\Filinq\Service\DossierPlaceholderRanker
 *
 * @spec openspec/specs/anonymisation-grondslagen-summary/spec.md
 */
class LegalBasesSummaryActingUserTraceTest extends TestCase {
	use RecordsCalls;

	private const GOLDEN = __DIR__.'/golden/legal-bases-summary-acting-user.json';

	/**
	 * The cases: session user (or null), whether OpenRegister is installed, and the call.
	 *
	 * @return array<string, array{0: ?string, 1: bool, 2: string, 3: string}>
	 */
	public static function cases(): array {
		return [
			'authorise a readable dossier'                => ['alice', true, 'authorise', 'd-1'],
			'authorise an unknown dossier'                => ['alice', true, 'authorise', 'd-404'],
			'authorise without OpenRegister'              => ['alice', false, 'authorise', 'd-1'],
			'beside file names the session user'          => ['alice', true, 'beside', ''],
			'beside file without a session user'          => [null, true, 'beside', ''],
			'append names the session user'               => ['bob', true, 'append', ''],
			'dossier summary in the session users folder' => ['alice', true, 'dossier', 'd-1'],
			'dossier summary for a forged user'           => ['mallory', true, 'dossier', 'd-1'],
			'dossier summary without a session user'      => [null, true, 'dossier', 'd-1'],
		];

	}//end cases()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param string|null $uid       The session user.
	 * @param bool        $installed Whether OpenRegister is installed.
	 * @param string      $call      Which entry point runs.
	 * @param string      $dossier   The dossier id.
	 *
	 * @dataProvider cases
	 *
	 * @return void
	 */
	public function testTrace(?string $uid, bool $installed, string $call, string $dossier): void {
		$service = $this->service(uid: $uid, installed: $installed);
		$file    = $this->recorder(File::class, ['getName' => 'besluit.pdf', 'getId' => 77, 'getPath' => '/alice/files/besluit.pdf']);

		$this->traceCall(
			match ($call) {
				'authorise' => fn () => $service->authorizeAccess(dossierId: $dossier),
				'beside'    => fn () => $service->renderSummaryBesideFile(anonymisedFile: $file, sourceFileId: 12),
				'append'    => fn () => $service->appendSummaryToPdf(anonymisedFile: $file, sourceFileId: 12),
				'dossier'   => fn () => $service->renderDossierSummary(dossierUuid: $dossier),
			}
		);

		$this->assertTrace(file: self::GOLDEN, name: (string) $this->dataName());

	}//end testTrace()

	/**
	 * The service over recording doubles, wired the way the container wires it.
	 *
	 * @param string|null $uid       The session user.
	 * @param bool        $installed Whether OpenRegister is installed.
	 *
	 * @return LegalBasesSummaryService The service.
	 */
	private function service(?string $uid, bool $installed): LegalBasesSummaryService {
		$user = null;
		if ($uid !== null) {
			$user = $this->recorder(IUser::class, ['getUID' => $uid, 'getDisplayName' => ucfirst($uid).' Archivaris']);
		}

		$session    = $this->recorder(IUserSession::class, ['getUser' => $user]);
		$apps       = $this->recorder(IAppManager::class, ['getInstalledApps' => ($installed === true ? ['openregister'] : [])]);
		$objects    = $this->objectService();
		$container  = $this->recorder(
			ContainerInterface::class,
			[
				'get' => static function (string $id) use ($objects): object {
					if ($id === 'OCA\OpenRegister\Service\ObjectService') {
						return $objects;
					}

					throw new Exception('not registered: '.$id);
				},
			]
		);
		$rootFolder = $this->recorder(IRootFolder::class, ['getUserFolder' => fn (string $owner): Folder => $this->userFolder(owner: $owner)]);
		$logger     = $this->recorder(LoggerInterface::class, ['info' => null, 'warning' => null]);
		$summary    = $this->recorder(File::class, ['getId' => 501, 'getPath' => '/alice/files/dossier/grondslagen.pdf']);
		$written    = $this->recorder(File::class, ['getId' => 78, 'getPath' => '/alice/files/besluit_grondslagen.pdf']);
		$pdfWriter  = $this->recorder(
			GrondslagenPdfWriter::class,
			[
				'renderPerDocumentPdf' => 'document-pdf',
				'renderDossierPdf'     => 'dossier-pdf',
				'appendToPdf'          => null,
				'writeBesideFile'      => ['file' => $written, 'refreshed' => false],
				'saveDossierSummary'   => $summary,
			]
		);

		return new LegalBasesSummaryService(
			logger: $logger,
			pdfWriter: $pdfWriter,
			userSession: $session,
			data: new DossierSummaryDataService(
				rootFolder: $rootFolder,
				userSession: $session,
				appManager: $apps,
				container: $container,
				logger: $logger
			)
		);

	}//end service()

	/**
	 * An OpenRegister object service that knows dossier d-1 in folder 900.
	 *
	 * @return object The double.
	 */
	private function objectService(): object {
		$trace = &$this->trace;
		return new class($trace) {

			/**
			 * Constructor.
			 *
			 * @param array<int, array<string, mixed>> $trace The shared trace.
			 */
			public function __construct(private array &$trace) {
			}

			/**
			 * Record the call and answer it.
			 *
			 * @param string       $name      The method.
			 * @param array<mixed> $arguments The arguments as passed.
			 *
			 * @return mixed The answer.
			 */
			public function __call(string $name, array $arguments): mixed {
				if (($arguments['object']['configuration']['grondslagen']['lastGeneratedAt'] ?? null) !== null) {
					$arguments['object']['configuration']['grondslagen']['lastGeneratedAt'] = '<time>';
				}

				// Value objects are recorded as [class => their fields], as the other acting-user traces record them.
				$flatten = static function (mixed $value) use (&$flatten): mixed {
					if (is_object($value) === true) {
						return [get_class($value) => array_map($flatten, get_object_vars($value))];
					}

					return $value;
				};
				$this->trace[] = ['ObjectService::'.$name => array_map($flatten, $arguments)];
				if ($name === 'find' && ($arguments['id'] ?? '') === 'd-1') {
					return ['name' => 'Raadsbesluit', 'description' => 'Woo-verzoek', 'checkedOn' => 'yes', '@self' => ['folder' => 900]];
				}

				return null;
			}
		};

	}//end objectService()

	/**
	 * The user folder of $owner: folder 900 is only in alice's tree.
	 *
	 * @param string $owner The user whose folder is asked for.
	 *
	 * @return Folder The double.
	 */
	private function userFolder(string $owner): Folder {
		$nodes = [];
		if ($owner === 'alice') {
			$nodes = [$this->recorder(Folder::class, ['getDirectoryListing' => [], 'getName' => 'Raadsbesluit'])];
		}

		return $this->recorder(Folder::class, ['getById' => $nodes]);

	}//end userFolder()
}//end class
