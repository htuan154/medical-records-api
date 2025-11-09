<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CouchDB\MedicalTestService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @OA\Tag(
 *     name="Medical Tests",
 *     description="API endpoints for managing medical tests"
 * )
 */
class MedicalTestController extends Controller
{
    public function __construct(private MedicalTestService $svc) {}

    private function error(Throwable $e, int $code = 500)
    {
        return response()->json([
            'error'   => class_basename($e),
            'message' => $e->getMessage(),
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medical-tests",
     *     tags={"Medical Tests"},
     *     summary="Danh sách xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=50)),
     *     @OA\Parameter(name="skip", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Parameter(name="patient_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="doctor_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="medical_record_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="test_type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="start", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Danh sách xét nghiệm y tế"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function index(Request $req)
    {
        try {
            $limit = (int) $req->query('limit', 50);
            $skip  = (int) $req->query('skip', 0);
            $filters = [
                'patient_id'       => $req->query('patient_id'),
                'doctor_id'        => $req->query('doctor_id'),
                'medical_record_id'=> $req->query('medical_record_id'),
                'test_type'        => $req->query('test_type'),
                'start'            => $req->query('start'),
                'end'              => $req->query('end'),
                'status'           => $req->query('status'),
            ];

            $q = $req->query('q');
            $result = $this->svc->list($limit, $skip, $filters);

            // Tìm kiếm nâng cao: theo tên xét nghiệm và loại, hỗ trợ không dấu
            if ($q) {
                $keyword = mb_strtolower(trim($q));
                $keyword = preg_replace('/\s+/', ' ', $keyword);
                $keyword_no_sign = self::vnStrNoSign($keyword);
                $result['rows'] = array_filter($result['rows'], function($row) use ($keyword, $keyword_no_sign) {
                    $doc = $row['doc'] ?? $row;
                    $testName = mb_strtolower(trim($doc['test_info']['test_name'] ?? ''));
                    $testType = mb_strtolower(trim($doc['test_info']['test_type'] ?? ''));
                    $testName = preg_replace('/\s+/', ' ', $testName);
                    $testType = preg_replace('/\s+/', ' ', $testType);
                    $testName_no_sign = MedicalTestController::vnStrNoSign($testName);
                    $testType_no_sign = MedicalTestController::vnStrNoSign($testType);
                    return (strpos($testName, $keyword) !== false || strpos($testType, $keyword) !== false
                        || strpos($testName_no_sign, $keyword_no_sign) !== false || strpos($testType_no_sign, $keyword_no_sign) !== false);
                });
                $result['total_rows'] = count($result['rows']);
            }

            return response()->json($result, 200);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/medical-tests/{id}",
     *     tags={"Medical Tests"},
     *     summary="Chi tiết xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Thông tin xét nghiệm y tế"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function show(string $id)
    {
        $res = $this->svc->find($id);
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/medical-tests",
     *     tags={"Medical Tests"},
     *     summary="Tạo xét nghiệm y tế mới",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="patient_id", type="string", example="patient_123"),
     *             @OA\Property(property="doctor_id", type="string", example="doctor_456"),
     *             @OA\Property(property="medical_record_id", type="string", example="record_789"),
     *             @OA\Property(property="test_info.test_type", type="string", example="blood_test"),
     *             @OA\Property(property="test_info.test_name", type="string", example="Xét nghiệm máu tổng quát"),
     *             @OA\Property(property="test_info.ordered_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="test_info.sample_collected_date", type="string", format="date", example="2024-01-16"),
     *             @OA\Property(property="test_info.result_date", type="string", format="date", example="2024-01-17"),
     *             @OA\Property(property="results", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="interpretation", type="string", example="Kết quả bình thường"),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="lab_technician", type="string", example="tech_123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tạo thành công"),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=422, description="Lỗi validation"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function store(Request $req)
    {
        try {
            $data = $req->validate([
                '_id'                         => 'sometimes|string',
                '_rev'                        => 'sometimes|string',
                'type'                        => 'sometimes|in:medical_test',
                'patient_id'                  => 'required|string',
                'doctor_id'                   => 'required|string',
                'medical_record_id'           => 'nullable|string',
                'test_info.test_type'         => 'required|string',
                'test_info.test_name'         => 'nullable|string',
                'test_info.ordered_date'      => 'required|date',
                'test_info.sample_collected_date' => 'nullable|date',
                'test_info.result_date'       => 'nullable|date',
                'results'                     => 'nullable|array',
                'interpretation'              => 'nullable|string',
                'status'                      => 'nullable|string',
                'lab_technician'              => 'nullable|string',
            ]);

            // Hỗ trợ POST-as-update nếu có _id + _rev
            if (!empty($data['_id']) && !empty($data['_rev'])) {
                $res = $this->svc->update($data['_id'], $data);
                return response()->json($res['data'], $res['status']);
            }

            $created = $this->svc->create($data);
            return response()->json($created, !empty($created['ok']) ? 201 : 400);
        } catch (ValidationException $ve) {
            return response()->json(['error' => 'validation_error', 'details' => $ve->errors()], 422);
        } catch (Throwable $e) {
            return $this->error($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/medical-tests/{id}",
     *     tags={"Medical Tests"},
     *     summary="Cập nhật xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="_rev", type="string", example="1-abc123"),
     *             @OA\Property(property="results", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="interpretation", type="string", example="Kết quả bình thường"),
     *             @OA\Property(property="status", type="string", example="completed")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cập nhật thành công"),
     *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function update(Request $req, string $id)
    {
        $res = $this->svc->update($id, $req->all());
        return response()->json($res['data'], $res['status']);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/medical-tests/{id}",
     *     tags={"Medical Tests"},
     *     summary="Xóa xét nghiệm y tế",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="rev", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Xóa thành công"),
     *     @OA\Response(response=400, description="Thiếu rev parameter"),
     *     @OA\Response(response=404, description="Không tìm thấy"),
     *     @OA\Response(response=409, description="Conflict"),
     *     @OA\Response(response=500, description="Lỗi server")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $rev = $request->query('rev');
        $res = $this->svc->delete($id, $rev);
        return response()->json($res['data'], $res['status']);
    }

    /**
     * Loại bỏ dấu tiếng Việt
     */
    public static function vnStrNoSign($str) {
        if (!$str) return '';
        $unicode = [
            'a'=>'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd'=>'đ',
            'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i'=>'í|ì|ỉ|ĩ|ị',
            'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
            'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D'=>'Đ',
            'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
            'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
        ];
        foreach($unicode as $nonSign=>$signs) {
            $str = preg_replace("/($signs)/u", $nonSign, $str);
        }
        return $str;
    }
}
