import java.io.FileInputStream
import java.util.Properties

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

val keystoreProperties = Properties()
val keystorePropertiesFile = rootProject.file("key.properties")

if (keystorePropertiesFile.exists()) {
    FileInputStream(keystorePropertiesFile).use { keystoreProperties.load(it) }
}

val releaseStoreFile =
    keystoreProperties.getProperty("storeFile")
        ?: System.getenv("ANDROID_KEYSTORE_PATH")
val releaseStorePassword =
    keystoreProperties.getProperty("storePassword")
        ?: System.getenv("ANDROID_KEYSTORE_PASSWORD")
val releaseKeyAlias =
    keystoreProperties.getProperty("keyAlias")
        ?: System.getenv("ANDROID_KEY_ALIAS")
val releaseKeyPassword =
    keystoreProperties.getProperty("keyPassword")
        ?: System.getenv("ANDROID_KEY_PASSWORD")

val hasReleaseSigning =
    !releaseStoreFile.isNullOrBlank() &&
    !releaseStorePassword.isNullOrBlank() &&
    !releaseKeyAlias.isNullOrBlank() &&
    !releaseKeyPassword.isNullOrBlank()

val isReleaseTaskRequested =
    gradle.startParameter.taskNames.any { taskName ->
        taskName.contains("Release", ignoreCase = true)
    }

if (isReleaseTaskRequested && !hasReleaseSigning) {
    throw GradleException(
        "Release signing is not configured. Add android/key.properties or set " +
            "ANDROID_KEYSTORE_PATH, ANDROID_KEYSTORE_PASSWORD, ANDROID_KEY_ALIAS, " +
            "and ANDROID_KEY_PASSWORD before building a release APK.",
    )
}

android {
    namespace = "com.digitalclearance.mobile"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        applicationId = "com.digitalclearance.mobile"
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        create("release") {
            if (hasReleaseSigning) {
                storeFile = file(releaseStoreFile!!)
                storePassword = releaseStorePassword
                keyAlias = releaseKeyAlias
                keyPassword = releaseKeyPassword
            }
        }
    }

    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
        }
    }
}

flutter {
    source = "../.."
}
