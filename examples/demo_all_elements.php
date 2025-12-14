<?php
/**
 * ajaxCRUD v7.0 - Form Elements Showcase
 *
 * Demonstrates each form element type individually so you can see
 * how they look and interact with them.
 */

// Use SQLite for portable testing
$DB_DRIVER = 'sqlite';
$DB_CONFIG = [
    'sqlite' => [
        'path' => __DIR__ . '/demo_database.sqlite',
    ],
];

require_once(__DIR__ . '/../preheader.php');
require_once(__DIR__ . '/../ajaxCRUD.class.php');

// Initialize demo table
$db = getDB();
$db->exec("DROP TABLE IF EXISTS tblElementDemo");
$db->exec("CREATE TABLE tblElementDemo (
    pkID INTEGER PRIMARY KEY AUTOINCREMENT,
    fldValue TEXT
)");
$db->exec("INSERT INTO tblElementDemo (fldValue) VALUES ('Sample Value')");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ajaxCRUD v7.0 - Form Elements Showcase</title>
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="../css/default.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
            padding: 30px 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(to right, #2c3e50, #34495e);
            padding: 30px 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border-left: 5px solid #3498db;
        }

        header h1 {
            color: #ecf0f1;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        header p {
            color: #95a5a6;
            font-size: 15px;
        }

        .elements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .element-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .element-header {
            background: linear-gradient(to right, #2c3e50, #34495e);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .element-header h3 {
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .element-header .element-type {
            background: #3498db;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .element-body {
            padding: 25px;
        }

        .element-demo {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .element-code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .element-code .method {
            color: #3498db;
        }

        .element-code .string {
            color: #2ecc71;
        }

        .element-code .comment {
            color: #7f8c8d;
        }

        footer {
            text-align: center;
            padding: 30px 20px;
            margin-top: 30px;
        }

        footer p {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
        }

        /* Form element styles */
        .demo-label {
            display: block;
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-row {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* Specific element demos */
        .toggle-demo label {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>ajaxCRUD v7.0 - Form Elements Showcase</h1>
            <p>Interactive demonstration of all available form element types</p>
        </header>

        <div class="elements-grid">
            <!-- Text Input -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Text Input</h3>
                    <span class="element-type">Basic</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Name</span>
                            <input type="text" value="John Doe" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="comment">// Default - no special config needed</span>
<span class="method">$tbl</span>->displayAs("fldName", <span class="string">"Name"</span>);</div>
                </div>
            </div>

            <!-- Email Input -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Email Input</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Email Address</span>
                            <input type="email" value="john@example.com" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldEmail"</span>, <span class="string">"email"</span>);</div>
                </div>
            </div>

            <!-- URL Input -->
            <div class="element-card">
                <div class="element-header">
                    <h3>URL Input</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Website</span>
                            <input type="url" value="https://example.com" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldWebsite"</span>, <span class="string">"url"</span>);</div>
                </div>
            </div>

            <!-- Phone Input -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Phone Input</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Phone Number</span>
                            <input type="tel" value="555-123-4567" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldPhone"</span>, <span class="string">"tel"</span>);</div>
                </div>
            </div>

            <!-- Number Input -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Number Input</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Quantity</span>
                            <input type="number" value="25" min="0" step="1" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldQty"</span>, <span class="string">"number"</span>);</div>
                </div>
            </div>

            <!-- Decimal/Price Input -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Decimal / Price</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Price</span>
                            <input type="number" value="299.99" min="0" step="0.01" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldPrice"</span>, <span class="string">"decimal"</span>);</div>
                </div>
            </div>

            <!-- Date Picker -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Date Picker</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Birth Date</span>
                            <input type="date" value="1990-05-15" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldDate"</span>, <span class="string">"date"</span>);</div>
                </div>
            </div>

            <!-- DateTime Picker -->
            <div class="element-card">
                <div class="element-header">
                    <h3>DateTime Picker</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Meeting</span>
                            <input type="datetime-local" value="2024-01-20T09:00" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldMeeting"</span>, <span class="string">"datetime"</span>);</div>
                </div>
            </div>

            <!-- Time Picker -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Time Picker</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Start Time</span>
                            <input type="time" value="09:30" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldTime"</span>, <span class="string">"time"</span>);</div>
                </div>
            </div>

            <!-- Color Picker -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Color Picker</h3>
                    <span class="element-type">HTML5</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div class="demo-row">
                            <span class="demo-label">Theme Color</span>
                            <input type="color" value="#3498db">
                            <span style="color: #666; font-size: 13px;">#3498db</span>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->modifyFieldWithClass(<span class="string">"fldColor"</span>, <span class="string">"color"</span>);</div>
                </div>
            </div>

            <!-- Dropdown -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Dropdown Select</h3>
                    <span class="element-type">Selection</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Priority</span>
                            <select style="width: 100%;">
                                <option>Low</option>
                                <option selected>Medium</option>
                                <option>High</option>
                                <option>Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->defineAllowableValues(<span class="string">"fldPriority"</span>,
    [<span class="string">'low'</span>, <span class="string">'medium'</span>, <span class="string">'high'</span>, <span class="string">'critical'</span>]);</div>
                </div>
            </div>

            <!-- Radio Buttons -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Radio Buttons</h3>
                    <span class="element-type">Selection</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div>
                            <span class="demo-label">Status</span>
                            <div class="radio-group-inline">
                                <label class="radio-label"><input type="radio" name="status"> <span class="radio-text">Pending</span></label>
                                <label class="radio-label"><input type="radio" name="status" checked> <span class="radio-text">Active</span></label>
                                <label class="radio-label"><input type="radio" name="status"> <span class="radio-text">Complete</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->defineRadioButtons(<span class="string">"fldStatus"</span>, [
    <span class="string">'pending'</span> => <span class="string">'Pending'</span>,
    <span class="string">'active'</span> => <span class="string">'Active'</span>,
    <span class="string">'complete'</span> => <span class="string">'Complete'</span>
], true); <span class="comment">// true = inline</span></div>
                </div>
            </div>

            <!-- Toggle Switch -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Toggle Switch</h3>
                    <span class="element-type">Boolean</span>
                </div>
                <div class="element-body">
                    <div class="element-demo toggle-demo">
                        <div>
                            <span class="demo-label">Active Status</span>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->defineToggle(<span class="string">"fldActive"</span>, <span class="string">"1"</span>, <span class="string">"0"</span>);</div>
                </div>
            </div>

            <!-- Multi-Select -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Multi-Select</h3>
                    <span class="element-type">Selection</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Tags</span>
                            <select multiple class="multi-select" style="width: 100%;">
                                <option selected>Urgent</option>
                                <option>Important</option>
                                <option selected>VIP</option>
                                <option>Follow-up</option>
                                <option>Archived</option>
                            </select>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->defineMultiSelect(<span class="string">"fldTags"</span>, [
    <span class="string">'urgent'</span> => <span class="string">'Urgent'</span>,
    <span class="string">'important'</span> => <span class="string">'Important'</span>,
    <span class="string">'vip'</span> => <span class="string">'VIP'</span>
], <span class="string">','</span>); <span class="comment">// comma separator</span></div>
                </div>
            </div>

            <!-- Range Slider -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Range Slider</h3>
                    <span class="element-type">Numeric</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Rating</span>
                            <div class="range-wrapper">
                                <input type="range" min="0" max="100" value="75" id="ratingSlider" oninput="document.getElementById('ratingValue').textContent = this.value">
                                <span class="range-value" id="ratingValue">75</span>
                            </div>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->defineRange(<span class="string">"fldRating"</span>,
    0, 100, 5, true); <span class="comment">// min, max, step, show_value</span></div>
                </div>
            </div>

            <!-- Password -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Password Field</h3>
                    <span class="element-type">Secure</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Password</span>
                            <input type="password" value="secretpassword" style="width: 100%;">
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->setPassword(<span class="string">"fldPassword"</span>, false);
<span class="comment">// false = no confirmation field</span></div>
                </div>
            </div>

            <!-- Rich Text -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Rich Text Editor</h3>
                    <span class="element-type">WYSIWYG</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Notes</span>
                            <div class="richtext-wrapper">
                                <div class="richtext-toolbar">
                                    <button type="button"><b>B</b></button>
                                    <button type="button"><i>I</i></button>
                                    <button type="button"><u>U</u></button>
                                </div>
                                <div class="richtext-editor" contenteditable="true">
                                    <b>VIP Customer</b> - Priority support enabled.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->setRichText(<span class="string">"fldNotes"</span>, <span class="string">"full"</span>);
<span class="comment">// 'basic' or 'full' toolbar</span></div>
                </div>
            </div>

            <!-- Textarea -->
            <div class="element-card">
                <div class="element-header">
                    <h3>Textarea</h3>
                    <span class="element-type">Basic</span>
                </div>
                <div class="element-body">
                    <div class="element-demo">
                        <div style="width: 100%;">
                            <span class="demo-label">Description</span>
                            <textarea rows="3" style="width: 100%;">Multi-line text content goes here...</textarea>
                        </div>
                    </div>
                    <div class="element-code"><span class="method">$tbl</span>->setTextAreaSize(<span class="string">"fldDesc"</span>, 60, 4);
<span class="comment">// width in chars, height in rows</span></div>
                </div>
            </div>

        </div>

        <footer>
            <p>ajaxCRUD v7.0 - PHP CRUD Framework with AJAX Auto-Save</p>
            <p style="margin-top: 10px;">PHP <?php echo phpversion(); ?> | Driver: <?php echo getDBDriver(); ?></p>
        </footer>
    </div>
</body>
</html>
