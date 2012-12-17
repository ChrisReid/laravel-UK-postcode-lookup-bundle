<?php

Autoloader::map(array(
	'Postcode' => Bundle::path('postcode').'postcode.class.php',
));

Validator::register('uk_postcode', function($attribute, $value) // TODO Is this the best place to put it?
{
    return Postcode::is_valid_uk($value);
});