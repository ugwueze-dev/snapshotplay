<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use Google\Client as Google_Client;

class GoogleConnectors
{
    public static function connectToGoogleAndroidPublisher($serviceAccountKeyFilePath, $productID, $packageName, $platformProductID, $purchaseToken)
    {
        if (file_exists($serviceAccountKeyFilePath)) {
            $client = new Google_Client();
            $client->setAuthConfig($serviceAccountKeyFilePath);
            $scope = 'https://www.googleapis.com/auth/androidpublisher';
            $client->addScope($scope);
        } else {
            echo "Error: File does not exist at $serviceAccountKeyFilePath";
            return null;
        }

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/androidpublisher',
            $serviceAccountKeyFilePath
        );
        $middleware = new AuthTokenMiddleware($credentials);
        $stack = HandlerStack::create();
        $stack->push($middleware);
        
        // Constructing URL based on productID
        if ($productID == PLAYER_TOKEN || $productID == HOST_TOKEN) {
            $url = "$packageName/purchases/products/$platformProductID/tokens/$purchaseToken";
        } else {
            // If subscription, verify through Google
            $url = "$packageName/purchases/subscriptions/$platformProductID/tokens/$purchaseToken";
        }

        // Store the constructed URL in a variable
        $requestUrl = 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/' . $url;

        return new Client([
            'handler' => $stack,
            'base_uri' => 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/',
            'auth' => 'google_auth', // authorize all requests
            'url' => $requestUrl // Set the constructed URL
        ]);
    }
}
