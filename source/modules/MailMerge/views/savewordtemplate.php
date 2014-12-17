<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once('include/utils/utils.php');
global $upload_badext, $adb;

$uploaddir = "test/upload/"; // set this to wherever
// Arbitrary File Upload Vulnerability fix - Philip
//$binFile = sanitizeUploadFileName($file, $upload_badext);
//$_FILES["binFile"]["name"] = $binFile;
//if(isset($_REQUEST['binFile_hidden'])) {
//	$file = $_REQUEST['binFile_hidden'];
//} else {
$file = $_FILES['binFile']['name'];
//}
$binFile = sanitizeUploadFileName($file, $upload_badext);
$strDescription = vtlib_purify($_REQUEST['txtDescription']);
// Vulnerability fix ends
if (move_uploaded_file($_FILES["binFile"]["tmp_name"], $uploaddir . $_FILES["binFile"]["name"])) {
    $binFile = $_FILES['binFile']['name'];
    //$filename = basename($binFile);
    $filename = ltrim(basename(" " . $binFile)); //allowed filenames start with UTF-8 characters 
    $filetype = $_FILES['binFile']['type'];
    $filesize = $_FILES['binFile']['size'];

    $error_flag = "";
    $filetype_array = explode("/", $filetype);

    $file_type_value = strtolower($filetype_array[1]);

    if ($filesize != 0) {
        $merge_ext = array('msword', 'doc', 'document', 'rtf', 'odt', 'vnd.oasis.opendocument.text', 'octet-stream', 'vnd.oasi');
        if ($result = in_array($file_type_value, $merge_ext)) {
            if ($result != false) {
                $savefile = "true";
            }
        } else {
            $savefile = "false";
            $error_flag = "1";
        }
    }
} else {
    header("Location: index.php?module=MailMerge&view=createTemplates&error=2");
}

$data = base64_encode(fread(fopen($uploaddir . $_FILES["binFile"]["name"], "r"), $filesize));
$date_entered = date('Y-m-d H:i:s');

//Retreiving the return module and setting the parent type		
$ret_module = vtlib_purify($_REQUEST['return_module']);
$parent_type = '';
if ($_REQUEST['return_module'] == 'Leads') {
    $parent_type = 'Lead';
} elseif ($_REQUEST['return_module'] == 'Accounts') {
    $parent_type = 'Account';
} elseif ($_REQUEST['return_module'] == 'Contacts') {
    $parent_type = 'Contact';
} elseif ($_REQUEST['return_module'] == 'HelpDesk') {
    $parent_type = 'HelpDesk';
}

$genQueryId = $adb->getUniqueID("vtiger_wordtemplates");
if ($genQueryId != '') {
    if ($result != false && $savefile == "true") {
        $module = vtlib_purify($_REQUEST['target_module']);
        $sql = "INSERT INTO vtiger_wordtemplates ";
        $sql .= "(templateid,module,date_entered,parent_type,data,description,filename,filesize,filetype) values (?,?,?,?,?,?,?,?,?)";
        $params = array($genQueryId, $module, $adb->formatDate($date_entered, true), $parent_type, $adb->getEmptyBlob(false), $strDescription, $filename, $filesize, $filetype);
        $result = $adb->pquery($sql, $params);

        $result = $adb->updateBlob('vtiger_wordtemplates', 'data', " filename='" . $adb->sql_escape_string($filename) . "'", $data);
        deleteFile($uploaddir, $filename);
        header("Location: index.php?module=MailMerge&view=List");
    }
}
if ($error_flag == 1) {
    //include('modules/Vtiger/header.php');
    //include "upload.php";
    //echo "<script>alert('file type not allowed');window.history.back();</script>";
    header("Location: index.php?module=MailMerge&view=createTemplates&error=1");
}

function deleteFile($dir, $filename) {
    unlink($dir . $filename);
}

?>
