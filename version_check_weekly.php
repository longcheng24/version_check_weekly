<?php
/*
Plugin Name: Wordpress Weekly Version Check
Plugin URI: 
Description: Check wordpress, plugins and theme versions very week and send it to email
Author: Long Cheng
Version: 1.0.0
Author URI: longcheng24.com
Text Domain: 
Domain Path: 
*/
?>
<?
/*  Copyright 2014 Long Cheng 

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

?>
<?


function weeklyCheck(){
			define('ALTERNATE_WP_CRON', true);
			
			function wpjam_more_reccurences() {
			  return array(
				'weekly' => array(
				'interval' => 604800, 
				'display' => 'Once Weekly'),
			  );
			}
			
			add_filter('cron_schedules', 'wpjam_more_reccurences');	
			
			register_activation_hook(__FILE__, 'mailVersion');
			
			if (!wp_next_scheduled('mailVersion')) {
				wp_schedule_event( time(), 'weekly', 'mailVersion' );
			}	
			
			add_action( 'mailVersion', 'weeklyVesionUpdate');
			
			function weeklyVesionUpdate(){
				//Table To Store The Version Information
				$tablestr = "<html><head></head><body><table style='width:90%;'><tr><th style='width:50%;'>Plugin Status</th><th style='width:25%;'>Version</th><th style='width:25%;'>New Version</th></tr>";
				
				//Plugs Versions
				$plugininfo = get_site_transient('update_plugins');
				$allPlugins = get_plugins();
				$updatePlugins = $plugininfo->response;
				foreach($allPlugins as $k=>$v){
					$pName = $k;
					if(!empty($v['Version'])){
						$pVersion = "<td>".$v['Version']."</td>";
					}else{
						$pVersion = "<td style='color:#FF0040;'>No Version Number</td>";	
					}
					$pNewVersion;
					if(array_key_exists($k,$updatePlugins)){
						
						$pNewVersion = "<td style='color:#0101DF;background:#6e6e6e;'>".$updatePlugins[$k]->new_version."</td>";
					}else{
						$pNewVersion = $pVersion;
					}
					$tablestr .= "<tr><td>{$pName}</td>{$pVersion}{$pNewVersion}</tr>";
				}
				
				//Wordpress API and Json To Get The Wordpress Latest Version
				$url = 'https://api.wordpress.org/core/version-check/1.7/';
				$response = wp_remote_get($url);
				$json = $response['body'];
				$obj = json_decode($json);
				$upgrade = $obj->offers[0];
				$wp_version = get_bloginfo('version');
				if($wp_version != $upgrade->version){
					$tablestr .= "<tr><th>WordPress Status</th><th>Version</th><th>New Version</th></tr><tr><td>WordPress</td><td>{$wp_version}</td><td style='color:#0101DF;background:#6e6e6e;'>{$upgrade->version}</td></tr>";
				}else{
					$tablestr .= "<tr><th>WordPress Status</th><th>Version</th><th>New Version</th></tr><tr><td>WordPress</td><td>{$wp_version}</td><td>{$upgrade->version}</td></tr>";
				}
				
				//Themes Version
				$tablestr .= "<tr><th>Themes Status</th><th>Version</th><th>New Version</th></tr>";
				$themeinfo = get_site_transient('update_themes');
				$allThemes = $themeinfo->checked;
				$updateThemes = $themeinfo->response;
				foreach($allThemes as $k=>$v){
					$tName = $k;
					$tVersion = $v;
					$tNewVersion;
					if(array_key_exists($k,$updateThemes)){
						$tNewVersion = "<td style='color:#0101DF;background:#6e6e6e;'>".$updateThemes[$k]["new_version"]."</td>";
					}else{
						$tNewVersion = "<td>".$v."</td>";
					}
					$tablestr .= "<tr><td>{$tName}</td><td>{$tVersion}</td>{$tNewVersion}</tr>";
				}
				
				//Output Table
				$tablestr .="</table></body></html>";
				/*echo $tablestr;*/
				
				//Email
				
				$mailto = "ooxx@ooxx.com";
				
				$blogtitle = get_bloginfo('name');
				
				$mailsub = $blogtitle." Versions Info";
				
				$mailcontent = $tablestr;
				
				$mailheader = "Content-type: text/html; charset=iso-8859-1/r/n";
				
				mail($mailto,$mailsub,$mailcontent,$mailheader);	
				
			}
			
			register_deactivation_hook(__FILE__, 'my_deactivation');
			
			function my_deactivation() {
				wp_clear_scheduled_hook('mailVersion');	
			}

}

add_action( 'init', 'weeklyCheck');

?>