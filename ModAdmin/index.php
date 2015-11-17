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

$GLOBALS['LANG']->includeLLFile('EXT:solr/ModAdmin/locallang.xml');
$BE_USER->modAccess($MCONF, 1);


/**
 * Module 'Solr Search' for the 'solr' extension.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class  Tx_Solr_ModuleAdmin extends \TYPO3\CMS\Backend\Module\BaseScriptClass {
	var $pageinfo;

	/**
	 * @var Tx_Solr_Site
	 */
	protected $site = NULL;

	/**
	 * @var Tx_Solr_ConnectionManager
	 */
	protected $connectionManager = NULL;

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	public function init() {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

			// initialize doc
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
		$this->doc->setModuleTemplate(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('solr') . 'ModAdmin/mod_admin.html');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->bodyTagId = 'typo3-mod-php';
		$this->doc->bodyTagAdditions = 'class="tx_solr_mod-admin"';
	}

	/**
	 * Builds the drop down menu to select the solr instance we want to
	 * administer.
	 *
	 * @return	void
	 */
	public function menuConfig() {
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$sites    = Tx_Solr_Site::getAvailableSites();

			// TODO add a menu entry on top to manage all indexes, otherwise when selecting a specific index actions will only affect that specific one

		foreach ($sites as $key => $site) {
			$this->MOD_MENU['function'][$site->getRootPageId()] = $site->getLabel();
		}

		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	public function main() {
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

			// Access check!
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		$rootPageId = $this->MOD_SETTINGS['function'];

		if ($rootPageId) {
			$this->site              = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Site', $rootPageId);
			$this->connectionManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_ConnectionManager');
		}

		$docHeaderButtons = $this->getButtons();

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id)) {

				// Draw the form
			$this->doc->form = '<form action="" method="post" name="editform" enctype="multipart/form-data">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$this->doc->getPageRenderer()->addCssFile('../typo3conf/ext/solr/Resources/Css/ModAdmin/index.css');
			$this->doc->getPageRenderer()->addCssFile('../typo3conf/ext/solr/Resources/Css/Backend/indexingconfigurationselectorfield.css');

				// Render content:
			if ($this->site) {
				$this->getModuleContent();
			} else {
				$this->getModuleContentNoSiteConfigured();
			}

		} else {
				// If no access or if ID == zero
			$docHeaderButtons['save'] = '';
			$this->content .= $this->doc->spacer(10);
		}

			// compile document
		$markers['FUNC_MENU'] = $this->getFunctionMenu();
		$markers['CONTENT'] = $this->content;

				// Build the <body> for the module
		$this->content  = $this->doc->startPage($LANG->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content  = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return void
	 */
	protected function getModuleContent() {
			//// TEMPORARY
			// TODO add a "discover/update Solr connections button to the global section"

		$content = '
			<input type="hidden" id="solraction" name="solraction" value="" />
			';

		$content .= '<fieldset><legend>Site Actions</legend>';
		$content .= $this->renderIndexQueueInitializationSelector();
		$content .= '
				<input type="submit" value="Initialize Index Queue" name="s_initializeIndexQueue" onclick="document.forms[0].solraction.value=\'initializeIndexQueue\';" /> ';
		$content .= \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('', '', '', array(
			'title'       => 'Index Queue Initialization',
			'description' => 'Initializing the Index Queue is the most complete way to force reindexing, or to build the Index Queue for
							 the first time. The Index Queue Worker scheduler task will then index the items listed in the Index Queue.
							 Initializing the Index Queue without selecting specific indexing configurations will behave like selecting all.'
		));

		$content .= '
				<br /><br /><hr /><br />
				<input type="submit" value="Clean up Site Index" name="s_cleanupSiteCores" onclick="document.forms[0].solraction.value=\'cleanupSiteCores\';" />';

		$content .= '
				<br /><br />
				<input type="submit" value="Empty Site Index" name="s_deleteSiteDocuments" onclick="Check = confirm(\'This will commit documents which may be pending, delete documents belonging to the currently selected site and commit again afterwards. Are you sure you want to delete the site\\\'s documents?\'); if (Check == true) document.forms[0].solraction.value=\'deleteSiteDocuments\';" />
			';

		$content .= '
				<br /><br />
				<input type="submit" value="Reload Index Configuration" name="s_reloadCore" onclick="document.forms[0].solraction.value=\'reloadSiteCores\';" />';


		$content .= '
			<br /><br /><hr /><br />
			<p>
				Delete document(s) from site index<br /><br />
			</p>
			<label for="delete_uid" style="display:block;width:60px;float:left">Item uid</label>
			<input id="delete_uid" type="text" name="delete_uid" value="" /> (also accepts comma separated lists of uids)<br /><br />
			<label for="delete_type" style="display:block;width:60px;float:left;">Item type</label>
			<input id="delete_type" type="text" name="delete_type" value="" /> (table name)<br /><br />
			<input type="submit" value="Delete Document(s)"name="s_deleteDocument" onclick="document.forms[0].solraction.value=\'deleteDocument\';" /><br /><br />
			';

		$content .= '</fieldset>';

		$content .= '
			<fieldset>
				<legend>Global Actions (affecting all sites and indexes)</legend>
				<input type="submit" value="Empty Index" name="s_emptyIndex" onclick="Check = confirm(\'This will commit documents which may be pending, clear the index and commit again afterwards. Are you sure you want to empty the index?\'); if (Check == true) document.forms[0].solraction.value=\'emptyIndex\';" /><br /><br />
			</fieldset>';

		$content .= '
			<hr class="double" />
			API Key: ' . Tx_Solr_Api::getApiKey();
			// TODO add a checkbox to the delete documents fields to also remove from Index Queue

		switch($_POST['solraction']) {
			case 'initializeIndexQueue':
				$this->initializeIndexQueue();
				break;
			case 'cleanupSiteCores':
				$this->cleanupSiteIndex();
				break;
			case 'deleteSiteDocuments':
				$this->deleteSiteDocuments();
				break;
			case 'reloadSiteCores':
				$this->reloadSiteCores();
				break;
			case 'emptyIndex':
				$this->emptyIndex();
				break;
			case 'deleteDocument':
				$this->deleteDocument();
				break;
			default:
		}

		$this->content .= $this->doc->section('Apache Solr for TYPO3', $content, FALSE, TRUE);
	}

	/**
	 * Renders a field to select which indexing configurations to initialize.
	 *
	 * Uses TCEforms.
	 *
	 *  @return string Markup for the select field
	 */
	protected function renderIndexQueueInitializationSelector() {
		$selector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_backend_IndexingConfigurationSelectorField', $this->site);
		$selector->setFormElementName('tx_solr-index-queue-initialization');

		return $selector->render();
	}

	protected function getModuleContentNoSiteConfigured() {
		# TODO add button to init Solr connections
		$this->content = 'No sites configured for Solr yet.';
	}

	protected function getFunctionMenu() {
		$functionMenu = 'No sites configured for Solr yet.';

		if ($this->site) {
			$functionMenu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
				0,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			);
		}

		return $functionMenu;
	}

	//// TEMPORARY

	protected function initializeIndexQueue() {
		$initializedIndexingConfigurations = array();

		$itemIndexQueue = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_IndexQueue_Queue');

		$indexingConfigurationsToInitialize = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('tx_solr-index-queue-initialization');
		if (!empty($indexingConfigurationsToInitialize)) {
				// initialize selected indexing configuration only
			foreach ($indexingConfigurationsToInitialize as $indexingConfigurationName) {
				$initializedIndexingConfiguration = $itemIndexQueue->initialize(
					$this->site,
					$indexingConfigurationName
				);

					// track initialized indexing configurations for the flash message
				$initializedIndexingConfigurations = array_merge(
					$initializedIndexingConfigurations,
					$initializedIndexingConfiguration
				);
			}
		} else {
				// nothing selected specifically, initialize the complete queue
			$initializedIndexingConfigurations = $itemIndexQueue->initialize($this->site);
		}

			// TODO make status dependent on return value of IQ init
		$messagesForConfigurations = array();
		foreach (array_keys($initializedIndexingConfigurations) as $indexingConfigurationName) {
			$itemCount = $itemIndexQueue->getItemsCountBySite($this->site, $indexingConfigurationName);
			if (!is_int($itemCount)) {
				$itemCount = 0;
			}
			$messagesForConfigurations[] = $indexingConfigurationName . ' (' . $itemCount . ' records)';
		}

		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			'Initialized indexing configurations: ' . implode(', ', $messagesForConfigurations),
			'Index Queue initialized',
			\TYPO3\CMS\Core\Messaging\FlashMessage::OK
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	protected function emptyIndex() {
		$message = 'Index emptied.';
		$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;

		try {
			$solrServers = $this->connectionManager->getConnectionsBySite($this->site);
			foreach($solrServers as $solrServer) {
					// make sure maybe not-yet committed documents are committed
				$solrServer->commit();
				$solrServer->deleteByQuery('*:*');
				$solrServer->commit(FALSE, FALSE, FALSE);
			}
		} catch (Exception $e) {
			$message = '<p>An error occured while trying to empty the index:</p>'
					 . '<p>' . $e->__toString() . '</p>';
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
		}

		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			'',
			$severity
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	protected function cleanupSiteIndex() {
		$garbageCollector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_garbageCollector');
		$garbageCollector->cleanIndex($this->site);

		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			'Index cleaned up.',
			'',
			\TYPO3\CMS\Core\Messaging\FlashMessage::OK
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	protected function deleteSiteDocuments() {
		$siteHash = $this->site->getSiteHash();
		$message  = 'Documents deleted.';
		$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;

		try {
			$solrServers = $this->connectionManager->getConnectionsBySite($this->site);
			foreach($solrServers as $solrServer) {
				// make sure maybe not-yet committed documents are committed
				$solrServer->commit();
				$solrServer->deleteByQuery('siteHash:' . $siteHash);
				$solrServer->commit(FALSE, FALSE, FALSE);
			}
		} catch (Exception $e) {
			$message = '<p>An error occured while trying to delete documents from the index:</p>'
			. '<p>' . $e->__toString() . '</p>';
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
		}

		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			'',
			$severity
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	protected function reloadSiteCores() {
		$coresReloaded = TRUE;
		$solrServers = $this->connectionManager->getConnectionsBySite($this->site);

		foreach($solrServers as $solrServer) {
			/* @var $solrServer Tx_Solr_SolrService */

			$path = $solrServer->getPath();
			$pathElements = explode('/', trim($path, '/'));

			$coreName = array_pop($pathElements);

			$coreAdminReloadUrl =
				$solrServer->getScheme() . '://' .
				$solrServer->getHost() . ':' .
				$solrServer->getPort() . '/' .
				$pathElements[0] . '/' .
				'admin/cores?action=reload&core=' .
				$coreName;

			$httpTransport = $solrServer->getHttpTransport();
			$httpResponse  = $httpTransport->performGetRequest($coreAdminReloadUrl);
			$solrResponse  = new Apache_Solr_Response($httpResponse, $solrServer->getCreateDocuments(), $solrServer->getCollapseSingleValueArrays());

			if ($solrResponse->getHttpStatus() != 200) {
				$coresReloaded = FALSE;

				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					'Failed to reload index configuration for core "' . $coreName . '"',
					'',
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
				);
				\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
			}
		}

		if ($coresReloaded) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				'Index configuration reloaded.',
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::OK
			);
			\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
		}
	}

	protected function deleteDocument() {
		$documentUid  = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('delete_uid');
		$documentType = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('delete_type');

		$message  = 'Document(s) with type '. $documentType . ' and id ' . $documentUid . ' deleted';
		$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;

		if (empty($documentUid) || empty($documentType)) {
			$message  = 'Missing uid or type to delete documents.';
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
		} else {
			try {
				$uids         = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $documentUid);
				$uidCondition = implode(' OR ', $uids);

				$solrServers = $this->connectionManager->getConnectionsBySite($this->site);
				foreach($solrServers as $solrServer) {
					$response = $solrServer->deleteByQuery(
						'uid:(' . $uidCondition . ')'
						.' AND type:' . $documentType
						.' AND siteHash:' . $this->site->getSiteHash()
					);
					$solrServer->commit(FALSE, FALSE, FALSE);

					if ($response->getHttpStatus() != 200) {
						throw new RuntimeException('Delete Query failed.', 1332250835);
					}
				}
			} catch (Exception $e) {
				$message  = $e->getMessage();
				$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
			}
		}

		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			'',
			$severity
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	//// TEMPORARY END


	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array();

			// CSH
		$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem(
			'_MOD_web_func',
			'',
			$GLOBALS['BACK_PATH']
		);

			// SAVE button
		$buttons['save'] = '<input type="image" class="c-inputButton" name="submit" value="Update"'
			. \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '')
			. ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1)
			. '" />';

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon(
				'',
				'function',
				$this->MCONF['name']
			);
		}

		return $buttons;
	}

}



if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/ModAdmin/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/ModAdmin/index.php']);
}




// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_ModuleAdmin');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>