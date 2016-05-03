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

.. _tutorial7:

Tutorial 7: Guest book
======================

The aim of this extension is to deal with multiple forms of the same
table and to explain how update views can be used. It creates a guest
book which is inspired from the extension “ve\_guestbook” (Modern
guest book) available in the TER. Download this example from the TER
(sav\_library\_example7). This extension uses a table with the
following fields:

- the guest firstname,
- the guest lastname,
- the guest email,
- the guest website,
- the guest message,
- a comment field.

We want to have three forms associated with this table:

- one form for the guest input (FORM),
- one form for the list of guest inputs (LIST),
- one form for a teaser of the most recent entries (TEASER).

For the guest input, we want to avoid spams and to control the
content. Therefore, we want the following behavior:

#. The guest will answer to a captcha, then his/her email will be
   required.
#. If he/she has given a valid email, he/she will receive a personal link
   by email.
#. Using this link, the guest will be able to input data. His/her
   firstname, lastname and message should be required fields.
#. The guest input will only appear on your website if you validate the
   data.

To perform this task, we will use new concepts: the “update view” and
the filter “sav\_filter\_pageaccess” (you already know the filter
“sav\_filter\_abc” used in example 1).

Table of Contents
-----------------

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   ExtensionOverview/Index
   InstallationAndConfiguration/Index
   TemplateAndCssFiles/Index

