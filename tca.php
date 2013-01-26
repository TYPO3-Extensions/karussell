<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_karussell_inhalt'] = array (
	'ctrl' => $TCA['tx_karussell_inhalt']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,titel,meldung,link,bild'
	),
	'feInterface' => $TCA['tx_karussell_inhalt']['feInterface'],
	'columns' => array (
		't3ver_label' => array (		
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
			)
		),
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_karussell_inhalt',
				'foreign_table_where' => 'AND tx_karussell_inhalt.pid=###CURRENT_PID### AND tx_karussell_inhalt.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'titel' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:karussell/locallang_db.xml:tx_karussell_inhalt.titel',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'meldung' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:karussell/locallang_db.xml:tx_karussell_inhalt.meldung',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '5',
			)
		),
		'link' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:karussell/locallang_db.xml:tx_karussell_inhalt.link',		
			'config' => array (
				'type'     => 'input',
				'size'     => '15',
				'max'      => '255',
				'checkbox' => '',
				'eval'     => 'trim',
				'wizards'  => array(
					'_PADDING' => 2,
					'link'     => array(
						'type'         => 'popup',
						'title'        => 'Link',
						'icon'         => 'link_popup.gif',
						'script'       => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'bild' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:karussell/locallang_db.xml:tx_karussell_inhalt.bild',		
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],	
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],	
				'uploadfolder' => 'uploads/tx_karussell',
				'show_thumbs' => 1,	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, titel, meldung, link, bild')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>