<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Ingo Renner <ingo@typo3.org>
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
 * Search components manager, registration and stuff...
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class Tx_Solr_Search_SearchComponentManager {

	/**
	 * Search component registry.
	 *
	 * @var array
	 */
	protected static $searchComponents = array();


	/**
	 * Registers a search component.
	 *
	 * @param string $componentName Search component name
	 * @param string $componentClassName Component class
	 */
	public static function registerSearchComponent($componentName, $componentClassName) {
		self::$searchComponents[$componentName] = $componentClassName;
	}

	/**
	 * Returns all currently registered search components.
	 *
	 * @return array An array of search component instances
	 */
	public function getSearchComponents() {
		$searchComponents = array();

		foreach (self::$searchComponents as $componentName => $componentClass) {
			$searchComponents[$componentName] = $this->getSearchComponent($componentName);
		}

		return $searchComponents;
	}

	/**
	 * Instanciates a registered search component
	 *
	 * @param string $componentName Search component name
	 * @return Tx_Solr_SearchComponent Instance of the requested search component
	 * @throws InvalidArgumentException if $componentName is not a registered search component
	 * @throws RuntimeException if the class registered for $componentName is not an implementation of Tx_Solr_SearchComponent
	 */
	public function getSearchComponent($componentName) {
		if (!array_key_exists($componentName, self::$searchComponents)) {
			throw new InvalidArgumentException(
				'No search component registered named ' . $componentName,
				1343398440
			);
		}

		$searchComponent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(self::$searchComponents[$componentName]);

		if (!($searchComponent instanceof Tx_Solr_SearchComponent)) {
			throw new RuntimeException(
				'Class ' . self::$searchComponents[$componentName] . ' must implement interface Tx_Solr_SearchComponent.',
				1343398621
			);
		}

		return $searchComponent;
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/Search/SearchComponentManager.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/Search/SearchComponentManager.php']);
}

?>