# Gateway Sample PHP Code
This is a sample application to help developers start building PHP applications using the Payment Gateway.

## Steps for running on Heroku
1. Obtain an account with your Gateway provider
1. Register with [Heroku](https://www.heroku.com)
1. Click this button [![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)
1. Configure the app with your TEST GATEWAY_BASE_URL, GATEWAY_MERCHANT_ID and GATEWAY_API_PASSWORD
1. Visit the landing page of the newly deployed app for more details

## Steps for running locally
1. Download code
2. Set the following ENV variables using:
    - export GATEWAY_BASE_URL=*INSERT_YOUR_GATEWAY_URL_HERE* GATEWAY_MERCHANT_ID=*INSERT_YOUR_GATEWAY_MERCHANT_ID_HERE* GATEWAY_API_PASSWORD=*INSERT_YOUR_GATEWAY_API_PASSWORD_HERE* GATEWAY_DEFAULT_CURRENCY=*INSERT_YOUR_CURRENCY_HERE* GATEWAY_API_VERSION=*INSERT_YOUR_GATEWAY_VERSION_HERE*
    - *GATEWAY_DEFAULT_CURRENCY and GATEWAY_API_VERSION are optional*. If they aren't set, default values from settings.php will be used
3. To run the application in development, run these commands. Make sure you have composer in your PATH

    	composer install
    	composer start

    Use this command to run the test suite

    	composer test
4.  Navigate to *http://localhost:8080* to test locally

## Authentication
1.  **Certificate Authentication:**
    1. Please refer CERT_AUTH.md for details
2.  **API Key: **
    - You can grab the API Key from merchant portal and set the env variable GATEWAY_API_PASSWORD to that value.     

##Versions
    
1. Look at  composer.json for all plugin versions. 
        
    - php >=5.5.0
  
    - jquery 2.2.4
        
    - jquery-ui 1.11.4
        
    - slim ^3.1
              
        

## Disclaimer
* This software is intended for **TEST/REFERENCE** purposes **ONLY** and is not intended to be used in a production environment.