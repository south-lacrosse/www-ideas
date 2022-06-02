function clientSideInclude(id, url) {
 var element = document.getElementById(id);
 if (!element) {
  alert("Bad id " + id + " passed to clientSideInclude. You need a div or span element with this id in your page.");
  return;
 }
 var req = false;
 if (window.XMLHttpRequest) {
  try {
   req = new XMLHttpRequest();
  } catch (e) {
   req = false;
  }
 } else if (window.ActiveXObject) {
  try {
   new ActiveXObject("Microsoft.XMLHTTP");
  } catch (e) {
   req = false;
  }
 }
 if (!req) {
  element.innerHTML = "Sorry, this page requires Internet Explorer 5 or better, Firefox, Chrome, Safari or another XMLHTTPRequest compatible browser.";
  return;
 }
 req.open('GET', url, true);
 req.onreadystatechange = function () {
  if (req.readyState == 4) {
   if(req.status == 200) {
    element.innerHTML = req.responseText;
   } else {
    console.log('Error',req.statusText);
   }
  }  
 };
 req.send(null);
}