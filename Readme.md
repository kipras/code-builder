# CodeBuilder

CodeBuilder is a PHP library for code generation. It is used to generate program code.

CodeBuilder supports different backends that emit output code in different languages.
Currently there are two backends:

* PHP
* C (incomplete)

## Installation

CodeBuilder can be installed via composer:

    "require": {
        "kipras/code-builder": "~0.1",
    }

## Usage

**When can such a library be useful?**  
It is useful for example when you have some application logic that could be generated once based on some user input/action and then run many times.

## Hacking CodeBuilder

CodeBuilder repository is over at github: [https://github.com/kipras/code-builder/](https://github.com/kipras/code-builder/).
Feel free to fork it and mess around.

### Tests

CodeBuilder tests use the SimpleTest framework. They can be launched:

* **From the command line**  
From CodeBuilder root directory run:

        php tests/CodeBuilder.php
* **From a web browser**  
    If you have a webserver installed and put CodeBuilder somewhere under your webserver root, (to be
    accessible via browser), open:

        http://localhost/[path_to_codebuilder_root]/tests/CodeBuilder.php
