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


Changing the default CSS
------------------------

The extension “sav\_library\_plus” comes with a default CSS which is
in the “Resources/Public/Styles” directory of the SAV Library Plus.

You can use your own CSS file by using the following TypoScript
configuration:

::

   plugin.tx_savlibraryplus.stylesheet = yourStyleSheet


You also may want to modify the default styles only for one specific
extension. In that case, you just have to put a CSS file in the
“Resources/Public/Styles” directory of your extension under the name
under the name “yourExtensionName.css”, where “yourExtensionName” is
the key of the extension you have created with the generator. This CSS
will be automatically added in the HTML <head> section (see
“sav\_library\_example7” for such a case). You can also change it
using the following TypoScript configuration:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.stylesheet = yourStyleSheet

