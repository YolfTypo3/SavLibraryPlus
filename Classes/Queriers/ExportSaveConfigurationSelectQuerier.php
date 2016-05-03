<?php
namespace SAV\SavLibraryPlus\Queriers;

/**
 * Copyright notice
 *
 * (c) 2011 Laurent Foulloy (yolf.typo3@orange.fr)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Default Export Save Configuration Select Querier.
 *
 * @package SavLibraryPlus
 * @version $ID:$
 */
class ExportSaveConfigurationSelectQuerier extends ExportSelectQuerier
{

    /**
     * Executes the query
     *
     * @return none
     */
    protected function executeQuery()
    {

        // Gets the uri manager
        $uriManager = $this->getController()->getUriManager();

        // Gets the configuration uid
        $configurationIdentifier = intval($uriManager->getPostVariablesItem('configuration'));

        // Gets the post variables
        $postVariables = $uriManager->getPostVariables();

        // Updates or inserts the configuration
        if ($configurationIdentifier > 0) {
            // Updates the configuration
            $fieldsToUpdate = array();

            // Checks if the name of the configuration has to be changed
            if (empty($postVariables['configurationName']) === FALSE) {
                $fieldsToUpdate = array_merge($fieldsToUpdate, array(
                    'name' => $postVariables['configurationName']
                ));
                unset($postVariables['configurationName']);
            }
            $fieldsToUpdate = array_merge($fieldsToUpdate, array(
                'configuration' => serialize($postVariables)
            ));
            $this->updateFields(self::$exportTableName, $fieldsToUpdate, $configurationIdentifier);
        } else {
            // Inserts a new configuration
            $fieldsToInsert = array(
                'pid' => $GLOBALS['TSFE']->id,
                'cid' => $this->getController()
                    ->getExtensionConfigurationManager()
                    ->getExtensionContentObject()->data['uid'],
                'fe_group' => $postVariables['configurationGroup']
            );

            // Checks if the name of the configuration is set otherwise provides a default name
            if (empty($postVariables['configurationName']) === FALSE) {
                $name = $postVariables['configurationName'];
                unset($postVariables['configurationName']);
            } else {
                $name = \SAV\SavLibraryPlus\Controller\FlashMessages::translate('general.new');
            }
            $fieldsToInsert = array_merge($fieldsToInsert, array(
                'name' => $name
            ));

            // Inserts the new record
            $newUid = $this->insertFields(self::$exportTableName, $fieldsToInsert);

            // Sets the configuration uid
            $postVariables['configuration'] = $newUid;

            // Updates the new record
            $fieldsToUpdate = array(
                'configuration' => serialize($postVariables)
            );

            $this->updateFields(self::$exportTableName, $fieldsToUpdate, $newUid);
        }

        $this->exportConfiguration = $postVariables;

        return;
    }
}
?>
