.. include:: ../../Includes.txt

.. _tutorial6_extensionOverview:

==================
Extension Overview
==================

Installation
============

Download this example from the TER (`sav_library_example6 
<https://extensions.typo3.org/extension/sav_library_example6>`_).

#. Install the extension,

#. Copy the file **invoice.rtf**, which is in the extension directory, in
   the **fileadmin** directory,

#. Add the following line in the field TSconfig of the page where you
   have installed the extension. It will overload the email sender defined 
   in the extension as it will be  described in the configuration section.
   
   ::
   
      tx_savlibraryexample6.View1.editView.fields.email_flag.mailSender
      = your_email@your_provider 
   
   
Using the Extension
===================

Use the input form to enter a conference participant as in the
following caption.

.. figure:: ../../Images/Tutorial6EditViewBeforeEmailSent.png

Then select the email language (default or French) and click on the
email icon. If the email is correct, the form will slightly change as
shown below. You cannot click on the email icon anymore (if you need
to re-send the email, cancel the checkbox at the right hand side of
the email icon and save the form).

.. figure:: ../../Images/Tutorial6EditViewAfterEmailSent.png

You should have received an email as the one below:

.. code::

   Dear Yolf,

   Thank you for your registration to the conference.

   Your registration includes:

   √ Conference

   - Proceedings

   - Meals

   √ Banquet


   Your invoice will be available at the registration desk.

   Looking forwards to seeing you.

   Best regards,

   The conference organization committee.

To generate the RTF file, click on the icon associated with the
invoice, then open the generated file by clicking on the link and
print it (the fields are automatically updated).

.. figure:: ../../Images/Tutorial6EditViewAfterRtfGenerated.png

