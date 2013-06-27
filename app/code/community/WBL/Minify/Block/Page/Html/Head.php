<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Page
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Html page block
 *
 * @category   Mage
 * @package    Mage_Page
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class WBL_Minify_Block_Page_Html_Head extends Mage_Page_Block_Html_Head
{

    /**
     * Add CSS file to HEAD entity
     *
     * @param string $name
     * @param string $params
     * @param string $group
     * @return Mage_Page_Block_Html_Head
     */
    public function addCss($name, $params = "", $group='nogroup')
    {
        $this->addItem('skin_css', $name, $params, null, null, $group);
        return $this;
    }


    /**
     * Add JavaScript file to HEAD entity
     *
     * @param string $name
     * @param string $params
     * @param string $group
     * @return Mage_Page_Block_Html_Head
     */
    public function addJs($name, $params = "", $group='nogroup')
    {
        $this->addItem('js', $name, $params, null, null, $group);
        return $this;
    }


    /**
     * Add CSS file for Internet Explorer only to HEAD entity
     *
     * @param string $name
     * @param string $params
     * @param string $group
     * @return Mage_Page_Block_Html_Head
     */
    public function addCssIe($name, $params = "", $group='nogroup')
    {
        $this->addItem('skin_css', $name, $params, 'IE', null, $group);
        return $this;
    }


    /**
     * Add JavaScript file for Internet Explorer only to HEAD entity
     *
     * @param string $name
     * @param string $params
     * @param string $group
     * @return Mage_Page_Block_Html_Head
     */
    public function addJsIe($name, $params = "", $group='nogroup')
    {
        $this->addItem('js', $name, $params, 'IE', null, $group);
        return $this;
    }


    /**
     * Add HEAD Item
     *
     * Allowed types:
     *  - js
     *  - js_css
     *  - skin_js
     *  - skin_css
     *  - rss
     *
     * @param string $type
     * @param string $name
     * @param string $params
     * @param string $if
     * @param string $cond
     * @param string $group
     * @return Mage_Page_Block_Html_Head
     */
    public function addItem($type, $name, $params=null, $if=null, $cond=null, $group='nogroup')
    {
        if (($type==='skin_css' || $type==='skin_less') && empty($params)) {
            $params = 'media="all"';
        }
        $this->_data['items'][$type.'/'.$name] = array(
            'type'   => $type,
            'name'   => $name,
            'params' => $params,
            'if'     => (string) $if,
            'cond'   => (string) $cond,
            'group'  => (string) $group
        );
        return $this;
    }

    /**
     * Remove Item from HEAD entity
     *
     * @param string $type
     * @param string $name
     * @return Mage_Page_Block_Html_Head
     */
    public function removeItem($type, $name)
    {
        unset($this->_data['items'][$type.'/'.$name]);
        return $this;
    }

    /**
     * Classify HTML head item and queue it into "lines" array
     *
     * @see self::getCssJsHtml()
     * @param array &$lines
     * @param string $itemIf
     * @param string $itemType
     * @param string $itemParams
     * @param string $itemName
     * @param array $itemThe
     */
    protected function _separateOtherHtmlHeadElements(&$lines, $itemIf, $itemType, $itemParams, $itemName, $itemThe)
    {
    	$params = $itemParams ? ' ' . $itemParams : '';
    	$href   = $itemName;
    	switch ($itemType) {
    		case 'rss':
    			$lines[$itemThe['group']][$itemIf]['other'][] = sprintf('<link href="%s"%s rel="alternate" type="application/rss+xml" />',
    			$href, $params
    			);
    			break;
    		case 'link_rel':
    			$lines[$itemThe['group']][$itemIf]['other'][] = sprintf('<link%s href="%s" />', $params, $href);
    			break;
    	}
    }

    /**
     * Get HEAD HTML with CSS/JS/RSS definitions
     * (actually it also renders other elements, TODO: fix it up or rename this method)
     *
     * @return string
     */
    public function getCssJsHtml()
    {
        // separate items by types
        $lines  = array();
        foreach ($this->_data['items'] as $item) {
            if (!is_null($item['cond']) && !$this->getData($item['cond']) || !isset($item['name'])) {
                continue;
            }
            $if     = !empty($item['if']) ? $item['if'] : '';
            $params = !empty($item['params']) ? $item['params'] : '';

            switch ($item['type']) {
                case 'js':        // js/*.js
                case 'skin_js':   // skin/*/*.js
                case 'js_css':    // js/*.css
                case 'skin_css':  // skin/*/*.css
                case 'js_less':   // js/*.less
                case 'skin_less': // skin/*/*.less
                    $lines[$item['group']][$if][$item['type']][$params][$item['name']] = $item['name'];
                    break;
                default:
                    $this->_separateOtherHtmlHeadElements($lines, $if, $item['type'], $params, $item['name'], $item);
                    break;
            }
        }

        // prepare HTML
        $shouldMergeJs = Mage::getStoreConfigFlag('dev/js/merge_files');
        $shouldMergeCss = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        $html   = '';
        foreach ($lines as $group => $ifs) {
            $html .= "<!--group: $group-->\n";
            foreach ($ifs as $if => $items) {
                if (empty($items)) {
                    continue;
                }
                if (!empty($if)) {
                    $html .= '<!--[if '.$if.']>'."\n";
                }
                // static and skin css
                $html .= $this->_prepareStaticAndSkinElements('<link rel="stylesheet" type="text/css" href="%s"%s />' . "\n",
                    empty($items['js_css']) ? array() : $items['js_css'],
                    empty($items['skin_css']) ? array() : $items['skin_css'],
                    $shouldMergeCss ? array(Mage::getDesign(), 'getMergedCssUrl') : null
                );

                // static and skin css
                $type = $shouldMergeCss ? 'text/css' : 'text/less';
                $html .= $this->_prepareStaticAndSkinElements('<link rel="stylesheet" type="'.$type.'" href="%s"%s />' . "\n",
                    empty($items['js_less']) ? array() : $items['js_less'],
                    empty($items['skin_less']) ? array() : $items['skin_less'],
                    $shouldMergeCss ? array(Mage::getDesign(), 'getMergedCssUrl') : null
                );

                // static and skin javascripts
                $html .= $this->_prepareStaticAndSkinElements('<script type="text/javascript" src="%s"%s></script>' . "\n",
                    empty($items['js']) ? array() : $items['js'],
                    empty($items['skin_js']) ? array() : $items['skin_js'],
                    $shouldMergeJs ? array(Mage::getDesign(), 'getMergedJsUrl') : null
                );

                // other stuff
                if (!empty($items['other'])) {
                    $html .= $this->_prepareOtherHtmlHeadElements($items['other']) . "\n";
                }

                if (!empty($if)) {
                    $html .= '<![endif]-->'."\n";
                }
            }
        }
        return $html;
    }
}
