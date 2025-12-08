<?php

require_once('path.inc');              
require_once('get_host_info.inc');     
require_once('rabbitMQLib.inc');       


// This function is supposed to handle incoming requests made by MQ related to movies
function requestProcessor($request)
{
    echo "Received movie request:\n"; 
    print_r($request); 


    // Client ID is from Trakt where it is grabbing auth. API requests
    $Client_ID = "d8f75848dca47e56ee15ccbc8658a084800c3c101d56e3ae0f2d40278dfb8943";


    // checks that a type is actually being sent, otherwise request is not valid
    if (!isset($request['type'])) {
        return ['returnCode' => 0, 'message' => 'Missing request type'];
    }



    // function for searching different movies
    if ($request['type'] === 'movieSearch') {

        // would essentially confirm that a movie search query is being requested
        if (empty($request['query'])) {
        return ['returnCode' => 0, 'message' => 'No search query provided'];
        }

        // urlencode to securly include search query in the API url
        $query = urlencode($request['query']);

        // pagination 
        $page  = 1;
        $limit = 100;   // limit can be changed later

        // Trakt movie search endpoint with full details
        $url = "https://api.trakt.tv/search/movie?query=$query&page=$page&limit=$limit&extended=full";

        // cURL to send requests to the api, auth of what ID being used, and sending http requests to api
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "trakt-api-version: 2",     
                "trakt-api-key: $Client_ID", 
                "User-Agent: MovieHub/1.0"
            ]
        ]);

        // API response capture + status checking
        $response= curl_exec($ch);
        $code= curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error= curl_error($ch);
        curl_close($ch); 

        // if the api call failed it would return error message  
        if ($code !== 200 || !$response) {
            return [
                'returnCode' => 0,'message'    => 'Failed to search movies: ' . ($error ?: "HTTP $code")
            ];
        }

        
        $movies = json_decode($response, true);

        // Return the data to the requester
        return [
            'returnCode'=> 1,
            'message'=> 'Movie search successful',
            'data'=> $movies
        ]; 
    }



    // browser feature needed so using trending movies endpoint API 
    if ($request['type'] === 'trendingMovies') {

        // pagination i
        $page  = $request['page']  ?? 1;
        $limit = $request['limit'] ?? 20;

        // trending movies API from trakt 
        $url = "https://api.trakt.tv/movies/trending?limit=$limit&page=$page&extended=full";

        // using cURL method for API endpoint
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "trakt-api-version: 2",
                "trakt-api-key: $Client_ID",
                "User-Agent: MovieHub/1.0"
            ]
        ]);

        // API requests executed
        $response= curl_exec($ch);
        $code= curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error= curl_error($ch);
        curl_close($ch);

        // same thing previous, returns error message if fetching failed
        if ($code !== 200 || !$response) {
            return [
                'returnCode' => 0,'message'    => 'Failed to fetch trending movies: ' . ($error ?: "HTTP $code")
            ];
        }

        // json decoding for movies
        $movies = json_decode($response, true);

        // return message for trending movies
        return [
            'returnCode'=> 1,
            'message'=> 'Trending movies fetched successfully',
            'data'=> $movies
        ];
    }



    // upcoming movie releases feature for anticipated movies page
    if ($request['type'] === 'upcomingMovies') {

        // pagination 
        $page  = $request['page']  ?? 1;
        $limit = $request['limit'] ?? 20;

        //upcoming movies from trakt
        $url = "https://api.trakt.tv/movies/anticipated?limit=$limit&page=$page&extended=full";

        // using cURL method for anticipated movies
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "trakt-api-version: 2",
                "trakt-api-key: $Client_ID",
                "User-Agent: MovieHub/1.0"
            ]
        ]);

        // API requests executed
        $response= curl_exec($ch);
        $code= curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error= curl_error($ch);
        curl_close($ch);

        // error returned if call fails
        if ($code !== 200 || !$response) {
            return [
                'returnCode'=> 0,
                'message'=> 'Failed to fetch upcoming movies: ' . ($error ?: "HTTP $code")
            ];
        }

        // decode and clean up the response into smaller structure
        $raw = json_decode($response, true);
        $clean= [];

        foreach ($raw as $entry) {

            // each entry should have a movie object, otherwise skip
            if (!isset($entry['movie'])) continue;

            $m = $entry['movie'];

            // push cleaned movie info into array
            $clean[] = [
                'id'=> $m['ids']['trakt'],
                'title'=> $m['title'],
                'year'=> $m['year'],
                'release_date'=> $m['released'] ?? "TBA"
            ];
        }

        // final cleaned list returned back to requester
        return [
            'returnCode'=> 1,
            'data'=> $clean
        ];
    }



    // invalid request type if none of the above types matched
    return ['returnCode' => 0, 'message' => 'Invalid request type'];
}



// referencing MQ connection 
$server = new rabbitMQServer("movieRabbitMQ.ini", "DMZMovieServer");
echo "DMZ Movie Worker listening \n";
$server->process_requests('requestProcessor');
?>

