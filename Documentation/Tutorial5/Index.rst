.. include:: ../Includes.txt

.. _tutorial5:

=================
Tutorial 5: Hooks
=================

The configuration options that can be used in the SAV Library Kickstarter makes 
it possible to generate the rendering in most of the cases. However, when more 
complex renderings are required, hooks can be used either in the extension
itself or in another extension extension.

The extension `sav_library_example5 
<https://extensions.typo3.org/extension/sav_library_example5>`_  illustrates the use
of a hook.

The extension defines three fields: a field named ``title`` and two working fields 
respectively named ``field1`` and ``field2``. The aim of this example is to render
``field1`` and ``field2`` in the ``Single`` view, by means of a FLUID template,
such that their content are ordered in the ascending order.

.. note::

   Hooks are useful when the processing associated with the rendering is more complex
   than simply displaying fields. Indeed, since FLUIDTEMPLATE is a TypoScript content
   object, it can be used with the :ref:`tsObject <savlibrarykickstarter:general.tsObject>`
   and :ref:`tsProperties <savlibrarykickstarter:general.tsProperties>` attributes
   (see :ref:`Tutorial 10 <tutorial10>` for an example).

Table of Contents
=================

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   ExtensionOverview/Index

