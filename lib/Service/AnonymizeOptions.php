<?php

/**
 * Anonymize Options
 *
 * How one document is anonymised: output format, consents, overrides, acting user, placeholder scope, reversibility.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The options of one anonymise call. Immutable: every `with` method returns a changed copy.
 *
 * A fresh instance holds the defaults: PDF only, no consents, no overrides,
 * no acting user, per-document placeholder numbering, nothing reversible.
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class AnonymizeOptions {

	/**
	 * Output format: 'pdf-only', 'pdf' or 'preserve'.
	 *
	 * @var string
	 */
	private string $outputFormat = 'pdf-only';

	/**
	 * Entities to publish unredacted, each with a consent record.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $unredactedEntities = [];

	/**
	 * Acknowledged override entries {ruleId, entityId, reason?}.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $overrides = [];

	/**
	 * UID of the acting user, '' when none.
	 *
	 * @var string
	 */
	private string $userId = '';

	/**
	 * Placeholder-numbering scope: 'document' or 'dossier'.
	 *
	 * @var string
	 */
	private string $scope = 'document';

	/**
	 * Whether the caller says this run belongs to a Woo case (REQ-RWB-09).
	 *
	 * @var bool
	 */
	private bool $wooCase = false;

	/**
	 * Stable folder id of the dossier, null to let OpenRegister use the parent folder.
	 *
	 * @var string|null
	 */
	private ?string $dossierKey = null;

	/**
	 * Whether an encrypted key is kept so the placeholders can be turned back into names.
	 *
	 * @var bool
	 */
	private bool $reversible = false;

	/**
	 * Whether the grondslagen summary is appended. Set only by summaryRunFor().
	 *
	 * @var bool
	 */
	private bool $appendBasisSummary = false;

	/**
	 * A copy with this output format.
	 *
	 * @param string $outputFormat 'pdf-only', 'pdf' or 'preserve'.
	 *
	 * @return self
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function withOutputFormat(string $outputFormat): self {
		$copy = clone $this;
		$copy->outputFormat = $outputFormat;
		return $copy;
	}//end withOutputFormat()

	/**
	 * A copy with these consents and acknowledged overrides.
	 *
	 * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
	 * @param array<int, array<string, mixed>> $overrides          Acknowledged override entries {ruleId, entityId, reason?}.
	 *
	 * @return self
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function withConsents(array $unredactedEntities, array $overrides): self {
		$copy = clone $this;
		$copy->unredactedEntities = $unredactedEntities;
		$copy->overrides = $overrides;
		return $copy;
	}//end withConsents()

	/**
	 * A copy acting for this user.
	 *
	 * @param string $userId UID of the acting user.
	 *
	 * @return self
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function withUserId(string $userId): self {
		$copy = clone $this;
		$copy->userId = $userId;
		return $copy;
	}//end withUserId()

	/**
	 * A copy with this placeholder-numbering scope.
	 *
	 * @param string      $scope      'document' or 'dossier'.
	 * @param string|null $dossierKey Stable folder id for the dossier, null for the file's parent folder.
	 *
	 * @return self
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function withScope(string $scope, ?string $dossierKey): self {
		$copy = clone $this;
		$copy->scope = $scope;
		$copy->dossierKey = $dossierKey;
		return $copy;
	}//end withScope()

	/**
	 * Mark the run as part of a Woo case, so the four-eyes rule in `woo` mode applies.
	 *
	 * @param bool $wooCase Whether the caller says the document is in a Woo case.
	 *
	 * @return self A changed copy.
	 *
	 * @spec openspec/changes/redaction-four-eyes-setting/tasks.md#task-2
	 */
	public function withWooCase(bool $wooCase): self {
		$copy = clone $this;
		$copy->wooCase = $wooCase;
		return $copy;
	}//end withWooCase()

	/**
	 * A copy that keeps an encrypted key, so the placeholders can be turned back into names later.
	 *
	 * @return self
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function reversible(): self {
		$copy = clone $this;
		$copy->reversible = true;
		return $copy;
	}//end reversible()

	/**
	 * The run for this file and these entities, without the grondslagen summary.
	 *
	 * @param int                         $fileId   The Nextcloud file ID.
	 * @param array<array<string, mixed>> $entities The entities to anonymize.
	 *
	 * @return AnonymizeRun
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function runFor(int $fileId, array $entities): AnonymizeRun {
		return new AnonymizeRun(
			fileId: $fileId,
			entities: $entities,
			overrides: $this->overrides,
			options: [
				'appendBasisSummary' => $this->appendBasisSummary,
				'outputFormat' => $this->outputFormat,
				'unredactedEntities' => $this->unredactedEntities,
				'scope' => $this->scope,
				'dossierKey' => $this->dossierKey,
				'reversible' => $this->reversible,
				'userId' => $this->userId,
				'wooCase' => $this->wooCase,
			]
		);
	}//end runFor()

	/**
	 * The run for this file and these entities, with the grondslagen summary appended.
	 *
	 * @param int                         $fileId   The Nextcloud file ID.
	 * @param array<array<string, mixed>> $entities The entities to anonymize.
	 *
	 * @return AnonymizeRun
	 *
	 * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-2
	 */
	public function summaryRunFor(int $fileId, array $entities): AnonymizeRun {
		$copy = clone $this;
		$copy->appendBasisSummary = true;
		return $copy->runFor(fileId: $fileId, entities: $entities);
	}//end summaryRunFor()
}//end class
