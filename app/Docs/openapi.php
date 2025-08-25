<?php

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="Medical Records API",
 *     version="1.0.0",
 *     description="Clinic API (CouchDB + JWT)"
 *   ),
 *   @OA\Server(
 *     url="http://127.0.0.1:9000",
 *     description="Local dev"
 *   )
 * )
 */

/** Security: Bearer JWT */
 /**
  * @OA\SecurityScheme(
  *   securityScheme="bearerAuth",
  *   type="http",
  *   scheme="bearer",
  *   bearerFormat="JWT"
  * )
  */

/** ✅ Ít nhất 1 endpoint (PathItem) */
 /**
  * @OA\Post(
  *   path="/api/v1/auth/login",
  *   tags={"Auth"},
  *   summary="Đăng nhập",
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\JsonContent(
  *       required={"username","password"},
  *       @OA\Property(property="username", type="string", example="admin"),
  *       @OA\Property(property="password", type="string", example="admin123")
  *     )
  *   ),
  *   @OA\Response(response=200, description="OK"),
  *   @OA\Response(response=401, description="Unauthorized")
  * )
  */

/** Ví dụ thêm 1 GET để chắc chắn */
 /**
  * @OA\Get(
  *   path="/api/v1/patients",
  *   tags={"Patients"},
  *   summary="Danh sách bệnh nhân",
  *   @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
  *   @OA\Parameter(name="skip",  in="query", @OA\Schema(type="integer")),
  *   @OA\Response(response=200, description="OK"),
  *   security={{"bearerAuth": {}}}
  * )
  */
