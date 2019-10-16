<?php

namespace josecarlosphp\model;

class Item
{
    const TYPE_STRING = 'string';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';

    private $type;
    private $required;
    protected $errors = array();

    public function __construct($type=self::TYPE_STRING, $required=false)
    {
        $this->type = $type;
        $this->required = $required;
    }

    public function Type($type=null)
    {
        if(!is_null($type))
        {
            $this->type = $type;
        }

        return $this->type;
    }

    public function Required($required=null)
    {
        if(!is_null($required))
        {
            $this->required = $required;
        }

        return $this->required;
    }

    public function Validate($value)
    {
        $this->errors = array();

        $ok = true;

        if(is_object($value) || is_array($value))
        {
            return false;
        }

        switch($this->type)
        {
            case Item::TYPE_STRING:
                if($value != strval($value))
                {
                    $ok = false;
                }
                break;
            case Item::TYPE_DATE:
                $format = 'Y-m-d';
                $d = DateTime::createFromFormat($format, $value);
                if(!$d || $d->format($format) != $value)
                {
                    $ok = false;
                }
                break;
            case Item::TYPE_DATETIME:
                $value = str_replace('T', ' ', $value);
                $format = 'Y-m-d H:i:s';
                $d = DateTime::createFromFormat($format, $value);
                if(!$d || $d->format($format) != $value)
                {
                    $ok = false;
                }
                break;
            case Item::TYPE_INTEGER:
                if(!is_numeric($value) || $value != intval($value))
                {
                    $ok = false;
                }
                break;
            case Item::TYPE_DECIMAL:
                if(!is_numeric($value))
                {
                    $ok = false;
                }
                break;
        }

        if(!$ok)
        {
            $this->errors[] = sprintf('Invalid %s', $this->type);
        }

        return $ok;
    }

    public static function IsType($type)
    {
        foreach(self::getConstants() as $key=>$val)
        {
            if($val == $type && mb_substr($key, 0, 5) == 'TYPE_')
            {
                return true;
            }
        }

        return false;
    }

    public static function getConstants()
    {
        $reflectionClass = new \ReflectionClass(static::class);
        return $reflectionClass->getConstants();
    }

    public function Error()
    {
        return empty($this->errors) ? null : $this->errors[count($this->errors) - 1];
    }

    public function ErrorStack()
    {
        return $this->errors;
    }
}
