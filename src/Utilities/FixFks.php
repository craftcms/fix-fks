<?php

declare(strict_types=1);

namespace CraftCms\FixFks\Utilities;

use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\View\TemplateMode;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Restores missing foreign key constraints from the control panel.
 */
class FixFks extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Fix FKs', category: 'fix-fks');
    }

    #[Override]
    public static function id(): string
    {
        return 'fix-fks';
    }

    #[Override]
    public static function icon(): string
    {
        return 'database';
    }

    #[Override]
    public static function contentHtml(): string
    {
        return template('fix-fks/_index', templateMode: TemplateMode::Cp);
    }
}
