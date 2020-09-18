package tech.paycon.mobile_auth_sample.mvvm.viewmodels;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.lifecycle.MutableLiveData;
import androidx.lifecycle.ViewModel;

import tech.paycon.mobile_auth_sample.Constants;

import java.io.BufferedWriter;
import java.io.DataOutputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Arrays;

import tech.paycon.sdk.v5.PCConfirmation;
import tech.paycon.sdk.v5.PCSDK;
import tech.paycon.sdk.v5.PCTransaction;
import tech.paycon.sdk.v5.PCTransactionsManager;
import tech.paycon.sdk.v5.PCUser;
import tech.paycon.sdk.v5.PCUsersManager;
import tech.paycon.sdk.v5.utils.PCError;
import tech.paycon.sdk.v5.utils.PCGetTransactionBinaryDataCallback;
import tech.paycon.sdk.v5.utils.PCGetTransactionCallback;
import tech.paycon.sdk.v5.utils.PCListTransactionsCallback;
import tech.paycon.sdk.v5.utils.PCNetCallback;
import tech.paycon.sdk.v5.utils.PCNetError;
import tech.paycon.sdk.v5.utils.PCSignCallback;

/**
 * Simple ViewModel which controls the logic of app personalization and transactions confirmation
 */
public class MainViewModel extends ViewModel {

    /**
     * Enumeration of different states to notify activity about changes.
     * Set of changeable states can be used to control the application logic
     */
    public enum State {

        /**
         * State that comes after personalization is successfully performed
         */
        PersonalizationDone,

        /**
         * State that comes when authentication has failed
         */
        AuthenticationFailed,

        /**
         * State that comes when authentication has succeeded
         */
        AuthenticationSuccessful
    }

    private MutableLiveData<State> mState;

    public MutableLiveData<State> getState() {
        if (mState == null) {
            mState = new MutableLiveData<>();
        }
        return mState;
    }

    /**
     * Common messages to be added to visible log
     */
    MutableLiveData<String> mMessage;

    public MutableLiveData<String> getMessage() {
        if (mMessage == null) {
            mMessage = new MutableLiveData<>();
        }
        return mMessage;
    }

    /**
     * Errors to be added to log
     */
    MutableLiveData<String> mError;

    public MutableLiveData<String> getError() {
        if (mError == null) {
            mError = new MutableLiveData<>();
        }
        return mError;
    }

    /**
     * Messages that indicate success to be added to log
     */
    MutableLiveData<String> mSuccess;

    public MutableLiveData<String> getSuccess() {
        if (mSuccess == null) {
            mSuccess = new MutableLiveData<>();
        }
        return mSuccess;
    }

    /**
     * PCUser to perform personalization
     */
    private PCUser mUser;

    /**
     * Checks the value of QR-code
     * @param data  Data recognized from QR-code
     */
    public void checkQRCode(@NonNull String data) {
        new Thread(() -> {
            // Doing all actions in separate non-UI thread is better for performance
            PCSDK.PCQRType type = PCSDK.analyzeQRValue(data);
            getMessage().postValue("Type of QR-code: " + type);
            if (type != PCSDK.PCQRType.PCUser) {
                // We expected QR-code of type PCUser
                getError().postValue("This QR code does not contain information for personalization");
                return;
            }
            // We scanned QR-code with key information, try to import it
            mUser = PCUsersManager.importUser(data);
            if (mUser == null) {
                // PCUser wasn't because of some error
                getError().postValue("Cannot import PCUser from given QR-code");
                return;
            }

            // PCUser is ready to be used
            getSuccess().postValue("PCUser imported successfully");
            // Personalize app with the saved PCUser
            personalize();

        }).start();

    }

    /**
     * Personalizes the app after the key data is successfully imported from QR-code
     */
    private void personalize() {

        // This is sample app designed to work with one sample PCUser, thereby the storage is emptied here before
        // personalization. In real app this must not be performed
        for (PCUser user: PCUsersManager.listStorage()) {
            getMessage().postValue("Removed key " + user.getName() + " from storage: " + PCUsersManager.delete(user));
        }

        // Check that the user is activated
        if (!mUser.isActivated()) {
            // If your app is designed to work with keys that require activation, prompt activation code
            // from the client here
            int result = PCUsersManager.activate(mUser, "<<< Digital activation code entered by client >>>");
            if (result != PCError.PC_ERROR_OK) {
                // Key was not saved, handle the error
                getError().postValue("PCUser was not activated: " + new PCError(result).getMessage());
                return;
            }
        }

        // Store PCUser with some password and name
        int result = PCUsersManager.store(mUser, Constants.DUMMY_KEY_NAME, Constants.DUMMY_PASSWORD);
        if (result != PCError.PC_ERROR_OK) {
            // Key was not saved, handle the error
            getError().postValue("The key was not saved: " + new PCError(result).getMessage());
            return;
        }

        getMessage().postValue("Key saved to storage, registering public key...");
        // The key is saved, registering public key and Firebase token for notifications
        String token = " <<< Valid Firebase token for your app >>> ";
        PCUsersManager.register(mUser, token, new PCNetCallback() {
            @Override
            public void success() {
                // NOTE: PC SDK invokes callbacks in main thread regardless the thread from which PC SDK methods
                // have been called. If you don't intend to work in UI thread, you have to start a separate thread
                // here

                // Key has been successfully registered on PC Server
                getSuccess().postValue("The registration on PC Server is successful");
                getState().postValue(State.PersonalizationDone);
            }

            @Override
            public void error(PCNetError pcNetError) {
                // The PCUser was not registered by some error - show it
                getError().postValue("Key registration failed: " + pcNetError.getMessage());
            }
        });
    }

    /**
     * This method:
     *  - starts authentication by sending a request to server
     *  - downloads and signs the transaction to get authenticated
     *  - completes authentication by sending another request to the server to confirm that the authentication was
     *    successful
     */
    public void authenticate() {
        // Use separate thread to perform network requests
        new Thread(() -> {
            // STEP 1. Create a sample authentication request
            getMessage().postValue("STEP 1. Creating sample authentication request...");
            String body = "{\"pc_user_id\":\"" + mUser.getUserId() + "\"}";
            int result = performPlainRequest(Constants.URL_TO_WEBAPP_BACKEND + "/start_authentication.php", body);
            if (result != 200) {
                getError().postValue("Request failed, cannot continue");
                getState().postValue(State.AuthenticationFailed);
                return;
            }
            // STEP 2. Getting list of transaction for the user
            getMessage().postValue("STEP 2. Getting list of transactions...");
            PCTransactionsManager.getTransactionList(mUser, new PCListTransactionsCallback() {
                @Override
                public void success(String[] strings) {
                    getMessage().postValue("Loaded list of transactions: " + Arrays.toString(strings));
                    // STEP 3. Get the last transaction to be signed (the list is expected to contain one transaction
                    // only)
                    getMessage().postValue("STEP 3. Getting transaction data...");
                    PCTransactionsManager.getTransaction(mUser, strings[strings.length - 1], new PCGetTransactionCallback() {
                        @Override
                        public void success(PCTransaction pcTransaction) {
                            getMessage().postValue("Got transaction with text: " + pcTransaction.getTransactionText());
                            if (pcTransaction.hasBinaryData()) {
                                // Transaction can contain additional binary data (an attachment)
                                // In this case we must load it before signing
                                getMessage().postValue("STEP 3.1. Getting transaction binary data...");
                                PCTransactionsManager.getTransactionBinaryData(mUser, pcTransaction, new PCGetTransactionBinaryDataCallback() {
                                    @Override
                                    public void success(PCTransaction pcTransaction) {
                                        getMessage().postValue("Transaction binary data was loaded");
                                        signTransactionAndFinishAuthentication(pcTransaction);
                                    }

                                    @Override
                                    public void error(@Nullable PCError pcError, @Nullable PCNetError pcNetError) {
                                        getError().postValue("Failed to load transaction binary data: "
                                                + getErrorText(pcError, pcNetError));
                                        getState().postValue(State.AuthenticationFailed);
                                    }
                                });
                            } else {
                                signTransactionAndFinishAuthentication(pcTransaction);
                            }
                        }

                        @Override
                        public void error(PCNetError pcNetError) {
                            getError().postValue("Failed to load transaction data: " + pcNetError.getMessage());
                            getState().postValue(State.AuthenticationFailed);
                        }
                    });
                }

                @Override
                public void error(PCNetError pcNetError) {
                    getError().postValue("Failed to load list of transactions: " + pcNetError.getMessage());
                    getState().postValue(State.AuthenticationFailed);
                }
            });
        }).start();
    }

    /**
     * Called after transaction data is acquired to sign it and finish the authentication
     * @param transaction   Target PCTransaction
     */
    private void signTransactionAndFinishAuthentication(PCTransaction transaction) {
        // STEP 4. Signing transaction
        boolean readyToSign = mUser.isReadyToSign();
        getMessage().postValue("PCUser is ready to sign transaction: " + readyToSign);
        // If isReadyToSign() returns false, it means that the password must be submitted
        if (!readyToSign) {
            int result = PCUsersManager.submitPassword(mUser, Constants.DUMMY_PASSWORD);
            if (result != PCError.PC_ERROR_OK) {
                // This is normally does not happen if correct password is submitted
                getError().postValue("Error occurred while submitting password: " + new PCError(result).getMessage());
                getState().postValue(State.AuthenticationFailed);
                return;
            }
        }
        // Now transaction can be signed
        getMessage().postValue("STEP 4. Signing transaction...");
        PCTransactionsManager.sign(mUser, transaction, new PCSignCallback() {
            @Override
            public void success() {
                // Now we are again in UI thread. To perform another network request we must start a separate thread
                new Thread(() -> {
                    getMessage().postValue("Transaction was signed successfully");
                    // STEP 5. Finishing authentication
                    getMessage().postValue("STEP 5. Finishing authentication...");
                    String body = "{\"pc_user_id\":\"" + mUser.getUserId() + "\"}";
                    int result = performPlainRequest(Constants.URL_TO_WEBAPP_BACKEND + "/finish_authentication.php", body);
                    if (result != 200) {
                        getError().postValue("Authentication was not finished");
                        getState().postValue(State.AuthenticationFailed);
                    } else {
                        getSuccess().postValue("Successfully authenticated");
                        getState().postValue(State.AuthenticationSuccessful);
                    }
                }).start();
            }

            @Override
            public void error(@Nullable PCError pcError, @Nullable PCNetError pcNetError, @Nullable PCConfirmation pcConfirmation) {
                getError().postValue("Transaction was not signed: " + getErrorText(pcError, pcNetError));
                getState().postValue(State.AuthenticationFailed);
            }
        });
    }

    /**
     * Auxiliary method to perform simple post request with body which contains data in JSON format
     * @param urlAddress    Target URL
     * @param jsonBody      JSON body
     * @return  Response code
     */
    private int performPlainRequest(String urlAddress, String jsonBody) {
        try {
            getMessage().postValue("Connecting to: " + urlAddress);
            URL url = new URL(urlAddress);
            HttpURLConnection connection = (HttpURLConnection) url.openConnection();
            connection.setRequestMethod("POST");
            connection.setRequestProperty("Content-type", "application/json");
            // 20 secs to connect, 20 secs to read
            connection.setConnectTimeout(20000);
            connection.setReadTimeout(20000);
            // Write body
            getMessage().postValue("Sending data: " + jsonBody);
            if (jsonBody != null && !jsonBody.isEmpty()) {
                DataOutputStream wr = new DataOutputStream(connection.getOutputStream());
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(wr, "UTF-8"));
                writer.write(jsonBody);
                writer.close();
                wr.flush();
                wr.close();
            }
            // Get response
            int responseCode = connection.getResponseCode();
            String responseMessage = connection.getResponseMessage();
            getMessage().postValue("Received response: " + responseCode + " " + responseMessage);
            return responseCode;
        } catch (Exception e) {
            e.printStackTrace();
            getError().postValue("Caught exception while trying to perform post request: " + e.getMessage());
            return -1;
        }
    }

    /**
     * Auxiliary function to get text based on content of PCError and PCNetError
     * @param error     PCError or null
     * @param netError  PCNetError or ull
     * @return  Readable text
     */
    private String getErrorText(@Nullable PCError error, PCNetError netError) {
        String text = "";
        if (error != null) {
            text += error.getMessage();
        }
        if (netError != null) {
            text += (text.isEmpty() ? "" : "; ") + netError.getMessage();
        }
        return text;
    }

}
