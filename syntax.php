<?php

use dokuwiki\Extension\SyntaxPlugin;

class syntax_plugin_lightmenu extends SyntaxPlugin
{
	protected $_lm_id = '';
	protected $_lm_start = null;

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
		return 138;
	}

	public function connectTo($mode)
	{
		$this->Lexer->addSpecialPattern('<lightmenu>', $mode, 'plugin_lightmenu');
	}

	protected function _browse(string $path) : array
	{
		$tree = [[],[]];
		$list = scandir($path);
		foreach ($list as $name)
		{
			if (($name === '.') || ($name === '..'))
				continue;
			$filepath = $path.'/'.$name;
			if (is_dir($filepath))
				$tree[0][$name] = $this->_browse($filepath);
			elseif ($name === $this->_lm_start)
				continue;
			elseif (is_file($filepath)
				&& (substr($name,-4) === '.txt'))
			{
				$label = substr($name,0,-4);
				if (is_dir($path.'/'.$label) || ($label === basename($path)))
					continue;
				$tree[1][$label] = null;
			}
		}
		ksort($tree[0]);
		ksort($tree[1]);
		return $tree;
	}

	public function handle($match, $state, $pos, Doku_Handler $handler)
	{
        global $conf;

		$this->_lm_start = $conf['start'].'.txt';
		return $this->_browse($conf['datadir']);
	}

	protected function _html(array $data, string $prefix = '', int $level = 0) : string
	{
		$html = '';
//		$html .= sprintf('<pre>%s</pre>',print_r($data,true));
		foreach ($data[0] as $name => $children)
		{
			$html .= '<div class="child">'.PHP_EOL;
			$html .= sprintf('<input type="checkbox" id="lm-%s%s" />',$prefix,$name);
			$html .= sprintf('<label class="label" for="lm-%s%s"><a href="doku.php?id=%s%s:">%s</a></label>'.PHP_EOL,$prefix,$name,$prefix,$name,trim($name));
			if (count($children) > 0)
				$html .= '<div class="tree">'.PHP_EOL.$this->_html($children,$prefix.$name.':').'</div>'.PHP_EOL;
			$html .= '</div>'.PHP_EOL;
		}
		foreach ($data[1] as $name => $null)
		{
			if (($prefix === '') && ($name === 'sidebar'))
				continue;
			$html .= '<div class="child">'.PHP_EOL;
			$html .= sprintf('<span class="label" id="lm-%s%s"><a href="doku.php?id=%s%s">%s</a></span>'.PHP_EOL,$prefix,$name,$prefix,$name,trim($name));
			$html .= '</div>'.PHP_EOL;
		}
		return $html;
	}

	public function render($format, Doku_Renderer $renderer, $data)
	{
		global $conf, $ID;

		$this->_lm_id = $ID;
		$this->_lm_start = $conf['start'];
		if ($format == 'xhtml') {
			$renderer->doc .= '<div class="lm"><div class="tree">';
			$renderer->doc .= sprintf('<div class="child"><span class="label" id="lm-%s"><a href="doku.php">%s</a></span></div>',$this->_lm_start,$this->_lm_start);
			$renderer->doc .= $this->_html($data);
			$renderer->doc .= '</div></div>';
			return true;
		}
		return false;
	}
}
