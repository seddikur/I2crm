<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\rbac\rules\AuthorRule;
use app\modules\users\models\UserInitializer;

/**
 * Контроллер инициализации RBAC и пользователей
 */
class RbacController extends Controller
{
    /**
     * Инициализация ролей и правил RBAC
     */
    public function actionInitRbac()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();

        echo "Создание правил...\n";
        
        // Добавляем правило автора
        $authorRule = new AuthorRule();
        $auth->add($authorRule);
        
        echo "Создание разрешений...\n";
        
        // Создаем разрешения
        $viewProfile = $auth->createPermission('viewProfile');
        $viewProfile->description = 'Просмотр своего профиля';
        $auth->add($viewProfile);
        
        $editProfile = $auth->createPermission('editProfile');
        $editProfile->description = 'Редактирование своего профиля';
        $auth->add($editProfile);
        
        $manageUsers = $auth->createPermission('manageUsers');
        $manageUsers->description = 'Управление пользователями';
        $auth->add($manageUsers);
        
        $viewReports = $auth->createPermission('viewReports');
        $viewReports->description = 'Просмотр отчетов';
        $auth->add($viewReports);
        
        $viewAnyUser = $auth->createPermission('viewAnyUser');
        $viewAnyUser->description = 'Просмотр любого пользователя';
        $auth->add($viewAnyUser);
        
        $updateAnyUser = $auth->createPermission('updateAnyUser');
        $updateAnyUser->description = 'Редактирование любого пользователя';
        $auth->add($updateAnyUser);
        
        $viewOwnUser = $auth->createPermission('viewOwnUser');
        $viewOwnUser->description = 'Просмотр своей учетной записи';
        $viewOwnUser->ruleName = $authorRule->name;
        $auth->add($viewOwnUser);
        
        $updateOwnUser = $auth->createPermission('updateOwnUser');
        $updateOwnUser->description = 'Редактирование своей учетной записи';
        $updateOwnUser->ruleName = $authorRule->name;
        $auth->add($updateOwnUser);
        
        echo "Создание ролей...\n";
        
        // Роль обычного пользователя
        $user = $auth->createRole('user');
        $user->description = 'Обычный пользователь';
        $auth->add($user);
        $auth->addChild($user, $viewProfile);
        $auth->addChild($user, $editProfile);
        $auth->addChild($user, $viewOwnUser);
        $auth->addChild($user, $updateOwnUser);
        
        // Роль менеджера
        $manager = $auth->createRole('manager');
        $manager->description = 'Менеджер';
        $auth->add($manager);
        $auth->addChild($manager, $user);
        $auth->addChild($manager, $manageUsers);
        $auth->addChild($manager, $viewReports);
        
        // Роль администратора
        $admin = $auth->createRole('admin');
        $admin->description = 'Администратор';
        $auth->add($admin);
        $auth->addChild($admin, $manager);
        $auth->addChild($admin, $viewAnyUser);
        $auth->addChild($admin, $updateAnyUser);
        
        echo "RBAC инициализирован успешно.\n";
        
        // Инициализация пользователей
        echo "Инициализация пользователей...\n";
        UserInitializer::initUsers();
        echo "Пользователи созданы успешно.\n";
        
        // Назначение ролей пользователям
        echo "Назначение ролей...\n";
        
        // Для начала проверяем, есть ли пользователи
        $users = \app\modules\users\models\User::find()->all();
        if (!empty($users)) {
            // Admin
            $admin_user = \app\modules\users\models\User::findOne(['username' => 'admin']);
            if ($admin_user) {
                $auth->assign($admin, $admin_user->id);
                echo "Пользователю 'admin' назначена роль 'admin'\n";
            }
            
            // Manager
            $manager_user = \app\modules\users\models\User::findOne(['username' => 'manager']);
            if ($manager_user) {
                $auth->assign($manager, $manager_user->id);
                echo "Пользователю 'manager' назначена роль 'manager'\n";
            }
            
            // User
            $user_user = \app\modules\users\models\User::findOne(['username' => 'user']);
            if ($user_user) {
                $auth->assign($user, $user_user->id);
                echo "Пользователю 'user' назначена роль 'user'\n";
            }
        } else {
            echo "Ошибка: пользователи не найдены\n";
        }
        
        echo "Инициализация завершена.\n";
    }
} 