.. include:: ../../Includes.txt

.. _changingTheFieldConfiguration:

================================
Changing the Field Configuration
================================


At the Extension Level
======================

The configuration of any field can be changed at the extension level
by TypoScript. The syntax is the following:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.viewType.fields[.tableName].fieldName.fieldProperty = propertyValue

For example, assume that one wants to change the width and the height
of the image in the **List** view in the :ref:`Tutorial1 <tutorial1>`.

- The **tx_yourExtensionNameWithoutUnderscores_pi1** is
  **tx_savlibraryexample1_pi1**,

- the **formName** is **Contact**,

- the **viewType** is **listView** (use **singleView**, **editView** for the
  other types),

- the **tableName** is the table in which the field **fieldName** is. It can
  be omitted if the field is in the main table.

- the **fieldName** is **image**,

- the **fieldProperty** is **width** or **height**.

It leads to the following configuration:

::

   plugin.tx_savlibraryexample1_pi1.Contact.listView.fields.image.width = 200
   plugin.tx_savlibraryexample1_pi1.Contact.listView.fields.image.height = 200

.. important:: 

   Do not forget to add **_pi1** to the extension name.


At the Page Level
=================

The configuration of any field can be changed at the extension level
by means of the page TypoScript Config. The syntax is the following:

::

   tx_yourExtensionNameWithoutUnderscores.formName.viewType.fields[.tableName].fieldName.fieldProperty = propertyValue

Using the same example as above, it leads to:

::

   tx_savlibraryexample1.Contact.listView.fields.image.width = 200
   tx_savlibraryexample1.Contact.listView.fields.image.height = 200

.. important:: 

   Do not add **_pi1** to the extension name.

