.. include:: ../../Includes.txt

.. _routingSpeakingUrlsWithSavLibraryPlus:

===============================================
Routing - "Speaking URLs" With SAV Library Plus
===============================================

All extensions built with the `SAV Library Kickstarter
<https://extensions.typo3.org/extension/sav_library_kickstarter>`_ for the `SAV Library Plus
<https://extensions.typo3.org/extension/sav_library_plus>`_ extension use the parameter 
``sav_library_plus`` in the URLs.

The SAV Library Plus extension is provided with a specific enhancer
to generate human readable links. 

.. hint::

    See the `Routing - "Speaking URLs" in TYPO3 
    <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Routing/Index.html>`_
    section of the Main TYPO3 Core documentation for details.

The SAV Library Kickstarter generated the file
``Configuration/Routes/Default.yaml`` which can be imported into your site route configuration.
The following configuration illustrates the configuration for the
`sav_library_example0
<https://extensions.typo3.org/extension/sav_library_example0>`_ extension. 

::

	imports:
	  - { resource: 'EXT:sav_library_example0/Configuration/Routes/Default.yaml' }
	    
