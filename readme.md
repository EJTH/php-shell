PHP-Shell
==
![Screenshot of phpshell](https://raw.githubusercontent.com/EJTH/php-shell/master/doc/screenshot-1.png)

What is PHPShell
==
PHPShell is foremost a pet project I make in late evenings.
It is also a wannabe shell for your browser based on PHP, it is compact and comes in a single file (So you could use it for PoC pentesting etc. but seriously, there is better stuff out there)

Interactive support
==
While PHP-shell technically isnt an interactive shell it does have somewhat interactive support

Extending
==
PHP-shell has a very simple addon system. Take a look at the example addons in the `addons/` folder. You basicly just register PHP callables (eg. functions) with the function `registerCommand()` and voila they are listed in the `help` command and are usable through the shell interface.
