/* Replace text for small screens e.g.
    <td data-sml-text="Welwyn">Welwyn Warriors</td>

THIS SCRIPT IS NOT USED - but has been added to the repo as I'd
done the work, and it might be useful in future

The current method of doing this is with a little extra markup, and css:

<td data-sml-text="Welwyn"><span>Welwyn Warriors</span></td>
Need <span> wrapper for text so we can make that <span> not 
displayed. There are other methods of doing that, but this
seems to be the simplest, albeit with a bit of extra markup.
[data-sml-text]>span {
    display: none;
}
[data-sml-text]:before {
   content: attr(data-sml-text);
}
*/
(function() {
	'use strict';
	var isSmlText = false;
	var smlTextElems;
	window.addEventListener("resize",onResize,false);
   	window.addEventListener("orientationchange",onResize,false);
   	onResize(); // make sure we load right version

    function onResize() {
		if (document.body.clientWidth < 600 && !isSmlText) {
    		replaceText(true);
    	} else if (document.body.clientWidth > 599 && isSmlText) {
    		replaceText(false);
		}
    }

    // replace text on elements with data-sml-text atttibute with replacement or original text
    function replaceText(replaceText) {
    	var attr = toShort ? "data-sml-text" : "data-original";
    	var saveOriginal = false;
    	if (!smlTextElems) {
    		// never run before - so get elements with data-sml-text atttibute, and remember
    		// we need to saveOriginal originals in case of resize
    		smlTextElems = document.querySelectorAll("[data-sml-text]");
    		saveOriginal = true;
    	}   	
    	// [].forEach.call(document.querySelectorAll('[data-sml-text]'), function(dataShort) {
    	for (var i = smlTextElems.length; i--;) {
    		if (saveOriginal) {
	    		smlTextElems[i].setAttribute("data-original", smlTextElems[i].innerHTML);
	    	}
		    smlTextElems[i].innerHTML = smlTextElems[i].getAttribute(attr);
		}
		isSmlText = toShort;
    }
})();