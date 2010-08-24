<?php
require_once dirname(__FILE__).'/Elo.php';

class RunConfig extends Elo {
	
	public function __construct($xml=null){
		parent::__construct($xml);
		parent::addMetadata('type', 'RunConfig');
	}
	
	public function __set($name, $value) {
		$name = strtolower($name);
		parent::addMetadata($name, $value);
	}

    public function __get($name) {
    	$name = strtolower($name);
		return parent::getMetadata($name);
    }	
}

?>