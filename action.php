<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

class action_plugin_lightmenu extends ActionPlugin
{
	public function register(EventHandler $controller)
	{
		$controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'touch');
	}

	public function touch($event)
	{
		global $conf;

		$path = $conf['datadir'].'/sidebar.txt';
		if (! is_readable($path))
			return;

/*		if (($event->data['changeType'] === DOKU_CHANGE_TYPE_CREATE)
			|| ($event->data['changeType'] === DOKU_CHANGE_TYPE_DELETE))*/
			touch($path);
	}
}
