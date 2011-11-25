<?php
/*
Plugin Name: Media File Manager
Plugin URI: http://tempspace.net/plugins/?page_id=111
Description: You can make sub-directories in the upload directory, and move files into them. At the same time, this plugin modifies the URLs/path names in the database. Also an alternative file-selector is added in the editing post/page screen, so you can pick up media files from the subfolders easily.
Version: 0.8.0
Author: Atsushi Ueda
Author URI: http://tempspace.net/plugins/
License: GPL2
*/

define("MLOC_DEBUG", 0);

//function dbg2($str){$fp=fopen("/tmp/smdebug.txt","a");fwrite($fp,$str . "\n");fclose($fp);}

$mrelocator_plugin_URL = get_option( 'siteurl' ) . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));
$mrelocator_uploaddir_t = wp_upload_dir();
$mrelocator_uploaddir = str_replace("//","/",$mrelocator_uploaddir_t['basedir'] . "/");
$mrelocator_uploadurl = mrelocator_path2url($mrelocator_uploaddir);

function mrelocator_init() {
	wp_enqueue_script('jquery');
}
add_action('init', 'mrelocator_init');

function mrelocator_admin_register_head() {
	global $mrelocator_plugin_URL;
	$url = $mrelocator_plugin_URL . '/style.css';
	echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
}
add_action('admin_head', 'mrelocator_admin_register_head');


// 設定メニューの追加
add_action('admin_menu', 'mrelocator_plugin_menu');
function mrelocator_plugin_menu()
{
	/*  設定画面の追加  */
	add_submenu_page('upload.php', 'Media File Manager', 'Media File Manager', 'manage_options', 'mrelocator-submenu-handle', 'mrelocator_magic_function'); 
}


/*  設定画面出力  */
function mrelocator_magic_function()
{
	global $mrelocator_plugin_URL;
	global $mrelocator_uploaddir;
	//$plugin = plugin_basename('EsAudioPlayer'); $plugin = dirname(__FILE__);
	//$upload_dir = wp_upload_dir();
	echo "<script type=\"text/javascript\"> var uploaddir = '".$mrelocator_uploaddir."' </script>\n";
	?>


	<script type="text/javascript"> mrloc_document_root = '<?php echo $_SERVER['DOCUMENT_ROOT']?>';mrloc_url_root='<?php echo mrelocator_get_urlroot();?>';</script>
	<script type="text/javascript" src="<?php echo $mrelocator_plugin_URL;?>/media-relocator.js"></script>

	<div class="wrap">
		<h2>Media File Manager</h2>

		<div id="mrl_wrapper_all">
			<div class="mrl_wrapper_pane" id="mrl_left_wrapper">
				<div class="mrl_box1">
					<input type="textbox" class="mrl_path" id="mrl_left_path" s>
					<div style="clear:both;"></div>
					<div class="mrl_dir_up" id="mrl_left_dir_up"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_up.png";?>"></div>
					<div class="mrl_dir_up" id="mrl_left_dir_new"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_new.png";?>"></div>
				</div>
				<div style="clear:both;"></div>
				<div class="mrl_pane" id="mrl_left_pane">	</div>
			</div>

			<div id="mrl_center_wrapper">
				<div id="mrl_btn_left2right"><img src="<?php echo $mrelocator_plugin_URL."/images/right.png";?>" /></div>
				<div id="mrl_btn_right2left"><img src="<?php echo $mrelocator_plugin_URL."/images/left.png";?>" /></div>
			</div>

			<div class="mrl_wrapper_pane" id="mrl_right_wrapper">
				<div class="mrl_box1">
					<input type="textbox" class="mrl_path" id="mrl_right_path" >
					<div style="clear:both;"></div>
					<div class="mrl_dir_up" id="mrl_right_dir_up"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_up.png";?>"></div>
					<div class="mrl_dir_up" id="mrl_right_dir_new"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_new.png";?>"></div>
				</div>
				<div style="clear:both;"></div>
				<div class="mrl_pane" id="mrl_right_pane"></div>
			</div>
		</div>
<div id="debug">.<br></div>
<div id="mrl_test">test<br></div>

	</div>



	<?php 
	if ( isset($_POST['updateEsAudioPlayerSetting'] ) ) {
		//echo '<script type="text/javascript">alert("Options Saved.");</script>';
	}
}


function mrelocator_getdir_callback()
{
	global $wpdb;
	global $mrelocator_plugin_URL;

	$dir = $_POST['dir'];

	if (strlen($dir)) {
		if (substr($dir, strlen($dir)-1, 1)!="/") {
			$dir .= "/";
		}
	}

	$dh = @opendir ( $dir );

	if ($dh === false) {
		die("error: cannot open directory (".$dir.")");
	}
	for ($i=0;;$i++) {
		$str = readdir($dh);
		if ($str=="." || $str=="..") {$i--;continue;}
		if ($str === FALSE) break;
		$dir0[$i] = $str;
	}
	if (!is_array($dir0)) die("");
	for ($i=0; $i<count($dir0); $i++) {
		$name = $dir0[$i];
		$dir1[$i]['ids'] = $i;
		$dir1[$i]['name'] = $name;
		$dir1[$i]['isdir'] = is_dir($dir."/".$name)?1:0;
		$dir1[$i]['isthumb'] = 0;
	}
	usort($dir1, mrelocator_dircmp);
	for ($i=count($dir1)-1; $i>=0; $i--) {
		$dir1[$i]['id'] = "";
		if ($dir1[$i]['isdir']) {
			$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/dir.png";
		}
		if ($dir1[$i]['isdir']) {
			$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/dir.png";
		}
		else if (!mrelocator_isimage($dir1[$i]['name'])) {
			if (mrelocator_isaudio($dir1[$i]['name'])) {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/audio.png";
			} else if (mrelocator_isvideo($dir1[$i]['name'])) {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/video.png";
			} else {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/file.png";
			}
			continue;
		}
		if ($dir1[$i]['isthumb']==1 || $dir1[$i]['isdir']==1) {continue;}
		$subdir_fn = mrelocator_get_subdir($dir) . $dir1[$i]['name'];
		$dbres = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value='".$subdir_fn."'");
		$dir1[$i]['parent'] = "";
		$dir1[$i]['thumbnail'] = "";
		$dir1[$i]['thumbnail_url'] = "";
		if (count($dbres)) {
			$dir1[$i]['id'] = $dbres[0]->post_id;
			$res = wp_get_attachment_metadata( $dbres[0]->post_id );
//if (!is_array($res)) {print_r ($res);echo 'ID='+$dbres[0]->post_id+"  ";}
			if (array_key_exists('sizes', $res)) {
				$min_size = -1;
				$min_child = -1;
				foreach ($res['sizes'] as $key => $value) {
					for ($j=0; $j<count($dir1); $j++) {
						if ($dir1[$j]['name'] == $res['sizes'][$key]['file']) {
							$dir1[$j]['parent'] = $i;
							$dir1[$j]['isthumb'] = 1;
							$size = $res['sizes'][$key]['width']*$res['sizes'][$key]['height'];
							if ($size < $min_size || $min_size==-1) {
								$min_size = $size;
								$min_child = $j;
							}
							break;
						}
					}
				}
				$dir1[$i]['thumbnail'] = $min_child;
				$dir1[$i]['thumbnail_url'] = mrelocator_path2url($dir .  $dir1[$min_child]['name']);

				$backup_sizes = get_post_meta( $dbres[0]->post_id, '_wp_attachment_backup_sizes', true );
				$meta = wp_get_attachment_metadata( $dbres[0]->post_id );
				if ( is_array($backup_sizes) ) {
					foreach ( $backup_sizes as $size ) {
						for ($j=0; $j<count($dir1); $j++) {
							if ($dir1[$j]['name'] == $size['file']) {
								$dir1[$j]['parent'] = $i;
								$dir1[$j]['isthumb'] = 1;
								break;
							}
						}
					}
				}
			}
		}
		if ($dir1[$i]['thumbnail_url']=="" && $dir1[$i]['isthumb']==0) {
			$fsize = filesize($dir . $dir1[$i]['name']);
			if ($fsize>1 && $fsize<32768) {
				$dir1[$i]['thumbnail_url'] = mrelocator_path2url($dir .  $dir1[$i]['name']);
			} else {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/no_thumb.png";
			}
		}
	}
	echo json_encode($dir1);
	die();
}

add_action('wp_ajax_mrelocator_getdir', 'mrelocator_getdir_callback');

function mrelocator_dircmp($a, $b)
{
	$ret = $b['isdir'] - $a['isdir'];
	if ($ret) return $ret;
	return strcmp($a['name'], $b['name']);
}

function mrelocator_mkdir_callback()
{
	global $wpdb;

	$dir = $_POST['dir'];
	$newdir = $_POST['newdir'];

	$res = chdir($dir);
	if (!$res) die();

	$res = mkdir($newdir);
	if (!$res) die();

	die('');
}
add_action('wp_ajax_mrelocator_mkdir', 'mrelocator_mkdir_callback');


function udate($format, $utimestamp = null)
{
    if (is_null($utimestamp))
        $utimestamp = microtime(true);

    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);

    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}


function mrelocator_get_subdir($dir)
{
	$upload_dir_a = wp_upload_dir();
	$upload_dir = $upload_dir_a['path'];
	$upload_dir = substr($upload_dir, 0, strlen($upload_dir)-strlen($upload_dir_a['subdir']));
	$subdir = substr($dir,  strlen($upload_dir));
	if (substr($subdir,0,1)=="/" || substr($subdir,0,1)=="\\") {
		$subdir = substr($subdir, 1);
	}
	if (substr($subdir, strlen($subdir)-1, 1)!="/") {
		$subdir .= "/";
	}
	if ($subdir=="/") $subdir="";
	return $subdir;
}


function mrelocator_rename_callback()
{
	global $wpdb;
	global $mrelocator_uploaddir;

	$wpdb->show_errors();

	$dir = $_POST['dir'];
	if (substr($dir, strlen($dir)-1,1) != "/") $dir .= "/";
	$subdir = substr($dir, strlen($mrelocator_uploaddir));

	$old[0] = $_POST['from'];
	$new[0] = $_POST['to'];
	if ($old[0] == $new[0]) die();

	$old_url =  mrelocator_path2url($dir . $old[0]);
	$dbres = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value = '" . $subdir . $old[0] . "'");

	$smallimgs = array();

	// $old or $new [greater than 0] : smaller images
	if (count($dbres)) { 
		$res = wp_get_attachment_metadata( $dbres[0]->post_id );
		if (array_key_exists('sizes', $res)) {
			foreach ($res['sizes'] as $key => $value) {
				$file = $res['sizes'][$key]['file'];
				$width = $res['sizes'][$key]['width'];
				$height = $res['sizes'][$key]['height'];
				$path_parts = pathinfo($new[0]);
				$old[count($old)] = $file;
				$new[count($new)] = $path_parts['filename']."-".$width."x".$height.".".$path_parts['extension'];
				
				$smallimgs[$key]['old'] = $file;
				$smallimgs[$key]['new'] = $new[count($new)-1];
			}
		}
	}

	for ($i=0; $i<count($old); $i++) {
		$res = @rename($dir.$old[$i], $dir.$new[$i]);
		if (!res) {
			for ($j=0; $j<$i; $j++) {
				$res = @rename($dir.$new[$i], $dir.$old[$i]);
			}
			die("An error occured while rename files.");
		}
	}
//die("OK");

	$subdir = mrelocator_get_subdir($dir);
	$rc=0;

	if (!mysql_query("START TRANSACTION", $wpdb->dbh)) {$rc=1;goto ERR;}

	for ($i=0; $i<count($old); $i++) {
		$oldp = $dir . $old[$i];	//old path
		$newp = $dir . $new[$i];	//new path
		if (is_dir($newp)) {
			$oldp .= "/";
			$newp .= "/";
		}
		$oldu=mrelocator_path2url($oldp);	//old url
		$newu=mrelocator_path2url($newp);	//new url
		$olda = $subdir.$old[$i];	//old attachment file name (subdir+basename)
		$newa = $subdir.$new[$i];	//new attachment file name (subdir+basename)

		if ($wpdb->query("update $wpdb->posts set post_content=replace(post_content, '" . $oldu . "','" . $newu . "') where post_content like '%".$oldu."%'")===FALSE) {$rc=2;goto ERR;}
		if ($wpdb->query("update $wpdb->postmeta set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "') where meta_value like '%".$oldu."%'")===FALSE)  {$rc=3;goto ERR;}

		if (is_dir($newp)) {
			if ($wpdb->query("update $wpdb->posts set guid=replace(guid, '" . $oldu . "','" . $newu . "') where guid like '".$oldu."%'")===FALSE)  {$rc=4;goto ERR;}
			//$wpdb->query("update $wpdb->postmeta set meta_value=CONCAT('".$subdir.$new[$i]."/',substr(meta_value,".(strlen($subdir.$old[$i]."/")+1).")) where meta_value like '".$subdir.$old[$i]."/%'");


			$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value like '".$subdir.$old[$i]."/%'");
			for ($j=0; $j<count($ids); $j++) {
				$meta = wp_get_attachment_metadata($ids[$j]->post_id);
				//CONCAT('".$subdir.$new[$i]."/',substr(meta_value,".(strlen($subdir.$old[$i]."/")+1)."))
				$meta['file'] = $subdir.$new[$i]."/".substr($meta['file'], strlen($subdir.$old[$i]."/"));
				if (!wp_update_attachment_metadata($ids[$j]->post_id, $meta))  {$rc=5;goto ERR;}
				$wpdb->query("update $wpdb->postmeta set meta_value='".$meta['file']."' where post_id=".$ids[$j]->post_id." and meta_key='_wp_attached_file'");
			}
		} else {
			if ($i>0) break;
			$res = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_key='_wp_attached_file' and meta_value='".$olda."'");
			if (count($res)) {
				if ($wpdb->query("update $wpdb->postmeta set meta_value='" . $newa . "' where meta_value = '".$olda."'")===FALSE)  {$rc=6;goto ERR;}
				$id = $res[0]->post_id;
				$pt=pathinfo($newa);
				if ($wpdb->query("update $wpdb->posts set guid='".$newu."', post_title='".$pt['filename']."' where ID = '".$id."'")===FALSE)  {$rc=7;goto ERR;}

				$meta = wp_get_attachment_metadata($id);
				foreach ($smallimgs as $key => $value) {
					$meta['sizes'][$key]['file'] = $smallimgs[$key]['new'];
				}
				$meta['file'] = $subdir . $new[$i];
//echo $id;print_r($meta);
				if (wp_update_attachment_metadata($id, $meta)===FALSE)  {$rc=8;goto ERR;}
			}
		}
	}

	$rc=mysql_query("COMMIT", $wpdb->dbh);

	die();
ERR:
	mysql_query("ROLLBACK", $wpdb->dbh);
	for ($j=0; $j<count($new); $j++) {
		$res = @rename($dir.$new[$i], $dir.$old[$i]);
	}
	die("Error ".$rc);
}
add_action('wp_ajax_mrelocator_rename', 'mrelocator_rename_callback');

function mrelocator_move_callback()
{
	global $wpdb;
$wpdb->show_errors();

	$dir_from = $_POST['dir_from'];
	$dir_to = $_POST['dir_to'];
	if (substr($dir_from, strlen($dir_from)-1,1) != "/") $dir_from .= "/";
	if (substr($dir_to, strlen($dir_to)-1,1) != "/") $dir_to .= "/";

	$items0 = $_POST['items'];
	$items = explode("/",$items0);

	for ($i=0; $i<count($items); $i++) {
		$res = @rename($dir_from . $items[$i] , $dir_to . $items[$i]);
		if (!$res) {
			for ($j=0; $j<$i; $j++) {
				$res = @rename($dir_to . $items[$j] , $dir_from . $items[$j]);
			}
			die("Items could not be moved.");
		}
	}
//die("OK");
	$rc = 0;
	mysql_query("BEGIN", $wpdb->dbh);

	$subdir_from = mrelocator_get_subdir($dir_from);
	$subdir_to = mrelocator_get_subdir($dir_to);

	for ($i=0; $i<count($items); $i++) {
		$old = $dir_from . $items[$i];
		$new = $dir_to . $items[$i];
		$isdir=false;
		if (is_dir($new)) {
			$old .= "/";
			$new .= "/";
			$isdir=true;
		}
		$oldu=mrelocator_path2url($old);
		$newu=mrelocator_path2url($new);
		if ($wpdb->query("update $wpdb->posts set post_content=replace(post_content, '" . $oldu . "','" . $newu . "') where post_content like '%".$oldu."%'")===FALSE) {$rc=1;goto ERR;}
		if ($wpdb->query("update $wpdb->postmeta set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "') where meta_value like '%".$oldu."%'")===FALSE) {$rc=2;goto ERR;}

		if ($isdir) {
			if ($wpdb->query("update $wpdb->posts set guid=replace(guid, '" . $oldu . "','" . $newu . "') where guid like '".$oldu."%'")===FALSE) {$rc=3;goto ERR;}
			if ($wpdb->query("update $wpdb->postmeta set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "') where meta_value like '".$oldu."%'")===FALSE) {$rc=4;goto ERR;}

			$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value like '".$subdir_from.$items[$i]."/%'");
			for ($j=0; $j<count($ids); $j++) {
				$meta = wp_get_attachment_metadata($ids[$j]->post_id);
				//$meta->file = CONCAT('".$subdir_to.$items[$i]."/',substr(meta_value,".(strlen($subdir_from.$items[$i]."/")+1)."))
				$meta['file'] = $subdir_to.$items[$i]."/" . substr($meta['file'], strlen($subdir_from.$items[$i]."/"));
				wp_update_attachment_metadata($ids[$j]->post_id, $meta);
				if ($wpdb->query("update $wpdb->postmeta set meta_value='".$meta['file']."' where post_id=".$ids[$j]->post_id." and meta_key='_wp_attached_file'")===FALSE) {$rc=5;goto ERR;}
			}
			//$wpdb->query("update $wpdb->postmeta set meta_value=CONCAT('".$subdir_to.$items[$i]."/',substr(meta_value,".(strlen($subdir_from.$items[$i]."/")+1).")) where meta_value like '".$subdir_from.$items[$i]."/%'");
		} else {
			if ($wpdb->query("update $wpdb->posts set guid='" . $newu . "' where guid = '".$oldu."'")===FALSE) {$rc=6;goto ERR;}
			$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value = '".$subdir_from.$items[$i]."'");
			for ($j=0; $j<count($ids); $j++) {
				$meta = wp_get_attachment_metadata($ids[$j]->post_id);
				$meta['file'] = $subdir_to.$items[$i]; 
				wp_update_attachment_metadata($ids[$j]->post_id, $meta);
			}
			if ($wpdb->query("update $wpdb->postmeta set meta_value='" . $subdir_to.$items[$i] . "'where meta_value = '".$subdir_from.$items[$i]."'")===FALSE) {$rc=7;goto ERR;}
		}
	}

	mysql_query("COMMIT", $wpdb->dbh);

	die("");
ERR:
	mysql_query("ROLLBACK", $wpdb->dbh);
	for ($j=0; $j<count($items); $j++) {
		$res = @rename($dir_to . $items[$j] , $dir_from . $items[$j]);
	}
	die("Error ".$rc);
}
add_action('wp_ajax_mrelocator_move', 'mrelocator_move_callback');


function mrelocator_url2path($url)
{
	$urlroot = mrelocator_get_urlroot();
	if (stripos($url, $urlroot) != 0) {
		return "";
	}
	return $_SERVER['DOCUMENT_ROOT'] . substr($url, strlen($urlroot));
//get_bloginfo('url'); → http://tempspace.net/hu6
//$_SERVER['DOCUMENT_ROOT']  /home/tempspace/public_html
}

function mrelocator_path2url($path)
{
	$path = str_replace("//","/",$path);
	$urlroot = mrelocator_get_urlroot();
	$docroot = $_SERVER['DOCUMENT_ROOT'];
	if (stripos($path, $docroot) != 0) {
		return "";
	}
	return $urlroot . substr($path, strlen($docroot));
}

function mrelocator_get_urlroot()
{
	$urlroot = get_bloginfo('url');
	$pos = strpos($urlroot, "//");
	if (!$pos) return "";
	$pos = strpos($urlroot, "/", $pos+2);
	if ($pos) {
		$urlroot = substr($urlroot, 0, $pos);
	}
	return $urlroot;
}

function mrelocator_isimage($fname)
{
	$ext = array(".jpg", ".jpeg", ".gif", ".png", ".bmp", ".tif", ".dng", ".pef", ".cr2");
	for ($i=0; $i<count($ext); $i++) {
		if (strcasecmp(substr($fname, strlen($fname)-strlen($ext[$i])) , $ext[$i]) == 0) {
			return true;
		}
	}
	return false;
}

function mrelocator_isaudio($fname)
{
	$ext = array(".mp3", ".m3u", ".wma", ".ra", ".ram", ".aac", ".flac", ".ogg");
	for ($i=0; $i<count($ext); $i++) {
		if (strcasecmp(substr($fname, strlen($fname)-strlen($ext[$i])) , $ext[$i]) == 0) {
			return true;
		}
	}
	return false;
}

function mrelocator_isvideo($fname)
{
	$ext = array(".mp4", ".wav", ".wma", ".avi", ".flv", ".ogv", ".divx", ".asf");
	for ($i=0; $i<count($ext); $i++) {
		if (strcasecmp(substr($fname, strlen($fname)-strlen($ext[$i])) , $ext[$i]) == 0) {
			return true;
		}
	}
	return false;
}

function mrelocator_test()
{
	global $wpdb;

	$res = wp_get_attachment_metadata( 4272);
	print_r($res);
return;

	//print_r(wp_get_attachment_metadata( 2916));

	print_r(get_intermediate_image_sizes());

	unset($cfiles);
	$cfiles=array();
	foreach ( get_intermediate_image_sizes() as $size ) {
		if ( $intermediate = image_get_intermediate_size(2916, $size) ) {
			$intermediate_file = $intermediate['path'];
print_r($intermediate);
			$cfiles[count($cfiles)] = $intermediate_file;
		}
	}

	$backup_sizes = get_post_meta( 2916, '_wp_attachment_backup_sizes', true );
	$meta = wp_get_attachment_metadata( 2916 );
	if ( is_array($backup_sizes) ) {
		foreach ( $backup_sizes as $size ) {
			$cfiles[count($cfiles)] = $size['file']."\n";
		}
	}
	print_r($cfiles);


	//echo mrelocator_get_subdir("/home/tempspace/public_html/hu6/wp-content/uploads/abc/def")."\n";

	$a=pathinfo("abc.jpg");
	//print_r($a);

	$res = get_post_meta(2916,'_wp_attachment_backup_sizes');
	//print_r($res);
	die();
}
add_action('wp_ajax_mrelocator_test', 'mrelocator_test');


include 'media-selector.php';



?>
