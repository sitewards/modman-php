<?php


class Modman {
	public function run($aParameters) {
		if (!isset($aParameters[1])) {
			throw new Exception('command not found');
		}

		switch ($aParameters[1]) {
			case 'link':
				break;
			case 'init':
				break;
			default:
				throw new Exception('command does not exist');
		}

	}
}

class Modman_Init {

}

class Modman_Link {

}

class Modman_Status {

}

$oModman = new Modman();
$oModman->run($argv);