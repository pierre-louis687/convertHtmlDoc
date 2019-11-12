## convertHtmlDoc

version 1.0.1

Optimise Word HTML documents

ConvertHtmlDoc is a standards-compliant HTML5 optimiser for HTML documents generated with Word
It is written entirely in PHP/HTML.
It is stable and tested over many documents.

convertHtmlDoc provides the following features.

- Optimise HTML and CSS code generated by Word
- Runs on **PHP** 7.0.0 or newer
- The optimized document can be generated with or without headers
- The CSS code can be minified

## Installation

Copy the following files in your folder:
index.php
convert-functions.php
classes/dbManager.php
classes/myConvert.php

Make a subfolder for uploads according to the $folderDest var into index.php
Make a database with user privileges   and put the parameters into index.php



## Basic Usage

From Word, save your document as html 
Don't forget the encoding parameter (utf8 for example) into the saving toolbox: tools, web option/Coding

Then run index.php to convert your html document into an optimised html document

## Demo

the following files shows the optimisation:
Installer easyadmin Symfony 4.html: saved from Word - Before optimisation
Installer easyadmin Symfony 4_new.html: the HTML code optimized
Installer easyadmin Symfony 4.css: CSS code extracted into this file

## License

This software is released under the MIT license. The original html5lib
library was also released under the MIT license.

See LICENSE.txt

Certain files contain copyright assertions by specific individuals
involved with html5lib. Those have been retained where appropriate.
