<?php

require_once __DIR__.'/lightmenu.class.php';

use dokuwiki\Extension\SyntaxPlugin;

class syntax_plugin_lightmenu extends SyntaxPlugin
{
	public function getType()
	{
		return 'substition';
	}

	public function getPType()
	{
		return 'block';
	}

	public function getSort()
	{
		return 121;
	}

	public function connectTo($mode)
	{
		$this->Lexer->addSpecialPattern('<lightmenu[^>]*>', $mode, 'plugin_lightmenu');
		$this->Lexer->addSpecialPattern('<lm:[^>]*>', $mode, 'plugin_lightmenu');
	}

	public function handle($match, $state, $pos, Doku_Handler $handler)
	{
		global $conf;

		if (preg_match('|^<lightmenu([^>]*)>$|',$match,$matches))
			return lightmenu::get_data($matches[1]);

		return null;
	}

	public function render($format, Doku_Renderer $renderer, $data)
	{
		if ($data === null)
			return false;
		if ($format == 'xhtml')
		{
			$renderer->doc .= lightmenu::render($data);
			return true;
		}
		return false;
	}
}
