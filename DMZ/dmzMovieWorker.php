#!/usr/bin/php
<?php

require_once('path.inc');              
require_once('get_host_info.inc');     
require_once('rabbitMQLib.inc');       



// This function is supposed to handle incoming requests made by MQ related to movies
function requestProcessor($request)
{
  echo "Received movie request:\n"; // echo for debugging or catching where errors may occer
    print_r($request); 




// Client ID is from Trakt where it is grabbing auth. API requests
$Client_ID = "d8f75848dca47e56ee15ccbc8658a084800c3c101d56e3ae0f2d40278dfb8943";
 
    // function for searching different movies
if (isset($request['type']) && $request['type'] === 'movieSearch') {
        // would essentially confirm that a movie search query is being requested
       if (!isset($request['query'])) {
	return ['returnCode' => 0, 'message' => 'No search query provided'];}


        // urlencode to securly include search query in the API url
$query = urlencode($request['query']);
$url = "https://api.trakt.tv/search/movie?query=$query&limit=10";

        
        // cURL to send requests to the api, auth of what ID being used, and sending http requests to api
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "trakt-api-version: 2",     
            "trakt-api-key: $Client_ID", 
            "User-Agent: MyMovieApp/1.0" 
        ]);

        
        $response = curl_exec($ch);
        $Code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 

        // if the api call failed it would return error message  
        if ($Code !== 200 || !$response) {
            return ['returnCode' => 0, 'message' => 'Failed to fetch movies'];
        }

        // Decode JSON response from Trakt API
        $movies = json_decode($response, true);

        // Return the data to the requester
        return [
            'returnCode' => 1,
            'message' => 'Movie search successful',
            'data' => $movies
        ]; 
        }

    // browser feature needed so using trending movies endpoint API 
    if (isset($request['type']) && $request['type'] === 'trendingMovies') {
        // pagination if required
        $page = isset($request['page']) ? (int)$request['page'] : 1;
        $limit = isset($request['limit']) ? (int)$request['limit'] : 20;

        // trending movies API from trakt
        $url = "https://api.trakt.tv/movies/trending?limit=$limit&page=$page";

        // using cURL method for grabbing trending movies to use for the browse feature
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "trakt-api-version: 2",
            "trakt-api-key: $Client_ID",
            "User-Agent: MyMovieApp/1.0"
        ]);


        // API requests executed
        $response = curl_exec($ch);
        $Code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // same thing previous, returns error message if fetching failed
        if ($Code !== 200 || !$response) {
            return ['returnCode' => 0, 'message' => 'Failed to fetch trending movies']; }

        // json decoding for movies
        $movies = json_decode($response, true);

        // return message for trending movies
        return [
            'returnCode' => 1,
            'message' => 'Trending movies fetched successfully',
            'data' => $movies
        ];	}

    return ['returnCode' => 0, 'message' => 'Invalid request type']; }

// referencing MQ connection 
$server = new rabbitMQServer("movieRabbitMQ.ini", "DMZMovieServer");

// message when the requests are being picked up , basically if this message is showing that means MQ is acknowledging the requests
echo "DMZ Movie Worker listening \n";

// processing incoming MQ requests using handler
$server->process_requests('requestProcessor');
?>
