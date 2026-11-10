<?php

declare(strict_types=1);

namespace a9f\Typo3Fractor\CodeQuality\Fluid;

use a9f\FractorFluid\Contract\FluidFractorRule;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Link/Email.html
 * @see \a9f\Typo3Fractor\Tests\CodeQuality\Fluid\RemoveRedundantLinkEmailContentFractor\RemoveRedundantLinkEmailContentFractorTest
 */
final class RemoveRedundantLinkEmailContentFractor implements FluidFractorRule
{
    private const TAG_PATTERN = '#<f:link\.email(?<attributes>(?:\s(?:[^>"\'/]|"[^"]*"|\'[^\']*\')*)?)>(?<content>[^<]*)</f:link\.email>#';

    private const EMAIL_ATTRIBUTE_PATTERN = '#\semail\s*=\s*(?:"(?<double>[^"]*)"|\'(?<single>[^\']*)\')#';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove f:link.email content that repeats the email argument, so the link text respects spamProtectEmailAddresses',
            [new CodeSample(
                <<<'CODE_SAMPLE'
<f:link.email email="{address.email}">{address.email}</f:link.email>
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
<f:link.email email="{address.email}"/>
CODE_SAMPLE
            )]
        );
    }

    public function refactor(string $fluid): string
    {
        return (string) preg_replace_callback(
            self::TAG_PATTERN,
            static function (array $match): string {
                if (preg_match(self::EMAIL_ATTRIBUTE_PATTERN, $match['attributes'], $email) !== 1) {
                    return $match[0];
                }

                $address = ($email['single'] ?? '') !== '' ? $email['single'] : $email['double'];
                if ($address === '' || trim($match['content']) !== $address) {
                    return $match[0];
                }

                return '<f:link.email' . rtrim($match['attributes']) . '/>';
            },
            $fluid
        );
    }
}
