.. include:: ../../Includes.txt

.. _tutorial10_singleView:

===========
Single View
===========

In this view, we have to deal with two problems:

- Positioning correctly the fields,

- Executing a plugin from the `maps2 <https://extensions.typo3.org/extension/maps2>`_ extenstion.


Positioning the Fields
======================

The positioning of the fields is very simple in the ``List`` view
because you can define the template. For the ``Single`` and ``Edit``
views, the positioning can be obtained using the 
:ref:`wrapItem  <savlibrarykickstarter:general.wrapItem>` property.
This property has the same syntax and the same behaviour as the ``wrap``
property in TypoScript.

To perform the requested positioning, we will use <div> tags organized
as follows:

.. figure:: ../../Images/Tutorial10SingleViewPositionning.png

The wrapping is done field by field. For example, the first field is
``image``. It defines the beginning of the container <div> and the image
<div> when the following property is used:

::

   wrapItem = <div class="container"><div class="image"> | </div>;

- Analyze the :ref:`wrapItem  <savlibrarykickstarter:general.wrapItem>`  
  for all the fields, then open the file
  ``sav_library_example10.css`` in the
  ``Resources/Public/Css`` directory to analyze the configuration. As
  it can be seen, the labels associated with the field are not displayed
  thanks to the {display:none;} CSS configuration. Let us note that the
  same result could have been obtained using the 
  :ref:`cutLabel  <savlibrarykickstarter:general.cutLabel>` property in
  the Kickstarter (see for example the ``image`` and ``map`` fields).


Executing the Plugin
====================

Executing a plugin in the extension can simply be done by means of the
:ref:`tsObject <savlibrarykickstarter:general.tsObject>` and
:ref:`tsProperties  <savlibrarykickstarter:general.tsProperties>` properties. 
The :ref:`tsObject <savlibrarykickstarter:general.tsObject>` is a content
object in TypoScript, that is TEXT, FILE, CONTENT, ... Below is the configuration
of the field ``map``.

::

   tsObject = USER;

The ``USER`` is used to execute the action ``show`` of the plugin ``Maps2``.
The extension ``settings`` are iported and modified. Let us note the use of
the markers ``###pid###`` and ``###poi_uid###`` which provide the storage pid
and the uid of the point of interest for the map.

::

   tsProperties =
      userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
      userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
      vendorName = JWeiland
      extensionName = Maps2
      pluginName = Maps2
      controller = PoiCollection

      persistence {
         storagePid = ###pid###
      }

      settings {
         zoom = 18
         poiCollection = ###poi_uid###
         category =
         mapWidth = 100%
         mapHeight = 300
      } 
   ;

::

      showIf = 0 < ###poi_uid###;

The :ref:`showIf <savlibrarykickstarter:general.showIf>` property checks 
if the marker ``###poi_uid###`` is positive. If true the map is displayed,
otherwise it is cut as shown below.

.. figure:: ../../Images/Tutorial10SingleViewWithoutAddress.png

Let us now explain how the marker ``###poi_uid###`` is set. Let us 
have a look to the configuration of the field ``poi_uid`` whose type
is ``Show Only``.

The first configuration is to always cut the field, i.e. it will not 
be displayed because it is is just a working field.

::

   cutIf = true;

The second configuration gets the ``uid`` by means of a ``CONTENT`` object.
The marker ``###uidMainTable###`` is always available. It is replaced by the
uid of the current field in the main table.  

::   

   tsObject = CONTENT;
   tsProperties =
      table = tx_maps2_domain_model_poicollection
      select {
         join = tx_savlibraryexample10_poi_mm ON tx_maps2_domain_model_poicollection.uid = tx_savlibraryexample10_poi_mm.uid_foreign
         selectFields = tx_maps2_domain_model_poicollection.uid
         where = uid_local = ###uidMainTable###
      }
   renderObj = TEXT
   renderObj.field = uid
   ; 

The third configuration sets the field value in the marker ``poi_uid``, i.e.
the marker ``###poi_uid###`` is now available. 

::

   renderFieldInMarker = poi_uid;
