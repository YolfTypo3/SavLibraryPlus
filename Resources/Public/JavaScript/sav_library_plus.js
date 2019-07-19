// JavaScript Document

// PickList II script (aka Menu Swapper)- By Phil Webb (http://www.philwebb.com)
// Visit JavaScript Kit (http://www.javascriptkit.com) for this JavaScript and 100s more
// Please keep this notice intact
// Modified by Laureny Foulloy <yolf.typo3@orange.fr>

  // Compare functions
  function sortAlpha(a,b){
    return (a[1]>b[1] ? 1 : -1);
  }
  function sortByValue(a,b){
    return a[0]-b[0];
  }

  
  // Move item function
  function move(form, from, to, sort) {
    var arrFbox = new Array();
    var arrTbox = new Array();
    var fbox=document.forms[form].elements[from];
    var tbox=document.forms[form].elements[to];
    var i;
    var selected = false;
     
    // Check if one option is selected
    for(i=0; i<fbox.options.length; i++) {
      if(fbox.options[i].selected) {
        selected = true;
      }
    }
    if (!selected)  return;  
                 
    // get the existing to item          
    for(i=0; i<tbox.options.length; i++) {
      arrTbox[i] = new Array(2);
      arrTbox[i][0] = tbox.options[i].value;
      arrTbox[i][1] = tbox.options[i].text;
    }


    // add the selected items to the to items     
    var fLength = 0;
    var tLength = arrTbox.length;
    for(i=0; i<fbox.options.length; i++) {
      if(fbox.options[i].selected && fbox.options[i].value != "") {
        arrTbox[tLength] = new Array(2);
        arrTbox[tLength][0] = fbox.options[i].value;
        arrTbox[tLength][1] = fbox.options[i].text;
        tLength++;
      } else {
        arrFbox[fLength] = new Array(2);
        arrFbox[fLength][0] = fbox.options[i].value;
        arrFbox[fLength][1] = fbox.options[i].text;
        fLength++;
      }
    }
     
    // Sort the array
    if (sort) {
      arrFbox.sort(sortAlpha);
      arrTbox.sort(sortAlpha);
    } else {
      arrFbox.sort(sortByValue);
      arrTbox.sort(sortByValue);
    }
     
    // Create the new to and from items
    fbox.length = 0;
    tbox.length = 0;
        
    var c;
    for(c=0; c<arrFbox.length; c++) {
      var no = new Option();
      no.value = arrFbox[c][0];
      no.text = arrFbox[c][1];
      fbox[c] = no;
    }
    for (c=0; c<arrTbox.length; c++) {
      var no = new Option();
     	no.value = arrTbox[c][0];
     	no.text = arrTbox[c][1];
     	tbox[c] = no;
    }
  }

  // Select all items  
  function selectAll(form, from) {
    var box = document.forms[form].elements[from];
    
    if(!box.length) {
      document.forms[form].elements[from].length = 1;
    }
    
    for(var i=0; i<box.length; i++) {
      box[i].selected = true;
    }
  } 
