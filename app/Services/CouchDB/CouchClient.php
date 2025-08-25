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
        $host = (string) config('couchdb.host', '127.0.0.1');
        $port = (int)    config('couchdb.port', 5984);

        $this->baseUrl  = "http://{$host}:{$port}";
        $this->username = (string) config('couchdb.username', '');
        $this->password = (string) config('couchdb.password', '');
    }

    /** Chọn database */
    public function db(string $name): self
    {
        $clone = clone $this;
        $prefix = (string) config('couchdb.prefix', '');
        $clone->db = $prefix . $name;
        return $clone;
    }

    /** HTTP client chung */
    protected function http()
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Chuẩn hoá query: booleans -> 'true' / 'false' đúng chuẩn CouchDB.
     * Đồng thời “gột rửa” cả các trường hợp bị bao trong dấu ngoặc kép như "\"1\""…
     */
    private function normalizeParams(array $params): array
    {
        // Các key boolean CouchDB hỗ trợ ở query string
        $boolKeys = [
            'include_docs','reduce','group','descending','conflicts',
            'inclusive_end','stable','update','attachments','att_encoding_info'
        ];

        foreach ($params as $k => $v) {
            if (!in_array($k, $boolKeys, true)) {
                continue;
            }

            // Nếu bị json_encode thành chuỗi có ngoặc kép, bóc ra:
            if (is_string($v)) {
                $trim = trim($v);
                if (strlen($trim) >= 2 && $trim[0] === '"' && substr($trim, -1) === '"') {
                    $v = substr($trim, 1, -1);
                }
            }

            $truthy = [true, 1, '1', 'true', 'TRUE', 'on', 'yes'];
            $falsy  = [false, 0, '0', 'false', 'FALSE', 'off', 'no', null, ''];

            if (in_array($v, $truthy, true)) {
                $params[$k] = 'true';
            } elseif (in_array($v, $falsy, true)) {
                $params[$k] = 'false';
            } else {
                // Giá trị lạ -> ép false cho an toàn
                $params[$k] = 'false';
            }
        }

        return $params;
    }

    /* ===========================
       APIs thường dùng
       =========================== */

    /** GET /{db}/_all_docs */
    public function allDocs(array $params = []): array
    {
        // mặc định include_docs = true để trả full doc
        $params = array_merge(['include_docs' => true], $params);
        $params = $this->normalizeParams($params);

        return $this->http()->get("/{$this->db}/_all_docs", $params)->json();
    }

    /** GET /{db}/{id} */
    public function get(string $id): array
    {
        return $this->http()->get("/{$this->db}/{$id}")->json();
    }

    /** POST /{db} */
    public function create(array $doc): array
    {
        return $this->http()->post("/{$this->db}", $doc)->json();
    }

    /** PUT /{db}/{id} */
    public function put(string $id, array $docWithRev): array
    {
        return $this->http()->put("/{$this->db}/{$id}", $docWithRev)->json();
    }

    /** DELETE /{db}/{id}?rev=xxx */
    public function delete(string $id, string $rev): array
    {
        return $this->http()->delete("/{$this->db}/{$id}", ['rev' => $rev])->json();
    }

    /** GET /{db}/_design/{design}/_view/{view} */
    public function view(string $design, string $view, array $params = []): array
    {
        $params = $this->normalizeParams($params);
        return $this->http()->get("/{$this->db}/_design/{$design}/_view/{$view}", $params)->json();
    }

    /** POST /{db}/_bulk_docs */
    public function bulk(array $docs): array
    {
        return $this->http()->post("/{$this->db}/_bulk_docs", ['docs' => $docs])->json();
    }

    /** HEAD /{db}/{id} -> doc có tồn tại không */
    public function exists(string $id): bool
    {
        $resp = $this->http()->withOptions(['http_errors' => false])->head("/{$this->db}/{$id}");
        return $resp->status() === 200;
    }

    /** PUT design doc */
    public function putDesign(string $designId, array $doc): array
    {
        return $this->http()->put("/{$this->db}/{$designId}", $doc)->json();
    }
}
