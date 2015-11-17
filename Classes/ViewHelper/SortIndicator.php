<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2014 Ingo Renner <ingo@typo3.org>
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
 * Creates a graphical representation of the current sorting direction by
 * expanding a ###SORT_INDICATOR:sortDirection### marker, where sortDirection is
 * expected to be either 'asc' or 'desc'
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class Tx_Solr_ViewHelper_SortIndicator implements Tx_Solr_ViewHelper {

	/**
	 * constructor for class Tx_Solr_ViewHelper_SortIndicator
	 */
	public function __construct(array $arguments = array()) {

	}

	/**
	 * Returns an URL that switches the sorting indicator according to the
	 * given sorting direction
	 *
	 * @param array $arguments Expects 'asc' or 'desc' as sorting direction in key 0
	 * @return string
	 * @throws InvalidArgumentException when providing an invalid sorting direction
	 */
	public function execute(array $arguments = array()) {
		$content            = '';
		$sortDirection      = trim($arguments[0]);
		$configuration      = Tx_Solr_Util::getSolrConfiguration();
		$contentObject      = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$defaultImagePrefix = 'EXT:solr/Resources/Images/Indicator';

		switch ($sortDirection) {
			case 'asc':
				$imageConfiguration = $configuration['viewHelpers.']['sortIndicator.']['up.'];
				if (!isset($imageConfiguration['file'])) {
					$imageConfiguration['file'] = $defaultImagePrefix . 'Up.png';
				}
				$content = $contentObject->IMAGE($imageConfiguration);
				break;
			case 'desc':
				$imageConfiguration = $configuration['viewHelpers.']['sortIndicator.']['down.'];
				if (!isset($imageConfiguration['file'])) {
					$imageConfiguration['file'] = $defaultImagePrefix . 'Down.png';
				}
				$content = $contentObject->IMAGE($imageConfiguration);
				break;
			case '###SORT.CURRENT_DIRECTION###':
			case '':
				// ignore
				break;
			default:
				throw new InvalidArgumentException(
					'Invalid sorting direction "' . $arguments[0] . '", must be "asc" or "desc".',
					1390868460
				);
		}

		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/ViewHelper/SortIndicator.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/ViewHelper/SortIndicator.php']);
}

?>