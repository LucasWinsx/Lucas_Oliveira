<?php
/* Conexão com o banco via PDO (padrão Singleton: uma única conexão por requisição).
   Ajuste host, banco, usuário e senha se o seu XAMPP for diferente do padrão. */
date_default_timezone_set('America/Sao_Paulo');   // hora de Brasília (o php.ini do XAMPP vem com Europe/Berlin)

/* Credenciais do banco: por padrão usa o XAMPP (root sem senha). Para usar outro usuário/senha,
   copie db.local.exemplo.php para db.local.php (fica no .gitignore) e preencha. */
$GLOBALS['__db_cfg'] = ['host' => 'localhost', 'banco' => 'lucas_oliveira', 'usuario' => 'root', 'senha' => ''];
if (is_file(__DIR__ . '/db.local.php')) $GLOBALS['__db_cfg'] = array_merge($GLOBALS['__db_cfg'], (array)require __DIR__ . '/db.local.php');

class Database {
    private static ?PDO $pdo = null;

    public static function conectar(): PDO {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=' . $GLOBALS['__db_cfg']['host'] . ';dbname=' . $GLOBALS['__db_cfg']['banco'] . ';charset=utf8mb4',
                    $GLOBALS['__db_cfg']['usuario'],
                    $GLOBALS['__db_cfg']['senha'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-03:00'",   // mesmo fuso do PHP nos TIMESTAMP
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Falha ao conectar no banco: ' . $e->getMessage());
                exit('Não foi possível conectar ao banco de dados. Verifique se o MySQL do XAMPP está ligado '
                   . 'e se o script sql/script.sql foi importado.');
            }
        }
        return self::$pdo;
    }
}

// compatibilidade com as páginas antigas que usam a variável global $pdo
$pdo = Database::conectar();
