<?php

/**
 * A publishable list, composed out of redacted copies and nothing else.
 *
 * 🔴 THE ORIGINAL IS NEVER REFERENCED, AND "NEVER" IS THE WHOLE REQUIREMENT.
 * A Woo-publicatielijst that links one original among four hundred redacted
 * copies publishes that person's file, and it looks exactly like a list that
 * did not. So the entry is resolved through the `anonymizationLink` that pairs
 * source and copy, never by taking the record's own file, and never by guessing
 * a name from the source with a suffix. A naming convention is how the wrong
 * file gets published: it answers confidently for a file that was never
 * redacted.
 *
 * 🔴 A RECORD WITH NO REDACTED COPY STOPS THE LIST. It is tempting to leave it
 * out and produce the rest, because a list of three hundred and ninety-nine is
 * nearly right. It is not: the missing one is invisible afterwards, and nobody
 * can tell a record that was excluded from one that was never in the view. The
 * composition reports it and produces nothing.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

use OCA\Filinq\Service\SavedViewReader;

/**
 * Composes one publishable document over the records a saved view returns.
 */
class PublicationListComposer {

	/**
	 * The view does not resolve at all.
	 *
	 * @var string
	 */
	public const NO_SUCH_VIEW = 'no_such_view';

	/**
	 * One or more records have no redacted copy yet.
	 *
	 * @var string
	 */
	public const NOT_READY = 'not_ready';

	/**
	 * Constructor.
	 *
	 * @param SavedViewReader          $views The saved view and its records.
	 * @param AnonymizationLinkReader  $links The source-to-copy pairing.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SavedViewReader $views,
		private readonly AnonymizationLinkReader $links,
	) {

	}//end __construct()

	/**
	 * Compose the list over one saved view.
	 *
	 * @param string $view  The view id or slug.
	 * @param string $title What the list is called.
	 *
	 * @return array<string, mixed> The composed list, or a refusal carrying `refused`.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function compose(string $view, string $title = ''): array {
		$records = $this->views->records(view: $view);
		if ($records === null) {
			return [
				'refused' => self::NO_SUCH_VIEW,
				'message' => sprintf('The view "%s" does not resolve, so there is nothing to compose.', $view),
				'view' => $view,
			];
		}

		$entries = [];
		$notReady = [];

		foreach ($records as $record) {
			$fields = $this->fieldsOf(record: $record);
			$sourceFileId = (int)($fields['fileId'] ?? ($fields['sourceFileId'] ?? 0));
			$label = $this->labelOf(fields: $fields, sourceFileId: $sourceFileId);

			$link = $this->links->forSource(sourceFileId: $sourceFileId);
			$copyId = 0;
			if ($link !== null) {
				$copyId = (int)($link['anonymizedFileId'] ?? 0);
			}

			if ($copyId <= 0) {
				$notReady[] = ['record' => $label, 'sourceFileId' => $sourceFileId];
				continue;
			}

			// Only the copy travels. The source id is not carried into the
			// entry at all, so there is nothing for a later renderer to link
			// by accident.
			$entries[] = [
				'record' => $label,
				'fileId' => $copyId,
				'fileName' => (string)($link['anonymizedFileName'] ?? ''),
				'filePath' => (string)($link['anonymizedFilePath'] ?? ''),
			];
		}

		if ($notReady !== []) {
			return [
				'refused' => self::NOT_READY,
				'message' => sprintf(
					'%d of %d records in this view have no redacted copy yet, so the list is not composed. '
					.'Redact them, or take them out of the view.',
					count($notReady),
					count($records)
				),
				'view' => $view,
				'notReady' => $notReady,
			];
		}

		$heading = $title;
		if ($title === '') {
			$heading = $view;
		}

		return [
			'view' => $view,
			'title' => $heading,
			'count' => count($entries),
			'composedAt' => gmdate(format: 'c'),
			'entries' => $entries,
		];

	}//end compose()

	/**
	 * One record's fields, whatever shape OpenRegister returned it in.
	 *
	 * @param mixed $record The record.
	 *
	 * @return array<string, mixed> The fields.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function fieldsOf(mixed $record): array {
		$data = $record;
		if (is_object($record) === true && method_exists($record, 'jsonSerialize') === true) {
			$data = $record->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		if (isset($data['object']) === true && is_array($data['object']) === true) {
			return $data['object'];
		}

		return $data;

	}//end fieldsOf()

	/**
	 * What to call one record in the list.
	 *
	 * @param array<string, mixed> $fields       The record's fields.
	 * @param int                  $sourceFileId Its source file id.
	 *
	 * @return string The label.
	 *
	 * @spec exclude Naming helper behind compose().
	 */
	private function labelOf(array $fields, int $sourceFileId): string {
		$label = trim((string)($fields['title'] ?? ($fields['name'] ?? '')));
		if ($label !== '') {
			return $label;
		}

		if ($sourceFileId > 0) {
			return 'Document '.$sourceFileId;
		}

		return 'Unnamed record';

	}//end labelOf()
}//end class
