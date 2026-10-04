<?php declare(strict_types = 1);

// ftm-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinancialExtractionService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      '4b4541d06028bee9be751741a8600655' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '946b7012412e01d26f022f75b23d7745' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '50574fc29a57f928813b3638ac534ba2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractFinancial',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b8b9ad13cb49436f43287ae9a1cc5cb1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'resolveRequestParams',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'ac6c68c45cba936e63c624b9f5bdd58b' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractFileReference',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '52d4e7e9786bea497561eb9525811473' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'resolveFileIdFallback',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '40d6b1ace490e12d6b7fdcac98081e6e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'addCorrection',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '8ae292b528c9e37a261b778541a34c9d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'runExtraction',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2aaa8910a85973c5af558de09ba7508d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'applyAiEnhancement',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2cbfac255154b84dd1fd2e12ca2419cb' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'mergeAiFields',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'ffad88d7432973d96c8b967d576bc4f5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'applyExtraction',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '7f642a2221bd4d351eefdc2809a67017' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractSupplierName',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '6ebeabd71bc484071e1b2d0b68dcf65c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractInvoiceNumber',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '3fe69516290993ede775490e93934aff' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractCurrency',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '2fadd229113eecd01b289326f3f4e891' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractVatBreakdown',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '5bbf790e3a01d9c59edfa3e3e26f27c0' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'splitBaseAndAmount',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'a5a738d0a30df00b1e2e5326b95319fe' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'lineContaining',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'efa202a6b854743dbadd4f2481193e8d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'aggregateConfidence',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b85cd442103e8fb63740fdf41562d596' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'fillableFields',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '68a63507471754e84e17cf3ae06a193d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'lockedFields',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f5fb06b85ec9b4a0c5ac13c0831d9215' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'resolveAiManager',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b70f248c6e0fa1ec63bb420d0c9ee1f6' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'runAiTask',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '71ad9c093d162ebff03096a04ce39dfb' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'buildPrompt',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'ad232102cd19a61d2c7978e8fed4d86a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'stripCodeFences',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '6c9a5aefe591c71b81590274ed8ede8a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'dispatchCompletionEvent',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '79ae7a07e714f411a187a2fbd717a543' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'resolveText',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'e722f02730a422b3d0912d6317f4b3c5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'runOcr',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'd7728ee3994df1c056ce82c6fb3e3a9d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'extractEmbeddedPdfText',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '03256b93e4f0c2fd64ca368cd6fdfeba' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'resolveFile',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'dc10046b1bdaae99ec820992ef214222' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'writeToTemp',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '9ba3d2069ff431bb338c653cae703dd5' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
         'functionName' => 'toArray',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'OCA\\Filinq\\Service',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'datetimeinterface' => 'DateTimeInterface',
            'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
            'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
            'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
            'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
            'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
            'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
            'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
            'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
            'file' => 'OCP\\Files\\File',
            'irootfolder' => 'OCP\\Files\\IRootFolder',
            'iusersession' => 'OCP\\IUserSession',
            'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
            'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
            'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
            'textprocessingtask' => 'OCP\\TextProcessing\\Task',
            'containerinterface' => 'Psr\\Container\\ContainerInterface',
            'loggerinterface' => 'Psr\\Log\\LoggerInterface',
            'runtimeexception' => 'RuntimeException',
            'throwable' => 'Throwable',
          ),
           'className' => 'OCA\\Filinq\\Service\\FinancialExtractionService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '824e4e47e2bae9c5a793158429ffe811' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'OCA\\Filinq\\Service',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'datetimeinterface' => 'DateTimeInterface',
          'financialextractioncompletedevent' => 'OCA\\Filinq\\Event\\FinancialExtractionCompletedEvent',
          'amountextractor' => 'OCA\\Filinq\\Service\\Extraction\\AmountExtractor',
          'dateextractor' => 'OCA\\Filinq\\Service\\Extraction\\DateExtractor',
          'ibanextractor' => 'OCA\\Filinq\\Service\\Extraction\\IbanExtractor',
          'kvkextractor' => 'OCA\\Filinq\\Service\\Extraction\\KvkExtractor',
          'totalsreconciler' => 'OCA\\Filinq\\Service\\Extraction\\TotalsReconciler',
          'vatidextractor' => 'OCA\\Filinq\\Service\\Extraction\\VatIdExtractor',
          'ieventdispatcher' => 'OCP\\EventDispatcher\\IEventDispatcher',
          'file' => 'OCP\\Files\\File',
          'irootfolder' => 'OCP\\Files\\IRootFolder',
          'iusersession' => 'OCP\\IUserSession',
          'taskprocessingtask' => 'OCP\\TaskProcessing\\Task',
          'texttotext' => 'OCP\\TaskProcessing\\TaskTypes\\TextToText',
          'freeprompttasktype' => 'OCP\\TextProcessing\\FreePromptTaskType',
          'textprocessingtask' => 'OCP\\TextProcessing\\Task',
          'containerinterface' => 'Psr\\Container\\ContainerInterface',
          'loggerinterface' => 'Psr\\Log\\LoggerInterface',
          'runtimeexception' => 'RuntimeException',
          'throwable' => 'Throwable',
        ),
         'className' => NULL,
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/FinancialExtractionService.php' => 'd6736a742afc761ed11f137ca52364daa07efd4969825fc63fe27c2a4cac7eae',
    ),
  ),
));