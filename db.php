
/**/
 * FALCONS Theater - Database Connection
 * Centralized database connection with security enhancements
 */

$host = "localhost";
$user = "root";
$password = "";
$database = "cinema_db";

// Establishing a connection to the database
$conn = mysqli_connect($host, $user, $password, $database);

// Check if the connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set the character set to UTF-8 for handling special characters
mysqli_set_charset($conn, "utf8mb4");

function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition)
{
    $schemaResult = mysqli_query($conn, "SELECT DATABASE()");
    $schemaRow = $schemaResult ? mysqli_fetch_row($schemaResult) : null;
    $schema = mysqli_real_escape_string($conn, (string) ($schemaRow[0] ?? $GLOBALS['database']));
    $tableSafe = mysqli_real_escape_string($conn, $table);
    $columnSafe = mysqli_real_escape_string($conn, $column);
    $columnCheck = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = '$schema'
           AND TABLE_NAME = '$tableSafe'
           AND COLUMN_NAME = '$columnSafe'"
    );
    $columnRow = $columnCheck ? mysqli_fetch_assoc($columnCheck) : ['column_count' => 0];

    if ((int) ($columnRow['column_count'] ?? 0) === 0) {
        @mysqli_query($conn, "ALTER TABLE `$tableSafe` ADD `$columnSafe` $definition");
    }
}

ensureColumnExists($conn, 'bookings', 'theater', "varchar(20) NOT NULL DEFAULT 'T1 - 4K' AFTER movie");
ensureColumnExists($conn, 'payments', 't
