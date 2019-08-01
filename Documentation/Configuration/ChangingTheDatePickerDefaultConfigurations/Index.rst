.. include:: ../../Includes.txt

.. _changingTheDatePickerDefaultConfiguration:

===============================================
Changing the Date Picker Default Configurations
===============================================


Style Sheet
===========

There are several style sheets provided with the date picker. The
default CSS is **calendar-win2k-2.css**. You can change the default CSS
at the extension level or library level by using the following
TypoScript configuration:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1. datePicker.stylesheet = yourFormat
   plugin.tx_savlibraryplus.datePicker.stylesheet = yourStyleSheet

For example:

::

   plugin.tx_savlibraryplus.datePicker.stylesheet = EXT:sav_library_plus/Classes/DatePicker/css/calendar-tas.css


Tooltip and Tile Bar Formats
=============================

The date format of the tooltip (footer bar of the date picker) or the
title bar can be changed at the extension or library level by the
following TypoScript configuration:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.datePicker.format.toolTipDate = yourFormat
   plugin.tx_savlibraryplus.datePicker.format.toolTipDate =  yourFormat

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.datePicker.format.titleBarDate = yourFormat
   plugin.tx_savlibraryplus.datePicker.format.titleBarDate =  yourFormat

Default values for the title bar is **%B, %Y** and depends on the
language for the tool tip (see the variable
**Calendar._TT[“TT_DATE_FORMAT”]** in files in the directory
**Classes/DatePicker/lang**).

