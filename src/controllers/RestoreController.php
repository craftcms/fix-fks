<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\fixfks\controllers;

use Craft;
use craft\fields\Matrix;
use craft\fixfks\Command;
use craft\migrations\CreateMatrixContentTable;
use craft\migrations\Install;
use craft\web\Controller;

/**
 * Fix FKs controller class
 */
class RestoreController extends Controller
{
    /**
     * @event \yii\base\Event The event that is triggered after all Craft FKs have been restored
     */
    const EVENT_AFTER_RESTORE_FKS = 'afterRestoreFks';

    /**
     * Restore the foreign keys
     */
    public function actionIndex()
    {
        // Swap out the Command class with our own
        $db = Craft::$app->getDb();
        $commandMap = $db->commandMap;
        $db->commandMap = [
            $db->getDriverName() => Command::class,
        ];

        // Start an output buffer
        ob_start();

        // Disable FK checks
        $queryBuilder = $db->getSchema()->getQueryBuilder();
        $db->createCommand($queryBuilder->checkIntegrity(false))->execute();

        // Add default FKs
        (new Install())->addForeignKeys();

        // Add Matrix FKs
        $fields = Craft::$app->getFields()->getAllFields();
        foreach ($fields as $field) {
            if ($field instanceof Matrix) {
                $migration = new CreateMatrixContentTable([
                    'tableName' => $field->contentTable
                ]);
                $migration->addForeignKeys();
            }
        }

        // Give plugins the opportunity to restore their own FKs
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE_FKS)) {
            $this->trigger(self::EVENT_AFTER_RESTORE_FKS);
        }

        // Re-enable FK checks
        $db->createCommand($queryBuilder->checkIntegrity(true))->execute();

        // End the output buffer
        ob_end_clean();

        // Restore the original Command class
        $db->commandMap = $commandMap;

        Craft::$app->getSession()->setNotice(Craft::t('fix-fks', 'Foreign keys restored.'));
    }
}
