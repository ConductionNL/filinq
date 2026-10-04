<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PostRegisterReader.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\PostRegisterReader
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-295f378dddd08df4a7b43d3e6a5c2b9e30ea7f567bda61d48790367d5f66c867',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/PostRegisterReader.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\PostRegisterReader',
    'shortName' => 'PostRegisterReader',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The post register\'s two derived answers.
 *
 * 🔴 THE DISCHARGE IS READ, NEVER WRITTEN. An inbound entry carries no
 * `answered` flag and never will: a flag can be set by anybody, at any time,
 * without an answer existing, and then the register reports a letter as dealt
 * with because somebody ticked a box. Reading it from the outbound entry that
 * NAMES the inbound one means the register can only claim a discharge that has
 * a document behind it.
 *
 * 🔑 THE SEARCH CONTRACT WAS CHECKED, NOT ASSUMED. OpenRegister\'s objects
 * search takes BARE property keys beside a `@self` block naming the register and
 * schema — `[\'@self\' => [...], \'answers\' => $uuid]` — and the sibling
 * aggregations endpoint spells the same filter the opposite way. A `filter[...]`
 * wrapper here would be read as the empty set and this method would report every
 * inbound entry as undischarged, confidently and silently.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 431,
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
      'REGISTER' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'name' => 'REGISTER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 45,
            'startFilePos' => 1788,
            'endTokenPos' => 45,
            'endFilePos' => 1795,
          ),
        ),
        'docComment' => '/**
 * The register the entries live in.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
      'SCHEMA' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'name' => 'SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'documentRegistration\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 58,
            'startFilePos' => 1868,
            'endTokenPos' => 58,
            'endFilePos' => 1889,
          ),
        ),
        'docComment' => '/**
 * The schema the entries live in.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
      'DIRECTION_INBOUND' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'name' => 'DIRECTION_INBOUND',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'inbound\'',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 71,
            'startFilePos' => 2362,
            'endTokenPos' => 71,
            'endFilePos' => 2370,
          ),
        ),
        'docComment' => '/**
 * The direction of a document that came in.
 *
 * 🔴 THE VALUE IS THE SCHEMA\'S, NOT THE PROSE\'S. `documentRegistration`
 * declares `enum: [inbound, outbound]`, and OpenRegister refuses anything
 * else on save, so no row can ever carry `incoming`. Filtering on the word
 * the proposal uses returned an empty open-post list on every unit, with
 * no error and nothing in the log to say the filter matched nothing.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'objectResolver' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'name' => 'objectResolver',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 3,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'objectResolver' => 
          array (
            'name' => 'objectResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCA\\Filinq\\Service\\DocumentObjectServiceResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 3,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 3,
            'endColumn' => 42,
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
 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister\'s ObjectService.
 * @param LoggerInterface               $logger         Structured logger.
 */',
        'startLine' => 76,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'answersFor' => 
      array (
        'name' => 'answersFor',
        'parameters' => 
        array (
          'inboundUuid' => 
          array (
            'name' => 'inboundUuid',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 29,
            'endColumn' => 47,
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
 * The outbound registrations that answer an inbound one.
 *
 * 🔑 IT RETURNS THE ANSWERS, NOT A BOOLEAN. "Discharged: yes" loses which
 * document did it, and that is the thing somebody reading the register a
 * year later actually wants. A caller that only needs the boolean can ask
 * whether the list is empty; a caller given a boolean cannot get the list
 * back.
 *
 * @param string $inboundUuid The inbound registration.
 *
 * @return array<int, array<string, mixed>> The outbound entries naming it, possibly empty.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 97,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'openPostFor' => 
      array (
        'name' => 'openPostFor',
        'parameters' => 
        array (
          'unit' => 
          array (
            'name' => 'unit',
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
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 30,
            'endColumn' => 41,
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
 * The undischarged inbound entries of a unit, oldest first.
 *
 * 🔴 IT IS BUILT FROM THE SAME READ AS THE DISCHARGE, NOT FROM A FLAG.
 * REQ-DIO-02 says the discharge is read from the link and never written as a
 * status, so "open" cannot be a stored field either: an entry is open when
 * nothing names it, which is a question asked of the outbound entries. A
 * cached `open` column would be the same lie as an `answered` flag, one step
 * further away from where anybody would look for it.
 *
 * 🔴 AN ENTRY WHOSE DISCHARGE COULD NOT BE READ IS RAISED, NOT LISTED AS
 * OPEN. Listing it would put a letter somebody answered a month ago at the
 * top of the work list, oldest first, and the handler would answer it again.
 * Leaving it out silently is worse still: a letter nobody answered would
 * vanish from the only list that would have caught it.
 *
 * 🔑 OLDEST FIRST IS THE POINT OF THE LIST. It is a work list, and the order
 * is what makes it one. An entry with no registration date sorts LAST rather
 * than first: an unknown date is not evidence of age, and sorting it first
 * would put it above letters that really have been waiting.
 *
 * @param string $unit The organisational unit.
 *
 * @return array<int, array<string, mixed>> The undischarged inbound entries, oldest first.
 *
 * @throws Throwable When the register could not be read.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 165,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'seriesFor' => 
      array (
        'name' => 'seriesFor',
        'parameters' => 
        array (
          'unit' => 
          array (
            'name' => 'unit',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 28,
            'endColumn' => 39,
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
 * A unit\'s series, read from the register and accounted for end to end.
 *
 * 🔑 THE FETCH LIVES BESIDE `series()` RATHER THAN IN THE CALLER. A
 * controller that built this query itself would be a second place the
 * objects endpoint\'s bare-key spelling has to be got right, and the wrong
 * spelling there answers the EMPTY SET rather than an error: the series
 * would read as a unit with no post at all.
 *
 * @param string $unit The organisational unit.
 *
 * @return array<int, array<string, mixed>> The series, ordered by number.
 *
 * @throws Throwable When the register could not be read.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 241,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'oldestFirst' => 
      array (
        'name' => 'oldestFirst',
        'parameters' => 
        array (
          'left' => 
          array (
            'name' => 'left',
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'right' => 
          array (
            'name' => 'right',
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 44,
            'endColumn' => 55,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Order two entries oldest first, with an undated entry last.
 *
 * @param array<string, mixed> $left  One entry.
 * @param array<string, mixed> $right The other.
 *
 * @return int The comparison.
 *
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod) Called as the callable array
 * `[$this, \'oldestFirst\']` from usort() at line 204. PHPMD resolves only
 * direct `$this->method()` calls, so a callable-array reference reads to it
 * as no caller at all — a false positive, verified by grep.
 *
 * @psalm-suppress UnusedReturnValue usort() consumes the comparison; psalm
 * reads the callable array no better than PHPMD does.
 *
 * @spec exclude Comparison helper; the ordering rule it implements is documented on openPostFor().
 */',
        'startLine' => 300,
        'endLine' => 317,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'plain' => 
      array (
        'name' => 'plain',
        'parameters' => 
        array (
          'row' => 
          array (
            'name' => 'row',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 328,
            'endLine' => 328,
            'startColumn' => 25,
            'endColumn' => 34,
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
 * One search result as a plain array.
 *
 * @param mixed $row The row as the object service returned it.
 *
 * @return array<string, mixed>|null The entry.
 *
 * @spec exclude Shape adapter over a search result; no behaviour of its own.
 */',
        'startLine' => 328,
        'endLine' => 353,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'withdrawalOf' => 
      array (
        'name' => 'withdrawalOf',
        'parameters' => 
        array (
          'registration' => 
          array (
            'name' => 'registration',
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
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 31,
            'endColumn' => 49,
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
 * Whether a number in the series was withdrawn, and why.
 *
 * 🔑 A GAP WITH A REASON IS A DECISION; A GAP WITHOUT ONE READS AS A LOST
 * DOCUMENT. That is the whole point of recording the withdrawal, so a
 * withdrawal carrying a reason and no moment, or a moment and no reason, is
 * reported as INCOMPLETE rather than quietly treated as either.
 *
 * @param array<string, mixed> $registration The registration.
 *
 * @return array{withdrawn: bool, complete: bool, reason: string, at: string} The withdrawal state.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 369,
        'endLine' => 383,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'aliasName' => NULL,
      ),
      'series' => 
      array (
        'name' => 'series',
        'parameters' => 
        array (
          'registrations' => 
          array (
            'name' => 'registrations',
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
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 25,
            'endColumn' => 44,
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
 * The series, in number order, with every gap accounted for.
 *
 * 🔴 THE POINT IS THAT IT READS END TO END. A numbered series with an
 * unexplained hole is the failure this whole requirement exists to prevent:
 * an auditor cannot tell a withdrawn allocation from a lost document, and
 * the register cannot tell them either. So every entry is returned in order
 * with its withdrawal state attached, and an entry whose withdrawal is
 * half-recorded is marked rather than smoothed over.
 *
 * @param array<int, array<string, mixed>> $registrations The entries.
 *
 * @return array<int, array<string, mixed>> The series, ordered by number.
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/document-register/spec.md
 */',
        'startLine' => 401,
        'endLine' => 430,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'implementingClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
        'currentClassName' => 'OCA\\Filinq\\Service\\PostRegisterReader',
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