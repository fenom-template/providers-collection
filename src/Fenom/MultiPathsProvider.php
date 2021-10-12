<?php

namespace Fenom;

use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Loads template from the filesystem, find file in multiple directories.
 
 * @package Fenom
 */
class MultiPathsProvider implements ProviderInterface
{
    /**
     * @var string[]
     */
    protected $paths = [];
    /**
     * @var string
     */
    protected $extension = 'tpl';
    /**
     * @var bool
     */
    protected $clear_cache = false;

    /**
     * Creates new instance.
     *
     * @param string|string[] $paths Path or paths
     * @param string $extension File extension
     */
    public function __construct($paths, $extension = 'tpl')
    {
        $this->setPaths($paths);
        $this->extension = strtolower($extension);
    }

    /**
     * Adds a path where templates are stored.
     *
     * @param string $path A path where to look for templates
     * @param bool $prepend Wherever prepends a path
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addPath($path, $prepend = false)
    {
        $realPath = realpath($path);
        if (! $realPath) {
            throw new InvalidArgumentException("Template directory {$path} doesn't exists");
        } 
        if (! in_array($realPath, $this->paths)) {
            if ($prepend) {
                array_unshift($this->paths, $realPath);
            } else {
                $this->paths[] = $realPath;
            }
        }
        return $this;
    }

    /**
     * Sets the paths where templates are stored.
     *
     * @param string|string[] $paths A path or an array of paths where to look for templates
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = [];
        foreach ((array) $paths as $path) {
            $this->addPath($path);
        }
        return $this;
    }

    /**
     * Returns the paths to the templates.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Disable PHP cache for files. PHP cache some operations with files then script works.
     * @link http://php.net/manual/en/function.clearstatcache.php
     *
     * @param bool $status
     * @return $this
     */
    public function setClearCachedStats($status = true)
    {
        $this->clear_cache = (bool) $status;
        return $this;
    }

    /**
     * Get realpath of template.
     *
     * @param string $tpl the template name
     * @param bool $throw
     * @return string
     * @throws InvalidArgumentException
     */
    public function getTemplatePath($tpl, $throw = true)
    {
        if (strtolower(substr($tpl, -strlen($this->extension))) != $this->extension) {
            $tpl .= '.' . $this->extension;
        }
        foreach ($this->paths as $path) {
            $file = realpath($path . DIRECTORY_SEPARATOR . $tpl);
            if ($file) {
                return $file;
            }
        }
        if ($throw) {
            throw new InvalidArgumentException("Template $tpl not found");
        }
    }

    /**
     * @param string $tpl
     * @return bool
     */
    public function templateExists($tpl)
    {
        return (bool) $this->getTemplatePath($tpl, false);
    }

    /**
     * @param string $tpl
     * @param int $time
     * @return string
     */
    public function getSource($tpl, &$time)
    {
        $path = $this->getTemplatePath($tpl);
        if ($this->clear_cache) {
            clearstatcache(true,  $path);
        }
        $time = filemtime($path);
        return file_get_contents($path);
    }

    /**
     * @param string $tpl
     * @return int
     */
    public function getLastModified($tpl)
    {
        $path = $this->getTemplatePath($tpl);
        if ($this->clear_cache) {
            clearstatcache(true, $path);
        }
        return filemtime($path);
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
            $path = $this->getTemplatePath($template, false);
            if (! $path) {
                return false;
            }
            if ($this->clear_cache) {
                clearstatcache(true, $path);
            }
            if (filemtime($path) != $mtime) {
                return false;
            }

        }
        return true;
    }

    /**
     * Get all names of templates from provider.
     * 
     * @param string|null $extension File extension
     * @return array
     */
    public function getList($extension = null)
    {
        $list = [];
        $extension = $extension ? strtolower($extension) : $this->extension;
        foreach($this->paths as $path) {
            $list = array_merge($list, $this->getListFromPath($path, $extension));
        }
        return array_unique($list);
    }

    /**
     * Returns list of templates.
     *
     * @param string $path Path to templates
     * @param string $extension File extension
     * @return array
     */
    protected function getListFromPath($path, $extension)
    {
        $list = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        $path_length = strlen($path) + 1;
        /* @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) == $extension) {
                $list[] = substr($file->getPathname(), $path_length);
            }
        }
        return $list;
    }
}
