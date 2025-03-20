<?php

require_once __DIR__.'/lightmenu.class.php';

use dokuwiki\Extension\AdminPlugin;

class admin_plugin_lightmenu extends AdminPlugin
{
	public function getMenuText($language)
	{
		return 'Lightmenu';
	}

	public function getMenuSort()
	{
		return 2048;
	}

	public function handle()
	{
		global $INPUT;
 
		if (! $INPUT->has('rescan'))
			return;
 		$this->output = 'invalid';
 		if (! checkSecurityToken())
			return;
		if (! is_string($INPUT->param('rescan')))
			return;
 		lightmenu::rescan();
	}
 
	public function html()
	{
		global $ID;

		printf('<h1>%s</h1>',$this->getLang('admin-title'));
		printf('<form action="%s" method="post">'.PHP_EOL,wl($ID));
		echo '<input type="hidden" name="do" value="admin" />'.PHP_EOL;
		printf('<input type="hidden" name="page" value="%s" />'.PHP_EOL,$this->getPluginName());
		formSecurityToken();
 		printf('<p><button type="submit" name="rescan" value="true" />%s</button>'.PHP_EOL,$this->getLang('rescan'));
 		printf(' %s</p>'.PHP_EOL,$this->getLang('rescan-help'));
		echo '</form>'.PHP_EOL;
	}
 
}

