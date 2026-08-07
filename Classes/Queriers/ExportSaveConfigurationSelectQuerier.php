<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace YolfTypo3\SavLibraryPlus\Queriers;

use YolfTypo3\SavLibraryPlus\Controller\FlashMessages;

/**
 * Default Export Save Configuration Select Querier.
 *
 * @package SavLibraryPlus
 */
class ExportSaveConfigurationSelectQuerier extends ExportSelectQuerier
{

    /**
     * Executes the query
     *
     * @return void
     */
    protected function executeQuery(): void
    {
        // Gets the uri manager
        $uriManager = $this->controller->getUriManager();

        // Gets the configuration uid
        $configurationIdentifier = intval($uriManager->getPostVariablesItem('configuration'));

        // Gets the post variables
        $postVariables = $uriManager->getPostVariables();

        // Updates or inserts the configuration
        if ($configurationIdentifier > 0) {
            // Updates the configuration
            $fieldsToUpdate = [];

            // Checks if the name of the configuration has to be changed
            if (empty($postVariables['configurationName']) === false) {
                $fieldsToUpdate = array_merge($fieldsToUpdate, [
                    'name' => $postVariables['configurationName']
                ]);
                unset($postVariables['configurationName']);
            }
            $fieldsToUpdate = array_merge($fieldsToUpdate, [
                'configuration' => serialize($postVariables)
            ]);
            $this->updateFields(self::$exportTableName, $fieldsToUpdate, $configurationIdentifier);
        } else {
            // Inserts a new configuration
            $fieldsToInsert = [
                'pid' => $this->controller->getPageId(),
                'cid' => $this->controller->getContentObjectRendererDataAttribute('uid'),
                'fe_group' => $postVariables['configurationGroup']
            ];

            // Checks if the name of the configuration is set otherwise provides a default name
            if (empty($postVariables['configurationName']) === false) {
                $name = $postVariables['configurationName'];
                unset($postVariables['configurationName']);
            } else {
                $name = FlashMessages::translate('general.new');
            }
            $fieldsToInsert = array_merge($fieldsToInsert, [
                'name' => $name
            ]);

            // Inserts the new record
            $this->insertFields(self::$exportTableName, $fieldsToInsert);

            // Sets the configuration uid
            $postVariables['configuration'] = $this->insertedUid;

            // Updates the new record
            $fieldsToUpdate = [
                'configuration' => serialize($postVariables)
            ];

            $this->updateFields(self::$exportTableName, $fieldsToUpdate, $this->insertedUid);
        }

        $this->exportConfiguration = $postVariables;

        return;
    }
}
