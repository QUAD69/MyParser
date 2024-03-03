<?php

namespace Quad69\MyParser;

use Exception;
use function base64_encode;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function stream_context_create;

/**
 * Основной класс для запросов к экземпляру MyParser.
 * @author QUAD69
 */
class Worker
{
    /**
     * @var string Хост.
     */
    public readonly string $host;

    /**
     * @var string Логин.
     */
    public readonly string $username;

    /**
     * @var string Пароль.
     */
    public readonly string $password;

    public function __construct(string $host, string $username = '', string $password = '')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Возвращает основную информацию о текущем экземпляре.
     *
     * @return array{
     *     worker_id: int,
     *     threads: int,
     *     working: bool,
     *     memory: array{
     *         free: int,
     *         total: int
     *     },
     *     summary: array{
     *         domains: int,
     *         proxies: int,
     *         workers: int,
     *         tasks: int
     *     }
     * }
     * @throws Exception Если не удалось выполнить запрос.
     */
    public function me(): array
    {
        return $this->request('me');
    }

    /**
     * Возвращает информацию обо всех экземплярах зарегистрированных в системе.
     *
     * @return array<array{
     *     id: int,
     *     host: string,
     *     port: int,
     *     threads: int,
     *     working: bool,
     *     memory: array{
     *         free: int,
     *         total: int
     *     },
     *     acquired: array{
     *         queries: int,
     *         proxies: int
     *     },
     *     updated_at: int,
     *     connected_at: int
     * }>
     * @throws Exception Если не удалось выполнить запрос.
     */
    public function workers(): array
    {
        return $this->request('workers');
    }

    protected function request(string $path, string $method = 'GET', ?array $data = null): array
    {
        $headers = [
            'User-Agent: MyParser/1.0.3 (https://github.com/QUAD69/MyParser) PHP/' . PHP_VERSION,
            'Accept: application/json'
        ];

        if ($this->username or $this->password) {
            $encoded = base64_encode($this->username . ':' . $this->password);
            $headers[] = 'Authorization: Basic ' . $encoded;
        }

        if ($data !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null
            ]
        ]);

        if (!$response = @file_get_contents("http://{$this->host}/api/{$path}", false, $context) or
            !$response = @json_decode($response, true)) {

            throw new Exception("Invalid response from http://{$this->host}/api/{$path}");
        }

        return $response;
    }
}