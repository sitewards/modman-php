<?php


class Modman {
	public function run($aParameters) {
		if (!isset($aParameters[1])) {
			throw new Exception('command not found');
		}

		switch ($aParameters[1]) {
			case 'link':
				$oLink = new Modman_Command_Link(getcwd() . DIRECTORY_SEPARATOR . $aParameters[2]);
				$oLink->createSymlinks();
				break;
			case 'init':
				$oInit = new Modman_Command_Init();
				$oInit->doInit();
				break;
			default:
				throw new Exception('command does not exist');
		}

	}
}

class Modman_Command_Init {

	// directory name
	const MODMAN_DIRECTORY_NAME = '.modman';

	/**
	 * Creates directory ".modman" if it doesn't exist
	 */
	public function doInit(){
		$sCurrentDirectory = getcwd();
		$sModmanDirectory = $sCurrentDirectory . DIRECTORY_SEPARATOR . self::MODMAN_DIRECTORY_NAME;
		if (!is_dir($sModmanDirectory)){
			mkdir($sModmanDirectory);
		}
	}
}

class Modman_Command_Link {
	private $sSourceDirectory;
	private $oReader;

	public function __construct($sSourceDirectory) {
		if (empty($sSourceDirectory)) {
			throw new Exception('no source defined');
		}
		$this->sSourceDirectory = $sSourceDirectory;
		$this->oReader = new Modman_Reader($sSourceDirectory);
	}

	public function createSymlinks() {
		foreach ($this->oReader->getObjectsPerRow('Modman_Command_Link_Line') as $oLine) {
			/* @var $oLine Modman_Command_Link_Line */
			if ($oLine->getSourceDirectory() AND $oLine->getTargetDirectory()) {
				// create directories if path does not exist
				if (!is_dir(dirname($oLine->getTargetDirectory()))) {
					mkdir(dirname($oLine->getTargetDirectory()));
				}
				// TODO check if link already exists, send warning, when changing links, removing empty files
				symlink(
					$this->sSourceDirectory .
						DIRECTORY_SEPARATOR .
						$oLine->getSourceDirectory(),
					$oLine->getTargetDirectory()
				);
			}
		}

	}
}

class Modman_Command_Link_Line {
	private $sSourceDirectory, $sTargetDirectory;

	public function __construct($aDirectories) {
		$this->sSourceDirectory = $aDirectories[0];
		if (empty($aDirectories[1])) {
			$this->sTargetDirectory = $this->sSourceDirectory;
		} else {
			$this->sTargetDirectory = $aDirectories[1];
		}
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
		$this->aFileContent = file($sDirectory . DIRECTORY_SEPARATOR . 'modman');
	}

	public function getObjectsPerRow($sClassName) {
		$aObjects = array();
		foreach ($this->aFileContent as $sLine) {
			if (substr($sLine, 0, 1) == '#') {
				// skip comments
				continue;
			}
			if (substr($sLine, 0, 1) == '@') {
				// skip commmmands for now
				continue;
			}
			$aParameters = explode(' ', preg_replace('/\s+/', ' ', $sLine));
			$aObjects[] = new $sClassName($aParameters);
		}
		return $aObjects;
	}
}

class Modman_Command_Status {

}

$oModman = new Modman();
$oModman->run($argv);