<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\UserInitializer;

/**
 * Контроллер для управления пользователями из консоли
 */
class UserController extends Controller
{
    /**
     * Инициализация начальных пользователей системы
     */
    public function actionInit()
    {
        echo "Инициализация пользователей...\n";
        UserInitializer::initUsers();
        echo "Пользователи успешно созданы.\n";
        
        // Назначаем роли, если есть менеджер авторизации
        if (Yii::$app->has('authManager')) {
            $auth = Yii::$app->authManager;
            
            // Проверяем, есть ли роли в системе
            $adminRole = $auth->getRole('admin');
            $managerRole = $auth->getRole('manager');
            $userRole = $auth->getRole('user');
            
            if ($adminRole && $managerRole && $userRole) {
                echo "Назначение ролей пользователям...\n";
                
                // Admin
                $admin_user = \app\modules\users\models\User::findOne(['username' => 'admin']);
                if ($admin_user && !$auth->getAssignment('admin', $admin_user->id)) {
                    $auth->assign($adminRole, $admin_user->id);
                    echo "Пользователю 'admin' назначена роль 'admin'\n";
                }
                
                // Manager
                $manager_user = \app\modules\users\models\User::findOne(['username' => 'manager']);
                if ($manager_user && !$auth->getAssignment('manager', $manager_user->id)) {
                    $auth->assign($managerRole, $manager_user->id);
                    echo "Пользователю 'manager' назначена роль 'manager'\n";
                }
                
                // User
                $user_user = \app\modules\users\models\User::findOne(['username' => 'user']);
                if ($user_user && !$auth->getAssignment('user', $user_user->id)) {
                    $auth->assign($userRole, $user_user->id);
                    echo "Пользователю 'user' назначена роль 'user'\n";
                }
            } else {
                echo "Внимание: роли не найдены в системе. Выполните сначала 'php yii rbac/init-rbac' для инициализации ролей.\n";
            }
        }
        
        echo "Операция завершена.\n";
    }
    
    /**
     * Создание нового пользователя
     * 
     * @param string $username Имя пользователя
     * @param string $email Email пользователя
     * @param string $password Пароль
     * @param string $role Роль (admin, manager или user)
     */
    public function actionCreate($username, $email, $password, $role = 'user')
    {
        echo "Создание пользователя '$username'...\n";
        
        $user = new \app\modules\users\models\User();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->role = $role;
        
        if ($user->save()) {
            echo "Пользователь успешно создан.\n";
            
            // Назначаем роль, если есть менеджер авторизации
            if (Yii::$app->has('authManager') && in_array($role, ['admin', 'manager', 'user'])) {
                $auth = Yii::$app->authManager;
                $roleObject = $auth->getRole($role);
                
                if ($roleObject) {
                    $auth->assign($roleObject, $user->id);
                    echo "Роль '$role' назначена пользователю.\n";
                } else {
                    echo "Внимание: роль '$role' не найдена в системе.\n";
                }
            }
        } else {
            echo "Ошибка при создании пользователя:\n";
            foreach ($user->getErrors() as $attribute => $errors) {
                echo "$attribute: " . implode(', ', $errors) . "\n";
            }
        }
    }
} 