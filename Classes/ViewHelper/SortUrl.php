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
 * Creates a solr sorting URL by expanding a ###SOLR_URL:sortOption### marker.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class Tx_Solr_ViewHelper_SortUrl implements Tx_Solr_ViewHelper {

	/**
	 * Holds the solr configuration
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * An instance of a Solr Search
	 *
	 * @var Tx_Solr_Search
	 */
	protected $search;

	/**
	 * Query Link Builder
	 *
	 * @var Tx_Solr_Query_LinkBuilder
	 */
	protected $queryLinkBuilder = NULL;


	/**
	 * constructor for class Tx_Solr_ViewHelper_SortUrl
	 */
	public function __construct(array $arguments = array()) {
		$this->search = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Search');

		$this->configuration    = Tx_Solr_Util::getSolrConfiguration();
		$this->queryLinkBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query_LinkBuilder', $this->search->getQuery());
	}

	/**
	 * Returns an URL that switches sorting to the given sort option
	 *
	 * @param array $arguments
	 * @return	string
	 */
	public function execute(array $arguments = array()) {
		$sortUrl           = '';
		$urlParameters     = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_solr');
		$urlSortParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $urlParameters['sort']);
		$sortOptions       = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $arguments[0]);
		$currentSortOption = '';

		$sortHelper  = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'Tx_Solr_Sorting',
			$this->configuration['search.']['sorting.']['options.']
		);
		$configuredSortOptions = $sortHelper->getSortOptions();

		$sortParameters = array();
		foreach($sortOptions as $sortOption){
			if (array_key_exists($sortOption, $configuredSortOptions)) {
				$sortDirection = $this->configuration['search.']['sorting.']['defaultOrder'];
				$sortParameter = $sortOption . ' ' . $sortDirection;

				foreach($urlSortParameters as $urlSortParameter){
					$explodedUrlSortParameter = explode(' ', $urlSortParameter);

					if($explodedUrlSortParameter[0] == $sortOption){
						list($currentSortOption, $currentSortDirection) = $explodedUrlSortParameter;
						break;
					}
				}

				if ($currentSortOption == $sortOption) {
					switch ($currentSortDirection) {
						case 'asc':
							$sortDirection = 'desc';
							break;
						case 'desc':
							$sortDirection = 'asc';
							break;
					}

					if (!empty($this->configuration['search.']['sorting.']['options.'][$sortOption . '.']['fixedOrder'])) {
						$sortDirection = $this->configuration['search.']['sorting.']['options.'][$sortOption . '.']['fixedOrder'];
					}

					$sortParameter = $sortOption . ' ' . $sortDirection;
				}

				$sortParameters[] = $sortParameter;
			}
		}
		$sortUrl = $this->queryLinkBuilder->getQueryUrl(array('sort' => implode(', ',$sortParameters)));

		return $sortUrl;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/ViewHelper/SortUrl.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/ViewHelper/SortUrl.php']);
}

?>