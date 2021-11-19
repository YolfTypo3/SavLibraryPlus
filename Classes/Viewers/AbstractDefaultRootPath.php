<?php

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

namespace YolfTypo3\SavLibraryPlus\Viewers;

/**
 * Abstract class Viewer.
 *
 * @package SavLibraryPlus
 */
abstract class AbstractDefaultRootPath
{

    // Default item viewvier directory
    const DEFAULT_ITEM_VIEWERS_DIRECTORY = 'General';

    /**
     * The default partial root path
     *
     * @var string
     */
    protected $defaultPartialRootPath = 'EXT:sav_library_plus/Resources/Private/Partials';

    /**
     * The default layout root path
     *
     * @var string
     */
    protected $defaultLayoutRootPath = 'EXT:sav_library_plus/Resources/Private/Layouts';

    /**
     * The default template root path
     *
     * @var string
     */
    protected $defaultTemplateRootPath = 'EXT:sav_library_plus/Resources/Private/Templates/Default';
}
