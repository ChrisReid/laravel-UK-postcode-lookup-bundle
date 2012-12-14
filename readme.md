Laravel UK Postcode Lookup
==========================

A simple UK Postcode lookup Bundle using Google API


Installation
============
Copy the whole folder inside your Bundles folder. Add the following to bundles.php :

	'postcode' => array('auto'  => true, 'handles' => 'postcode'),


Usage
=====

	$address = Postcode::is('{postcode}')->get_address('{house_name_or_number(optional)}');

	or

	$address = Postcode::lat_lng_is('{latitude}', {'longitude'})->get_address('{house_name_or_number(optional)}');


Example
=======

	$address = Postcode::is('w1e8rx')->get_address('21');

	returns:

		array(1) {
		["result"]=>
			array(10) {
			["company_name"]=>
			string(0) ""
			["address1"]=>
			string(18) "21 Margaret Street"
			["address2"]=>
			string(0) ""
			["address3"]=>
			string(0) ""
			["city"]=>
			string(6) "London"
			["county"]=>
			string(14) "Greater London"
			["country"]=>
			string(7) "England"
			["postcode"]=>
			string(7) "W1W 8RX"
			["lat"]=>
			float(51.5167305)
			["lng"]=>
			float(-0.1417548)
			}
		}

or alternatively:

	$postcode = new Postcode('{postcode}');

	$address = $postcode->get_address('{house_name_or_number(optional)}');


You can also access the properties directly:

	$postcode->address1
	$postcode->address2
	$postcode->address3
	$postcode->city
	$postcode->county
	$postcode->country
	$postcode->postcode


Format a Postcode
=================
A useful method which doesn't use an HTTP request:

	echo Postcode::format('w1w8rx'); // Returns W1W 8RX

	or format it for database storage or URI insertion:

	echo Postcode::format('W1W 8RX'); // Returns w1w8rx


Ajax
====
Send your Ajax request to eg www.yourwebapp.com/postcode/{postcode}/{house_number_or_name(optional)}

For an alpha house name, ensure your javascript replaces spaces with underscores.

Example JQuery:

	.on('#postcode_lookup_button', 'click', function () {
        var postcode = $('#postcode').val().replace(' ', '');
        var house_number = $('#house_number').val().replace(' ', '_');
        if (postcode.length < 6) {
            alert('too short');
            return;
        }
        $.getJSON(baseURL + '/postcode/' + postcode + '/' + house_number, function (data) {
            if (data.error) {
                alert(data.error);
            } else if (data.result) {
                $.each(data.result, function (key, value) {
                    $('#' + key).val(value);
                });
            }
    	});
    });


The Future
==========
Perhaps a useful UK Postcode validation method using regex to add to the wonderful Laravel Validation model. Any contributions appreciated.










