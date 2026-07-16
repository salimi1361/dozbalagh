package ir.itcakh.dozoleh.mobile_driver

import android.app.Activity
import android.content.Context
import android.content.Intent
import android.graphics.Color
import android.os.Bundle
import android.print.PrintAttributes
import android.print.PrintManager
import android.view.ViewGroup
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat

class PermitPreviewActivity : Activity() {
    companion object {
        private const val EXTRA_URL = "preview_url"
        private const val EXTRA_TOKEN = "preview_token"

        fun intent(context: Context, url: String, token: String) =
            Intent(context, PermitPreviewActivity::class.java).apply {
                putExtra(EXTRA_URL, url)
                putExtra(EXTRA_TOKEN, token)
            }
    }

    private lateinit var webView: WebView
    private lateinit var progress: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val url = intent.getStringExtra(EXTRA_URL).orEmpty()
        val token = intent.getStringExtra(EXTRA_TOKEN).orEmpty()
        if (url.isBlank() || token.isBlank()) {
            finish()
            return
        }

        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setBackgroundColor(Color.rgb(226, 232, 240))
        }
        ViewCompat.setOnApplyWindowInsetsListener(root) { view, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            view.setPadding(0, systemBars.top, 0, systemBars.bottom)
            insets
        }
        val toolbar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL
            gravity = android.view.Gravity.CENTER_VERTICAL
            setPadding(18, 10, 12, 10)
            setBackgroundColor(Color.rgb(7, 17, 31))
        }
        val close = TextView(this).apply {
            text = "✕"
            textSize = 26f
            setTextColor(Color.WHITE)
            gravity = android.view.Gravity.CENTER
            setPadding(18, 4, 18, 4)
            setOnClickListener { finish() }
        }
        val title = TextView(this).apply {
            text = "تصویر دوزوله راننده"
            textSize = 17f
            setTextColor(Color.WHITE)
            gravity = android.view.Gravity.RIGHT or android.view.Gravity.CENTER_VERTICAL
            typeface = android.graphics.Typeface.DEFAULT_BOLD
        }
        toolbar.addView(close, LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.WRAP_CONTENT,
            ViewGroup.LayoutParams.WRAP_CONTENT,
        ))
        toolbar.addView(title, LinearLayout.LayoutParams(
            0,
            ViewGroup.LayoutParams.MATCH_PARENT,
            1f,
        ))

        progress = ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal).apply {
            isIndeterminate = true
        }
        webView = WebView(this).apply {
            setBackgroundColor(Color.rgb(226, 232, 240))
            settings.javaScriptEnabled = true
            settings.domStorageEnabled = true
            settings.builtInZoomControls = true
            settings.displayZoomControls = false
            settings.loadWithOverviewMode = true
            settings.useWideViewPort = true
            addJavascriptInterface(PrintBridge(), "PermitAndroid")
            webViewClient = object : WebViewClient() {
                override fun onPageFinished(view: WebView, pageUrl: String) {
                    this@PermitPreviewActivity.progress.visibility = android.view.View.GONE
                    view.evaluateJavascript(
                        "window.print=function(){PermitAndroid.printDocument();};",
                        null,
                    )
                }
            }
            loadUrl(
                url,
                mapOf(
                    "Authorization" to "Bearer $token",
                    "Accept" to "text/html",
                ),
            )
        }
        root.addView(toolbar, LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            ViewGroup.LayoutParams.WRAP_CONTENT,
        ))
        root.addView(progress, LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            5,
        ))
        root.addView(webView, LinearLayout.LayoutParams(
            ViewGroup.LayoutParams.MATCH_PARENT,
            0,
            1f,
        ))
        setContentView(root)
    }

    override fun onDestroy() {
        if (::webView.isInitialized) {
            webView.removeJavascriptInterface("PermitAndroid")
            webView.destroy()
        }
        super.onDestroy()
    }

    inner class PrintBridge {
        @JavascriptInterface
        fun printDocument() {
            runOnUiThread {
                val manager = getSystemService(Context.PRINT_SERVICE) as PrintManager
                val adapter = webView.createPrintDocumentAdapter("دوزوله راننده")
                manager.print(
                    "دوزوله راننده",
                    adapter,
                    PrintAttributes.Builder().build(),
                )
            }
        }
    }
}
