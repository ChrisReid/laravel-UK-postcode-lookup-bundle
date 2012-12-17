<?php

Route::get('(:bundle)/(:any)/(:any?)', function($postcode, $house_number = null)
{
	//Postcode::default_region('{google supported region}'); // For UK postcodes you can delete this line as the class will default to UK
	return Response::json(Postcode::is($postcode, null, true)->get_address($house_number));
});
