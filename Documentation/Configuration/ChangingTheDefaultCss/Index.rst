.. include:: ../../Includes.txt

.. _changingTheDefaultCss:

========================
Changing the Default CSS
========================

The extension `sav_library_plus <https://extensions.typo3.org/extension/sav_library_plus>`_
comes with a default CSS which is
in the **Resources/Public/Css** directory of the SAV Library Plus.

You can use your own CSS file by using the following TypoScript
configuration:

::

   plugin.tx_savlibraryplus.stylesheet = yourStyleSheet


You also may want to modify the default styles only for one specific
extension. In that case, you just have to put a CSS file in the
**Resources/Public/Css** directory of your extension under the name
under the name **yourExtensionName.css**, where **yourExtensionName** is
the key of the extension you have created with the generator. This CSS
will be automatically added in the HTML <head> section (see
:ref:`Tutorial 7 <tutorial7>` for such a case). You can also change it
using the following TypoScript configuration:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.stylesheet = yourStyleSheet

