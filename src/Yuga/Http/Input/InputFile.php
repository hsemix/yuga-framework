<?php
namespace Yuga\Http\Input;

use Yuga\Interfaces\Http\Input\IInputItem;

class InputFile implements IInputItem
{
    public $index;
    public $name;
    public $filename;
    public $size;
    public $type;
    public $error;
    public $tmpName;

    public function __construct($index)
    {
        $this->index = $index;

        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', $this->index));
    }

    /**
     * Create from array
     *
     * @param array $values
     * @throws \InvalidArgumentException
     * @return static
     */
    public static function createFromArray(array $values)
    {
        if (!isset($values['index'])) {
            throw new \InvalidArgumentException('Index key is required');
        }

        /* Easy way of ensuring that all indexes-are set and not filling the screen with isset() */

        $values = array_merge([
            'tmp_name' => null,
            'type'     => null,
            'size'     => null,
            'name'     => null,
            'error'    => null,
        ], $values);

        return (new static($values['index']))
            ->setSize($values['size'])
            ->setError($values['error'])
            ->setType($values['type'])
            ->setTmpName($values['tmp_name'])
            ->setFilename($values['name']);

    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set input index
     * @param string $index
     * @return static $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set file size
     * @param int $size
     * @return static $this
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get mime-type of file
     * @return string
     */
    public function getMime()
    {
        return $this->getType();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     * @param string $type
     * @return static $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns extension without "."
     *
     * @return string
     */
    public function getExtension()
    {
        $file = explode('.', basename($this->getFilename()));
        return end($file);
        //return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * Get human friendly name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set human friendly name.
     * Useful for adding validation etc.
     *
     * @param string $name
     * @return static $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set filename
     *
     * @param string $name
     * @return static $this
     */
    public function setFilename($name)
    {
        $this->filename = $name;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Move the uploaded temporary file to it's new home
     *
     * @param string $destination
     * @return bool
     */
    public function move($destination, $newFilename)
    {
        return move_uploaded_file($this->tmpName, $destination . '/' .$newFilename);
    }

    /**
     * Get file contents
     *
     * @return string
     */
    public function getContents()
    {
        return file_get_contents($this->tmpName);
    }

    /**
     * Return true if an upload error occurred.
     *
     * @return bool
     */
    public function hasError()
    {
        return ($this->getError() !== 0);
    }

    /**
     * Get upload-error code.
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set error
     *
     * @param int $error
     * @return static $this
     */
    public function setError($error)
    {
        $this->error = (int)$error;

        return $this;
    }

    /**
     * @return string
     */
    public function getTmpName()
    {
        return $this->tmpName;
    }

    /**
     * Set file temp. name
     * @param string $name
     * @return static $this
     */
    public function setTmpName($name)
    {
        $this->tmpName = $name;

        return $this;
    }

    public function __toString()
    {
        return $this->getTmpName();
    }

    public function getValue()
    {
        return $this->getFilename();
    }

    public function toArray()
    {
        return [
            'tmp_name' => $this->tmpName,
            'type'     => $this->type,
            'size'     => $this->size,
            'name'     => $this->filename,
            'error'    => $this->error,
        ];
    }

}