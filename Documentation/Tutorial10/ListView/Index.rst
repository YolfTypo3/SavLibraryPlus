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


List view
---------

The template of this view is very simple since the only field to
display is the image. However, we want to take advantage of the TYPO3
image processing instead of using the conventional modification of the
size of the image through the properties “width” and “height”.

When the field is an image and you use the property “tsProperties”, an
IMAGE cObject is generated with the provided TS properties. You can
use markers to refer to the fields. In the example, the configuration
is the following:

::

   func = makeItemLink;

::

   tsProperties =
   file = uploads/tx_savlibraryexample10/###image###
   file.width= 100
   ;

The first property is already known. It makes it possible to open the
“single” view by clicking on the image.

The second one is just TypoScript syntax for an IMAGE cObject. It
defines the file using the ###image### marker, that is the file name
and it sets the width to 100px. You may insert any other TS property
as, for example, GIFBUILDER.

The syntax is exactly the same as in TypoScript.  **Do not forget to
end the property by a semicolon. If you need a semicolon in a
TypoScript property, please use “\;”.**

The last problem to solve in the “list” view is to have several images
on the same line instead of having them one per line. It can be simply
done by changing the default style (See the file
“sav\_library\_example10.css” inthe “Resources/Private/Styles”
directory) as follows:

::

   .sav_library_example10_gallery .savLibraryPlus .listView .items .item {float:left;width:125px;height:105px;background-color:#ffffff;}

