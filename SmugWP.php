<?php
/*
Plugin Name: SmugWP
Plugin URI: http://www.coverthisphotography.com/services/web-designdevelopment/smugwp-wordpress-plugin
Description: SmugMug Client Proofing gallery engine for WordPress.  Serves an HTML form, parses the data, confirms ownership and redirects the user to the correct client proofing gallery.
Author: CoverThis Photography
Version: 3.04
Author URI: http://www.coverthisphotography.com/
*/

/*  Copyright 2008  CoverThis Photography  (email : mike.muligan@coverthisphotography.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* ---------------------------------------------------------------------------------------------------------
   Although heavily commented, the following code needs no modification.  Indeed, modifying much of anything 
   below this line will likely break some portion of this program.  Comments are for explanation only.  It's
   like a museum.  You can look.  But keep your dirty mittens off.  :-)
   Correction as of Version 2.0 - I removed most of the comments.  Ignore that.
   --------------------------------------------------------------------------------------------------------- */
   
// ini_set('error_reporting', E_ALL);	// Un-Comment these two lines to turn error display on manualy.
// ini_set('display_errors', '1');		// (if your host has it turned off at the server level)

// Initiating SmugWP program variables, once the options page has been run once, these will no longer change anything:

$sWP['apikey'] = '57aZ47egEMPtYyDe5Ow9rV3WagsuCrK0';
$sWP['version'] = 304;
$sWP['versionpoint'] = ($sWP['version']/100);
$sWP['appname'] = 'External gallery owner confirm and redirect/v'.$sWP['versionpoint'].' (http://www.belineperspectives.com/)';

// Default options, changinf this won't help you....
$sWP_options['username'] = 'username@someplace.com';
$sWP_options['password'] = 'password';
$sWP_options['requestid'] = 'this_is_the_Request_key';
$sWP_options['formcode'] = '
	<form method="post" action="">
	  <table width="100%" border="0" cellspacing="0" cellpadding="0">
		<!-- sWP_error_insert -->
		<tr>
	      <td id="sWP_table_GalleryIDtext" >Gallery ID:</td>
	      <td id="sWP_table_GalleryIDfield" ><input type="text" name="this_is_the_Request_key" /></td>
	    </tr>
	    <tr>
	      <td id="sWP_table_empty" >&nbsp;</td>
	      <td id="sWP_table_formButtons" ><input type="submit" name="submit" id="button" value="Proof Gallery" /><input type="reset" name="button2" id="button2" value="Reset" /></td>
	    </tr>
	  </table>
	</form>';
$sWP_options['lightbox'] = 'alert';
$sWP_options['cols'] = '5';
$sWP_options['rows'] = '6';

// Functions

function sWP_options($task='ini') {
	global $sWP_options, $sWP_options_keys, $sWP_options_updated, $sWP;
	$sWP_options_keys = array_keys($sWP_options);
	if (function_exists('get_settings') && ($sWP['pluginurl'] = get_settings('siteurl')."/wp-content/plugins/SmugWP/"));
	$count = 0;
	foreach ($sWP_options as $key => $value) {
		if (!get_option('sWP_'.$key) || (isset($_POST['reset']) && isset($_POST['sWP_'.$key]))) {
			update_option('sWP_'.$key, $value);
			$sWP[$key] = $value;
			$sWP['updated'] .= $pre."sWP_".$key;
			if(!isset($pre) && $pre = ", ");
		} elseif (isset($_POST['sWP_submit']) && isset($_POST['sWP_'.$key]) && ($tmp_data = stripslashes($_POST['sWP_'.$key])) && ($tmp_data != get_option('sWP_'.$key))) {
			update_option('sWP_'.$key, $tmp_data);
			$sWP[$key] = $tmp_data;
			$tmp_data = '';
			$sWP['updated'] .= $pre."sWP_".$key;
			if(!isset($pre) && $pre = ", ");
		} else {
			$sWP[$key] = get_option('sWP_'.$key);
		}
	}
	// print_r($sWP);
	$sWP['total'] = $sWP['cols'] * $sWP['rows'];
	if ((!isset($_GET['galpage']) && ($sWP['galpage'] = 1)) || $sWP['galpage'] = $_GET['galpage']);
	$sWP['at'] = ($sWP['galpage'] * $sWP['total']) - $sWP['total'];
}

function sWP_options_page() {
	global $sWP, $sWP_options;
	
	if (isset($sWP['updated'])) {
		$sWP['updated'] = "The following options have been updated: <br />".$sWP['updated'].".";		
	?>
		<div class="updated"><p><strong><?php _e($sWP['updated'], 'mt_trans_domain' ); ?></strong></p></div>
	<?php
	}
	
	$sWP['options_header'] = "SmugWP v.".$sWP['versionpoint']." Options";
	echo '<div class="wrap smugwpoptions">';
	echo "<h2>" . __( $sWP['options_header'], 'mt_trans_domain' ) . "</h2>";
	echo '<p>For more information and updates, please visit:<br /><a href="http://www.coverthisphotography.com/services/web-designdevelopment/smugwp-wordpress-plugin" target="_blank">http://www.coverthisphotography.com/services/web-designdevelopment/smugwp-wordpress-plugin</a><br />';
	
	?>
<form method="post" action="">
<fieldset class="options">
		<legend>SmugMug Login Information
		<input name="sWP_submit" type="hidden" id="sWP_submit" value="Y" />
		</legend>
		<div style="padding: 10px 10px 0pt 25px;">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td width="25%"><label for="sWP_username">Username:</label>
					</td>
					<td><input name="sWP_username" type="text" id="sWP_username" value="<?php print $sWP['username'] ?>" /></td>
				</tr>
				<tr>
					<td width="25%"><label for="sWP_password">Password:</label></td>
					<td><input type="password" name="sWP_password" value="<?php print $sWP['password'] ?>" /></td>
				</tr>
  </table>
  <input name="" type="submit" value="Submit" />
  <input type="reset" name="Reset" id="button" value="Reset" />
  </div>
  </fieldset>
</form>
  <form method="post" action="">
  <fieldset class="options">
	<legend>Form Configuration
	<input name="sWP_submit" type="hidden" id="sWP_submit" value="Y" />
	</legend>
	<div style="padding: 10px 10px 0pt 25px;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td width="25%"><label for="sWP_formtag">Display Key:</label></td>
					<td><input name="sWP_formtag" type="text" id="sWP_formtag" value="[smugWPform]" readonly="true" class="readOnly"/> (Place this in the Post/Page where you want the form code below printed)</td>
				</tr>
				<tr>
					<td width="25%"><label for="sWP_requestid">Request Key:</label></td>
					<td><input name="sWP_requestid" type="text" id="sWP_requestid" value="<?php print $sWP['requestid'] ?>" /> (the name of the input field in the form that will be sending the Gallery ID to be checked and redirected to)</td>
				</tr>
  </table>
  <input name="" type="submit" value="Submit" />
  <input type="reset" name="Reset" id="button" value="Reset" />
  </div>
  </fieldset>
</form>
  <form method="post" action="">
  <fieldset class="options">
  <legend>Form Code - Reset to Factory Defaults <input name="reset" type="checkbox" value="reset" />
  <input name="sWP_submit" type="hidden" id="sWP_submit" value="Y" />
  </legend>
  <div style="padding: 10px 10px 0pt 25px;">
  <textarea name="sWP_formcode" cols="70" rows="10" style="margin:auto"><?php print $sWP['formcode'] ?></textarea>
  <h3>Example</h3>
  <?php sWP_CSS() ?>
  <div style="width:75%; background:#CCCCCC; border: 1px solid #666666; text-align:center; margin: 20px auto; padding:20px;"><?php sWP_displayForm() ?></div>
  <input name="" type="submit" value="Submit" />
  <input type="reset" name="Reset" id="button" value="Reset" />
  </div>
  </fieldset>
</form>
  <form method="post" action="">
  <fieldset class="options">
	<legend>SmugMug Gallery Browser Options - Reset to Factory Defaults <input name="reset" type="checkbox" value="reset" />
	<input name="sWP_submit" type="hidden" id="sWP_submit" value="Y" />
	</legend>
	<div style="padding: 10px 10px 0pt 25px;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<!--<tr>
					<td width="25%"><label for="sWP_wide">Columns:</label></td>
					<td><input name="sWP_cols" type="text" id="sWP_cols" value="<?php print $sWP['cols'] ?>" class="readOnly"/> (This determines how many images will be displayed width-wise in the image grid when browsing.  I do not suggest you change this, and if you do, remember the default value is <?php print $sWP_options['cols'] ?>.)</td>
				</tr>
				<tr>
					<td width="25%"><label for="sWP_tall">Rows:</label></td>
					<td><input name="sWP_rows" type="text" id="sWP_rows" value="<?php print $sWP['rows'] ?>" /> (This is how many images tall the grid will be.  You can change this if you wish.  Just remember the default is <?php print $sWP_options['rows'] ?>.)</td>
				</tr>-->
				<tr>
					<td width="25%"><label for="sWP_lightbox">WP lightbox 2 Support:</label></td>
					<td><select name="sWP_lightbox" id="lightbox">
        <option value="alert" <?PHP if ($sWP['lightbox'] == 'alert'){ echo 'selected'; } ?>>Alert On</option>
        <option value="off" <?PHP if ($sWP['lightbox'] == 'off'){ echo 'selected'; } ?>>Alert Off</option>
        <option value="support" <?PHP if ($sWP['lightbox'] == 'support'){ echo 'selected'; } ?>>Support On</option>
      </select><ul><li>Alert - Will support Lightbox functionality if WP Lightbox 2, if it is available, and alert you if it is not available.</li>
<li>Alert Off - Will support Lightbox functionality if WP Lightbox 2, is is available, but will not warn you if the plugin is not activated.</li>
<li>Support On - Will support Lightbox functionality, even if WP Lightbox 2 is not available.</li></ul></td>
				</tr>
  </table>
  <input name="" type="submit" value="Submit" />
  <input type="reset" name="Reset" id="button" value="Reset" />
  </div>
  </fieldset>
</form>
	
	<?php
	
	echo '</div>';
 
}

function sWP_displayForm() {
	global $sWP;
	print $sWP['formcode'];
}

function sWP_CSS() {
	global $sWP;
	echo '<link rel="stylesheet" href="'.$sWP['pluginurl'].'SmugWP.css" type="text/css" media="screen" />';
}

function sWP_smugLog() {
	global $sWP;
	require_once("phpSmug/phpSmug.php");
	$sWP['f'] = new phpSmug($sWP['apikey'],$sWP['appname']);
	if (!$sWP['f']->login_withPassword($sWP['username'],$sWP['password'])) {
		$sWP['alerts']['login'] = "Your login credentials for SmugMug are INCORRECT.  This will cause SmugWP to not function properly.  Please proceed to the SmugWP Options page, and update your SmugMug Login Information immediately.";
	}
	$sWP['nickname'] = $sWP['f']->parsed_response['Login']['User']['NickName'];
}

function sWP_lightboxCheck() {
	global $sWP;
	if (!function_exists('wp_lightbox2_init') && ($sWP['lightbox'] != 'support')) {
		$sWP['checks']['lightbox'] = true;
		if ($sWP['lightbox'] != 'off') {
			$sWP['alerts']['lightbox'] = "The SmugMug LightBox feature for SmugWP requires an activated copy of <a href='http://zeo.unic.net.my/notes/lightbox2-for-wordpress/' target='_blank'>WP lightbox 2</a> to work.  Please download, install and activate this plug-in if you wish to use this feature.<br /><br />You can disable this warning on the SmugWP Options Page.";
		}
	}
}

function sWP_alerts() {
	global $sWP;
	if(isset($sWP['alerts'])){
		foreach($sWP['alerts'] as $alert) {
			echo '<div class="error"><p><strong>'.$alert.'</strong></p></div>';
		}
	}
}

function sWP_media_retrieve($what = 'nothing', $is = 'nothing') {
	global $sWP;
	$sWP['albums'] = $sWP['f']->albums_get($sWP['nickname']);
	foreach ($sWP['albums'] as $album) {
		if (!isset($sWP['cat']['ids']) || !in_array($album['Category']['id'], $sWP['cat']['ids'])) {
			$sWP['cat']['names'][$album['Category']['id']] = $album['Category']['Name'];
			$sWP['cat']['ids'][$album['Category']['Name']] = $album['Category']['id'];
			// echo $cat['names'][$album['Category']['id']].' '.$cat['ids'][$album['Category']['Name']]."\n";
		}
		$sWP['albums']['names'][$album['id']] = $album['Title'];
		$sWP['albums']['ids'][$album['Title']] = $album['id'];
	}
	// print_r($sWP);
	asort($sWP['cat']['names']);
	if (isset($_GET['categoryID'])) {
		$sWP['subcats'] = $sWP['f']->subcategories_get($_GET['categoryID'], $sWP['nickname']);
		if (is_array($sWP['subcats'])) {
			foreach ($sWP['subcats'] as $subcategory) {
				if (!is_array($sWP['subcat']['ids']) || !in_array($subcategory['id'], $sWP['subcat']['ids'])) {
					$sWP['subcat']['names'][$subcategory['id']] = $subcategory['Title'];
					$sWP['subcat']['ids'][$subcategory['Title']] = $subcategory['id'];
				// echo $cat['names'][$album['Category']['id']].' '.$cat['ids'][$album['Category']['Name']]."\n";
				}
			}
			asort($sWP['subcat']['names']);
		}
	}
	if (isset($_GET['albumID'])) {
		$sWP['images'] = $sWP['f']->images_get($_GET['albumID']);
		// echo count($sWP['images']);
		$sWP['imagecount'] = count($sWP['images']);
		$sWP['pages'] = $sWP['imagecount'] / $sWP['total'];
		$sWP['pages'] = ceil($sWP['pages']);
	}
	if ((isset($_GET['imageID']) && $gofor = $_GET['imageID']) || (($what == "image") && ($gofor = $is))) {
		$sWP['imageInfo'] = $sWP['f']->images_getInfo($gofor);
	}
}
	
function sWP_media_crumb() {
	global $sWP;
	$sWP['bread']['url'] = '?post_id='.$_GET['post_id'].'&type=image&tab='.$_GET['tab'];
	$sWP['bread']['link'] = '<li id="home"><a href="'.$sWP['bread']['url'].'" >Home</a></li>';
	if (isset($_GET['categoryID'])) {
		$sWP['bread']['url'] .= '&categoryID='.$_GET['categoryID'];
		$sWP['bread']['link'] .= '<li><a href="'.$sWP['bread']['url'].'" >'.$sWP['cat']['names'][$_GET['categoryID']].'</a></li>';
	}
	if (isset($_GET['subcategoryID'])) {
		$sWP['bread']['url'] .= '&subcategoryID='.$_GET['subcategoryID'];
		$sWP['bread']['link'] .= '<li><a href="'.$sWP['bread']['url'].'" >'.$sWP['subcat']['names'][$_GET['subcategoryID']].'</a></li>';
	}
	if (isset($_GET['albumID'])) {
		$sWP['bread']['url'] .= '&albumID='.$_GET['albumID'];
		$sWP['bread']['link'] .= '<li><a href="'.$sWP['bread']['url'].'" >'.$sWP['albums']['names'][$_GET['albumID']].'</a></li>';
	}
	$sWP['bread']['image'] = $sWP['bread']['url'];
	if (isset($_GET['galpage'])) {
		$sWP['bread']['image'] .= '&galpage='.$_GET['galpage'];
	}
	$sWP['bread']['galpage'] = $sWP['bread']['url'];
	if (isset($_GET['imageID'])) {
		$sWP['bread']['link'] .= '<li>Image ID: '.$_GET['imageID'].'</li>';
		$sWP['bread']['galpage'] .= '&imageID='.$_GET['imageID'];
	}
	$sWP['bread']['link'] = "<div class='smugmugmediapanel'>\n<ul>\n".$sWP['bread']['link']."\n</ul></div><br />\n";
	return $sWP['bread']['link'];
}

function sWP_media_pages() {
	global $sWP;
	$push = "";
	// echo $sWP['pages'];
	if ($sWP['pages'] > 1) {
		$count = 1;
		$push = '<SCRIPT LANGUAGE="javascript">
        
        function LinkUp() 
        {
        var number = document.PageCrumb.PClinks.selectedIndex;
        location.href = document.PageCrumb.PClinks.options[number].value;
        }
        </SCRIPT>
		<table border="0" cellspacing="0" cellpadding="0">
		  <tr>
			<td>Now displaying:&nbsp;</td>
			<td> 
        <FORM NAME="PageCrumb"><SELECT NAME="PClinks" onChange="LinkUp(this.form)" >';
		while ($count <= $sWP['pages']) {
			$push .= '<OPTION ';
			if ($count == $sWP['galpage']) {
				$push .= 'SELECTED ';
			}
			$push .= 'VALUE='.$sWP['bread']['galpage'].'&galpage='.$count.'> Page '.$count;
			$count++;
		} 
		$push .='</SELECT>
			</FORM>
		  </td>
		  <td>';
		if ($sWP['galpage'] > 1)
        	$push .= '<a href="'.$sWP['bread']['galpage'].'&galpage='.($sWP['galpage'] - 1).'" title="Go Back"><img src="'.get_option('siteurl').'/wp-content/plugins/SmugWP/images/media_navigation_right.gif"></a> ';
		if ($sWP['pages'] > $sWP['galpage'])
        	$push .= '<a href="'.$sWP['bread']['galpage'].'&galpage='.($sWP['galpage'] + 1).'" title="Go Back"><img src="'.get_option('siteurl').'/wp-content/plugins/SmugWP/images/media_navigation_left.gif"></a>';
		$push .='
		  </td>
		  </tr>
		</table>';
	}
	return $push;
}

function sWP_media_display_error() {
	global $sWP, $wp_version;
	if(isset($sWP['displayerror'])) {
		echo "<h4 class='sWPerror'>There has been an error!</h4>\n";
		switch ($sWP['displayerror']) {
		case "album":
		case "subcat":
		case "cat":
			echo "<p>Bah, piece of cake!</p><p>There was nothing to display for the request '".$sWP['displayerror'].".'  Likely cause?  Wherever you just navigated to is empty (Album, Sub Category, Category).</p><p>Easiest way to fix that?  Put something in it, of course!</p><p>Go outside, take some pictures, put them here, and you shouldn't receive this error again (here anyway).</p><p><em>If this is an Album, there is another possibility</em>.</p><blockquote><p>Go to the <strong>SmugWP Options</strong> page an make sure that both '<em>Columns</em>' and '<em>Rows</em>' are <u>numbers</u>, and <em>don't</em> contain letters.</p></blockquote>";
			break;
		case "home":
			echo "<p>Ok, this is an easy one.  There was nothing to display on the Home screen, the absolute lowest level of your account.</p><p>This could either mean your log-in information is wrong, or your account is just empty.</p><p>The easiest way to solve that is to go out ant take some pictures (be sure to upload them to your SmugMug account).";
			break;
		default:
			echo "<p>Actually, something really bad happen.  So bad that there was actually an error processing your <em>error</em>!</p><p>For reference, this error came from '".$sWP['displayerror'].".'</p>";
			break;
		}
		echo "<p>However, most errors are likely caused by incorrect log-in information (are you receiving warnings on the admin pages?).  Head on over to the SmugWP Options page to update your log-in information.</p><p>If that doesn't work, and you keep getting this error, it is likely an coding issue (very, very bad), but just to be sure, double check to make sure your settings are all correct on the SmugWP Options page one more time (hey, it happens).</p><p>Still no go?</p><p>Check the plug-in <a href='http://www.coverthisphotography.com/services/web-designdevelopment/smugwp-wordpress-plugin' target='_blank' >Home Page</a> for alerts and updates, and/or re-install the plugin.</p><p>Best of luck!</p>";
		echo "<h4>Diagnostic Information</h4><textarea cols='70' rows='20' style='margin:auto'>";
		print_r($sWP);
		echo "</textarea><h5>If you are going to send this information in for help, be sure to find and X out your username and password.</h5>";
		return true;
	} else {
		return false;
	}
}

function sWP_media_display_album() {
	global $sWP;
	$sWP['images'] = array_slice($sWP['images'], $sWP['at'], $sWP['total']);
	if (is_array($sWP['images'])) {
		$push = "<table id='sWP_media_images'> \n";
		$row = 1;
		while ($row <= $sWP['rows']) { 
			$push .= "<tr> \n";
			$cell = 1;
			while ($cell <=$sWP['cols']) {
				$image = array_shift($sWP['images']);
				if (isset($image['id'])) {
					$sWP['imageInfo'] = $sWP['f']->images_getInfo($image['id']);
					if (($image['id'] == $_GET['imageID']) && ($active = " class='active'"));
					$push .= "<td".$active."><a href='".$sWP['bread']['image']."&imageID=".$image['id']."' title='".$sWP['imageInfo']['Caption']."' ><img src='".$sWP['imageInfo']['TinyURL']."' alt='External links MUST be ENABLED for this gallery.' /></a></td> \n";
				} elseif (!isset($image['id']) && ($cell == 1)) {
					$row = $sWP['rows'];
					$cell = $sWP['cols'];
				} else {
					$push .= "<td>&nbsp;</td> \n";
					$row = $sWP['rows'];
				}
				$active = '';
				$cell++;
			}
			$push .= "</tr> \n";
			$row++;
		}
		$push .= "</table> \n";
		return $push;
	} else {
		$sWP['displayerror'] = 'album';
	}
}

function sWP_media_display_subcat() {
	global $sWP;
	foreach ($sWP['albums'] as $album) {
		if ((isset($album['Category']['id']) && isset($album['SubCategory']['id'])) && ($album['Category']['id'] == $_GET['categoryID']) && ($album['SubCategory']['id'] == $_GET['subcategoryID'])) {
			$push .= "<li><a href='".$sWP['bread']['url']."&albumID=".$album['id']."'>".$album['Title']."</a></li>\n";
			// echo $cat['names'][$album['Category']['id']].' '.$cat['ids'][$album['Category']['Name']]."\n";
		}
	}
	if ($push) {
		return "\n<h4>Albums:</h4>\n<ul>\n".$push."\n</ul>";
	} else {
		$sWP['displayerror'] = 'subcat';
	}
}

function sWP_media_display_cat() {
	global $sWP;
	if (is_array($sWP['subcat']['names'])) {
		foreach ($sWP['subcat']['names'] as $name) {
			$catch['subcats'] .= "<li><a href='".$sWP['bread']['url']."&subcategoryID=".$sWP['subcat']['ids'][$name]."'>".$name."</a></li>\n";
		}
	}
	if (isset($catch['subcats'])) {
		$push .= "\n<h4>Categories:</h4>\n<ul>\n".$catch['subcats']."</ul>\n";
	}
	foreach ($sWP['albums'] as $album) {
		if (($album['Category']['id'] == $_GET['categoryID']) && !isset($album['SubCategory']['id'])) {
			$catch['albums'] .= "<li><a href='".$sWP['bread']['url']."&albumID=".$album['id']."'>".$album['Title']."</a></li>\n";
			// echo $cat['names'][$album['Category']['id']].' '.$cat['ids'][$album['Category']['Name']]."\n";
		}
	}
	if (isset($catch['albums'])) {
		$push .= "\n<h4>Albums:</h4>\n<ul>\n".$catch['albums']."</ul>\n";
	}
	if ($push) {
		return $push;
	} else {
		$sWP['displayerror'] = 'cat';
	}
}

function sWP_media_display_home() {
	global $sWP;
	foreach ($sWP['cat']['names'] as $name) {
		$push .= "<li><a href='".$sWP['bread']['url']."&categoryID=".$sWP['cat']['ids'][$name]."'>".$name."</a></li>\n";
	}
	if (isset($push)) {
		return "\n<h4>Categories:</h4>\n<ul>\n".$push."</ul>\n";
	} else {
		$sWP['displayerror'] = 'home';
	}
}

function sWP_media_display_image() {
	global $sWP;
	if (is_array($sWP['imageInfo'])) {
		$push .= "
			<form name='buildSmugMug' id='buildSmugMug'><table border='0' cellspacing='5' cellpadding='5'>
			<tr>
			<td colspan='4'><img src='".$sWP['imageInfo']['SmallURL']."'/><input name='ImageID' type='hidden' id='ImageID' value='".$_GET['imageID']."'></td>
			</tr>";
		$push .= "
			<tr>
			<td>Display Size</td>
			<td>Lightbox Size</td>
			<td>Group (leave blank for none)</td>
			<td>Float</td>
				</tr>";
		$push .= "
			<tr>
			<td><select name='DisplaySize' id='DisplaySize'>
				<option value='".$sWP['imageInfo']['TinyURL']."'>Tiny</option>
				<option value='".$sWP['imageInfo']['ThumbURL']."' selected>Thumbnail</option>
				<option value='".$sWP['imageInfo']['SmallURL']."'>Small</option>
				<option value='".$sWP['imageInfo']['MediumURL']."'>Medium</option>
				<option value='".$sWP['imageInfo']['LargeURL']."'>Large</option>
				<option value='".$sWP['imageInfo']['XLargeURL']."'>Extra Large</option>
			</select></td>
			<td><select name='LightSize' id='LightSize'>";
		if ($sWP['checks']['lightbox'] != true) {
			$push .= "
				<option value='OFF' selected>OFF</option>
				<option value='".$sWP['imageInfo']['AlbumURL']."'>Link to the Album</option>
				<option value='".$sWP['imageInfo']['TinyURL']."'>Tiny</option>
				<option value='".$sWP['imageInfo']['ThumbURL']."'>Thumbnail</option>
				<option value='".$sWP['imageInfo']['SmallURL']."'>Small</option>
				<option value='".$sWP['imageInfo']['MediumURL']."'>Medium</option>
				<option value='".$sWP['imageInfo']['LargeURL']."'>Large</option>
				<option value='".$sWP['imageInfo']['XLargeURL']."'>Extra Large</option>";
		} else {
			$push .= "
				<option value='OFF' selected>DISABLED</option>";
		}
		$push .= "
			</select></td>
			<td><input name='Group' type='text' id='Group' size='20' maxlength='20'></td>
			<td><select name='Float' id='Float'>
				<option value='None' selected>OFF</option>
				<option value='Left' >Float Left</option>
				<option value='Right' >Float Right</option>
				<option value='Center' >Center</option>
			</select></td>
			</tr>";
		$push .= "
			<tr>
			<td colspan='4' valign='top'>Title/Caption<br />
			<input name='Caption' id='Caption' style='width: 80%;' value='".$sWP['imageInfo']['Caption']."'></td>
			</tr>";
		$push .= "
			<tr><td colspan='4' valign='top'><div align='center'><input onclick='buildSmugTag();' type='button' value='Generate Tag' name='generate'><input type='reset' value='Reset' name='reset'></div></td></tr>
			</table></form>";
		return $push;
	} else {
		$sWP['displayerror'] = 'image';
	}
	// print_r($sWP['imageURLs']);
}

// Add SmugWP options page.
function sWP_add_pages() {
	add_options_page('SmugWP Options', 'SmugWP', 8, 'SmugWPoptions', 'sWP_options_page');
}

function media_upload_sWP_content() {
	global $sWP;
	media_upload_header();
	// echo $sWP['counts']['albums'];
	// echo $sWP['counts']['cats'];
	// echo $sWP['counts']['subcats'];
	// echo $sWP['counts']['images'];
	echo sWP_media_crumb();
	echo "<div id='sWP_media'>\n";
	if (isset($_GET['imageID'])) {
		echo sWP_media_display_image();
		echo sWP_media_pages();
		echo sWP_media_display_album();
	} elseif (isset($_GET['albumID'])) {  // Album contents display
		echo sWP_media_pages();
		echo sWP_media_display_album();
	} elseif (isset($_GET['subcategoryID'])) { // Third level (just albums).
		echo sWP_media_display_subcat();
	} elseif (isset($_GET['categoryID'])) {  // Second level (album/category mix).
		// print_r($subcategories);
		echo sWP_media_display_cat();
	} else {  // First level (just categories).
		echo sWP_media_display_home();
	}
	sWP_media_display_error();
	echo "<div class='smugmugmediapanel'><a class='poweredby' href='http://www.coverthisphotography.com/services/web-designdevelopment/smugwp-wordpress-plugin' title='Powered by SmugWP (Version ".$sWP['versionpoint'].")'>Powered By SmugWP Version ".$sWP['versionpoint']."</a></div></div>";
}

// BETA BETA 
function media_buttons_smugwp($context) {
	global $post_ID, $temp_ID;
	$dir = dirname(__FILE__);

	$image_btn = get_option('siteurl').'/wp-content/plugins/SmugWP/images/media_upload_button.gif';
	$image_title = 'SmugWP';
		
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);

	$media_upload_iframe_src = get_option('siteurl')."/wp-admin/media-upload.php?tab=smugmug&post_id=$uploading_iframe_ID";
	$out = ' <a href="'.$media_upload_iframe_src.'&TB_iframe=true" class="thickbox" title="'.$image_title.'"><img src="'.$image_btn.'" alt="'.$image_title.'" /></a>';
		
	return $context.$out;
}

function sWP_media_head() {
	global $sWP;
	sWP_smugLog();
	sWP_media_retrieve();
	?>
	<script language="javascript">
<!--

function buildSmugTag(){

swpImageID = document.buildSmugMug.ImageID.value;
swpDisplay = document.buildSmugMug.DisplaySize.value;
swpLight = '';
swpCaption = 'Powered by SmugWP version <?PHP echo $sWP['versionpoint'] ?>';
swpGroup = '';
swpClassGroup = '';
swpClassFloat = 'swpFL_' + document.buildSmugMug.Float.value;
swpClassID = ' swpID_' + document.buildSmugMug.ImageID.value;


if(document.buildSmugMug.Caption.value != "default"){swpLight = document.buildSmugMug.Caption.value }
if(document.buildSmugMug.LightSize.value != "OFF"){swpLight = document.buildSmugMug.LightSize.value }
if(document.buildSmugMug.Caption.value != ''){swpCaption = document.buildSmugMug.Caption.value }

if(document.buildSmugMug.Group.value != ''){
	var charpos = document.buildSmugMug.Group.value.search("[^A-Za-z0-9]"); 
    if(document.buildSmugMug.Group.value.length > 0 &&  charpos >= 0){
        alert("Lightbox Group: Only alpha-numeric characters allowed. \n [Error character position " + eval(charpos+1) + "]"); 
        return false; 
    }
	swpGroup = "[" + document.buildSmugMug.Group.value + "]";
	swpClassGroup = ' swpGR_' + document.buildSmugMug.Group.value;
}

display = '<img src="' + swpDisplay + '" alt="Powered By SmugWP" class="' + swpClassFloat + swpClassID + swpClassGroup + '" />';

if(swpLight != ''){ display = '<a href="' + swpLight +'" rel="lightbox' + swpGroup + '" title="' + swpCaption + '" class="smugwp" >' + display + '</a>' }

if(swpClassFloat == "Center"){display = '<div class="smugwpCenter' + swpClassID + swpClassGroup + '" >' + display + '</div>' }

var win = window.opener ? window.opener : window.dialogArguments;

if ( !win )
	win = top;
tinyMCE = win.tinyMCE;
if ( typeof tinyMCE != 'undefined' && tinyMCE.getInstanceById('content') ) {
	tinyMCE.selectedInstance.getWin().focus();
	tinyMCE.execCommand('mceInsertContent', false, display);
} else {
	win.edInsertContent(win.edCanvas, display);
}

}
//-->
</script>
<style type="text/css">
	.smugmugmediapanel { margin: 10px; }
	.smugmugmediapanel ul { padding: 0; margin: 5px 0 0 0; list-style-type: none; }
	.smugmugmediapanel ul li { display: block; float: left; padding: 5px 8px; font-size: 0.9em; border-left: 1px solid #E9E0E2; }
	.smugmugmediapanel ul li#home { border: none; }
	.smugmugmediapanel ul li a { text-decoration: none; }
	.smugmugmediapanel ul li a.active { text-decoration: none; border-bottom: 1px solid #223852; }
	.smugmugmediapanel a.poweredby { text-decoration: none; border: none; float: right; margin-top: 5px; padding-right: 15px; }
	.smugmugmediapanel h3 { margin: 20px 0px; padding-top: 8px; clear: both; }
	
	#sWP_media { width: 600px; margin: auto; clear: both; }
	#sWP_media_images td { padding: 5px; margin: 5px; width: 110px; height: 110px; text-align: center; background-color: #F2F2F2; border: 1px solid #CCCCCC; }
	#sWP_media_images td:hover { background-color: #E2E2E2; }
	#sWP_media_images td.active { background-color: #DEDEDE; border: 1px solid #2583AD; }
	
	#buildSmugMug td { text-align: center; }
	html { background: url(/wp-content/plugins/SmugWP/images/media_upload_background.gif) bottom left no-repeat fixed; height: auto;}
	#media-upload { background: none; }
	#buildSmugMug table { margin: auto; }
	</style><?PHP
}

function media_upload_smugmug() {
	return wp_iframe('media_upload_sWP_content');
}

function add_smugTab($tabs) {
	$tabs['smugmug'] = 'SmugMug';
	return $tabs;
}

function fixed_media_admin_css() {
     wp_admin_css('css/media');
}


// Original SmugWP initiation script.
function sWP_ini() {
	global $sWP;
	sWP_options();
	add_action('admin_head_smugmug_content', 'fixed_media_admin_css');
	add_action('media_upload_smugmug', 'media_upload_smugmug');
	add_action('media_upload_tabs','add_smugTab');
	add_action('wp_head', 'sWP_CSS', 1);
	add_action('admin_head', 'sWP_lightboxCheck');
	add_action('admin_head', 'sWP_CSS');
	add_action('admin_menu', 'sWP_add_pages');
	add_action('admin_head_media_upload_sWP_content', 'fixed_media_admin_css');
	add_action('admin_head_media_upload_sWP_content', 'sWP_media_head');
	add_action('media_upload_smugmug', 'media_upload_smugmug');
	add_action('media_upload_tabs','add_smugTab');
	add_action('admin_head', 'sWP_smugLog');
	add_action('admin_notices', 'sWP_alerts');
	add_filter('media_buttons_context', 'media_buttons_smugwp');
	add_shortcode('smugWPform', 'sWP_displayForm');
	if (!isset($_GET['submit']) && isset($_POST[$sWP['requestid']]) && ($req = $_POST[$sWP['requestid']]) && ($req = explode("_", $req))) {
		sWP_smugLog();
		// require_once("phpSmug/phpSmug.php");
		// $sWP['f'] = new phpSmug($sWP['apikey'],$sWP['appname']);
		// $sWP['f']->login_withPassword($sWP['username'],$sWP['password']);
		$albums = $sWP['f']->albums_get($sWP['nickname']);
		foreach ($albums as $album) {
			if (($album['id'] == $req[0]) && ($album['Key'] == $req[1])) {
				header("Location: http://".$sWP['nickname'].".smugmug.com/gallery/".$req[0]."_".$req[1]);
				exit;
			}
		}
		$sWP['formcode'] = str_replace( '<!-- sWP_error_insert -->', '<tr><td colspan="2" id="sWP_table_Error" >Sorry, but we encountered an error processing your request: Gallery Does not exist.</td></tr>', $sWP['formcode'] );
	}
}


// WordPress Hooks.
add_action('init', 'sWP_ini');

?>