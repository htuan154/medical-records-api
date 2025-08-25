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

    public function all(array $params = []): array   { return $this->client->allDocs($params); }
    public function get(string $id): array           { return $this->client->get($id); }
    public function create(array $doc): array        { return $this->client->create($doc); }
    public function update(string $id, array $doc): array { return $this->client->put($id, $doc); }
    public function delete(string $id, string $rev): array { return $this->client->delete($id, $rev); }
    public function view(string $design, string $view, array $params = []): array
    { return $this->client->view($design, $view, $params); }
}
