<?php

/**
 * The Picovico way of handling Execptions
 *
 * @author acpmasquerade <acpmasquerade@picovico.com>
 */
class PicovicoException extends Exception {

    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;
    protected $type; 

    public function __construct($result) {

        $this->type = $result;
        
        if(is_string($result)){
            parent::__construct($result);
        }elseif(is_numeric ($result)){
            parent::__construct(NULL, $result);
        }else{
            // do something else            
            $result = $this->to_array($result);
            if(isset($result["type"]) AND $result["type"] ){
                $this->type = $result["type"];
            }else{
                $this->type = "PicovicoException";
            }
            parent::__construct($this->type);
        }

        $this->result = $result;
    }

    private function to_array($var){
        if(is_array($var)){
            return $var;
        }elseif(is_object($var)){
            $return = array();
            foreach($var as $key=>$val){
                $return[$key] = $val;
            }
            return $return;
        }else{
            return array($var);
        }
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @return array The result from the API server
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Returns the associated type for the error. This will default to
     * 'UFO_Exception' when a type is not available.
     *
     * @return string
     */
    public function getType() {
        if(!isset($this->type) OR !($this->type)){
            return 'UFO_Exception';
        }else{
            return $this->type;
        }
    }

    /**
     * @return string The string representation of the error
     */
    public function __toString() {
        return "".$this->type ."\n" . json_encode($this->result)."\n-\n";
    }

}
