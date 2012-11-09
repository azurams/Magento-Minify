<?php

class WBL_Minify_Helper_Core_Data extends Mage_Core_Helper_Data
{
    const XML_PATH_MINIFY_ENABLE_YUICOMPRESSOR  = 'dev/minify/enable_yuicompressor';
    const XML_PATH_MINIFY_CSS_FILES             = 'dev/minify/css_files';
    const XML_PATH_MINIFY_JS_FILES              = 'dev/minify/js_files';

    protected $_lessphp = null;


    public function isYUICompressEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MINIFY_ENABLE_YUICOMPRESSOR);
    }

    public function canMinifyJs()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MINIFY_JS_FILES);
    }

    public function canMinifyCss()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MINIFY_CSS_FILES);
    }

    public function minifyJsCss($data,$target)
    {
        if ($this->canMinifyCss() || $this->canMinifyJs()) {

            if ($this->isYUICompressEnabled()) {
                Minify_YUICompressor::$jarFile = Mage::getBaseDir().DS.'bin'.DS.'yuicompressor-2.4.7.jar';
                Minify_YUICompressor::$tempDir = realpath(sys_get_temp_dir());
            }

            switch (pathinfo($target, PATHINFO_EXTENSION)) {
                case 'js':
                    if ($this->canMinifyJs()) {
                        if ($this->isYUICompressEnabled()) {
                            try {
                                $data = Minify_YUICompressor::minifyJs($data);
                                $YUICompressorFailed = false;
                            } catch(Exception $e) {
                                Mage::logException($e);
                                $YUICompressorFailed = true;
                            }
                        }

                        if (!$this->isYUICompressEnabled() || $YUICompressorFailed) {
                            $data = Minify_JSMin::minify($data);
                        }
                    }
                break;

                case 'css':
                    if ($this->canMinifyCss()) {
                        if ($this->isYUICompressEnabled()) {
                            try {
                                $data = Minify_YUICompressor::minifyCss($data);
                                $YUICompressorFailed = false;
                            } catch(Exception $e) {
                                Mage::logException($e);
                                $YUICompressorFailed = true;
                            }
                        }

                        if (!$this->isYUICompressEnabled() || $YUICompressorFailed) {
                            $data = Minify_Css_Compressor::process($data);
                        }
                    }
                break;

                default:
                    return false;
            }
        }
        return $data;
    }

    public function preProcess($data, $file)
    {
        if ($this->canMinifyCss() || $this->canMinifyJs()) {
            switch (pathinfo($file, PATHINFO_EXTENSION))
            {
                case 'less':
                    $data =  $this->_getLessphpModel()->compileFile($file);
                break;

                default:
                    return $data;
            }
        }
        return $data;
    }


    /**
     * @return lessc
     */
    protected function _getLessphpModel()
    {
        if ($this->_lessphp === null)
        {
            require_once Mage::getBaseDir('lib').DS.'lessphp'.DS.'lessc.inc.php';
            $this->_lessphp = new lessc();
        }
        return $this->_lessphp;
    }

    /**
     * 
     * Merge specified files into one
     *
     * By default will not merge, if there is already merged file exists and it
     * was modified after its components
     * If target file is specified, will attempt to write merged contents into it,
     * otherwise will return merged content
     * May apply callback to each file contents. Callback gets parameters:
     * (<existing system filename>, <file contents>)
     * May filter files by specified extension(s)
     * Returns false on error
     *
     * @param array $srcFiles
     * @param string|false $targetFile - file path to be written
     * @param bool $mustMerge
     * @param callback $beforeMergeCallback
     * @param array|string $extensionsFilter
     * @return bool|string
     */
    public function mergeFiles(array $srcFiles, $targetFile = false, $mustMerge = false,
            $beforeMergeCallback = null, $extensionsFilter = array())
    {
        try {
            // check whether merger is required
            $shouldMerge = $mustMerge || !$targetFile;
            if (!$shouldMerge) {
                if (!file_exists($targetFile)) {
                    $shouldMerge = true;
                } else {
                    $targetMtime = filemtime($targetFile);
                    foreach ($srcFiles as $file) {
                        if (!file_exists($file) || @filemtime($file) > $targetMtime) {
                            $shouldMerge = true;
                            break;
                        }
                    }
                }
            }

            // merge contents into the file
            if ($shouldMerge) {
                if ($targetFile && !is_writeable(dirname($targetFile))) {
                    // no translation intentionally
                    throw new Exception(sprintf('Path %s is not writeable.', dirname($targetFile)));
                }

                // filter by extensions
                if ($extensionsFilter) {
                    if ($extensionsFilter == 'css')
                    {
                        $extensionsFilter = array('css','less');
                    }
                    if (!is_array($extensionsFilter)) {
                        $extensionsFilter = array($extensionsFilter);
                    }
                    if (!empty($srcFiles)){
                        foreach ($srcFiles as $key => $file) {
                            $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (!in_array($fileExt, $extensionsFilter)) {
                                unset($srcFiles[$key]);
                            }
                        }
                    }
                }
                if (empty($srcFiles)) {
                    // no translation intentionally
                    throw new Exception('No files to compile.');
                }

                $data = '';
                foreach ($srcFiles as $file) {
                    if (!file_exists($file)) {
                        continue;
                    }
                    $contents = file_get_contents($file) . "\n";
                    $contents = $this->preProcess($contents, $file);
                    if ($beforeMergeCallback && is_callable($beforeMergeCallback)) {
                        $contents = call_user_func($beforeMergeCallback, $file, $contents);
                    }
                    $data .= $contents;
                }
                if (!$data) {
                    // no translation intentionally
                    throw new Exception(sprintf("No content found in files:\n%s", implode("\n", $srcFiles)));
                }
                if ($targetFile) {

                    //only the following line has been added for WBL_Minify
                    $data = $this->minifyJsCss($data, $targetFile);

                    file_put_contents($targetFile, $data, LOCK_EX);
                } else {
                    return $data; // no need to write to file, just return data
                }
            }

            return true; // no need in merger or merged into file successfully
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return false;
    }
}