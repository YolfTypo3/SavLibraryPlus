.. include:: ../../Includes.txt

.. _tutorial8_extensionOverview:

==================
Extension Overview
==================

Edit the extension `sav_library_example8 
<https://extensions.typo3.org/extension/sav_library_example8>`_ 
in the SAV Library Kickstarter to get an overview. It contains:

- Two forms (USER, ADMIN),

- Five views (USER_List, USER_Edit, ADMIN_List, ADMIN_Single,
  ADMIN_Edit),

- Two queries (USER_Query, ADMIN_Query).

The organization of the forms is quite similar to the previous
examples. Just click on them to analyze it. Let us focus on the
configurations associated with the existing table ``fe_users`` by
clicking on the link ``fe_users``. As it can be seen, all fields have
type ``Only shown in SAV Form``.

.. figure:: ../../Images/Tutorial8KickstarterFeUsersTableImport.png 

When the extension was created, by clicking on the link  ``Import
fields from table as “Only shown in SAV form”`` , all fields from
the table ``fe_users`` were imported, then unwanted fields were
removed.

The User Form (USER)
====================

In this example, it was chosen to design a very simple form consisting
in the display of the user image field. The image is associated with a
link to open the user form in the edit mode.


The Query USER_Query
====================

The query is used to filter the ``fe_users`` table with the
authenticated user. This is easily done by using the marker ``###user###``
in the ``WHERE clause`` of the query.

.. figure:: ../../Images/Tutorial8KickstarterUserQuery.png 


The Views USER_List and USER_Edit
=================================

The template associated with these views is quite simple since the
only field to display is ``image``.

.. figure:: ../../Images/Tutorial8KickstarterUserListView.png 

And to make it possible to generate the link to open the input view,
only a few configuration attributes are required.

.. figure:: ../../Images/Tutorial8KickstarterFieldConfiguration.png 

- ``func = makeItemLink;`` generates the link for the current item.

- ``edit = 1;`` opens the edit view instead of the default view (``Single``
  view).

- ``width = 50;`` and ``height = 50;`` define the size of the image.

Because there is no ``Single`` view associated with the user form, the
default ``Edit`` view title bar must be changed, in particular the ``save
and show`` and the ``show button`` must be removed. The example comes a
directory ``Resources/Private/Partials`` which contains two directories
``TitleBars`` and ``Footers``. They respectively contain a folder
``EditView`` which contain themselves a new ``default.html`` Fluid file.
We will see later how to call these new ``Partials``.

Finally, to override the default css, the example comes with a css
file ``sav_library_example8.css`` in the ``Resources/Public/Css``
directory which contains the following instructions :

::

   .sav_library_example8_user .savLibraryPlus .listView {width:62px;background-color:#ffffff;}
   .sav_library_example8_user .savLibraryPlus .listView .titleBar {display:none;}
   .sav_library_example8_user .savLibraryPlus .listView .items .item {border:none;background-color:#ffffff;}

Concerning the view USER_edit, each field with the tyep ``Only shown in SAV Form`` (ShowOnly fields)
has the property :ref:`updateShowOnlyField <savlibrarykickstarter:showOnly.updateShowOnlyField>` set to 1. 
By default in ``ShowOnly`` fields are not created 
nor can be updated. Setting this property to 1 overrides the default behavior. 

The Administration Form (ADMIN)
===============================

The administration form is used in the frontend to manage, give
rights, export frontend users. It is based on a conventional query, ``List``,
``Single`` and ``Edit`` views for which no specific configuration is
needed. Just click on the different views and tabs to see how fields
are grouped.