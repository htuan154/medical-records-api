<?php
namespace App\Repositories\CouchDB;

use App\Services\CouchDB\CouchClient;

abstract class BaseCouchRepository
{
    protected CouchClient $client;
    protected string $db;

    public function __construct(CouchClient $client)
    {
        $this->client = $client->db($this->db);
    }

    public function all(array $params = []): array   { 
        $result = $this->client->allDocs($params);
        if (is_object($result)) {
            $result = json_decode(json_encode($result), true);
        }
        return $result;
    }
    public function get(string $id): array           { 
        $result = $this->client->get($id);
        if (is_object($result)) {
            $result = json_decode(json_encode($result), true);
        }
        return $result;
    }
    public function create(array $doc): array        { return $this->client->create($doc); }
    public function update(string $id, array $doc): array { return $this->client->put($id, $doc); }

    public function delete(string $id, string $rev): array
    {
        if (!$rev) {
            return ['ok' => false, 'error' => 'conflict', 'reason' => 'Missing rev'];
        }

        return $this->client->delete($id, $rev);
    }

    public function view(string $design, string $view, array $params = []): array
    { 
        $result = $this->client->view($design, $view, $params);
        // Đảm bảo luôn trả về array, không phải object
        if (is_object($result)) {
            $result = json_decode(json_encode($result), true);
        }
        return $result;
    }
}
