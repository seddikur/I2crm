<?php

namespace app\rbac\rules;

use yii\rbac\Rule;

/**
 * Правило для проверки, является ли текущий пользователь владельцем записи
 */
class AuthorRule extends Rule
{
    public $name = 'isAuthor';

    /**
     * @param int $user ID пользователя.
     * @param \yii\rbac\Item $item роль или разрешение, с которым связано правило
     * @param array $params параметры, переданные через ManagerInterface::checkAccess().
     * @return bool разрешен ли доступ текущему пользователю.
     */
    public function execute($user, $item, $params)
    {
        return isset($params['user']) ? $params['user']->id == $user : false;
    }
} 