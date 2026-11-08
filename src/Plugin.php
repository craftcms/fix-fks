<?php

declare(strict_types=1);

namespace CraftCms\FixFks;

use CraftCms\Cms\Plugin\Plugin as BasePlugin;
use CraftCms\FixFks\Utilities\FixFks;

/**
 * Fix FKs plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Plugin extends BasePlugin
{
    public string $minVersionRequired = '2.0.0';

    protected array $utilities = [
        FixFks::class,
    ];
}
