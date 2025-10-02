<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Medical Records API",
 *   version="1.0.0",
 *   description="Clinic API (CouchDB + JWT)"
 * )
 *
 * @OA\Server(
 *   url="/",
 *   description="Current host"
 * )
 *
 * @OA\Server(
 *   url="http://127.0.0.1:9000",
 *   description="Local dev"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 */
final class OpenApiSpec {}
