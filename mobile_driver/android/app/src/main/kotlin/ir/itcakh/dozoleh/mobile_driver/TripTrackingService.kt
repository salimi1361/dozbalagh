package ir.itcakh.dozoleh.mobile_driver

import android.Manifest
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Build
import android.os.Bundle
import android.os.IBinder
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import java.util.UUID
import java.util.concurrent.Executors

class TripTrackingService : Service(), LocationListener {
    companion object {
        private const val ACTION_START = "dozoleh.action.START_TRACKING"
        private const val ACTION_STOP = "dozoleh.action.STOP_TRACKING"
        private const val EXTRA_ITEM_ID = "item_id"
        private const val EXTRA_TOKEN = "token"
        private const val EXTRA_API_BASE = "api_base"
        private const val PREFS = "dozoleh_trip_tracking"
        private const val KEY_ACTIVE = "active"
        private const val KEY_ITEM_ID = "item_id"
        private const val KEY_TOKEN = "token"
        private const val KEY_API_BASE = "api_base"
        private const val CHANNEL_ID = "dozoleh_active_trip"
        private const val NOTIFICATION_ID = 2410

        fun startIntent(
            context: Context,
            itemId: Int,
            token: String,
            apiBase: String,
        ) = Intent(context, TripTrackingService::class.java).apply {
            action = ACTION_START
            putExtra(EXTRA_ITEM_ID, itemId)
            putExtra(EXTRA_TOKEN, token)
            putExtra(EXTRA_API_BASE, apiBase)
        }

        fun stopIntent(context: Context) =
            Intent(context, TripTrackingService::class.java).apply { action = ACTION_STOP }

        fun isActive(context: Context): Boolean =
            context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .getBoolean(KEY_ACTIVE, false)

        fun activeItemId(context: Context): Int? {
            val value = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
                .getInt(KEY_ITEM_ID, -1)
            return value.takeIf { it > 0 }
        }
    }

    private lateinit var locationManager: LocationManager
    private val networkExecutor = Executors.newSingleThreadExecutor()
    private var itemId = -1
    private var token = ""
    private var apiBase = ""
    private var lastSentAt = 0L

    override fun onCreate() {
        super.onCreate()
        locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACTION_STOP) {
            stopTracking()
            return START_NOT_STICKY
        }

        val preferences = getSharedPreferences(PREFS, Context.MODE_PRIVATE)
        itemId = intent?.getIntExtra(EXTRA_ITEM_ID, -1)
            ?.takeIf { it > 0 }
            ?: preferences.getInt(KEY_ITEM_ID, -1)
        token = intent?.getStringExtra(EXTRA_TOKEN)
            ?.takeIf { it.isNotBlank() }
            ?: preferences.getString(KEY_TOKEN, "").orEmpty()
        apiBase = intent?.getStringExtra(EXTRA_API_BASE)
            ?.takeIf { it.isNotBlank() }
            ?: preferences.getString(KEY_API_BASE, "").orEmpty()

        if (itemId <= 0 || token.isBlank() || apiBase.isBlank()) {
            stopSelf()
            return START_NOT_STICKY
        }

        val wasActive = preferences.getBoolean(KEY_ACTIVE, false)
        val previousItemId = preferences.getInt(KEY_ITEM_ID, -1)
        preferences.edit()
            .putBoolean(KEY_ACTIVE, true)
            .putInt(KEY_ITEM_ID, itemId)
            .putString(KEY_TOKEN, token)
            .putString(KEY_API_BASE, apiBase.trimEnd('/'))
            .apply()
        apiBase = apiBase.trimEnd('/')

        startForeground(NOTIFICATION_ID, trackingNotification())
        requestLocationUpdates()
        if (!wasActive || previousItemId != itemId) {
            sendEvent("tracking_started")
        }
        sendLastKnownLocation()
        return START_STICKY
    }

    override fun onLocationChanged(location: Location) {
        val now = System.currentTimeMillis()
        if (now - lastSentAt < 10_000) return
        lastSentAt = now
        sendLocation(location)
    }

    @Deprecated("Deprecated in Android")
    override fun onStatusChanged(provider: String?, status: Int, extras: Bundle?) = Unit

    override fun onProviderEnabled(provider: String) = Unit

    override fun onProviderDisabled(provider: String) = Unit

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        locationManager.removeUpdates(this)
        networkExecutor.shutdown()
        super.onDestroy()
    }

    private fun requestLocationUpdates() {
        if (checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            stopTracking()
            return
        }
        listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER).forEach { provider ->
            if (locationManager.isProviderEnabled(provider)) {
                @Suppress("MissingPermission")
                locationManager.requestLocationUpdates(provider, 10_000L, 0f, this)
            }
        }
    }

    private fun sendLastKnownLocation() {
        if (checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) return
        val location = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER)
            .mapNotNull { provider ->
                runCatching {
                    @Suppress("MissingPermission")
                    locationManager.getLastKnownLocation(provider)
                }.getOrNull()
            }
            .maxByOrNull { it.time }
        if (location != null) sendLocation(location)
    }

    private fun sendLocation(location: Location) {
        val payload = JSONObject()
            .put("dozbalagh_item_id", itemId)
            .put("latitude", location.latitude)
            .put("longitude", location.longitude)
            .put("client_uuid", UUID.randomUUID().toString())
            .put("accuracy", location.accuracy.toDouble())
            .put("altitude", location.altitude)
            .put("speed", location.speed.toDouble())
            .put("heading", location.bearing.toDouble())
            .put("recorded_at", isoDate(location.time))
        post("location/sync", payload)
    }

    private fun sendEvent(eventType: String) {
        val payload = JSONObject()
            .put("dozbalagh_item_id", itemId)
            .put("event_type", eventType)
        post("event/log", payload)
    }

    private fun isoDate(timestamp: Long): String =
        SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }.format(Date(timestamp))

    private fun post(path: String, payload: JSONObject) {
        val currentToken = token
        val currentBase = apiBase
        if (currentToken.isBlank() || currentBase.isBlank()) return
        networkExecutor.execute {
            var connection: HttpURLConnection? = null
            try {
                connection = URL("$currentBase/$path").openConnection() as HttpURLConnection
                connection.requestMethod = "POST"
                connection.connectTimeout = 15_000
                connection.readTimeout = 20_000
                connection.doOutput = true
                connection.setRequestProperty("Accept", "application/json")
                connection.setRequestProperty("Content-Type", "application/json")
                connection.setRequestProperty("Authorization", "Bearer $currentToken")
                connection.outputStream.use { output ->
                    output.write(payload.toString().toByteArray(Charsets.UTF_8))
                }
                connection.inputStream.use { it.readBytes() }
            } catch (_: Throwable) {
                runCatching { connection?.errorStream?.close() }
            } finally {
                connection?.disconnect()
            }
        }
    }

    private fun stopTracking() {
        if (itemId <= 0) {
            val preferences = getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            itemId = preferences.getInt(KEY_ITEM_ID, -1)
            token = preferences.getString(KEY_TOKEN, "").orEmpty()
            apiBase = preferences.getString(KEY_API_BASE, "").orEmpty()
        }
        if (itemId > 0) sendEvent("tracking_stopped")
        locationManager.removeUpdates(this)
        getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit().clear().apply()
        stopForeground(STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val channel = NotificationChannel(
            CHANNEL_ID,
            "ردیابی سفر",
            NotificationManager.IMPORTANCE_LOW,
        ).apply {
            description = "نمایش وضعیت ارسال زنده موقعیت سفر به شرکت"
            setShowBadge(false)
        }
        getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
    }

    private fun trackingNotification(): Notification {
        val openIntent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
        }
        val openPendingIntent = PendingIntent.getActivity(
            this,
            10,
            openIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
        val stopPendingIntent = PendingIntent.getService(
            this,
            11,
            stopIntent(this),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            Notification.Builder(this, CHANNEL_ID)
        } else {
            @Suppress("DEPRECATION")
            Notification.Builder(this)
        }
        return builder
            .setSmallIcon(R.drawable.ic_stat_chat)
            .setContentTitle("سفر و ردیابی فعال است")
            .setContentText("موقعیت شما برای شرکت ارسال می‌شود.")
            .setContentIntent(openPendingIntent)
            .setOngoing(true)
            .setOnlyAlertOnce(true)
            .addAction(android.R.drawable.ic_media_pause, "پایان سفر", stopPendingIntent)
            .build()
    }
}
