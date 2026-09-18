<?php

/**
 * Intake Document Received Event
 *
 * Dispatched by every channel that delivers a document before anyone knows
 * which record it belongs to: the scan folder watch, the mail intake and the
 * digital post adapter. A feeder says what arrived; it never says where it
 * belongs. Filinq listens, creates one intake document in `received`, and the
 * assignment stays a clerk's decision.
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

use OCP\EventDispatcher\Event;

/**
 * One document arrived through one channel.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeDocumentReceivedEvent extends Event {

	/**
	 * The scanner channel.
	 *
	 * @var string
	 */
	public const CHANNEL_SCAN = 'scan';

	/**
	 * The shared mailbox channel.
	 *
	 * @var string
	 */
	public const CHANNEL_MAIL = 'mail';

	/**
	 * The digital post channel.
	 *
	 * @var string
	 */
	public const CHANNEL_DIGITAL_POST = 'digitalPost';

	/**
	 * Every channel this app accepts.
	 *
	 * @var array<int, string>
	 */
	public const CHANNELS = [
		self::CHANNEL_SCAN,
		self::CHANNEL_MAIL,
		self::CHANNEL_DIGITAL_POST,
	];

	/**
	 * Constructor.
	 *
	 * @param string $channel The channel that delivered the document.
	 * @param int|null $fileId Nextcloud file id of the delivered file.
	 * @param string $fileName Name of the delivered file.
	 * @param string $subject Subject line or document title.
	 * @param string $sender Who sent it.
	 * @param string $sourceRef The channel's own reference for this delivery.
	 * @param string|null $receivedAt When it arrived, ISO 8601, or null for now.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $channel,
		private readonly ?int $fileId = null,
		private readonly string $fileName = '',
		private readonly string $subject = '',
		private readonly string $sender = '',
		private readonly string $sourceRef = '',
		private readonly ?string $receivedAt = null,
	) {
		parent::__construct();

	}//end __construct()

	/**
	 * The channel that delivered the document.
	 *
	 * @return string The channel.
	 */
	public function getChannel(): string {
		return $this->channel;

	}//end getChannel()

	/**
	 * The Nextcloud file id, when the channel stored a file.
	 *
	 * @return int|null The file id.
	 */
	public function getFileId(): ?int {
		return $this->fileId;

	}//end getFileId()

	/**
	 * The file name as delivered.
	 *
	 * @return string The file name.
	 */
	public function getFileName(): string {
		return $this->fileName;

	}//end getFileName()

	/**
	 * The subject line or document title.
	 *
	 * @return string The subject.
	 */
	public function getSubject(): string {
		return $this->subject;

	}//end getSubject()

	/**
	 * Who sent the document.
	 *
	 * @return string The sender.
	 */
	public function getSender(): string {
		return $this->sender;

	}//end getSender()

	/**
	 * The channel's own reference for this delivery.
	 *
	 * @return string The reference.
	 */
	public function getSourceRef(): string {
		return $this->sourceRef;

	}//end getSourceRef()

	/**
	 * When the document arrived.
	 *
	 * @return string|null The ISO 8601 timestamp, or null when the channel did not say.
	 */
	public function getReceivedAt(): ?string {
		return $this->receivedAt;

	}//end getReceivedAt()
}//end class
