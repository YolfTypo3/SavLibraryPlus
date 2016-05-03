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


Flexform associated with the Plugin
-----------------------------------

The configuration of  **each generated extension** is done by means of
a flexform. The flexform has four folders:

- General

- Input controls

- Advanced

- Help pages

General folder
^^^^^^^^^^^^^^

.. figure:: ../../Images/ConfigurationFlexformGeneralFolder.png

#. **Help** : click on the word “Help” to get the context sensitive help.

#. **Select form** : use this selector to select the form name. Let use
   recall that the “sav\_library\_plus” makes it possible to build
   several forms associated with the same extension, thus providing
   different views of your tables.

#. **Show all if no filter** : if set, all items are displayed if no
   filter is applied, for example by means of the “sav\_filter\_abc”
   extension.

#. **If no information available** : use the selector to choose what to
   display when no information are available.

#. **Max number of items** : maximum number of items that will be
   displayed in a page. If set to 0, all items are displayed.
   
Input controls folder
^^^^^^^^^^^^^^^^^^^^^   

.. figure:: ../../Images/ConfigurationFlexformControlInputsFolder.png

#. **Help** : click on the word “Help” to get the context sensitive help.

#. **Input on form** : if set, Front End inputs are allowed (set by
   default).

#. **Allowed groups** : if you select user groups, user must belong to
   one of these groups to be allowed to input data in the Front End.

#. **Input Admin field** : put here a field under the form
   “tableName.fieldName” (if you use only “fieldName”, the main table is
   taken as “tableName”). This will restrict the input to users that have
   “Admin” right for this field in their TS Config. For example, if one
   user has “extKey\_Admin=value1,value2”  **in his TS Config** , he will
   be allowed to edit or delete items for which “fieldName” is equal to
   “value1” or “value2” for the extension “extKey”. The fields or the
   folders which have the attribute “editAdminPlus= 1;” can be modified
   if the user has the “Admin+” rights. For example, if the TS Config is
   “extKey\_Admin=value1+,value2” , the user is an “Admin+” for the
   records where “fieldName” is equal to “value1” and just “Admin” for
   the records where “fieldName” is equal to “value2”. Users become
   “Super Admin” if their TS Config is “extKey\_Admin=\*”.

#. **No “new” button** : no new button is added to the form. It means
   that you can modify existing records but you cannot create new record.

#. **No “edit” button** : an edit button will not be added in front of
   the records in “show All” views.

#. **No “delete” button** : a delete button will not be added in front of
   the records in “show All” views.

#. **Add a “delete” button only for records created by the user** : add a
   "delete" button only for records created by the user.

#. **Input start date** : if set, inputs in the Front End will not be
   possible before this date.

#. **Input end date** : if set, inputs in the Front End will not be
   possible after this date.

#. **Apply date limit** : use the selector to set either “Nobody”, “All”,
   “Admin plus users”, “All excluding Super Admin”. The date limit is
   applied according to this selector.
   
   
Advanced flder
^^^^^^^^^^^^^^

.. figure:: ../../Images/ConfigurationFlexformAdvancedFolder.png

#. **Help** : click on the word “Help” to get the context sensitive help.

#. **Permanent filter** : you can use this field to add a where clause
   that will be “anded” to the where clause of the form query.

#. **Add a fragment (# content id) to links** : the content id is added
   as a fragment to the links.

#. **Allow the use of the “query” property:** the “query” property makes
   it possible to execute queries in “input” or “update” forms. Because
   any query may be executed, for security reason, only admin users can
   check this field when this property is needed.

#. **Allow the use of the exec function in export:** The use of the php
   exec function is allowed in export which makes the execution of text
   processors possible, for example.

#. **Storage page** : this option makes it possible to store your record
   in a storage page, for example a “sysfolder”, otherwise records are
   stored in the current page. When this option is used, records are
   fetched in the storage page and in the pages defined in the “starting
   point” if any.


Help pages folder
^^^^^^^^^^^^^^^^^

.. figure:: ../../Images/ConfigurationFlexformHelpPagesFolder.png

#. **Help** : click on the word “Help” to get the context sensitive help.

#. **Help page for the list view** : use this selector to choose a page
   of your site which will be use as a help page for the list view. In
   this case, an icon is displayed in the title bar of your extension.

#. **Help page for the single view** : use this selector to choose a page
   of your site which will be use as a help page for the single view. In
   this case, an icon is displayed in the title bar of your extension.

#. **Help page for the edit view** : use this selector to choose a page
   of your site which will be use as a help page for the edit view. In
   this case, an icon is displayed in the title bar of your extension.

