<?php

namespace Wasabi\Core\Event;

use Cake\Database\Expression\QueryExpression;
use Wasabi\Core\Model\Entity\User;
use Wasabi\Core\Model\Table\UsersGroupsTable;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Wasabi\Core\Model\Table\UsersTable;
use Wasabi\Core\Wasabi;

class AuthListener implements EventListenerInterface
{
    /**
     * Returns a list of events this object is implementing.
     *
     * @return array associative array or event key names pointing to the function
     * that should be called in the object when the respective event is fired
     */
    public function implementedEvents()
    {
        return [
            'Auth.afterIdentify' => [
                'callable' => 'setupUser',
                'priority' => 1000
            ],
            'Auth.failedLogin' => [
                'callable' => 'onFailedLogin',
                'priority' => 1000
            ]
        ];
    }

    /**
     * Setup the group ids and set the password of the logged in users to store it in the session.
     * Reset failed login attempts.
     *
     * @param Event $event The Auth.afterIdentify event that was fired.
     * @param array $user The user array of the authenticated user.
     * @return array
     */
    public function setupUser(Event $event, $user)
    {
        /** @var UsersGroupsTable $UsersGroups */
        $UsersGroups = TableRegistry::get('Wasabi/Core.UsersGroups');

        // setup the group ids for the given user
        $user['group_id'] = $UsersGroups->getGroupIds($user['id']);

        $user['password_hashed'] = $UsersGroups->Users->get($user['id'], ['fields' => ['password']])->get('password');
        return $user;
    }

    /**
     * On a failed login attempt increase the failed login attempt of the corresponding user and update
     * the last failed login attempt datetime.
     *
     * @param Event $event
     * @param string $clientIp
     * @param string $loginField
     * @param string $loginFieldValue
     */
    public function onFailedLogin(Event $event, $clientIp, $loginField, $loginFieldValue)
    {
        $recognitionTime = Wasabi::setting('Core.Auth.failed_login_recognition_time');
        $maxLoginAttempts = Wasabi::setting('Core.Auth.max_login_attempts');
        $past = (new \DateTime())->modify('-' . $recognitionTime . ' minutes');

        $LoginLogs = TableRegistry::get('Wasabi/Core.LoginLogs');

        $loginLog = $LoginLogs->newEntity([
            'client_ip' => $clientIp,
            'login_field' => $loginField,
            'login_field_value' => $loginFieldValue,
            'success' => false
        ]);

        $failedLogins = $LoginLogs->find()
            ->where([
                'client_ip' => $clientIp,
                'success' => false
            ])
            ->andWhere(function (QueryExpression $exp) use ($past) {
                return $exp->gt('created', $past->format('Y-m-d H:i:s'));
            })
            ->count();

        if (($failedLogins + 1) >= $maxLoginAttempts) {
            $loginLog->set('blocked', true);
        }

        $LoginLogs->save($loginLog);
    }
}
