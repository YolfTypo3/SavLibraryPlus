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

.. _tutorial9:

Tutorial 9: Using XML JpGraph
=============================

The aim of this tutorial is to show how to include XML JpGraphs into
an extension (see the extension “sav\_jpgraph” for detailed
information and a tutorial).

In this example, we want to display list of events as Gantt graphs.
Each graph is a period of time as, for example months or quarters. Two
forms are requested:

- The first one (Admin) will be used to input events in FE. An event
  includes a title, a begin and a end date, a category. Categories will
  be input in BE. They have a name and a color which will be used in the
  Gantt graph.

- The second one (Display) is the Gantt graph display.

You can download this example from the TER (sav\_library\_example9).

Table of Contents
-----------------

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   FeInputEvents/Index
   GanttGraphsDisplay/Index
   UsingTheGraphWithAYearSelector/Index

