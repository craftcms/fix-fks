<?php

declare(strict_types=1);

namespace CraftCms\FixFks;

use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\FixFks\Database\RestoringBlueprint;
use CraftCms\FixFks\Events\ForeignKeysRestoring;
use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds any of Craft’s foreign keys that are missing from the database, then lets plugins add theirs.
 */
class ForeignKeyRestorer
{
    /** @var list<string> */
    private array $restored = [];

    /** @var list<string> */
    private array $failed = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Runs Craft’s install migration’s `addForeignKeys()` and dispatches {@see ForeignKeysRestoring},
     * with every `Schema::table()` blueprint replaced by a {@see RestoringBlueprint}.
     *
     * @return array{restored: list<string>, failed: list<string>} The keys that were added, and those that couldn’t be
     */
    public function restore(): array
    {
        $this->restored = [];
        $this->failed = [];

        // Schema builders resolve their blueprints from the container
        $previousBinding = $this->container->getBindings()[Blueprint::class] ?? null;
        $this->container->bind(Blueprint::class, fn (Container $container, array $parameters) => new RestoringBlueprint(
            $parameters['connection'],
            $parameters['table'],
            $parameters['callback'] ?? null,
            $this,
        ));

        Schema::disableForeignKeyConstraints();

        try {
            new Install()->addForeignKeys();
            event(new ForeignKeysRestoring);
        } finally {
            Schema::enableForeignKeyConstraints();

            if ($previousBinding !== null) {
                $this->container->bind(Blueprint::class, $previousBinding['concrete'], $previousBinding['shared']);
            } else {
                $this->container->offsetUnset(Blueprint::class);
            }
        }

        return [
            'restored' => $this->restored,
            'failed' => $this->failed,
        ];
    }

    /** @internal */
    public function recordRestored(string $foreignKey): void
    {
        $this->restored[] = $foreignKey;
    }

    /** @internal */
    public function recordFailed(string $foreignKey): void
    {
        $this->failed[] = $foreignKey;
    }
}
