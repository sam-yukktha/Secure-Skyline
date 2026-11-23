<?php

/**
 * DataSource interface
 *
 * @author Lukasz Krawczyk <contact@lukaszkrawczyk.eu>
 * @copyright Copyright © 2013 Lukasz Krawczyk
 * @license MIT
 * @link http:/www.lukaszkrawczyk.eu
 */
interface IDataSource {
    public function getNextDocument();
    public function getAllDocuments();
    public function open();
    public function close();
}

/**
 * FileDataSource
 *
 * @author Lukasz Krawczyk <contact@lukaszkrawczyk.eu>
 * @copyright Copyright © 2013 Lukasz Krawczyk
 * @license MIT
 * @link http:/www.lukaszkrawczyk.eu
 */
class FileDataSource implements IDataSource {

    protected $filename;
    protected $file;

    /**
     * Constructor
     *
     * @param string $filename
     * @return FileDataSource
     */
    public function __construct($filename) {
        $this->filename = $filename;
        $this->open();
        return $this;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Opening file
     */
    public function open() {
        if (is_file($this->filename))
            $this->file = fopen($this->filename, 'r');
    }

    /**
     * Closing file
     */
    public function close() {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
        $this->file = null;
    }

    /**
     * Reading next document from file
     *
     * @return string | boolean
     */
    public function getNextDocument() {
        if (!is_resource($this->file)) {
            return false;
        }
        // if it's possible to read from file, remove new line character
        return (($line = fgets($this->file)) !== false)
            ? trim(preg_replace('/\s\s+/', ' ', $line))
            : false;
    }

    /**
     * Reading all documents from file
     *
     * @return array
     * @throws Exception
     */
    public function getAllDocuments() {
        if (!is_resource($this->file)) {
            return array();
        }
        rewind($this->file);
        $documents = array();

        while (($document = $this->getNextDocument()) !== false)
            $documents[] = $document;

        if (!feof($this->file))
            throw new Exception('Error: unexpected file error');
        rewind($this->file);
        return $documents;
    }
}

?>
