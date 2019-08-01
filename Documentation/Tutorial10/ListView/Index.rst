.. include:: ../../Includes.txt

.. _tutorial10_listView:

=========
List View
=========

The template of this view is very simple since the only field to
display is the image. However, we want to take advantage of the TYPO3
image processing instead of using the conventional modification of the
size of the image through the properties 
:ref:`width <savlibrarykickstarter:filesAndImages.width>` and
:ref:`height <savlibrarykickstarter:filesAndImages.height>`.

When the field is an image and you use the property 
:ref:`tsProperties  <savlibrarykickstarter:general.tsProperties>`, an
IMAGE cObject is generated with the provided TypoScript properties. You can
use markers to refer to the fields. In the example, the configuration
is the following:

::

   func = makeItemLink;

::

   tsProperties =
     file.width= 100
   ;

The first property is already known. It makes it possible to open the
**Single** view by clicking on the image.

The second one is just TypoScript syntax for an IMAGE cObject. It
defines the file using the **###image###** marker. The width is set to 100px.
You may insert any other TypoScript property as, for example, GIFBUILDER.

.. important::

   The syntax is exactly the same as in TypoScript.  **Do not forget to
   end the property by a semicolon. If you need a semicolon in a
   TypoScript property, please use \\;.**

The last problem to solve in the **List** view is to have several images
on the same line instead of having them one per line. It can be simply
done by changing the default style (See the file
**sav_library_example10.css** in the **Resources/Public/Css**
directory) as follows:

::

   .sav_library_example10_gallery .savLibraryPlus .listView .items .item {float:left;width:125px;height:105px;background-color:#ffffff;}

