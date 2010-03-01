<?php 
class Tx_SugarMine_Utils_Debug 
{
	public static function dump($var)
	{
		echo '<div style="background:#fff; padding:1em; white-space:pre;">';
		var_dump($var);
		echo '</div>';
	}
}