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

### Install with [modgit](https://github.com/jreinke/modgit):

```bash
$ cd /path/to/magento
$ modgit init
$ modgit -e README.md clone magento-minify git://github.com/azurams/Magento-Minify.git
```

### Install with modman:
```bash
$ cd /path/to/magento
$ modman init
$ modman clone WBL_Minify git://github.com/azurams/Magento-Minify.git
```

### Download package manually:

* Download latest version [here](https://github.com/azurams/Magento-Minify/archive/master.zip)
* Unzip in Magento root folder
* Clean cache

Log-out then Log-in in magento backend, go to: 

System > Configuration > Developer > JavaScript Settings 
<<<<<<< HEAD

=======
>>>>>>> 1473e32... Updated README documentation. In Magento 1.9.2.2 there is no "Minification Settings".
System > Configuration > Developer > CSS Settings 

Then flush media/js and media/css files... and that's it !

## *NEW* LESS support

[LESS: The dynamic stylesheet language.](http://lesscss.org/)

Ability to compile less files with [lessphp](http://leafo.net/lessphp/) and adds the less.js if
merging is disabled.

Adding a .less file to your scripts is easy:

```xml
<layout>
    <default>
        <reference name="head">
            <action method="addItem"><type>skin_css</type><stylesheet>less/responsive.less</stylesheet></action>
        </reference>
    </default>
</layout>
```

## *NEW* Grouping files functionality

It is advised to disable merging in Magento because the seeming performance benifits aren't as real
as they seem. See the excellent Fishpig article:
[Why You Shouldn't Merge JavaScript in Magento](http://fishpig.co.uk/blog/why-you-shouldnt-merge-javascript-in-magento.html)

### How does it work?

The js and css files are normally combined to one large file, with this you can group them in
relevant groups (the product page gets its own group for example). More examples:

```xml
<layout>
	<!-- we add a group specifically for each locale, when customers are switching a language the whole css doesn't need to be reloaded -->
    <default>
        <reference name="head">
            <action method="addItem"><type>skin_css</type><stylesheet>less/responsive.less</stylesheet><params/><if/><cond/><group>locale</group></action>
            <!-- not the the <params/>, <if/> and the <cond/>, those are required. -->
        </reference>
    </default>

    <!-- on the product page we include the js in a different group (given the same name as the handle) -->
    <catalog_product_view>
        <reference name="head">
            <action method="addJs"><script>varien/product.js</script><params/><group>catalog_product_view</group></action>
            <!-- addCss works the same, addCssIe and addJsIe work the same -->
        </reference>
    </catalog_product_view>
</layout>
```

## License

???
