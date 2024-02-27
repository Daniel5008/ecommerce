<?php 

namespace hcode;

class Model {

    private $values = []; //array to keep the class data 

    public function __call($name, $args) { //function which is called in every 'call' of the class

        $method = substr($name, 0, 3);
        $fieldName = substr($name, 3, strlen($name));

        switch ($method) 
        {

            case "get":
                return $this->values[$fieldName];
            break;
            case "set":
                $this->values[$fieldName] = $args[0];
            break;
        }
    }

    public function setData($data = array()) 
    {
        foreach ($data as $key => $value) {

            $this->{"set".$key}($value);

        }

    }

    public function getValues () 
    {
        return $this->values;
    }

}


?>