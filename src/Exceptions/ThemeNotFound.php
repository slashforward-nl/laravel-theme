<?php namespace Slashforward\LaravelTheme\Exceptions;

class ThemeNotFound extends \Exception{

	public function __construct($themeName) {
		parent::__construct("Theme $themeName not Found", 1);
	}

}