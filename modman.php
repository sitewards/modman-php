<?php
class Modman {
	public function run($aParameters) {
		try {
			if (!isset($aParameters[1])) {
				// show help if called without parameters
				$this->printHelp();
				exit;
			}

			$bForce = array_search('--force', $aParameters);

			switch ($aParameters[1]) {
				case 'link':
					$sLinkPath = realpath($aParameters[2]);
					if (!$sLinkPath){
						throw new Exception('Link path is invalid!');
					}
					$oLink = new Modman_Command_Link($sLinkPath);
					$oLink->createSymlinks($bForce);
					break;
				case 'init':
					$oInit = new Modman_Command_Init();
					$oInit->doInit();
					break;
				case 'deploy':
					$oDeploy = new Modman_Command_Deploy($aParameters[2]);
					$oDeploy->doDeploy($bForce);
					break;
				case 'repair':
					$bForce = true;
				case 'deploy-all':
					$oDeployAll = new Modman_Command_All('Modman_Command_Deploy');
					$oDeployAll->doDeploy($bForce);
					break;
				case 'clean':
					$oClean = new Modman_Command_Clean();
					$oClean->doClean();
					break;
				case 'remove':
					$oRemove = new Modman_Command_Remove($aParameters[2]);
					$oRemove->doRemove($bForce);
					break;
				default:
					throw new Exception('command does not exist');
			}
		} catch (Exception $oException) {
			echo 'An error occured:' . PHP_EOL;
			echo $oException->getMessage();
		}
	}

	private function printHelp(){
		$sHelp = <<< EOH
PHP-based module manager, originally implemented as bash-script
(for original implementation see https://github.com/colinmollenhour/modman)

Following general commands are currently supported:
- link (with or without --force)
- init
- repair
- deploy (with or without --force)
- deploy-all (with or without --force)
- clean

Currently supported in modman-files:
- symlinks
- @import and @shell command
EOH;

		echo $sHelp;
	}

}

class Modman_Command_All {
	private $sClassName;

	public function __construct($sClassName) {
		$this->sClassName = $sClassName;
	}

	private function getAllModules() {
		$aDirEntries = scandir(Modman_Command_Init::MODMAN_DIRECTORY_NAME);
		unset($aDirEntries[array_search('.', $aDirEntries)]);
		unset($aDirEntries[array_search('..', $aDirEntries)]);
		return $aDirEntries;
	}

	public function __call($sMethodName, $aArguments) {
		foreach ($this->getAllModules() as $sModuleName) {
			$oClass = new $this->sClassName($sModuleName);
			$oClass->$sMethodName(current($aArguments));
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

	public function __construct($sTarget) {
		if (empty($sTarget)) {
			throw new Exception('no source defined');
		}
		$this->sTarget = $sTarget;
	}

	public function createSymlinks($bForce = false) {
		$sModuleName = basename($this->sTarget);
		$sModuleSymlink = Modman_Command_Init::MODMAN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . $sModuleName;
		if (is_link($sModuleSymlink)) {
			throw new Exception($sModuleName . ' is already linked');
		}
		symlink($this->sTarget, $sModuleSymlink);

		$oDeploy = new Modman_Command_Deploy($sModuleName);
		$oDeploy->doDeploy($bForce);
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

	public function getSymlinkBaseDir() {
		return dirname($this->getSymlink());
	}
}

class Modman_Reader {
	private $aFileContent = array();
	private $aObjects = array();
	private $sClassName;
	private $aShells = array();

	public function __construct($sDirectory) {
		$this->aFileContent = file($sDirectory . DIRECTORY_SEPARATOR . 'modman');
	}

	private function getParamsArray($sRow){
		return explode(' ', preg_replace('/\s+/', ' ', $sRow));
	}

	public function getObjectsPerRow($sClassName) {
		$this->sClassName = $sClassName;
		foreach ($this->aFileContent as $sLine) {
			if (substr($sLine, 0, 1) == '#') {
				// skip comments
				continue;
			}
			$aParameters = $this->getParamsArray($sLine);
			if (substr($sLine, 0, 7) == '@import') {
				$this->doImport($aParameters);
				continue;
			} elseif (substr($sLine, 0, 6) == '@shell') {
				unset($aParameters[0]);
				$this->aShells[] = implode(' ', $aParameters);
				continue;
			} elseif (substr($sLine, 0, 1) == '@'){
				echo 'Do not understand: ' . $sLine . PHP_EOL;
				continue;
			}
			$this->aObjects[] = new $sClassName($aParameters);
		}
		return $this->aObjects;
	}

	private function doImport($aCommandParams){
		$sDirectoryName = realpath($aCommandParams[1]);
		if (!$sDirectoryName){
			throw new Exception('The import path could not be parsed!');
		}

		$oModmanReader = new Modman_Reader($sDirectoryName);
		$aObjects = $oModmanReader->getObjectsPerRow($this->sClassName);

		$this->aObjects = array_merge($this->aObjects, $aObjects);
	}

	public function getShells() {
		return $this->aShells;
	}

}

class Modman_Reader_Conflicts {
	private $aConflicts = array();

	public function checkForConflict($sSymlink, $sType, $sTarget = false) {
		if (is_link($sSymlink)) {
			if (
				!(
					$sType == 'link'
					AND
					realpath($sSymlink) == realpath($sTarget)
				)
			) {
				$this->aConflicts[$sSymlink] = 'link';
			}
		} elseif (file_exists($sSymlink)) {
			if (is_dir($sSymlink)) {
				if ($sType == 'dir') {
					return;
				}
				$this->aConflicts[$sSymlink] = 'dir';
			} else {
				$this->aConflicts[$sSymlink] = 'file';
			}
		}
	}

	public function hasConflicts() {
		return (count($this->aConflicts) > 0);
	}

	public function getConflictsString() {
		$sString = '';
		foreach ($this->aConflicts as $sFilename => $sType) {
			switch ($sType) {
				case 'dir':
					$sString .= $sFilename . ' is an existing directory.' . PHP_EOL;
					break;
				case 'file':
					$sString .= $sFilename . ' is an existing file.' . PHP_EOL;
					break;
				case 'link':
					$sString .= $sFilename . ' is an existing link pointing to ' . realpath($sFilename) . '.' . PHP_EOL;
					break;
			}
		}
		return $sString;
	}

	public function cleanup() {
		$oResourceRemover = new Modman_Resource_Remover();
		foreach ($this->aConflicts as $sFilename => $sType) {
			switch ($sType) {
				case 'dir':
					$this->delTree($sFilename);
					break;
				case 'file':
				case 'link':
					$oResourceRemover->doRemoveResource($sFilename);
					break;
			}
		}
	}

	private function delTree($sDirectory) {
		$aFiles = array_diff(scandir($sDirectory), array('.','..'));
		$oResourceRemover = new Modman_Resource_Remover();
		foreach ($aFiles as $sFile) {
			(is_dir("$sDirectory/$sFile"))
				? $this->delTree("$sDirectory/$sFile")
				: $oResourceRemover->doRemoveResource("$sDirectory/$sFile");
		}
		return rmdir($sDirectory);
	}
}

class Modman_Command_Deploy {
	private $sModuleName;

	public function __construct($sModuleName) {
		if (empty($sModuleName)) {
			throw new Exception('please provide a module name to deploy');
		}
		$this->sModuleName = $sModuleName;
	}

	public function doDeploy($bForce = false) {
		$oModmanModuleSymlink = new Modman_Module_Symlink($this->sModuleName);
		$sTarget = $oModmanModuleSymlink->getModmanModuleSymlinkPath();

		$this->oReader = new Modman_Reader($sTarget);
		$aLines = $this->oReader->getObjectsPerRow('Modman_Command_Link_Line');
		$oConflicts = new Modman_Reader_Conflicts();
		foreach ($aLines as $iLine => $oLine) {
			/* @var $oLine Modman_Command_Link_Line */
			if ($oLine->getTarget() AND $oLine->getSymlink()) {
				$sDirectoryName = $oLine->getSymlinkBaseDir();
				if (!is_dir($sDirectoryName)) {
					$oConflicts->checkForConflict($sDirectoryName, 'dir');
				}
				$oConflicts->checkForConflict($oLine->getSymlink(), 'link', $oLine->getTarget());
			} else {
				unset($aLines[$iLine]);
			}
		}
		if ($oConflicts->hasConflicts()) {
			$sConflictsString = 'conflicts detected: ' . PHP_EOL .
				$oConflicts->getConflictsString() . PHP_EOL;
			if ($bForce) {
				echo $sConflictsString;
				echo 'Doing cleanup ... ' . PHP_EOL;
				$oConflicts->cleanup();
			} else {
				throw new Exception($sConflictsString .
					'use --force'
				);
			}
		}
		foreach ($aLines as $oLine) {
			/* @var $oLine Modman_Command_Link_Line */
			$sFullTarget = $sTarget .
				DIRECTORY_SEPARATOR .
				$oLine->getTarget();
			if (!file_exists($sFullTarget)) {
				throw new Exception('can not link to non-existing file ' . $sFullTarget);
			}
			// create directories if path does not exist
			$sDirectoryName = $oLine->getSymlinkBaseDir();
			if (!is_dir($sDirectoryName)) {
				echo 'Create directory ' . $sDirectoryName . PHP_EOL;
				mkdir($sDirectoryName, 0777, true);
			}
			if (!is_link($oLine->getSymlink())) {
				symlink(
					$sFullTarget,
					$oLine->getSymlink()
				);
			}
		}

		foreach ($this->oReader->getShells() as $sShell) {
			$sShell = str_replace('rm -rf', 'deltree', $sShell);
			$sShell = str_replace('$MODULE', $sTarget, $sShell);
			$sShell = str_replace('$PROJECT', getcwd(), $sShell);
			system($sShell);
		}
	}
}


class Modman_Module_Symlink {
	private $sModuleName;

	public function __construct($sModuleName){
		if (empty($sModuleName)) {
			throw new Exception('please provide a module name to deploy');
		}
		$this->sModuleName = $sModuleName;
	}

	public function getModmanModuleSymlink(){
		$sModmanModuleSymlink = Modman_Command_Init::MODMAN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . $this->sModuleName;
		return $sModmanModuleSymlink;
	}

	public function getModmanModuleSymlinkPath(){
		$sModmanModuleSymlink = $this->getModmanModuleSymlink();
		if (!is_link($sModmanModuleSymlink)) {
			throw new Exception($this->sModuleName . ' is not linked');
		}
		$sTarget = realpath($sModmanModuleSymlink);
		return $sTarget;
	}
}

class Modman_Command_Clean {
	private $aDeadSymlinks = array();

	public function doClean() {
		$oResourceRemover = new Modman_Resource_Remover();
		foreach ($this->getDeadSymlinks() as $sSymlink) {
			echo 'Remove ' . $sSymlink . '.' . PHP_EOL;
			$oResourceRemover->doRemoveResource($sSymlink);
		}
	}

	private function getDeadSymlinks($sDirectory = NULL) {
		if (is_null($sDirectory)) {
			$sDirectory = getcwd();
		}
		$this->scanForDeadSymlinks($sDirectory);
		return $this->aDeadSymlinks;
	}

	private function scanForDeadSymlinks($sDirectory) {
		foreach (scandir($sDirectory) as $sFilename) {
			if ($sFilename == '.' OR $sFilename == '..') {
				continue;
			}
			$sFullFilename = $sDirectory . DIRECTORY_SEPARATOR . $sFilename;
			if (is_dir($sFullFilename)) {
				$this->scanForDeadSymlinks($sFullFilename);
			} elseif (is_link($sFullFilename) AND !file_exists(realpath($sFullFilename))) {
				$this->aDeadSymlinks[] = $sFullFilename;
			}
		}
	}
}

class Modman_Command_Remove {

	public function __construct($sModuleName) {
		if (empty($sModuleName)) {
			throw new Exception('please provide a module name to deploy');
		}
		$this->sModuleName = $sModuleName;
	}

	public function doRemove($bForce = false){
		$oModmanModuleSymlink = new Modman_Module_Symlink($this->sModuleName);
		$sTarget = $oModmanModuleSymlink->getModmanModuleSymlinkPath();

		$this->oReader = new Modman_Reader($sTarget);
		$aLines = $this->oReader->getObjectsPerRow('Modman_Command_Link_Line');

		$oResourceRemover = new Modman_Resource_Remover();

		foreach ($aLines as $oLine) {
			$sOriginalPath = $oLine->getTarget();
			$sLinkPath = $oLine->getSymlink();
			$sSymlinkPath = getcwd() . DIRECTORY_SEPARATOR . $sLinkPath;
			if (is_link($sSymlinkPath)
				&& file_exists($sTarget . DIRECTORY_SEPARATOR . $sOriginalPath)){

				if (is_link($sSymlinkPath)){
					$oResourceRemover->doRemoveResource($sSymlinkPath);
				} elseif ($bForce){
					$oResourceRemover->doRemoveResource($sSymlinkPath);
				} else {
					throw new Exception('Problem with removing ' . $sSymlinkPath .
							' - use --force'
					);
				}
			}
		}

		$oResourceRemover->doRemoveResource($oModmanModuleSymlink->getModmanModuleSymlink());

	}
}

class Modman_Resource_Remover{

	public function doRemoveResource($sSymlinkPath){
		if (is_dir($sSymlinkPath)){
			rmdir($sSymlinkPath);
		} else if (is_file($sSymlinkPath)){
			unlink($sSymlinkPath);
		}
	}

}

$oModman = new Modman();
$oModman->run($argv);