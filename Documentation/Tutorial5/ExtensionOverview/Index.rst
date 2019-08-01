.. include:: ../../Includes.txt

.. _tutorial5_extensionOverview:

==================
Extension Overview
==================

Edit the extension `sav_library_example5 
<https://extensions.typo3.org/extension/sav_library_example5>`_
in the SAV Library Kickstarter to get an overview. It contains
one form with three conventional **List**, **Single** and **Edit** views.

There is nothing special in the **List** and **Edit** views.

Attributes in the Single View
=============================
 
The **Single** view contains the call to the hook in the field 
**hook_content** defined as **Only shown in SAV form**. 
The attributes must define the hook name and the hook parameters.

The hook name is defined by means of the hookName property.

::

   hookName = SavLibraryExample5;
   
The hook parameters are defined by the hookParameters property 
as a JSON array.  
   
::

   hookParameters = {
     "template": "Test.html",
     "uid": "###uidMainTable###"
   };

Configuration of the Hook in the File ext_localconf.php
=======================================================  

The class to be called is set in the variable 
**$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sav_library_plus']['hooks']['YourHookName']**
in the file **ext_localconf.php file**. 

::

   // Adds a hook for SAV Library Plus
   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sav_library_plus']['hooks']['SavLibraryExample5'] = \YolfTypo3\SavLibraryExample5\Hooks\SavLibraryPlus::class;
   
In this example, the class **\YolfTypo3\SavLibraryExample5\Hooks\SavLibraryPlus**
is in the file **typo3conf/sav_library_example5/Classes/Hooks/SavLibraryPlus.php**.

Rendering the Hook
==================

The class **\YolfTypo3\SavLibraryExample5\Hooks\SavLibraryPlus** extends the
class **\YolfTypo3\SavLibraryPlus\Hooks\AbstractHook**. The method **renderHook($parameters)**
is called to render the hook.

In this example, this method creates a template view, whose name is provided by the hook parameter 
**template** and fetches the current record associayed with the **Single** view.

::

   /**
    * Renders the hook
    *
    * @param array $parameters
    *
    * @return string
    */
   public function renderHook($parameters)
   {
       // Gets the parameters
       $template = $parameters['template'];
       $uid = $parameters['uid'];

       // Creates a view for more fluid processings of the template
       /** @var StandaloneView $view */
       $view = GeneralUtility::makeInstance(StandaloneView::class);
       $view->setTemplatePathAndFilename('EXT:sav_library_example5/Resources/Private/Templates/' . $template);

       // Selects the record
       $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_savlibraryexample5');
       $rows = $queryBuilder->select('*')
           ->from('tx_savlibraryexample5')
           ->where($queryBuilder->expr()
           ->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
           ->execute()
           ->fetchAll();

       // Assigns the row variable
       $view->assign('row', $rows[0]);

       // Renders the content
       $content = $view->render();

       return $content;
   }

The FLUID template is in the folder **Resources/Private/Templates** of the extension. 
It compares the values of the field and displays them in the ascending order.

::
 
   <div class="hookHeader">
       <f:translate key="message" extensionName="sav_library_example5" />
   </div>
   <ul class="hookList">
       <f:if condition="{row.field1} < {row.field2}">
           <f:then>
               <li>{row.field1}</li>
               <li>{row.field2}</li>
           </f:then>
           <f:else>
               <li>{row.field2}</li>
               <li>{row.field1}</li>
           </f:else>
       </f:if>
   </ul>
   
   