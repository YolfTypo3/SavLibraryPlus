.. include:: ../../Includes.txt

.. _tutorial10_editView:

=========
Edit View
=========

This view includes a subform which defines a relation n-n with the table
``tx_maps2_domain_model_poicollection`` of the extension 
`maps2 <https://extensions.typo3.org/extension/maps2>`_. 
This table is defined as an existing table. The fields are, by default, 
set to the type ``Show Only`` by the Kickstarter.

Because we want to enter points of interest from the frontend of the extension, 
the property :ref:`updateShowOnlyField <savlibrarykickstarter:showOnly.updateShowOnlyField>` 
is set to 1.

::

   updateShowOnlyField = 1;

