<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2012 Ingo Renner <ingo@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * command resolver
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class Tx_Solr_CommandResolver {

	/**
	 * A mapping of command names to command classes to use when executing plugins.
	 *
	 * @var array
	 */
	protected static $commands = array();

	/**
	 * Registers a command and its command class for several plugins
	 *
	 * @param string $plugins comma separated list of plugin names (without pi_ prefix)
	 * @param string $commandName command name
	 * @param string $commandClass name of the class implementing the command
	 * @param integer $requirements Bitmask of which requirements need to be met for a command to be executed
	 */
	public static function registerPluginCommand($plugins, $commandName, $commandClass, $requirements = Tx_Solr_PluginCommand::REQUIREMENT_HAS_SEARCHED) {
		if (!array_key_exists($commandName, self::$commands)) {
			$plugins = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $plugins, TRUE);

			self::$commands[$commandName] = array(
				'plugins'      => $plugins,
				'commandName'  => $commandName,
				'commandClass' => $commandClass,
				'requirements' => $requirements
			);
		}
	}

	/**
	 * Unregisters a command
	 *
	 * This can for example be helpful to override core-modules shipped and automatically loaded with EXT:solr
	 *
	 * @param string $commandName command name
	 * @return boolean Result if command was found (and successfully unregistered)
	 */
	public static function unregisterPluginCommand($commandName) {
		$pluginUnregistered = FALSE;

		if (array_key_exists($commandName, self::$commands)) {
			unset(self::$commands[$commandName]);
			$pluginUnregistered = TRUE;
		}

		return $pluginUnregistered;
	}

	/**
	 * Gets the commands registered for a specific plugin.
	 *
	 * @param string $pluginName Plugin name to get the registered commands for.
	 * @param integer $pluginStatus Bitmask required by commands to be registered for.
	 * @return array An array of plugin command names registered
	 */
	public static function getPluginCommands($pluginName, $pluginStatus = Tx_Solr_PluginCommand::REQUIREMENT_NONE) {
		$commands = array();

		$requiredBits = self::getRequiredBits($pluginStatus);
		foreach (self::$commands as $command) {
			if (!in_array($pluginName, $command['plugins'])) {
				continue;
			}

			if ($command['requirements'] == Tx_Solr_PluginCommand::REQUIREMENT_NONE) {
				$commands[] = $command['commandName'];
				continue;
			}

			foreach ($requiredBits as $requiredBit) {
				$currentBitValue = (1 << $requiredBit);
				$bitMatched = (boolean) ($command['requirements'] & $currentBitValue);

				if (!$bitMatched) {
					continue 2;
				}
			}
			$commands[] = $command['commandName'];
		}

		return $commands;
	}

	/**
	 * Gets all the registered plugin commands' names without checking requirements.
	 *
	 * @return array Array of names of registered plugin command
	 */
	public static function getAllPluginCommandsList() {
		return array_keys(self::$commands);
	}

	/**
	 * Determines which bits are set as a requirement for the plugin commands
	 * to be registered for.
	 *
	 * @param integer $bitmask Bitmask
	 * @return array An array of integers - the bit positions set to 1
	 */
	protected static function getRequiredBits($bitmask) {
		$requiredBits = array();

		for ($i = 0; $i < Tx_Solr_PluginCommand::REQUIREMENTS_NUM_BITS; $i++) {
			if (!(($bitmask & pow(2, $i)) == 0)) {
				$requiredBits[] = $i;
			}
		}

		return $requiredBits;
	}

	/**
	 * Creates an instance of a command class
	 *
	 * @param string $commandName command name
	 * @param object $parent parent object, most likely a plugin object
	 * @return Tx_Solr_PluginCommand the requested command if found, or NULL otherwise
	 * @throws RuntimeException when a command fails to implement interface Tx_Solr_PluginCommand
	 */
	public function getCommand($commandName, $parent) {
		$command = NULL;

		if (array_key_exists($commandName, self::$commands)) {
			$className = self::$commands[$commandName]['commandClass'];
			$command   = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $parent);

			if (!($command instanceof Tx_Solr_PluginCommand)) {
				throw new RuntimeException(
					self::$commands[$commandName]['commandClass'] . ' is not an implementation of Tx_Solr_PluginCommand',
					1297899998
				);
			}
		}

		return $command;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/CommandResolver.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/CommandResolver.php']);
}

?>