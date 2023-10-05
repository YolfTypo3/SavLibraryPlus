.. include:: ../../Includes.txt

.. _routingSpeakingUrlsWithSavLibraryPlus:

===============================================
Routing - "Speaking URLs" With SAV Library Plus
===============================================

All extensions built with the `SAV Library Kickstarter
<https://extensions.typo3.org/extension/sav_library_kickstarter>`_ to work 
with the `SAV Library Plus
<https://extensions.typo3.org/extension/sav_library_plus>`_ extension use the parameter 
``sav_library_plus`` in the URLs.

The SAV Library Plus extension is provided with a specific mapper
to generate human readable links to ``Single`` views for items in ``List`` views.
The provided mapper ``SavLibraryPlusPattern`` can be used with the Simple Enhancer.

.. hint::

    See the `Routing - "Speaking URLs" in TYPO3 
    <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Routing/Index.html>`_
    section of the Main TYPO3 Core documentation for details.

The following configuration illustrates the configuration for the
`sav_library_example0
<https://extensions.typo3.org/extension/sav_library_example0>`_ extension. 
The options ``limitToPages`` and ``formName`` must be adapted to your page uid and content uid. 

::

	routeEnhancers:	
	  SavLibraryExample0:
	    type: Simple
	    limitToPages: [98]
	    routePath: '/{sav_library_plus}'
	    _arguments:
	      sav_library_plus: 'sav_library_plus'
	    aspects:
	      sav_library_plus:
	        type: SavLibraryPlusPatternMapper
	        formName: 'sav_library_example0_test_133'
	        tableName: 'tx_savlibraryexample0_table1'
	        routeFieldPattern: '^(?P<uid>\d+)-(?P<field1>.+)$'
	        routeFieldResult: '{uid}-{field1}'
 
The configuration of the mapper ``SavLibraryPlusPatternMapper``
is quite similar to the mapper ``PersistedPatternMapper``. The 
option ``formName`` is required. The syntax for this option
is the following :

::
	
	extensionName_formName_contentObjectUid
	

In the given confguration example, the three parts are :

- ``extensionName`` = ``sav_library_example0``
- ``formName`` = ``test`` (the name used for the form in the 
  SAV Library Kickstarter, in lower case)
- ``contentObjectUid`` = ``133`` (the uid of the plugin content object)

.. note::

    To adapt the configuration to your needs:

    - Set the ``limitToPages`` option to your page uid.
    - Change the ``formName`` option according to the syntax 
      explained above.
    - Adapt the ``routeFieldPattern`` and the ``routeFieldResult`` 
      options. 

    .. warning::

        - ``uid`` is mandatory in the ``routeFieldPattern`` and
          the ``routeFieldResult`` options.

