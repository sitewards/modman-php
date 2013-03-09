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
	private $sTarget;
	private $oReader;

	public function __construct($sTarget) {
		if (empty($sTarget)) {
			throw new Exception('no source defined');
		}
		$this->sTarget = $sTarget;
		$this->oReader = new Modman_Reader($sTarget);
	}

	public function createSymlinks() {
		foreach ($this->oReader->getObjectsPerRow('Modman_Command_Link_Line') as $oLine) {
			/* @var $oLine Modman_Command_Link_Line */
			if ($oLine->getTarget() AND $oLine->getSymlink()) {
				// create directories if path does not exist
				$sDirectoryName = dirname($oLine->getSymlink());
				if (!is_dir($sDirectoryName)) {
					$this->removeConflicts($sDirectoryName);
					echo 'Create directory ' . $sDirectoryName . PHP_EOL;
					mkdir($sDirectoryName);
				}
				$this->removeConflicts($oLine->getSymlink());
				symlink(
					$this->sTarget .
						DIRECTORY_SEPARATOR .
						$oLine->getTarget(),
					$oLine->getSymlink()
				);
			}
		}
	}

	private function removeConflicts($sFileToClean) {
		if (file_exists($sFileToClean)) {
			if (is_dir($sFileToClean)) {
				echo 'Remove conflicted directory ' . $sFileToClean . PHP_EOL;
				$this->delTree($sFileToClean);
			} else {
				echo 'Remove conflicted file ' . $sFileToClean . PHP_EOL;
				unlink($sFileToClean);
			}
		} elseif (is_link($sFileToClean)) {
			echo 'Remove conflicted symlink ' . $sFileToClean . PHP_EOL;
			unlink($sFileToClean);
		}
	}

	private function delTree($sDirectory) {
		$aFiles = array_diff(scandir($sDirectory), array('.','..'));
		foreach ($aFiles as $sFile) {
			(is_dir("$sDirectory/$sFile")) ? $this->delTree("$sDirectory/$sFile") : unlink("$sDirectory/$sFile");
		}
		return rmdir($sDirectory);
	}
}

class Modman_Command_Link_Line {
	private $sTarget, $sSymlink;

	public function __construct($aDirectories) {
		$this->sTarget = $aDirectories[0];
		if (empty($aDirectories[1])) {
			$this->sSymlink = $this->sTarget;
		} else {
			$this->sSymlink = $aDirectories[1];
		}
	}

	public function getTarget() {
		return $this->sTarget;
	}

	public function getSymlink() {
		return $this->sSymlink;
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