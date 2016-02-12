<?php
/**
 * Wasabi CMS
 * Copyright (c) Frank Förster (http://frankfoerster.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Frank Förster (http://frankfoerster.com)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Wasabi\Core\Model\Table;

use Cake\Cache\Cache;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Wasabi\Core\Model\Entity\GroupPermission;

/**
 * Class GroupPermissionsTable
 * @property GroupsTable Groups
 * @package Wasabi\Core\Model\Table
 */
class GroupPermissionsTable extends Table
{
    /**
     * Hold a mapping of paths to plugin controller actions in cache.
     *
     * @var array
     */
    protected $_actionMap = [];

    /**
     * Initialize a table instance. Called after the constructor.
     *
     * @param array $config Configuration options passed to the constructor
     */
    public function initialize(array $config)
    {
        $this->belongsTo('Groups', [
            'className' => 'Wasabi/Core.Groups'
        ]);

        $this->addBehavior('Timestamp');
    }

    /**
     * Find all permissions for a specific $groupId.
     *
     * @param string $groupId
     * @return array|mixed
     */
    public function findAllForGroup($groupId)
    {
        if (!$groupId) {
            return [];
        }

        $permissions = Cache::remember($groupId, function () use ($groupId) {
            return $this
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'path'
                ])
                ->where([
                    'group_id' => $groupId,
                    'allowed' => true
                ])
                ->hydrate(false)
                ->toArray();
        }, 'wasabi/core/group_permissions');

        return $permissions;
    }

    /**
     * Get all permissions paths (Plugin.Controller.action) for a specific $groupId.
     *
     * @param string $groupId
     * @return array of permission paths
     */
    public function getAllPermissionPathsForGroup($groupId)
    {
        $groupPermissions = $this->find('all')
            ->select('path')
            ->where(['group_id' => $groupId])
            ->hydrate(false)
            ->toArray();

        return Hash::extract($groupPermissions, '{n}.path');
    }

    /**
     * Create all missing permissions for a specific $groupId and the
     * supplied $actionMap.
     *
     * @param string $groupId
     * @param array $actionMap
     */
    public function createMissingPermissions($groupId, array $actionMap)
    {
        $existingPaths = $this->getAllPermissionPathsForGroup($groupId);

        $missingPaths = array_diff(array_keys($actionMap), $existingPaths);

        if (empty($missingPaths)) {
            return;
        }

        $this->connection()->transactional(function () use ($missingPaths, $actionMap, $groupId) {
            foreach ($missingPaths as $missingPath) {
                $action = $actionMap[$missingPath];
                $this->save(
                    new GroupPermission([
                        'group_id' => $groupId,
                        'path' => $missingPath,
                        'plugin' => $action['plugin'],
                        'controller' => $action['controller'],
                        'action' => $action['action'],
                    ])
                );
            }
        });
    }

    /**
     * Delete all permission for a $groupId for paths (Plugin.Controller.action)
     * that are no longer present in the codebase.
     *
     * @param string $groupId
     * @param array $actionMap
     */
    public function deleteOrphans($groupId, array $actionMap)
    {
        $groupPermissions = $this->getAllPermissionPathsForGroup($groupId);

        $orphans = array_diff($groupPermissions, array_keys($actionMap));

        if (!empty($orphans)) {
            $this->deleteAll([
                'group_id' => $groupId,
                'path IN' => $orphans
            ]);
        }
    }

    /**
     * Find all existing group permissions. Structure: group_id.path.enity
     *
     * @return \Cake\Collection\CollectionInterface
     */
    public function findAllExisting()
    {
        $existingGroupPermissions = $this->find('all')->combine(
            'path',
            function ($entity) { return $entity; },
            'group_id'
        );
        return $existingGroupPermissions;
    }

    /**
     * Create a new GroupPermission entity for the provided $groupId, $path and $allowed setting.
     *
     * @param integer $groupId
     * @param string $path
     * @param bool|integer $allowed
     * @return GroupPermission
     */
    public function newEntityFor($groupId, $path, $allowed)
    {
        if (empty($this->_actionMap)) {
            $this->_actionMap = guardian()->getActionMap();
        }

        return $this->newEntity([
            'group_id' => $groupId,
            'path' => $path,
            'allowed' => $allowed,
            'plugin' => $this->_actionMap[$path]['plugin'],
            'controller' => $this->_actionMap[$path]['controller'],
            'action' => $this->_actionMap[$path]['action']
        ]);
    }
}
