<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Kurt Gusbeth <k.gusbeth@fixpunkt.com>
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

require_once(PATH_tslib.'class.tslib_pibase.php');
//require_once(PATH_t3lib.'class.t3lib_parsehtml_proc.php');

/**
 * Plugin 'Karussell' for the 'karussell' extension.
 *
 * @author    Kurt Gusbeth <k.gusbeth@fixpunkt.com>
 * @package    TYPO3
 * @subpackage    tx_karussell
 */
class tx_karussell_pi1 extends tslib_pibase {
    public $prefixId      = 'tx_karussell_pi1';        // Same as class name
    public $scriptRelPath = 'pi1/class.tx_karussell_pi1.php';    // Path to this script relative to the extension dir.
    public $extKey        = 'karussell';    // The extension key.
    public $pi_checkCHash = true;
    public $conf = array();
    public $conf_template = array();
    public $use_conf_template = false;
    protected $templateCode = '';
    protected $allowedTables = 'tx_karussell_inhalt,tt_content,tt_news*,pages*,tx_mvcooking*,tx_irfaq*,tx_myquizpoll*,tx_cmwlinklist*,tx_srsendcard*';                    // table
    protected $table = '';					// table
    protected $searchFieldList = '';        // fields
    protected $foreignSelect = '';			// foreign-select
    protected $dirName = '';                // directory name in the uploads-folder
    protected $lang = '';					// language uid
    protected $start = 0;					// start-tag: 1=mo, 7=so
    protected $writeDevLog = false;			// Debug aktiv?
    protected $showUid = 0;					// Uebergabe-Parameter fuer Where
    
    /**
     * Main method of your PlugIn
     *
     * @param    string        $content: The content of the PlugIn
     * @param    array        $conf: The PlugIn Configuration
     * @return    string        The content that should be displayed on the website
     */
    function main($content, $conf)    {
        $this->conf=$conf;
        $this->pi_initPIflexForm(); // Init FlexForm configuration for plugin 
        $this->copyFlex();            // copy Felxform-Variables to this->conf
        $this->initTemplate();
        $this->lang = intval($GLOBALS['TSFE']->config['config']['sys_language_uid']);
        //$this->local_cObj = t3lib_div::makeInstance("tslib_cObj");    // Local cObj
        //$this->parseObj = t3lib_div::makeInstance('t3lib_parsehtml_proc');

        // GETTING configuration for the extension:
        $this->conf_template = unserialize($GLOBALS["TYPO3_CONF_VARS"]["EXT"]["extConf"]["karussell"]);
        if ($this->conf_template['table']!='') $this->use_conf_template = true;
        if ($this->conf_template['allowedTables']!='') $this->allowedTables = $this->conf_template['allowedTables'];
        
        // $this->pidList = $this->pi_getPidList($this->cObj->data['pages'],$this->conf['recursive']);
        if (!$this->conf['pidList']) {
            if( !($this->cObj->data['pages'] == '') ) {        // PID
                $this->conf['pidList'] = addslashes($this->cObj->data['pages']);
            } else { 
                $this->conf['pidList'] = intval($GLOBALS["TSFE"]->id);
            }
        }
        
        if (is_array(t3lib_div::_GP($this->prefixId))) {
          if (is_array(t3lib_div::_POST($this->prefixId)))
            $params = t3lib_div::_POST($this->prefixId);
          else
            $params = t3lib_div::_GET($this->prefixId);
          $this->showUid = intval($params['showUid']);
        }
        
        $mit_id = ($this->conf['loadEverytime']) ? '_'.$this->cObj->data['uid'] : '';
        
        // checks if t3jquery is loaded
        if (t3lib_extMgm::isLoaded('t3jquery')) {
            require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');
        } else {
            return $this->pi_wrapInBaseClass('<b>Extension t3jquery not loaded! Please install it!</b>');
        }
        // if t3jquery is loaded and the custom Library had been created
        if (T3JQUERY === true) {
            tx_t3jquery::addJqJS();
        } else if ($this->conf['pathToJquery']) {
            // if none of the previous is true, you need to include your own library just as an example in this way
            $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_1'] = '<script src="'.$this->conf['pathToJquery'].'" type="text/javascript"></script>'; //$this->getPath()
        }
        
        //if (!$this->conf['jsFile'])    $this->conf['jsFile'] = 'EXT:karussell/res/jquery.jcarousel.min.js';    // auskommentiert am 13.10.12
        if ($this->conf['jsFile']) {
            $datei = str_replace('EXT:'.$this->extKey.'/', t3lib_extMgm::siteRelPath($this->extKey), $this->conf['jsFile']);
            $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_2'.$mit_id] = '<script language="JavaScript" type="text/javascript" src="'.$datei.'"></script>';
        }
        
        if ($this->conf['styleFile']) {
            $datei = str_replace('EXT:'.$this->extKey.'/', t3lib_extMgm::siteRelPath($this->extKey), $this->conf['styleFile']);
            $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_4'.$mit_id] = '<link rel="stylesheet" type="text/css" href="'.$datei.'" />';
        }
        
        // enable dev logging if set
        // if ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog']) $this->writeDevLog = TRUE;
        if (TYPO3_DLOG || $this->conf['debug']) $this->writeDevLog = TRUE;
        
        if ($this->conf['table'] && $this->conf['searchFieldList']) {
            $this->table = ($this->use_conf_template) ? $this->conf_template['table'] : 
            											$this->checkTable($this->conf['table'], true);	// security check
        	if ($this->table=='') return 'illegal table in sql query!';
            $this->searchFieldList = ($this->use_conf_template) ? $this->conf_template['searchFieldList'] : 
            													  $this->checkSQLstring($this->conf['searchFieldList'], true);	// security check
            if ($this->searchFieldList=='') return 'illegal fields in sql query!';
            $this->searchFieldList = $this->table.'.uid,'.$this->searchFieldList;
        	$this->dirName = ($this->use_conf_template) ? $this->conf_template['dirName'] : 
            											  $this->conf['images.']['dirName'];
        } else {
            $this->table = 'tx_karussell_inhalt';
            $this->searchFieldList = 'uid,titel,meldung,bild,link';
            $this->dirName = 'tx_karussell';
        }
    
        $content .= $this->listView();
        //return $this->pi_wrapInBaseClass($content);
        return $this->baseWrap($content);
    }
    
    /**
     * Shows a list of database entries
     *
     * @return    string    HTML list of table entries
     */
    function listView() {    //$content, $conf) {
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();        // Loading the LOCAL_LANG values
        
        $lConf = $this->conf['listView.'];    // Local settings for the listView function    
        $version = class_exists('t3lib_utility_VersionNumber') ?
            t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) : t3lib_div::int_from_ver(TYPO3_version);
        
            // Initializing the query parameters:
        $sort = ($this->use_conf_template) ? $this->conf_template['sort'] : 
        									 $this->checkSQLstring($this->conf['sort'], true);	// security check
        if ($sort && $sort != 'rand()') {
            list($this->internal['orderBy'],$this->internal['descFlag']) = explode(':', $sort);
            if (!$this->internal['orderBy']) $this->internal['orderBy'] = 'tstamp';
            $this->internal['descFlag'] = (strtoupper($this->internal['descFlag']) == 'ASC') ? false : true;
            $this->internal['orderByList'] = $this->internal['orderBy'];
        }
        $this->internal['currentTable'] = $this->table;
        if ($version >= 4006000) {
            $this->internal['results_at_a_time']=t3lib_utility_Math::forceIntegerInRange($lConf['results_at_a_time'],0,1000,10);        // Number of results to show in a listing.
            $this->internal['maxPages']=t3lib_utility_Math::forceIntegerInRange($lConf['maxPages'],0,1000,1);        // The maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
        } else {
            $this->internal['results_at_a_time']=t3lib_div::intInRange($lConf['results_at_a_time'],0,1000,10);        // Number of results to show in a listing.
            $this->internal['maxPages']=t3lib_div::intInRange($lConf['maxPages'],0,1000,1);;        // The maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
        }
        $this->internal['searchFieldList']=$this->searchFieldList;
        
            // Get number of records:
        $andWhere = ($this->use_conf_template) ? $this->conf_template['andWhere'] : 
        										 $this->conf['andWhere'];
        if ($andWhere) {
            $andWhere = preg_replace("/###LANG_UID###/s", $this->lang, $andWhere);
            $andWhere = preg_replace("/###PARAM_UID###/s", $this->showUid, $andWhere);
            $andWhere = preg_replace("/###UID###/s", $GLOBALS['TSFE']->id, $andWhere);
            $andWhere = preg_replace("/###PID###/s", $GLOBALS['TSFE']->page['pid'], $andWhere);
            $andWhere = $this->checkSQLstring($andWhere, false);	// security check
        	if ($andWhere=='') return 'illegal where in sql query!';
        }
        if ($sort == 'rand()') {
            $andWhere .= ' ORDER BY rand()';
        }
        
        $this->foreignSelect = ($this->use_conf_template) ? $this->conf_template['foreignSelect'] : $this->conf['foreignSelect'];
        if ($this->foreignSelect && !$this->use_conf_template) {	// security check
        	// split the foreign select statement!
			require_once(t3lib_extMgm::extPath('karussell').'pi1/class.tx_karussell_pi1_SqlParser.php');
			$sqlParser = t3lib_div::makeInstance('tx_karussell_pi1_SqlParser');
			$sqlParser->ParseString($this->foreignSelect);
			$foreignQueryArray[0] = $this->checkSQLstring(trim(substr($sqlParser->getSelectStatement(),6)), false);
            $foreignQueryArray[1] = trim(substr($sqlParser->getFromStatement(),4));
        	$foreignQueryArray[2] = trim(substr($sqlParser->getWhereStatement(),5));
        	$foreignQueryArray[3] = trim(substr($sqlParser->getLimitStatement(),5));
			if ($foreignQueryArray[0]=='') return 'illegal fields in foreign sql query!';
			foreach (explode(',', $foreignQueryArray[1]) as $oneTable) {
				$oneTable_checked = $this->checkTable($oneTable, false);
      		  	if ($oneTable_checked=='') return 'illegal table in foreign sql query: '.$oneTable;
			}
      		// select neu zusammensetzen
        	$this->foreignSelect = 'SELECT '.$foreignQueryArray[0].' FROM '.$foreignQueryArray[1];
        	if ($foreignQueryArray[2]) {
        		$foreignQueryArray[2] = $this->checkSQLstring($foreignQueryArray[2], false);
	        	if ($foreignQueryArray[2]=='') return 'illegal where in foreign sql query!';
        		$this->foreignSelect .= ' WHERE '.$foreignQueryArray[2];
        	}
        	if ($foreignQueryArray[3]) {
        		$this->foreignSelect .= ' LIMIT '.intval($foreignQueryArray[3]);
        	}
        }
		//$res = $this->pi_exec_query($this->table,1,$andWhere);
		//list($this->internal['res_count']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
        
            // Make listing query, pass query to SQL database:
        $res = $this->pi_exec_query($this->table,0,$andWhere);
        $this->internal['res_count'] = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        if ($this->writeDevLog)
            t3lib_div::devLog($this->internal['res_count'].' results: SELECT '.$this->searchFieldList.' FROM '.$this->table.' WHERE pid='.$this->conf['pidList'].' '.$andWhere.' ORDER BY '.$this->internal['orderBy'].' '.$this->internal['descFlag'].'. And: recurisive='.$this->conf['recursive'].', results_at_a_time='.$this->internal['results_at_a_time'].', maxPages='.$this->internal['maxPages'], $this->extKey, 0);
        
        // default values
        $autoTime = $this->conf['jCarousel.']['auto'];
        if (!$autoTime) $autoTime = 0;
        $visible = $this->conf['jCarousel.']['visible'];
        if (!$visible) $visible = 'null';
        $animation = $this->conf['jCarousel.']['animation'];
        if (!$animation) $animation = "'fast'";
        $easing = $this->conf['jCarousel.']['easing'];
        if (!$easing) $easing = "'swing'";
        $this->start = $this->conf['jCarousel.']['start'];
        if (!$this->start) $this->start = 1;
        $scroll = $this->conf['jCarousel.']['scroll'];
        if (!$scroll) $scroll = '1';
        $vertical = $this->conf['jCarousel.']['vertical'];
        if (!$vertical) $vertical = 'false';
        $rtl = $this->conf['jCarousel.']['rtl'];
        if (!$rtl) $rtl = 'false';
        $wrap = $this->conf['jCarousel.']['wrap'];
        if (!$wrap) $wrap = "'circular'";
        $temp = $this->conf['jCarousel.']['dynamic'];
        $dynamic = ($temp=='true' || $temp==1) ? true : false;
    
        $markerArray=array();
        $markerArray['###UID###'] = intval($this->cObj->data['uid']);
        $markerArray['###MAX###'] = intval($this->internal['res_count']);
        $markerArray['###ITEMS###'] = $this->pi_list_makelist($res,$dynamic,false);
        $markerArray['###CONTROL###'] = ($this->conf['disableControl']) ? '' : $this->pi_list_makelist($res,false,true);
        if (strpos($this->templateCode, '###ITEMS2###'))
            $markerArray['###ITEMS2###'] = $this->pi_list_makelist($res,$dynamic,false,'2');
        $markerArray['###WRAP###'] = $wrap;
        $markerArray['###AUTO###'] = $autoTime;
        $markerArray['###ANIMATION###'] = $animation;
        $markerArray['###EASING###'] = $easing;
        $markerArray['###SCROLL###'] = $scroll;
        $markerArray['###START###'] = $this->start;
        $markerArray['###VISIBLE###'] = $visible;
        $markerArray['###VERTICAL###'] = $vertical;
        $markerArray['###RTL###'] = $rtl;
        $template_global = $this->cObj->getSubpart($this->templateCode, "###TEMPLATE_GLOBAL###");
        
            // Put the whole list together:
        $fullTable=$this->cObj->substituteMarkerArray($template_global, $markerArray);

            // Returns the content from the plugin.
        return $fullTable;
    }
    
    /**
     * Returns all table rows for list view
     *
     * @param    array    $res: DB result
     * @param    boolen    $isJS: is JavaScript?
     * @param    boolean    $isControl: control elements?
     * @param    string    $inr: item number
     * @return    string    A HTML table row
     */
     function pi_list_makelist($res,$isJS,$isControl,$inr='') {
        // Make list table header:
        $tRows=array();
//        $this->internal['currentRow']='';
//        $tRows[] = $this->pi_list_header();
            
        // Make list table rows
        $c=0;
        mysql_data_seek($res, $c);
        while($this->internal['currentRow'] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))  {
             $c++;
             $visible_wrap_start='';
             $visible_wrap_end='';
             if (!$isJS) {
                 if ($this->conf['jCarousel.']['visible_wrap_start'] && $this->conf['jCarousel.']['visible'] && $this->conf['jCarousel.']['visible']!='null') {
                     $visible=intval($this->conf['jCarousel.']['visible']);
                     if ($c%$visible==1) $visible_wrap_start=$this->conf['jCarousel.']['visible_wrap_start'];
                 }
                 if ($this->conf['jCarousel.']['visible_wrap_end'] && $this->conf['jCarousel.']['visible'] && $this->conf['jCarousel.']['visible']!='null') {
                     $visible=intval($this->conf['jCarousel.']['visible']);
                     if ($c%$visible==0) $visible_wrap_end=$this->conf['jCarousel.']['visible_wrap_end'];
                 }
             }
            $tRows[] = $visible_wrap_start.$this->pi_list_row($c,$isJS,$isControl,$inr).$visible_wrap_end;
            if ($inr=='2' && $this->conf['listView.']['results_at_a_time2'] && $c==intval($this->conf['listView.']['results_at_a_time2']))
                break;    // beim 2. mal evtl. früher aufhören
        }
 
        $out = implode('',$tRows);
        return ($isJS) ? substr($out,0,-1)."\n" : $out;
    }

    /**
     * Returns a single table row for list view
     *
     * @param    int        $c: Counter for odd / even behavior
     * @param    boolen    $isJS: is JavaScript?
     * @param    boolean    $isControl: control elements?
     * @param    string    $inr: item number
     * @return    string    A HTML table row
     */
    function pi_list_row($c,$isJS,$isControl,$inr='') {
        $markerArray = array();
        $searchFields = array();
        $myFields = array();
        //list($uid, $title, $content, $image, $link) = explode(',',$this->searchFieldList);
        $searchFields = explode(',',$this->searchFieldList);
        $uid=intval($this->internal['currentRow']['uid']);
        $title=trim($searchFields[1]);
        $content=trim($searchFields[2]);
        $image=trim($searchFields[3]);
        $link=trim($searchFields[4]);
        if (count($searchFields) > 5) {
            for ($i=5; $i<count($searchFields); $i++) $myFields[]=trim($searchFields[$i]);
        }
        
        $inhalt = $this->getFieldContent($content,$isJS);
        if ($this->conf['crop']) $inhalt = $this->Cut($inhalt, $this->conf['crop']);
        $titel = $this->getFieldContent($title,$isJS);
        $imageName = $this->getFieldContent($image);
        if ($this->writeDevLog)
            t3lib_div::devLog("C=$c, isJS=$isJS, isControl=$isControl, imagename=$imageName, lang=".$this->lang.', check='.$this->conf['images.']['checkDefaultLang'], $this->extKey, 0);
        if (!$imageName && $this->lang!=0 && $this->conf['images.']['checkDefaultLang']) {
            $langField = $this->conf['images.']['langField'];
            if (!$langField) $langField = 't3_origuid';
            $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery($langField,
                    $this->table,
                    'uid='.$uid);
            $fetchedRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2);
            $origUid = intval($fetchedRow[$langField]);
            $GLOBALS['TYPO3_DB']->sql_free_result($res2);
            $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery($image,
                    $this->table,
                    'uid='.$origUid);
            $fetchedRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2);
            $imageName = $fetchedRow[$image];
            $GLOBALS['TYPO3_DB']->sql_free_result($res2);
            if ($this->writeDevLog)
                t3lib_div::devLog("C=$c, isJS=$isJS, isControl=$isControl, origUid=$origUid, imagefield=$image, imagename=$imageName", $this->extKey, 0);
        }
        if ($imageName) {
            if (strpos($imageName, ',') !== false) {
                // es sind mehrere Bilder da. wir nehmen nur das 1.
                $imgArray = explode(',', $imageName);
                $imageName = $imgArray[0];
            }
            $bild = $this->dirName.'/'.$imageName;
            if (substr($bild, 0, 10) != 'fileadmin/') $bild = "uploads/$bild";
            $imgTSConfig = array();
            $imgTSConfig['file'] = $bild;
            $imgTSConfig['altText'] = '';
            $imgTSConfig['titleText'] = '';
            if (is_array($this->conf['images.'])) {
                if ($isControl) {
                    if ($this->conf['images.']['maxWthumb']) $imgTSConfig['file.']['maxW'] = $this->conf['images.']['maxWthumb'];
                    if ($this->conf['images.']['maxHthumb']) $imgTSConfig['file.']['maxH'] = $this->conf['images.']['maxHthumb'];
                } else {
                    if ($this->conf['images.']['maxW']) $imgTSConfig['file.']['maxW'] = $this->conf['images.']['maxW'];
                    if ($this->conf['images.']['maxH']) $imgTSConfig['file.']['maxH'] = $this->conf['images.']['maxH'];
                }
                if ($this->conf['images.']['setTitle']) $imgTSConfig['altText'] = $titel;
                if ($this->conf['images.']['setTitle']) $imgTSConfig['titleText'] = $titel;
                //$imgTSConfig['params'] = 'id="bild'.$c.'"';
            }
            $bildTag = $this->cObj->IMAGE($imgTSConfig);
            if ($this->conf['images.']['removeWidthHeight']) {
            	$pos1 = strpos($bildTag, 'width=');
            	$pos2 = strpos($bildTag, '" ', $pos1+5);
            	if ($pos1>0 && $pos2>0)
            		$bildTag = substr($bildTag, 0, $pos1-1) . substr($bildTag, $pos2+1);
            	$pos1 = strpos($bildTag, 'height=');
            	$pos2 = strpos($bildTag, '" ', $pos1+5);
            	if ($pos1>0 && $pos2>0)
            		$bildTag = substr($bildTag, 0, $pos1-1) . substr($bildTag, $pos2+1);
            }
        } else {
            $bild = 'clear.gif';
            $bildTag = '';
        }
        
        $target = '';
        $targetTag = '';
        $class = '';
        $alt = '';
        if ($this->table<>'tx_karussell_inhalt' && $this->conf['parameterUID'] && $this->conf['destinationPID']) {
            $typolink_conf = array(
                'parameter' => intval($this->conf['destinationPID']),
                'additionalParams' => '&'.addslashes($this->conf['parameterUID']).'='.$uid,
                'useCacheHash' => 1);
            $linkTag = $this->cObj->typolink($this->conf['linkText'], $typolink_conf);
            $link = $this->pi_getPageLink(intval($this->conf['destinationPID']), '', array(addslashes($this->conf['parameterUID']) => $uid));
        } else if ($link) {
            $link = trim($this->getFieldContent($link));
			if (strpos($link, ' ') !== false) {
				// es gibt einen target
				$linkArray = explode(' ', $link);
				if (substr($link, 0, 1) == '<') {
					$link = $linkArray[1];
					$target = $linkArray[2];
					if ($target=='-') $target = '';
					$class = $linkArray[3];
					if ($class && $class!='-') $class = ' class="'.$class.'"';
					$ende = 0;
					if ($linkArray[4]) $ende = strpos($linkArray[4], '"', 1);
					if ($ende) {
						$alt = substr($linkArray[4],1,$ende-1);
						if ($alt) $alt = ' title="'.$alt.'"';
					}
				} else {
					$link = $linkArray[0];
					$target = $linkArray[1];
				}
				if ($target) $targetTag = ' target="'.$target.'"';
			}
            if (is_numeric($link)) {
                $typolink_conf = array(
                    'parameter' => $link,
                    'useCacheHash' => 1);
                $linkTag = $this->cObj->typolink($this->conf['linkText'], $typolink_conf);
                $link = $this->pi_getPageLink($link);
            } else {
                // http:// fehlt?
                if ((substr($link, 0, 4) == 'www.')) $link = 'http://'.$link; 
                $linkTag = '<a href="'.$link.'"'.$targetTag.$class.$alt.'>'.$this->conf['linkText'].'</a>';
            }
        }
        
        if ($isJS) {
            return "\n    {title: '$titel', content: '$inhalt', link: '$link', target: '$target', linkTag: '$linkTag', image: '$bild', imageTag: '$bildTag', nr: '$c'},";
        } else {
            if ($isControl)
                $template=$this->cObj->getSubpart($this->templateCode, "###TEMPLATE_CONTROL###");
            else
                $template=$this->cObj->getSubpart($this->templateCode, "###TEMPLATE_ITEM$inr###");
            $markerArray['###MAX###'] = $this->internal['res_count'];
            $markerArray['###LINK###'] = $link;
            $markerArray['###TARGET###'] = $targetTag;
            $markerArray['###LINKTAG###'] = $linkTag;
            $markerArray['###IMAGE###'] = $bild;
            $markerArray['###IMAGETAG###'] = $bildTag;
            $markerArray['###TITLE###'] = $titel;
            $markerArray['###CONTENT###'] = $inhalt;
            $markerArray['###NR###'] = $c;
            $markerArray['###UID###'] = $uid;
            $markerArray['###IS_START###'] = ($c == $this->start) ? 1 : 0;
            $markerArray['###CONT_UID###'] = intval($this->cObj->data['uid']);
            if ($this->conf['fimila']) {
                $fimilaArray = explode('|', $this->conf['fimila']);
                if ($c==1) $markerArray['###FIMILA###'] = $fimilaArray[0];
                elseif ($c==$this->internal['res_count']) $markerArray['###FIMILA###'] = $fimilaArray[2];
                else $markerArray['###FIMILA###'] = $fimilaArray[1];
            }
            if (count($myFields)>0) {
                for ($i=0; $i<count($myFields); $i++) $markerArray['###LOCAL_'.strtoupper($myFields[$i]).'###'] = $this->getFieldContent($myFields[$i],$isJS);
            }
            if ($this->foreignSelect) {	// foreign select?
            	// complete the query
                $query = $this->cObj->substituteMarkerArray($this->foreignSelect, $markerArray);
                // da das bloede Select nur Zahlen, statt Namen zurueckliefert, muss man die noch parsen
                $pos_from = strpos(strtoupper($query), 'FROM');
                $fields = trim(substr($query,7,$pos_from-7));
                $field_array = explode(',',$fields);
                if ($this->writeDevLog)
                    t3lib_div::devLog("Executing foreign-select: $query; fields for the marker: $fields", $this->extKey, 0);
                $queryResult = $GLOBALS['TYPO3_DB']->sql_query($query);
                $row2 = $GLOBALS['TYPO3_DB']->sql_fetch_row($queryResult);
                foreach ($row2 as $key => $value)
                    $markerArray['###FOREIGN_'.strtoupper(trim($field_array[$key])).'###'] = $value;
                //    $markerArray['###FOREIGN_'.strtoupper($key).'###'] = $value;
                $GLOBALS['TYPO3_DB']->sql_free_result($queryResult);
            }
            return $this->cObj->substituteMarkerArray($template, $markerArray);
        }
    }
    
    /**
     * Returns the content of a given field
     *
     * @param    string    $fN: name of table field
     * @param    boolean    $isJS: javascript?
     * @return    string    Value of the field
     */
    function getFieldContent($fN,$isJS=true) {
        switch($fN) {
            case 'uid':
                return $this->pi_list_linkSingle($this->internal['currentRow'][$fN],$this->internal['currentRow']['uid'],1);    // The "1" means that the display of single items is CACHED! Set to zero to disable caching.
            break;
            
            default:
                if ($this->conf["use_stdWrap"]==1 || $this->conf["use_stdWrap"]===$fN) {
                    $feld = $this->formatStr($this->internal['currentRow'][$fN]);
                //    $feld = $this->parseObj->TS_links_rte($this->pi_RTEcssText($this->internal['currentRow'][$fN]));
                } else if ($this->conf["nl2br"]==1) {
                    $feld = nl2br($this->internal['currentRow'][$fN]);
                    $feld = preg_replace("/\r|\n/s", "", $feld);
                } else {
                    $feld = $this->internal['currentRow'][$fN];
                }
                if ($isJS) $feld = preg_replace("/'/s", "\\'", $feld);
                return $feld;
            break;
        }
    }
    
    /**
     * Returns the label for a fieldname from local language array
     *
     * @param    string    $fN: fieldname
     * @return    string    text
     */
    function getFieldHeader($fN)    {
        switch($fN) {
            default:
                return $this->pi_getLL('listFieldHeader_'.$fN,'['.$fN.']');
            break;
        }
    }
    
    /**
     * Returns a sorting link for a column header
     *
     * @param    string    $fN: Fieldname
     * @return    string    The fieldlabel wrapped in link that contains sorting vars
     */
    function getFieldHeader_sortLink($fN)    {
        return $this->pi_linkTP_keepPIvars($this->getFieldHeader($fN),array('sort'=>$fN.':'.($this->internal['descFlag']?0:1)));
    }
    
    
    /**
    * Sets one flexform-value (if set)
    *
    * @param    string    $pre: pre-name
    * @param    string    $name: name
    * @param    string    $sheet: sheet
    * @param    int        $type: 0-1
    */
    function setFlexValue($pre,$name,$sheet,$type) {
        $value = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $name, $sheet);
        $new = '';
        if ($type==1) {    // text
            if ($value || $value==='0' || $value===0) $new = $value;            
        } else {        // number
            if ($value || $value==='0' || $value===0) $new = intval($value);            
        }
        if ($new || $new==='0' || $new===0) {
            if ($pre) {
                $this->conf[$pre.'.'][$name] = $new;
            } else {
                $this->conf[$name] = $new;
            }
        }
    }
    
    /**
    * copy flexform-values to '$this->conf'
    */
    function copyFlex() {
        $this->setFlexValue('','recursive', 'sDEF', 0);
        $this->setFlexValue('','templateFile', 'sDEF', 1);
        $this->setFlexValue('','styleFile', 'sDEF', 1);
        $this->setFlexValue('','jsFile', 'sDEF', 1);
        $this->setFlexValue('','table', 'sDEF', 1);
        $this->setFlexValue('','searchFieldList', 'sDEF', 1);
        $this->setFlexValue('','andWhere', 'sDEF', 1);
        $this->setFlexValue('','sort', 'sDEF', 1);
        $this->setFlexValue('','parameterUID', 'sDEF', 1);
        $this->setFlexValue('','destinationPID', 'sDEF', 0);
        $this->setFlexValue('','foreignSelect', 'sDEF', 1);
        $this->setFlexValue('','disableControl', 'sDEF', 0);
        $this->setFlexValue('','use_stdWrap', 'sDEF', 0);
        $this->setFlexValue('','linkText', 'sDEF', 1);
        $this->setFlexValue('','crop', 'sDEF', 0);
        $this->setFlexValue('listView','results_at_a_time', 'sDEF', 0);
        $this->setFlexValue('images','dirName', 'sIMAGES', 1);
        $this->setFlexValue('images','maxW', 'sIMAGES', 0);
        $this->setFlexValue('images','maxH', 'sIMAGES', 0);
        $this->setFlexValue('images','maxWthumb', 'sIMAGES', 0);
        $this->setFlexValue('images','maxHthumb', 'sIMAGES', 0);
        $this->setFlexValue('images','removeWidthHeight', 'sIMAGES', 0);
        $this->setFlexValue('images','setTitle', 'sIMAGES', 0);
        $this->setFlexValue('images','checkDefaultLang', 'sIMAGES', 0);
        $this->setFlexValue('images','langField', 'sIMAGES', 1);
        $this->setFlexValue('jCarousel','auto', 'sJCAROUSEL', 0);
        $this->setFlexValue('jCarousel','animation', 'sJCAROUSEL', 1);
        $this->setFlexValue('jCarousel','easing', 'sJCAROUSEL', 1);
        $this->setFlexValue('jCarousel','start', 'sJCAROUSEL', 0);
        $this->setFlexValue('jCarousel','scroll', 'sJCAROUSEL', 0);
        $this->setFlexValue('jCarousel','visible', 'sJCAROUSEL', 1);
        $this->setFlexValue('jCarousel','vertical', 'sJCAROUSEL', 1);
        $this->setFlexValue('jCarousel','dynamic', 'sJCAROUSEL', 1);
        $this->setFlexValue('jCarousel','rtl', 'sJCAROUSEL', 1);
        $this->setFlexValue('jCarousel','wrap', 'sJCAROUSEL', 1);
    }
    
    /**
    * read the template file, fill in global wraps and markers and write the result
    * to '$this->templateCode'
    */
    function initTemplate() {
       // read template-file
       $origTemplateFile = 'EXT:karussell/examples/template.html';
       if ($this->conf['templateFile']) {
           $templateFile = $this->conf['templateFile'];
       } else {
              $templateFile = $origTemplateFile;          
       }
       $this->templateCode = $this->cObj->fileResource($templateFile);
    }
    
    /**
     * Format string with nl2br and htmlspecialchars()
     *
     * @param    string    $str: input-string
     * @return    string    formatted string
     */
    function formatStr($str)    {
        if (is_array($this->conf["general_stdWrap."]))    {
            $str = $this->cObj->stdWrap($str, $this->conf["general_stdWrap."]);
        }
        return $str;
    }
    
    /**
     * Crop a string after max ... chars
     *
     * @param    string    $string: input-string
     * @param    string    $max_length: max length
     * @return    string    cropped string
     */
    function Cut($string, $max_length){  
        if (mb_strlen($string) > $max_length){  
            $string = mb_substr($string, 0, $max_length);  
            $pos = mb_strrpos($string, " ");
            if($pos === false) {
                return mb_substr($string, 0, $max_length)." ...";
            }
            return substr($string, 0, $pos)." ...";
        }else{
            return $string;
        }
    }
    
    /**
     * Inhalt wrappen
     *
     * @param    string    $content
     * @return    string    wrapped content
     */
    protected function baseWrap($content) {
      if (isset($this->conf['baseWrap.'])) {
        return $this->cObj->stdWrap($content,$this->conf['baseWrap.']);
      } else {
        return $this->pi_wrapInBaseClass($content);
      }
    }
    
    /**
     * Checks, if a table is allowed
     *
     * @param	string	$table
     * @param	boolean	$clean	clean up the string?
     * @return	string	allowed table
     */
    protected function checkTable($table, $clean) {
    	$allowedTablesArray = explode(',', $this->allowedTables);
    	$allowedTable = '';
    	$table = trim($table);
    	foreach ($allowedTablesArray as $value) {
    		if ($table==trim($value)) {
    			// e.g. "tt_content" == "tt_content"
    			$allowedTable=$table;
    			break;
    		} else if ((strpos($value, '*')>1) && (strpos($table, rtrim(trim($value),'*'))===0)) {
    			// e.g. "tx_myquizpoll"* is in "tx_myquizpoll_result" at position 0
    			$allowedTable=$table;
    			break;
    		} else if (!$clean) {
    			$tableArray = explode(' ',$table);
    			if (trim($tableArray[0]) == trim($value) && count($tableArray)<=3) {
    				// e.g. "tt_news_cat" is in "tt_news_cat AS cat" at position 0
    				$allowedTable=$table;
    				break;
    			}
    		}
    	}
    	return $this->checkSQLstring($allowedTable, $clean);
    }
    
    /**
     * Checks, if the string is OK
     *
     * @param	string	$check	string to be checked
     * @param	boolean	$clean	clean up the string?
     * @return	string	allowed SQL-string
     */
    protected function checkSQLstring($check, $clean) {
    	if (preg_match('/fe_users/i', $check) || preg_match('/be_users/i', $check) || preg_match('/be_sessions/i', $check) || 
    		preg_match('/insert /i', $check) || preg_match('/update /i', $check) || preg_match('/delete /i', $check) || preg_match('/select /i', $check)) 
    		return '';
    	else if ($clean)
    		return str_replace(' ','',addslashes($check));
    	else
    		return $check;
    }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/karussell/pi1/class.tx_karussell_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/karussell/pi1/class.tx_karussell_pi1.php']);
}
?>