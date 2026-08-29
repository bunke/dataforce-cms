<?php

/*
 * DataForce CMS — example model.
 *
 * Delete this file once you've created your first real model.
 *
 * Naming convention:  file  AdminProducts.php
 *                    class  admin_products
 *                    table  products  (MySQL)
 *
 * Access in admin:
 *   /admin/catalog.php?tabler=products&srci=items.php&under=-1
 *
 * If the MySQL table doesn't exist yet, visit once with &crt=1
 * appended and DataForce will CREATE it from the $fld definitions.
 */

class admin_example extends AdminTable
{
    public $NAME      = 'Examples';
    public $NAME2     = 'example';
    public $ECHO_NAME = 'title';
    public $SORT      = 'sort DESC, id DESC';

    // Image handling (set IMG_NUM = 0 to disable image upload entirely)
    public $IMG_NUM         = 1;
    public $IMG_SIZE        = 150;     // thumbnail size (px)
    public $IMG_BIG_SIZE    = 1200;    // large image max width
    public $IMG_RESIZE_TYPE = 3;       // 1=width · 2=height · 3=fit square

    public $RUBS_NO_UNDER = 1;         // flat list (no parent/child tree)

    // Machine API + MCP exposure (off by default). Set to 1 for full CRUD
    // via /admin/api.php and as MCP tools via /admin/mcp.php, or 'ro' for
    // read-only. Requires 'api_token' in config. See README → Machine API.
    // public $API = 1;

    // Optional metadata for apiAction_* methods (docs + MCP inputSchema):
    // public $API_ACTIONS = [
    //     'publish' => [
    //         'description' => 'Publish an example by id.',
    //         'input'    => ['id' => ['type' => 'integer', 'description' => 'Record id']],
    //         'required' => ['id'],
    //     ],
    // ];

    public function __construct()
    {
        $this->fld = [
            new Field('title',        'Title',         C_TEXTLINE, ['showInList' => 1]),
            new Field('slug',         'URL slug',      C_TEXTLINE),
            new Field('body',         'Body',          C_TEXT),
            new Field('is_active',    'Active',        C_CHECKBOX, ['showInList' => 1, 'editInList' => 1]),
            new Field('sort',         'Sort',          C_NOGEN),
            new Field('creation_time', 'Created',      C_NOGEN),
        ];
    }

    // --- Lifecycle hooks (all optional) ---

    // Called right before INSERT with an array of Field objects holding
    // POSTed values. Validate or adjust here.
    // public function beforeAdd($fld) { }

    // Called after the row has been written and re-fetched.
    // public function afterAdd($row) { }

    // public function beforeEdit($row)   { }
    // public function afterEdit($row)    { }
    // public function beforeDelete($row) { }

    // Render a custom field value in the list (use with C_SPEC type).
    // public function showed_title($row) { return '<b>' . $row['title'] . '</b>'; }

    // Extra HTML rendered inside the edit form.
    // public function addSpFields($row, $under) { }

    // --- Machine API domain action (optional) ---
    // Exposed as POST api.php?resource=example&action=publish and as the
    // MCP tool `example_publish`. Use $GLOBALS['pdo'] with prepared
    // statements; return a JSON-serializable array. Throw DataForceApiError
    // for clean HTTP/tool errors.
    // public function apiAction_publish(array $params) {
    //     $stmt = $GLOBALS['pdo']->prepare('UPDATE example SET is_active=1 WHERE id=?');
    //     $stmt->execute([(int)($params['id'] ?? 0)]);
    //     return ['published' => true, 'id' => (int)$params['id']];
    // }
}
