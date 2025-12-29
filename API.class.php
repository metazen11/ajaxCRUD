<?php
/************************************************************************/
/* API.class.php - JSON REST API Layer with OpenAPI Support            */
/* Exposes ajaxCRUD tables as REST endpoints                            */
/************************************************************************/

/**
 * JSON REST API for ajaxCRUD
 * 
 * Usage:
 * 1. Create API instance: $api = new CrudAPI('tblContacts', 'pkID');
 * 2. Configure: $api->allowMethods(['GET', 'POST', 'PUT', 'DELETE']);
 * 3. Handle request: $api->handle();
 * 
 * Or use the router:
 * $router = new APIRouter();
 * $router->register('contacts', 'tblContacts', 'pkID');
 * $router->handle();
 */
class CrudAPI {
    private $table;
    private $primaryKey;
    private $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'];
    private $readOnly = false;
    private $searchableFields = [];
    private $sortableFields = [];
    private $defaultLimit = 50;
    private $maxLimit = 1000;
    private $allowedFields = []; // If set, only these fields are exposed
    private $hiddenFields = []; // Fields to hide from responses
    private $requiredFields = []; // Required fields for POST
    private $validation = [];
    private $beforeHooks = [];
    private $afterHooks = [];

    public function __construct(string $table, string $primaryKey) {
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Set allowed HTTP methods
     */
    public function allowMethods(array $methods): void {
        $this->allowedMethods = array_map('strtoupper', $methods);
    }

    /**
     * Make this API read-only (only GET)
     */
    public function readOnly(): void {
        $this->readOnly = true;
        $this->allowedMethods = ['GET'];
    }

    /**
     * Set searchable fields (for ?search=query)
     */
    public function setSearchableFields(array $fields): void {
        $this->searchableFields = $fields;
    }

    /**
     * Set sortable fields (for ?sort=field)
     */
    public function setSortableFields(array $fields): void {
        $this->sortableFields = $fields;
    }

    /**
     * Set fields to expose (whitelist)
     */
    public function setAllowedFields(array $fields): void {
        $this->allowedFields = $fields;
    }

    /**
     * Set fields to hide (blacklist)
     */
    public function setHiddenFields(array $fields): void {
        $this->hiddenFields = $fields;
    }

    /**
     * Set required fields for POST
     */
    public function setRequiredFields(array $fields): void {
        $this->requiredFields = $fields;
    }

    /**
     * Add validation rule
     */
    public function addValidation(string $field, string $rule, $param = null): void {
        if (!isset($this->validation[$field])) {
            $this->validation[$field] = [];
        }
        $this->validation[$field][] = ['rule' => $rule, 'param' => $param];
    }

    /**
     * Add before hook (executed before operation)
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param callable $callback Function to execute
     */
    public function before(string $method, callable $callback): void {
        $this->beforeHooks[strtoupper($method)][] = $callback;
    }

    /**
     * Add after hook (executed after operation)
     * @param string $method HTTP method
     * @param callable $callback Function to execute
     */
    public function after(string $method, callable $callback): void {
        $this->afterHooks[strtoupper($method)][] = $callback;
    }

    /**
     * Handle incoming API request
     */
    public function handle(): void {
        // Set JSON response header
        header('Content-Type: application/json');

        // Handle CORS
        $this->handleCORS();

        // Get HTTP method
        $method = $_SERVER['REQUEST_METHOD'];

        // Check if method is allowed
        if (!in_array($method, $this->allowedMethods)) {
            $this->error(405, "Method $method not allowed");
        }

        // Check authentication
        $auth = AuthManager::getInstance();
        $table = $this->table;

        try {
            // Route to appropriate handler
            switch ($method) {
                case 'GET':
                    if (!$auth->can_read($table)) {
                        $this->error(403, "Access denied: read permission required");
                    }
                    $this->handleGet();
                    break;

                case 'POST':
                    if (!$auth->can_write($table)) {
                        $this->error(403, "Access denied: write permission required");
                    }
                    $this->handlePost();
                    break;

                case 'PUT':
                case 'PATCH':
                    if (!$auth->can_write($table)) {
                        $this->error(403, "Access denied: write permission required");
                    }
                    $this->handlePut();
                    break;

                case 'DELETE':
                    if (!$auth->can_delete($table)) {
                        $this->error(403, "Access denied: delete permission required");
                    }
                    $this->handleDelete();
                    break;

                default:
                    $this->error(405, "Method not supported");
            }
        } catch (Exception $e) {
            $this->error(500, "Internal server error: " . $e->getMessage());
        }
    }

    /**
     * Handle GET request (list or single record)
     */
    private function handleGet(): void {
        $this->runHooks('before', 'GET');

        // Check if requesting single record
        $id = $this->getIdFromPath();
        
        if ($id !== null) {
            // Get single record
            $this->getSingle($id);
        } else {
            // Get collection
            $this->getCollection();
        }
    }

    /**
     * Get single record
     */
    private function getSingle(string $id): void {
        $rls = RLS::getInstance();
        $whereClause = $rls->getWhereClause($this->table);
        $params = $rls->getParameters($this->table);

        if ($whereClause) {
            $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? AND $whereClause";
            $params = array_merge([$id], $params);
        } else {
            $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
            $params = [$id];
        }

        $result = q($sql, $params);

        if (empty($result)) {
            $this->error(404, "Record not found");
        }

        $record = $this->filterFields($result[0]);
        $this->runHooks('after', 'GET', $record);
        $this->success($record);
    }

    /**
     * Get collection of records
     */
    private function getCollection(): void {
        $rls = RLS::getInstance();
        $baseWhere = $rls->getWhereClause($this->table);
        $baseParams = $rls->getParameters($this->table);

        // Parse query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min($this->maxLimit, max(1, intval($_GET['limit'] ?? $this->defaultLimit)));
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? $this->primaryKey;
        $order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Build WHERE clause
        $whereParts = [];
        $params = [];

        if ($baseWhere) {
            $whereParts[] = "($baseWhere)";
            $params = array_merge($params, $baseParams);
        }

        // Add search
        if ($search && !empty($this->searchableFields)) {
            $searchParts = [];
            foreach ($this->searchableFields as $field) {
                $searchParts[] = "`$field` LIKE ?";
                $params[] = "%$search%";
            }
            $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        // Add field filters (e.g., ?status=active)
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['page', 'limit', 'search', 'sort', 'order']) && $key !== '') {
                $field = escapeIdentifier($key);
                $whereParts[] = "`$field` = ?";
                $params[] = $value;
            }
        }

        $whereClause = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';

        // Validate sort field
        if (!empty($this->sortableFields) && !in_array($sort, $this->sortableFields)) {
            $sort = $this->primaryKey;
        }
        $sort = escapeIdentifier($sort);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $whereClause";
        $countResult = q($countSql, $params);
        $total = $countResult[0]['total'] ?? 0;

        // Get records
        $sql = "SELECT * FROM `{$this->table}` $whereClause ORDER BY `$sort` $order LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $records = q($sql, $params);

        // Filter fields
        $records = array_map([$this, 'filterFields'], $records);

        $this->runHooks('after', 'GET', $records);

        $this->success([
            'data' => $records,
            'meta' => [
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int)ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Handle POST request (create record)
     */
    private function handlePost(): void {
        $this->runHooks('before', 'POST');

        $data = $this->getRequestBody();

        // Validate required fields
        foreach ($this->requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->error(400, "Missing required field: $field");
            }
        }

        // Validate fields
        foreach ($data as $field => $value) {
            if (isset($this->validation[$field])) {
                foreach ($this->validation[$field] as $rule) {
                    $error = $this->validateField($field, $value, $rule['rule'], $rule['param']);
                    if ($error) {
                        $this->error(400, $error);
                    }
                }
            }
        }

        // Build INSERT query
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $fieldList = '`' . implode('`,`', $fields) . '`';

        $sql = "INSERT INTO `{$this->table}` ($fieldList) VALUES ($placeholders)";
        qr($sql, $values);

        // Get inserted ID
        $insertId = lastInsertId();

        // Log audit
        auditLog()->logInsert($this->table, $insertId, $data);

        // Get created record
        $record = q("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?", [$insertId])[0] ?? null;
        $record = $this->filterFields($record);

        $this->runHooks('after', 'POST', $record);

        $this->success($record, 201);
    }

    /**
     * Handle PUT request (update record)
     */
    private function handlePut(): void {
        $this->runHooks('before', 'PUT');

        $id = $this->getIdFromPath();
        if ($id === null) {
            $this->error(400, "Record ID required");
        }

        // Get existing record for audit
        $oldRecord = q("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?", [$id])[0] ?? null;
        if (!$oldRecord) {
            $this->error(404, "Record not found");
        }

        $data = $this->getRequestBody();

        // Validate fields
        foreach ($data as $field => $value) {
            if (isset($this->validation[$field])) {
                foreach ($this->validation[$field] as $rule) {
                    $error = $this->validateField($field, $value, $rule['rule'], $rule['param']);
                    if ($error) {
                        $this->error(400, $error);
                    }
                }
            }
        }

        // Build UPDATE query
        $setParts = [];
        $params = [];
        foreach ($data as $field => $value) {
            $setParts[] = "`$field` = ?";
            $params[] = $value;
        }
        $params[] = $id;

        $setClause = implode(', ', $setParts);
        $sql = "UPDATE `{$this->table}` SET $setClause WHERE `{$this->primaryKey}` = ?";
        qr($sql, $params);

        // Log audit
        auditLog()->logUpdate($this->table, $id, $oldRecord, array_merge($oldRecord, $data));

        // Get updated record
        $record = q("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?", [$id])[0] ?? null;
        $record = $this->filterFields($record);

        $this->runHooks('after', 'PUT', $record);

        $this->success($record);
    }

    /**
     * Handle DELETE request
     */
    private function handleDelete(): void {
        $this->runHooks('before', 'DELETE');

        $id = $this->getIdFromPath();
        if ($id === null) {
            $this->error(400, "Record ID required");
        }

        // Get existing record for audit
        $oldRecord = q("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?", [$id])[0] ?? null;
        if (!$oldRecord) {
            $this->error(404, "Record not found");
        }

        // Delete
        qr("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?", [$id]);

        // Log audit
        auditLog()->logDelete($this->table, $id, $oldRecord);

        $this->runHooks('after', 'DELETE', $oldRecord);

        $this->success(['message' => 'Record deleted', 'id' => $id]);
    }

    /**
     * Extract ID from path (e.g., /api/contacts/123)
     */
    private function getIdFromPath(): ?string {
        $path = $_GET['id'] ?? null;
        if ($path === null) {
            // Try to extract from URL path
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $parts = explode('/', trim($requestUri, '/'));
            $path = end($parts);
            if (is_numeric($path) || preg_match('/^[a-zA-Z0-9_-]+$/', $path)) {
                return $path;
            }
            return null;
        }
        return $path;
    }

    /**
     * Get request body as array
     */
    private function getRequestBody(): array {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error(400, "Invalid JSON in request body");
        }

        return $data ?? [];
    }

    /**
     * Filter fields based on allowedFields/hiddenFields
     */
    private function filterFields(array $record): array {
        if (!empty($this->allowedFields)) {
            $record = array_intersect_key($record, array_flip($this->allowedFields));
        }

        if (!empty($this->hiddenFields)) {
            $record = array_diff_key($record, array_flip($this->hiddenFields));
        }

        return $record;
    }

    /**
     * Validate a field value
     */
    private function validateField(string $field, $value, string $rule, $param = null): ?string {
        switch ($rule) {
            case 'required':
                if ($value === '' || $value === null) {
                    return "$field is required";
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "$field must be a valid email";
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return "$field must be a valid URL";
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    return "$field must be numeric";
                }
                break;

            case 'min':
                if (is_numeric($value) && $value < $param) {
                    return "$field must be at least $param";
                }
                break;

            case 'max':
                if (is_numeric($value) && $value > $param) {
                    return "$field must be at most $param";
                }
                break;

            case 'minlength':
                if (strlen($value) < $param) {
                    return "$field must be at least $param characters";
                }
                break;

            case 'maxlength':
                if (strlen($value) > $param) {
                    return "$field must be at most $param characters";
                }
                break;
        }

        return null;
    }

    /**
     * Run hooks
     */
    private function runHooks(string $when, string $method, &$data = null): void {
        $hooks = $when === 'before' ? $this->beforeHooks : $this->afterHooks;
        
        if (isset($hooks[$method])) {
            foreach ($hooks[$method] as $callback) {
                $callback($data);
            }
        }
    }

    /**
     * Handle CORS
     */
    private function handleCORS(): void {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            exit(0);
        }
    }

    /**
     * Send success response
     */
    private function success($data, int $code = 200): void {
        http_response_code($code);
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send error response
     */
    private function error(int $code, string $message): void {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Generate OpenAPI schema for this endpoint
     */
    public function getOpenAPISchema(string $pathPrefix = '/api'): array {
        $fields = $this->getTableSchema();
        $path = "$pathPrefix/{$this->table}";

        $schema = [
            'paths' => []
        ];

        // GET collection
        if (in_array('GET', $this->allowedMethods)) {
            $schema['paths'][$path]['get'] = [
                'summary' => "List {$this->table} records",
                'parameters' => [
                    ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                    ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer']],
                    ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ['name' => 'sort', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ['name' => 'order', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['ASC', 'DESC']]],
                ],
                'responses' => [
                    '200' => ['description' => 'Success']
                ]
            ];

            // GET single
            $schema['paths']["$path/{id}"]['get'] = [
                'summary' => "Get single {$this->table} record",
                'parameters' => [
                    ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]
                ],
                'responses' => [
                    '200' => ['description' => 'Success'],
                    '404' => ['description' => 'Not found']
                ]
            ];
        }

        // POST
        if (in_array('POST', $this->allowedMethods)) {
            $schema['paths'][$path]['post'] = [
                'summary' => "Create {$this->table} record",
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'object']
                        ]
                    ]
                ],
                'responses' => [
                    '201' => ['description' => 'Created']
                ]
            ];
        }

        // PUT
        if (in_array('PUT', $this->allowedMethods)) {
            $schema['paths']["$path/{id}"]['put'] = [
                'summary' => "Update {$this->table} record",
                'parameters' => [
                    ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]
                ],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'object']
                        ]
                    ]
                ],
                'responses' => [
                    '200' => ['description' => 'Updated']
                ]
            ];
        }

        // DELETE
        if (in_array('DELETE', $this->allowedMethods)) {
            $schema['paths']["$path/{id}"]['delete'] = [
                'summary' => "Delete {$this->table} record",
                'parameters' => [
                    ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]
                ],
                'responses' => [
                    '200' => ['description' => 'Deleted']
                ]
            ];
        }

        return $schema;
    }

    /**
     * Get table schema
     */
    private function getTableSchema(): array {
        global $DB_DRIVER;
        $fields = [];

        if ($DB_DRIVER === 'mysql') {
            $result = q("DESCRIBE `{$this->table}`");
            foreach ($result as $row) {
                $fields[$row['Field']] = $row['Type'];
            }
        }

        return $fields;
    }
}

/**
 * API Router - manages multiple API endpoints
 */
class APIRouter {
    private $endpoints = [];
    private $basePath = '/api';

    public function setBasePath(string $path): void {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * Register an API endpoint
     * @param string $name Endpoint name (e.g., 'contacts')
     * @param string $table Database table
     * @param string $primaryKey Primary key field
     * @param callable|null $configurator Optional callback to configure the CrudAPI instance
     */
    public function register(string $name, string $table, string $primaryKey, ?callable $configurator = null): CrudAPI {
        $api = new CrudAPI($table, $primaryKey);
        
        if ($configurator) {
            $configurator($api);
        }

        $this->endpoints[$name] = $api;
        return $api;
    }

    /**
     * Handle incoming request
     */
    public function handle(): void {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        
        // Remove base path
        if (strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }

        $path = trim($path, '/');
        $parts = explode('/', $path);
        $endpoint = $parts[0] ?? '';

        if (!isset($this->endpoints[$endpoint])) {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
        }

        $this->endpoints[$endpoint]->handle();
    }

    /**
     * Generate full OpenAPI spec for all endpoints
     */
    public function getOpenAPISpec(array $info = []): array {
        $spec = [
            'openapi' => '3.0.0',
            'info' => array_merge([
                'title' => 'ajaxCRUD API',
                'version' => '1.0.0',
                'description' => 'Auto-generated REST API from ajaxCRUD'
            ], $info),
            'servers' => [
                ['url' => $this->basePath]
            ],
            'paths' => []
        ];

        foreach ($this->endpoints as $name => $api) {
            $endpointSchema = $api->getOpenAPISchema($this->basePath);
            $spec['paths'] = array_merge($spec['paths'], $endpointSchema['paths']);
        }

        return $spec;
    }

    /**
     * Output OpenAPI spec as JSON
     */
    public function outputOpenAPISpec(array $info = []): void {
        header('Content-Type: application/json');
        echo json_encode($this->getOpenAPISpec($info), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/**
 * Helper function to get last insert ID
 */
function lastInsertId() {
    global $conn;
    if ($conn instanceof PDO) {
        return $conn->lastInsertId();
    }
    return null;
}
