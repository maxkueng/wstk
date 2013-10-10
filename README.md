WSTK - The Website Toolkit
==========================

**Old Code Warning!**

This code was written somewhere between 2001 and 2004 and hasn't really been touched even since. The first version was even in VBScript!

Even though this code is still running in production in some places and it has never caused any problems, further use is not recommended. It isn't well documented, probably not very straight-forward to use and not maintained any longer. There may be security issues.

## What is it?

WSTK is a file-based CMS with a plugin framweork for quickly creating simple dynamic websites in PHP.

### Core Ideas

 - A page consists of two sections: header and content
 - Everything is a plugin

### Features

 - Plugin framework
 - Plugis can depend on and use other plugins
 - Nested layouts (through plugin)
 - Multiple content sections (through plugin)
 - Partials (through plugin)
 - Any template language (through plugin, ships Liquid)
 - Any text preprocessor (through plugin, ships Markdown)
 - File-based data providers (through plugin, ships YAML provider)
 - Plugins as data providers
 - Dynamic page titles (through plugin)
 - Routes and dynamic links (through plugin)
 - More stuff (JavaScript loader, Flickr sets, syntax highlighting with GeSHi, custom HTTP headers...)

## Up and Running

### Setup

Make sure you have Apache2 with PHP5 and mod_rewrite installed.

Assuming your document root looks something like `/var/www/mysite/public_html/`, place everything in `/var/www/mysite/`

Add the following rewrite rules to your Apache2 VirtualHost configuration or create a `.htaccess` file in the `./public_html` directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_URI} !(^$)
RewriteCond %{REQUEST_URI} !(^/static/)
RewriteRule ^(.*)?$ index.php?q=$1 [L]
```

Then copy `config.dist.php` to `config.php` and edit it. Make sure you set the right `$basedir`. In our case this would be `/var/www/mysite/wstk`.

When done, the directory structure will look like this:

 - /var/www/mysite/
   - public_html/
     - index.php
     - static/
   - data/
     - pages/
   - lib/
   - plugins/
   - tmp/
   - vendor/

If you want to move one of the directories somewhere else -- e.g. the "pages" directory -- just edit the path in the `config.php` file.

### Creating a Page

Pages reside in the directory specified in `$conf['pages_dir']`. In your case that's `/var/www/mysite/wstk/data/pages`.

`$conf['default_page'] = 'home';` says that our default page is called "home". So lets create it and add some Markdown content.

```markdown
---
title: {page: Home}
syntax: Markdown
---

## Hello

Welcome to my cyberspace.
```

That's it. We have created the "/home" page with Markdown content and told the "title" plugin that the title of the page is "Home".

### Using Layouts

A layout is just a page that is handled by a plugin. So we can create a file called "main-layout" in the pages directory. This layout uses the Liquid template engine. But you could use a different template engine for every layout.

```text
---
title: {app: My Site}
type: liquid-template
---

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="{{ app.encoding }}" />
    <title>{{#title format="[page] - [app]" /}}</title>
  </head>
  <body>
    <h1>{{#title format="[app]" /}}</h1>
    <hr />
    {{ content }}
    <hr />
    <p>Last modified: {{ page.modification_time | date: "%B %d %Y %H:%M" }}</p>
  </body>
</html>
```

We have just created a layout using the Liquid template engine. In the header section we are setting the "app" part of our title to "My Site" using the "title" plugin. In the `<title>` tag we are echoing both the "app" and the "page" parts of the title while in the `<h1>` tag we are echoing just the "app" part of the title.

`{{ content }}` is where our page content will be rendered.

To use the layout in our "home" page, we just have to specify th name of the layoutin the "layout" header:

```markdown
---
title: {page: Home}
syntax: Markdown
layout: main-layout
---

## Hello

Welcome to my cyberspace.
```

## Plugins

Every plugin in the "plugins" directory will be instanciated once when the application starts. Most plugins will register hooks to perform actions upon certain events.

Let's create the "title" plugin:

```php
<?php
class TitlePlugin extends WeAbstractPlugin {

  private $title = array();

  public static function info () {
    return array(
      'name'          => 'Title', 
      'description'   => 'HTML page title', 
      'version'       => '0.1', 
    );
  }

  public function __construct ($app) {
    $app->hook('page.header.title', $this, 'onPageHeaderTitle');
    $app->hook('page.parser.tag.title', $this, 'onTitleTag');
  }

  public function onPageHeaderTitle ($hook, $page, $title) {
    $this->title = array_merge($this->title, $title);
  }

  public function onTitleTag ($hook, $parser, $params, $content) {
    if (!isset($params['format'])) return '';

    $title = $params['format'];

    preg_match_all('/\[([^\]]+)\]/i', $params['format'], $matches,  PREG_SET_ORDER  );

    foreach ($matches as $match) {
      $tag = $match[0];
      $key = $match[1];
      $value = $this->title[$key];
      $title = str_replace($tag, $value, $title);
    }

    return $title;
  }

}

WeApplication::instance()->registerPlugin('title', 'TitlePlugin');
```

Every plugin subclasses the `WeAbstractPlugin` class and provides a static `info` method. The plugin then registers itself using the `WeApplication::instance()->registerPlugin` method.

In the constructor, a plugin is provided a `WeApplication` instance and may "hook" in to certain events of the application.

In the case if the "title" plugin, the plugin hooks in to the "page.header.title" event to store part(s) of the title in its title property. The "page.header.title" is fired if the page header section contains a "title" field.

Secondly, it hooks in to the "page.parser.tag.title" event, which is triggered when the content parser comes across the `{{#title /}}` tag, to output a formated title in place of the tag.

### Hooks

 - **app.init (app):** fired right after the all the plugins have been instanciated
 - **app.root\_page\_init (app):** fired after the root page has been initialized
 - **page.header (page, headerName, headerValue):** fired for every header in the header section
 - **page.header.{name} (page, headerValue):** fired when the header "{name}" occurs in the header section
 - **page.parser.before_body (parser):** fired before the content is being parsed
 - **page.parser.after_body (parser):** fired after the content has been parsed
 - **page.parser.before_tokenize (parser):** fired before the content is tokenized and the tags (plugin calls) are being resolved
 - **page.parser.after_tokenize (parser):** Same as above but after
 - **page.parser.tag.{tagname} (parser, params, content?):** fired when a tag names {tagname} has been found. If the tag is not self-closing then the content between the opening and the closing tag will be provided as a third parameter

## Further Documentation

Try to read the code and look at the plugins.

## License

MIT License

Copyright (c) 2001 Max Kueng (http://maxkueng.com/)
 
Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:
 
The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.