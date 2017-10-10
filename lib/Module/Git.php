<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  https://www.horde.org/licenses/bsd BSD
 * @package  GitTools
 */

namespace Horde\GitTools\Module;

use Horde\GitTools\Action;
use Horde\GitTools\Action\Git\Checkout;
use Horde\GitTools\Action\Git\CloneRepositories;
use Horde\GitTools\Action\Git\Command;
use Horde\GitTools\Action\Git\Diff;
use Horde\GitTools\Action\Git\ListRemote;
use Horde\GitTools\Action\Git\Pull;
use Horde\GitTools\Action\Git\Status;
use Horde\GitTools\Exception;
use Horde\GitTools\Repositories;
use Horde_Argv_Option as Option;
use Horde_Cache as Cache;
use Horde_Cache_Storage_File as Storage_File;

use Components_Configs as Configs;

/**
 * Class for handling 'git' actions.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   https://www.horde.org/licenses/bsd BSD
 * @package   GitTools
 */
class Git extends Base
{
    /**
     * Handles the module's actions.
     *
     * @param \Components_Config $config  The configuration object
     */
    public function handle(Configs $config)
    {
        // Grab the CLI options
        $this->_params = $config->getOptions();

        // ...and the arguments.
        $arguments = $config->getArguments();

        // Exit early if we aren't actually calling any git related commands.
        if (array_shift($arguments) != 'git' || !count($arguments)) {
            // Exit early if we didn't request any actual actions.
            return false;
        }

        switch (array_shift($arguments)) {
        case 'checkout':
            if (!$branch = array_shift($arguments)) {
                throw new Exception('Missing required arguemnts to checkout.');
            }
            $this->_doCheckout($branch);
            return true;
        case 'clone':
            $this->_doClone();
            return true;
        case 'diff':
            $this->_doDiff();
            return true;
        case 'list':
            $this->_doList();
            return true;
        case 'pull':
            $this->_doPull();
            return true;
        case 'run':
            $this->_doCmd($arguments);
            return true;
        case 'status':
            $this->_doStatus();
            return true;
        }

        return false;
    }

    /**
     * Perform cloning of remote Github repositories to local copy.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doClone()
    {
        $list = new ListRemote($this->_params, $this->_dependencies);
        $repositories = $list->run();

        $action = new CloneRepositories(
            $this->_params, $this->_dependencies
        );
        foreach ($repositories as $package) {
            $action->run($package->name, $this->_isApplication($package->name));
        }
    }

    /**
     * Report diff of all repositories.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doDiff()
    {
        $action = new Diff($this->_params, $this->_dependencies);
        $action->run();
    }

    /**
     * Report git status of all repositories.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doStatus()
    {
        $action = new Status($this->_params, $this->_dependencies);
        $action->run();
    }

    /**
     * Report git status of all repositories.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doPull()
    {
        $action = new Pull($this->_params, $this->_dependencies);
        $action->run();
    }

    /**
     * Report git status of all repositories.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doList()
    {
        $action = new ListRemote($this->_params, $this->_dependencies);
        $repos = $action->run();
        $this->_dependencies->getOutput()->info(
            'Available remote repositories on ' . $this->_params['org']
        );
        foreach (array_keys($repos) as $name) {
            $this->_dependencies->getOutput()->plain($name);
        }
    }

    /**
     * Recursively checkout out $branch.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doCheckout($branch)
    {
        $action = new Checkout($this->_params, $this->_dependencies);
        $action->run($branch);
    }

    /**
     * Report git status of all repositories.
     *
     * @param  array $params  Configuration parameters.
     */
    protected function _doCmd($cmd)
    {
        $action = new Command($this->_params ,$this->_dependencies);
        $action->run($cmd);
    }

    /**
     * Get a list of all available repositories from the Github remote.
     *
     * @param  array $params  Configuration parameters.
     *
     * @return  Horde\GitTools\Repositories\Http
     */
    protected function _getRepositories()
    {
        if (!empty($this->_params['cache'])) {
            $storage = new Storage_File();
            $cache = new Cache($storage);
        }
        $repositories = new Repositories\Http($this->_params, $cache);
        $repositories->load(array(
            'org' => $params['org'],
            'user-agent' => self::USERAGENT)
        );

        return $repositories;
    }

    /**
     * Return whether or not the specified package is an application.
     * For now, this is true if the package name starts with a lower case
     * letter.
     *
     * @param string  $package_name  The package name to check.
     *
     * @return boolean True if $package_name is an applicaton.
     */
    protected function _isApplication($package_name)
    {
        return strtoupper($package_name[0]) != $package_name[0];
    }

    /* Horde_Cli_Modular methods */

    public function getOptionGroupOptions($action = null)
    {
        return array(
            new Option(
                '',
                '--use-git-get',
                array(
                    'action' => 'store_true',
                    'help'   => 'Use the \'get\' alias for updating.'
                )
            ),
            new Option(
                '',
                '--repositories',
                array(
                    'action' => 'store',
                    'help'   => 'Only perform any actions on the specified list of comma delimited repositories.'
                )
            )
        );
    }

    public function hasOptionGroup()
    {
        return true;
    }

    /**
     * Returns the title for the option group representing this module.
     *
     * @return string  The group title.
     */
    public function getOptionGroupTitle()
    {
        return 'Git actions';
    }

    /**
     * Returns the description for the option group representing this module.
     *
     * @return string  The group description.
     */
    public function getOptionGroupDescription()
    {
        return 'This command performs various git related commands on all local repositories';
    }

    /**
     * Return the options that should be explained in the context help.
     *
     * @return array A list of option help texts.
     */
    public function getContextOptionHelp($action = null)
    {
        $all_options = array('--repositories' => '');
        $options = array(
            'pull' => array('--use-git-get' => '')
        );

        if (!empty($options[$action])) {
            return array_merge(
                $all_options,
                $options[$action]
            );
        }

        return array();
    }

    /**
     * Returns additional usage title for this module.
     *
     * @return string  The usage title.
     */
    public function getTitle()
    {
        return 'git ACTION';
    }

    /**
     * Returns additional usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return 'Perform Git related actions.';
    }

    public function getActions()
    {
        return array(
            'list'                => 'Lists available remote repositories.',
            'clone'               => 'Clones all remote repositories locally.',
            'pull'                => 'Update local repositories.',
            'checkout [BRANCH]'   => 'Checkout BRANCH on all local repositories.',
            'status'              => 'Display status of all local repositories.',
            'diff'                => 'Display a diff of all local repositories.',
            'run ["GIT COMMAND"]' => 'Run [GIT COMMAND] on all local repositories.'
        );
    }

    /**
     * Return the help text for the specified action.
     *
     * @param string $action The action.
     *
     * @return string The help text.
     */
    public function getHelp()
    {
        return 'This module performs Git related actions on the locally
checked out repositories.

Available actions for this module are:' . $this->_actionFormatter();
    }

}
