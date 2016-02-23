<?php

namespace Fenom;

/**
 * Loads template from the filesystem from multiple directories.
 * @package Fenom
 */
class MultiPathsProvider implements ProviderInterface
{

    protected $_paths       = array();
    protected $_clear_cache = array();

    /**
     * Constructor
     *
     * @param string|string[] $path
     */
    public function __construct($path) {
        if(is_array($path)) {
            foreach($path as $dir) {
                $this->addPath($dir);
            }
        } else {
            $this->addPath($path);
        }
    }

    /**
     * Adds a path where templates are stored.
     *
     * @param string $path A path where to look for templates
     *
     * @return $this
     */
    public function addPath($path)
    {
        if ($_dir = realpath($path)) {
            $this->_paths[] = $_dir;
        } else {
            throw new \LogicException("Template directory {$path} doesn't exists");
        }
        return $this;
    }

    /**
     * Prepends a path where templates are stored.
     *
     * @param string $path A path where to look for templates
     *
     * @return $this
     */
    public function prependPath($path)
    {
        if ($_dir = realpath($path)) {
            array_unshift($this->_paths, $_dir);
        } else {
            throw new \LogicException("Template directory {$path} doesn't exists");
        }
        return $this;
    }

    /**
     * Sets the paths where templates are stored.
     *
     * @param string|string[] $paths A path or an array of paths where to look for templates
     *
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->_paths = array();
        if(is_array($paths)) {
            foreach($paths as $path) {
                $this->addPath($path);
            }
        } else {
            $this->addPath($paths);
        }
        return $this;
    }

    /**
     * Returns the paths to the templates.
     *
     * @return array
     */
    public function getPaths() {
        return $this->_paths;
    }

    /**
     * Disable PHP cache for files. PHP cache some operations with files then script works.
     * @see http://php.net/manual/en/function.clearstatcache.php
     *
     * @param bool $status
     *
     * @return $this
     */
    public function setClearCachedStats($status = true) {
        $this->_clear_cache = $status;
        return $this;
    }

    /**
     * Get realpath of template
     *
     * @param string $tpl the template name
     * @return bool|string
     */
    public function getTemplatePath($tpl) {
        foreach($this->_paths as $path) {
            if(($path = realpath($path . "/" . $tpl)) && strpos($path, $path) === 0) {
                return $path;
            }
        }
        return false;
    }

    /**
     * @param string $tpl
     * @return bool
     */
    public function templateExists($tpl)
    {
        return (bool)$this->getTemplatePath($tpl);
    }

    /**
     * Get template path
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _getTemplatePath($tpl)
    {
        if ($path = $this->getTemplatePath($tpl)) {
            return $path;
        }
        throw new \RuntimeException("Template $tpl not found");
    }

    /**
     * @param string $tpl
     * @param int $time
     * @return string
     */
    public function getSource($tpl, &$time)
    {
        $tpl = $this->_getTemplatePath($tpl);
        if($this->_clear_cache) {
            clearstatcache(true, $tpl);
        }
        $time = filemtime($tpl);
        return file_get_contents($tpl);
    }

    /**
     * @param string $tpl
     * @return int
     */
    public function getLastModified($tpl)
    {
        $tpl = $this->_getTemplatePath($tpl);
        if($this->_clear_cache) {
            clearstatcache(true, $tpl);
        }
        return filemtime($tpl);
    }

    /**
     * Verify templates (check mtime)
     *
     * @param array $templates [template_name => modified, ...] By conversation, you may trust the template's name
     * @return bool if true - all templates are valid else some templates are invalid
     */
    public function verify(array $templates)
    {
        foreach ($templates as $template => $mtime) {
            $path = $this->getTemplatePath($template);
            if(!$path) {
                return false;
            }
            if($this->_clear_cache) {
                clearstatcache(true, $path);
            }
            if (@filemtime($path) !== $mtime) {
                return false;
            }

        }
        return true;
    }

    /**
     * Get all names of templates from provider
     * @param string $extension
     * @return array|\Iterator
     */
    public function getList($extension = "tpl")
    {
        $list = array();
        foreach($this->_paths as $path) {
            $list = array_merge($list, $this->_getListFromPath($path, $extension));
        }
        return array_unique($list);
    }

    /**
     * @param string $extension
     * @return array
     */
    protected function _getListFromPath($path, $extension = "tpl")
    {
        $list     = array();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        $path_len = strlen($path);
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() == $extension) {
                $list[] = substr($file->getPathname(), $path_len + 1);
            }
        }
        return $list;
    }
}