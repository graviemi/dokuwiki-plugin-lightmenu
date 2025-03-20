<?php

require_once __DIR__.'/lightmenu.class.php';

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

class action_plugin_lightmenu extends ActionPlugin
{
	public function register(EventHandler $controller)
	{
		$controller->register_hook('COMMON_WIKIPAGE_SAVE', 'BEFORE', $this, 'lm_update');
	}

	// take care of updating lightmenu meta data and sidebar cache on wiki page save.
	public function lm_update($event)
	{
		if ($event->data['contentChanged'])
			lightmenu::update($event->data['id'],$event->data['newContent']);
	}
}
