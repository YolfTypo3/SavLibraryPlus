.. include:: ../../Includes.txt

.. _tutorial7_TemplateAndCssFiles:

======================
Template and CSS Files
======================

You have certainly remarked that the forms and the styles used in the
guest book were not the same as in the other SAV Library examples.

The reason is very simple. The extension `sav_library_example7 
<https://extensions.typo3.org/extension/sav_library_example7>`_ comes
with its own CSS file. By convention:

- If a file **extensionKey.css** is in the **Resources/Public/Css**
  directory of an extension, this file is loaded in the <head> section
  of the HTML. It is used to overload several default styles.

