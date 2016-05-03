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


Changing Date and dateTime default configurations
-------------------------------------------------

This new feature was introduced in version 0.0.3. By default the date
and dateTime format are respectively “%d/%m/%Y” and “%d/%m/%Y %H:%M”.
Each date and dateTime field can have a separate configuration using
the format attribute for that field in the SAV Library Kickstarter or
using the page TS Config as explained above.

Global changes can also be performed at the extension or library
levels in TypoScript. The priority for the default are extension level
if any, else library level if any, else the default format. For
example, the two following TypoScript instructions respectively modify
the default date format only for the extension sav\_library\_example0
and the default dateTime format for all extensions using SAV Library
Plus.

::

   plugin.tx_savlibraryexample0_pi1.format.date = %d.%m.%Y
   plugin.tx_savlibraryplus.format.dateTime = %d.%m.%Y %H:%M

You may also want to apply the changes only for one form in one
specific extension. The syntax becomes:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.format.date = yourFormat

