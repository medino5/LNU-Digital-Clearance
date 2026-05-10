package com.digitalclearance.mobile

import android.content.ContentValues
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.embedding.android.FlutterActivity
import io.flutter.plugin.common.MethodChannel
import java.io.File
import java.io.FileOutputStream

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "digital_clearance/downloads"
        ).setMethodCallHandler { call, result ->
            if (call.method != "savePdfToDownloads") {
                result.notImplemented()
                return@setMethodCallHandler
            }

            val fileName = call.argument<String>("fileName") ?: "student-clearance.pdf"
            val bytes = call.argument<ByteArray>("bytes")

            if (bytes == null) {
                result.error("INVALID_BYTES", "No PDF bytes were provided.", null)
                return@setMethodCallHandler
            }

            try {
                result.success(savePdfToDownloads(fileName, bytes))
            } catch (error: Exception) {
                result.error(
                    "DOWNLOAD_SAVE_FAILED",
                    error.message ?: "Unable to save the PDF to Downloads.",
                    null
                )
            }
        }
    }

    private fun savePdfToDownloads(fileName: String, bytes: ByteArray): String {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val values = ContentValues().apply {
                put(MediaStore.Downloads.DISPLAY_NAME, fileName)
                put(MediaStore.Downloads.MIME_TYPE, "application/pdf")
                put(MediaStore.Downloads.IS_PENDING, 1)
            }

            val resolver = applicationContext.contentResolver
            val uri = resolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, values)
                ?: throw IllegalStateException("Unable to create a Downloads entry.")

            resolver.openOutputStream(uri)?.use { output ->
                output.write(bytes)
            } ?: throw IllegalStateException("Unable to open the Downloads file.")

            values.clear()
            values.put(MediaStore.Downloads.IS_PENDING, 0)
            resolver.update(uri, values, null, null)

            return uri.toString()
        }

        val downloadsDirectory = Environment.getExternalStoragePublicDirectory(
            Environment.DIRECTORY_DOWNLOADS
        )
        if (!downloadsDirectory.exists()) {
            downloadsDirectory.mkdirs()
        }

        val file = File(downloadsDirectory, fileName)
        FileOutputStream(file).use { output ->
            output.write(bytes)
        }

        return file.absolutePath
    }
}
