<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ExternalMountValidator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\ExternalMountValidator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-65e8748eada55ae1eb55309bbcd09cd65f589a5eea440052e0faaf84cbac37ec',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/ExternalMountValidator.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
    'shortName' => 'ExternalMountValidator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Names what a mount cannot do, before a domain is put on it.
 *
 * 🔴 IT NAMES THE REQUIREMENT, NOT THE CAPABILITY. REQ-CDF-06 asks setup to
 * "name the reconciliation requirement it cannot meet", and a report reading
 * "supportsPerGroupPermissions: false" tells the administrator a property name,
 * not a consequence. So every finding carries the sentence somebody has to act
 * on: which promise filinq will stop keeping on this mount.
 *
 * 🔴 "COULD NOT BE FOUND OUT" IS ITS OWN FINDING, LOUDER THAN "NO". A mount
 * that answers no is one filinq knows how to describe; a mount that answers
 * nothing is one where the first sign of a problem is a group that still has
 * access to a folder it was removed from. Folding null into either true or
 * false loses exactly that, so `unknown` is reported apart from `cannot`.
 *
 * 🔑 IT REFUSES NOTHING AND CONFIGURES NOTHING. A validator that blocked the
 * mount would be a policy decision an administrator may legitimately overrule
 * for a store whose permissions are managed outside Nextcloud. What it must not
 * do is let that decision be made without the consequence in front of them, so
 * it always returns the findings and lets setup decide.
 *
 * 🔑 THE UPLOAD POLICY IS READ, NOT ASSUMED. A mount that bypasses filinq on
 * write matters only when there IS a policy to bypass; on an instance with no
 * policy declared, reporting an unenforceable one would be a warning about a
 * rule nobody wrote.
 *
 * @category Service
 * @package  OCA\\Filinq\\Service
 * @author   Conduction Development Team <dev@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 61,
    'endLine' => 207,
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
      'CANNOT' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'name' => 'CANNOT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'cannot\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 40,
            'startFilePos' => 2539,
            'endTokenPos' => 40,
            'endFilePos' => 2546,
          ),
        ),
        'docComment' => '/**
 * A requirement the mount cannot meet.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'UNKNOWN' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'name' => 'UNKNOWN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'unknown\'',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 53,
            'startFilePos' => 2651,
            'endTokenPos' => 53,
            'endFilePos' => 2659,
          ),
        ),
        'docComment' => '/**
 * A requirement nobody could find out about.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
    ),
    'immediateProperties' => 
    array (
      'probe' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'name' => 'probe',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 3,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'uploadPolicy' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'name' => 'uploadPolicy',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\UploadPolicyService',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 3,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'probe' => 
          array (
            'name' => 'probe',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\MountCapabilityProbe',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 3,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'uploadPolicy' => 
          array (
            'name' => 'uploadPolicy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\UploadPolicyService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 3,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Collaborators.
 *
 * @param MountCapabilityProbe $probe        Asks the mount what it can do.
 * @param UploadPolicyService  $uploadPolicy The policy whose enforcement is at stake.
 *
 * @return void
 */',
        'startLine' => 85,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
 * Validate the store a domain would live on.
 *
 * @param string $path The folder the domain would live in.
 *
 * @return array{path: string, ok: bool,
 *               findings: array<int, array{requirement: string, verdict: string, message: string}>}
 *               What it can and cannot keep.
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 103,
        'endLine' => 143,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'aliasName' => NULL,
      ),
      'hasUploadPolicy' => 
      array (
        'name' => 'hasUploadPolicy',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether a policy is declared at all.
 *
 * @return bool True when there is a policy whose enforcement can be at stake.
 *
 * @spec exclude Reads UploadPolicyService; the behaviour under test is validate().
 */',
        'startLine' => 152,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'aliasName' => NULL,
      ),
      'consider' => 
      array (
        'name' => 'consider',
        'parameters' => 
        array (
          'findings' => 
          array (
            'name' => 'findings',
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 180,
            'endLine' => 180,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'answer' => 
          array (
            'name' => 'answer',
            'default' => NULL,
            'type' => 
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
                      'name' => 'bool',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 181,
            'endLine' => 181,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'requirement' => 
          array (
            'name' => 'requirement',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'cannot' => 
          array (
            'name' => 'cannot',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'unknown' => 
          array (
            'name' => 'unknown',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 184,
            'endLine' => 184,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Turn one three-valued answer into nothing, a cannot, or an unknown.
 *
 * @param array<int, array{requirement: string, verdict: string, message: string}> $findings    The findings so far, added to in place.
 * @param bool|null                                                                $answer      What the probe said.
 * @param string                                                                   $requirement The promise at stake.
 * @param string                                                                   $cannot      What to say when the mount cannot.
 * @param string                                                                   $unknown     What to say when nobody could find out.
 *
 * @return void
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */',
        'startLine' => 179,
        'endLine' => 206,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'implementingClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
        'currentClassName' => 'OCA\\Filinq\\Service\\ExternalMountValidator',
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