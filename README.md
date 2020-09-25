# Mobile Authentication Sample with PC
This is the sample to show how to implement authentication for mobile application with Airome's PayConfirm solution.

## Scenarios description
Before authentication process mobile application must be personalized.

So, this sample realizes 2 scenarios:
1. Mobile app personalization
2. Authentication of personalized mobile app

## How to launch
The sample consists of 2 parts:
1. Web-app that emulates your mobile application back-end
2. Mobile application

### Web App
Pre-requisites:
1. Apache2 or another HTTP-server
2. php-7.0 or newer
3. php-curl extension
4. HTTP-server must have permissions to write to `/tmp` directory (see `backend/common_functions.php` if you want to check work with temporary files)

Installation process:
1. copy content of `web-app` directory to your web-server
2. rename `config.php.template` to `config.php`
3. fill values in `config.php` regarding comments

### Mobile App
Pre-requisites:
1. Android Studio
2. Username and Password to PC repository (request from Airome / SafeTech)

Compilation process:
1. open the project with Android Studio
2. open `build.gradle` and replace in following block credentials with your username and password
```gradle
maven {
    url "https://repo.payconfirm.org/android/maven"
    credentials {
        username = " <<< get from safetech/airome >>> "
        password = " <<< get from safetech/airome >>> "
    }
}
```
3. open `app/src/main/java/tech/paycon/mobile_auth_sample/Constants.java` and fill values regarding comments
4. build the app

## How to use
1. in your browser go to `<your server address>/web-app/ui/`
2. press `Create QR-code` button
3. if you have configured web-app correctly, you will see User ID and QR-code from PC
4. launch mobile app
5. press `Personalize` button on the main screen and scan the QR-code

After this step your mobile application is personalized

6. press `Authenticate` button on the main screen
7. you will see authentication process and result in the app's log

## Process description
1. Personalization is made by standard PC scenario with QR-code only (see [docs here](https://repo.payconfirm.org/server/doc/v5/arch_and_principles/#mobile-app-personalization-and-keys-generation))

2. Authentication process
   - mobile-app -> backend/start_authentication.php - send user id to be authenticated
   - backend/start_authentication.php -> PC Server - create transaction (see [docs here](https://repo.payconfirm.org/server/doc/v5/rest-api/#create-transaction))
   - mobile-app -> PC Server - confirm (digitally sign) transaction (see [docs here](https://repo.payconfirm.org/android/doc/5.x/getting_started/#transaction-confirmation-and-declination))
   - PC Server -> backend/pc_callback_receiver.php - callback with event 'transaction confirmed' or error (see [docs here](https://repo.payconfirm.org/server/doc/v5/rest-api/#transactions-endpoint))
   - mobile-app -> backend/finish_authentication.php - "what about my authentication?"
   - backend/finish_authentication.php -> mobile-app - if PC transaction has been confirmed, then authorize (grant permissions)
