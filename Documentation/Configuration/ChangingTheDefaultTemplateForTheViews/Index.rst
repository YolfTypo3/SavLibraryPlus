.. include:: ../../Includes.txt

.. _changingTheDefaultTemplateForTheView:

===========================================
Changing the Default Template for the Views
===========================================

The extension `sav_library_plus
<https://extensions.typo3.org/extension/sav_library_plus>`_ 
comes with default templates for
the views. Templates, layouts, partials are respectively in the
directory ``Resources/Private/Templates/Default``,
``Resources/Private/Layouts`` and ``Resources/Private/Partials``. There
are several ways of changing them.


At the Library Level
====================

Changes are made in TypoScript and will be applied to all extensions
using the SAV Library Plus. The syntax is the following for the
template, layout and partial root paths:

::

   plugin.tx_savlibraryplus.templateRootPath = yourTemplateRootPath
   plugin.tx_savlibraryplus.layoutRootPath = yourLayoutRootPath
   plugin.tx_savlibraryplus.partialRootPath = yourPartialRootPath

The default partials directory ``Resources/Private/Partials`` contains
the defaut title bars and footers respectively in ``TitleBars`` and
``Footers`` sub-directories. If you use your own partials, your
destination directory must have the same organization and must contain
the same files as in the default partials directories. It may happen
that you want to change only the title bar or the footer for one type
of view, for example the EditView as in :ref:`Tutorial 8 <tutorial8>`. 
The syntax is the following where ``viewType`` is either ``listView``, 
``singleView`` or ``editView``:

::

   plugin.tx_savlibraryplus.viewType.partialRootPath = yourPartialRootPath

In that case your partials directory needs only to contain the
partials for the title bar and the footer of the given view (see
`sav_library_example8 <https://extensions.typo3.org/extension/sav_library_example8>`_).


At the Extension Level
======================

Changes are made in TypoScript and will be applied to one specific
extension. The syntax is the following:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.templateRootPath = yourTemplateRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.layoutRootPath = yourLayoutRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.partialRootPath = yourPartialRootPath

You may also want to apply the changes only for one form in one
specific extension. The syntax becomes:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.templateRootPath = yourTemplateRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.layoutRootPath = yourLayoutRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.partialRootPath = yourPartialRootPath

To change the partial root path for a specific view type, please use:

::

   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.viewType.partialRootPath = yourPartialRootPath
   plugin.tx_yourExtensionNameWithoutUnderscores_pi1.formName.viewType.partialRootPath = yourPartialRootPath


At the Page Level
=================

Changes are made by means of the Page TSConfig. The syntax is the
following:

::

   tx_yourExtensionNameWithoutUnderscores_pi1.formName.templateRootPath = yourTemplateRootPath
   tx_yourExtensionNameWithoutUnderscores_pi1.formName.layoutRootPath = yourLayoutRootPath
   tx_yourExtensionNameWithoutUnderscores_pi1.formName.partialRootPath = yourPartialRootPath

To change the partial root path for a specific view type, please use:

::

   tx_yourExtensionNameWithoutUnderscores_pi1.formName.viewType.partialRootPath = yourPartialRootPath

