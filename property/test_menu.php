<?php

	$GLOBALS['phpgw_info'] = array();

	$GLOBALS['phpgw_info']['flags'] = array
	(
		'noheader'                => true,
		'nonavbar'                => false,
		'currentapp'              => 'home',
		'enable_network_class'    => true,
		'enable_contacts_class'   => true,
		'enable_nextmatchs_class' => true
	);


	include_once('../header.inc.php');

// Start-------------------------------------------------

	phpgw::import_class('phpgwapi.yui');
	$GLOBALS['phpgw']->css->add_external_file('phpgwapi/js/yahoo/examples/treeview/assets/css/folders/tree.css');
	phpgwapi_yui::load_widget('treeview');
	$GLOBALS['phpgw']->js->validate_file( 'yahoo', 'test.menu', 'property' );

		$currentapp = $GLOBALS['phpgw_info']['flags']['currentapp'];
		$applications = array();
		$exclude = array('home', 'preferences', 'about', 'logout');
		$navbar = execMethod('phpgwapi.menu.get', 'navbar');

		$i = 1;
		foreach ( $navbar as $app => $app_data )
		{
			if ( in_array($app, $exclude) )
			{
				continue;
			}
			if ( $app == $currentapp)
			{
				$app_data['text'] = "[<b>{$app_data['text']}</b>]";
			}

			$applications[] = array
			(
				'text' => $app_data['text'],
				'href'	=> $app_data['url'],
			);
			$mapping[$i] = $app;
			$i ++;
		}
		$applications = json_encode($applications);
		$mapping = json_encode($mapping);

$html = <<<HTML
							<div id="treeDiv1"></div>
								<!-- Some style for the expand/contract section-->
								<style>
									#expandcontractdiv {border:1px dotted #dedede; margin:0 0 .5em 0; padding:0.4em;}
									#treeDiv1 { background: #fff; padding:1em; margin-top:1em; }
								</style>
								<script type="text/javascript">
								   var apps = {$applications};
								   var mapping = {$mapping};
								</script>
								<div id="treeDiv1"/>

HTML;



// End--------------------------------------------------

	$GLOBALS['phpgw']->common->phpgw_header();
	echo parse_navbar();

	echo $html;


	$GLOBALS['phpgw']->common->phpgw_footer();


