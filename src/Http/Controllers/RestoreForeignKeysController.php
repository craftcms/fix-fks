<?php

declare(strict_types=1);

namespace CraftCms\FixFks\Http\Controllers;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\FixFks\ForeignKeyRestorer;
use CraftCms\FixFks\Utilities\FixFks;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class RestoreForeignKeysController
{
    use RespondsWithFlash;

    public function __construct(Utilities $utilities)
    {
        if (! $utilities->checkAuthorization(FixFks::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(ForeignKeyRestorer $restorer): Response
    {
        ['restored' => $restored, 'failed' => $failed] = $restorer->restore();

        if ($failed !== [] && $restored !== []) {
            return $this->asFailure(t('Restored {count, plural, =1{# foreign key} other{# foreign keys}}, but couldn’t restore these: {keys}. Check the logs for details.', [
                'count' => count($restored),
                'keys' => implode('; ', $failed),
            ], 'fix-fks'));
        }

        if ($failed !== []) {
            return $this->asFailure(t('Couldn’t restore these foreign keys: {keys}. Check the logs for details.', [
                'keys' => implode('; ', $failed),
            ], 'fix-fks'));
        }

        if ($restored === []) {
            return $this->asSuccess(t('No foreign keys were missing.', category: 'fix-fks'));
        }

        return $this->asSuccess(t('Restored {count, plural, =1{# foreign key} other{# foreign keys}}.', [
            'count' => count($restored),
        ], 'fix-fks'));
    }
}
