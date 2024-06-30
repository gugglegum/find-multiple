# Find-Multiple
**Recursive search for files in a directory whose contents match several regular expressions at the same time**

English | [Russian](README_RU.md)

Sometimes you need to find a certain place in a large project, but you don't know where to look. You just assume that there will be mentions of certain words or sequences of characters in that file, but you don't know in what order or what will be between them. For example, you assume that there must be calls to certain functions or names of tables in the database in that place. However, searching for each of these words individually yields too many results. I would like to have a function like "search in found" to narrow down the manual search. Unfortunately, your favorite IDE from JetBrains (PHPStorm) can't do that. This utility comes to the rescue. You pass to it as command line parameters a search directory, a file mask (a wildcard, for example, "*.php") and then one or more regular expressions of the form, each of which must correspond to at least one line of the searched file. The script recursively scans the specified directory, searches for files of the specified mask, checks them for compliance with all regular expressions and outputs the found files. In addition to file names, the script also displays fragments of these files with line numbers where lines matching each of the regular expressions were found. It shows 5 lines before and 5 lines after the line that matches the regular expression. The line that matches the regular expression is highlighted with the ">" symbol. Several close fragments are merged into one large fragment. See the screenshot to better understand how it works by example.

## Screenshot
![2024-06-30_02-18-34](https://github.com/gugglegum/find-multiple/assets/1580712/020740cb-5cb9-4541-896f-08635cd86e3b)

In the screenshot above, we are looking for `*.php` files in the current directory that contain lines corresponding to 2 regular expressions: `/'_'|"_"/` and `/time\(\)/`.

## Usage

```shell
php find-multiple.php /path/to/dir "*.php" "/regex1/" "/regex2/" ... "/regexN/"
```

Regular expressions are passed in a form suitable for passing to the `preg_match()` function. It is recommended to enclose them in quotes to avoid problems with the interpretation of quotation marks, asterisks (`*`), vertical dashes (`|`), `<`, `>`, `&`, etc. In this case, quotes inside a regular expression must be additionally escaped with a backslash `\`. To check the correctness of parameter passing, decoded parameters are output at the beginning of the script.

**Linux users' attention:** When passing the file mask (2nd parameter), don't forget to enclose it in quotes too, because otherwise bash will implicitly replace this parameter with files that correspond to this mask in the current directory. As a result, the order of parameters will be broken and some file names will be interpreted by the script as incorrect regular expressions.

## Set as system command

Add the directory with this utility to the PATH environment variable, and then you will be able to call this utility from any directory without having to specify the script path and the php interpreter. On Windows you don't need to do anything else, the `find-multiple` command will automatically call `find-multiple.cmd` which will call the php script from the same directory as the CMD file. On Linux you can create a symbolic link to the script without the .php extension (`ln -s find-multiple.php find-multiple`).
