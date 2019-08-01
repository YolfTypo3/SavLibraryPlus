.. include:: ../../Includes.txt

.. _tutorial1_howTo:

=======
How to?
=======

How to Specify the Size of the Image?
=====================================

The image is displayed in the **List** and **Single** views. The following
configuration parameters define a 50x50 image, for example in the
**List** view:

::

   width = 50;
   height = 50;


How to Change the Number of Items Displayed by Page in the List View?
=======================================================================

By default, the **Max number of items** in the flexform is set to 10,
just change this number. Setting this number to 0 will display all
items. Using the default configuration, you will get:

.. figure:: ../../Images/Tutorial1ListViewWithAllItems.png 

Now, if you set **Max number of item** to 2, you will obtain:

.. figure:: ../../Images/Tutorial1ListViewWith2Items.png

How to Introduce an Alphabetic Selector?
========================================

It is often useful to select records based on, for example, the first
letter of the name. The extension `sav_filters
<https://extensions.typo3.org/extension/sav_filters>`_ was specially
built to deal with this problem.

- Download `sav_filters <https://extensions.typo3.org/extension/sav_filters>`_ 
  from the TER,  **read the documentation**  and install it.

- Add the plugin `sav_filters <https://extensions.typo3.org/extension/sav_filters>`_ 
  as a content element in the same page as the extension 
  `sav_library_example1 <https://extensions.typo3.org/extension/sav_library_example1>`_.

- Configure the flexform by filling the table name and the field name. A
  Context Sensitive Help is available.

.. figure:: ../../Images/Tutorial1SavFilterAlphabeticFlexform.png

- Save your configuration and go in the Front end.

.. figure:: ../../Images/Tutorial1ListViewWithAlphabeticFilter.png

Only the letters corresponding to the first letter of existing records
can be selected. Click on a letter to select the records.

.. figure:: ../../Images/Tutorial1ListViewWithAlphabeticFilterSelection.png
 

