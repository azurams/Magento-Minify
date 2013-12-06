<?php

class WBL_Minify_Helper_Core_Data extends Mage_Core_Helper_Data
{
    const XML_PATH_MINIFY_ENABLE_YUICOMPRESSOR  = 'dev/js/enable_yuicompressor';

    protected $_lessphp = null;

    /**
     * @return bool
     */
    public function isYUICompressEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MINIFY_ENABLE_YUICOMPRESSOR);
    }


    /**
     * @param string $data
     * @param string $target
     *
     * @return string
     */
    public function minifyJsCss($data,$target)
    {

        if ($this->isYUICompressEnabled()) {
            Minify_YUICompressor::$jarFile = Mage::getBaseDir().DS.'lib'.DS.'yuicompressor'.DS.'yuicompressor.jar';
            Minify_YUICompressor::$tempDir = realpath(sys_get_temp_dir());
        }
        $YUICompressorFailed = false;
        switch (pathinfo($target, PATHINFO_EXTENSION)) {
            case 'js':
                if ($this->isYUICompressEnabled()) {
                    try {
                        Varien_Profiler::start('Minify_YUICompressor::minifyJs');
                        $data = Minify_YUICompressor::minifyJs($data);
                        Varien_Profiler::stop('Minify_YUICompressor::minifyJs');
                        $YUICompressorFailed = false;
                    } catch(Exception $e) {
                        Mage::logException($e);
                        $YUICompressorFailed = true;
                    }
                    Mage::log(Minify_YUICompressor::$yuiCommand);
                }

                if (!$this->isYUICompressEnabled() || $YUICompressorFailed === true) {
                    Varien_Profiler::start('Minify_JSMin::minify');
                    $data = Minify_JSMin::minify($data);
                    Varien_Profiler::stop('Minify_JSMin::minify');
                }
            break;

            case 'css':
                if ($this->isYUICompressEnabled()) {
                    try {
                        Varien_Profiler::start('Minify_YUICompressor::minifyCss');
                        $data = Minify_YUICompressor::minifyCss($data);
                        Varien_Profiler::stop('Minify_YUICompressor::minifyCss');
                        $YUICompressorFailed = false;
                    } catch(Exception $e) {
                        Mage::logException($e);
                        $YUICompressorFailed = true;
                    }
                    Mage::log(Minify_YUICompressor::$yuiCommand);
                }

                if (!$this->isYUICompressEnabled() || $YUICompressorFailed === true) {
                    Varien_Profiler::start('Minify_Css_Compressor::process');
                    $data = Minify_Css_Compressor::process($data);
                    Varien_Profiler::stop('Minify_Css_Compressor::process');
                }
            break;

            default:
                return false;
        }

        return $data;
    }


    /**
     * PreCompile the files (less files for example) to CSS so the default
     * minifier can handle the files. The file paths aren't expanded yet.
     *
     * @param string $data
     * @param string $file
     *
     * @return string
     */
    public function preProcess($data, $file)
    {
        switch (pathinfo($file, PATHINFO_EXTENSION))
        {
            case 'less':
                Varien_Profiler::start('lessc::compileFile');
                $data = $this->_getLessphpModel()->compileFile($file);
                Varien_Profiler::stop('lessc::compileFile');
                return $data;
            break;

            default:
                return $data;
        }
    }


    /**
     * Get the less compiler
     *
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
     * @param array        $srcFiles
     * @param string|bool  $targetFile - file path to be written
     * @param bool         $mustMerge
     * @param callback     $beforeMergeCallback
     * @param array|string $extensionsFilter
     *
     * @throws Exception
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
                        if (!is_file($file)) {
                            throw new Exception(sprintf('File %s is not a file, probably the file doesn\'t exist.', $file));
                        }

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
