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


Changing the field configuration
--------------------------------


At the extension level
^^^^^^^^^^^^^^^^^^^^^^

The configuration of any field can be changed at the extension level
by TypoScript. The syntax is the following:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.viewType.fields[.tableName].fieldName.fieldProperty = propertyValue

For example, assume that one wants to change the width and the height
of the image in the “list” view in the example 1.

- The “tx\_yourExtensionNameWithoutUnderscores\_pi1” is
  “tx\_savlibraryexample1\_pi1”,

- the “formName” is “Contact”,

- the “viewType” is “listView” (use “singleView”, “editView” for the
  other types),

- the “tableName” is the table in which the field “fieldName” is. It can
  be omitted if the field is in the main table.

- the “fieldName” is “image”,

- the “fieldProperty” is “width” or “height”.

It leads to the following configuration:

::

   plugin.tx_savlibraryexample1_pi1.Contact.listView.fields.image.width = 200
   plugin.tx_savlibraryexample1_pi1.Contact.listView.fields.image.height = 200

Note: do not forget to add “\_pi1” to the extension name.


At the page level
^^^^^^^^^^^^^^^^^

The configuration of any field can be changed at the extension level
by means of the page TS Config. The syntax is the following:

::

   tx_yourExtensionNameWithoutUnderscores.formName.viewType.fields[.tableName].fieldName.fieldProperty = propertyValue

Using the same example as above, it leads to:

::

   tx_savlibraryexample1.Contact.listView.fields.image.width = 200
   tx_savlibraryexample1.Contact.listView.fields.image.height = 200

Note: do not add “\_pi1” to the extension name.

