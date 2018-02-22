<?php
/**
* DLNA 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 23:02:30 [Feb 16, 2018])
*/
//
//
class app_dlna extends module {
/**
* app_dlna
*
* Module class constructor
*
* @access private
*/
function app_dlna() {
  $this->name="app_dlna";
  $this->title="DLNA";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
 $out['API_URL']=$this->config['API_URL'];
 if (!$out['API_URL']) {
  $out['API_URL']='http://';
 }
 $out['API_KEY']=$this->config['API_KEY'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];
 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_key;
   $this->config['API_KEY']=$api_key;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   $this->saveConfig();
   $this->redirect("?");
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='dlna_dev' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_dlna_dev') {
   $this->search_dlna_dev($out);
  }
  if ($this->view_mode=='edit_dlna_dev') {
   $this->edit_dlna_dev($out, $this->id);
  }
  if ($this->view_mode=='delete_dlna_dev') {
   $this->delete_dlna_dev($this->id);
   $this->redirect("?data_source=dlna_dev");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='dlna_prop') {
  if ($this->view_mode=='' || $this->view_mode=='search_dlna_prop') {
   $this->search_dlna_prop($out);
  }
  if ($this->view_mode=='edit_dlna_prop') {
   $this->edit_dlna_prop($out, $this->id);
  }
 }
 if ($this->view_mode=='scan_devices') {
	$this->scan_devices($out);
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* dlna_dev search
*
* @access public
*/
 function search_dlna_dev(&$out) {
  require(DIR_MODULES.$this->name.'/dlna_dev_search.inc.php');
 }
/**
* dlna_dev edit/add
*
* @access public
*/
 function edit_dlna_dev(&$out, $id) {
  require(DIR_MODULES.$this->name.'/dlna_dev_edit.inc.php');
 }
/**
* dlna_dev delete record
*
* @access public
*/
 function delete_dlna_dev($id) {
  $rec=SQLSelectOne("SELECT * FROM dlna_dev WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM dlna_dev WHERE ID='".$rec['ID']."'");
 }
/**
* dlna_prop search
*
* @access public
*/
 function search_dlna_prop(&$out) {
  require(DIR_MODULES.$this->name.'/dlna_prop_search.inc.php');
 }
 
 function scan_devices(&$out) {
  require(DIR_MODULES.$this->name.'/app_dlna.scan.inc.php');
 }
/**
* dlna_prop edit/add
*
* @access public
*/
 function edit_dlna_prop(&$out, $id) {
  require(DIR_MODULES.$this->name.'/dlna_prop_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
   $table='dlna_dev';
   $properties=SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
		require_once(DIR_MODULES.$this->name.'/app_dlna.remote.inc.php'); 
    }
   }
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS dlna_dev');
  SQLExec('DROP TABLE IF EXISTS dlna_prop');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data = '') {
/*
dlna_dev - 
dlna_prop - 
*/
  $data = <<<EOD
 dlna_dev: ID int(10) unsigned NOT NULL auto_increment
 dlna_dev: TITLE varchar(100) NOT NULL DEFAULT ''
 dlna_dev: LOCATION varchar(255) NOT NULL DEFAULT ''
 dlna_dev: LOGO varchar(255) NOT NULL DEFAULT ''
 dlna_dev: UUID varchar(255) NOT NULL DEFAULT ''
 dlna_dev: JSON_DATA TEXT NOT NULL DEFAULT ''
 dlna_dev: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 dlna_dev: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 dlna_prop: ID int(10) unsigned NOT NULL auto_increment
 dlna_prop: TITLE varchar(100) NOT NULL DEFAULT ''
 dlna_prop: VALUE varchar(255) NOT NULL DEFAULT ''
 dlna_prop: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 dlna_prop: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 dlna_prop: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgRmViIDE2LCAyMDE4IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
