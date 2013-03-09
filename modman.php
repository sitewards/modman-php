<?php


class Modman {
	public function run($aParameters) {
		if (!isset($aParameters[1])) {
			throw new Exception('command not found');
		}

		switch ($aParameters[1]) {
			case 'link':
				$oLink = new Modman_Command_Link($aParameters[2]);
				$oLink->createSymlinks();

				break;
			case 'init':
				break;
			default:
				throw new Exception('command does not exist');
		}

	}
}

class Modman_Command_Init {

}

class Modman_Command_Link {
	public function __construct($sSourceDirectory) {
		if (empty($sSourceDirectory)) {
			throw new Exception('no source defined');
		}
		$oReader = new Modman_Reader($sSourceDirectory);
		foreach ($oReader->getObjectsPerRow('Modman_Command_Link_Line') as $oLine) {
			
		}

	}
}

class Modman_Command_Link_Line {
	private $sSourceDirectory, $sTargetDirectory;

	public function __construct($aDirectories) {
		$this->sSourceDirectory = $aDirectories[0];
		$this->sTargetDirectory = $aDirectories[1];
	}

	public function getSourceDirectory() {
		return $this->sSourceDirectory;
	}

	public function getTargetDirectory() {
		return $this->sTargetDirectory;
	}
}

class Modman_Reader {
	private $aFileContent = array();

	public function __construct($sDirectory) {
		$this->aFileContent = file($sDirectory . PATH_SEPARATOR . 'modman');
	}

	public function getObjectsPerRow($sClassName) {
		$aObjects = array();
		foreach ($this->aFileContent as $sLine) {
			$aObjects[] = new $sClassName(explode(' ', $sLine));
		}
		return $aObjects;
	}
}

class Modman_Command_Status {

}

$oModman = new Modman();
$oModman->run($argv);