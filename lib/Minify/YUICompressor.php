<?php
/**
 * Class Minify_YUICompressor
 * @package Minify
 */

/**
 * Compress Javascript/CSS using the YUI Compressor
 *
 * You must set $jarFile and $tempDir before calling the minify functions.
 * Also, depending on your shell's environment, you may need to specify
 * the full path to java in $javaExecutable or use putenv() to setup the
 * Java environment.
 *
 * <code>
 * Minify_YUICompressor::$jarFile = '/path/to/yuicompressor-2.3.5.jar';
 * Minify_YUICompressor::$tempDir = '/tmp';
 * $code = Minify_YUICompressor::minifyJs(
 *   $code
 *   ,array('nomunge' => true, 'line-break' => 1000)
 * );
 * </code>
 *
 * @todo unit tests, $options docs
 *
 * @package Minify
 * @author Stephen Clay <steve@mrclay.org>
 */
class Minify_YUICompressor {

    /**
     * Filepath of the YUI Compressor jar file. This must be set before
     * calling minifyJs() or minifyCss().
     *
     * @var string
     */
    public static $jarFile = null;

    /**
     * Writable temp directory. This must be set before calling minifyJs()
     * or minifyCss().
     *
     * @var string
     */
    public static $tempDir = null;

    /**
     * Filepath of "java" executable (may be needed if not in shell's PATH)
     *
     * @var string
     */
    public static $javaExecutable = 'java';

    /**
     * Contains the shell command for yui for debugging purposes
     *
     * @var string
     */
    public static $yuiCommand = '';

    /**
     * Minify a Javascript string
     *
     * @param string $js
     *
     * @param array $options (verbose is ignored)
     *
     * @see http://www.julienlecomte.net/yuicompressor/README
     *
     * @return string
     */
    public static function minifyJs($js, $options = array())
    {
        return self::_minify('js', $js, $options);
    }

    /**
     * Minify a CSS string
     *
     * @param string $css
     *
     * @param array $options (verbose is ignored)
     *
     * @see http://www.julienlecomte.net/yuicompressor/README
     *
     * @return string
     */
    public static function minifyCss($css, $options = array())
    {
        return self::_minify('css', $css, $options);
    }

    /**
     * @param $type
     * @param $content
     * @param $options
     *
     * @return string
     * @throws Exception
     */
    protected static function _minify($type, $content, $options)
    {
        self::_prepare();
        if (! ($tmpFile = tempnam(self::$tempDir, 'yuic_'))) {
            throw new Exception('Minify_YUICompressor : could not create temp file.');
        }
        file_put_contents($tmpFile, $content);
        self::$yuiCommand = self::_getCmd($options, $type, $tmpFile);

        $result_code = 0;
        $output = array();
        exec(self::$yuiCommand, $output, $result_code);
        unlink($tmpFile);
        if ((int)$result_code !== 0) {
            throw new Exception('Minify_YUICompressor : YUI compressor execution failed.');
        }
        return implode("\n", $output);
    }

    /**
     * @param $userOptions
     * @param $type
     * @param $tmpFile
     *
     * @return string
     */
    protected static function _getCmd($userOptions, $type, $tmpFile)
    {
        $o = array_merge(
            array(
                'charset' => ''
                ,'line-break' => 3000
                ,'type' => $type
                ,'nomunge' => false
                ,'preserve-semi' => false
                ,'disable-optimizations' => false
            )
            ,$userOptions
        );
        $cmd = self::$javaExecutable . ' -jar ' . escapeshellarg(self::$jarFile)
             . ' --type ' . $type
             . (preg_match('/^[\\da-zA-Z0-9\\-]+$/', $o['charset'])
                ? " --charset {$o['charset']}"
                : '')
             . (is_numeric($o['line-break']) && $o['line-break'] >= 0
                ? ' --line-break ' . (int)$o['line-break']
                : '');
        if ($type === 'js') {
            foreach (array('nomunge', 'preserve-semi', 'disable-optimizations') as $opt) {
                $cmd .= empty($o[$opt]) === false
                    ? ' --' . $opt
                    : '';
            }
        }
        return $cmd . ' ' . escapeshellarg($tmpFile);
    }

    protected static function _prepare()
    {
        if (! is_link(self::$jarFile)) {
            throw new Exception('Minify_YUICompressor : $jarFile('.self::$jarFile.') is not a valid link.');
        }
        if (! is_dir(self::$tempDir)) {
            throw new Exception('Minify_YUICompressor : $tempDir('.self::$tempDir.') is not a valid direcotry.');
        }
        if (! is_writable(self::$tempDir)) {
            throw new Exception('Minify_YUICompressor : $tempDir('.self::$tempDir.') is not writable.');
        }
    }
}

