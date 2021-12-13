<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace YolfTypo3\SavLibraryPlus\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\PersistedPatternMapper;
use YolfTypo3\SavLibraryPlus\Controller\AbstractController;

/**
 * This mapper extends PersistedPatternMapper for sav_library_plus extensions
 *
 * Example:
 *   routeEnhancers:
 *     SavLibraryExample0:
 *       type: Simple
 *       routePath: '/{sav_library_plus}'
 *       limitToPages: [98]
 *       _arguments:
 *         sav_library_plus: 'sav_library_plus'
 *       aspects:
 *         sav_library_plus:
 *           type: SavLibraryPlusPatternMapper
 *           formName: 'sav_library_example0_test_133'
 *           tableName: 'tx_savlibraryexample0_table1'
 *           routeFieldPattern: '^(?P<uid>\d+)-(?P<field1>.+)$'
 *           routeFieldResult: '{uid}-{field1}'
 *
 * @internal might change its options in the future, be aware that there might be modifications.
 */
class SavLibraryPlusPatternMapper extends PersistedPatternMapper
{
    /**
     * @var string
     */
    protected $formName;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        // Calls the parent constructor
        parent::__construct($settings);

        // Adds the form name
        $formName = $settings['formName'] ?? null;
        if (!is_string($formName)) {
            throw new \InvalidArgumentException('formName must be string', 1537277173);
        }

        $this->formName = $formName;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        // Decodes the sav_library_plus parameter
        $parameters = AbstractController::uncompressParameters($value, $this->formName);
        if (!is_array($parameters) || count($parameters)!=3 || $parameters['formAction']!='single') {
            return $value;
        }
        // Gets and processes the uid
        $value = $parameters['uid'];
        $result = $this->findByIdentifier($value);
        $result = $this->resolveOverlay($result);
        return $this->createRouteResult($result);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        $matches = [];
        if (!preg_match('#^(?:' . $this->routeFieldPattern . '|(?P<sav_library_plus>[a-z0-9]+))$#', $value, $matches)) {
            return null;
        }
        $values = $this->filterNamesKeys($matches);

        // Checks if the parameter is sav_library_plus
        if (isset($values['sav_library_plus'])) {
            return $value;
        }

        // Regenerates the sav_library_plus parameter
        $parameters = [
            'uid' => $values['uid'],
            'formAction' => 'single',
            'formName' => $this->formName
        ];
        $value = AbstractController::compressParameters($parameters);
        return $value;
    }

}
