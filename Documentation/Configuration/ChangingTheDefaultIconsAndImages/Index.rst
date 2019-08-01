.. include:: ../../Includes.txt

.. _changingTheIconsAndImages:

=====================================
Changing the Default Icons and Images
=====================================

The extension `sav_library_plus
<https://extensions.typo3.org/extension/sav_library_plus>`_ 
comes with default icons and images
which are respectively in the directory **Resources/Public/Icons** and
**Resources/Public/Images**. There are several ways of changing icons
and images. When changing an icon, you may also change the file
extension. Allowed extensions are **.gif**, **.png**, **.jpg** or **.jpeg**.
For example, you may replace the icon file **calendar.gif** by
**calendar.png**. If icons with the same name but different extensions
are in the icon directory, the priority is **.gif** first, then **.png**
then **.jpg** and finally **.jpeg**.


At the Library Level
====================

Changes are made in TypoScript and will be applied to all extensions
using the SAV Library Plus. The syntax is the following:

::

   plugin.tx_savlibraryplus.iconRootPath = yourIconRootPath
   plugin.tx_savlibraryplus.imageRootPath = yourImageRootPath


At the Extension Level
======================

There are two different ways of changing the icon or images root
paths.

#. Create a **Resources/Public/Icons** (resp. **Resources/Public/Images**)
   directory in the generated extension in which you put your icon files
   with the same names as in the default directory
   **Resources/Public/Icons** (resp. **Resources/Public/Images**) in the
   extension `sav_library_plus
   <https://extensions.typo3.org/extension/sav_library_plus>`_.

#. Create a directory where you want in the fileadmin directory, in which
   you put your icon files with the same names as in the default
   directory. Then, in the **Setup** of your template, write:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.iconRootPath = yourIconRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.iconImagePath = yourImageRootPath


where **yourExtensionNameWithoutUnderscores** is the key of the
extension you have created with the SAV Library Kickstarter, but
without underscores if any, and **yourIconRootPath** (resp.
**yourImageRootPath**)is the relative path of the directory where you
have put your icons (resp. images).

You may also want to apply the changes only for one form in one
specific extension. The syntax becomes:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.iconRootPath = yourIconRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.iconImagePath = yourImageRootPath

