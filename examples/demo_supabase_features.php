<?php
/************************************************************************/
/* Example: Complete Supabase-Level Feature Demo                       */
/* Shows Auth, RLS, Audit Log, and API working together                */
/************************************************************************/

session_start();
require_once('../preheader.php');
require_once('../ajaxCRUD.class.php');
require_once('../Auth.class.php');
require_once('../RLS.class.php');
require_once('../AuditLog.class.php');

// Simulate logged-in user (in production, use real auth)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin@example.com';
    $_SESSION['role'] = 'admin';
    $_SESSION['tenant_id'] = 1;
}

// 1. Setup Authentication & RBAC
$rbac = new RoleBasedRBAC($_SESSION['user_id'], $_SESSION['role']);

// Add custom table permissions
$rbac->addTablePermission('tblContacts', [
    'read' => true,
    'write' => true,
    'delete' => function($user, $row) {
        // Only allow deleting contacts created by current user
        return isset($row['fldCreatedBy']) && $row['fldCreatedBy'] == $user;
    }
]);

AuthManager::getInstance()->init($rbac);

// 2. Setup Row-Level Security (RLS)
$rls = RLS::getInstance();

// Example: Multi-tenant isolation
// Uncomment if your tables have a tenant_id field
// $rls->addRule('tblContacts', 'tenant_id', $_SESSION['tenant_id']);

// Example: Only show active records
// $rls->addRule('tblContacts', 'fldActive', 1);

// Example: Soft deletes - exclude deleted records
// $rls->addGlobalRule('deleted_at', null, 'IS', ['crud_audit']);

// 3. Setup Audit Log
$audit = AuditLog::getInstance();
$audit->enable();
$audit->setUser($_SESSION['username']);

// Create audit table if it doesn't exist
if (isset($_GET['setup_audit'])) {
    if (AuditLog::createTable()) {
        echo "<div style='padding:20px;background:#dff0d8;border:1px solid #d6e9c6;color:#3c763d;margin:20px;'>
            ✅ Audit log table created successfully!
        </div>";
    } else {
        echo "<div style='padding:20px;background:#f2dede;border:1px solid #ebccd1;color:#a94442;margin:20px;'>
            ⚠️ Could not create audit log table. It may already exist or there's a permission issue.
        </div>";
    }
}

// Create CRUD table
$tbl = new ajaxCRUD("Contact", "tblContacts", "pkContactID");

// Configure display
$tbl->displayAs("fldContactName", "Name");
$tbl->displayAs("fldEmail", "Email");
$tbl->displayAs("fldPhone", "Phone");
$tbl->displayAs("fldCompany", "Company");
$tbl->displayAs("fldActive", "Active");

// Field types
$tbl->modifyFieldWithClass("fldEmail", "email");
$tbl->modifyFieldWithClass("fldPhone", "tel");
$tbl->defineToggle("fldActive", "1", "0");

// Validation
$tbl->addValidationRule("fldContactName", "required");
$tbl->addValidationRule("fldContactName", "minlength", 2);
$tbl->addValidationRule("fldEmail", "required");
$tbl->addValidationRule("fldEmail", "email");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supabase-Level Features Demo - ajaxCRUD v7.1</title>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="../css/default.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2em;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1.1em;
        }
        .info-card p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status.active {
            background: #d4edda;
            color: #155724;
        }
        .status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .feature-list li {
            padding: 5px 0;
            padding-left: 25px;
            position: relative;
        }
        .feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        .audit-viewer {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .audit-viewer h2 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .crud-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 ajaxCRUD v7.1 - Supabase-Level Features</h1>
        <p>Auth/RBAC • Row-Level Security • Audit Log • REST API with OpenAPI</p>
    </div>

    <div class="container">
        <div class="info-grid">
            <div class="info-card">
                <h3>🔐 Current User</h3>
                <p><strong>User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>Role:</strong> <span class="status active"><?php echo htmlspecialchars($_SESSION['role']); ?></span></p>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
            </div>

            <div class="info-card">
                <h3>✅ Active Features</h3>
                <ul class="feature-list">
                    <li>Auth: <?php echo AuthManager::getInstance()->isEnabled() ? 'Enabled' : 'Disabled'; ?></li>
                    <li>RLS: <?php echo RLS::getInstance()->isEnabled() ? 'Enabled' : 'Disabled'; ?></li>
                    <li>Audit: <?php echo AuditLog::getInstance()->isEnabled() ? 'Enabled' : 'Disabled'; ?></li>
                </ul>
            </div>

            <div class="info-card">
                <h3>🛡️ Permissions</h3>
                <ul class="feature-list">
                    <li>Read: <?php echo AuthManager::getInstance()->can_read('tblContacts') ? 'Yes' : 'No'; ?></li>
                    <li>Write: <?php echo AuthManager::getInstance()->can_write('tblContacts') ? 'Yes' : 'No'; ?></li>
                    <li>Delete: Conditional</li>
                </ul>
            </div>

            <div class="info-card">
                <h3>📚 Resources</h3>
                <a href="api-demo.php" class="btn">REST API Demo</a>
                <a href="?setup_audit=1" class="btn btn-secondary">Setup Audit Table</a>
                <a href="../SUPABASE_FEATURES.md" class="btn btn-secondary">Documentation</a>
            </div>
        </div>

        <div class="crud-container">
            <h2>Contact Management</h2>
            <p style="color: #666; margin-bottom: 20px;">
                This table demonstrates all features working together. Try editing, adding, and deleting records. 
                All changes are automatically audited and checked against your permissions.
            </p>
            <?php $tbl->showTable(); ?>
        </div>

        <?php if (AuditLog::getInstance()->isEnabled()): ?>
        <div class="audit-viewer">
            <h2>📝 Recent Audit Log</h2>
            <p style="color: #666; margin-bottom: 15px;">Last 10 changes to the contacts table:</p>
            
            <?php
            try {
                $recentAudits = AuditLog::getInstance()->query([
                    'table' => 'tblContacts'
                ], 10);
                
                if (!empty($recentAudits)):
            ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa; text-align: left;">
                            <th style="padding: 10px; border-bottom: 2px solid #dee2e6;">Time</th>
                            <th style="padding: 10px; border-bottom: 2px solid #dee2e6;">Action</th>
                            <th style="padding: 10px; border-bottom: 2px solid #dee2e6;">Record ID</th>
                            <th style="padding: 10px; border-bottom: 2px solid #dee2e6;">User</th>
                            <th style="padding: 10px; border-bottom: 2px solid #dee2e6;">Changed Fields</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAudits as $log): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                <span class="status <?php echo strtolower($log['action']); ?>"><?php echo $log['action']; ?></span>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo htmlspecialchars($log['record_id']); ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo htmlspecialchars($log['user_id'] ?? 'N/A'); ?></td>
                            <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                <?php 
                                if ($log['changed_fields']) {
                                    $fields = json_decode($log['changed_fields'], true);
                                    echo htmlspecialchars(implode(', ', $fields));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php 
                else:
                    echo "<p style='color: #999; font-style: italic;'>No audit records yet. Make some changes to see them here!</p>";
                endif;
            } catch (Exception $e) {
                echo "<p style='color: #a94442;'>⚠️ Audit table not created yet. <a href='?setup_audit=1'>Click here to create it</a></p>";
            }
            ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 4px;">
            <h3 style="margin-top: 0;">💡 Try These Features:</h3>
            <ul style="line-height: 1.8;">
                <li><strong>Inline Editing:</strong> Click any cell to edit. Changes save automatically.</li>
                <li><strong>Add Record:</strong> Click "Add Contact" and fill in the form.</li>
                <li><strong>Delete:</strong> Try deleting a record (permission checks apply!).</li>
                <li><strong>View Audit:</strong> Scroll down to see all changes tracked in real-time.</li>
                <li><strong>API Access:</strong> Check out <a href="api-demo.php">REST API demo</a> for JSON access.</li>
            </ul>
        </div>
    </div>
</body>
</html>
