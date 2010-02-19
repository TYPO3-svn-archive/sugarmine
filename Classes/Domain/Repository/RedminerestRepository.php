<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Stein <sebastian.stein@netzelf.de>
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

require_once(t3lib_extMgm::extPath('sugar_mine').'Classes/Domain/Repository/SetupRepository.php');
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/PhpActiveResource/ActiveResource.php');

/**
 * SugarMines Repository for RESTful-WebServices of Redmine (PMS).
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Repository_RedminerestRepository extends Tx_Extbase_Persistence_Repository {

	/**
	 * 
	 * @var	Tx_SugarMine_Domain_Repository_SetupRepository
	 */
	private $setup = '';
	
	/**
	 * 
	 * @var	array
	 */
	public $ticketFields = '';
	
	/**
	 * 
	 * @var	array
	 */
	public $projectFields = '';
	
	/**
	 * 
	 * @var string
	 */
	private $restUrl = '';
	
	/**
	 * Constructor of RedminerestRepository: loads typoscript fieldsetup from setup.txt.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function __construct() {
		
		$this->setup = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SetupRepository');
		if(is_object($this->setup)) {
			
			$this->ticketFields['view'] = $this->setup->getValue('redmine.ticket.viewable.');
			$this->ticketFields['alter'] = $this->setup->getValue('redmine.ticket.alterable.');
			
			$this->projectFields['view'] = $this->setup->getValue('redmine.project.viewable.');
			$this->projectFields['alter'] = $this->setup->getValue('redmine.project.alterable.');
			
			$this->restUrl = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['redmine']['url'];
			//var_dump($this->projectFields);
		}
	}
	
	/**
	 * Create an Issue of related redmine-project.
	 * 
	 * @param	array	$filterParams
	 * 
	 * @return 	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function createIssue(array $filterParams) {
		if($filterParams['project_id'] && $filterParams['subject']) {
			$issue = new Issue($filterParams);
			$issue->site = $this->restUrl;
    		$issue->request_format = 'xml';
			$issue->save();
		}//var_dump($issue->id);
	}
	
	/**
	 * Filter and find issues.
	 * 		
	 * @param	array	$filterParams	associative array (field => value)
	 * @param	string	$issueId		'all' is default value
	 * 
	 * @return 	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function findIssues($filterParams, $issueId='all') {
		$filterParams = (is_array($filterParams))? $filterParams: null;
		
		$issue = new Issue();
		$issue->site = $this->restUrl;
    	$issue->request_format = 'xml';
		$issues = $issue->find ($issueId, $filterParams);//var_dump($issues);

		if(is_array($issues)) { // $issueId === 'all': array is used -> iteration needed
			
			for ($i=0; $i < count($issues); $i++) {

				$data[$i]['id'] = (is_string($issues[$i]->id)) ? $issues[$i]->id:null ; //TODO: predefined fields are not very state of the art -> reason: buggy (shifting) data-format of rest-respond
				$data[$i]['subject'] = (is_string($issues[$i]->subject)) ? $issues[$i]->subject:null ;
				$data[$i]['description'] = (is_string($issues[$i]->description)) ? $issues[$i]->description:null ;
				
				/*
				foreach($issues[$i] as $key => $value) {
    				if($key === '_data') {
    					$data[$i] = $value;
    				}
    			}
    			*/
			}
		} else { // $issueId was defined: single object is used
			
				$data[0]['id'] = (is_string($issues->id)) ? $issues->id:null ;
				$data[0]['subject'] = (is_string($issues->subject)) ? $issues->subject:null ;
				$data[0]['description'] = (is_string($issues->description)) ? $issues->description:null ;
			/*
			foreach($issues as $key => $value) {
    			if($key === '_data') {
    					$data[0] = $value;
    			}
    		}
    		*/
		} return $data;
	}
	
		 	
	/**
	 * Filter and find projects.
	 * 
	 * @param	array	$filterParams	associative array (field => value)
	 * @param	string	$projectId		'all' is default value
	 * 
	 * @return 	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function findProjects($filterParams, $projectId='all') {
		$data = false;
		$filterParams = (is_array($filterParams))? $filterParams: null;
		
		$project = new Project();
		$project->site = $this->restUrl;
    	$project->request_format = 'xml';
		$projects = $project->find ($projectId, $filterParams);
		if(is_array($projects)) { // $projectId === 'all': array is used
			
			for ($i=0; $i < count($projects); $i++) {
    			//var_dump($projects[$i]->name);
    			foreach($projects[$i] as $key => $value) {
    				if($key === '_data') {
    					$data[$i] = $value;
    				}
    			}
			}
		} else { // $projectId was defined: single object is used
			
			foreach($projects as $key => $value) {
    			if($key === '_data') {
    					$data[0] = $value;
    			}
    		}
		}
		return $data;
	}
	
	
	
}
?>