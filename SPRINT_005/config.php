<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Configuración (XAMPP por defecto) ---------- */
const DB_HOST = 'localhost';
const DB_NAME = 'fueguitos';
const DB_USER = 'root';
const DB_PASS = '';

// Link de billetera virtual (Mercado Pago, Ualá, etc.). Cambialo por el tuyo.
const DONATION_URL = 'https://link.mercadopago.com.ar/fueguitos';

// En XAMPP local no hay envío de mails: si es true, el link de recuperación se muestra en pantalla.
const DEV_MODE = true;

const UPLOAD_DIR = __DIR__ . '/uploads/';

/* ---------- Base de datos ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('No se pudo conectar a la base de datos. Revisá que MySQL esté iniciado en XAMPP y que hayas importado sql/fueguitos.sql.');
        }
    }
    return $pdo;
}

/* ---------- Helpers ---------- */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Solicitud inválida (token CSRF).');
    }
}

/* ---------- Sesión y permisos ---------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['rol'] ?? '') === 'admin';
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'     => (int)$u['id'],
        'nombre' => $u['nombre'],
        'email'  => $u['email'],
        'rol'    => $u['rol'],
    ];
}

function require_login(): void
{
    if (!current_user()) {
        flash('warning', 'Iniciá sesión para ver el catálogo.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!current_user()) {
        flash('warning', 'Iniciá sesión para continuar.');
        redirect('login.php');
    }
    if (!is_admin()) {
        http_response_code(403);
        exit('Acceso restringido a administradores.');
    }
}
