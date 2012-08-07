![Magento js file minified](http://i.imgur.com/w9NdG.png)
## Introduction

WBL_Minify extension enables minification of magento css merged files and/or  javascript merged files.  
You can choose to use YUICompressor (included). In that case, be sure to have Java installed on your server and MAGE_ROOT_DIR/lib/YUICompressor.(-version-).jar executable.  
Or you may prefer PHP Minifying classes (included).

PHP minifying libraries (Minify_Css_Compressor , JSMin) are taken from Stephen Clay's Minify project - http://code.google.com/p/minify/  

## Facts

|   Total Size 	  | frontend js  | frontend css | backend js   |  backend css |
|:---------------:|-------------:|-------------:|-------------:|-------------:|
| no minification |  359.6 KB	 | 105.9 KB	|   627.1 KB   |   107.6 KB   |
| YUICompressor   |  205.7 KB  	 |  85.9 KB	|   340.9 KB   |    80.4 KB   |
| php classes     |  255.1 KB	 |  86.3 KB 	|   413.5 KB   |    81.2 KB   |

## Behaviour

This extension simply minifies css and javascript content before merged files are saved as in the normal magento behaviour.  
No cache proxy server, or anything complicated : the simple genuine js and css magento browser cache behaviour, but with minified files ;)

## Installation

Install with [modgit](https://github.com/jreinke/modgit):

    $ cd /path/to/magento
    $ modgit init
    $ modgit -e README.md clone magento-minify git://github.com/azurams/Magento-Minify.git

or download package manually:

* Download latest version [here](https://github.com/azurams/Magento-Minify/downloads)
* Unzip in Magento root folder
* Clean cache

Log-out then Log-in in magento backend, go to System > Configuration > Developer > Minification Settings.
Then flush media/js and media/css files... and that's it !

