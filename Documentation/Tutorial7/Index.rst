.. include:: ../Includes.txt

.. _tutorial7:

======================
Tutorial 7: Guest Book
======================

The aim of this extension is to deal with multiple forms of the same
table and to explain how update views can be used. It creates a guest
book which is inspired from the extensions available in the TER. 
Download this example from the TER (`sav_library_example7 
<https://extensions.typo3.org/extension/sav_library_example7>`_). 
This extension uses a table with the following fields:

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

To perform this task, we will use new concepts: the **update view** and
a filter for the page access thanks to the extension 
`sav_filters 
<https://extensions.typo3.org/extension/sav_filters>`_ already used in 
:ref:`tutorial 1 <tutorial1_howTo>`.

Table of Contents
=================

.. toctree::
   :maxdepth: 5
   :titlesonly:
   :glob:

   ExtensionOverview/Index
   InstallationAndConfiguration/Index
   TemplateAndCssFiles/Index

