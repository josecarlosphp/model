<?php

namespace josecarlosphp\model;

class Node
{
    protected $multiple = false;
    protected $childs = array();
    protected $errors = array();

    public function __construct($multiple=false)
    {
        $this->multiple = $multiple;
    }

    public function Multiple($multiple=null)
    {
        if(!is_null($multiple))
        {
            $this->multiple = $multiple;
        }

        return $this->multiple;
    }

    public function AddNode($key, $multiple=false)
    {
        $this->childs[$key] = new Node($multiple);
    }

    public function AddItem($key, $type=Item::TYPE_STRING, $required=false)
    {
        $this->childs[$key] = new Item($type, $required);
    }

    public function AppendNode($key, $node)
    {
        $this->childs[$key] = $node;
    }

    public function AppendItem($key, $item)
    {
        $this->childs[$key] = $item;
    }

    public function ToArray()
    {
        $array = array();

        foreach($this->childs as $key=>$child)
        {
            switch(get_class($child))
            {
                case 'Node':
                    $array[$key] = $this->multiple ? [$child->ToArray()] : $child->ToArray();
                    break;
                case 'Item':
                    $array[$key] = $child->Type();
                    break;
                default:
                    //TODO: error?
                    break;
            }
        }

        return $array;
    }

    public function ToJson($options=JSON_PRETTY_PRINT)
    {
        return json_encode($this->ToArray(), $options);
    }

    public function Validate($data)
    {
        $this->errors = array();

        if(is_array($data))
        {
            return $this->ValidateArray($data);
        }
        elseif(is_object($data))
        {
            //TODO: return $this->ValidateObject($data);
        }

        $this->errors[] = sprintf('Unexpected data type: %s', gettype($data));

        return false;
    }

    private function ValidateArray($array)
    {
        $ok = true;

        foreach($this->childs as $key=>$child)
        {
            if(is_object($child))
            {
                if(array_key_exists($key, $array))
                {
                    $class = basename(str_replace('\\', '/', get_class($child)));
                    switch($class)
                    {
                        case 'Node':
                            if($child->Multiple())
                            {
                                if(is_array($array[$key]))
                                {
                                    foreach($array[$key] as $i=>$subarray)
                                    {
                                        if(!$child->Validate($subarray))
                                        {
                                            $ok = false;
                                            foreach($child->ErrorStack() as $error)
                                            {
                                                $this->errors[] = sprintf('[\'%s\'][%s] - %s', $key, is_int($i) ? $i : "'{$i}'", $error);
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    $ok = false;
                                    $this->errors[] = sprintf('Expected array for \'%s\'', $key);
                                }
                            }
                            elseif(!$child->Validate($array[$key]))
                            {
                                $ok = false;
                                foreach($child->ErrorStack() as $error)
                                {
                                    $this->errors[] = sprintf('[\'%s\'] - %s', $key, $error);
                                }
                            }
                            break;
                        case 'Item':
                            if($child->Required() && trim($array[$key]) == '')
                            {
                                $ok = false;
                                $this->errors[] = sprintf('Required value for \'%s\'', $key);
                            }
                            else
                            {
                                if(Item::IsType($child->Type()))
                                {
                                    if(!$child->Validate($array[$key]))
                                    {
                                        $ok = false;
                                        foreach($child->ErrorStack() as $error)
                                        {
                                            $this->errors[] = sprintf('[\'%s\'] - %s', $key, $error);
                                        }
                                    }
                                }
                                else
                                {
                                    $ok = false;
                                    $this->errors[] = sprintf('Unexpected type in model: %s for \'%s\'', $child->Type(), $key);
                                }
                            }
                            break;
                        default:
                            $ok = false;
                            $this->errors[] = sprintf('Unexpected class in model: %s for \'%s\'', $class, $key);
                            break;
                    }
                }
                else
                {
                    $ok = false;
                    $this->errors[] = sprintf('Missing \'%s\'', $key);
                }
            }
            else
            {
                $ok = false;
                $this->errors[] = sprintf('Unexpected child in model: %s for \'%s\'', gettype($child), $key);
            }
        }

        return $ok;
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