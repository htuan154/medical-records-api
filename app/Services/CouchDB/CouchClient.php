<?php

namespace App\Services\CouchDB;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CouchClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected ?string $db = null;

    public function __construct()
    {
        $scheme   = (string) config('couchdb.scheme', 'http');   // <-- thêm scheme
        $host     = (string) config('couchdb.host', '127.0.0.1');
        $port     = (int)    config('couchdb.port', 5984);

        $this->baseUrl  = rtrim("{$scheme}://{$host}:{$port}", '/');
        $this->username = (string) config('couchdb.username', '');
        $this->password = (string) config('couchdb.password', '');
    }

    /** Chọn database (có áp dụng prefix nếu có) */
    public function db(string $name): self
    {
        $clone  = clone $this;
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
            ->asJson()
            ->timeout(10)
            ->connectTimeout(5);
    }

    /** Đảm bảo đã chọn DB trước khi gọi */
    protected function ensureDb(): void
    {
        if (!$this->db) {
            throw new RuntimeException('CouchClient: database is not selected. Hãy gọi ->db("users") trước.');
        }
    }

    /** Parse JSON an toàn (ném lỗi khi JSON invalid) */
    protected function parseJson($response): array
    {
        $data = $response->json();
        if ($data === null && $response->body() !== 'null' && $response->body() !== '') {
            throw new RuntimeException('CouchClient: cannot parse JSON response: ' . $response->body());
        }
        return is_array($data) ? $data : [];
    }

    /**
     * Chuẩn hoá query: booleans -> 'true' / 'false' đúng chuẩn CouchDB.
     * Đồng thời xử lý trường hợp bị bao bởi dấu ngoặc kép "\"1\""...
     */
    private function normalizeParams(array $params): array
    {
        $boolKeys = [
            'include_docs','reduce','group','descending','conflicts',
            'inclusive_end','stable','update','attachments','att_encoding_info'
        ];

        foreach ($params as $k => $v) {
            if (!in_array($k, $boolKeys, true)) continue;

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
        $this->ensureDb();
        $params = array_merge(['include_docs' => true], $params);
        $params = $this->normalizeParams($params);

        try {
            $res = $this->http()->get("/{$this->db}/_all_docs", $params);
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB allDocs error: '.$e->getMessage(), 0, $e);
        }
    }

    /** GET /{db}/{id} */
    public function get(string $id): array
    {
        $this->ensureDb();
        try {
            $res = $this->http()->get("/{$this->db}/".ltrim($id, '/'));
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB get error: '.$e->getMessage(), 0, $e);
        }
    }

    /** POST /{db} */
    public function create(array $doc): array
    {
        $this->ensureDb();
        try {
            $res = $this->http()->post("/{$this->db}", $doc);
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB create error: '.$e->getMessage(), 0, $e);
        }
    }

    /** PUT /{db}/{id} */
    public function put(string $id, array $docWithRev): array
    {
        $this->ensureDb();
        try {
            $res = $this->http()->put("/{$this->db}/".ltrim($id, '/'), $docWithRev);
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB put error: '.$e->getMessage(), 0, $e);
        }
    }

    /** DELETE /{db}/{id}?rev=xxx */
    public function delete(string $id, string $rev): array
    {
        $this->ensureDb();

        if (empty($rev)) {
            return [
                'ok' => false,
                'error' => 'bad_request',
                'reason' => 'Missing revision parameter'
            ];
        }

        try {

            $url = "/{$this->db}/" . ltrim($id, '/') . "?rev=" . urlencode($rev);

            $res = $this->http()
                ->withOptions(['http_errors' => false])  // Không throw exception cho 4xx/5xx
                ->delete($url);

            // Parse response
            $data = $this->parseJson($res);

            // Nếu status không phải 200/201/202, CouchDB sẽ trả về error trong body
            if ($res->status() >= 400) {
                return $data; // Trả về error response từ CouchDB
            }

            return $data;

        } catch (ConnectionException|RequestException $e) {
            return [
                'ok' => false,
                'error' => 'connection_error',
                'reason' => $e->getMessage()
            ];
        }
    }

    /** GET /{db}/_design/{design}/_view/{view} */
    public function view(string $design, string $view, array $params = []): array
    {
        $this->ensureDb();
        $params = $this->normalizeParams($params);

        try {
            $res = $this->http()->get("/{$this->db}/_design/{$design}/_view/{$view}", $params);
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB view error: '.$e->getMessage(), 0, $e);
        }
    }

    /** POST /{db}/_bulk_docs */
    public function bulk(array $docs): array
    {
        $this->ensureDb();
        try {
            $res = $this->http()->post("/{$this->db}/_bulk_docs", ['docs' => $docs]);
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB bulk error: '.$e->getMessage(), 0, $e);
        }
    }

    /** HEAD /{db}/{id} -> doc có tồn tại không */
    public function exists(string $id): bool
    {
        $this->ensureDb();
        $res = $this->http()->withOptions(['http_errors' => false])->head("/{$this->db}/".ltrim($id, '/'));
        return $res->status() === 200;
    }

    /** PUT design doc (id kiểu _design/users) */
    public function putDesign(string $designId, array $doc): array
    {
        $this->ensureDb();
        try {
            $res = $this->http()->put("/{$this->db}/".ltrim($designId, '/'), $doc);
            return $this->parseJson($res);
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('CouchDB putDesign error: '.$e->getMessage(), 0, $e);
        }
    }

    /* ===== Helpers bổ sung ===== */

    /** Ping CouchDB: GET /_up -> {"status":"ok"} */
    public function up(): array
    {
        $res = $this->http()->get('/_up');
        return $this->parseJson($res);
    }
}
