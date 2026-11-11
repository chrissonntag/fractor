<?php

declare(strict_types=1);

namespace a9f\Typo3Fractor\TYPO3v14\Fluid;

use a9f\FractorFluid\Contract\FluidFractorRule;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Feature-93334-TranslationDomainMapping.html
 * @see \a9f\Typo3Fractor\Tests\TYPO3v14\Fluid\MigrateLabelFileReferenceToTranslationDomainFractor\MigrateLabelFileReferenceToTranslationDomainFractorTest
 */
final class MigrateLabelFileReferenceToTranslationDomainFractor implements FluidFractorRule
{
    private const LABEL_REFERENCE_PATTERN = '#LLL:EXT:(?<extension>[a-z0-9_]+)/(?<path>[\w/-]+)\.xlf:(?<label>[\w.-]+)#';

    private const DOMAIN_PATTERN = '#^[a-z0-9_]+(\.[a-z0-9_]+)+$#';

    /**
     * Core's LabelFileResolver only assigns domains to label files in these directories.
     */
    private const DISCOVERABLE_PATH_PATTERN = '#^(?:Resources/Private/Language|Configuration/Sets)/#';

    /**
     * Label files whose domain resolves to another file with different label IDs.
     */
    private const KNOWN_COLLISIONS = ['form/Resources/Private/Language/locallang_module'];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace LLL:EXT: label file references with TYPO3 14 translation domains',
            [new CodeSample(
                <<<'CODE_SAMPLE'
<f:translate key="LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:news.loadMore" />
<f:translate key="LLL:EXT:my_extension/Resources/Private/Language/Form/locallang_tabs.xlf:general" />
{f:translate(key: 'LLL:EXT:my_extension/Configuration/Sets/MyExtension/labels.xlf:title')}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
<f:translate key="my_extension.messages:news.loadMore" />
<f:translate key="my_extension.form.tabs:general" />
{f:translate(key: 'my_extension.sets.my_extension:title')}
CODE_SAMPLE
            )]
        );
    }

    public function refactor(string $fluid): string
    {
        return (string) preg_replace_callback(
            self::LABEL_REFERENCE_PATTERN,
            static function (array $match): string {
                if (preg_match(self::DISCOVERABLE_PATH_PATTERN, $match['path']) !== 1) {
                    return $match[0];
                }

                if (in_array($match['extension'] . '/' . $match['path'], self::KNOWN_COLLISIONS, true)) {
                    return $match[0];
                }

                $domain = $match['extension'] . '.' . self::mapPathToResource($match['path']);
                if (preg_match(self::DOMAIN_PATTERN, $domain) !== 1) {
                    return $match[0];
                }

                return $domain . ':' . $match['label'];
            },
            $fluid
        );
    }

    /**
     * Mirrors TYPO3\CMS\Core\Localization\TranslationDomainResolver::transformFilePathToResource().
     */
    private static function mapPathToResource(string $path): string
    {
        if (preg_match('#^Configuration/Sets/([^/]+)/labels$#', $path, $matches) === 1) {
            return 'sets.' . self::toSnakeCase($matches[1]);
        }

        if (str_starts_with($path, 'Resources/Private/Language/')) {
            $path = substr($path, strlen('Resources/Private/Language/'));
        }

        $parts = explode('/', $path);
        $fileName = array_pop($parts);
        $parts = array_map(self::toSnakeCase(...), $parts);

        if ($fileName === 'locallang') {
            $parts[] = 'messages';
        } elseif (str_starts_with($fileName, 'locallang_')) {
            $parts[] = self::toSnakeCase(substr($fileName, strlen('locallang_')));
        } else {
            $parts[] = self::toSnakeCase($fileName);
        }

        return implode('.', $parts);
    }

    private static function toSnakeCase(string $value): string
    {
        return str_replace('-', '_', strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value)));
    }
}
