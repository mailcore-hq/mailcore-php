<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

// Executado somente na imagem de desenvolvimento, com relatório montado à parte.
$arquivo = '/tmp/mailcore-phpunit.xml';
passthru('php -d zend.exception_ignore_args=0 vendor/bin/phpunit --log-junit '.escapeshellarg($arquivo), $codigo);
$xml = is_file($arquivo) ? simplexml_load_file($arquivo) : false;
$suite = $xml === false ? null : $xml->testsuite[0];
$relatorio = [
    'data_utc' => gmdate('c'),
    'php' => PHP_VERSION,
    'phpunit' => PHPUnit\Runner\Version::id(),
    'imagem' => getenv('MAILCORE_IMAGEM_TESTE') ?: 'nao_informada',
    'testes' => $suite === null ? 0 : (int) $suite['tests'],
    'assercoes' => $suite === null ? 0 : (int) $suite['assertions'],
    'falhas' => $suite === null ? 1 : (int) $suite['failures'],
    'erros' => $suite === null ? 1 : (int) $suite['errors'],
    'ignorados' => $suite === null ? 0 : (int) $suite['skipped'],
    'resultado' => $codigo === 0 && $suite !== null && (int) $suite['skipped'] === 0 ? 'aprovado' : 'reprovado',
    'rede_externa' => false,
    'instalacao_host' => false,
];
$destino = '/relatorios/php-'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.json';
file_put_contents($destino, json_encode($relatorio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
exit($relatorio['resultado'] === 'aprovado' ? 0 : 1);
