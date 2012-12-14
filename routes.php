<?php

Route::get('(:bundle)/(:any)/(:any?)', function($postcode, $house_number = null)
{
	return Response::json(Postcode::is($postcode)->get_address($house_number));
});
