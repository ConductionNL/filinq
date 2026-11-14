<?php

/**
 * How one unit of a mail archive is read
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

/**
 * The folder name for messages outside any folder, the most messages in one
 * unit, where the previous unit stopped, and whether the message bytes are read.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 */
final class MailArchiveRead {

	/**
	 * The most messages in one unit, at least one.
	 *
	 * @var integer
	 */
	public readonly int $limit;

	/**
	 * Where the previous unit stopped; [] to start.
	 *
	 * @var array<string, int>
	 */
	private array $cursor = [];

	/**
	 * Whether the message bytes are returned; false to only count.
	 *
	 * @var boolean
	 */
	private bool $withContent = true;

	/**
	 * Constructor.
	 *
	 * @param string $rootName The folder name for messages outside any folder.
	 * @param int    $limit    The most messages in one unit; below one reads one.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $rootName,
		int $limit,
	) {
		$this->limit = max(1, $limit);

	}//end __construct()

	/**
	 * The same read, continuing from a saved cursor.
	 *
	 * @param array<string, int> $cursor Where the previous unit stopped.
	 *
	 * @return self The read.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function from(array $cursor): self {
		$read = clone $this;
		$read->cursor = $cursor;

		return $read;

	}//end from()

	/**
	 * The same read without the message bytes, for counting.
	 *
	 * @return self The read.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-2
	 */
	public function countingOnly(): self {
		$read = clone $this;
		$read->withContent = false;

		return $read;

	}//end countingOnly()

	/**
	 * Where the previous unit stopped.
	 *
	 * @return array<string, int> The cursor; [] to start.
	 */
	public function cursor(): array {
		return $this->cursor;

	}//end cursor()

	/**
	 * Whether the message bytes are returned.
	 *
	 * @return bool True to return them.
	 */
	public function withContent(): bool {
		return $this->withContent;

	}//end withContent()
}//end class
