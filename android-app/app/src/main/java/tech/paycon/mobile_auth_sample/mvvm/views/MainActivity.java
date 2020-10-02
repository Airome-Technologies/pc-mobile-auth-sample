package tech.paycon.mobile_auth_sample.mvvm.views;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.text.SpannableString;
import android.text.method.ScrollingMovementMethod;
import android.text.style.ForegroundColorSpan;
import android.view.Gravity;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.annotation.RequiresApi;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.lifecycle.ViewModelProvider;

import com.google.zxing.integration.android.IntentIntegrator;
import com.google.zxing.integration.android.IntentResult;

import tech.paycon.mobile_auth_sample.R;
import tech.paycon.mobile_auth_sample.databinding.ActivityMainBinding;
import tech.paycon.mobile_auth_sample.databinding.DialogPersonalizationBinding;
import tech.paycon.mobile_auth_sample.mvvm.viewmodels.MainViewModel;

import java.text.SimpleDateFormat;
import java.util.Date;

/**
 * Simple activity which illustrates the registration of the user and transaction confirmation
 */
public class MainActivity extends AppCompatActivity {

    private final static int REQUEST_CODE_CAMERA_PERMISSION = 100;

    private ActivityMainBinding mBinding;
    private MainViewModel mViewModel;

    /**
     * Indicates whether the permission to use camera is already granted. It as always true for device running Android
     * 5.1 or older
     */
    private boolean mIsCameraPermissionGranted;

    /**
     * Types of messages to be added to log in a coloured way
     */
    private enum MessageType {
        /**
         * Message with neutral information which is not highlighted
         */
        Info,
        /**
         * Message highlighted as error
         */
        Error,
        /**
         * Message highlighted as successful result of something
         */
        Success
    }

    @RequiresApi(api = Build.VERSION_CODES.M)
    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Bind layout
        mBinding = ActivityMainBinding.inflate(getLayoutInflater());
        setContentView(mBinding.getRoot());

        // Acquire ViewModel
        mViewModel = new ViewModelProvider(this).get(MainViewModel.class);

        // Check the permission to use camera
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.M) {
            // Permission is auto-granted for device running Android older than M
            mIsCameraPermissionGranted = true;
        } else {
            // For devices controlled by Android M and newer check if the permission is actually granted
            mIsCameraPermissionGranted =
                    checkSelfPermission("android.permission.CAMERA") == PackageManager.PERMISSION_GRANTED;
        }

        // Initialize button for personalization with QR code
        mBinding.buttonPersonalizeWithQr.setOnClickListener(v -> {
            if (mIsCameraPermissionGranted) {
                // Camera can be used - scan QR-code with key information
                openQRCodeScanner();
            } else {
                // Request permission to use the camera
                requestPermissions(new String[]{"android.permission.CAMERA"}, REQUEST_CODE_CAMERA_PERMISSION);
            }
        });

        // Initialize button for personalization with alias
        mBinding.buttonPersonalizeWithAlias.setOnClickListener(v -> openDialogForPersonalization());

        // Observe changes from ViewModel

        mBinding.textViewLogs.setMovementMethod(new ScrollingMovementMethod());

        mViewModel.getMessage().observe(this, text -> {
            if (text != null) {
                logMessage(text, MessageType.Info);
            }
        });

        mViewModel.getError().observe(this, text -> {
            if (text != null) {
                logMessage(text, MessageType.Error);
            }
        });

        mViewModel.getSuccess().observe(this, text -> {
            if (text != null) {
                logMessage(text, MessageType.Success);
            }
        });

        mViewModel.getState().observe(this, state -> {
            if (state != null) {
                switch (state) {

                    case PersonalizationDone:
                        // Application has been personalized successfully
                        logMessage("The app is now personalized and you can authenticate", MessageType.Success);
                        mBinding.buttonAuthenticate.setEnabled(true);
                        mBinding.buttonAuthenticate.setOnClickListener(v -> {
                            mBinding.buttonAuthenticate.setEnabled(false);
                            mBinding.buttonAuthenticate.setText(R.string.action_authenticating);
                            mViewModel.authenticate();
                        });
                        break;

                    case AuthenticationFailed:
                        // Authentication failed by some reason - unblock the button to retry
                        mBinding.buttonAuthenticate.setEnabled(true);
                        mBinding.buttonAuthenticate.setText(R.string.action_authenticate);
                        break;

                    case AuthenticationSuccessful:
                        // Authentication finished - unblock button to allow a new authentication procedure and
                        // show toast
                        mBinding.buttonAuthenticate.setEnabled(true);
                        mBinding.buttonAuthenticate.setText(R.string.action_authenticate);
                        Toast toast = Toast.makeText(this, R.string.action_authenticated, Toast.LENGTH_SHORT);
                        toast.setGravity(Gravity.CENTER, 0, 0);
                        toast.show();
                        break;

                }
            }
        });

        mViewModel.init();
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        if (requestCode == REQUEST_CODE_CAMERA_PERMISSION) {
            // Check if we have been granted permission to use the camera
            mIsCameraPermissionGranted =  grantResults[0] == PackageManager.PERMISSION_GRANTED;
            if (mIsCameraPermissionGranted) {
                // Camera can be used - scan QR-code with key information
                openQRCodeScanner();
            }
        }
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.menu_main, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(@NonNull MenuItem item) {
        if (item.getItemId() == R.id.menu_clear_logs) {
            mBinding.textViewLogs.setText("");
            return false;
        }
        return true;
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        // Check result of QR-code scanning
        IntentResult result = IntentIntegrator.parseActivityResult(requestCode, resultCode, data);
        if(result != null) {
            String scannedData = result.getContents();
            if(scannedData == null) {
                logMessage("Scanning of QR code was cancelled", MessageType.Info);
            } else {
                logMessage("QR-code has been scanned. Checking contents: " + scannedData, MessageType.Info);
                mViewModel.checkQRCode(scannedData);
            }
        } else {
            super.onActivityResult(requestCode, resultCode, data);
        }
    }

    /**
     * Open camera for QR-code scanning
     */
    private void openQRCodeScanner() {
        IntentIntegrator integrator = new IntentIntegrator(this);
        integrator.setDesiredBarcodeFormats(IntentIntegrator.QR_CODE_TYPES); // Only QR-codes are expected
        integrator.setBeepEnabled(false); // Do not emerge sound
        integrator.setBarcodeImageEnabled(true);    // Do not preview QR-codes
        integrator.setOrientationLocked(false); // Do not freeze the landscape orientation
        integrator.setPrompt("Scan QR-code to start personalization");
        integrator.initiateScan();
    }

    /**
     * Adds message to visible log
     * @param text  Text to be logged
     * @param type  Type of message to be logged
     */
    private void logMessage(String text, MessageType type) {
        @SuppressLint("SimpleDateFormat") SimpleDateFormat format = new SimpleDateFormat("HH:mm:ss");
        String prefix = "\n" + format.format(new Date()) + ": ";
        if (type == MessageType.Error || type == MessageType.Success) {
            // Add highlighted message to log
            SpannableString string = new SpannableString(prefix + text);
            string.setSpan(new ForegroundColorSpan(
                    getResources().getColor(type == MessageType.Error ? R.color.colorRed : R.color.colorGreen)),
                    0, prefix.length() + text.length(), 0);
            mBinding.textViewLogs.append(string);
        }  else {
            // Add regular message to log
            mBinding.textViewLogs.append(prefix + text);
        }
        // Scroll log to bottom
        mBinding.textViewLogs.post(()-> {
            final int scrollAmount = mBinding.textViewLogs.getLayout().getLineTop(mBinding.textViewLogs.getLineCount())
                    + mBinding.textViewLogs.getPaddingTop()+ mBinding.textViewLogs.getPaddingBottom()
                    - mBinding.textViewLogs.getBottom() + mBinding.textViewLogs.getTop();
            mBinding.textViewLogs.scrollTo(0, Math.max(scrollAmount, 0));
        });
    }

    /**
     * Opens the dialog to enter an alias and activation code in order to start personalization
     */
    private void openDialogForPersonalization() {
        AlertDialog.Builder builder = new AlertDialog.Builder(this);
        DialogPersonalizationBinding binding = DialogPersonalizationBinding.inflate(getLayoutInflater());
        builder.setView(binding.getRoot())
                .setCancelable(true)
                .setNegativeButton(R.string.action_cancel, (d, i) -> d.cancel())
                .setPositiveButton(R.string.action_continue, (d, i) -> {
                    d.dismiss();
                    mViewModel.submitAliasAndActivationCode(binding.edittextAlias.getText().toString(),
                            binding.edittextActivationCode.getText().toString());
                })
                .show();
    }
}
