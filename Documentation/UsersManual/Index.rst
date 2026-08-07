.. include:: ../Includes.txt

.. _usersManual:

============
Users Manual
============

#. If not installed, download the extension `SAV Library Kickstarter 
   <https://extensions.typo3.org/extension/sav_library_kickstarter>`_ and install it.

#. Download the extension `SAV Library Plus 
   <https://extensions.typo3.org/extension/sav_library_plus>`_ and install it.

#. Read the :ref:`SAV Library Kickstarter tutorial section <yolftypo3/sav-library-kickstarter:tutorial>`
   to create a new extension or download one of the SAV Library Examples 
   (``sav_library_exampleX``, where X is a number) available in the TER.
   
.. important::

	The SAV Library Kickstarter now generates CType plugins instead of list_type plugins.
	If you regenerate existing extensions, flush TYPO3 cache after regeneration.
	
	.. code::
		
		vendor/bin/typo3 cache:flush
		
	Then use the Upgrade Admin Tool and run Upgrade Wizard to migrate the plugins to CType.
	 