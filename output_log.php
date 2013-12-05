<?php
require_once('../../../wp-blog-header.php');
header('Content-type: text/plain');
header('Content-Disposition: attachment; filename="mfm_log.txt"');

$sql = "select * from " . $wpdb->prefix . "media_file_manager_log order by date_time desc";
$res = $wpdb->get_results($sql);

for ($i=0; $i<count($res); $i++) {
	echo $res[$i]->date_time . "\t" . $res[$i]->log_data . "\n";
}
?>
