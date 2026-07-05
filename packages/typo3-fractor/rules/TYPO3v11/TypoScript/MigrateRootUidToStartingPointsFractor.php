<?php

declare(strict_types=1);

namespace a9f\Typo3Fractor\TYPO3v11\TypoScript;

use a9f\FractorTypoScript\AbstractTypoScriptFractor;
use Helmich\TypoScriptParser\Parser\AST\ObjectPath;
use Helmich\TypoScriptParser\Parser\AST\Operator\Assignment;
use Helmich\TypoScriptParser\Parser\AST\Statement;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.4/Deprecation-95037-RootUidRelatedSettingOfTrees.html
 */
final class MigrateRootUidToStartingPointsFractor extends AbstractTypoScriptFractor
{
    public function refactor(Statement $statement): null|Statement
    {
        if (! $statement instanceof Assignment) {
            return null;
        }

        $objectPath = $statement->object;
        $pathSegments = explode('.', $objectPath->absoluteName);
        $lastSegment = end($pathSegments);

        if ($lastSegment !== 'rootUid') {
            return null;
        }

        $parentPath = $objectPath->parent();
        $parentSegments = explode('.', $parentPath->absoluteName);

        if (end($parentSegments) !== 'treeConfig') {
            return null;
        }

        $newAbsoluteName = $parentPath->absoluteName . '.startingPoints';
        $newRelativeName = preg_replace('/rootUid$/', 'startingPoints', $objectPath->relativeName);
        $statement->object = new ObjectPath($newAbsoluteName, $newRelativeName);

        return $statement;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Migrate TSconfig treeConfig.rootUid to treeConfig.startingPoints', [new CodeSample(
            <<<'CODE_SAMPLE'
TCEFORM.aTable.aField.config.treeConfig.rootUid = 42
CODE_SAMPLE
            ,
            <<<'CODE_SAMPLE'
TCEFORM.aTable.aField.config.treeConfig.startingPoints = 42
CODE_SAMPLE
        )]);
    }
}
