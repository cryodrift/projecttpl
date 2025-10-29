# PHP based Site Generator JAMSTACK Style

### attention the system auth does only run with https cookies ! 
### on php -S localhost:port index.php without a https router it does not work
- made with php8.3 and javascript modules and sqlite
- all the non-dynamic parts are pre-rendered and the dynamic elements are hydrated into the site on the client side.
- single page app generator
- everything commandline

## simple small code base, easy to understand, reusable, interactiv

- prerendered static, dynamic, cacheable
- api delivering html parts
- save ui state in url

### Project Structure

- src/appname
    - Api.php contains methods that build the page parts
    - Page.php builds the page by using Api
    - Cli.php contains methods that handle commandline requests
- src/appname/ui
- src/appname/ui/...parts
- src/appname/ui/shared/...parts
- src/appname/db
    - Repository.php contains methods that return your data
    - c_index.sql create indexes
    - c_tables.sql create tables
    - c_views.sql create views
    - c_triggers.sql create trigger
    - s_table.sql select from
- src/appname/shared
- libs/
- .data/configs...php (generated)
- /index.php
- /sys/
- /sys/cli
- /sys/tool
- /sys/js
- /sys/css
- /sys/trait
- /cfg (override configs here)


# Template Syntax

This project ships with a very small, fast HTML templating syntax used by the JAMStack-style renderer (sys/HtmlUi). You write plain HTML and sprinkle it with double-brace placeholders and a few lightweight directives. Most templates live in src/<yourapp>/ui and are used by Api/Page classes.

Core concepts at a glance
- Variables: {{name}}
- Attribute injection: <input value="{{name}}" {{readonly}}>
- Repeat blocks: {{@}}blockName{{@}} ... {{@}}blockName{{@}}
- File include: {{@file@}}relative/path/to/file.html{{@file@}}
- I18n/labels: {{Name}} vs data keys like {{Name}}
- Works great with data-* attributes for client-side hydration (see next section)

Variables and attribute injection
- Use {{var}} to insert values anywhere in text or attributes.
- Boolean attributes: provide keys like readonly, disabled, hidden, selected etc. If the key is non-empty, the attribute is rendered; if empty, it disappears.

Example (from scaffolding: rechnung/ui/invoiceedit.html)
```html
<form id="invoice-form" class="p-4">
  <div class="mb-3">
    <label for="inputInvoiceNo" class="form-label">{{Invoice No.}}</label>
    <input id="inputInvoiceNo" type="text" class="form-control" name="name" value="{{name}}" {{readonly}}>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="inputCompany" class="form-label">{{Company}}</label>
      <select id="inputCompany" class="form-select" name="company_id" {{disabled}}>
        {{@}}companies{{@}}
        <option value="{{id}}" {{selected}}>{{name}}</option>
        {{@}}companies{{@}}
      </select>
    </div>
  </div>
</form>
```

Repeat blocks (lists)
- Wrap a region with {{@}}blockName{{@}} markers to declare a repeatable block.
- In PHP, pass an array under blockName to render one instance per item.

Example (from scaffolding: rechnung/ui/invoiceproducts.html)
```html
<div id="invoice-products-container" class="mb-3">
  {{@}}invoice_products{{@}}
  <div class="row g-2 align-items-end mb-2" data-line-id="{{line_id}}">
    <div class="col">
      <input type="text" class="form-control" name="lines[{{line_id}}][product_name]" value="{{product_name}}" placeholder="{{Description}}" required {{readonly}}>
    </div>
    <div class="col-2">
      <input type="number" class="form-control" name="lines[{{line_id}}][quantity]" value="{{quantity}}" placeholder="{{Quantity}}" required {{readonly}}>
    </div>
    <div class="col-2">
      <input type="number" step="0.01" class="form-control" name="lines[{{line_id}}][unit_price]" value="{{unit_price}}" placeholder="{{Unit Price}}" required {{readonly}}>
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-danger btn-remove-line {{hidden}}" type="button">{{Remove}}</button>
    </div>
  </div>
  {{@}}invoice_products{{@}}
</div>
```

Include other template files
- Use the file include directive to inline another template at build time.
- Syntax: {{@file@}}relative/path/from/src/<app>/ui{{@file@}}

Example (from scaffolding: rechnung/ui/invoiceedit.html)
```html
<h5 class="mt-4">{{Line Items}}</h5>
{{@file@}}rechnung/ui/invoiceproducts.html{{@file@}}
```

Simple i18n/labels
- Using Title Case placeholders like {{Name}}, {{Invoice No.}}, {{Save Invoice}} allows you to feed translated strings from the backend.
- Data keys like {{name}} refer to fields inside your data arrays/objects.

Composing in PHP with HtmlUi
- Read a file: HtmlUi::fromFile($path, $rootBlock?)
- Read a string: HtmlUi::fromString($html, $rootBlock?)
- Select a block: ->fromBlock('blockName')
- Bind data: ->setAttributes(['blockName' => $listArray, 'name' => 'value', ...])

Backend example (used in src/files/Web.php)
```php
return HtmlUi::fromFile(__DIR__ . '/ui/files/row.html', 'files')
  ->fromBlock('files')
  ->setAttributes(['files_block' => $data, ...$ctx->request()->vars()]);
```

Note on Twig-like loops in scaffolding
- Some scaffold examples show Twig-style loops like {% for product in products %} for illustration. The preferred native syntax in this repo is the {{@}}block{{@}} repeater with data passed via HtmlUi::setAttributes(). If you use Twig blocks, ensure your pipeline renders them before HtmlUi, or convert them to {{@}} blocks.

See also
- Live data and hydration are done via data-* attributes described below.
- More examples: src/rechnung/ui/preview.html, products.html, invoiceedit.html, invoiceproducts.html.

# Use data-* attributes to do Anything

## data-scrollable="apiName"
- 
- use to load content when scrolling
- manages state as url param
- used in ui/memos.html

### data-scrollable-page
- use to make change the url-param onscroll

```html
{{@}}memos{{@}}
<a class="separator g-dh" id="memos_page-{{memos_page}}" data-scrollable-page="{{memos_page}}">Page {{memos_page}}</a>
{{@}}memos_block{{@}}
<div id="memo{{id}}" class="card mb-2">
  <div class="card-body">
      <button class="btn" href="{{ROUTE}}/{{query}}" data-loader="memo">Show</button>
      <h5 class="card-title">{{aktiv}} {{status}} {{kat}} {{name}} {{title}}</h5>
      <p class="card-text">{{shortcontent}}</p>
  </div>
</div>
{{@}}memos_block{{@}}
{{@}}memos{{@}}
```

- used in api::memos()

```php
if (count($data)) {
    $data = HtmlUi::addQuery($ctx, $data, ['id' => 'memo_id'], ['memo_id', 'memos_page', 'memos_search']);
    return $base->fromBlock('memos')->setAttributes(['memos_block' => $data, ...$ctx->request()->vars()]);
} else {
    return HtmlUi::fromString('');
}
```

## data-loader

- use to fetch html from server and insert into document
- used in ui/memo.html
- param format: apiname|destId|outer
- optional param: outer replaces the outerHTML and only one childNode is allowed in Dokument
- if outer is left out innerHTML gets replaced and many childNodes are allowed
- params: apiname|destination|replacepos
- params: (some apiname)|[self(keyword)|id|(.or empty for closest parent id)]|[outer(keyword)|(.or empty for inner)]
- params example: memo|self|outer

 
### data-replace
- data-replace="search|replace" replace data in destination path to whatever you want

### data-loader-url
- data-loader-url="{{ROUTE}}/{{query}}"
- or
- href="{{ROUTE}}/{{query}}"

```html
{{@}}memo{{@}}
<div class="card mb-2">
    <div class="card-body">
        <button class="btn" href="{{ROUTE}}/{{query}}" data-loader="memo_delete|memo memo{{id}}||outer" data-loader-method="post">Delete</button>
        <h5 class="card-title">{{aktiv}} {{status}} {{kat}} {{name}} {{title}}</h5>
        <p class="card-text">{{content}}</p>
    </div>
</div>
{{@}}memo{{@}}
```

- backend used in api::memo()

```php
$data = HtmlUi::addQuery($ctx, $data, ['id' => 'memo_id'], ['memo_id']);
return $base->fromBlock('memo', true)->setAttributes(['memo' => $data]);
```

## data-

- data-click (old version only click) and data-handler (new version every event)

### data-click 

- use this on a parent
- can use any javascript module look at eventhandler.js how it works
- used in eventhandler.js tablisttools.js

### data-handler 

- data-handler="/eventhandlers.js|click" 
- use this on a parent
- can use any javascript module look at eventhandler.js how it works
- used in eventhandler.js tablisttools.js

### data-click

- use to make anything happen on element.click

- "functionname|param1|param2|paramX functionname|targetId functionname"

```html
 <button data-click="toggle|insert_box_fields toggle|insert_box_content"  type="button">Button</button>
```

### data-queryclick

- use to restore a ui feature from url
- clicks the Element when query state matches
- params: query-param-name|query-param-value
- this is boolean
-  

```html
 <button data-queryclick="test|hallo" data-click="togglenext hide addquery|where|here" type="button">Here</button>
 <button class="g-dh" data-click="toggleprev hide remquery|where|here" type="button">There</button>
```

### data-observe
 
- everytime that div gets attached to the dom the scripts will run again
- we run it also once when the page is ready

```script
 import {dataObserver} from '/system.js';
 dataObserver(document.body)

```
```html
 <div data-observe="/scriptfile.js|method|param|..s"></div>
```


# Extractcomp Tool

The extractcomp tool uses an external helper script git-filter.cmd (a wrapper you provide) to run git filter-repo and filter a repository to a specific subdirectory's history and optionally rewrite metadata across all commits.

- Command used: git-filter.cmd --path <subdir>/ --force [--replace-text <file>] [--replace-text <file>] ... [<extra args from -gitmeta> ...]

Rewrite file format for --replace-text:
- Each line defines one rewrite mapping applied across history
- literal:old_text==>new_text
- regex:/pattern/flags==>replacement

Notes:
- You can pass multiple -gitrewritefile arguments; each becomes a separate --replace-text file processed by git filter-repo.
- Deprecated: extractcomp previously allowed -gitsearch and -gitreplace pairs. These are now internally converted into a temporary rewrite file with literal:search==>replace entries for compatibility.
- The -gitmeta options have two roles:
  - Flags starting with '-' (e.g., --mailmap PATH) are passed verbatim to git-filter.cmd to influence history rewrite.
  - Key/value pairs (e.g., user.name John Doe, user.email john@example.com) are applied as git config in the working repo before we create commits so new commits use this identity. Aliases authorName/authorEmail are also accepted and mapped to user.name/user.email.
  - If you provide authorName/authorEmail (or user.name/user.email), extractcomp auto-generates git filter-repo callbacks: --name-callback 'return b"<Name>"' and --email-callback 'return b"<email>"' unless you already passed these flags.

Ensure your git-filter.cmd is on PATH and forwards arguments to git filter-repo, accepting all original filter-repo parameters. Non-flag tokens are not forwarded to git-filter-repo to avoid errors like "unrecognized arguments: authorName ...".


# ERRORS

## Missing session user!

on cli you need -sessionuser="username"
on web you need to have a session (install src/user)
