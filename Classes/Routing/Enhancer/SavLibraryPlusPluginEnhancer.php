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
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace YolfTypo3\SavLibraryPlus\Routing\Enhancer;

use TYPO3\CMS\Core\Routing\Enhancer\PluginEnhancer;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteCollection;

/**
 * Used for SAV Library Plus plugins.
 * *
 * routeEnhancers:
 *  SavLibraryExample0:
 *    type: SavLibraryPlusPlugin
 *    namespace: 'tx_savlibraryexample0_pi1'
 *    routePath: '/savLibraryExample0/{controller}/{action}/{sav_library_plus}'
 *    requirements:
 *      controller: 'Default'
 *      action: '[a-zA-Z]+'
 *      sav_library_plus: '[0-9a-f]+'
 */
class SavLibraryPlusPluginEnhancer extends PluginEnhancer 
{

    /**
     * {@inheritdoc}
     */
    public function enhanceForGeneration(RouteCollection $collection, array $parameters): void
    {
        if (array_key_exists('sav_library_plus', $parameters)) {
            $parameters[$this->namespace]['sav_library_plus'] = $parameters['sav_library_plus'];
            unset($parameters['sav_library_plus']);
        }
        // No parameter for this namespace given, so this route does not fit the requirements
        if (!is_array($parameters[$this->namespace] ?? null)) {
            return;
        }
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        $variant = $this->getVariant($defaultPageRoute, $this->configuration);
        $compiledRoute = $variant->compile();
        // contains all given parameters, even if not used as variables in route
        $deflatedParameters = $this->deflateParameters($variant, $parameters);
        $variables = array_flip($compiledRoute->getPathVariables());
        $mergedParams = array_replace($variant->getDefaults(), $deflatedParameters);
        // all params must be given, otherwise we exclude this variant
        if ($variables === [] || array_diff_key($variables, $mergedParams) !== []) {
            return;
        }
        $variant->addOptions(['deflatedParameters' => $deflatedParameters]);
        $collection->add('enhancer_' . $this->namespace . spl_object_hash($variant), $variant);
    }

}
