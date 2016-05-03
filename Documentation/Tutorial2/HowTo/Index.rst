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


How to?
-------


How to display a title in the upper icon bar of the view?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

A title can be associated with each view. It is defined in the “Title
bar” section of the views. Text, language markers or field markers can
be used.

For example, if you use “$$$formTitle$$$” in the “Title bar” field,
this language marker will be replaced by its definition in the
locallang.xml file. In this example, “formTitle” is defined as “CD
Collection” for the default language and “Liste de CD” for the French
language. Therefore, the output becomes:

.. figure:: ../../Images/Tutorial2ListViewWithTitleBar.png

Now, if you use “###artist### - ###album\_title###” in the “Title bar”
section of the “Single” and “Edit” views, the markers “###artist###”
and “###album\_title###” will be replaced by the respective values of
the field “artist” and “album\_title” for the current record. If these
markers are used in the “Title bar” section of the “List” view, they
will be replaced by the label associated with the fields. Thus, the
output is the following for the “Single” view:

.. figure:: ../../Images/Tutorial2SingleViewWithTitleBar.png

How to display the labels associated with each field in the title bar?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

As explain above, field markers used in “List” view are replaced by
the label associated with the fields. Therefore, if the “Title bar”
section is the following:

::

   <ul>
    <li class="artist">###artist###</li>
    <li class="title">###album_title###</li>
    <li class="date">###date_of_purchase###</li>
     <li class="category">###category###</li>
     <li class="image">###coverimage###</li>
   </ul>

the title bar becomes:

.. figure:: ../../Images/Tutorial2ListViewWithFieldsInTitleBar.png

How to change the order of the item list by clicking on the label in the title bar?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

For example, assume that we want to change the displayed order by
clicking on “Artist” (or “Artiste” if you use the french language).

- Add the following configuration parameter in the “artist” field of the
  “All” view.

::

   OrderLinkInTitle = 1;

- Add the following configuration in the “WHERE Tags” section of your
  query. Click on the “plus” icon to add an entry.

.. figure:: ../../Images/Tutorial2KickstarterWhereTag.png 

In this configuration, use “filedname+” or “fieldname-” and associate
the order clause you want. In general “+” can be used for the
ascending order and “-” for the descending order.

By default, the displayed link will behave as a toogle between the
ascending and the descending sort. However you can control the display
using the property “orderLinkInTitleSetup” which introduces links
associated with icons. For example, using “orderLinkInTitleSetup =
:value:ascdesc;” or “orderLinkInTitleSetup = asc:value:desc;” will
respectively provide the following outputs:

.. figure:: ../../Images/Tutorial2OrderLink1.png  
.. figure:: ../../Images/Tutorial2OrderLink2.png 

Of course, you may keep the toggle link if wanted which gives
respectively “orderLinkInTitleSetup = :link:ascdesc;” or
“orderLinkInTitleSetup = asc:link:desc;”.

The icon color will change depending on the sort order as shown below.
In the following caption, the descending order is displayed.

.. figure:: ../../Images/Tutorial2ListViewWithOrderLinkInTitleBar.png  

How to reorganize the fields with folders?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Folders may be introduced in the “Single” and “Edit” views to provide
a better organization of the information. Click on the “plus” icon to
add a folder.

.. figure:: ../../Images/Tutorial2KickstarterSingleViewWithFolder.png  

In this example, two folders are defined. If the labels “General” and
“Comments” are defined in the locallang.xml file, they will be
replaced by their definition in the selected language, otherwise they
are used as it. Then, set a folder to each selected field of the “CD
Collection” table for the “Single” view as shown below, save and
generate the extension:

.. figure:: ../../Images/Tutorial2KickstarterFieldsOverviewWithFolders.png   

If you set all the fields to the folder “General” except the field
“description” which is set to “Comments”, the following views are
obtained in Front End where the folder “General” and “Comments” have a French 
translation in locallang.xml

.. figure:: ../../Images/Tutorial2SingleViewGeneralFolder.png   

.. figure:: ../../Images/Tutorial2SingleViewCommentsFolder.png   


