/**
 * Javascript which should be copied into the <head> section of the website (i.e.
 * in header.php).
 * It is split out here so the code can be worked on, then minified.
 * To minify run "npm run site-js" from the command line.
 * Note: this is a manual process, as it really isn't worth automating :) So you
 * need to copy in-head.min.js into header.php
 */

var haveSs = "sessionStorage" in window, d = document.documentElement;
/**
 * With webfonts we want to load them async to speed up page load, BUT also display
 * without a FOUT (flash of unstyled text) when they have downloaded.
 *
 * See https://css-tricks.com/loading-web-fonts-with-the-web-font-loader/ for details
 *
 * Browser without sessionStorage will FOUT on every page (<=IE9), but others will
 * recognise that fonts are cached and add the css class to use the web font asap
 */
if ("addEventListener" in window) {
  d.className = "js";
}
// if we have already loaded our font on a previous page, then make sure page uses the font asap
d.className += (haveSs && sessionStorage.fonts ? " wf-active" : "") +
  (typeof SVGRect === "undefined" ? "" : " svg");
WebFontConfig = {
  google: { families: ["Open Sans:400,400i,700"] }
};
if (haveSs) {
  WebFontConfig.active = function () {
    sessionStorage.fonts = true;
  };
}
