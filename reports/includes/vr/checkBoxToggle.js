
// JS Functions for deselecting and selecting all checkboxes in a group
  function getElementsByClass( searchClass, domNode, tagName) {
    if (domNode == null) domNode = document;
    if (tagName == null) tagName = '*';
    var el = new Array();
    var tags = domNode.getElementsByTagName(tagName);
    var tcl = " "+searchClass+" ";
    for(i=0,j=0; i<tags.length; i++) {
      var test = " " + tags[i].className + " ";
      if (test.indexOf(tcl) != -1)
        el[j++] = tags[i];
      }
    return el;
  }

    function checkBoxToggleAllByName( source, cbname ){
     checkboxes = document.getElementsByName(cbname);
     for (var box in checkboxes)
       checkboxes[box].checked = source.checked;
    }

    function checkBoxToggleAllByClass( source, searchClass ){
     checkboxes = getElementsByClass(searchClass);
     for (var box in checkboxes)
       checkboxes[box].checked = source.checked;
    }


