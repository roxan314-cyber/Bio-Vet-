import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Locale;
import java.util.Map;
import java.util.Properties;

public class DatabaseConfig {
    public static Connection getConnection() throws SQLException {
        Map<String, String> dotenv = loadDotEnv();
        String host = getSetting("DB_HOST", "database.default.hostname", dotenv, "127.0.0.1").trim();
        String database = getSetting("DB_NAME", "database.default.database", dotenv, "biovet_db").trim();
        String username = getSetting("DB_USER", "database.default.username", dotenv, "root").trim();
        String password = getSetting("DB_PASS", "database.default.password", dotenv, "");
        String portValue = getSetting("DB_PORT", "DB_PORT", dotenv, "3306").trim();
        String sslMode = getSetting("DB_SSL_MODE", "DB_SSL_MODE", dotenv, "PREFERRED").trim()
            .toUpperCase(Locale.ROOT);

        if (host.isEmpty() || database.isEmpty() || username.isEmpty()) {
            throw new SQLException("DB_HOST, DB_NAME, and DB_USER must not be empty.");
        }

        int port;
        try {
            port = Integer.parseInt(portValue);
        } catch (NumberFormatException exception) {
            throw new SQLException("DB_PORT must be a number between 1 and 65535.", exception);
        }
        if (port < 1 || port > 65535) {
            throw new SQLException("DB_PORT must be a number between 1 and 65535.");
        }
        if (!sslMode.matches("DISABLED|PREFERRED|REQUIRED|VERIFY_CA|VERIFY_IDENTITY")) {
            throw new SQLException(
                "DB_SSL_MODE must be DISABLED, PREFERRED, REQUIRED, VERIFY_CA, or VERIFY_IDENTITY."
            );
        }

        String url = "jdbc:mysql://" + host + ":" + port + "/" + database;
        Properties properties = new Properties();
        properties.setProperty("user", username);
        properties.setProperty("password", password);
        properties.setProperty("sslMode", sslMode);
        properties.setProperty("allowPublicKeyRetrieval", "false");
        properties.setProperty("serverTimezone", "UTC");
        properties.setProperty("characterEncoding", "UTF-8");

        return DriverManager.getConnection(url, properties);
    }

    private static String getSetting(
        String key,
        String dotenvKey,
        Map<String, String> dotenv,
        String defaultValue
    ) {
        String environmentValue = System.getenv(key);
        if ((environmentValue == null || environmentValue.isEmpty()) && "DB_PASS".equals(key)) {
            environmentValue = System.getenv("DB_PASSWORD");
        }
        if (environmentValue != null && !environmentValue.isEmpty()) {
            return environmentValue;
        }
        if (dotenv.containsKey(key)) {
            return dotenv.get(key);
        }
        if ("DB_PASS".equals(key) && dotenv.containsKey("DB_PASSWORD")) {
            return dotenv.get("DB_PASSWORD");
        }
        return dotenv.getOrDefault(dotenvKey, defaultValue);
    }

    private static Map<String, String> loadDotEnv() throws SQLException {
        String configuredPath = System.getProperty("biovet.env.file");
        Path dotenvPath;
        try {
            dotenvPath = configuredPath == null || configuredPath.isEmpty()
                ? findDotEnv()
                : Paths.get(configuredPath);
        } catch (RuntimeException exception) {
            throw new SQLException("The biovet.env.file path is invalid.", exception);
        }

        Map<String, String> values = new HashMap<>();
        if (dotenvPath == null || !Files.isRegularFile(dotenvPath)) {
            return values;
        }

        try {
            for (String line : Files.readAllLines(dotenvPath, StandardCharsets.UTF_8)) {
                String trimmed = line.trim();
                if (trimmed.isEmpty() || trimmed.startsWith("#")) {
                    continue;
                }

                int separator = trimmed.indexOf('=');
                if (separator < 1) {
                    continue;
                }

                String key = trimmed.substring(0, separator).trim();
                String value = trimmed.substring(separator + 1).trim();
                if (value.length() >= 2
                    && ((value.startsWith("\"") && value.endsWith("\""))
                    || (value.startsWith("'") && value.endsWith("'")))) {
                    value = value.substring(1, value.length() - 1);
                }
                values.put(key, value);
            }
        } catch (IOException exception) {
            throw new SQLException("Could not read database configuration from " + dotenvPath, exception);
        }
        return values;
    }

    private static Path findDotEnv() {
        for (Path directory = Paths.get("").toAbsolutePath();
             directory != null;
             directory = directory.getParent()) {
            Path localDotEnv = directory.resolve(".env");
            if (Files.isRegularFile(localDotEnv)) {
                return localDotEnv;
            }

            Path projectDotEnv = directory.resolve("Proyecto").resolve(".env");
            if (Files.isRegularFile(projectDotEnv)) {
                return projectDotEnv;
            }
        }
        return null;
    }
}