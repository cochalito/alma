<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Reservation;

// Disable foreign key checks
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Truncate target tables
echo "Cleaning existing data...\n";
DB::table('reservation_histories')->truncate();
DB::table('reservation_product')->truncate();
DB::table('reservations')->truncate();
DB::table('customers')->truncate();

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Data cleared.\n";

// Connect to almudena_sistema
$sourceDb = env('DB_HOST', '127.0.0.1');
$sourceUser = env('DB_USERNAME', 'root');
$sourcePass = env('DB_PASSWORD', 'sample');
$pdoStr = "mysql:host=$sourceDb;dbname=almudena_sistema;charset=utf8mb4";
$pdo = new PDO($pdoStr, $sourceUser, $sourcePass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "Migrating Customers (Huespedes)...\n";
// Migrate customers
$stmt = $pdo->query("SELECT * FROM cliente");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$customerCount = 0;
foreach ($clientes as $cli) {
    // Map status enum('0','1')
    $status = '1';
    if ($cli['cli_estado'] === 'Inactivo') { // guess the string value
        $status = '0';
    }

    // Map gender/doc_type if we have it? The current table only has document_type enum('1','2','3','4'), document_number, firstname, lastname, email, cellphone.
    DB::table('customers')->insert([
        'id' => $cli['cli_id'],
        'document_type' => '1', // Default or map if known
        'document_number' => $cli['cli_ci_numero'] ?: null,
        'firstname' => $cli['cli_nombre'] ?: 'N/A',
        'lastname' => $cli['cli_apellido'] ?: 'N/A',
        'email' => $cli['cli_email'] ?: 'no-email@example.com',
        'cellphone' => $cli['cli_telefono'] ?: null,
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    $customerCount++;
}
echo "Migrated $customerCount customers.\n";

echo "Migrating Reservations...\n";
// Migrate reservations
$stmt = $pdo->query("SELECT * FROM reserva");
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resCount = 0;
foreach ($reservas as $res) {
    // Determine location if possible, otherwise rely on the dep_id or just set a default "LA PAZ".
    // Wait, check_out and check_in need to be date times
    $statusMap = [
        'Pendiente' => '1',
        'Confirmada' => '2',
        'CheckIn' => '2',
        'Concluida' => '3',
        'Cancelada' => '4'
    ];
    // Map statuses from almudena_sistema:
    $estado_str = $res['res_estado'];
    $estado = '1'; // Default
    if (stripos($estado_str, 'pend') !== false) {
        $estado = '1';
    } elseif (stripos($estado_str, 'confir') !== false || stripos($estado_str, 'check') !== false || stripos($estado_str, 'curso') !== false || stripos($estado_str, 'aprob') !== false) {
        $estado = '2';
    } elseif (stripos($estado_str, 'conclu') !== false || stripos($estado_str, 'finaliz') !== false) {
        $estado = '3';
    } elseif (stripos($estado_str, 'cancel') !== false) {
        $estado = '4';
    }

    // Some basic mapping for employee_id since it might not be in the source 'sol_id'. Or just 1.
    $employee_id = 1;
    if ($res['sol_id'] > 0) {
        $employee_id = $res['sol_id'];
    }

    DB::table('reservations')->insert([
        'id' => $res['res_id'],
        'employee_id' => $employee_id,
        'departament_id' => $res['dep_id'],
        'customer_id' => $res['cli_id'],
        'location' => 'LP', // Default location. Let's see if we should fetch it from departament? We can update later
        'check_in' => $res['res_fecha_inicio'],
        'check_out' => $res['res_fecha_fin'],
        'total_stay_cost' => $res['res_estadia_total'],
        'total_extra_cost' => 0, // Not sure if res_cobros_total is extra cost or total paid? Let's keep 0 as default, or from almudena?
        'requests' => $res['res_solicitudes'],
        'comments' => $res['res_comentarios'],
        'status' => $estado,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    $resCount++;
}

// Update locations based on departament
DB::statement("UPDATE reservations r JOIN departament d ON r.departament_id = d.id SET r.location = d.location");

echo "Migrated $resCount reservations.\n";
echo "Migration complete.\n";
