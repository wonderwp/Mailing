<?php

namespace WonderWp\Component\Mailing\Exception;

use Exception;
use JsonSerializable;
use Throwable;

abstract class AbstractEmailException extends Exception implements JsonSerializable
{
    /** @var array */
    protected $details;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $details
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, array $details = [])
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /** @inheritdoc */
    public function jsonSerialize()
    {
        $vars            = get_object_vars($this);
        $unnecessaryArgs = ['file', 'line', 'xdebug_message'];
        foreach ($unnecessaryArgs as $arg) {
            if (isset($vars[$arg])) {
                unset($vars[$arg]);
            }
        }
        $frags        = explode('\\', get_called_class());
        $vars['type'] = 'Mailing/' . end($frags);

        return $vars;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return json_decode(json_encode($this), true);
    }
}
