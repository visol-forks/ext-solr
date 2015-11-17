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
 * Writes statistics after searches have been conducted.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @author	Dimitri Ebert <dimitri.ebert@dkd.de>
 * @package	TYPO3
 * @subpackage	solr
 */
class Tx_Solr_Response_Processor_StatisticsWriter implements Tx_Solr_ResponseProcessor {

	/**
	 * Internal function to mask portions of the visitor IP address
	 *
	 * @param string $ip IP address in network address format
	 * @param integer $maskLength Number of octets to reset
	 * @return string
	 */
	protected function applyIpMask($ip, $maskLength) {
		// IPv4 or mapped IPv4 in IPv6
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$i = strlen($ip);
			if ($maskLength > $i) {
				$maskLength = $i;
			}

			while ($maskLength-- > 0) {
				$ip[--$i] = chr(0);
			}
		} else {
			$masks = array(
				'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff',
				'ffff:ffff:ffff:ffff::',
				'ffff:ffff:ffff:0000::',
				'ffff:ff00:0000:0000::'
			);
			return $ip & pack('a16', inet_pton($masks[$maskLength]));
		}

		return $ip;
	}

	/**
	 * Processes a query and its response after searching for that query.
	 *
	 * @param	Tx_Solr_Query	The query that has been searched for.
	 * @param	Apache_Solr_Response	The response for the last query.
	 */
	public function processResponse(Tx_Solr_Query $query, Apache_Solr_Response $response) {
		$urlParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_solr');
		$keywords      = $query->getKeywords();
		$filters       = isset($urlParameters['filter']) ? $urlParameters['filter'] : array();

		if (empty($keywords)) {
				// do not track empty queries
			return;
		}

		$keywords = \TYPO3\CMS\Core\Utility\GeneralUtility::removeXSS($keywords);
		$keywords = htmlentities($keywords, ENT_QUOTES, $GLOBALS['TSFE']->metaCharset);

		$configuration = Tx_Solr_Util::getSolrConfiguration();
		if ($configuration['search.']['frequentSearches.']['useLowercaseKeywords']) {
			$keywords = strtolower($keywords);
		}

		$ipMaskLength = (int) $configuration['statistics.']['anonymizeIP'];

		$insertFields = array(
			'pid'               => $GLOBALS['TSFE']->id,
			'root_pid'          => $GLOBALS['TSFE']->tmpl->rootLine[0]['uid'],
			'tstamp'            => $GLOBALS['EXEC_TIME'],
			'language'          => $GLOBALS['TSFE']->sys_language_uid,

			'num_found'         => $response->response->numFound,
			'suggestions_shown' => (is_object($response->spellcheck->suggestions)) ? (int) get_object_vars($response->spellcheck->suggestions) : 0,
			'time_total'        => $response->debug->timing->time,
			'time_preparation'  => $response->debug->timing->prepare->time,
			'time_processing'   => $response->debug->timing->process->time,

			'feuser_id'         => (int) $GLOBALS['TSFE']->fe_user->user['uid'],
			'cookie'            => $GLOBALS['TSFE']->fe_user->id,
			'ip'                => $this->applyIpMask(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),$ipMaskLength),

			'page'              => (int) $urlParameters['page'],
			'keywords'          => $keywords,
			'filters'           => serialize($filters),
			'sorting'           => $urlParameters['sort'] ? $urlParameters['sort'] : '', // FIXME sanitize me!
			'parameters'        => serialize($response->responseHeader->params)
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_solr_statistics', $insertFields);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/response/processor/StatisticsWriter.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/solr/Classes/response/processor/StatisticsWriter.php']);
}

?>
