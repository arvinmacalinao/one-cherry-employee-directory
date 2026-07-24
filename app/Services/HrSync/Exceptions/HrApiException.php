<?php

namespace App\Services\HrSync\Exceptions;

use RuntimeException;

/**
 * Thrown for anything that stops a fetch from the HR REST API entirely
 * (auth failure, network error, malformed response). Per-record problems
 * (an unmapped company/department/designation) are handled downstream in
 * HrSyncService instead — they should never abort the whole run.
 */
class HrApiException extends RuntimeException {}
