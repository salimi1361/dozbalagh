package ir.itcakh.dozoleh.mobile_driver

import android.Manifest
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.LocationManager
import android.net.Uri
import android.os.Build
import android.provider.Settings
import android.speech.RecognitionListener
import android.speech.RecognizerIntent
import android.speech.SpeechRecognizer
import android.speech.tts.TextToSpeech
import android.os.Bundle
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.embedding.android.FlutterFragmentActivity
import io.flutter.plugin.common.MethodChannel
import java.util.Locale

class MainActivity : FlutterFragmentActivity() {
    companion object {
        private const val CHAT_CHANNEL_ID = "dozoleh_driver_chat"
        private const val CHAT_NOTIFICATION_ID = 2401
        private const val NOTIFICATION_PERMISSION_REQUEST = 2402
        private const val LOCATION_PERMISSION_REQUEST = 2403
        private const val SPEECH_PERMISSION_REQUEST = 2404
    }

    private var pendingLocationPermissionResult: MethodChannel.Result? = null
    private var pendingSpeechResult: MethodChannel.Result? = null
    private var speechRecognizer: SpeechRecognizer? = null
    private var textToSpeech: TextToSpeech? = null
    private var ttsReady = false
    private var pendingTtsText: String? = null
    private var pendingTtsResult: MethodChannel.Result? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        createChatNotificationChannel()
        initializeTextToSpeech()

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/app_update",
        ).setMethodCallHandler { call, result ->
            if (call.method != "openDownload") {
                result.notImplemented()
                return@setMethodCallHandler
            }
            val url = call.argument<String>("url")
            if (url.isNullOrBlank()) {
                result.error("missing_download_url", "لینک دریافت نسخه جدید موجود نیست.", null)
                return@setMethodCallHandler
            }
            try {
                startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                result.success(true)
            } catch (_: Exception) {
                result.error("download_open_failed", "باز کردن لینک دریافت ممکن نشد.", null)
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/device_info",
        ).setMethodCallHandler { call, result ->
            if (call.method != "getDeviceInfo") {
                result.notImplemented()
                return@setMethodCallHandler
            }

            val packageInfo = packageManager.getPackageInfo(packageName, 0)
            val buildNumber = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                packageInfo.longVersionCode.toString()
            } else {
                @Suppress("DEPRECATION")
                packageInfo.versionCode.toString()
            }

            result.success(
                mapOf(
                    "device_uuid" to Settings.Secure.getString(
                        contentResolver,
                        Settings.Secure.ANDROID_ID,
                    ),
                    "platform" to "android",
                    "manufacturer" to Build.MANUFACTURER,
                    "model" to Build.MODEL,
                    "device_name" to Build.DEVICE,
                    "os_version" to Build.VERSION.RELEASE,
                    "sdk_version" to Build.VERSION.SDK_INT,
                    "app_version" to packageInfo.versionName,
                    "app_build" to buildNumber,
                    "app_identifier" to packageName,
                    "locale" to Locale.getDefault().toLanguageTag(),
                ),
            )
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/notifications",
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "requestPermission" -> {
                    requestNotificationPermission()
                    result.success(null)
                }
                "showChatNotification" -> {
                    showChatNotification(
                        title = call.argument<String>("title") ?: "پیام جدید دوزوله",
                        message = call.argument<String>("message") ?: "شما یک پیام جدید دارید.",
                        unreadCount = call.argument<Int>("unread_count") ?: 1,
                    )
                    result.success(null)
                }
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/location_access",
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "getLocationState" -> result.success(
                    mapOf(
                        "service_enabled" to isLocationServiceEnabled(),
                        "permission_granted" to hasPreciseLocationPermission(),
                    ),
                )
                "requestPermission" -> requestLocationPermission(result)
                "openLocationSettings" -> {
                    startActivity(Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS))
                    result.success(null)
                }
                "openAppSettings" -> {
                    startActivity(
                        Intent(
                            Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                            Uri.parse("package:$packageName"),
                        ),
                    )
                    result.success(null)
                }
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/assistant_speech",
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "isAvailable" -> result.success(
                    mapOf(
                        "recognition" to SpeechRecognizer.isRecognitionAvailable(this),
                        "synthesis" to (textToSpeech != null),
                    ),
                )
                "listen" -> requestSpeechInput(result)
                "speak" -> {
                    val text = call.argument<String>("text")?.trim()
                    if (text.isNullOrEmpty()) {
                        result.error("empty_speech", "متن پاسخ برای خواندن خالی است.", null)
                    } else {
                        speakText(text, result)
                    }
                }
                "stopSpeaking" -> {
                    textToSpeech?.stop()
                    result.success(null)
                }
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/trip_tracking",
        ).setMethodCallHandler { call, result ->
            when (call.method) {
                "getState" -> result.success(
                    mapOf(
                        "active" to TripTrackingService.isActive(this),
                        "item_id" to TripTrackingService.activeItemId(this),
                    ),
                )
                "start" -> {
                    val itemId = call.argument<Number>("item_id")?.toInt()
                    val token = call.argument<String>("token")
                    val apiBase = call.argument<String>("api_base")
                    if (itemId == null || token.isNullOrBlank() || apiBase.isNullOrBlank()) {
                        result.error("invalid_tracking_data", "اطلاعات شروع ردیابی کامل نیست.", null)
                        return@setMethodCallHandler
                    }
                    val intent = TripTrackingService.startIntent(this, itemId, token, apiBase)
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                        startForegroundService(intent)
                    } else {
                        startService(intent)
                    }
                    result.success(true)
                }
                "stop" -> {
                    startService(TripTrackingService.stopIntent(this))
                    result.success(true)
                }
                else -> result.notImplemented()
            }
        }

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "ir.itcakh.dozoleh.mobile_driver/permit_preview",
        ).setMethodCallHandler { call, result ->
            if (call.method != "open") {
                result.notImplemented()
                return@setMethodCallHandler
            }
            val url = call.argument<String>("url")
            val token = call.argument<String>("token")
            if (url.isNullOrBlank() || token.isNullOrBlank()) {
                result.error("invalid_preview_data", "اطلاعات نمایش دوزوله کامل نیست.", null)
                return@setMethodCallHandler
            }
            startActivity(PermitPreviewActivity.intent(this, url, token))
            result.success(true)
        }
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray,
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == LOCATION_PERMISSION_REQUEST) {
            pendingLocationPermissionResult?.success(hasPreciseLocationPermission())
            pendingLocationPermissionResult = null
        } else if (requestCode == SPEECH_PERMISSION_REQUEST) {
            if (
                grantResults.isNotEmpty() &&
                grantResults.first() == PackageManager.PERMISSION_GRANTED
            ) {
                startSpeechInput()
            } else {
                pendingSpeechResult?.error(
                    "microphone_denied",
                    "برای گفت‌وگوی صوتی، دسترسی میکروفن را فعال کنید.",
                    null,
                )
                pendingSpeechResult = null
            }
        }
    }

    override fun onDestroy() {
        speechRecognizer?.destroy()
        speechRecognizer = null
        textToSpeech?.stop()
        textToSpeech?.shutdown()
        textToSpeech = null
        super.onDestroy()
    }

    private fun requestSpeechInput(result: MethodChannel.Result) {
        if (pendingSpeechResult != null) {
            result.error("speech_request_active", "دستیار در حال شنیدن است.", null)
            return
        }
        if (!SpeechRecognizer.isRecognitionAvailable(this)) {
            result.error(
                "speech_unavailable",
                "تشخیص گفتار روی این دستگاه در دسترس نیست.",
                null,
            )
            return
        }
        pendingSpeechResult = result
        if (
            checkSelfPermission(Manifest.permission.RECORD_AUDIO) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            requestPermissions(
                arrayOf(Manifest.permission.RECORD_AUDIO),
                SPEECH_PERMISSION_REQUEST,
            )
            return
        }
        startSpeechInput()
    }

    private fun startSpeechInput() {
        if (pendingSpeechResult == null) return
        speechRecognizer?.destroy()
        speechRecognizer = SpeechRecognizer.createSpeechRecognizer(this).apply {
            setRecognitionListener(object : RecognitionListener {
                override fun onReadyForSpeech(params: Bundle?) = Unit
                override fun onBeginningOfSpeech() = Unit
                override fun onRmsChanged(rmsdB: Float) = Unit
                override fun onBufferReceived(buffer: ByteArray?) = Unit
                override fun onEndOfSpeech() = Unit
                override fun onEvent(eventType: Int, params: Bundle?) = Unit
                override fun onPartialResults(partialResults: Bundle?) = Unit

                override fun onError(error: Int) {
                    val currentResult = pendingSpeechResult ?: return
                    pendingSpeechResult = null
                    if (
                        error == SpeechRecognizer.ERROR_NO_MATCH ||
                        error == SpeechRecognizer.ERROR_SPEECH_TIMEOUT
                    ) {
                        currentResult.success("")
                    } else {
                        currentResult.error(
                            "speech_error_$error",
                            "صدای شما دریافت نشد؛ دوباره تلاش کنید.",
                            null,
                        )
                    }
                }

                override fun onResults(results: Bundle?) {
                    val matches = results?.getStringArrayList(
                        SpeechRecognizer.RESULTS_RECOGNITION,
                    )
                    val currentResult = pendingSpeechResult ?: return
                    pendingSpeechResult = null
                    currentResult.success(matches?.firstOrNull().orEmpty())
                }
            })
            startListening(
                Intent(RecognizerIntent.ACTION_RECOGNIZE_SPEECH).apply {
                    putExtra(
                        RecognizerIntent.EXTRA_LANGUAGE_MODEL,
                        RecognizerIntent.LANGUAGE_MODEL_FREE_FORM,
                    )
                    putExtra(RecognizerIntent.EXTRA_LANGUAGE, "fa-IR")
                    putExtra(RecognizerIntent.EXTRA_LANGUAGE_PREFERENCE, "fa-IR")
                    putExtra(RecognizerIntent.EXTRA_MAX_RESULTS, 3)
                    putExtra(RecognizerIntent.EXTRA_PROMPT, "سؤال خود را از دستیار دوزوله بپرسید")
                },
            )
        }
    }

    private fun initializeTextToSpeech() {
        if (textToSpeech != null) return
        textToSpeech = TextToSpeech(this) { status ->
            ttsReady = status == TextToSpeech.SUCCESS
            if (ttsReady) {
                textToSpeech?.language = Locale("fa", "IR")
                val text = pendingTtsText
                val result = pendingTtsResult
                pendingTtsText = null
                pendingTtsResult = null
                if (!text.isNullOrBlank()) {
                    textToSpeech?.speak(
                        text,
                        TextToSpeech.QUEUE_FLUSH,
                        null,
                        "dozoleh-assistant",
                    )
                }
                result?.success(true)
            } else {
                textToSpeech?.shutdown()
                textToSpeech = null
                pendingTtsResult?.error(
                    "tts_unavailable",
                    "خواندن صوتی پاسخ روی این دستگاه در دسترس نیست.",
                    null,
                )
                pendingTtsText = null
                pendingTtsResult = null
            }
        }
    }

    private fun speakText(text: String, result: MethodChannel.Result) {
        if (ttsReady) {
            textToSpeech?.speak(
                text,
                TextToSpeech.QUEUE_FLUSH,
                null,
                "dozoleh-assistant",
            )
            result.success(true)
            return
        }
        if (pendingTtsResult != null) {
            result.error("tts_request_active", "پاسخ قبلی در حال آماده‌سازی است.", null)
            return
        }
        pendingTtsText = text
        pendingTtsResult = result
        initializeTextToSpeech()
    }

    private fun isLocationServiceEnabled(): Boolean {
        val manager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            manager.isLocationEnabled
        } else {
            @Suppress("DEPRECATION")
            manager.isProviderEnabled(LocationManager.GPS_PROVIDER)
        }
    }

    private fun hasPreciseLocationPermission(): Boolean =
        checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) ==
            PackageManager.PERMISSION_GRANTED

    private fun requestLocationPermission(result: MethodChannel.Result) {
        if (hasPreciseLocationPermission()) {
            result.success(true)
            return
        }
        if (pendingLocationPermissionResult != null) {
            result.error("permission_request_active", "درخواست مجوز در حال اجرا است.", null)
            return
        }
        pendingLocationPermissionResult = result
        requestPermissions(
            arrayOf(
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION,
            ),
            LOCATION_PERMISSION_REQUEST,
        )
    }

    private fun createChatNotificationChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val channel = NotificationChannel(
            CHAT_CHANNEL_ID,
            "پیام‌های گفتگو",
            NotificationManager.IMPORTANCE_HIGH,
        ).apply {
            description = "پیام‌های جدید شرکت و سایر گفتگوهای راننده"
            enableVibration(true)
        }
        getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
    }

    private fun requestNotificationPermission() {
        if (
            Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            requestPermissions(
                arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                NOTIFICATION_PERMISSION_REQUEST,
            )
        }
    }

    private fun showChatNotification(title: String, message: String, unreadCount: Int) {
        if (
            Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) return

        val openAppIntent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
        }
        val pendingIntent = PendingIntent.getActivity(
            this,
            0,
            openAppIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, CHAT_CHANNEL_ID)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this)
        }
        val notification = builder
            .setSmallIcon(R.drawable.ic_stat_chat)
            .setContentTitle(title)
            .setContentText(message)
            .setStyle(Notification.BigTextStyle().bigText(message))
            .setContentIntent(pendingIntent)
            .setAutoCancel(true)
            .setNumber(unreadCount)
            .build()

        getSystemService(NotificationManager::class.java)
            .notify(CHAT_NOTIFICATION_ID, notification)
    }
}
