<?php

namespace App\Services\CouchDB;

use Illuminate\Support\Facades\Http;

class CouchClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected ?string $db = null;

    public function __construct()
    {
        $cfg = config('couchdb');
        $this->baseUrl  = sprintf('http://%s:%s', $cfg['host'], $cfg['port']);
        $this->username = (string) $cfg['username'];
        $this->password = (string) $cfg['password'];
    }

    public function db(string $name): self
    {
        $clone = clone $this;
        $prefix = (string) config('couchdb.prefix', '');
        $clone->setDb($prefix.$name);
        return $clone;
    }

    public function setDb(string $db): void
    {
        $this->db = $db;
    }

    protected function http()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson();
    }

    public function info(): array
    {
        return $this->http()->get('/')->json();
    }

    public function allDbs(): array
    {
        return $this->http()->get('/_all_dbs')->json();
    }

    public function allDocs(array $params = []): array
    {
        $params = array_merge(['include_docs' => true], $params);
        return $this->http()->get("/{$this->db}/_all_docs", $params)->json();
    }

    public function get(string $id): array
    {
        return $this->http()->get("/{$this->db}/{$id}")->json();
    }

    public function view(string $design, string $view, array $params = []): array
    {
        return $this->http()->get("/{$this->db}/_design/{$design}/_view/{$view}", $params)->json();
    }

    public function create(array $doc): array
    {
        return $this->http()->post("/{$this->db}", $doc)->json();
    }

    public function put(string $id, array $docWithRev): array
    {
        return $this->http()->put("/{$this->db}/{$id}", $docWithRev)->json();
    }

    public function delete(string $id, string $rev): array
    {
        return $this->http()->delete("/{$this->db}/{$id}", ['rev' => $rev])->json();
    }

    public function bulk(array $docs): array
    {
        return $this->http()->post("/{$this->db}/_bulk_docs", ['docs' => $docs])->json();
    }
}
