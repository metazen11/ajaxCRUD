<?php
/************************************************************************/
/* API Demo - REST API with OpenAPI Spec                                */
/* Access contacts via JSON REST API                                    */
/************************************************************************/

session_start();
require_once('../preheader.php');
require_once('../Auth.class.php');
require_once('../RLS.class.php');
require_once('../AuditLog.class.php');
require_once('../API.class.php');

// Simulate logged-in user
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'api@example.com';
    $_SESSION['role'] = 'admin';
}

// Setup Auth
$rbac = new RoleBasedRBAC($_SESSION['user_id'], $_SESSION['role']);
AuthManager::getInstance()->init($rbac);

// Setup Audit
AuditLog::getInstance()->enable();
AuditLog::getInstance()->setUser($_SESSION['username']);

// Check if this is an API request
$isApiRequest = isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] !== 'GET' || isset($_GET['id']);

if ($isApiRequest) {
    // Handle API requests
    $router = new APIRouter();
    $router->setBasePath('/examples/api-demo.php');

    // Register contacts endpoint
    $router->register('contacts', 'tblContacts', 'pkContactID', function($api) {
        // Configure API
        $api->setSearchableFields(['fldContactName', 'fldEmail', 'fldCompany']);
        $api->setSortableFields(['fldContactName', 'fldEmail', 'fldCompany', 'pkContactID']);
        $api->setRequiredFields(['fldContactName', 'fldEmail']);
        
        // Validation
        $api->addValidation('fldContactName', 'required');
        $api->addValidation('fldContactName', 'minlength', 2);
        $api->addValidation('fldEmail', 'required');
        $api->addValidation('fldEmail', 'email');
        
        // Hide sensitive fields (if any)
        // $api->setHiddenFields(['password', 'secret_token']);
    });

    // Check if requesting OpenAPI spec
    if (isset($_GET['openapi'])) {
        $router->outputOpenAPISpec([
            'title' => 'ajaxCRUD Contacts API',
            'version' => '1.0.0',
            'description' => 'Auto-generated REST API for contact management'
        ]);
    } else {
        $router->handle();
    }
    exit;
}

// Otherwise show the demo page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REST API Demo - ajaxCRUD v7.1</title>
    <link rel="stylesheet" href="../css/default.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0 0 10px 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        .endpoint-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85em;
            margin-right: 10px;
        }
        .method.get { background: #61affe; color: white; }
        .method.post { background: #49cc90; color: white; }
        .method.put { background: #fca130; color: white; }
        .method.delete { background: #f93e3e; color: white; }
        .url {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            margin: 10px 0;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #11998e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn:hover {
            background: #0e8072;
        }
        .response-area {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        .response-area.show {
            display: block;
        }
        .param-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }
        .param-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .param-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔌 REST API Demo</h1>
        <p>Full CRUD API with OpenAPI specification</p>
    </div>

    <div class="container">
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h2>Quick Links</h2>
            <a href="?openapi=1" class="btn" target="_blank">📄 OpenAPI Spec (JSON)</a>
            <a href="demo_supabase_features.php" class="btn">← Back to UI Demo</a>
            <a href="https://editor.swagger.io/" class="btn" target="_blank">🔗 Swagger Editor</a>
        </div>

        <h2>Available Endpoints</h2>

        <!-- List Contacts -->
        <div class="endpoint-card">
            <h3><span class="method get">GET</span> List Contacts</h3>
            <div class="url">GET /examples/api-demo.php?action=contacts</div>
            
            <h4>Query Parameters</h4>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>page</code></td>
                        <td>integer</td>
                        <td>Page number (default: 1)</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td>integer</td>
                        <td>Items per page (default: 50, max: 1000)</td>
                    </tr>
                    <tr>
                        <td><code>search</code></td>
                        <td>string</td>
                        <td>Search in name, email, company</td>
                    </tr>
                    <tr>
                        <td><code>sort</code></td>
                        <td>string</td>
                        <td>Sort field (fldContactName, fldEmail, etc.)</td>
                    </tr>
                    <tr>
                        <td><code>order</code></td>
                        <td>string</td>
                        <td>Sort order: ASC or DESC</td>
                    </tr>
                </tbody>
            </table>

            <h4>Example</h4>
            <div class="code-block">curl "http://<?php echo $_SERVER['HTTP_HOST']; ?>/examples/api-demo.php?action=contacts&page=1&limit=10"</div>
            
            <button class="btn" onclick="testEndpoint('GET', '?action=contacts&page=1&limit=5', null, 'response1')">▶ Try It</button>
            <div id="response1" class="response-area"></div>
        </div>

        <!-- Get Single Contact -->
        <div class="endpoint-card">
            <h3><span class="method get">GET</span> Get Single Contact</h3>
            <div class="url">GET /examples/api-demo.php?action=contacts&id={id}</div>
            
            <h4>Example</h4>
            <div class="code-block">curl "http://<?php echo $_SERVER['HTTP_HOST']; ?>/examples/api-demo.php?action=contacts&id=1"</div>
            
            <button class="btn" onclick="testEndpoint('GET', '?action=contacts&id=1', null, 'response2')">▶ Try It</button>
            <div id="response2" class="response-area"></div>
        </div>

        <!-- Create Contact -->
        <div class="endpoint-card">
            <h3><span class="method post">POST</span> Create Contact</h3>
            <div class="url">POST /examples/api-demo.php?action=contacts</div>
            
            <h4>Request Body</h4>
            <div class="code-block">{
    "fldContactName": "Jane Doe",
    "fldEmail": "jane@example.com",
    "fldPhone": "555-0123",
    "fldCompany": "Example Inc",
    "fldActive": "1"
}</div>

            <h4>Example</h4>
            <div class="code-block">curl -X POST "http://<?php echo $_SERVER['HTTP_HOST']; ?>/examples/api-demo.php?action=contacts" \
  -H "Content-Type: application/json" \
  -d '{"fldContactName":"Jane Doe","fldEmail":"jane@example.com"}'</div>
            
            <button class="btn" onclick="testCreateContact('response3')">▶ Try It</button>
            <div id="response3" class="response-area"></div>
        </div>

        <!-- Update Contact -->
        <div class="endpoint-card">
            <h3><span class="method put">PUT</span> Update Contact</h3>
            <div class="url">PUT /examples/api-demo.php?action=contacts&id={id}</div>
            
            <h4>Request Body</h4>
            <div class="code-block">{
    "fldPhone": "555-9999",
    "fldCompany": "New Company Ltd"
}</div>

            <h4>Example</h4>
            <div class="code-block">curl -X PUT "http://<?php echo $_SERVER['HTTP_HOST']; ?>/examples/api-demo.php?action=contacts&id=1" \
  -H "Content-Type: application/json" \
  -d '{"fldPhone":"555-9999"}'</div>
            
            <button class="btn" onclick="testUpdateContact('response4')">▶ Try It</button>
            <div id="response4" class="response-area"></div>
        </div>

        <!-- Delete Contact -->
        <div class="endpoint-card">
            <h3><span class="method delete">DELETE</span> Delete Contact</h3>
            <div class="url">DELETE /examples/api-demo.php?action=contacts&id={id}</div>
            
            <h4>Example</h4>
            <div class="code-block">curl -X DELETE "http://<?php echo $_SERVER['HTTP_HOST']; ?>/examples/api-demo.php?action=contacts&id=999"</div>
            
            <p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px;">
                ⚠️ <strong>Note:</strong> This will permanently delete the record. Use with caution!
            </p>
        </div>

        <!-- Search Examples -->
        <div class="endpoint-card">
            <h3>🔍 Advanced Queries</h3>
            
            <h4>Search by keyword</h4>
            <div class="code-block">GET /examples/api-demo.php?action=contacts&search=john</div>
            <button class="btn" onclick="testEndpoint('GET', '?action=contacts&search=john&limit=5', null, 'response5')">▶ Try It</button>
            <div id="response5" class="response-area"></div>

            <h4>Sort by name (descending)</h4>
            <div class="code-block">GET /examples/api-demo.php?action=contacts&sort=fldContactName&order=DESC&limit=5</div>
            <button class="btn" onclick="testEndpoint('GET', '?action=contacts&sort=fldContactName&order=DESC&limit=5', null, 'response6')">▶ Try It</button>
            <div id="response6" class="response-area"></div>

            <h4>Filter by status</h4>
            <div class="code-block">GET /examples/api-demo.php?action=contacts&fldActive=1&limit=5</div>
            <button class="btn" onclick="testEndpoint('GET', '?action=contacts&fldActive=1&limit=5', null, 'response7')">▶ Try It</button>
            <div id="response7" class="response-area"></div>
        </div>

        <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #2196F3;">
            <h3 style="margin-top: 0;">📘 OpenAPI Integration</h3>
            <p>This API automatically generates an OpenAPI 3.0 specification. You can:</p>
            <ol>
                <li>View the spec: <a href="?openapi=1" target="_blank">api-demo.php?openapi=1</a></li>
                <li>Copy the JSON and import it into <a href="https://editor.swagger.io/" target="_blank">Swagger Editor</a></li>
                <li>Generate client libraries using <a href="https://openapi-generator.tech/" target="_blank">OpenAPI Generator</a></li>
                <li>Use tools like Postman or Insomnia for testing</li>
            </ol>
        </div>
    </div>

    <script>
        function testEndpoint(method, url, body, responseId) {
            const responseEl = document.getElementById(responseId);
            responseEl.textContent = 'Loading...';
            responseEl.classList.add('show');

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            if (body) {
                options.body = JSON.stringify(body);
            }

            fetch(url, options)
                .then(response => response.json())
                .then(data => {
                    responseEl.textContent = JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    responseEl.textContent = 'Error: ' + error.message;
                });
        }

        function testCreateContact(responseId) {
            const randomNum = Math.floor(Math.random() * 10000);
            const newContact = {
                fldContactName: `Test User ${randomNum}`,
                fldEmail: `test${randomNum}@example.com`,
                fldPhone: `555-${randomNum}`,
                fldCompany: 'API Test Co',
                fldActive: '1'
            };
            testEndpoint('POST', '?action=contacts', newContact, responseId);
        }

        function testUpdateContact(responseId) {
            // First get a contact, then update it
            fetch('?action=contacts&limit=1')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.data && result.data.data.length > 0) {
                        const contact = result.data.data[0];
                        const id = contact.pkContactID;
                        const updateData = {
                            fldPhone: `555-${Math.floor(Math.random() * 10000)}`
                        };
                        testEndpoint('PUT', `?action=contacts&id=${id}`, updateData, responseId);
                    } else {
                        document.getElementById(responseId).textContent = 'No contacts found to update. Create one first!';
                        document.getElementById(responseId).classList.add('show');
                    }
                })
                .catch(error => {
                    document.getElementById(responseId).textContent = 'Error: ' + error.message;
                    document.getElementById(responseId).classList.add('show');
                });
        }
    </script>
</body>
</html>
