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
 * Tests the Query class
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class Tx_Solr_QueryTest extends Tx_Phpunit_TestCase {

	/**
	 * @test
	 */
	public function noFiltersAreSetAfterInitialization() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');
		$filters = $query->getFilters();

		$this->assertTrue(
			empty($filters),
			'Query already contains filters after intialization.'
		);
	}

	/**
	 * @test
	 */
	public function addsCorrectAccessFilterForAnonymousUser() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');
		$query->setUserAccessGroups(array(-1, 0));
		$filters = $query->getFilters();

		$this->assertContains(
			'{!typo3access}-1,0',
			$filters,
			'Access filter not found in [' . implode('], [', $filters) . ']'
		);
	}

	/**
	 * @test
	 */
	public function grantsAccessToGroupZeroIfNoGroupsProvided() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');
		$query->setUserAccessGroups(array());
		$filters = $query->getFilters();

		$this->assertContains(
			'{!typo3access}0',
			$filters,
			'Access filter not found in [' . implode('], [', $filters) . ']'
		);
	}

	/**
	 * @test
	 */
	public function grantsAccessToGroupZeroIfZeroNotProvided() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');
		$query->setUserAccessGroups(array(5));
		$filters = $query->getFilters();

		$this->assertContains(
			'{!typo3access}0,5',
			$filters,
			'Access filter not found in [' . implode('], [', $filters) . ']'
		);
	}

	/**
	 * @test
	 */
	public function filtersDuplicateAccessGroups() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');
		$query->setUserAccessGroups(array(1,1));
		$filters = $query->getFilters();

		$this->assertContains(
			'{!typo3access}0,1',
			$filters,
			'Access filter not found in [' . implode('], [', $filters) . ']'
		);
	}

	/**
	 * @test
	 */
	public function allowsOnlyOneAccessFilter() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');
		$query->setUserAccessGroups(array(1));
		$query->setUserAccessGroups(array(2));
		$filters = $query->getFilters();

		$this->assertSame(
			count($filters),
			1,
			'Too many filters in [' . implode('], [', $filters) . ']'
		);
	}

	// TODO if user is in group -2 (logged in), disallow access to group -1


	// grouping


	/**
	 * @test
	 */
	public function groupingIsNotActiveAfterInitialization() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');

		$queryParameters = $query->getQueryParameters();
		foreach ($queryParameters as $queryParameter => $value) {
			$this->assertTrue(
				!\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($queryParameter, 'group'),
				'Query already contains grouping parameter "' . $queryParameter . '"'
			);
		}
	}

	/**
	 * @test
	 */
	public function settingGroupingTrueActivatesGrouping() {
		$query = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Solr_Query', 'test');

		$query->setGrouping(TRUE);

		$queryParameters = $query->getQueryParameters();
		$this->assertArrayHasKey('group', $queryParameters);
		$this->assertEquals('true', $queryParameters['group']);

		$this->assertArrayHasKey('group.format', $queryParameters);
		$this->assertEquals('grouped', $queryParameters['group.format']);

		$this->assertArrayHasKey('group.ngroups', $queryParameters);
		$this->assertEquals('true', $queryParameters['group.ngroups']);

		return $query;
	}

	/**
	 * @test
	 * @depends settingGroupingTrueActivatesGrouping
	 */
	public function settingGroupingFalseDeactivatesGrouping(Tx_Solr_Query $query) {
		$query->setGrouping(FALSE);

		$queryParameters = $query->getQueryParameters();

		foreach ($queryParameters as $queryParameter => $value) {
			$this->assertTrue(
				!\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($queryParameter, 'group'),
				'Query contains grouping parameter "' . $queryParameter . '"'
			);
		}
	}

}

?>