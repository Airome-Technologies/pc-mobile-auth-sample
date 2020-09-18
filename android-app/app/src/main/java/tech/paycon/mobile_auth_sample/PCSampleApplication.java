package tech.paycon.mobile_auth_sample;

import androidx.multidex.MultiDexApplication;

import tech.paycon.sdk.v5.PCSDK;

/**
 * It is better to initialize PC SDK right in your main Application class when the application is being created.
 * After initialization you had better keep the same instance of PC SDK until your app is being destroyed.
 */
public class PCSampleApplication extends MultiDexApplication {

    /**
     * Instance of currently running app to be accessed by other classes
     */
    private static PCSampleApplication sInstance;

    /**
     * Instance of PC SDK supposed to be used throughout the whole app lifecycle
     */
    private PCSDK mPCSDK;

    public static PCSampleApplication getInstance() {
        return sInstance;
    }

    public PCSDK getPCSDK() {
        return mPCSDK;
    }

    @Override
    public void onCreate() {
        super.onCreate();
        sInstance = this;
        // Create PC SDK instance
        mPCSDK = new PCSDK();
        // Set the appropriate log level for your app.
        // PC_LOG_VERBOSE will log lots of the information except sensitive data (passwords, private key values etc)
        // Use PC_LOG_DEBUG to log less information or PC_NO_LOGGING to prevent PC SDK from writing anything to LogCat
        mPCSDK.setLogLevel(PCSDK.PC_LOG_VERBOSE);
        // Initialize PC SDK when your app is created
        // Initialization is fast and won't significantly affect the pace of app initialization
        mPCSDK.init(this);
    }

    @Override
    public void onTerminate() {
        // Finish working with PC SDK
        mPCSDK.destroy();
        sInstance = null;
        super.onTerminate();
    }
}
