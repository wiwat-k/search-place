<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class GoogleMapRestaurant extends Controller
{


	private $googleUrl = "https://maps.googleapis.com/maps/api/place/textsearch/json?"; // Google API Url
	private $type = "restaurant"; // Type place search, see more detail in google map api place type

	public function search(Request $request)
	{
		$textSearch = '';
		// Check Text Search Not Empty
		if (!empty($request->input('text'))) {
			$textSearch = $request->input('text');
		}
		$gkey = $request->input('gkey'); // get google key api
		$cache = $textSearch.$request->input('pagetoken'); // Cache By text search + pagetoken
		if (Cache::has($cache)) { // Check Cache exist
			$response = Cache::get($cache);
			$response['cache'] = true;
		} else {
			// set Parameters
			$data = array(
				'key' => $gkey,
				'type' => $this->type,
				'query' => $textSearch
			);
			if(!empty($request->input('pagetoken'))) { //Check Page if pageToekn empty is first page else next page
				$data['pagetoken'] = $request->input('pagetoken');
			}
			$params = http_build_query($data); // Parameters to url
			$json = json_decode(file_get_contents($this->googleUrl . $params), true); // Get Google Map API Response json
			$response = $json; // Response Json
			Cache::put($cache, $response, 30); // Cache Response Google Map API 30 Minutes
			$response['cache'] = false;
		}
		return $response;
	}
}
