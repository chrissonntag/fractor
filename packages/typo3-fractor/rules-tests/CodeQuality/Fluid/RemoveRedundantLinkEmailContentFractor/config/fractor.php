<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Typo3Fractor\CodeQuality\Fluid\RemoveRedundantLinkEmailContentFractor;

return FractorConfiguration::configure()
    ->withRules([RemoveRedundantLinkEmailContentFractor::class]);
