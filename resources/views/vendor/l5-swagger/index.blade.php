<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('l5-swagger.documentations.' . ($documentation ?? 'default') . '.api.title', 'API Docs') }}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation ?? 'default', 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation ?? 'default', 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation ?? 'default', 'favicon-16x16.png') }}" sizes="16x16"/>

    <style>
      html { box-sizing: border-box; overflow-y: scroll; }
      *, *:before, *:after { box-sizing: inherit; }
      body { margin: 0; background: #fafafa; }
    </style>

    @if(config('l5-swagger.defaults.ui.display.dark_mode', false))
      <style>
        body#dark-mode, #dark-mode .scheme-container { background:#1b1b1b; }
        #dark-mode .scheme-container, #dark-mode .opblock .opblock-section-header{ box-shadow:0 1px 2px 0 rgba(255,255,255,0.15); }
        #dark-mode .operation-filter-input, #dark-mode .dialog-ux .modal-ux, #dark-mode input[type=email], #dark-mode input[type=file],
        #dark-mode input[type=password], #dark-mode input[type=search], #dark-mode input[type=text], #dark-mode textarea { background:#343434; color:#e7e7e7; }
        #dark-mode .title, #dark-mode li, #dark-mode p, #dark-mode table, #dark-mode label, #dark-mode .opblock-tag,
        #dark-mode .opblock .opblock-summary-operation-id, #dark-mode .opblock .opblock-summary-path, #dark-mode .opblock .opblock-summary-path__deprecated,
        #dark-mode h1, #dark-mode h2, #dark-mode h3, #dark-mode h4, #dark-mode h5, #dark-mode .btn, #dark-mode .tab li,
        #dark-mode .parameter__name, #dark-mode .parameter__type, #dark-mode .prop-format, #dark-mode .loading-container .loading:after { color:#e7e7e7; }
        #dark-mode .opblock-summary-description { color:#fafafa; }
      </style>
    @endif
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode', false)) id="dark-mode" @endif>
  <div id="swagger-ui"></div>

  <script src="{{ l5_swagger_asset($documentation ?? 'default', 'swagger-ui-bundle.js') }}"></script>
  <script src="{{ l5_swagger_asset($documentation ?? 'default', 'swagger-ui-standalone-preset.js') }}"></script>

  {{-- 1) Xuất config từ PHP sang JSON để JS parse (tránh @json trong JS) --}}
  @php
    $swaggerUrl   = isset($urlToDocs) ? $urlToDocs : url('api-docs.json');
    $opsSorter    = $operationsSorter ?? null;
    $cfgUrl       = $configUrl ?? null;
    $valUrl       = $validatorUrl ?? null;
    $oauthCb      = route('l5-swagger.' . ($documentation ?? 'default') . '.oauth2_callback', [], $useAbsolutePath ?? false);
    $docExpand    = config('l5-swagger.defaults.ui.display.doc_expansion', 'none');
    $filter       = config('l5-swagger.defaults.ui.display.filter', false);
    $persistAuth  = config('l5-swagger.defaults.ui.authorization.persist_authorization', false);
    $schemes      = config('l5-swagger.defaults.securityDefinitions.securitySchemes', []);
    $hasOauth2    = collect($schemes)->contains(fn($s) => ($s['type'] ?? null) === 'oauth2');
    $usePkce      = (bool)config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant', false);
  @endphp

  <script id="swagger-config" type="application/json">
    {!! json_encode([
      'swaggerUrl'  => $swaggerUrl,
      'opsSorter'   => $opsSorter,
      'cfgUrl'      => $cfgUrl,
      'valUrl'      => $valUrl,
      'oauthCb'     => $oauthCb,
      'docExpand'   => $docExpand,
      'filter'      => $filter,
      'persistAuth' => $persistAuth,
      'hasOauth2'   => $hasOauth2,
      'usePkce'     => $usePkce,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
  </script>

  {{-- 2) Khởi tạo Swagger UI --}}
  <script>
    const cfg = JSON.parse(document.getElementById('swagger-config').textContent);

    window.onload = function () {
      const ui = SwaggerUIBundle({
        dom_id: '#swagger-ui',
        url: cfg.swaggerUrl,
        operationsSorter: cfg.opsSorter ?? null,
        configUrl: cfg.cfgUrl ?? null,
        validatorUrl: cfg.valUrl ?? null,
        oauth2RedirectUrl: cfg.oauthCb,

        requestInterceptor: function (request) {
          request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
          const t = localStorage.getItem('jwt');
          if (t) request.headers['Authorization'] = 'Bearer ' + t;
          return request;
        },

        presets: [ SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset ],
        plugins:  [ SwaggerUIBundle.plugins.DownloadUrl ],
        layout: 'StandaloneLayout',
        docExpansion: cfg.docExpand,
        deepLinking: true,
        filter: !!cfg.filter,
        persistAuthorization: !!cfg.persistAuth,
      });

      window.ui = ui;

      if (cfg.hasOauth2 && ui.initOAuth) {
        ui.initOAuth({ usePkceWithAuthorizationCodeGrant: !!cfg.usePkce });
      }
    };
  </script>
</body>
</html>
