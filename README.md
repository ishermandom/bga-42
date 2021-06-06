A BoardGameArena implementation for Texas 42.

Game information: https://en.wikipedia.org/wiki/42_(dominoes)


## Development setup

When writing code, please autoformat using the
[`atom-beautify`](https://github.com/Glavin001/atom-beautify#installation)
package.  You will need to locally download/install
[php](https://www.php.net/manual/en/install.php) and
[PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer#installation).

After installation, go into the settings for atom-beautify (command pallet
(Ctrl+Shift+P) -> Settings -> Packages -> atom-beautify -> Settings) and under
PHP, switch "Allow risky rules" to "yes", and under "PHP-CS-Fixer Config File",
point it to the .php-cs-fixer.php file in the base directory of this project
(sdspikes: I give it an absolute path, I couldn't figure out how to do a
relative one, nor did using the naming it suggested work for me.  I used this
name because when I run php-cs-fixer on the command line it wants it to be
`.php-cs-fixer.php`, so I figured that was a better default).  You may or may
not want to turn on "Beautify on Save".
