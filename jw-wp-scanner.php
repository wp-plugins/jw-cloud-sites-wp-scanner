<?php
/*
Plugin Name: JW Cloud Sites WordPress Scanner
Plugin URI: http://jacksonwhelan.com/2010/06/cloudsites-wordpress-scanner/
Description: Scan your WordPress installation for hidden files, backdoors in wp_options, spam links in your posts, and remove world and group permissions from all files. Designed for Rackspace Cloud Sites. Use at your own risk! Activate, and find new page under Tools.
Author: Jackson Whelan
Version: 2.2
Author URI: http://jacksonwhelan.com/
*/

$JWWPScan = new JWWPScan();

class JWWPScan {

	function JWWPScan() {	
		add_action('admin_menu', array(&$this, 'jwwps_create_menu'));
		add_action("admin_print_scripts", array(&$this, 'jwwps_js_libs'));
		add_action("admin_print_styles", array(&$this, 'jwwps_style_libs'));
		add_action('wp_ajax_jwwps_file_contents', array(&$this, 'jwwps_file_contents'));	
		add_action('wp_ajax_jwwps_delete_option_confirm', array(&$this, 'jwwps_delete_option_confirm'));	
	}
	
	function jwwps_create_menu() {
		add_submenu_page('tools.php', 'JW Cloud Sites WordPress Scanner', 'JW CS+WP Scan', 'administrator', 'jwwps', array(&$this, 'jwwps_settings_page'));
	}
	function jwwps_js_libs() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
	}
	function jwwps_style_libs() {
		wp_enqueue_style('thickbox');
	}

	function jwwps_file_contents() {
		if($_GET['confirm'] == 'true' && isset($_GET['file'])) {
			$filename = $_GET['file'];
			$out = "<div class=\"wrap\"><p>Deleting $filename...";
			if(unlink($filename)) {
				$out.= "<br /><button onClick=\"parent.tb_remove(); parent.location.reload(1)\">Successfully Deleted - Click to Return</button>";
				$deleted = get_option('jwwps_file_delete');
				$deleted = $deleted.$filename.date (" | F d Y H:i:s,");
				update_option('jwwps_file_delete',$deleted);
			} else {
				$out.= "<br />Failed to Delete File! <button onClick=\"parent.tb_remove(); parent.location.reload(1)\">Click to Return</button>";
			}
			$out.= "</p></div>";
		} else {
			$filename = $_GET['file'];
			$filetext = file_get_contents($filename);
			$filetext = htmlentities($filetext);
			$filetime = date ("F d Y H:i:s.", filemtime($filename));
			$uri = $_SERVER['REQUEST_URI'].'&confirm=true';
$out = <<<EOF
<div class="wrap">
	<p>$filename : Last Modified $filetime</p>
	<a class="deleteconfirm button" href="$uri">Click here to DELETE PERMANENTLY</a>
	<pre style="wrap:normal;">$filetext</pre>
</div>
EOF;
		}
		echo $out;
		exit(); 
	}	
	
	function jwwps_delete_option_confirm() {
		$out = "<div class=\"wrap\"><p>Deleting option...";
		if($_GET['confirm'] == 'true' && isset($_GET['option_to_delete'])) {
			if($this->jwwps_delete_option($_GET['option_to_delete'])) { 
				$out.= "<br /><button onClick=\"parent.tb_remove(); parent.location.reload(1)\">Successfully Deleted - Click to Return</button>";
			} else {
				$out.= "<br />Failed to Delete Option! <button onClick=\"parent.tb_remove(); parent.location.reload(1)\">Click to Return</button>";
			}
		} else {
			$out.='Are you sure you wish to delete? <a class="deleteconfirm" href="'.$_SERVER['REQUEST_URI'].'&confirm=true" id="'.$_GET['option_id_delete'].'">Click here to proceed.</a> This cannot be reversed.';
		}
		$out.= "</p></div>";
		echo $out;
		exit();
	}
	
	function jwwps_delete_option($option) {
		if ( ! delete_option($option)) {
			return FALSE;
		} else {
			return TRUE;
		}		
	}

	function jwwps_check_perms($name,$path,$perm) {
	    clearstatcache();
	    $configmod = substr(sprintf(".%o.", fileperms($path)), -4);
	    $trcss = (($configmod != $perm) ? "background-color:#fd7a7a;" : "background-color:#91f587;");
	    $corrected = '';
	    if($configmod != $perm && $_GET['jwwps-run'] == 'true') {
	    	if(chmod($path, octdec($perm))) {
	    		$corrected = ' Permissions Corrected : )';
	    	} else {
	    		$corrected = '<strong>!Permissions NOT Corrected!</strong>';
	    	}
	    }    
	    echo "<tr style=".$trcss.">";
	    echo '<td>'. $path ."</td>";
	    echo '<td>' . $perm . '</td>';
	    echo '<td>' . $configmod . $corrected . '</td>';
	    echo "</tr>";
	    $corrected = '';
	}
	
	function jwwps_find_files($path, $pattern, $callback) {
		$path = rtrim(str_replace("\\", "/", $path), '/') . '/';
		$matches = Array();
		$entries = Array();
		$dir = dir($path);
		while (false !== ($entry = $dir->read())) {
			$entries[] = $entry;
		}
		$dir->close();
		foreach ($entries as $entry) {
			$fullname = $path . $entry;
			if ($entry != '.' && $entry != '..' && is_dir($fullname)) {
				  $this->jwwps_find_files($fullname, $pattern, $callback);
			} else if (is_file($fullname) && preg_match($pattern, $entry)) {
				  call_user_func($callback, $fullname);
			}
		}
	}
	
	function jwwps_filerow_output($filename) {	
		$siteurl = get_option('siteurl');
		$filetime = date ("F d Y H:i:s.", filemtime($filename));
		$fileperms = substr(sprintf(".%o.", fileperms($filename)), -4);
$out = <<<EOF
	<tr><td><a class="thickbox" href="$siteurl/wp-admin/admin-ajax.php?action=jwwps_file_contents&width=600&height=800&file=$filename&keepThis=true&TB_iframe=true" title="View File">
	$filename</a></td><td>Last Modified: $filetime - $fileperms</td><td class="desc"><a class="thickbox button" href="$siteurl/wp-admin/admin-ajax.php?action=jwwps_file_contents&width=600&height=800&file=$filename&keepThis=true&TB_iframe=true" title="View File">Inspect File Contents / Delete</a></td></tr>
EOF;
		echo $out; 
	}
	
	function jwwps_scan(){ ?>
		<table cellspacing="0" class="widefat post fixed">
			<thead>
				<tr>
					<th><b>File/Dir</b></th>
			        <th><b>Target Permissions</b></th>
			        <th><b>Current Permissions</b></th>
			    </tr>
			</thead>
			<tbody>
		    <?php
		    	if($_GET['jwwps_lockdown'] == 'true') {
			    	$this->jwwps_check_perms("installation directory","../","0500");
	    		    $this->jwwps_check_perms("wp-config.php","../wp-config.php",'0400');
			        $this->jwwps_check_perms(".htaccess","../.htaccess","0400");
			        $this->jwwps_check_perms("wp-admin/","../wp-admin","0500");
			        $this->jwwps_check_perms("wp-admin/index.php","../wp-admin/index.php","0400");
			        $this->jwwps_check_perms("wp-admin/js/","../wp-admin/js/","0500");
			        $this->jwwps_check_perms("wp-content/themes/","../wp-content/themes","0500");
			        $this->jwwps_check_perms("wp-content/plugins/","../wp-content/plugins","0500");
			        $this->jwwps_check_perms("wp-content/","../wp-content","0500");
			        $this->jwwps_check_perms("wp-includes/","../wp-includes","0500");
			        if($_GET['jwwps-run'] == 'true') {
						update_option('jwwps_run_lockdown',time());
        				update_option('jwwps_is_lockdown',time());
        				delete_option('jwwps_is_basic');
			        }
        	 	} elseif(get_option('jwwps_is_lockdown') && $_GET['jwwps_restore_basic'] != true) {
			    	$this->jwwps_check_perms("installation directory","../","0500");
	    		    $this->jwwps_check_perms("wp-config.php","../wp-config.php",'0400');
			        $this->jwwps_check_perms(".htaccess","../.htaccess","0400");
			        $this->jwwps_check_perms("wp-admin/","../wp-admin","0500");
			        $this->jwwps_check_perms("wp-admin/index.php","../wp-admin/index.php","0400");
			        $this->jwwps_check_perms("wp-admin/js/","../wp-admin/js/","0500");
			        $this->jwwps_check_perms("wp-content/themes/","../wp-content/themes","0500");
			        $this->jwwps_check_perms("wp-content/plugins/","../wp-content/plugins","0500");
			        $this->jwwps_check_perms("wp-content/","../wp-content","0500");
			        $this->jwwps_check_perms("wp-includes/","../wp-includes","0500");
			        if($_GET['jwwps-run'] == 'true') {
						update_option('jwwps_run_lockdown',time());
        				update_option('jwwps_is_lockdown',time());
        				delete_option('jwwps_is_basic');
			        }
        	 	} else {
		    	    $this->jwwps_check_perms("installation directory","../","0700");
	    		    $this->jwwps_check_perms("wp-config.php","../wp-config.php",'0600');
			        $this->jwwps_check_perms(".htaccess","../.htaccess","0600");
			        $this->jwwps_check_perms("wp-admin/","../wp-admin","0700");
			        $this->jwwps_check_perms("wp-admin/index.php","../wp-admin/index.php","0600");
			        $this->jwwps_check_perms("wp-admin/js/","../wp-admin/js/","0700");
			        $this->jwwps_check_perms("wp-content/themes/","../wp-content/themes","0700");
			        $this->jwwps_check_perms("wp-content/plugins/","../wp-content/plugins","0700");
			        $this->jwwps_check_perms("wp-content/","../wp-content","0700");
			        $this->jwwps_check_perms("wp-includes/","../wp-includes","0700");
				        if($_GET['jwwps-run'] == 'true') {	
	        			update_option('jwwps_run_basic',time());
	        			update_option('jwwps_is_basic', time());
	        			delete_option('jwwps_is_lockdown');	        			
	        		}
			    }
		    ?>
		    </tbody>
		</table>
	<?php
	} 
	
	function jwwps_settings_page () { ?>

	<div class="wrap">
		<div id="icon-tools" class="icon32"></div>
		<h2>JW Cloud Sites WordPress Scanner</h2>
	   	<p>Here are some directories and files with current and recommended permissions. These permissions have worked for me so far on Cloud Sites, I cannot guarantee or speak to the fitness of this approach on other hosts. As with all freebies on the internet, please use at your own risk. Back up and back up often - I recommend <a href="http://www.webdesigncompany.net/automatic-wordpress-backup/">Automatic WordPress Backup</a>. Be sure to check out <a href="http://sucuri.net/">Sucuri</a> monitoring services as well to stay on top of these parasites.</p>
	   	
		<h3>File Permissions<?php 
		if(get_option('jwwps_is_basic')) { 
			echo ' - Basic';
			if($_GET['jwwps_lockdown'] == 'true') {
				echo ' Switching to Lockdown Permissions';
			}
		} elseif(get_option('jwwps_is_lockdown')) {
			echo ' - Lockdown'; 
			if($_GET['jwwps_restore_basic'] == 'true') {
				echo ' Restoring Basic Permissions';
			}
		} ?></h3>
		<?php $this->jwwps_scan(); ?>
		<p><strong>The Basics</strong>: <a href="tools.php?jwwps-run=true&page=jwwps">Click here</a> to correct the permissions of files above if needed.
		<?php if(get_option('jwwps_run_basic')) echo('<br/><em>Basic Permission Set Applied:'.date("F d Y H:i:s.",get_option('jwwps_run_basic')).'</em>');?>		
		</p>
		
		<p><strong>The Lockdown</strong>: <a href="tools.php?jwwps-run=true&page=jwwps&jwwps_lockdown=true">Click here</a> to remove writable permissions for file owner from above. This will prevent automatic upgrades from working, and plugin and theme work. <?php if(get_option('jwwps_is_lockdown')) echo '<a href="tools.php?jwwps-run=true&page=jwwps&jwwps_restore_basic=true">Click here to restore basic permissions.</a>'; ?>
		<?php if(get_option('jwwps_run_lockdown')) echo('<br/><em>Lockdown Permission Set Applied:'.date("F d Y H:i:s.",get_option('jwwps_run_lockdown')).'</em>');?>
		</p>
		
		<p><?php if($_GET['jwwps-run-ww'] == 'true') {
			shell_exec('/bin/chmod -R o-rwx '.ABSPATH.'');
			echo '<strong>rwx removed for others from all files and directories here: '.ABSPATH.'</strong>';
			update_option('jwwps_run_operms',time());
		} else { 
			echo('<strong>The Others</strong>: <a href="tools.php?jwwps-run-ww=true&page=jwwps">Click here</a> to remove rwx for other (chmod -R o-rwx) from <u>all</u> files and directories. This cannot be undone!') ;
			if(get_option('jwwps_run_operms')) echo('<br/><em>Last Run:'.date("F d Y H:i:s.",get_option('jwwps_run_operms')).'</em>');
		} ?></p>
		<p><?php if($_GET['jwwps-run-gw'] == 'true') {
			shell_exec('/bin/chmod -R g-rwx '.ABSPATH.'');
			echo '<strong>rwx bit removed for group from all files and directories here: '.ABSPATH.'</strong>';
			update_option('jwwps_run_gperms',time());
		} else { 
			echo('<strong>The Group</strong>: Take it to the next level? <a href="tools.php?jwwps-run-gw=true&page=jwwps">Click here</a> to remove rwx for group (chmod -R g-rwx) from <u>all</u> files and directories. This cannot be undone!') ;
			if(get_option('jwwps_run_gperms')) echo('<br/><em>Last Run:'.date("F d Y H:i:s.",get_option('jwwps_run_gperms')).'</em>');
		} ?></p>
		
		<h3>Options Table Scan</h3>
		<table cellspacing="0" class="widefat post fixed">
		<thead>
		<tr><th>option_id</th><th>option_name</th><th>option_value</th><th>autoload</th><th>Delete?</th></tr>
		</thead>
		<tbody>
		<?php 
		global $wpdb;
		$siteurl = get_option('siteurl');
		$optionscan = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE (option_id LIKE '%base64_decode%' OR blog_id LIKE '%base64_decode%' OR option_name LIKE '%base64_decode%' OR option_value LIKE '%base64_decode%' OR autoload LIKE '%base64_decode%' OR option_id LIKE '%edoced_46esab%' OR blog_id LIKE '%edoced_46esab%' OR option_name LIKE '%edoced_46esab%' OR option_value LIKE '%edoced_46esab%' OR autoload LIKE '%edoced_46esab%' OR option_name LIKE 'wp_check_hash' OR option_name LIKE 'class_generic_support' OR option_name LIKE 'widget_generic_support' OR option_name LIKE 'ftp_credentials' OR option_name LIKE 'fwp' OR option_name LIKE 'rss_%') order by option_id");
		if($optionscan) {
			foreach ($optionscan as $optionrow) {
				echo '<tr id="'.$optionrow->option_id.'"><td>'.$optionrow->option_id.'</td><td>'.$optionrow->option_name.'</td><td>'.$optionrow->option_value.'</td><td>'.$optionrow->autoload.'</td><td><a class="thickbox button" href="'.$siteurl.'/wp-admin/admin-ajax.php?action=jwwps_delete_option_confirm&width=400&height=300&option_to_delete='.$optionrow->option_name.'&option_id_delete='.$optionrow->option_id.'&keepThis=true&TB_iframe=true" title="Delete Option" style="margin-top:5px;">Delete Option</a></td></tr>';
			} 
		} else { ?>
			<tr><td colspan="3"><p>Lucky you, no known bad options containing base64_decode (or edoced_46esab) found.</p></td></tr>
		<?php }	?>
		</tbody>
		</table>
		
		<h3>Post Table Scan</h3>
		<table cellspacing="0" class="widefat post fixed">
		<thead>
		<tr><th>ID</th><th>post_title / status</th><th>post_content</th></tr>
		</thead>
		<tbody>
		<?php 
		global $wpdb;
		$postscan = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE (post_content LIKE '%base64_decode%' OR post_content LIKE '%<script%' OR post_content LIKE '%edoced_46esab%' OR post_content LIKE '%visibility:%' OR post_content LIKE '%display:%' OR post_content LIKE '%visibility :%' OR post_content LIKE '%display :%' OR post_content LIKE '%<iframe%')");
		if($postscan) {
			foreach ($postscan as $postrow) {
				echo '<tr><td><a href="post.php?post='.$postrow->ID.'&action=edit">'.$postrow->ID.'</a></td><td>'.$postrow->post_title.' / '.$postrow->post_status.'</td><td><pre style="white-space:normal;">'.htmlentities($postrow->post_content).'</pre></td></tr>';
			}
		} else { ?>
			<tr><td colspan="3"><p>Lucky you, no posts containing base64_decode (or edoced_46esab), script tags, hidden css elements or iframes found.</p></td></tr>
		<?php }	?>
		</tbody>
		</table>
		
		<h3>Hidden File Scan</h3>
		<table cellspacing="0" class="widefat post fixed">
		<thead>
		<tr><th>File</th><th></th><th></th></tr>
		</thead>
		<tbody>
		<?php $pattern = '/(\.bak|\.cache|\.old|\.jpg|\.gif|\.png|\.pdf|\.js|class-rss)\.php$/';
		$this->jwwps_find_files(ABSPATH, $pattern, array(&$this,'jwwps_filerow_output'));
		$deleted = get_option('jwwps_file_delete');
		if($deleted) {
			$deletes = explode(',', $deleted);
			echo '<tr><td colspan="3"><p>Files previously deleted:<br />';
			foreach($deletes as $delete) {
				echo $delete.'<br />';
			}
			echo '</p></td></tr>';
		}
		?>
		</tbody>
		</table>
		
		<h3>File List</h3>
		<table cellspacing="0" class="widefat post fixed">
			<thead>
				<tr><th><?php echo ABSPATH;?></th></tr>
			</thead>
			<tbody>
				<tr><td><p>Here's a big old listing of all your files and directories. Review for suspicious files, modification dates, file owners, permissions, etc.</p></td></tr>
				<tr><td><div class="scroll" style="height:500px; overflow:scroll;">
<pre><?php passthru('ls '.ABSPATH.' -lR');?></pre>
				</div></td></tr>
			</tbody>
		</table>
	</div>
	<?php }
} ?>