.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Template and CSS files
----------------------

You have certainly remarked that the forms and the styles used in the
guest book were not the same as in the other SAV Library examples.

The reason is very simple. The extension “sav\_library\_example7” comes
with its own CSS file. By convention:

- If a file “extensionKey.css” is in the “Resources/Private/Styles”
  directory of an extension, this file is loaded in the <head> section
  of the HTML. It is used to overload several default styles.

