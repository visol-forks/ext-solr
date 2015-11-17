<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2012 Ingo Renner <ingo@typo3.org>
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
 * Dispatches the actions requested to the matching frontend helpers.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class Tx_Solr_IndexQueue_FrontendHelper_Dispatcher {

	/**
	 * Frontend helper manager.
	 *
	 * @var	Tx_Solr_IndexQueue_FrontendHelper_Manager
	 */
	protected $frontendHelperManager;

	/**
	 * Constructor for Tx_Solr_IndexQueue_FrontendHelper_Dispatcher
	 */
	public function __construct() {
		$this->frontendHelperManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_IndexQueue_FrontendHelper_Manager');
	}

	/**
	 * Takes the request's actions and hands them of to the according frontend
	 * helpers.
	 *
	 * @param	Tx_Solr_IndexQueue_PageIndexerRequest	$request The request to dispatch
	 * @param	Tx_Solr_IndexQueue_PageIndexerResponse	$response The request's response
	 */
	public function dispatch(Tx_Solr_IndexQueue_PageIndexerRequest $request, Tx_Solr_IndexQueue_PageIndexerResponse $response) {
		$actions = $request->getActions();

		foreach ($actions as $action) {
			$frontendHelper = $this->frontendHelperManager->resolveAction($action);
			$frontendHelper->activate();
			$frontendHelper->processRequest($request, $response);
		}
	}

	/**
	 * Sends a shutdown signal to all activated frontend helpers.
	 *
	 * @return	void
	 */
	public function shutdown() {
		$frontendHelpers = $this->frontendHelperManager->getActivatedFrontendHelpers();

		foreach ($frontendHelpers as $frontendHelper) {
			$frontendHelper->deactivate();
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/IndexQueue/FrontendHelper/Dispatcher.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/IndexQueue/FrontendHelper/Dispatcher.php']);
}

?>