<?php

/**
 * @file classes/file/PublicFileManager.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicFileManager
 * @ingroup file
 *
 * @brief Wrapper class for uploading files to a site/press' public directory.
 */

namespace APP\file;

use PKP\config\Config;
use PKP\file\PKPPublicFileManager;

class PublicFileManager extends PKPPublicFileManager
{
    /**
     * @copydoc PKPPublicFileManager::getContextFilesPath()
     */
    public function getContextFilesPath($contextId)
    {
        return Config::getVar('files', 'public_files_dir') . '/presses/' . (int) $contextId;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\file\PublicFileManager', '\PublicFileManager');
}
