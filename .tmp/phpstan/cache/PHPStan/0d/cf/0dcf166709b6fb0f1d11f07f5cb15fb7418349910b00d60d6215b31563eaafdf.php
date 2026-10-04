<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Office/SupportedTypeProbe.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Office\SupportedTypeProbe
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-bc036ec70a227ba2b7de5d977d9bb40d6a0811ab2f4cea78bf6bc97ebd4effdd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Office/SupportedTypeProbe.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service\\Office',
    'name' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
    'shortName' => 'SupportedTypeProbe',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Parses a WOPI discovery document into a per-type support declaration.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 266,
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
      'CANDIDATE_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'name' => 'CANDIDATE_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'odt\', \'docx\', \'doc\', \'ods\', \'xlsx\', \'xls\', \'odp\', \'pptx\', \'ppt\', \'odg\', \'csv\', \'pdf\']',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 58,
            'startTokenPos' => 35,
            'startFilePos' => 1808,
            'endTokenPos' => 73,
            'endFilePos' => 1910,
          ),
        ),
        'docComment' => '/**
 * Every type the editing tools could plausibly be asked to handle.
 *
 * A type NOT on this list is never reported as supported, however the suite
 * advertises it: the list is the set of types this app has decided how to
 * treat, and discovering an unexpected one is a reason to think, not to
 * enable it silently.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'REFUSED_TYPES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'name' => 'REFUSED_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'docm\', \'xlsm\', \'pptm\', \'odb\']',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 86,
            'startFilePos' => 2325,
            'endTokenPos' => 97,
            'endFilePos' => 2355,
          ),
        ),
        'docComment' => '/**
 * Types refused regardless of what the suite advertises.
 *
 * 🔴 Macro-bearing formats are a code-execution vector in document
 * clothing, and `.odb` is a database with no "edit a cell" semantics. The
 * refusal lives HERE, in front of the probe, so a suite that happily edits
 * them cannot talk the system into offering them.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 62,
      ),
      'REFUSAL_GROUNDS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'name' => 'REFUSAL_GROUNDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'docm\' => \'macro-bearing format — editing it is a code-execution vector\', \'xlsm\' => \'macro-bearing format — editing it is a code-execution vector\', \'pptm\' => \'macro-bearing format — editing it is a code-execution vector\', \'odb\' => \'a database, with no document block or cell to edit\']',
          'attributes' => 
          array (
            'startLine' => 82,
            'endLine' => 87,
            'startTokenPos' => 110,
            'startFilePos' => 2780,
            'endTokenPos' => 140,
            'endFilePos' => 3082,
          ),
        ),
        'docComment' => '/**
 * Why each refused type is refused.
 *
 * Two different reasons live in one list and they are not interchangeable:
 * three are refused because editing them is a code-execution vector, and one
 * because "edit a cell" means nothing in it. An operator asking to have a
 * refusal lifted needs to know which argument they are up against.
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 82,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'declare' => 
      array (
        'name' => 'declare',
        'parameters' => 
        array (
          'discoveryXml' => 
          array (
            'name' => 'discoveryXml',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 26,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'suite' => 
          array (
            'name' => 'suite',
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
                      'name' => 'string',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 48,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'probedAt' => 
          array (
            'name' => 'probedAt',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 64,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'endpoint' => 
          array (
            'name' => 'endpoint',
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
            'startLine' => 101,
            'endLine' => 101,
            'startColumn' => 82,
            'endColumn' => 97,
            'parameterIndex' => 3,
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
 * Build the support declaration from a WOPI discovery document.
 *
 * @param string $discoveryXml The suite\'s WOPI discovery document.
 * @param string|null $suite The identified suite name, when known.
 * @param string $probedAt ISO-8601 timestamp of this measurement.
 * @param string $endpoint The endpoint measured, for provenance.
 *
 * @return array<string, mixed> The declaration.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-0.2
 */',
        'startLine' => 101,
        'endLine' => 146,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'aliasName' => NULL,
      ),
      'unavailable' => 
      array (
        'name' => 'unavailable',
        'parameters' => 
        array (
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 30,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'probedAt' => 
          array (
            'name' => 'probedAt',
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
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 1,
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
 * An empty declaration, for when the suite could not be reached.
 *
 * ⚠️ Every type reports UNSUPPORTED rather than unknown. A probe that could
 * not run has not established that anything works, and treating "we could
 * not ask" as "probably yes" is the exact failure this class exists to
 * prevent.
 *
 * @param string $reason Why the probe could not run.
 * @param string $probedAt ISO-8601 timestamp of the attempt.
 *
 * @return array<string, mixed> The declaration.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-0.2
 */',
        'startLine' => 163,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'aliasName' => NULL,
      ),
      'extensionsFor' => 
      array (
        'name' => 'extensionsFor',
        'parameters' => 
        array (
          'discoveryXml' => 
          array (
            'name' => 'discoveryXml',
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
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 55,
            'endColumn' => 68,
            'parameterIndex' => 1,
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
 * The extensions the discovery document declares for one action.
 *
 * @param string $discoveryXml The discovery document.
 * @param string $action The WOPI action name (`edit`, `view`).
 *
 * @return array<string, true> Extension set, as a lookup.
 */',
        'startLine' => 199,
        'endLine' => 221,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'aliasName' => NULL,
      ),
      'versionFrom' => 
      array (
        'name' => 'versionFrom',
        'parameters' => 
        array (
          'discoveryXml' => 
          array (
            'name' => 'discoveryXml',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 31,
            'endColumn' => 50,
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
                  'name' => 'string',
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
 * The suite version, when the discovery document carries one.
 *
 * @param string $discoveryXml The discovery document.
 *
 * @return string|null The version, or null.
 */',
        'startLine' => 230,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'aliasName' => NULL,
      ),
      'reasonFor' => 
      array (
        'name' => 'reasonFor',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'refused' => 
          array (
            'name' => 'refused',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'advertised' => 
          array (
            'name' => 'advertised',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 58,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
 * Say why a type is or is not editable.
 *
 * @param string $type The candidate type.
 * @param bool $refused Whether this app refuses it outright.
 * @param bool $advertised Whether the suite advertises editing it.
 *
 * @return string The reason.
 */',
        'startLine' => 251,
        'endLine' => 265,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service\\Office',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
        'currentClassName' => 'OCA\\Filinq\\Service\\Office\\SupportedTypeProbe',
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