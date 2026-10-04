<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Portal/PortalContributionProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Portal\PortalContributionProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-a73a438403a92367dd0eb2192a090ca1ac1e8651bd3cd28d7cb02d2c374f3dc6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Portal/PortalContributionProvider.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Portal',
    'name' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
    'shortName' => 'PortalContributionProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Declares what an external portal subject may see in Filinq.
 *
 * The contribution is a declarative manifest (pure data — no I/O, no
 * callbacks). All subject identity (subjectRef, audience, organisation, trust)
 * is derived server-side by portaliq\'s auth edge and MUST never be trusted
 * from the client (ADR-005). Scoping uses server-managed portalAccount claims
 * (a contact-record reference for the data subject, the invited signer email
 * for the signer) — never Nextcloud user ids, because externals have no
 * Nextcloud account by premise (amendment A4).
 *
 * Every read collection ships an explicit `fields` whitelist so portaliq (which
 * whitelist-projects rows AFTER per-row verification — identifiers always
 * survive, a malformed declaration degrades to identifiers-only) never hands a
 * subject a staff-only or other-party column. The `signerSigningRequests`
 * collection uses the contract-v2.1 one-hop `via` join (A5): it routes through
 * the subject\'s own `signerRecord` rows to reach the parent `signingRequest`,
 * because `signingRequest` carries no direct subject-scope property. Rationale +
 * whitelist tables: openspec/changes/portal-contribution/design.md.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 90,
    'endLine' => 429,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'REGISTER_CONSENT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'REGISTER_CONSENT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 99,
            'endLine' => 99,
            'startTokenPos' => 35,
            'startFilePos' => 5090,
            'endTokenPos' => 35,
            'endFilePos' => 5097,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister register slug holding the consent surfaces.
 *
 * `filinq`, not `consent`: this app declares ONE register holding all 23
 * schemas. The five it used to declare are retired.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'REGISTER_SIGNING' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'REGISTER_SIGNING',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 112,
            'endLine' => 112,
            'startTokenPos' => 48,
            'startFilePos' => 5560,
            'endTokenPos' => 48,
            'endFilePos' => 5567,
          ),
        ),
        'docComment' => '/**
 * The OpenRegister register slug holding the signing surfaces.
 *
 * `filinq`, not `signing`, for the same reason. The two constants now hold
 * the same value and are deliberately NOT collapsed into one: they name two
 * different portal surfaces, and folding them together would lose the
 * record of which surface each call site is addressing the moment anything
 * ever moves again.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 112,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'LABEL' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'LABEL',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'Filinq\'',
          'attributes' => 
          array (
            'startLine' => 119,
            'endLine' => 119,
            'startTokenPos' => 61,
            'startFilePos' => 5692,
            'endTokenPos' => 61,
            'endFilePos' => 5699,
          ),
        ),
        'docComment' => '/**
 * The human label portaliq renders for this app\'s portal section.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 119,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'SIGN_ENDPOINT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'SIGN_ENDPOINT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/apps/filinq/api/portal/signing/sign\'',
          'attributes' => 
          array (
            'startLine' => 131,
            'endLine' => 131,
            'startTokenPos' => 74,
            'startFilePos' => 6084,
            'endTokenPos' => 74,
            'endFilePos' => 6121,
          ),
        ),
        'docComment' => '/**
 * Instance-local relative endpoint the `sign` rowAction resolves to.
 *
 * Targets the `portal-signing-actions` receiver\'s `signDocument` act
 * (design.md "The identity chain"). NOT YET implemented at HEAD (see this
 * class\'s docblock) — declared here so the manifest and the future
 * receiver ship in sync.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 131,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 70,
      ),
      'DECLINE_ENDPOINT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'DECLINE_ENDPOINT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/apps/filinq/api/portal/signing/decline\'',
          'attributes' => 
          array (
            'startLine' => 141,
            'endLine' => 141,
            'startTokenPos' => 87,
            'startFilePos' => 6399,
            'endTokenPos' => 87,
            'endFilePos' => 6439,
          ),
        ),
        'docComment' => '/**
 * Instance-local relative endpoint the `decline` rowAction resolves to.
 *
 * Targets the `portal-signing-actions` receiver\'s `declineDocument` act.
 * NOT YET implemented at HEAD — see `SIGN_ENDPOINT`.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 76,
      ),
      'VIEW_DOCUMENT_ENDPOINT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'VIEW_DOCUMENT_ENDPOINT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/apps/filinq/api/portal/signing/viewDocument\'',
          'attributes' => 
          array (
            'startLine' => 152,
            'endLine' => 152,
            'startTokenPos' => 100,
            'startFilePos' => 6769,
            'endTokenPos' => 100,
            'endFilePos' => 6814,
          ),
        ),
        'docComment' => '/**
 * Instance-local relative endpoint the `viewDocument` A6 action resolves to.
 *
 * Targets the `portal-signing-actions` receiver\'s `viewDocument` act
 * (REQ-DDPSA-006) — lets the verified invited signer read the target
 * document before signing.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 152,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 87,
      ),
      'SIGNING_MIN_TRUST' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'name' => 'SIGNING_MIN_TRUST',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'substantial\'',
          'attributes' => 
          array (
            'startLine' => 167,
            'endLine' => 167,
            'startTokenPos' => 113,
            'startFilePos' => 7378,
            'endTokenPos' => 113,
            'endFilePos' => 7390,
          ),
        ),
        'docComment' => '/**
 * Minimum eIDAS-aligned portal trust required to sign or decline.
 *
 * An advanced-electronic-signature-grade act requires a
 * substantial-assurance portal session (design.md "eIDAS levels"). This
 * surface exposes SES/AES assurance only and never claims QES
 * (qualified electronic signature, eIDAS Article 3(12)) — QES is
 * certificate-backed via an external QTSP and is explicitly out of scope
 * (REQ-DDPSS-005); the exposed assurance MUST NOT exceed this session
 * trust.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 167,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 49,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getAudiences' => 
      array (
        'name' => 'getAudiences',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The audiences this provider contributes to (contract v2, preferred).
 *
 * The registry probes for this method first. Filinq serves WOO-affected
 * data subjects (`data-subject`) and external document signers (`signer`).
 *
 * @return array<int, string> The audience identifiers.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */',
        'startLine' => 179,
        'endLine' => 181,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'aliasName' => NULL,
      ),
      'getAudience' => 
      array (
        'name' => 'getAudience',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The primary audience this provider contributes to (contract v1 fallback).
 *
 * Kept alongside getAudiences() so the provider also works against a v1
 * registry that predates multi-audience support.
 *
 * @return string The primary audience identifier.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */',
        'startLine' => 193,
        'endLine' => 195,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'aliasName' => NULL,
      ),
      'getContribution' => 
      array (
        'name' => 'getContribution',
        'parameters' => 
        array (
          'subject' => 
          array (
            'name' => 'subject',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 34,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the declarative portal manifest for one resolved subject.
 *
 * The subject array is server-derived by portaliq (subjectRef UUID,
 * audience, organisation, trust level low|substantial|high). Returns null
 * for any audience Filinq does not serve (fail-closed; the registry
 * already filters by audience, but a provider must not rely on that). This
 * wave declares read collections only — no create or endpoint actions.
 *
 * @param array<string, mixed> $subject The resolved portal subject.
 *
 * @return array<string, mixed>|null The manifest, or null when not contributing.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */',
        'startLine' => 212,
        'endLine' => 224,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'aliasName' => NULL,
      ),
      'dataSubjectContribution' => 
      array (
        'name' => 'dataSubjectContribution',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Manifest for the `data-subject` audience (a WOO-affected entity).
 *
 * Scoped by `publicationConsent.contactRef` — the linkage pointer to the
 * canonical Nextcloud Contact record — via the `contactId` claim. The
 * PII-in-clear `contactEmail` is deliberately NOT used as the scope: a
 * cleartext email is a weaker identity binding than the contact reference
 * (design.md "Claim-names contract"). `minTrust: substantial` because a
 * consent/objection case file carries the subject\'s own GDPR/WOO rights
 * data (mirrors the Wave-1 avgVerzoek gating). The field whitelist projects
 * only subject-safe transparency + objection-rights columns; every internal
 * detection key, notification-delivery internal, staff note, matching rule
 * and the polymorphic prohibition linkage is dropped (design.md exclusions).
 *
 * @return array<string, mixed> The data-subject manifest.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 */',
        'startLine' => 244,
        'endLine' => 277,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'aliasName' => NULL,
      ),
      'signerContribution' => 
      array (
        'name' => 'signerContribution',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Manifest for the `signer` audience (an external document signer).
 *
 * `signerRecord` is scoped directly by the invited `email` via the
 * `signerEmail` claim: the record carries no external-contact UUID, and its
 * `userId` is a Nextcloud account id — unusable for accountless externals
 * (amendment A4) — so the verified invitation email is the only stable
 * subject key (design.md documents this PII-in-clear scope choice). Its
 * whitelist exposes only the signer\'s own participation facts; the base64
 * `signatureData` (schema `visible:false`), captured `ipAddress`, internal
 * `userId` and parent linkage are dropped.
 *
 * `signerSigningRequests` reaches the parent `signingRequest` through the
 * one-hop `via` join over the subject\'s own `signerRecord` rows
 * (targetField `signingRequestId`); it is gated at `substantial` because it
 * reveals which binding documents await the subject\'s signature, and its
 * whitelist drops the initiator\'s identity (`initiatorUserId`), the full
 * co-signer roster (`signerIds`) and the internal Nextcloud `documentFileId`
 * — every other-party and system-internal column.
 *
 * `signerSigningRequests` additionally carries contract-v2.2 `rowActions`
 * — `sign` and `decline` (REQ-DDPSS-001) — so portaliq renders a
 * per-document sign/decline control on exactly the rows awaiting the
 * subject. Each rowAction is gated `minTrust: substantial`
 * (eIDAS-aligned: an AES-grade act needs a substantial-assurance
 * session — `SIGNING_MIN_TRUST`) and resolves to an instance-local
 * relative endpoint (`SIGN_ENDPOINT` / `DECLINE_ENDPOINT`). This surface
 * exposes SES/AES assurance only; QES (qualified electronic signature,
 * certificate-backed via an external QTSP, eIDAS Article 3(12)) is
 * delegated and never claimed here (REQ-DDPSS-005). The rowActions are
 * pure data — no I/O, no callbacks — keeping this class plain and
 * dependency-free; the `signerRecords` collection and the entire
 * `data-subject` manifest carry no write action. The endpoints they name
 * target the `portal-signing-actions` receiver
 * (`PortalSigningReceiverController`), now implemented — a row already
 * in a terminal state (`signed`/`declined`) must not offer the actions,
 * which is the receiver\'s terminal-state guard (via the honest
 * `SigningService` status machine) to enforce, not something this
 * pure-data manifest can express.
 *
 * The manifest\'s top-level `actions` array additionally declares the
 * SAME three acts as contract-v2 A6 `endpoint`-type actions — `sign`,
 * `decline`, `viewDocument` (REQ-DDPSA-001) — for the A6
 * `POST /portal/api/actions/{appId}/{actionId}` forward mechanism, which
 * is a distinct rendering path from the per-row `rowActions` above (both
 * ultimately hit the SAME `PortalSigningReceiverController` endpoints).
 * `viewDocument` (GET, REQ-DDPSA-006) has no rowAction equivalent — it
 * lets the signer read the target document before deciding to sign or
 * decline.
 *
 * @return array<string, mixed> The signer manifest.
 *
 * @spec openspec/changes/portal-contribution/specs/portal-contribution/spec.md
 * @spec openspec/specs/portal-signing-surface/spec.md
 * @spec openspec/specs/portal-signing-actions/spec.md
 */',
        'startLine' => 335,
        'endLine' => 428,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Portal',
        'declaringClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'implementingClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'currentClassName' => 'OCA\\Filinq\\Portal\\PortalContributionProvider',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));