# Laravel Postcode Lookup

A simple Postcode lookup Bundle using Google API

# Installation

You can install this bundle by running the following CLI command:

    php artisan bundle:install postcode
    

Alternatively you can download it directly from GitHub:

    http://github.com/chrisreiduk/laravel-UK-postcode-lookup-bundle
    

Add the following to bundles.php :

    'postcode' => array('auto'  => true, 'handles' => 'postcode'),
    

# Usage

    Postcode::default_region('{any google supported region}'); // This line is not necessary for the UK. Also you only need to set the default region once.
    
    $address = Postcode::is('{postcode}, {region for this call only - optional}')->get_address('{house_name_or_number -optional}');
    
    or
    
    $address = Postcode::lat_lng_is('{latitude}', {'longitude'})->get_address('{house_name_or_number(optional)}');
    

(Note: I have noticed that region setting is not always necessary; it seemss that sometimes Google API works it out from the format of the postcode)

# Example

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
    

or alternatively the more traditional objective way:

    $postcode = new Postcode('{postcode}, {region for this call only - optional}');
    
    $address = $postcode->get_address('{house_name_or_number(optional)}');
    

You can also access the properties directly:

    $postcode->address1
    $postcode->address2
    $postcode->address3
    $postcode->city
    $postcode->county
    $postcode->country
    $postcode->postcode
    

# Format a Postcode

A useful method which doesn't use an HTTP request:

    echo Postcode::format('w1w8rx'); // Returns W1W 8RX
    
    or format it for database storage or URI insertion:
    
    echo Postcode::format('W1W 8RX'); // Returns w1w8rx
    

# Ajax

Send your Ajax request to eg www.yourwebapp.com/postcode/{postcode}/{house\_number\_or_name(optional)}

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
    

# Updates

##UK Postcode Validation

There is now a useful validation feature to check for a valid UK postcode format using regex.

    Postcode::is_valid_uk('m320rl') // returns true;
    Postcode::is_valid_uk('m32qrl') // returns false;

It also extends Laravel's validator so you can just add the rule 'uk_postcode' to the postcode field.

##Reverse Lookup

You can now look up postcodes by address:

    Postcode::address_is('12 mountbatten road, norfolk')->get_address();

or with $auto_address = true, it will also work as follows:

    Postcode::is('12 mountbatten road, norfolk')->get_address();

if $auto_address = false, the latter query would result in an error as it's not a postcode.

##The future

Does anyone want to add a regex to validate postcodes in their region?


# Contact

You can email me with any questions or feedback at chrisreiduk@gmail.com
