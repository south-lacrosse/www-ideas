<?php
/**
 * This is the template that displays all of the <head> section and everything up until
 * the main content area
 * 
 * Note: quite a bit of info is hardcoded. Since this is a custom theme
 * for only this site, and hardcoding stops database lookups
 *
 * If the CSS changes then update the version in ?ver=1.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 * @package Lax
 */
// if comments are enabled then uncomment this
// if (is_singular() && comments_open() && get_option('thread_comments')) {
// 	wp_enqueue_script('comment-reply');
// }
$style = get_theme_file_uri( '/style' . SEMLA_MIN . '.css' ) . '?ver=1.0';
?><!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
<?php if (defined('SEMLA_ANALYTICS')) : ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= SEMLA_ANALYTICS ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= SEMLA_ANALYTICS ?>');</script>
<?php endif; ?>
<script>var haveSs="sessionStorage"in window,d=document.documentElement;"addEventListener"in window&&(d.className="js"),d.className+=(haveSs&&sessionStorage.fonts?" wf-active":"")+("undefined"==typeof SVGRect?"":" svg"),WebFontConfig={google:{families:["Open Sans:400,400i,700"]}},haveSs&&(WebFontConfig.active=function(){sessionStorage.fonts=!0});</script>
<noscript><link href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,700&amp;display=swap" rel="stylesheet"/></noscript>
<link href="<?= $style ?>" rel="stylesheet">
<link href="/favicon.ico" rel="shortcut icon">
</head>
<body <?php body_class(); ?>>
<script src="https://cdnjs.cloudflare.com/ajax/libs/webfont/1.6.28/webfontloader.js" async></script>
<a class="skip-link screen-reader-text" href="#content">Skip to content</a>
<header>
<div class="banner inner">
<a title="Display Menu" href="#menu" id="show-menu" role="button" aria-expanded="false">Menu</a>
<a title="South of England Men's Lacrosse Association Home" href="/" class="logo">SEMLA</a>
<nav class="popular-links">
<a href="/fixtures">Fixtures</a> |
<a href="/tables">Tables</a> |
<a href="/flags">Flags</a> |
<a href="/clubs">Clubs</a>
</nav>
</div>
</header>
<nav class="menu-nav">
<div id="menu" class="inner">
<div id="overlay"></div>
<div class="menu-wrapper">
<button id="close-menu">Close Menu</button>
<?php
// wp_nav_menu executes 4 queries, which return a load of data, so rather than
// calling directly the menus are cached here.
lax_menu();
?>
</div>
<a id="search" title="Search the site" href="/search">Search</a>
</div>
</nav>
<div class="middle inner">
