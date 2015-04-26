<?php
/**
 * @file
 * Documentation missing.
 */

require_once "phing/Task.php";

/**
 *
 * Class UnserializeTask.
 */
class UnserializeTask extends Task {

    /**
     * @var string
     */
    protected $source = 'file';

    /**
     * @var string
     */
    protected $format = 'json';

    /**
     * @var string
     */
    protected $encoded = '';

    /**
     * @var string
     */
    protected $encodedIsEmpty = TRUE;

    /**
     * @var mixed
     */
    protected $decoded = NULL;

    /**
     * @var string
     */
    protected $property = '';

    /**
     * @var string[]
     */
    protected $validFormats = array(
        'json',
        'yaml',
        'php',
    );

    /**
     * @param string $value
     *   Allowed values are:
     *   - file
     *   - string
     */
    public function setSource($value) {
        $this->source = $value;
    }

    /**
     * @param string $value
     *   Allowed values are:
     *   - json
     *   - yaml
     *   - php
     */
    public function setFormat($value) {
        $this->format = $value;
    }

    /**
     * If the 'source' is 'file' then a path, if the 'source' is 'string' then
     * the raw serialized string.
     *
     * @param string $value
     */
    public function setEncoded($value) {
        $this->encoded = $value;
        $this->encodedIsEmpty = FALSE;
    }

    /**
     * @param string $value
     */
    public function setProperty($value) {
      $this->property = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function main() {
        $this
            ->detectFormat()
            ->decode()
            ->initProperties($this->property, $this->decoded);
    }

    /**
     * @return $this
     *
     * @throws BuildException
     */
    protected function detectFormat() {
        if (!$this->format && $this->source === 'file') {
            $extension = pathinfo($this->decoded, PATHINFO_EXTENSION);
            if ($extension === 'yml') {
              $extension = 'yaml';
            }

            $this->format = $extension;
        }

        if (!in_array($this->format, $this->validFormats)) {
            throw new \BuildException("Invalid format: '{$this->format}'");
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws BuildException
     */
    protected function decode() {
        if ($this->encodedIsEmpty) {
          throw new BuildException('The "encoded" attribute is required');
        }

        $encoded = $this->encodedValue();

        switch ($this->format) {
            case 'json':
                $this->decoded = json_decode($encoded, TRUE);
              break;

            case 'yaml':
                $this->decoded = yaml_parse($encoded);
              break;

            case 'php':
                $this->encoded = unserialize($encoded);
              break;

            default:
                throw new BuildException("Format is invalid: {$this->format}");

        }

        return $this;
    }

    /**
     * @return string
     *
     * @throws BuildException
     */
    protected function encodedValue() {
        if ($this->source === 'file') {
            if (!is_file($this->encoded) || !is_readable($this->encoded)) {
                throw new BuildException("File is not exists or readable: {$this->encoded}");
            }

            return file_get_contents($this->encoded);
        }
        elseif ($this->source === 'string') {
            return $this->encoded;
        }

        throw new BuildException("Invalid source: '{$this->source}'");
    }

    /**
     * @param string $prefix
     * @param mixed $decoded
     */
    protected function initProperties($prefix, $decoded) {
        if (is_array($decoded)) {
            // @todo Numeric indexed array.
            foreach ($decoded as $key => $value) {
                $name = ($prefix ? "$prefix.$key" : $key);
                $this->initProperties($name, $value);
            }
        }
        else {
            $this->getProject()->setProperty("$prefix", $decoded);
        }
    }
}
