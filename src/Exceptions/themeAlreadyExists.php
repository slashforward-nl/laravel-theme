<?php namespace Igaster\LaravelTheme\Exceptions;

class ThemeAlreadyExists extends \Exception{

	public function __construct($theme) {
		parent::__construct("Theme {$theme->name} already exists", 1);
	}

}