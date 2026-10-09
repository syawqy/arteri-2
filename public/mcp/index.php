<?php

/**
 * MCP HTTP Transport Entry Point
 *
 * Web server should route /mcp/* requests to this file.
 * For Nginx: try_files $uri $uri/ /mcp/index.php?$query_string;
 */

require_once dirname(__DIR__) . '/index.php';
