# Render:: v0.8.0 (beta)

This is is simple module, that autoloads a single static class Master::, which then gives programmer access to simpler work with templates: `view loading`, `template stacking` and more.

## Why?

I despise dealing with templates (as in: html templates) in ProcessWire templates. Loading the correct JS only for some pages, loading different stylesheets, figuring what is acceptable HTML to be outputted in foreach loops and such. I want a folder dedicated to html pages, inclusion of partials to be as streamlined and possible, etc.

There is a lot of testing ahead – what features are missing, which are illogical and such, so this sure isn't final version, but rather living beta version.

## Usage & notes

* When you pass a data array to the function, it has to be **associative** array. All values are then available as variables under their respective keys
* All filename parameters are relative to `/site/views/` and **without trialing .php**, so if you want to use e.g. `/site/views/_master/application.php`, the parameters is `_master/application`
* For available functions see [#methods](Documentation – Methods section)

## Example

Following example will render `$page->title` followed by `p.special` with content '**anything**' and followed by `$page->body`

/site/templates/Home.php
``` php
  Render::init();
  $page->specialValue = 'anything';
  Render::master(array(
  	'content'=>Render::page()
  ));
```

/site/views/_master/application.php
``` html
   <html><body>
     <?= $content ?>
   </body></html>
```

/site/views/Home.php
``` html
	<h1><?= $title ?></h1>
	<p class="special"><?= $specialValue ?></p>
  <?= $body ?>
```

## Installation

1. Install this module
2. Create directory `/site/views/`
3. Add your templates.

Note: if you wish to use default master path in the `init()`, you have to add it into `/site/views/_master/application.php`

## Documentation

### Introduction

Very simple. This module consists of 4 files:
* `README.md` – readme, this file
* `HelperRender.module` – PW Module used for simpler manipulation with code and auto initialization of the class
* `HelperRender_Template.php` – Object used for handling the 'templates' – data manipulation, file assignment, etc. You don't need to concern yourself with this one
* `HelperRender_Render.php` – The master `Render::` class. This one you'll use, via publicly available methods

### Methods

note: parameters marked (opt) are optional

`Render::init`, parameters: `(opt) string $view`
Initializes the master template. If $view is given, it is used as master template. If not, default master template (defaulting to `_master/application`) is used.

`Render::master`, parameters: `(opt) array $data`, returns: `string`
Core function. Renders the master template and returns it as a string.

`Render::partial`, parameters: `string $fileName, (opt) array $data`, returns: `string`
Renders simple partial by `$fileName`*, given the data in the $data array.

`Render::page`, returns: `string`
Renders simple partial; as template filename is used name of current template, data is extracted from current page ('autmagically')

`Render::loop`, parameters: `string $fileName, (opt) array $data, (opt) string $separatorFileName`, returns: `string`
Renders collection of partials and returns it as a string. Data passed should be an array of items, which are then **passed to the single collection item as** `$item`. Optionally, you can pass a `$separatorFileName`, a name of a view that should be used between the items as a separator. In both template and separator template, you get additional variable `$HM_Count`, which has the number of an item in it.

`Render::auto()`
Simplest possible usage, good for articles for instance; Uses default master template, which is given variable 'content', cotaining render of a `/site/views/{page template}.php`, which was given all the fields of page.

## TODO

This is a list of features I plan to implement as I go.

* ~~Inclusion of partials~~ – done in v0.3.0
* ~~Auto extraction of page variables (fields+changes)~~ – done in v0.5.0
* ~~Auto extraction of unformatted page variable~~ – done in v0.5.1
* ~~Inclusion of collections~~ – done in v0.3.0
* ~~Inclusion of collections with separators~~ – done in v0.6.0
* Inclusion of collection with variables informing developer where in the array he is (HR_first, HR_last, HR_odd, HR_even)
* Inclusion of collections with separators included in partials (to be not shown if HR_last)
* Force `render::collection` to expect array of arrays, which will be then extracted as variables
* Add render::pageCollection, where the data will be extracted from each page half-automatically; Developer will have to manually mark fields he wants to extract
* Stylesheet and Javascript handling 
* Javascript handling: differentiate between 'head' and 'before /body' javascript
* Create Render::add() function, so developer doesn't have to pass all data as array when calling Render::master()

---
Created in 2012 by Adam Kiss
Licensed under WTFPL (http://sam.zoy.org/wtfpl/)