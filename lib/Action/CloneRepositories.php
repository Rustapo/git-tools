<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL
 * @package  GitTools
 */

namespace Horde\GitTools\Action;

/**
 * Clone all repositories existing in specified organization.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   GitTools
 */
class CloneRepositories extends Base
{
    const MODE = 0775;
    const HTTPS_GITURL = 'https://github.com';

    /**
     * Clones the specified package/repository.
     *
     * @param  string  $package  The repository name.
     * @param  boolean $app      If repository is an application, set to true.
     */
    public function run($package = '', $app = false)
    {
        // @todo validate the package name.
        //       validate the same type of checkout (dev/anon)?

        // Ensure the base directory exists.
        if (!file_exists($this->_params['git_base'])) {
            mkdir($this->_params['git_base'], self::MODE, true);
        }

        // Is this a developer checkout or anon?
        if (!empty($this->_user)) {
            // Do a developer checkout.
            // @todo
        } else {
            // Anon.
            $source = self::HTTPS_GITURL . '/' . $this->_params['org'] . '/' . $package . '.git';
            $target = $this->_params['git_base'] . '/' . ($app ? 'applications/' : '') . $package;
            passthru("git clone $source $target");
        }
    }
}
