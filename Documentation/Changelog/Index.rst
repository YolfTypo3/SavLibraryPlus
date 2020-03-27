.. include:: ../Includes.txt

.. _changelog:

=========
Changelog
=========

.. tabularcolumns:: |r|p{13.7cm}|

=======  ===========================================================================
Version  Changes
=======  ===========================================================================
10.3.0   - Compatiblity changed to TYPO3 10.3.0.
         - Compatibility with TYPO3 8.7 removed.
         
9.5.0	 - Compatibility with TYPO3 7.6 removed.
         - Compatibility changed to TYPO3 9.5.x
         - Files processed in the FAL.
         - New features (requiredIf, valueIf, processing of "and", "or" in conditions...).

1.2.0    - Compatibility with TYPO3 6.2 removed

1.1.0    - Processing for RichTextEditor modified.
         - Compatibility changed to TYPO3 8.9.
         - composer.json added
         - Vendor name changed
         
1.0.1    - Compatibility changed to TYPO3 8.1.
         - New attribute rteStyleSheet added for the rich text editor item viewer.
         
1.0.0    - Several bugs corrected (see the forge).
         - Compatibility with TYPO3 8.0.1 added.
         - css, javaScript and icon files transfered to the directory Resources/Public.
         - Compatiblility with TYPO3 versions lower than 6.x removed.
         
0.3.0    - Documentation converted to the reStructuredText format.
         - New feature in export view: query can be used to export data
           (see Tutorial 8).
         - The queriers have been simplified to provide faster queries.
         - Localized records are now handled.
         - Compatibility with TYPO3 7.x.x added.
         
0.2.1    - Several warnings generated when using arrays corrected.
         - js file for the calendar in Deutch corrected (Thanks to Erwin Winkel).
         - New type "currency" added (Feature #52986).
         - Small bugs corrected.
         - Code slighly modified in accordance to the TYPO3 coding guidelines.

0.2.0    - Compatibility with TYPO3 6.1 and 6.2 added.
         - Code for exporting data slightly modified.
         - Small modification in the graphItemViewer to allow the use of queries
           in sav_jpgraph.
         - New configuration feature added. Additional parameters can be added to
           links from TypoScript at the library, extension or page levels.
           Additional parameters can also be added from TypoScript to filters.
         - Subforms can be included into subforms.
         - New DIV savFilter added to filters in order to allow global
           configuration in CSS.
         - New special marker ###link[...]### and ###linkDefault[...]### added
           for title bars.(see the note in the orderLinkInTitle attribute in the
           SAV Library Kickstarter Reference section).
         - Default CSS simplified.

0.1.0    - New attribute addLinkInEditMode added for files.
         - Small bug corrected in the subform title.
         - Suggestion #41038 added. Icons in item viewers can changed by adding
           them in the iconRoot directory defined by iconRootPath. Icon file
           extensions can be either .gif or .png or .jpg or .jpeg.
         - documentation and csh files updated.
         - Compatibility with TYPO3 6.0 added.

0.0.4    - Feature #39265 added. The former "iconsDir" and "imagesDir" are now
           replaced respectively by "iconRootPath", "imageRootPath". The values
           and the templates, layouts, partials rooth paths can be changed at the
           library, extension or page levels. Field configurations can now also
           be changed at the extension or page level. The default cascading style
           sheet is now â€œResources/Private/Stylesâ€�. It can be changed by
           TypoScript.
         - Documentation updated to the new documentation template (doc_template
           1.6.2).
         - Language files in the XML Localisation Interchange File Format (.xlf)
           added in Resources/Private/Language for the translation.
         - Suggestions #39436 and #39437 added. Configuration of the style sheet,
           the date format fo the title bar and the tool tip are now possible by
           TypoScript at the extension and library level.
         - Suggestion #39505 added. Icon of the sav_filter_abc extension can be
           changed by typoScript.
         - Major feature #39829 added. Values filled by the user in edit view are
           reloaded in case of errors.
         - Feature #40074 (from the SAV Library Kickstarter forge project) added.
           Table sorting configuration from the SAV Library Kickstarter is now
           working correctly.
         - Context Sensitive Help localization files have been totally
           reorganized to facilitate the translation process.

0.0.3    - Small bug in the cutIf attribute corrected.
         - Bugs #39078, #39079, #39082, #39200, #39206, #39207, #39230 corrected.
         - New date and dateTime default configuration (feature input as Bug
           #39181).

0.0.2    - Small changes in the â€œExportâ€� feature.
         - Small bugs corrected.
         - Documentation updated.

0.0.1    - 1st public release
=======  ===========================================================================