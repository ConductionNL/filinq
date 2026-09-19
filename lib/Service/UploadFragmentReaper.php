<?php

/**
 * Removing what an interrupted upload left behind.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reaps upload fragments older than a declared age, and says what it removed.
 *
 * 🔴 IT SWEEPS FILINQ'S OWN TREE AND NOTHING ELSE. Nextcloud already reaps its
 * DAV chunk directories under `<user>/uploads` with `OCA\DAV\BackgroundJob\
 * UploadCleanup`, and a second reaper over the same directory would be a second
 * answer to one question, with two different declared ages disagreeing about
 * which one won. What nothing reaps is the half-written file an interrupted
 * upload leaves INSIDE the documents folder, which is filinq's own tree, so
 * that is the only place this looks.
 *
 * 🔴 A FRAGMENT IS RECOGNISED BY ITS SUFFIX, NEVER BY BEING EMPTY OR SMALL.
 * "Zero bytes" would reap a legitimately empty document somebody created on
 * purpose, and "small" would reap a one-line note. The sync client and the web
 * uploader both write a recognisable suffix while a transfer is in flight; a
 * finished upload no longer carries it. Anything without one of these suffixes
 * is somebody's file and is not this job's business.
 *
 * 🔑 THE COUNT AND THE BYTES ARE THE DELIVERABLE. REQ-CDF-05 asks for both,
 * because a count alone cannot answer "did the reaper reclaim anything", and
 * bytes alone cannot answer "is something writing fragments in a loop".
 *
 * 🔑 A FRAGMENT THAT COULD NOT BE DELETED IS REPORTED, NOT COUNTED AS REMOVED.
 * Counting it would make the reclaimed bytes a number nobody can check against
 * the disk, and a read-only mount would report a clean sweep every night for
 * ever.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class UploadFragmentReaper {

	/**
	 * The suffixes an in-flight upload carries.
	 *
	 * @var array<int, string>
	 */
	public const FRAGMENT_SUFFIXES = ['.part', '.filepart', '.ocTransferId'];

	/**
	 * Collaborators.
	 *
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether a name is an upload fragment's name.
	 *
	 * @param string $name The file name.
	 *
	 * @return bool True when the name carries a fragment suffix.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function isFragmentName(string $name): bool {
		$lower = strtolower($name);
		foreach (self::FRAGMENT_SUFFIXES as $suffix) {
			if (str_ends_with($lower, strtolower($suffix)) === true) {
				return true;
			}

			// The sync client appends a transfer id after the marker, as in
			// `advies.pdf.ocTransferId873492.part`; matching only the tail
			// would miss every one of those.
			if (str_contains($lower, strtolower($suffix)) === true && $suffix !== '.part') {
				return true;
			}
		}

		return false;

	}//end isFragmentName()

	/**
	 * Reap one folder tree.
	 *
	 * @param Folder $folder        The tree to sweep, which is filinq's documents folder.
	 * @param int    $maxAgeSeconds How old a fragment must be before it is reaped.
	 * @param int    $now           The moment to measure age against, as a unix timestamp.
	 *
	 * @return array{removed: int, bytes: int, kept: int, refused: array<int, array{path: string, reason: string}>} What the sweep did.
	 *
	 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
	 */
	public function reap(Folder $folder, int $maxAgeSeconds, int $now): array {
		$removed = 0;
		$bytes = 0;
		$kept = 0;
		$refused = [];

		foreach ($this->fragments(folder: $folder) as $fragment) {
			try {
				$age = ($now - $fragment->getMTime());
			} catch (Throwable $e) {
				// An unreadable timestamp is NOT reaped. Guessing "old enough"
				// deletes somebody's in-flight upload; guessing "too new"
				// leaves a fragment for one more night, which costs disk and
				// nothing else.
				$refused[] = ['path' => $this->pathOf(node: $fragment), 'reason' => $e->getMessage()];
				continue;
			}

			if ($age < $maxAgeSeconds) {
				$kept++;
				continue;
			}

			try {
				$size = (int)$fragment->getSize();
				$path = $this->pathOf(node: $fragment);
				$fragment->delete();
				$removed++;
				$bytes += $size;
				unset($path);
			} catch (Throwable $e) {
				$refused[] = ['path' => $this->pathOf(node: $fragment), 'reason' => $e->getMessage()];
			}
		}//end foreach

		$this->logger->info(
			'filinq.upload-fragments.reaped',
			['removed' => $removed, 'bytes' => $bytes, 'kept' => $kept, 'refused' => count($refused)]
		);

		foreach ($refused as $entry) {
			$this->logger->warning('filinq.upload-fragments.refused', $entry);
		}

		return ['removed' => $removed, 'bytes' => $bytes, 'kept' => $kept, 'refused' => $refused];

	}//end reap()

	/**
	 * Every fragment in a tree, depth first.
	 *
	 * @param Folder $folder The tree.
	 *
	 * @return array<int, File> The fragments.
	 *
	 * @spec exclude Tree walk with no decision of its own; the decision is isFragmentName().
	 */
	private function fragments(Folder $folder): array {
		$found = [];

		try {
			$children = $folder->getDirectoryListing();
		} catch (Throwable $e) {
			$this->logger->warning(
				'filinq.upload-fragments.unreadable-folder',
				['error' => $e->getMessage()]
			);

			return [];
		}

		foreach ($children as $child) {
			if ($child instanceof Folder === true) {
				$found = array_merge($found, $this->fragments(folder: $child));
				continue;
			}

			if ($child instanceof File === true && $this->isFragmentName(name: $child->getName()) === true) {
				$found[] = $child;
			}
		}

		return $found;

	}//end fragments()

	/**
	 * A node's path, for the report, without letting a broken node stop the sweep.
	 *
	 * @param Node $node The node.
	 *
	 * @return string The path, or its name when the path cannot be read.
	 *
	 * @spec exclude Reporting helper.
	 */
	private function pathOf(Node $node): string {
		try {
			return $node->getPath();
		} catch (Throwable $e) {
			unset($e);

			try {
				return $node->getName();
			} catch (Throwable $inner) {
				unset($inner);

				return '(unknown)';
			}
		}

	}//end pathOf()
}//end class
